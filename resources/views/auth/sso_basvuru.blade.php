<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DMS Başvuru Ekranı</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="antialiased bg-gray-50 text-gray-900">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-xl shadow-lg border border-gray-100">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-indigo-100 mb-4">
                    <svg class="h-8 w-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                </div>
                <h2 class="text-3xl font-extrabold text-gray-900">Hoş Geldiniz</h2>
                <h3 class="text-lg font-medium text-indigo-600 mt-1">{{ $centralUser['first_name'] }} {{ $centralUser['last_name'] }}</h3>
                <p class="mt-2 text-sm text-gray-500">
                    DMS Sistemine ilk girişiniz.<br>Lütfen bağlı olduğunuz departmanı seçiniz.
                </p>
            </div>

            <form class="mt-8 space-y-6" action="{{ route('sso.basvuru_kaydet') }}" method="POST">
                @csrf
                <div>
                    <label for="department_id" class="block text-sm font-medium text-gray-700 mb-1">Departmanınız</label>
                    <select id="department_id" name="department_id" required class="block w-full pl-3 pr-10 py-3 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-lg bg-gray-50 border transition-colors">
                        <option value="">Seçiniz...</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r-md">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700 font-medium">
                                Başvurunuzu tamamladıktan sonra DMS sistem yöneticisi onayı beklenecektir.
                            </p>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent text-sm font-bold rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-sm transition-colors">
                    Başvuruyu Tamamla
                </button>
            </form>
        </div>
    </div>
</body>
</html>