<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Daftar Akun UMKM - Inamikro UMKM Website Builder</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased min-h-screen flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-xl">
        <div class="flex justify-center items-center gap-2 mb-2">
            <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-blue-600 text-white font-bold text-xl shadow-md shadow-blue-500/20">
                IM
            </span>
            <span class="text-2xl font-bold tracking-tight text-slate-900">Inamikro</span>
        </div>
        <h2 class="text-center text-xl font-bold tracking-tight text-slate-900">
            Daftar Akun UMKM
        </h2>
        <p class="mt-2 text-center text-sm text-slate-600">
            Bangun website profesional dan katalog produk digital Anda dalam hitungan menit
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-xl px-4 sm:px-0">
        <div class="bg-white py-8 px-6 shadow-sm ring-1 ring-slate-900/5 rounded-2xl sm:px-10">
            @if ($errors->any())
                <div class="mb-6 rounded-xl bg-red-50 p-4 border border-red-200">
                    <div class="flex items-start">
                        <div class="flex-shrink-0 text-red-500">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-semibold text-red-800">Mohon periksa data Anda kembali</h3>
                            <div class="mt-1 text-sm text-red-700">
                                <ul class="list-disc pl-5 space-y-1">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <form class="space-y-5" action="{{ route('register') }}" method="POST">
                @csrf

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-700">Nama Pemilik</label>
                        <div class="mt-1">
                            <input id="name" name="name" type="text" autocomplete="name" required
                                   value="{{ old('name') }}"
                                   class="block w-full rounded-lg border-slate-300 py-2.5 px-3 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm border @error('name') border-red-300 @enderror"
                                   placeholder="Contoh: Budi Santoso">
                        </div>
                    </div>

                    <div>
                        <label for="business_name" class="block text-sm font-medium text-slate-700">Nama Usaha</label>
                        <div class="mt-1">
                            <input id="business_name" name="business_name" type="text" required
                                   value="{{ old('business_name') }}"
                                   class="block w-full rounded-lg border-slate-300 py-2.5 px-3 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm border @error('business_name') border-red-300 @enderror"
                                   placeholder="Contoh: Kopi Mantap Jiwa">
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-slate-700">Kategori Usaha</label>
                        <div class="mt-1">
                            <select id="category_id" name="category_id"
                                    class="block w-full rounded-lg border-slate-300 py-2.5 px-3 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm border @error('category_id') border-red-300 @enderror">
                                <option value="">-- Pilih Kategori --</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="whatsapp" class="block text-sm font-medium text-slate-700">No. WhatsApp</label>
                        <div class="mt-1">
                            <input id="whatsapp" name="whatsapp" type="text" required
                                   value="{{ old('whatsapp') }}"
                                   class="block w-full rounded-lg border-slate-300 py-2.5 px-3 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm border @error('whatsapp') border-red-300 @enderror"
                                   placeholder="081234567890">
                        </div>
                    </div>
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700">Alamat Email</label>
                    <div class="mt-1">
                        <input id="email" name="email" type="email" autocomplete="email" required
                               value="{{ old('email') }}"
                               class="block w-full rounded-lg border-slate-300 py-2.5 px-3 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm border @error('email') border-red-300 @enderror"
                               placeholder="nama@email.com">
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-700">Kata Sandi</label>
                        <div class="mt-1">
                            <input id="password" name="password" type="password" required
                                   class="block w-full rounded-lg border-slate-300 py-2.5 px-3 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm border @error('password') border-red-300 @enderror"
                                   placeholder="Minimal 8 karakter">
                        </div>
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Konfirmasi Kata Sandi</label>
                        <div class="mt-1">
                            <input id="password_confirmation" name="password_confirmation" type="password" required
                                   class="block w-full rounded-lg border-slate-300 py-2.5 px-3 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm border"
                                   placeholder="Ulangi kata sandi">
                        </div>
                    </div>
                </div>

                <div>
                    <button type="submit"
                            class="flex w-full justify-center rounded-lg bg-blue-600 py-2.5 px-4 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 transition-colors">
                        Daftar &amp; Buat Website
                    </button>
                </div>
            </form>

            <div class="mt-6 border-t border-slate-200 pt-6 text-center text-sm text-slate-600">
                Sudah memiliki akun?
                <a href="{{ route('login') }}" class="font-medium text-blue-600 hover:text-blue-500 hover:underline">
                    Masuk di sini
                </a>
            </div>
        </div>
    </div>
</body>
</html>
