@extends('layouts.app')

@section('title', '確定申告書類')

@section('content')
<div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
    <!-- ヘッダー -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">📋 確定申告書類</h1>
        <p class="mt-1 text-sm text-gray-500">確定申告に必要な書類を出力します</p>
    </div>

    <!-- 対象年選択 -->
    <div class="mb-6 rounded-lg bg-blue-50 p-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-medium text-gray-900">申告対象年</h3>
                <p class="mt-1 text-sm text-gray-600">確定申告する年度を選択してください</p>
            </div>
            <select id="tax-year" class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @for($y = date('Y') - 1; $y >= date('Y') - 5; $y--)
                    <option value="{{ $y }}" {{ (request('year', date('Y') - 1) == $y) ? 'selected' : '' }}>{{ $y }}年</option>
                @endfor
            </select>
        </div>
    </div>

    <!-- サマリー -->
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-4">
        <div class="rounded-lg bg-white p-6 shadow">
            <div class="text-sm font-medium text-gray-500">収入金額</div>
            <div class="mt-2 text-2xl font-bold text-gray-900">¥{{ number_format($totalIncome ?? 0) }}</div>
        </div>
        <div class="rounded-lg bg-white p-6 shadow">
            <div class="text-sm font-medium text-gray-500">必要経費</div>
            <div class="mt-2 text-2xl font-bold text-gray-900">¥{{ number_format($totalExpense ?? 0) }}</div>
        </div>
        <div class="rounded-lg bg-white p-6 shadow">
            <div class="text-sm font-medium text-gray-500">所得金額</div>
            <div class="mt-2 text-2xl font-bold text-blue-600">¥{{ number_format(($totalIncome ?? 0) - ($totalExpense ?? 0)) }}</div>
        </div>
        <div class="rounded-lg bg-white p-6 shadow">
            <div class="text-sm font-medium text-gray-500">青色申告特別控除</div>
            <div class="mt-2 text-2xl font-bold text-green-600">¥650,000</div>
        </div>
    </div>

    <!-- 書類一覧 -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- 青色申告決算書 -->
        <div class="overflow-hidden rounded-lg bg-white shadow">
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-4">
                <h3 class="text-lg font-medium text-white">青色申告決算書（損益計算書）</h3>
            </div>
            <div class="p-6">
                <p class="text-sm text-gray-600 mb-4">事業所得の収支内訳を記載した書類です。</p>

                <div class="space-y-3 mb-6">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">売上（収入）金額</span>
                        <span class="font-medium">¥{{ number_format($totalIncome ?? 0) }}</span>
                    </div>
                    <div class="border-t pt-3">
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-gray-600">必要経費</span>
                            <span class="font-medium">¥{{ number_format($totalExpense ?? 0) }}</span>
                        </div>
                        @foreach($expenseBreakdown ?? [] as $category => $amount)
                        <div class="flex justify-between text-xs text-gray-500 ml-4">
                            <span>{{ $category }}</span>
                            <span>¥{{ number_format($amount) }}</span>
                        </div>
                        @endforeach
                    </div>
                    <div class="border-t pt-3">
                        <div class="flex justify-between text-base font-bold">
                            <span>所得金額</span>
                            <span class="text-blue-600">¥{{ number_format(($totalIncome ?? 0) - ($totalExpense ?? 0)) }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex gap-2">
                    <a href="{{ route('reports.generate', ['type' => 'aoiro', 'format' => 'pdf', 'year' => request('year', date('Y') - 1)]) }}" class="flex-1 inline-flex justify-center items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        PDF生成
                    </a>
                    <a href="{{ route('reports.generate', ['type' => 'aoiro', 'format' => 'csv', 'year' => request('year', date('Y') - 1)]) }}" class="inline-flex items-center rounded-md bg-white px-4 py-2 text-sm font-medium text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                        CSV
                    </a>
                </div>
            </div>
        </div>

        <!-- 勘定科目内訳書 -->
        <div class="overflow-hidden rounded-lg bg-white shadow">
            <div class="bg-gradient-to-r from-green-500 to-green-600 px-6 py-4">
                <h3 class="text-lg font-medium text-white">勘定科目内訳書</h3>
            </div>
            <div class="p-6">
                <p class="text-sm text-gray-600 mb-4">各勘定科目の詳細な内訳を記載した書類です。</p>

                <div class="space-y-2 mb-6">
                    @foreach($expenseBreakdown ?? [] as $category => $amount)
                    <div class="flex justify-between rounded-lg bg-gray-50 p-3 text-sm">
                        <span class="font-medium text-gray-900">{{ $category }}</span>
                        <span class="font-semibold text-gray-900">¥{{ number_format($amount) }}</span>
                    </div>
                    @endforeach
                </div>

                <div class="flex gap-2">
                    <a href="{{ route('reports.generate', ['type' => 'breakdown', 'format' => 'pdf', 'year' => request('year', date('Y') - 1)]) }}" class="flex-1 inline-flex justify-center items-center rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        PDF生成
                    </a>
                    <a href="{{ route('reports.generate', ['type' => 'breakdown', 'format' => 'csv', 'year' => request('year', date('Y') - 1)]) }}" class="inline-flex items-center rounded-md bg-white px-4 py-2 text-sm font-medium text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                        CSV
                    </a>
                </div>
            </div>
        </div>

        <!-- 減価償却資産台帳 -->
        <div class="overflow-hidden rounded-lg bg-white shadow">
            <div class="bg-gradient-to-r from-purple-500 to-purple-600 px-6 py-4">
                <h3 class="text-lg font-medium text-white">減価償却資産台帳</h3>
            </div>
            <div class="p-6">
                <p class="text-sm text-gray-600 mb-4">固定資産の減価償却費を記載した書類です。</p>

                <div class="space-y-2 mb-6">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">資産件数</span>
                        <span class="font-medium">{{ $assetCount ?? 0 }}件</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">今年度の償却費</span>
                        <span class="font-medium text-purple-600">¥{{ number_format($yearlyDepreciation ?? 0) }}</span>
                    </div>
                </div>

                <div class="flex gap-2">
                    <a href="{{ route('reports.generate', ['type' => 'depreciation', 'format' => 'pdf', 'year' => request('year', date('Y') - 1)]) }}" class="flex-1 inline-flex justify-center items-center rounded-md bg-purple-600 px-4 py-2 text-sm font-medium text-white hover:bg-purple-700">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        PDF生成
                    </a>
                    <a href="{{ route('reports.generate', ['type' => 'depreciation', 'format' => 'csv', 'year' => request('year', date('Y') - 1)]) }}" class="inline-flex items-center rounded-md bg-white px-4 py-2 text-sm font-medium text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                        CSV
                    </a>
                </div>
            </div>
        </div>

        <!-- 弥生会計連携 -->
        <div class="overflow-hidden rounded-lg bg-white shadow">
            <div class="bg-gradient-to-r from-orange-500 to-orange-600 px-6 py-4">
                <h3 class="text-lg font-medium text-white">会計ソフト連携</h3>
            </div>
            <div class="p-6">
                <p class="text-sm text-gray-600 mb-4">弥生会計やfreeeにインポート可能な形式で出力します。</p>

                <div class="space-y-3">
                    <a href="{{ route('reports.generate', ['type' => 'yayoi', 'format' => 'csv', 'year' => request('year', date('Y') - 1)]) }}" class="flex items-center justify-between rounded-lg border-2 border-gray-200 p-4 hover:border-orange-500 hover:bg-orange-50 transition">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <span class="text-2xl">📊</span>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-900">弥生会計形式</p>
                                <p class="text-xs text-gray-500">CSV形式でエクスポート</p>
                            </div>
                        </div>
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </a>
                    <a href="{{ route('reports.generate', ['type' => 'freee', 'format' => 'csv', 'year' => request('year', date('Y') - 1)]) }}" class="flex items-center justify-between rounded-lg border-2 border-gray-200 p-4 hover:border-orange-500 hover:bg-orange-50 transition">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <span class="text-2xl">💼</span>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-900">freee形式</p>
                                <p class="text-xs text-gray-500">CSV形式でエクスポート</p>
                            </div>
                        </div>
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- 注意事項 -->
    <div class="mt-6 rounded-lg bg-yellow-50 p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-yellow-800">ご注意ください</h3>
                <div class="mt-2 text-sm text-yellow-700">
                    <ul class="list-disc space-y-1 pl-5">
                        <li>出力された書類は確認のため必ず内容をご確認ください</li>
                        <li>控除額や所得税額の計算は税理士または税務署にご確認ください</li>
                        <li>e-Taxでの電子申告の場合は別途手続きが必要です</li>
                        <li>確定申告の期限は毎年3月15日です</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // 年選択の変更
    document.getElementById('tax-year').addEventListener('change', function() {
        window.location.href = '{{ route("reports.tax") }}?year=' + this.value;
    });
</script>
@endsection
