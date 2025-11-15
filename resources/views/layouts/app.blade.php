<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', '簡単帳簿') }} - @yield('title', 'フリーランス向け確定申告支援')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 font-sans antialiased">
    <div class="min-h-screen">
        <!-- ナビゲーション -->
        <nav class="bg-white border-b border-gray-200">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between">
                    <div class="flex">
                        <!-- Logo -->
                        <div class="flex shrink-0 items-center">
                            <a href="{{ route('dashboard') }}" class="text-xl font-bold text-blue-600">
                                📊 簡単帳簿
                            </a>
                        </div>

                        <!-- ナビゲーションリンク -->
                        <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center border-b-2 {{ request()->routeIs('dashboard') ? 'border-blue-600 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }} px-1 pt-1 text-sm font-medium transition">
                                ダッシュボード
                            </a>
                            <a href="{{ route('transactions.index') }}" class="inline-flex items-center border-b-2 {{ request()->routeIs('transactions.*') ? 'border-blue-600 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }} px-1 pt-1 text-sm font-medium transition">
                                取引記録
                            </a>
                            <a href="{{ route('receipts.create') }}" class="inline-flex items-center border-b-2 {{ request()->routeIs('receipts.*') ? 'border-blue-600 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }} px-1 pt-1 text-sm font-medium transition">
                                レシート登録
                            </a>
                            <a href="{{ route('assets.index') }}" class="inline-flex items-center border-b-2 {{ request()->routeIs('assets.*') ? 'border-blue-600 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }} px-1 pt-1 text-sm font-medium transition">
                                固定資産
                            </a>
                            <a href="{{ route('reports.monthly') }}" class="inline-flex items-center border-b-2 {{ request()->routeIs('reports.*') ? 'border-blue-600 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }} px-1 pt-1 text-sm font-medium transition">
                                レポート
                            </a>
                        </div>
                    </div>

                    <!-- ユーザーメニュー -->
                    <div class="hidden sm:ml-6 sm:flex sm:items-center">
                        <div class="relative ml-3">
                            <div class="flex items-center space-x-4">
                                <span class="text-sm text-gray-700">{{ auth()->user()->name ?? 'ユーザー' }}</span>
                                <form method="POST" action="{{ route('logout') }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-sm text-gray-500 hover:text-gray-700">
                                        ログアウト
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- モバイルメニューボタン -->
                    <div class="-mr-2 flex items-center sm:hidden">
                        <button type="button" class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500" id="mobile-menu-button">
                            <span class="sr-only">メニューを開く</span>
                            <svg class="block h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- モバイルメニュー -->
            <div class="hidden sm:hidden" id="mobile-menu">
                <div class="space-y-1 pb-3 pt-2">
                    <a href="{{ route('dashboard') }}" class="block border-l-4 {{ request()->routeIs('dashboard') ? 'border-blue-600 bg-blue-50 text-blue-700' : 'border-transparent text-gray-600 hover:border-gray-300 hover:bg-gray-50 hover:text-gray-800' }} py-2 pl-3 pr-4 text-base font-medium">
                        ダッシュボード
                    </a>
                    <a href="{{ route('transactions.index') }}" class="block border-l-4 {{ request()->routeIs('transactions.*') ? 'border-blue-600 bg-blue-50 text-blue-700' : 'border-transparent text-gray-600 hover:border-gray-300 hover:bg-gray-50 hover:text-gray-800' }} py-2 pl-3 pr-4 text-base font-medium">
                        取引記録
                    </a>
                    <a href="{{ route('receipts.create') }}" class="block border-l-4 {{ request()->routeIs('receipts.*') ? 'border-blue-600 bg-blue-50 text-blue-700' : 'border-transparent text-gray-600 hover:border-gray-300 hover:bg-gray-50 hover:text-gray-800' }} py-2 pl-3 pr-4 text-base font-medium">
                        レシート登録
                    </a>
                    <a href="{{ route('assets.index') }}" class="block border-l-4 {{ request()->routeIs('assets.*') ? 'border-blue-600 bg-blue-50 text-blue-700' : 'border-transparent text-gray-600 hover:border-gray-300 hover:bg-gray-50 hover:text-gray-800' }} py-2 pl-3 pr-4 text-base font-medium">
                        固定資産
                    </a>
                    <a href="{{ route('reports.monthly') }}" class="block border-l-4 {{ request()->routeIs('reports.*') ? 'border-blue-600 bg-blue-50 text-blue-700' : 'border-transparent text-gray-600 hover:border-gray-300 hover:bg-gray-50 hover:text-gray-800' }} py-2 pl-3 pr-4 text-base font-medium">
                        レポート
                    </a>
                </div>
            </div>
        </nav>

        <!-- ページヘッダー -->
        @if (isset($header))
            <header class="bg-white shadow">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endif

        <!-- ページコンテンツ -->
        <main class="py-8">
            @if (session('success'))
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="rounded-md bg-green-50 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="rounded-md bg-red-50 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <script>
        // モバイルメニュートグル
        document.getElementById('mobile-menu-button')?.addEventListener('click', function() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        });
    </script>
</body>
</html>
