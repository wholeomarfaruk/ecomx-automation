<?php

namespace App\Push;

use App\Models\PushGatewayConfig;
use App\Models\User;
use App\Push\Contracts\PushGatewayInterface;
use Illuminate\Support\Manager;

class PushManager extends Manager
{
    public function getDefaultDriver()
    {
        $active = PushGatewayConfig::active();

        return $active?->driver_key ?? config('push.default');
    }

    protected function createDriver($driver)
    {
        $gateways = config('push.gateways', []);

        if (! isset($gateways[$driver])) {
            throw new \InvalidArgumentException("Push gateway [{$driver}] is not registered in config/push.php.");
        }

        $config = PushGatewayConfig::forDriver($driver);

        $class = $gateways[$driver];

        return new $class($config->credentials ?? []);
    }

    public function driverFor(string $key): PushGatewayInterface
    {
        return $this->driver($key);
    }

    public function installedGateways(): array
    {
        return collect(config('push.gateways', []))
            ->map(fn (string $class) => $class::meta())
            ->values()
            ->all();
    }

    public function sendToUser(User $user, string $eventKey, array $data = []): ?string
    {
        if (! PushGatewayConfig::active()) {
            return null;
        }

        $title = $data['push_title'] ?? config('app.name');
        $body = $data['push_body'] ?? ($data['message'] ?? $eventKey);

        $result = $this->driver()->sendToUser($user, $title, $body, $data);

        return $result['success'] ? $this->getDefaultDriver() : null;
    }
}
