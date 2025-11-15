<?php

use Illuminate\Support\Facades\Route;

// ウェルカム画面
Route::get('/', function () {
    return view('welcome');
});

// 認証画面（仮）
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::get('/register', function () {
    return view('auth.register');
})->name('register');

Route::post('/login', function () {
    return redirect()->route('dashboard');
});

Route::post('/register', function () {
    return redirect()->route('dashboard');
});

Route::post('/logout', function () {
    return redirect()->route('login');
})->name('logout');

// 認証が必要な画面（ダミーデータで表示）
Route::middleware(['web'])->group(function () {

    // ダッシュボード
    Route::get('/dashboard', function () {
        return view('dashboard', [
            'yearlyIncome' => 5000000,
            'yearlyExpense' => 2000000,
            'totalAssets' => 1500000,
            'monthlyIncome' => [500000, 450000, 600000, 550000, 520000, 480000, 510000, 530000, 490000, 500000, 520000, 550000],
            'monthlyExpense' => [200000, 180000, 220000, 190000, 210000, 185000, 195000, 205000, 200000, 210000, 190000, 220000],
            'recentTransactions' => [
                (object)[
                    'id' => 1,
                    'date' => '2025-11-15',
                    'type' => 'income',
                    'category' => '売上高',
                    'description' => 'Webサイト制作',
                    'amount' => 300000,
                ],
                (object)[
                    'id' => 2,
                    'date' => '2025-11-14',
                    'type' => 'expense',
                    'category' => '通信費',
                    'description' => 'インターネット料金',
                    'amount' => 5000,
                ],
                (object)[
                    'id' => 3,
                    'date' => '2025-11-13',
                    'type' => 'expense',
                    'category' => '消耗品費',
                    'description' => 'プリンター用紙',
                    'amount' => 3000,
                ],
            ],
        ]);
    })->name('dashboard');

    // 取引一覧
    Route::get('/transactions', function () {
        return view('transactions.index', [
            'transactions' => [
                (object)[
                    'id' => 1,
                    'date' => '2025-11-15',
                    'type' => 'income',
                    'category' => '売上高',
                    'description' => 'Webサイト制作',
                    'client' => '株式会社サンプル',
                    'amount' => 300000,
                    'receipt_path' => null,
                ],
                (object)[
                    'id' => 2,
                    'date' => '2025-11-14',
                    'type' => 'expense',
                    'category' => '通信費',
                    'description' => 'インターネット料金',
                    'client' => 'NTT',
                    'amount' => 5000,
                    'receipt_path' => null,
                ],
            ],
            'categories' => ['売上高', '雑収入', '通信費', '消耗品費', '旅費交通費'],
            'totalIncome' => 300000,
            'totalExpense' => 5000,
        ]);
    })->name('transactions.index');

    Route::get('/transactions/create', function () {
        return view('transactions.create');
    })->name('transactions.create');

    Route::post('/transactions', function () {
        return redirect()->route('transactions.index')->with('success', '取引を登録しました');
    })->name('transactions.store');

    Route::get('/transactions/{id}/edit', function ($id) {
        return view('transactions.edit', [
            'transaction' => (object)[
                'id' => $id,
                'date' => '2025-11-15',
                'type' => 'expense',
                'category' => '通信費',
                'description' => 'インターネット料金',
                'client' => 'NTT',
                'amount' => 5000,
                'receipt_path' => null,
                'memo' => '',
            ],
        ]);
    })->name('transactions.edit');

    Route::put('/transactions/{id}', function ($id) {
        return redirect()->route('transactions.index')->with('success', '取引を更新しました');
    })->name('transactions.update');

    Route::delete('/transactions/{id}', function ($id) {
        return redirect()->route('transactions.index')->with('success', '取引を削除しました');
    })->name('transactions.destroy');

    Route::get('/transactions/export', function () {
        return response()->json(['message' => 'CSV export']);
    })->name('transactions.export');

    // レシート登録
    Route::get('/receipts/create', function () {
        return view('receipts.create');
    })->name('receipts.create');

    Route::post('/receipts', function () {
        return redirect()->route('transactions.index')->with('success', 'レシートを登録しました');
    })->name('receipts.store');

    // 固定資産
    Route::get('/assets', function () {
        return view('assets.index', [
            'assets' => [
                (object)[
                    'id' => 1,
                    'name' => 'MacBook Pro 2024',
                    'category' => 'パソコン',
                    'acquisition_date' => '2024-04-01',
                    'useful_life' => 4,
                    'acquisition_cost' => 300000,
                    'accumulated_depreciation' => 37500,
                    'book_value' => 262500,
                    'depreciation_rate' => 12.5,
                    'status' => 'active',
                ],
                (object)[
                    'id' => 2,
                    'name' => 'Adobe Creative Cloud',
                    'category' => 'ソフトウェア',
                    'acquisition_date' => '2023-01-01',
                    'useful_life' => 5,
                    'acquisition_cost' => 150000,
                    'accumulated_depreciation' => 90000,
                    'book_value' => 60000,
                    'depreciation_rate' => 60.0,
                    'status' => 'active',
                ],
            ],
            'totalBookValue' => 322500,
            'totalAcquisitionCost' => 450000,
            'yearlyDepreciation' => 67500,
            'assetCount' => 2,
        ]);
    })->name('assets.index');

    Route::get('/assets/create', function () {
        return view('assets.create');
    })->name('assets.create');

    Route::post('/assets', function () {
        return redirect()->route('assets.index')->with('success', '固定資産を登録しました');
    })->name('assets.store');

    Route::get('/assets/{id}', function ($id) {
        return response()->json(['message' => 'Asset detail']);
    })->name('assets.show');

    Route::get('/assets/{id}/edit', function ($id) {
        return response()->json(['message' => 'Asset edit']);
    })->name('assets.edit');

    Route::delete('/assets/{id}', function ($id) {
        return redirect()->route('assets.index')->with('success', '固定資産を削除しました');
    })->name('assets.destroy');

    // レポート
    Route::get('/reports/monthly', function () {
        $monthlyData = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthlyData[$i] = [
                'income' => rand(400000, 600000),
                'expense' => rand(150000, 250000),
            ];
        }

        return view('reports.monthly', [
            'yearlyIncome' => 5000000,
            'yearlyExpense' => 2000000,
            'monthlyData' => $monthlyData,
            'categoryBreakdown' => [
                '通信費' => 60000,
                '消耗品費' => 45000,
                '旅費交通費' => 80000,
                '接待交際費' => 120000,
                '地代家賃' => 600000,
                '減価償却費' => 67500,
            ],
        ]);
    })->name('reports.monthly');

    Route::get('/reports/tax', function () {
        return view('reports.tax', [
            'totalIncome' => 5000000,
            'totalExpense' => 2000000,
            'expenseBreakdown' => [
                '通信費' => 60000,
                '消耗品費' => 45000,
                '旅費交通費' => 80000,
                '接待交際費' => 120000,
                '地代家賃' => 600000,
                '減価償却費' => 67500,
                'その他' => 1027500,
            ],
            'assetCount' => 2,
            'yearlyDepreciation' => 67500,
        ]);
    })->name('reports.tax');

    Route::get('/reports/export', function () {
        return response()->json(['message' => 'Report export']);
    })->name('reports.export');

    Route::get('/reports/generate', function () {
        return response()->json(['message' => 'Generate report']);
    })->name('reports.generate');
});
