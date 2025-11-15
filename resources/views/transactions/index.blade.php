@extends('layouts.app')

@section('title', '取引一覧')

@section('content')
<div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
    <!-- ヘッダー -->
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">取引一覧</h1>
            <p class="mt-1 text-sm text-gray-500">収入・支出の記録を管理します</p>
        </div>
        <a href="{{ route('transactions.create') }}" class="inline-flex items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            新規登録
        </a>
    </div>

    <!-- フィルター -->
    <div class="mb-6 rounded-lg bg-white p-6 shadow">
        <form method="GET" action="{{ route('transactions.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <!-- 期間（開始） -->
            <div>
                <label for="date_from" class="block text-sm font-medium text-gray-700">期間（開始）</label>
                <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
            </div>

            <!-- 期間（終了） -->
            <div>
                <label for="date_to" class="block text-sm font-medium text-gray-700">期間（終了）</label>
                <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
            </div>

            <!-- 区分 -->
            <div>
                <label for="type" class="block text-sm font-medium text-gray-700">区分</label>
                <select name="type" id="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    <option value="">すべて</option>
                    <option value="income" {{ request('type') === 'income' ? 'selected' : '' }}>収入</option>
                    <option value="expense" {{ request('type') === 'expense' ? 'selected' : '' }}>支出</option>
                </select>
            </div>

            <!-- 勘定科目 -->
            <div>
                <label for="category" class="block text-sm font-medium text-gray-700">勘定科目</label>
                <select name="category" id="category" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    <option value="">すべて</option>
                    @foreach($categories ?? [] as $category)
                        <option value="{{ $category }}" {{ request('category') === $category ? 'selected' : '' }}>{{ $category }}</option>
                    @endforeach
                </select>
            </div>

            <!-- 検索ボタン -->
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                    検索
                </button>
                <a href="{{ route('transactions.index') }}" class="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                    クリア
                </a>
            </div>
        </form>
    </div>

    <!-- サマリー -->
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-lg bg-white p-4 shadow">
            <div class="text-sm font-medium text-gray-500">収入合計</div>
            <div class="mt-1 text-2xl font-semibold text-green-600">¥{{ number_format($totalIncome ?? 0) }}</div>
        </div>
        <div class="rounded-lg bg-white p-4 shadow">
            <div class="text-sm font-medium text-gray-500">支出合計</div>
            <div class="mt-1 text-2xl font-semibold text-red-600">¥{{ number_format($totalExpense ?? 0) }}</div>
        </div>
        <div class="rounded-lg bg-white p-4 shadow">
            <div class="text-sm font-medium text-gray-500">差引</div>
            <div class="mt-1 text-2xl font-semibold text-blue-600">¥{{ number_format(($totalIncome ?? 0) - ($totalExpense ?? 0)) }}</div>
        </div>
    </div>

    <!-- 取引一覧テーブル -->
    <div class="overflow-hidden rounded-lg bg-white shadow">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                            日付
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                            区分
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                            勘定科目
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                            摘要
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                            取引先
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                            金額
                        </th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">
                            添付
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                            操作
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($transactions ?? [] as $transaction)
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
                        <td class="px-6 py-4 text-sm text-gray-900">
                            {{ Str::limit($transaction->description, 40) }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                            {{ $transaction->client ?? '-' }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium {{ $transaction->type === 'income' ? 'text-green-600' : 'text-gray-900' }}">
                            {{ $transaction->type === 'income' ? '+' : '-' }}¥{{ number_format($transaction->amount) }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-center text-sm">
                            @if($transaction->receipt_path)
                                <a href="{{ $transaction->receipt_url }}" target="_blank" class="text-blue-600 hover:text-blue-900">
                                    <svg class="inline h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                    </svg>
                                </a>
                            @else
                                <span class="text-gray-300">-</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('transactions.edit', $transaction->id) }}" class="text-blue-600 hover:text-blue-900">編集</a>
                                <form method="POST" action="{{ route('transactions.destroy', $transaction->id) }}" class="inline" onsubmit="return confirm('本当に削除しますか？')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900">削除</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-sm text-gray-500">
                            <div class="flex flex-col items-center">
                                <svg class="mb-4 h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="mb-2 text-gray-900">取引がまだ登録されていません</p>
                                <a href="{{ route('transactions.create') }}" class="text-blue-600 hover:text-blue-500">取引を登録する →</a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- ページネーション -->
        @if(isset($transactions) && is_object($transactions) && method_exists($transactions, 'links'))
        <div class="border-t border-gray-200 bg-white px-4 py-3 sm:px-6">
            {{ $transactions->links() }}
        </div>
        @endif
    </div>

    <!-- エクスポート -->
    <div class="mt-6 flex justify-end gap-3">
        <a href="{{ route('transactions.export', ['format' => 'csv']) }}" class="inline-flex items-center rounded-md bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
            <svg class="-ml-1 mr-2 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            CSV出力
        </a>
        <a href="{{ route('transactions.export', ['format' => 'pdf']) }}" class="inline-flex items-center rounded-md bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
            <svg class="-ml-1 mr-2 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
            PDF出力
        </a>
    </div>
</div>
@endsection
