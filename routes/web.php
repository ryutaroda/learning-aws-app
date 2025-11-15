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
