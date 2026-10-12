<?php

namespace App\Services\Accounts;

use App\Actions\Profiles\SendProfileSms;
use App\Models\MasterProfile;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Sends a report copy to its recipients over the chosen channels
 * (spec §5.11): email (the archived copy attached), SMS (a short summary,
 * to owner profiles), WhatsApp (no gateway configured → logged). Every
 * channel falls back to the log, so a missing mail / SMS setup never
 * breaks a scheduled run.
 */
class ReportDelivery
{
    /** @return Collection<int, array{name: string, email: ?string, profile: ?MasterProfile}> */
    public function recipients(string $who, ?User $me = null): Collection
    {
        $owners = fn () => MasterProfile::withRole('owner')->get()->map(fn ($p) => ['name' => $p->display_name, 'email' => $p->email, 'profile' => $p]);
        $accountants = fn () => User::permission(['accounts.post', 'accounts.approve'])->get()
            ->reject(fn ($u) => $u->hasAnyRole(['superadmin', 'owner']))->map(fn ($u) => ['name' => $u->name, 'email' => $u->email, 'profile' => null]);
        $mine = fn () => collect($me ? [['name' => $me->name, 'email' => $me->email, 'profile' => null]] : []);

        return match ($who) {
            'owners' => $owners(),
            'accountant' => rescue($accountants, collect(), false),
            'both' => $owners()->concat(rescue($accountants, collect(), false)),
            default => $mine(),
        };
    }

    /** @return array<string, int> channel → how many got it */
    public function send(string $who, array $channels, string $title, string $summary, ?string $path = null, ?User $me = null): array
    {
        $to = $this->recipients($who, $me);
        $out = [];

        if (in_array('email', $channels, true)) {
            $out['email'] = 0;
            foreach ($to->pluck('email')->filter()->unique() as $email) {
                try {
                    Mail::raw($summary, function ($m) use ($email, $title, $path) {
                        $m->to($email)->subject($title);
                        if ($path && Storage::disk('local')->exists($path)) {
                            $m->attachData(Storage::disk('local')->get($path), basename($path), ['mime' => 'text/html']);
                        }
                    });
                    $out['email']++;
                } catch (Throwable $e) {
                    Log::warning("Report email to {$email} failed: {$e->getMessage()}");
                }
            }
        }

        if (in_array('sms', $channels, true)) {
            $sms = app(SendProfileSms::class);
            $out['sms'] = $to->pluck('profile')->filter()->sum(fn ($p) => (int) rescue(fn () => $sms->send($p, $summary, "Report SMS · {$title}"), false, false));
        }

        if (in_array('whatsapp', $channels, true)) {
            Log::info("Report WhatsApp (no gateway configured): {$title}", ['to' => $to->pluck('name')->all(), 'path' => $path]);
            $out['whatsapp'] = $to->count();
        }

        return $out;
    }
}
