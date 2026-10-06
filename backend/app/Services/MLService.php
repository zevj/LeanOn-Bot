<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MLService
{
    protected bool $enabled;
    protected string $url;
    protected float $timeout;
    protected bool $fallbackCli;
    protected string $pythonPath;
    protected string $scriptPath;

    public function __construct()
    {
        $this->enabled = (bool) config('services.ml.enabled', true);
        $this->url = rtrim((string) config('services.ml.url', 'http://127.0.0.1:8001'), '/');
        $this->timeout = (float) config('services.ml.timeout', 1.5);
        $this->fallbackCli = (bool) config('services.ml.fallback_cli', true);
        $this->pythonPath = (string) config('services.ml.python_path', 'python');
        $this->scriptPath = (string) config('services.ml.script_path', base_path('../ml_service/predict.py'));
    }

    /**
     * Analyze a user message with optional conversation history.
     */
    public function analyze(string $message, iterable $history = []): array
    {
        if (!$this->enabled) {
            return $this->fallbackDefaults('disabled');
        }

        // Format history into array of lightweight dicts
        $formattedHistory = [];
        foreach ($history as $msg) {
            $formattedHistory[] = [
                'message' => $msg->message ?? '',
                'reply'   => $msg->reply ?? '',
            ];
        }

        // 1. Attempt HTTP call to Python FastAPI microservice
        try {
            $response = Http::timeout($this->timeout)->post("{$this->url}/predict", [
                'message' => $message,
                'history' => $formattedHistory,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (is_array($data) && isset($data['intent'])) {
                    $data['provider'] = 'http_microservice';
                    return $data;
                }
            }
        } catch (\Throwable $e) {
            Log::debug('ML microservice HTTP request failed: ' . $e->getMessage());
        }

        // 2. Fallback to direct Python CLI invocation if configured
        if ($this->fallbackCli && file_exists($this->scriptPath)) {
            $cliResult = $this->runCliInference($message, $formattedHistory);
            if ($cliResult !== null) {
                $cliResult['provider'] = 'python_cli';
                return $cliResult;
            }
        }

        // 3. Fallback defaults to guarantee zero disruption to student chat
        return $this->fallbackDefaults('circuit_breaker');
    }

    /**
     * Execute inference via direct Python process.
     */
    protected function runCliInference(string $message, array $history): ?array
    {
        try {
            $payload = json_encode([
                'message' => $message,
                'history' => $history,
            ], JSON_UNESCAPED_UNICODE);

            $python = '"' . str_replace('/', '\\', trim($this->pythonPath, '"')) . '"';
            $script = '"' . str_replace('/', '\\', trim($this->scriptPath, '"')) . '"';
            $command = "{$python} {$script}";

            $descriptors = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];

            $cwd = str_replace('/', '\\', dirname($this->scriptPath));
            $process = proc_open($command, $descriptors, $pipes, $cwd);
            if (!is_resource($process)) {
                return null;
            }

            fwrite($pipes[0], $payload);
            fclose($pipes[0]);

            $output = stream_get_contents($pipes[1]);
            $errorOutput = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);

            if (!empty($errorOutput)) {
                Log::debug('ML CLI inference stderr: ' . $errorOutput);
            }

            $decoded = json_decode(trim($output), true);
            if (is_array($decoded) && isset($decoded['intent'])) {
                return $decoded;
            }
        } catch (\Throwable $e) {
            Log::debug('ML CLI inference failed: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Trigger ML model retraining.
     */
    public function train(): array
    {
        try {
            $response = Http::timeout(60)->post("{$this->url}/train");
            if ($response->successful()) {
                return $response->json();
            }
            return [
                'success' => false,
                'error'   => 'HTTP service error: ' . $response->body(),
            ];
        } catch (\Throwable $e) {
            // CLI fallback for training
            $trainScript = base_path('../ml_service/train.py');
            if (file_exists($trainScript)) {
                $output = shell_exec(escapeshellcmd($this->pythonPath) . ' ' . escapeshellarg($trainScript) . ' 2>&1');
                return [
                    'success' => true,
                    'output'  => $output,
                    'mode'    => 'cli',
                ];
            }

            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * Get ML service health status and model metrics.
     */
    public function getStatus(): array
    {
        $status = [
            'enabled'      => $this->enabled,
            'url'          => $this->url,
            'service_live' => false,
            'models'       => null,
            'metadata'     => null,
        ];

        try {
            $response = Http::timeout(1.0)->get("{$this->url}/health");
            if ($response->successful()) {
                $status['service_live'] = true;
                $status['health'] = $response->json();
            }
        } catch (\Throwable $e) {
            $status['service_live'] = false;
        }

        $metaPath = base_path('../ml_service/models/metadata.json');
        if (file_exists($metaPath)) {
            $status['metadata'] = json_decode(file_get_contents($metaPath), true);
        }

        return $status;
    }

    /**
     * Circuit-breaker safe default output.
     */
    protected function fallbackDefaults(string $statusReason): array
    {
        return [
            'is_mental_health'         => true,
            'mental_health_confidence' => 0.85,
            'intent'                   => 'venting',
            'intent_confidence'        => 0.80,
            'emotion'                  => 'stressed',
            'emotion_confidence'       => 0.80,
            'tone'                     => 'casual',
            'distress_score'           => 0.40,
            'suggested_severity'       => 'low',
            'recommended_strategy'     => 'EMPATHETIC_LISTENING',
            'strategy_prompt'          => '',
            'ml_status'                => $statusReason,
            'provider'                 => 'fallback',
        ];
    }
}
