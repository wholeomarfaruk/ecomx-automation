<?php

namespace App\Push\Drivers;

use App\Models\DeviceToken;
use App\Models\User;
use App\Push\Contracts\PushGatewayInterface;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;

class FirebaseDriver implements PushGatewayInterface
{
    protected array $serviceAccount;

    public function __construct(array $credentials, array $options = [])
    {
        $this->serviceAccount = $credentials['service_account_json'] ?? [];
    }

    protected function messaging()
    {
        return (new Factory())
            ->withServiceAccount($this->serviceAccount)
            ->createMessaging();
    }

    public function sendToUser(User $user, string $title, string $body, array $data = []): array
    {
        $tokens = DeviceToken::where('user_id', $user->id)->pluck('token')->all();

        if (empty($tokens)) {
            return ['success' => false, 'error_message' => 'No device tokens for this user.'];
        }

        if (empty($this->serviceAccount)) {
            return ['success' => false, 'error_message' => 'Firebase service account is not configured.'];
        }

        try {
            $message = CloudMessage::new()->withNotification(
                FirebaseNotification::create($title, $body)
            );

            $report = $this->messaging()->sendMulticast($message, $tokens);

            $success = $report->successes()->count() > 0;

            return [
                'success' => $success,
                'error_message' => $success ? null : 'All device token deliveries failed.',
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'error_message' => $e->getMessage()];
        }
    }

    public function testConnection(): array
    {
        if (empty($this->serviceAccount)) {
            return ['success' => false, 'error_message' => 'Firebase service account is not configured.'];
        }

        try {
            $this->messaging();

            return ['success' => true, 'error_message' => null];
        } catch (\Throwable $e) {
            return ['success' => false, 'error_message' => $e->getMessage()];
        }
    }

    public static function meta(): array
    {
        return [
            'key' => 'firebase',
            'label' => 'Firebase (Mobile Push / FCM)',
            'fields' => [
                ['name' => 'service_account_json', 'label' => 'Service Account JSON', 'type' => 'textarea', 'required' => true],
            ],
        ];
    }
}
