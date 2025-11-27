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

    // デバッグ情報を追加
    $debugInfo = [
        'raw_output' => $allStatus,
        'trimmed_output' => trim($allStatus),
        'output_length' => strlen($allStatus ?? ''),
        'preg_match_result' => preg_match('/queue-worker.*?(RUNNING|STARTING)/', $allStatus ?? ''),
    ];

    // エラーチェック
    if ($allStatus === null || trim($allStatus) === '') {
        return response()->json([
            'status' => 'unhealthy',
            'error' => 'Failed to get supervisor status',
            'debug' => $debugInfo,
            'timestamp' => now()->toIso8601String()
        ], 503);
    }

    // queue-workerプロセスがRUNNINGまたはSTARTINGか確認
    if (preg_match('/queue-worker.*?(RUNNING|STARTING)/', $allStatus)) {
        return response()->json([
            'status' => 'healthy',
            'all_status' => trim($allStatus),
            'debug' => $debugInfo,
            'timestamp' => now()->toIso8601String()
        ], 200);
    }

    // それ以外は異常
    return response()->json([
        'status' => 'unhealthy',
        'all_status' => trim($allStatus),
        'error' => 'Queue worker is not running',
        'debug' => $debugInfo,
        'timestamp' => now()->toIso8601String()
    ], 503);
});
