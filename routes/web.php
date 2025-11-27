<?php

use App\Jobs\TestJob;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test-job', function () {
    // Jobをキューに投入
    TestJob::dispatch('テストメッセージ from web route');

    return response()->json([
        'status' => 'success',
        'message' => 'TestJob has been dispatched to the queue.',
    ]);
});

// Queue Worker用ヘルスチェックエンドポイント
Route::get('/health/queue-worker', function () {
    // 全プロセスの状態を確認（プロセス名が正確でなくても動作する）
    $allStatus = shell_exec('/usr/bin/supervisorctl -c /etc/supervisor/conf.d/supervisord-queue.conf status 2>&1');

    // queue-workerプロセスがRUNNINGまたはSTARTINGか確認
    if (preg_match('/queue-worker.*?(RUNNING|STARTING)/', $allStatus)) {
        return response()->json([
            'status' => 'healthy',
            'all_status' => trim($allStatus),
            'timestamp' => now()->toIso8601String()
        ], 200);
    }

    // それ以外は異常
    return response()->json([
        'status' => 'unhealthy',
        'all_status' => trim($allStatus),
        'error' => 'Queue worker is not running',
        'timestamp' => now()->toIso8601String()
    ], 503);
});
