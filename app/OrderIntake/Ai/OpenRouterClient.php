<?php

namespace App\OrderIntake\Ai;

use App\OrderIntake\IntakeSettings;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Thin OpenRouter chat-completions client for the AI Order fallback:
 * structured output (json_schema), model fallback via the "models" list,
 * and the usage/cost OpenRouter returns with every response. The API key
 * only ever goes in the Authorization header from the server.
 */
class OpenRouterClient
{
    public function __construct(protected IntakeSettings $settings) {}

    /**
     * @param  list<array<string, mixed>>  $messages
     * @param  array<string, mixed>  $schema  JSON Schema for the reply
     * @return array{content: string, model: string, usage: array<string, mixed>, latency_ms: int}
     *
     * @throws AiExtractionException
     */
    public function chat(array $messages, array $schema, string $schemaName): array
    {
        $models = array_values(array_unique(array_filter([$this->settings->model(), $this->settings->fallbackModel()])));

        $body = [
            'messages'        => $messages,
            'temperature'     => $this->settings->temperature(),
            'max_tokens'      => $this->settings->maxOutputTokens(),
            'response_format' => [
                'type'        => 'json_schema',
                'json_schema' => ['name' => $schemaName, 'strict' => true, 'schema' => $schema],
            ],
        ];
        $body += count($models) > 1 ? ['models' => $models] : ['model' => $models[0]];

        $started = hrtime(true);

        try {
            $response = $this->request()->timeout($this->settings->timeout())->post('/chat/completions', $body);
        } catch (ConnectionException $e) {
            throw new AiExtractionException('AI request timed out or could not connect: ' . $e->getMessage(), $models[0], [], $this->elapsed($started));
        }

        $latency = $this->elapsed($started);
        $json = $response->json() ?? [];

        if ($response->failed() || isset($json['error'])) {
            $message = $json['error']['message'] ?? $response->reason();
            $prefix = match ($response->status()) {
                401, 403 => 'OpenRouter rejected the API key',
                402      => 'OpenRouter account is out of credit',
                429      => 'OpenRouter rate limit reached — try again shortly',
                default  => 'AI request failed',
            };

            throw new AiExtractionException("{$prefix}: {$message}", $json['model'] ?? $models[0], $json['usage'] ?? [], $latency);
        }

        $content = $json['choices'][0]['message']['content'] ?? null;

        if (! is_string($content) || trim($content) === '') {
            throw new AiExtractionException('AI returned an empty reply', $json['model'] ?? $models[0], $json['usage'] ?? [], $latency);
        }

        return [
            'content'    => $content,
            'model'      => (string) ($json['model'] ?? $models[0]),
            'usage'      => $json['usage'] ?? [],
            'latency_ms' => $latency,
        ];
    }

    /**
     * The key's own info (label, limit, usage) — Settings → AI Order's
     * "Test connection". Throws with OpenRouter's message on failure.
     *
     * @return array<string, mixed>
     */
    public function keyInfo(?string $key = null): array
    {
        try {
            $response = $this->request($key)->timeout(15)->get('/key');
        } catch (ConnectionException $e) {
            throw new AiExtractionException('Could not reach OpenRouter: ' . $e->getMessage());
        }

        if ($response->failed()) {
            throw new AiExtractionException('OpenRouter: ' . ($response->json('error.message') ?? $response->reason()));
        }

        return (array) $response->json('data', []);
    }

    protected function request(?string $key = null)
    {
        return Http::baseUrl($this->settings->baseUrl())
            ->withToken($key ?? $this->settings->apiKey())
            ->withHeaders([
                'HTTP-Referer' => config('app.url'),
                'X-Title'      => config('app.name') . ' — AI Order',
            ])
            ->acceptJson();
    }

    private function elapsed(int $started): int
    {
        return (int) round((hrtime(true) - $started) / 1e6);
    }
}
