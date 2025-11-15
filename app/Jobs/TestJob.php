<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class TestJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $message = 'Hello from TestJob!'
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('TestJob started', [
            'message' => $this->message,
            'timestamp' => now()->toDateTimeString(),
        ]);

        // シミュレーション: 2秒間処理
        sleep(2);

        Log::info('TestJob completed', [
            'message' => $this->message,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }
}
