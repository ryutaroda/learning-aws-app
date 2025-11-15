@extends('layouts.app')

@section('title', '固定資産管理')

@section('content')
<div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
    <!-- ヘッダー -->
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">💻 固定資産管理</h1>
            <p class="mt-1 text-sm text-gray-500">パソコンなど高額な資産の減価償却を管理します</p>
        </div>
        <a href="{{ route('assets.create') }}" class="inline-flex items-center justify-center rounded-md bg-purple-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">
            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            新規登録
        </a>
    </div>

    <!-- サマリーカード -->
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-4">
        <div class="rounded-lg bg-white p-4 shadow">
            <div class="text-sm font-medium text-gray-500">資産総額（簿価）</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900">¥{{ number_format($totalBookValue ?? 0) }}</div>
        </div>
        <div class="rounded-lg bg-white p-4 shadow">
            <div class="text-sm font-medium text-gray-500">取得価額合計</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900">¥{{ number_format($totalAcquisitionCost ?? 0) }}</div>
        </div>
        <div class="rounded-lg bg-white p-4 shadow">
            <div class="text-sm font-medium text-gray-500">今年の償却額</div>
            <div class="mt-1 text-2xl font-semibold text-purple-600">¥{{ number_format($yearlyDepreciation ?? 0) }}</div>
        </div>
        <div class="rounded-lg bg-white p-4 shadow">
            <div class="text-sm font-medium text-gray-500">資産件数</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $assetCount ?? 0 }}件</div>
        </div>
    </div>

    <!-- 固定資産一覧 -->
    <div class="overflow-hidden rounded-lg bg-white shadow">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                            資産名
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                            取得日
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                            耐用年数
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                            取得価額
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                            償却累計額
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                            簿価
                        </th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">
                            償却率
                        </th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">
                            ステータス
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                            操作
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($assets ?? [] as $asset)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    @if($asset->category === 'パソコン')
                                        <span class="text-2xl">💻</span>
                                    @elseif($asset->category === 'ソフトウェア')
                                        <span class="text-2xl">💿</span>
                                    @elseif($asset->category === '車両')
                                        <span class="text-2xl">🚗</span>
                                    @else
                                        <span class="text-2xl">📦</span>
                                    @endif
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $asset->name }}</div>
                                    <div class="text-sm text-gray-500">{{ $asset->category }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                            {{ $asset->acquisition_date }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                            {{ $asset->useful_life }}年
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-900">
                            ¥{{ number_format($asset->acquisition_cost) }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-500">
                            ¥{{ number_format($asset->accumulated_depreciation ?? 0) }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium text-gray-900">
                            ¥{{ number_format($asset->book_value ?? $asset->acquisition_cost) }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-center">
                            <div class="flex items-center justify-center">
                                <div class="w-16 h-2 bg-gray-200 rounded-full overflow-hidden">
                                    <div class="h-full bg-purple-600 rounded-full" style="width: {{ $asset->depreciation_rate ?? 0 }}%"></div>
                                </div>
                                <span class="ml-2 text-sm text-gray-600">{{ number_format($asset->depreciation_rate ?? 0, 1) }}%</span>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-center text-sm">
                            @if($asset->status === 'active')
                                <span class="inline-flex rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-800">使用中</span>
                            @elseif($asset->status === 'fully_depreciated')
                                <span class="inline-flex rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-800">償却済</span>
                            @else
                                <span class="inline-flex rounded-full bg-red-100 px-2 py-1 text-xs font-semibold text-red-800">除却</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('assets.show', $asset->id) }}" class="text-blue-600 hover:text-blue-900">詳細</a>
                                <a href="{{ route('assets.edit', $asset->id) }}" class="text-indigo-600 hover:text-indigo-900">編集</a>
                                <form method="POST" action="{{ route('assets.destroy', $asset->id) }}" class="inline" onsubmit="return confirm('本当に削除しますか？')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900">削除</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-6 py-12 text-center text-sm text-gray-500">
                            <div class="flex flex-col items-center">
                                <svg class="mb-4 h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <p class="mb-2 text-gray-900">固定資産がまだ登録されていません</p>
                                <a href="{{ route('assets.create') }}" class="text-purple-600 hover:text-purple-500">固定資産を登録する →</a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- ページネーション -->
        @if(isset($assets) && is_object($assets) && method_exists($assets, 'links'))
        <div class="border-t border-gray-200 bg-white px-4 py-3 sm:px-6">
            {{ $assets->links() }}
        </div>
        @endif
    </div>

    <!-- 説明 -->
    <div class="mt-6 rounded-lg bg-purple-50 p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-purple-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-purple-800">減価償却について</h3>
                <div class="mt-2 text-sm text-purple-700">
                    <p>10万円以上の資産（パソコン、車両、ソフトウェアなど）は、取得時に一括で経費計上せず、耐用年数に応じて毎年少しずつ経費計上します。</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        <li>パソコン：4年</li>
                        <li>ソフトウェア：5年</li>
                        <li>普通車：6年</li>
                        <li>軽自動車：4年</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
