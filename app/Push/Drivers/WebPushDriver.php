<?php

namespace App\Push\Drivers;

use App\Models\PushSubscription;
use App\Models\User;
use App\Push\Contracts\PushGatewayInterface;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushDriver implements PushGatewayInterface
{
    protected string $publicKey;
    protected string $privateKey;
    protected string $subject;

    public function __construct(array $credentials, array $options = [])
    {
        $this->publicKey = $credentials['vapid_public_key'] ?? '';
        $this->privateKey = $credentials['vapid_private_key'] ?? '';
        $this->subject = $credentials['vapid_subject'] ?? config('app.url');
    }

    public function sendToUser(User $user, string $title, string $body, array $data = []): array
    {
        $subscriptions = PushSubscription::where('user_id', $user->id)->get();

        if ($subscriptions->isEmpty()) {
            return ['success' => false, 'error_message' => 'No push subscriptions for this user.'];
        }

        if (! $this->publicKey || ! $this->privateKey) {
            return ['success' => false, 'error_message' => 'Web Push VAPID keys are not configured.'];
        }

        try {
            $webPush = new WebPush([
                'VAPID' => [
                    'subject' => $this->subject,
                    'publicKey' => $this->publicKey,
                    'privateKey' => $this->privateKey,
                ],
            ]);

            $payload = json_encode(['title' => $title, 'body' => $body]);

            foreach ($subscriptions as $subscription) {
                $webPush->queueNotification(
                    Subscription::create([
                        'endpoint' => $subscription->endpoint,
                        'publicKey' => $subscription->public_key,
                        'authToken' => $subscription->auth_token,
                        'contentEncoding' => $subscription->content_encoding ?: 'aesgcm',
                    ]),
                    $payload
                );
            }

            $success = true;

            foreach ($webPush->flush() as $report) {
                if (! $report->isSuccess()) {
                    $success = false;
                }
            }

            return ['success' => $success, 'error_message' => $success ? null : 'One or more push deliveries failed.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error_message' => $e->getMessage()];
        }
    }

    public function testConnection(): array
    {
        if (! $this->publicKey || ! $this->privateKey) {
            return ['success' => false, 'error_message' => 'VAPID keys are not configured.'];
        }

        return ['success' => true, 'error_message' => null];
    }

    public static function meta(): array
    {
        return [
            'key' => 'web_push',
            'label' => 'Web Push (Browser)',
            'fields' => [
                ['name' => 'vapid_public_key', 'label' => 'VAPID Public Key', 'type' => 'text', 'required' => true],
                ['name' => 'vapid_private_key', 'label' => 'VAPID Private Key', 'type' => 'password', 'required' => true],
                ['name' => 'vapid_subject', 'label' => 'VAPID Subject (mailto: or URL)', 'type' => 'text', 'required' => false],
            ],
        ];
    }
}
