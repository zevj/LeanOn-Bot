<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MLService;

class MLStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'ml:status';

    /**
     * The console command description.
     */
    protected $description = 'Display the status, health, and metrics of the Machine Learning service';

    /**
     * Execute the console command.
     */
    public function handle(MLService $mlService)
    {
        $status = $mlService->getStatus();

        $this->info('=== LeanOn-Bot Machine Learning System Status ===');
        $this->line('Enabled:           ' . ($status['enabled'] ? '<fg=green>Yes</>' : '<fg=red>No</>'));
        $this->line('Service URL:       ' . $status['url']);
        $this->line('Microservice Live: ' . ($status['service_live'] ? '<fg=green>Connected</>' : '<fg=yellow>Offline (CLI / Rule Fallback Active)</>'));

        if (!empty($status['metadata'])) {
            $meta = $status['metadata'];
            $this->newLine();
            $this->info('Model Metadata:');
            $this->line('Version:           ' . ($meta['version'] ?? '1.0.0'));
            $this->line('Trained At:        ' . ($meta['trained_at'] ?? 'N/A'));
            $this->line('Training Samples:  ' . ($meta['total_samples'] ?? 0));

            if (!empty($meta['metrics'])) {
                $this->newLine();
                $this->info('Evaluation Metrics:');
                $rows = [];
                foreach ($meta['metrics'] as $metric => $score) {
                    $rows[] = [$metric, is_float($score) ? number_format($score, 4) : $score];
                }
                $this->table(['Metric', 'Value'], $rows);
            }
        } else {
            $this->warn('No model metadata found. Run `php artisan ml:train` to train the models.');
        }

        // Test sample prediction
        $this->newLine();
        $this->info('Testing Live Inference Pipeline...');
        $sample = $mlService->analyze('Sobrang stressed ako sa capstone defense namin next week.');
        $this->line('Sample input:  "Sobrang stressed ako sa capstone defense namin next week."');
        $this->line('Provider:      ' . ($sample['provider'] ?? 'unknown'));
        $this->line('Domain:        ' . ($sample['is_mental_health'] ? 'In-Scope Mental Health' : 'Out-of-Scope') . " (conf: {$sample['mental_health_confidence']})");
        $this->line('Intent:        ' . $sample['intent'] . " (conf: {$sample['intent_confidence']})");
        $this->line('Emotion:       ' . $sample['emotion'] . " (conf: {$sample['emotion_confidence']})");
        $this->line('Tone:          ' . $sample['tone']);
        $this->line('Distress:      ' . $sample['distress_score'] . " ({$sample['suggested_severity']})");
        $this->line('Strategy:      ' . $sample['recommended_strategy']);
        if (!empty($sample['strategy_prompt'])) {
            $this->line('Guidance:      ' . $sample['strategy_prompt']);
        }
    }
}
