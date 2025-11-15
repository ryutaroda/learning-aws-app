@extends('layouts.app')

@section('title', 'レシート登録')

@section('content')
<div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
    <!-- ヘッダー -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">📸 レシート登録</h1>
        <p class="mt-1 text-sm text-gray-500">レシートを撮影またはアップロードして、自動で取引を登録します</p>
    </div>

    <!-- ステップ表示 -->
    <div class="mb-8">
        <nav aria-label="Progress">
            <ol role="list" class="overflow-hidden rounded-md lg:flex lg:rounded-none lg:border-l lg:border-r lg:border-gray-200">
                <li class="relative overflow-hidden lg:flex-1">
                    <div class="overflow-hidden border border-gray-200 lg:border-0">
                        <div class="group">
                            <span class="absolute left-0 top-0 h-full w-1 bg-blue-600 lg:bottom-0 lg:top-auto lg:h-1 lg:w-full" aria-hidden="true"></span>
                            <span class="flex items-start px-6 py-5 text-sm font-medium lg:pl-9">
                                <span class="flex-shrink-0">
                                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-600">
                                        <span class="text-white">1</span>
                                    </span>
                                </span>
                                <span class="ml-4 mt-0.5 flex min-w-0 flex-col">
                                    <span class="text-sm font-medium text-blue-600">レシート撮影</span>
                                </span>
                            </span>
                        </div>
                    </div>
                </li>
                <li class="relative overflow-hidden lg:flex-1">
                    <div class="overflow-hidden border border-gray-200 lg:border-0">
                        <div class="group">
                            <span class="absolute left-0 top-0 h-full w-1 bg-gray-300 lg:bottom-0 lg:top-auto lg:h-1 lg:w-full" aria-hidden="true"></span>
                            <span class="flex items-start px-6 py-5 text-sm font-medium lg:pl-9">
                                <span class="flex-shrink-0">
                                    <span class="flex h-10 w-10 items-center justify-center rounded-full border-2 border-gray-300">
                                        <span class="text-gray-500">2</span>
                                    </span>
                                </span>
                                <span class="ml-4 mt-0.5 flex min-w-0 flex-col">
                                    <span class="text-sm font-medium text-gray-500">自動読み取り</span>
                                </span>
                            </span>
                        </div>
                    </div>
                </li>
                <li class="relative overflow-hidden lg:flex-1">
                    <div class="overflow-hidden border border-gray-200 lg:border-0">
                        <div class="group">
                            <span class="absolute left-0 top-0 h-full w-1 bg-gray-300 lg:bottom-0 lg:top-auto lg:h-1 lg:w-full" aria-hidden="true"></span>
                            <span class="flex items-start px-6 py-5 text-sm font-medium lg:pl-9">
                                <span class="flex-shrink-0">
                                    <span class="flex h-10 w-10 items-center justify-center rounded-full border-2 border-gray-300">
                                        <span class="text-gray-500">3</span>
                                    </span>
                                </span>
                                <span class="ml-4 mt-0.5 flex min-w-0 flex-col">
                                    <span class="text-sm font-medium text-gray-500">内容確認</span>
                                </span>
                            </span>
                        </div>
                    </div>
                </li>
            </ol>
        </nav>
    </div>

    <!-- アップロードエリア -->
    <div class="rounded-lg bg-white p-8 shadow">
        <!-- カメラ撮影 -->
        <div class="mb-8">
            <h3 class="text-lg font-medium text-gray-900 mb-4">📷 カメラで撮影</h3>
            <div class="space-y-4">
                <!-- カメラプレビュー -->
                <div id="camera-container" class="relative hidden">
                    <video id="camera-stream" class="w-full rounded-lg" autoplay playsinline></video>
                    <div class="absolute bottom-4 left-1/2 -translate-x-1/2">
                        <button type="button" id="capture-button" class="inline-flex items-center rounded-full bg-blue-600 p-4 text-white shadow-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- カメラ起動ボタン -->
                <button type="button" id="start-camera" class="w-full rounded-md bg-blue-600 px-4 py-3 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <svg class="inline-block -ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    カメラを起動
                </button>
            </div>
        </div>

        <div class="relative mb-8">
            <div class="absolute inset-0 flex items-center" aria-hidden="true">
                <div class="w-full border-t border-gray-300"></div>
            </div>
            <div class="relative flex justify-center">
                <span class="bg-white px-2 text-sm text-gray-500">または</span>
            </div>
        </div>

        <!-- ファイルアップロード -->
        <div>
            <h3 class="text-lg font-medium text-gray-900 mb-4">📁 ファイルをアップロード</h3>
            <form method="POST" action="{{ route('receipts.store') }}" enctype="multipart/form-data" id="upload-form">
                @csrf
                <div class="flex justify-center rounded-lg border-2 border-dashed border-gray-300 px-6 py-10" id="drop-zone">
                    <div class="text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <div class="mt-4 flex text-sm leading-6 text-gray-600">
                            <label for="file-upload" class="relative cursor-pointer rounded-md bg-white font-semibold text-blue-600 focus-within:outline-none focus-within:ring-2 focus-within:ring-blue-600 focus-within:ring-offset-2 hover:text-blue-500">
                                <span>ファイルを選択</span>
                                <input id="file-upload" name="receipt" type="file" accept="image/*,.pdf" class="sr-only" required>
                            </label>
                            <p class="pl-1">またはドラッグ&ドロップ</p>
                        </div>
                        <p class="text-xs leading-5 text-gray-600">PNG, JPG, PDF (最大10MB)</p>
                    </div>
                </div>

                <!-- プレビューエリア -->
                <div id="preview-area" class="mt-6 hidden">
                    <div class="rounded-lg bg-gray-50 p-4">
                        <div class="flex items-start gap-4">
                            <img id="image-preview" class="h-32 w-32 rounded-lg object-cover" alt="プレビュー">
                            <div class="flex-1">
                                <h4 class="text-sm font-medium text-gray-900">ファイル選択済み</h4>
                                <p id="file-name" class="mt-1 text-sm text-gray-500"></p>
                                <p id="file-size" class="mt-1 text-xs text-gray-400"></p>
                                <div class="mt-4 flex gap-3">
                                    <button type="submit" class="inline-flex items-center rounded-md bg-blue-600 px-3 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                        <svg class="-ml-0.5 mr-1.5 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                        </svg>
                                        アップロード
                                    </button>
                                    <button type="button" id="cancel-upload" class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                                        キャンセル
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @error('receipt')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </form>
        </div>
    </div>

    <!-- 使い方のヒント -->
    <div class="mt-6 rounded-lg bg-blue-50 p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">撮影のコツ</h3>
                <div class="mt-2 text-sm text-blue-700">
                    <ul class="list-disc space-y-1 pl-5">
                        <li>レシート全体が写るように撮影してください</li>
                        <li>明るい場所で、影が入らないようにしてください</li>
                        <li>レシートを平らにして、しわを伸ばしてください</li>
                        <li>文字がぼやけないように、ピントを合わせてください</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let stream = null;

    // カメラ起動
    document.getElementById('start-camera').addEventListener('click', async function() {
        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'environment' } // スマホの背面カメラを使用
            });

            const video = document.getElementById('camera-stream');
            video.srcObject = stream;

            document.getElementById('camera-container').classList.remove('hidden');
            this.classList.add('hidden');
        } catch (error) {
            alert('カメラの起動に失敗しました。カメラへのアクセスを許可してください。');
            console.error('Camera error:', error);
        }
    });

    // 撮影
    document.getElementById('capture-button').addEventListener('click', function() {
        const video = document.getElementById('camera-stream');
        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;

        const context = canvas.getContext('2d');
        context.drawImage(video, 0, 0);

        // カメラ停止
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
        }

        // 画像をBlobに変換してフォームに追加
        canvas.toBlob(function(blob) {
            const file = new File([blob], 'receipt.jpg', { type: 'image/jpeg' });
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            document.getElementById('file-upload').files = dataTransfer.files;

            // プレビュー表示
            showPreview(file);
        }, 'image/jpeg', 0.95);

        document.getElementById('camera-container').classList.add('hidden');
        document.getElementById('start-camera').classList.remove('hidden');
    });

    // ファイル選択
    document.getElementById('file-upload').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            showPreview(file);
        }
    });

    // ドラッグ&ドロップ
    const dropZone = document.getElementById('drop-zone');

    dropZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('border-blue-500', 'bg-blue-50');
    });

    dropZone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.classList.remove('border-blue-500', 'bg-blue-50');
    });

    dropZone.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('border-blue-500', 'bg-blue-50');

        const file = e.dataTransfer.files[0];
        if (file && (file.type.startsWith('image/') || file.type === 'application/pdf')) {
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            document.getElementById('file-upload').files = dataTransfer.files;
            showPreview(file);
        } else {
            alert('画像ファイルまたはPDFファイルを選択してください。');
        }
    });

    // プレビュー表示
    function showPreview(file) {
        const previewArea = document.getElementById('preview-area');
        const imagePreview = document.getElementById('image-preview');
        const fileName = document.getElementById('file-name');
        const fileSize = document.getElementById('file-size');

        // ファイル情報
        fileName.textContent = file.name;
        fileSize.textContent = formatFileSize(file.size);

        // 画像プレビュー
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                imagePreview.src = e.target.result;
                previewArea.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        } else {
            imagePreview.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"%3E%3Cpath stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /%3E%3C/svg%3E';
            previewArea.classList.remove('hidden');
        }
    }

    // キャンセル
    document.getElementById('cancel-upload').addEventListener('click', function() {
        document.getElementById('file-upload').value = '';
        document.getElementById('preview-area').classList.add('hidden');
    });

    // ファイルサイズフォーマット
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }
</script>
@endsection
