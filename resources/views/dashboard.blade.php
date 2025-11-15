@extends('layouts.app')

@section('title', 'ダッシュボード')

@section('content')
<div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
    <!-- サマリーカード -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        <!-- 今年の収入 -->
        <div class="overflow-hidden rounded-lg bg-white shadow">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 rounded-md bg-green-500 p-3">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="truncate text-sm font-medium text-gray-500">今年の収入</dt>
                            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">
                                ¥{{ number_format($yearlyIncome ?? 0) }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-3">
                <div class="text-sm">
                    <a href="{{ route('transactions.index', ['type' => 'income']) }}" class="font-medium text-green-600 hover:text-green-500">
                        詳細を見る →
                    </a>
                </div>
            </div>
        </div>

        <!-- 今年の支出 -->
        <div class="overflow-hidden rounded-lg bg-white shadow">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 rounded-md bg-red-500 p-3">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="truncate text-sm font-medium text-gray-500">今年の支出</dt>
                            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">
                                ¥{{ number_format($yearlyExpense ?? 0) }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-3">
                <div class="text-sm">
                    <a href="{{ route('transactions.index', ['type' => 'expense']) }}" class="font-medium text-red-600 hover:text-red-500">
                        詳細を見る →
                    </a>
                </div>
            </div>
        </div>

        <!-- 今年の利益 -->
        <div class="overflow-hidden rounded-lg bg-white shadow">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 rounded-md bg-blue-500 p-3">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="truncate text-sm font-medium text-gray-500">今年の利益</dt>
                            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">
                                ¥{{ number_format(($yearlyIncome ?? 0) - ($yearlyExpense ?? 0)) }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-3">
                <div class="text-sm">
                    <a href="{{ route('reports.monthly') }}" class="font-medium text-blue-600 hover:text-blue-500">
                        レポートを見る →
                    </a>
                </div>
            </div>
        </div>

        <!-- 固定資産合計 -->
        <div class="overflow-hidden rounded-lg bg-white shadow">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 rounded-md bg-purple-500 p-3">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="truncate text-sm font-medium text-gray-500">固定資産合計</dt>
                            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">
                                ¥{{ number_format($totalAssets ?? 0) }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-3">
                <div class="text-sm">
                    <a href="{{ route('assets.index') }}" class="font-medium text-purple-600 hover:text-purple-500">
                        資産一覧 →
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- グラフとクイックアクション -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3 mb-8">
        <!-- 月別収支グラフ -->
        <div class="lg:col-span-2">
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">月別収支推移</h3>
                    <div class="h-80">
                        <canvas id="monthlyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- クイックアクション -->
        <div class="space-y-6">
            <!-- 新規登録 -->
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">クイックアクション</h3>
                    <div class="space-y-3">
                        <a href="{{ route('transactions.create') }}" class="flex w-full items-center justify-between rounded-md bg-blue-600 px-4 py-3 text-sm font-medium text-white hover:bg-blue-700 transition">
                            <span>取引を登録</span>
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                        </a>
                        <a href="{{ route('receipts.create') }}" class="flex w-full items-center justify-between rounded-md bg-green-600 px-4 py-3 text-sm font-medium text-white hover:bg-green-700 transition">
                            <span>レシートを登録</span>
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </a>
                        <a href="{{ route('assets.create') }}" class="flex w-full items-center justify-between rounded-md bg-purple-600 px-4 py-3 text-sm font-medium text-white hover:bg-purple-700 transition">
                            <span>固定資産を登録</span>
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- リマインダー -->
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">リマインダー</h3>
                    <div class="space-y-3">
                        <div class="flex items-start rounded-md bg-yellow-50 p-3">
                            <svg class="h-5 w-5 text-yellow-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                            </svg>
                            <p class="ml-3 text-sm text-yellow-800">
                                確定申告の準備を始めましょう（3月15日まで）
                            </p>
                        </div>
                        <div class="flex items-start rounded-md bg-blue-50 p-3">
                            <svg class="h-5 w-5 text-blue-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
                            </svg>
                            <p class="ml-3 text-sm text-blue-800">
                                今月の記帳がまだ完了していません
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 最近の取引 -->
    <div class="overflow-hidden rounded-lg bg-white shadow">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">最近の取引</h3>
                <a href="{{ route('transactions.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-500">
                    すべて見る →
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">日付</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">区分</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">勘定科目</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">摘要</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">金額</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @forelse($recentTransactions ?? [] as $transaction)
                        <tr class="hover:bg-gray-50">
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                {{ $transaction->date }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm">
                                @if($transaction->type === 'income')
                                    <span class="inline-flex rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-800">収入</span>
                                @else
                                    <span class="inline-flex rounded-full bg-red-100 px-2 py-1 text-xs font-semibold text-red-800">支出</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                {{ $transaction->category }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ Str::limit($transaction->description, 30) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium {{ $transaction->type === 'income' ? 'text-green-600' : 'text-gray-900' }}">
                                ¥{{ number_format($transaction->amount) }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500">
                                取引がまだ登録されていません
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    // 月別収支グラフ
    const ctx = document.getElementById('monthlyChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月'],
                datasets: [
                    {
                        label: '収入',
                        data: @json($monthlyIncome ?? array_fill(0, 12, 0)),
                        backgroundColor: 'rgba(34, 197, 94, 0.5)',
                        borderColor: 'rgb(34, 197, 94)',
                        borderWidth: 1
                    },
                    {
                        label: '支出',
                        data: @json($monthlyExpense ?? array_fill(0, 12, 0)),
                        backgroundColor: 'rgba(239, 68, 68, 0.5)',
                        borderColor: 'rgb(239, 68, 68)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '¥' + value.toLocaleString();
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ¥' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }
</script>
@endsection
