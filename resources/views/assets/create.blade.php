@extends('layouts.app')

@section('title', '固定資産登録')

@section('content')
<div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
    <!-- ヘッダー -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">固定資産登録</h1>
        <p class="mt-1 text-sm text-gray-500">パソコンなど高額な資産を登録し、減価償却を管理します</p>
    </div>

    <!-- フォーム -->
    <form method="POST" action="{{ route('assets.store') }}" class="space-y-6">
        @csrf

        <div class="rounded-lg bg-white p-6 shadow">
            <!-- 資産名 -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">
                    資産名 <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm @error('name') border-red-300 @enderror" placeholder="例: MacBook Pro 2024">
                @error('name')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- 資産区分 -->
            <div class="mt-6">
                <label for="category" class="block text-sm font-medium text-gray-700">
                    資産区分 <span class="text-red-500">*</span>
                </label>
                <select name="category" id="category" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm @error('category') border-red-300 @enderror">
                    <option value="">選択してください</option>
                    <option value="パソコン" data-years="4" {{ old('category') === 'パソコン' ? 'selected' : '' }}>💻 パソコン（耐用年数: 4年）</option>
                    <option value="ソフトウェア" data-years="5" {{ old('category') === 'ソフトウェア' ? 'selected' : '' }}>💿 ソフトウェア（耐用年数: 5年）</option>
                    <option value="普通車" data-years="6" {{ old('category') === '普通車' ? 'selected' : '' }}>🚗 普通車（耐用年数: 6年）</option>
                    <option value="軽自動車" data-years="4" {{ old('category') === '軽自動車' ? 'selected' : '' }}>🚙 軽自動車（耐用年数: 4年）</option>
                    <option value="機械装置" data-years="8" {{ old('category') === '機械装置' ? 'selected' : '' }}>⚙️ 機械装置（耐用年数: 8年）</option>
                    <option value="器具備品" data-years="5" {{ old('category') === '器具備品' ? 'selected' : '' }}>📦 器具備品（耐用年数: 5年）</option>
                    <option value="その他" data-years="5" {{ old('category') === 'その他' ? 'selected' : '' }}>その他</option>
                </select>
                @error('category')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2">
                <!-- 取得日 -->
                <div>
                    <label for="acquisition_date" class="block text-sm font-medium text-gray-700">
                        取得日 <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="acquisition_date" id="acquisition_date" value="{{ old('acquisition_date', date('Y-m-d')) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm @error('acquisition_date') border-red-300 @enderror">
                    @error('acquisition_date')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 取得価額 -->
                <div>
                    <label for="acquisition_cost" class="block text-sm font-medium text-gray-700">
                        取得価額 <span class="text-red-500">*</span>
                    </label>
                    <div class="relative mt-1 rounded-md shadow-sm">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <span class="text-gray-500 sm:text-sm">¥</span>
                        </div>
                        <input type="number" name="acquisition_cost" id="acquisition_cost" value="{{ old('acquisition_cost') }}" min="100000" step="1" required class="block w-full rounded-md border-gray-300 pl-7 pr-12 focus:border-purple-500 focus:ring-purple-500 sm:text-sm @error('acquisition_cost') border-red-300 @enderror" placeholder="100000">
                    </div>
                    <p class="mt-1 text-xs text-gray-500">※ 10万円以上の資産が対象です</p>
                    @error('acquisition_cost')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- 耐用年数 -->
            <div class="mt-6">
                <label for="useful_life" class="block text-sm font-medium text-gray-700">
                    耐用年数 <span class="text-red-500">*</span>
                </label>
                <div class="mt-1 flex rounded-md shadow-sm">
                    <input type="number" name="useful_life" id="useful_life" value="{{ old('useful_life', 4) }}" min="1" max="50" required class="block w-full rounded-md border-gray-300 focus:border-purple-500 focus:ring-purple-500 sm:text-sm @error('useful_life') border-red-300 @enderror">
                    <span class="inline-flex items-center rounded-r-md border border-l-0 border-gray-300 bg-gray-50 px-3 text-gray-500 sm:text-sm">年</span>
                </div>
                <p class="mt-1 text-xs text-gray-500">※ 資産区分を選択すると自動で設定されます</p>
                @error('useful_life')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- 償却方法 -->
            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700">
                    償却方法 <span class="text-red-500">*</span>
                </label>
                <div class="mt-2 space-y-2">
                    <label class="relative flex cursor-pointer rounded-lg border bg-white p-4 shadow-sm focus:outline-none {{ old('depreciation_method', 'straight_line') === 'straight_line' ? 'border-purple-600 ring-2 ring-purple-600' : 'border-gray-300' }}">
                        <input type="radio" name="depreciation_method" value="straight_line" class="sr-only" {{ old('depreciation_method', 'straight_line') === 'straight_line' ? 'checked' : '' }} required>
                        <span class="flex flex-1">
                            <span class="flex flex-col">
                                <span class="block text-sm font-medium text-gray-900">定額法（おすすめ）</span>
                                <span class="mt-1 flex items-center text-sm text-gray-500">毎年同じ金額を償却します</span>
                            </span>
                        </span>
                    </label>
                    <label class="relative flex cursor-pointer rounded-lg border bg-white p-4 shadow-sm focus:outline-none {{ old('depreciation_method') === 'declining_balance' ? 'border-purple-600 ring-2 ring-purple-600' : 'border-gray-300' }}">
                        <input type="radio" name="depreciation_method" value="declining_balance" class="sr-only" {{ old('depreciation_method') === 'declining_balance' ? 'checked' : '' }} required>
                        <span class="flex flex-1">
                            <span class="flex flex-col">
                                <span class="block text-sm font-medium text-gray-900">定率法</span>
                                <span class="mt-1 flex items-center text-sm text-gray-500">初年度の償却額が大きくなります</span>
                            </span>
                        </span>
                    </label>
                </div>
                @error('depreciation_method')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- 事業使用割合 -->
            <div class="mt-6">
                <label for="business_use_ratio" class="block text-sm font-medium text-gray-700">
                    事業使用割合
                </label>
                <div class="mt-1 flex items-center gap-4">
                    <input type="range" name="business_use_ratio" id="business_use_ratio" value="{{ old('business_use_ratio', 100) }}" min="0" max="100" step="10" class="block w-full">
                    <span id="ratio-display" class="text-sm font-medium text-gray-900 w-16 text-right">100%</span>
                </div>
                <p class="mt-1 text-xs text-gray-500">※ プライベートでも使用する場合は、事業で使用する割合を設定してください</p>
                @error('business_use_ratio')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- メモ -->
            <div class="mt-6">
                <label for="memo" class="block text-sm font-medium text-gray-700">
                    メモ
                </label>
                <textarea name="memo" id="memo" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm" placeholder="購入先、シリアル番号などの補足情報">{{ old('memo') }}</textarea>
            </div>
        </div>

        <!-- 償却シミュレーション -->
        <div class="rounded-lg bg-purple-50 p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">償却シミュレーション</h3>
            <div id="simulation-result" class="space-y-2 text-sm text-gray-700">
                <p>資産情報を入力すると、償却シミュレーションが表示されます</p>
            </div>
        </div>

        <!-- ボタン -->
        <div class="flex justify-end gap-3">
            <a href="{{ route('assets.index') }}" class="rounded-md bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                キャンセル
            </a>
            <button type="submit" class="inline-flex justify-center rounded-md bg-purple-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">
                登録する
            </button>
        </div>
    </form>
</div>

<script>
    // 資産区分選択時に耐用年数を自動設定
    document.getElementById('category').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const years = selectedOption.getAttribute('data-years');
        if (years) {
            document.getElementById('useful_life').value = years;
            updateSimulation();
        }
    });

    // 事業使用割合のスライダー
    const ratioSlider = document.getElementById('business_use_ratio');
    const ratioDisplay = document.getElementById('ratio-display');

    ratioSlider.addEventListener('input', function() {
        ratioDisplay.textContent = this.value + '%';
        updateSimulation();
    });

    // 償却シミュレーション更新
    const inputs = ['acquisition_cost', 'useful_life', 'depreciation_method', 'business_use_ratio'];
    inputs.forEach(id => {
        const element = document.getElementById(id);
        if (element.type === 'radio') {
            document.querySelectorAll(`input[name="${id}"]`).forEach(radio => {
                radio.addEventListener('change', updateSimulation);
            });
        } else {
            element.addEventListener('input', updateSimulation);
        }
    });

    function updateSimulation() {
        const cost = parseInt(document.getElementById('acquisition_cost').value) || 0;
        const life = parseInt(document.getElementById('useful_life').value) || 0;
        const method = document.querySelector('input[name="depreciation_method"]:checked')?.value || 'straight_line';
        const ratio = parseInt(document.getElementById('business_use_ratio').value) || 100;

        if (cost === 0 || life === 0) {
            return;
        }

        let yearlyDepreciation;
        if (method === 'straight_line') {
            yearlyDepreciation = Math.floor(cost / life);
        } else {
            // 定率法（簡易計算）
            const rate = 1 / life;
            yearlyDepreciation = Math.floor(cost * rate);
        }

        const businessDepreciation = Math.floor(yearlyDepreciation * (ratio / 100));

        const html = `
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <div class="text-xs text-gray-500">年間償却額（100%）</div>
                    <div class="text-xl font-semibold text-gray-900">¥${yearlyDepreciation.toLocaleString()}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500">経費計上額（事業使用${ratio}%）</div>
                    <div class="text-xl font-semibold text-purple-600">¥${businessDepreciation.toLocaleString()}</div>
                </div>
            </div>
            <div class="mt-4 text-xs text-gray-600">
                <p>💡 ${life}年間で、毎年¥${businessDepreciation.toLocaleString()}ずつ経費計上できます</p>
            </div>
        `;

        document.getElementById('simulation-result').innerHTML = html;
    }

    // ラジオボタンのスタイル切り替え
    document.querySelectorAll('input[name="depreciation_method"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.querySelectorAll('input[name="depreciation_method"]').forEach(r => {
                const label = r.closest('label');
                if (r.checked) {
                    label.classList.add('border-purple-600', 'ring-2', 'ring-purple-600');
                    label.classList.remove('border-gray-300');
                } else {
                    label.classList.remove('border-purple-600', 'ring-purple-600', 'ring-2');
                    label.classList.add('border-gray-300');
                }
            });
        });
    });
</script>
@endsection
