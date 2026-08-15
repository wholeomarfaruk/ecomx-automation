<?php

namespace App\Push\Contracts;

use App\Models\User;

interface PushGatewayInterface
{
    public function __construct(array $credentials, array $options = []);

    /**
     * @return array{success: bool, error_message: ?string}
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): array;

    public function testConnection(): array;

    /**
     * Static metadata used for auto-discovery in the admin panel.
     *
     * @return array{key: string, label: string, fields: array}
     */
    public static function meta(): array;
}
