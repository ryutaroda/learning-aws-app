@extends('layouts.app')

@section('title', '月次レポート')

@section('content')
<div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
    <!-- ヘッダー -->
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">📊 月次レポート</h1>
            <p class="mt-1 text-sm text-gray-500">月別の収支状況を確認できます</p>
        </div>
        <div class="flex items-center gap-3">
            <select id="year-select" class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                @for($y = date('Y'); $y >= date('Y') - 5; $y--)
                    <option value="{{ $y }}" {{ (request('year', date('Y')) == $y) ? 'selected' : '' }}>{{ $y }}年</option>
                @endfor
            </select>
        </div>
    </div>

    <!-- 年間サマリー -->
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-lg bg-gradient-to-br from-green-400 to-green-600 p-6 text-white shadow-lg">
            <div class="text-sm font-medium opacity-90">年間収入</div>
            <div class="mt-2 text-3xl font-bold">¥{{ number_format($yearlyIncome ?? 0) }}</div>
        </div>
        <div class="rounded-lg bg-gradient-to-br from-red-400 to-red-600 p-6 text-white shadow-lg">
            <div class="text-sm font-medium opacity-90">年間支出</div>
            <div class="mt-2 text-3xl font-bold">¥{{ number_format($yearlyExpense ?? 0) }}</div>
        </div>
        <div class="rounded-lg bg-gradient-to-br from-blue-400 to-blue-600 p-6 text-white shadow-lg">
            <div class="text-sm font-medium opacity-90">年間利益</div>
            <div class="mt-2 text-3xl font-bold">¥{{ number_format(($yearlyIncome ?? 0) - ($yearlyExpense ?? 0)) }}</div>
        </div>
    </div>

    <!-- 月別収支グラフ -->
    <div class="mb-6 overflow-hidden rounded-lg bg-white shadow">
        <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">月別収支推移</h3>
            <div class="h-96">
                <canvas id="monthlyChart"></canvas>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- 勘定科目別円グラフ（支出） -->
        <div class="overflow-hidden rounded-lg bg-white shadow">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">勘定科目別支出</h3>
                <div class="h-80">
                    <canvas id="expensePieChart"></canvas>
                </div>
            </div>
        </div>

        <!-- 勘定科目別リスト -->
        <div class="overflow-hidden rounded-lg bg-white shadow">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">勘定科目別集計</h3>
                <div class="space-y-3">
                    @forelse($categoryBreakdown ?? [] as $category => $amount)
                    <div class="flex items-center justify-between rounded-lg bg-gray-50 p-3">
                        <span class="text-sm font-medium text-gray-900">{{ $category }}</span>
                        <span class="text-sm font-semibold text-gray-900">¥{{ number_format($amount) }}</span>
                    </div>
                    @empty
                    <p class="text-sm text-gray-500">データがありません</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- 月別詳細テーブル -->
    <div class="mt-6 overflow-hidden rounded-lg bg-white shadow">
        <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">月別詳細</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">月</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">収入</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">支出</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">差引</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">操作</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @for($month = 1; $month <= 12; $month++)
                        <tr class="hover:bg-gray-50">
                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                {{ $month }}月
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-green-600 font-medium">
                                ¥{{ number_format($monthlyData[$month]['income'] ?? 0) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-red-600 font-medium">
                                ¥{{ number_format($monthlyData[$month]['expense'] ?? 0) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold {{ (($monthlyData[$month]['income'] ?? 0) - ($monthlyData[$month]['expense'] ?? 0)) >= 0 ? 'text-blue-600' : 'text-red-600' }}">
                                ¥{{ number_format(($monthlyData[$month]['income'] ?? 0) - ($monthlyData[$month]['expense'] ?? 0)) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                <a href="{{ route('transactions.index', ['year' => request('year', date('Y')), 'month' => $month]) }}" class="text-blue-600 hover:text-blue-900">
                                    詳細 →
                                </a>
                            </td>
                        </tr>
                        @endfor
                    </tbody>
                    <tfoot class="bg-gray-100">
                        <tr>
                            <td class="whitespace-nowrap px-6 py-4 text-sm font-bold text-gray-900">合計</td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-bold text-green-600">
                                ¥{{ number_format($yearlyIncome ?? 0) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-bold text-red-600">
                                ¥{{ number_format($yearlyExpense ?? 0) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-bold text-blue-600">
                                ¥{{ number_format(($yearlyIncome ?? 0) - ($yearlyExpense ?? 0)) }}
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- エクスポート -->
    <div class="mt-6 flex justify-end gap-3">
        <a href="{{ route('reports.export', ['type' => 'monthly', 'format' => 'pdf', 'year' => request('year', date('Y'))]) }}" class="inline-flex items-center rounded-md bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
            <svg class="-ml-1 mr-2 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
            PDF出力
        </a>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    // 年選択の変更
    document.getElementById('year-select').addEventListener('change', function() {
        window.location.href = '{{ route("reports.monthly") }}?year=' + this.value;
    });

    // 月別収支グラフ
    const monthlyCtx = document.getElementById('monthlyChart');
    if (monthlyCtx) {
        new Chart(monthlyCtx, {
            type: 'bar',
            data: {
                labels: ['1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月'],
                datasets: [
                    {
                        label: '収入',
                        data: @json(array_column($monthlyData ?? array_fill(1, 12, ['income' => 0]), 'income')),
                        backgroundColor: 'rgba(34, 197, 94, 0.5)',
                        borderColor: 'rgb(34, 197, 94)',
                        borderWidth: 2
                    },
                    {
                        label: '支出',
                        data: @json(array_column($monthlyData ?? array_fill(1, 12, ['expense' => 0]), 'expense')),
                        backgroundColor: 'rgba(239, 68, 68, 0.5)',
                        borderColor: 'rgb(239, 68, 68)',
                        borderWidth: 2
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

    // 勘定科目別円グラフ
    const pieCtx = document.getElementById('expensePieChart');
    if (pieCtx) {
        new Chart(pieCtx, {
            type: 'doughnut',
            data: {
                labels: @json(array_keys($categoryBreakdown ?? [])),
                datasets: [{
                    data: @json(array_values($categoryBreakdown ?? [])),
                    backgroundColor: [
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(251, 146, 60, 0.8)',
                        'rgba(250, 204, 21, 0.8)',
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(139, 92, 246, 0.8)',
                        'rgba(236, 72, 153, 0.8)',
                        'rgba(156, 163, 175, 0.8)',
                    ],
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return label + ': ¥' + value.toLocaleString() + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    }
</script>
@endsection
