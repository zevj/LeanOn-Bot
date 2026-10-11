<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MLService;

class TrainMLModel extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'ml:train';

    /**
     * The console command description.
     */
    protected $description = 'Train or retrain the LeanOn-Bot scikit-learn Machine Learning models';

    /**
     * Execute the console command.
     */
    public function handle(MLService $mlService)
    {
        $this->info('Starting Machine Learning model training...');
        $this->info('Loading training corpus and database conversation data...');

        $result = $mlService->train();

        if (!empty($result['success'])) {
            $this->info('ML Models successfully trained and serialized!');
            if (isset($result['metadata']['metrics'])) {
                $this->table(
                    ['Metric', 'Score'],
                    collect($result['metadata']['metrics'])->map(function ($val, $key) {
                        return [$key, is_float($val) ? number_format($val, 4) : $val];
                    })->values()->toArray()
                );
            }
            if (isset($result['output'])) {
                $this->line($result['output']);
            }
        } else {
            $this->error('Model training failed: ' . ($result['error'] ?? 'Unknown error'));
        }
    }
}
