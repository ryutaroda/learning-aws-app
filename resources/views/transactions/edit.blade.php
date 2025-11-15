@extends('layouts.app')

@section('title', '取引編集')

@section('content')
<div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
    <!-- ヘッダー -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">取引編集</h1>
        <p class="mt-1 text-sm text-gray-500">取引情報を編集します</p>
    </div>

    <!-- フォーム -->
    <form method="POST" action="{{ route('transactions.update', $transaction->id) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="rounded-lg bg-white p-6 shadow">
            <!-- 区分 -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700">区分 <span class="text-red-500">*</span></label>
                <div class="mt-2 grid grid-cols-2 gap-4">
                    <label class="relative flex cursor-pointer rounded-lg border bg-white p-4 shadow-sm focus:outline-none {{ old('type', $transaction->type) === 'income' ? 'border-green-600 ring-2 ring-green-600' : 'border-gray-300' }}">
                        <input type="radio" name="type" value="income" class="sr-only" {{ old('type', $transaction->type) === 'income' ? 'checked' : '' }} required>
                        <span class="flex flex-1">
                            <span class="flex flex-col">
                                <span class="block text-sm font-medium text-gray-900">💰 収入</span>
                                <span class="mt-1 flex items-center text-sm text-gray-500">売上・報酬など</span>
                            </span>
                        </span>
                    </label>
                    <label class="relative flex cursor-pointer rounded-lg border bg-white p-4 shadow-sm focus:outline-none {{ old('type', $transaction->type) === 'expense' ? 'border-red-600 ring-2 ring-red-600' : 'border-gray-300' }}">
                        <input type="radio" name="type" value="expense" class="sr-only" {{ old('type', $transaction->type) === 'expense' ? 'checked' : '' }} required>
                        <span class="flex flex-1">
                            <span class="flex flex-col">
                                <span class="block text-sm font-medium text-gray-900">💸 支出</span>
                                <span class="mt-1 flex items-center text-sm text-gray-500">経費・仕入など</span>
                            </span>
                        </span>
                    </label>
                </div>
                @error('type')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <!-- 日付 -->
                <div>
                    <label for="date" class="block text-sm font-medium text-gray-700">
                        日付 <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="date" id="date" value="{{ old('date', $transaction->date) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('date') border-red-300 @enderror">
                    @error('date')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 金額 -->
                <div>
                    <label for="amount" class="block text-sm font-medium text-gray-700">
                        金額 <span class="text-red-500">*</span>
                    </label>
                    <div class="relative mt-1 rounded-md shadow-sm">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <span class="text-gray-500 sm:text-sm">¥</span>
                        </div>
                        <input type="number" name="amount" id="amount" value="{{ old('amount', $transaction->amount) }}" min="0" step="1" required class="block w-full rounded-md border-gray-300 pl-7 pr-12 focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('amount') border-red-300 @enderror" placeholder="0">
                    </div>
                    @error('amount')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- 勘定科目 -->
            <div class="mt-6">
                <label for="category" class="block text-sm font-medium text-gray-700">
                    勘定科目 <span class="text-red-500">*</span>
                </label>
                <select name="category" id="category" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('category') border-red-300 @enderror">
                    <option value="">選択してください</option>
                    <optgroup label="収入">
                        <option value="売上高" {{ old('category', $transaction->category) === '売上高' ? 'selected' : '' }}>売上高</option>
                        <option value="雑収入" {{ old('category', $transaction->category) === '雑収入' ? 'selected' : '' }}>雑収入</option>
                    </optgroup>
                    <optgroup label="経費">
                        <option value="仕入高" {{ old('category', $transaction->category) === '仕入高' ? 'selected' : '' }}>仕入高</option>
                        <option value="給料賃金" {{ old('category', $transaction->category) === '給料賃金' ? 'selected' : '' }}>給料賃金</option>
                        <option value="外注工賃" {{ old('category', $transaction->category) === '外注工賃' ? 'selected' : '' }}>外注工賃</option>
                        <option value="減価償却費" {{ old('category', $transaction->category) === '減価償却費' ? 'selected' : '' }}>減価償却費</option>
                        <option value="地代家賃" {{ old('category', $transaction->category) === '地代家賃' ? 'selected' : '' }}>地代家賃</option>
                        <option value="水道光熱費" {{ old('category', $transaction->category) === '水道光熱費' ? 'selected' : '' }}>水道光熱費</option>
                        <option value="通信費" {{ old('category', $transaction->category) === '通信費' ? 'selected' : '' }}>通信費</option>
                        <option value="旅費交通費" {{ old('category', $transaction->category) === '旅費交通費' ? 'selected' : '' }}>旅費交通費</option>
                        <option value="接待交際費" {{ old('category', $transaction->category) === '接待交際費' ? 'selected' : '' }}>接待交際費</option>
                        <option value="会議費" {{ old('category', $transaction->category) === '会議費' ? 'selected' : '' }}>会議費</option>
                        <option value="消耗品費" {{ old('category', $transaction->category) === '消耗品費' ? 'selected' : '' }}>消耗品費</option>
                        <option value="広告宣伝費" {{ old('category', $transaction->category) === '広告宣伝費' ? 'selected' : '' }}>広告宣伝費</option>
                        <option value="雑費" {{ old('category', $transaction->category) === '雑費' ? 'selected' : '' }}>雑費</option>
                    </optgroup>
                </select>
                @error('category')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- 摘要 -->
            <div class="mt-6">
                <label for="description" class="block text-sm font-medium text-gray-700">
                    摘要 <span class="text-red-500">*</span>
                </label>
                <textarea name="description" id="description" rows="3" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('description') border-red-300 @enderror" placeholder="取引の内容を入力してください">{{ old('description', $transaction->description) }}</textarea>
                @error('description')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- 取引先 -->
            <div class="mt-6">
                <label for="client" class="block text-sm font-medium text-gray-700">
                    取引先
                </label>
                <input type="text" name="client" id="client" value="{{ old('client', $transaction->client) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('client') border-red-300 @enderror" placeholder="例: 株式会社〇〇">
                @error('client')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- 現在の領収書 -->
            @if($transaction->receipt_path)
            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    現在の領収書
                </label>
                <div class="flex items-center gap-4">
                    <a href="{{ $transaction->receipt_url }}" target="_blank" class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                        <svg class="mr-2 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                        </svg>
                        ファイルを表示
                    </a>
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="delete_receipt" value="1" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <span class="ml-2 text-sm text-gray-600">削除する</span>
                    </label>
                </div>
            </div>
            @endif

            <!-- 領収書添付 -->
            <div class="mt-6">
                <label for="receipt" class="block text-sm font-medium text-gray-700">
                    領収書・請求書{{ $transaction->receipt_path ? '（新しいファイルをアップロード）' : '' }}
                </label>
                <div class="mt-1 flex justify-center rounded-md border-2 border-dashed border-gray-300 px-6 pb-6 pt-5">
                    <div class="space-y-1 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <div class="flex text-sm text-gray-600">
                            <label for="receipt" class="relative cursor-pointer rounded-md bg-white font-medium text-blue-600 focus-within:outline-none focus-within:ring-2 focus-within:ring-blue-500 focus-within:ring-offset-2 hover:text-blue-500">
                                <span>ファイルをアップロード</span>
                                <input id="receipt" name="receipt" type="file" accept="image/*,.pdf" class="sr-only">
                            </label>
                            <p class="pl-1">またはドラッグ&ドロップ</p>
                        </div>
                        <p class="text-xs text-gray-500">PNG, JPG, PDF (最大10MB)</p>
                    </div>
                </div>
                @error('receipt')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- メモ -->
            <div class="mt-6">
                <label for="memo" class="block text-sm font-medium text-gray-700">
                    メモ
                </label>
                <textarea name="memo" id="memo" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" placeholder="補足情報など（任意）">{{ old('memo', $transaction->memo) }}</textarea>
            </div>
        </div>

        <!-- ボタン -->
        <div class="flex justify-end gap-3">
            <a href="{{ route('transactions.index') }}" class="rounded-md bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                キャンセル
            </a>
            <button type="submit" class="inline-flex justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                更新する
            </button>
        </div>
    </form>
</div>

<script>
    // ラジオボタンのスタイル切り替え
    document.querySelectorAll('input[name="type"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.querySelectorAll('input[name="type"]').forEach(r => {
                const label = r.closest('label');
                if (r.checked) {
                    if (r.value === 'income') {
                        label.classList.add('border-green-600', 'ring-2', 'ring-green-600');
                        label.classList.remove('border-red-600', 'ring-red-600', 'border-gray-300');
                    } else {
                        label.classList.add('border-red-600', 'ring-2', 'ring-red-600');
                        label.classList.remove('border-green-600', 'ring-green-600', 'border-gray-300');
                    }
                } else {
                    label.classList.remove('border-green-600', 'ring-green-600', 'border-red-600', 'ring-red-600', 'ring-2');
                    label.classList.add('border-gray-300');
                }
            });
        });
    });
</script>
@endsection
