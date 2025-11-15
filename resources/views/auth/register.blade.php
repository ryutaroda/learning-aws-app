<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>新規登録 - {{ config('app.name', '簡単帳簿') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 font-sans antialiased">
    <div class="flex min-h-screen flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <!-- Logo -->
            <div class="flex justify-center">
                <div class="text-center">
                    <div class="text-5xl mb-2">📊</div>
                    <h2 class="text-3xl font-bold tracking-tight text-gray-900">簡単帳簿</h2>
                    <p class="mt-2 text-sm text-gray-600">フリーランス向け確定申告支援アプリ</p>
                </div>
            </div>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white px-4 py-8 shadow sm:rounded-lg sm:px-10">
                <h3 class="mb-6 text-center text-xl font-semibold text-gray-900">新規アカウント作成</h3>

                <!-- エラーメッセージ -->
                @if ($errors->any())
                    <div class="mb-4 rounded-md bg-red-50 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">入力内容をご確認ください</h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <ul class="list-disc space-y-1 pl-5">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('register') }}" class="space-y-6">
                    @csrf

                    <!-- 名前 -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">
                            お名前 <span class="text-red-500">*</span>
                        </label>
                        <div class="mt-1">
                            <input id="name" name="name" type="text" autocomplete="name" required autofocus value="{{ old('name') }}" class="block w-full appearance-none rounded-md border border-gray-300 px-3 py-2 placeholder-gray-400 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500 sm:text-sm @error('name') border-red-300 @enderror" placeholder="山田太郎">
                        </div>
                        @error('name')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- メールアドレス -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">
                            メールアドレス <span class="text-red-500">*</span>
                        </label>
                        <div class="mt-1">
                            <input id="email" name="email" type="email" autocomplete="email" required value="{{ old('email') }}" class="block w-full appearance-none rounded-md border border-gray-300 px-3 py-2 placeholder-gray-400 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500 sm:text-sm @error('email') border-red-300 @enderror" placeholder="example@example.com">
                        </div>
                        @error('email')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- パスワード -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">
                            パスワード <span class="text-red-500">*</span>
                        </label>
                        <div class="mt-1">
                            <input id="password" name="password" type="password" autocomplete="new-password" required class="block w-full appearance-none rounded-md border border-gray-300 px-3 py-2 placeholder-gray-400 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500 sm:text-sm @error('password') border-red-300 @enderror" placeholder="8文字以上">
                        </div>
                        <p class="mt-1 text-xs text-gray-500">※ 8文字以上で設定してください</p>
                        @error('password')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- パスワード確認 -->
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
                            パスワード（確認） <span class="text-red-500">*</span>
                        </label>
                        <div class="mt-1">
                            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required class="block w-full appearance-none rounded-md border border-gray-300 px-3 py-2 placeholder-gray-400 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500 sm:text-sm">
                        </div>
                    </div>

                    <!-- 利用規約 -->
                    <div class="flex items-start">
                        <div class="flex h-5 items-center">
                            <input id="terms" name="terms" type="checkbox" required class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="terms" class="font-medium text-gray-700">
                                <a href="#" class="text-blue-600 hover:text-blue-500">利用規約</a>と<a href="#" class="text-blue-600 hover:text-blue-500">プライバシーポリシー</a>に同意します <span class="text-red-500">*</span>
                            </label>
                        </div>
                    </div>

                    <!-- 登録ボタン -->
                    <div>
                        <button type="submit" class="flex w-full justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            アカウントを作成
                        </button>
                    </div>
                </form>

                <!-- ログインリンク -->
                <div class="mt-6">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="bg-white px-2 text-gray-500">既にアカウントをお持ちの方</span>
                        </div>
                    </div>

                    <div class="mt-6">
                        <a href="{{ route('login') }}" class="flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            ログイン
                        </a>
                    </div>
                </div>
            </div>

            <!-- フッター -->
            <p class="mt-6 text-center text-xs text-gray-500">
                © {{ date('Y') }} 簡単帳簿. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>
