<?php

namespace App\Sms;

use App\Jobs\SendSmsJob;
use App\Models\Setting;
use App\Models\SmsGatewayConfig;
use App\Models\SmsLog;
use App\Models\SmsTemplate;
use App\Sms\Contracts\SmsGatewayInterface;
use App\Sms\DTO\SmsMessage;
use App\Sms\DTO\SmsResponse;
use App\Sms\Exceptions\SmsException;
use Illuminate\Support\Manager;

class SmsManager extends Manager
{
    public function getDefaultDriver()
    {
        $active = SmsGatewayConfig::active();

        return $active?->driver_key ?? config('sms.default');
    }

    protected function createDriver($driver)
    {
        $gateways = config('sms.gateways', []);

        if (! isset($gateways[$driver])) {
            throw new \InvalidArgumentException("SMS gateway [{$driver}] is not registered in config/sms.php.");
        }

        $config = SmsGatewayConfig::forDriver($driver);

        $class = $gateways[$driver];

        return new $class($config->credentials ?? [], [
            'timeout' => $config->timeout,
        ]);
    }

    public function driverFor(string $key): SmsGatewayInterface
    {
        return $this->driver($key);
    }

    public function installedGateways(): array
    {
        return collect(config('sms.gateways', []))
            ->map(fn (string $class) => $class::meta())
            ->values()
            ->all();
    }

    public function send(string $to, string $message, string $context = 'custom'): SmsResponse
    {
        return $this->dispatchOrSendNow(new SmsMessage($to, $message, context: $context));
    }

    public function sendOTP(string $to, string $code): SmsResponse
    {
        return $this->sendWithTemplate('otp', $to, ['code' => $code], 'otp');
    }

    public function sendOrder(string $to, array $data): SmsResponse
    {
        return $this->sendWithTemplate('order_confirmation', $to, $data, 'order');
    }

    public function sendInvoice(string $to, array $data): SmsResponse
    {
        return $this->sendWithTemplate('invoice', $to, $data, 'invoice');
    }

    public function sendNotification(string $to, string $message): SmsResponse
    {
        return $this->send($to, $message, 'notification');
    }

    public function sendMarketing(string $to, string $message): SmsResponse
    {
        return $this->send($to, $message, 'marketing');
    }

    /**
     * @return SmsResponse[]
     */
    public function sendBulk(array $recipients, string $message): array
    {
        return array_map(fn (string $to) => $this->send($to, $message), $recipients);
    }

    public function balance(): SmsResponse
    {
        try {
            return $this->driver()->getBalance();
        } catch (SmsException $e) {
            return SmsResponse::failure($this->getDefaultDriver(), $e->errorCode, $e->getMessage(), $e->rawResponse);
        }
    }

    public function status(): array
    {
        return $this->driver()->getStatus();
    }

    public function test(): SmsResponse
    {
        try {
            return $this->driver()->testConnection();
        } catch (SmsException $e) {
            return SmsResponse::failure($this->getDefaultDriver(), $e->errorCode, $e->getMessage(), $e->rawResponse);
        }
    }

    protected function sendWithTemplate(string $templateKey, string $to, array $data, string $context): SmsResponse
    {
        $template = SmsTemplate::where('key', $templateKey)->where('is_active', true)->first();
        $body = $template ? $template->render($data) : ($data['message'] ?? '');

        return $this->dispatchOrSendNow(new SmsMessage($to, $body, context: $context));
    }

    protected function dispatchOrSendNow(SmsMessage $message): SmsResponse
    {
        if (! Setting::get('enabled', true, 'sms')) {
            return SmsResponse::failure($this->getDefaultDriver(), 'sms_disabled', 'SMS sending is disabled.');
        }

        if (Setting::get('queue_sending', true, 'sms')) {
            SendSmsJob::dispatch($message, $this->getDefaultDriver());

            return SmsResponse::success($this->getDefaultDriver(), status: 'queued');
        }

        return $this->sendNow($message);
    }

    public function sendNow(SmsMessage $message, ?string $driverKey = null): SmsResponse
    {
        $driverKey ??= $this->getDefaultDriver();

        try {
            $response = $this->driver($driverKey)->send($message);
        } catch (SmsException $e) {
            $response = SmsResponse::failure($driverKey, $e->errorCode, $e->getMessage(), $e->rawResponse);
        }

        SmsLog::create([
            'driver_key' => $driverKey,
            'to' => $message->to,
            'message' => $message->message,
            'status' => $response->status,
            'message_id' => $response->messageId,
            'cost' => $response->cost,
            'error_code' => $response->errorCode,
            'error_message' => $response->errorMessage,
            'provider_response' => $response->providerResponse,
            'raw_response' => $response->rawResponse,
            'context' => $message->context,
            'sent_at' => $response->success ? now() : null,
        ]);

        return $response;
    }
}
