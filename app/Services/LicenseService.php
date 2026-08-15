<?php

namespace App\Services;

use App\Models\License;
use Illuminate\Support\Facades\Http;

class LicenseService
{
    public function activate(string $licenseKey, string $domain, string $company): array
    {
        $license = License::current();

        try {
            $response = Http::timeout(15)
                ->baseUrl(config('services.license.server_url'))
                ->post('/activate', [
                    'license_key' => $licenseKey,
                    'domain' => $domain,
                    'company' => $company,
                    'app_url' => config('app.url'),
                ]);

            if (! $response->successful()) {
                $license->update([
                    'license_key' => $licenseKey,
                    'status' => 'invalid',
                    'last_checked_at' => now(),
                    'last_response' => $response->json(),
                ]);

                return ['ok' => false, 'message' => $response->json('message', 'Activation failed.')];
            }

            $data = $response->json();

            $license->update([
                'license_key' => $licenseKey,
                'status' => $data['status'] ?? 'active',
                'plan_name' => $data['plan_name'] ?? null,
                'plan_features' => $data['plan_features'] ?? null,
                'registered_domain' => $domain,
                'registered_company' => $company,
                'activated_at' => now(),
                'expires_at' => $data['expires_at'] ?? null,
                'last_checked_at' => now(),
                'last_response' => $data,
            ]);

            return ['ok' => true, 'message' => 'License activated.'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Unable to reach the license server.'];
        }
    }

    public function check(): array
    {
        if (! config('app.license_enforced')) {
            return ['ok' => true, 'skipped' => true];
        }

        $license = License::current();

        if (! $license->license_key) {
            return ['ok' => false, 'skipped' => true];
        }

        try {
            $response = Http::timeout(15)
                ->baseUrl(config('services.license.server_url'))
                ->post('/check', [
                    'license_key' => $license->license_key,
                    'domain' => $license->registered_domain,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                $license->update([
                    'status' => $data['status'] ?? $license->status,
                    'plan_name' => $data['plan_name'] ?? $license->plan_name,
                    'plan_features' => $data['plan_features'] ?? $license->plan_features,
                    'expires_at' => $data['expires_at'] ?? $license->expires_at,
                    'last_checked_at' => now(),
                    'last_response' => $data,
                ]);
            } else {
                $license->update(['last_checked_at' => now()]);
            }
        } catch (\Throwable $e) {
            // Unreachable server: leave status as-is, don't punish for transient network issues.
            $license->update(['last_checked_at' => now()]);
        }

        // Local expiry check always applies regardless of connectivity.
        if ($license->expires_at && $license->expires_at->isPast() && $license->status !== 'expired') {
            $license->update(['status' => 'expired']);
        }

        return ['ok' => $license->status === 'active'];
    }

    public function checkForUpdates(): array
    {
        try {
            $response = Http::timeout(15)
                ->baseUrl(config('services.license.server_url'))
                ->get('/updates/latest');

            if (! $response->successful()) {
                return ['ok' => false, 'message' => 'Unable to check for updates.'];
            }

            $data = $response->json();

            return [
                'ok' => true,
                'latest_version' => $data['latest_version'] ?? null,
                'git_tag' => $data['git_tag'] ?? null,
                'changelog_url' => $data['changelog_url'] ?? null,
                'published_at' => $data['published_at'] ?? null,
                'update_available' => version_compare($data['latest_version'] ?? '0', config('app.version'), '>'),
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Unable to reach the license server.'];
        }
    }
}
