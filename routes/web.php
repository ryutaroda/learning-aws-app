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
    // Supervisorプロセスの状態を確認
    $supervisorStatus = shell_exec('/usr/bin/supervisorctl -c /etc/supervisor/conf.d/supervisord-queue.conf status queue-worker:queue-worker_00 2>&1');
    
    // RUNNINGまたはSTARTINGの場合は正常
    if (preg_match('/RUNNING|STARTING/', $supervisorStatus)) {
        return response()->json([
            'status' => 'healthy',
            'supervisor' => trim($supervisorStatus),
            'timestamp' => now()->toIso8601String()
        ], 200);
    }
    
    // それ以外（STOPPED, FATAL, BACKOFF等）は異常
    return response()->json([
        'status' => 'unhealthy',
        'supervisor' => trim($supervisorStatus),
        'timestamp' => now()->toIso8601String()
    ], 503);
});
