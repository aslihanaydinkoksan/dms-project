@extends('layouts.app') 

@section('content')
<div class="py-8 bg-slate-50 min-h-screen">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 flex flex-col md:flex-row md:items-center justify-between gap-4 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 bg-amber-50 rounded-full blur-3xl -mr-32 -mt-32 opacity-60 pointer-events-none"></div>
            <div class="relative z-10 flex items-center gap-4">
                <div class="w-14 h-14 bg-amber-100 text-amber-600 rounded-2xl flex items-center justify-center shadow-inner">
                    <i data-lucide="user-clock" class="w-7 h-7"></i>
                </div>
                <div>
                    <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Onay Bekleyenler</h2>
                    <p class="text-sm text-slate-500 font-medium mt-1">DMS sistemine erişim için departman ve rol ataması bekleyen adaylar.</p>
                </div>
            </div>
            <div class="relative z-10">
                <span class="bg-amber-100 text-amber-700 text-sm font-bold px-5 py-2.5 rounded-full shadow-sm border border-amber-200 flex items-center gap-2">
                    <span class="flex h-2 w-2 relative">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-500 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-600"></span>
                    </span>
                    {{ $bekleyenler->count() }} Bekleyen Kayıt
                </span>
            </div>
        </div>

        @if (session('success'))
            <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 flex items-center shadow-sm">
                <div class="flex-shrink-0 bg-emerald-100 rounded-full p-1.5 mr-3">
                    <i data-lucide="check" class="w-4 h-4 text-emerald-600"></i>
                </div>
                <p class="text-sm text-emerald-800 font-semibold">{{ session('success') }}</p>
            </div>
        @endif

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            @if($bekleyenler->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead>
                            <tr class="bg-slate-50/80">
                                <th scope="col" class="px-6 py-4 text-left text-xs font-extrabold text-slate-500 uppercase tracking-wider">Aday Bilgisi</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-extrabold text-slate-500 uppercase tracking-wider">Talep Durumu</th>
                                <th scope="col" class="px-6 py-4 text-right text-xs font-extrabold text-slate-500 uppercase tracking-wider">Aksiyon Alanı (Departman & Rol)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach($bekleyenler as $user)
                                <tr class="hover:bg-slate-50/70 transition-colors duration-200 group">
                                    <td class="px-6 py-5 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-11 w-11 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold shadow-sm ring-2 ring-white">
                                                {{ mb_strtoupper(mb_substr(trim($user->name), 0, 1, 'UTF-8'), 'UTF-8') }}
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-bold text-slate-800">{{ $user->name }}</div>
                                                <div class="text-xs font-medium text-slate-500 mt-0.5">{{ $user->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 whitespace-nowrap">
                                        <div class="flex flex-col gap-1.5">
                                            <span class="inline-flex items-center w-fit px-2.5 py-1 rounded-md text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                                <i data-lucide="building-2" class="w-3 h-3 mr-1.5 opacity-70"></i>
                                                {{ $user->department ? $user->department->name : 'Birim Seçilmemiş' }}
                                            </span>
                                            <span class="text-[11px] font-semibold text-slate-400 flex items-center">
                                                <i data-lucide="clock" class="w-3 h-3 mr-1"></i>
                                                {{ $user->created_at->diffForHumans() }} başvurdu
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 whitespace-nowrap text-right">
                                        <form action="{{ route('r_yonetim_basvuru_onayla', $user->id) }}" method="POST" class="flex items-center justify-end gap-3 opacity-90 group-hover:opacity-100 transition-opacity duration-200">
                                            @csrf
                                            
                                            <select name="department_id" class="bg-slate-50 border border-slate-200 text-slate-700 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-40 p-2 font-semibold cursor-pointer transition-colors hover:bg-slate-100" required>
                                                <option value="">Departman...</option>
                                                @foreach($departments as $dept)
                                                    <option value="{{ $dept->id }}" {{ $user->department_id == $dept->id ? 'selected' : '' }}>
                                                        {{ $dept->name }}
                                                    </option>
                                                @endforeach
                                            </select>

                                            <select name="role" class="bg-indigo-50 border border-indigo-100 text-indigo-700 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-32 p-2 font-bold cursor-pointer transition-colors hover:bg-indigo-100" required>
                                                <option value="">Rol Ata...</option>
                                                @foreach($roles as $role)
                                                    <option value="{{ $role->name }}">{{ $role->name }}</option>
                                                @endforeach
                                            </select>

                                            <button type="submit" class="inline-flex items-center justify-center gap-1.5 bg-slate-900 hover:bg-indigo-600 text-white font-bold py-2 px-4 rounded-lg shadow-sm transition-all transform active:scale-95 text-sm">
                                                <i data-lucide="check" class="w-4 h-4"></i> Onayla
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-20 px-4 text-center">
                    <div class="w-24 h-24 bg-emerald-50 rounded-full flex items-center justify-center mb-6 ring-8 ring-emerald-50/50">
                        <i data-lucide="shield-check" class="w-12 h-12 text-emerald-500"></i>
                    </div>
                    <h3 class="text-xl font-extrabold text-slate-800">Her Şey Kontrol Altında!</h3>
                    <p class="mt-2 text-sm text-slate-500 max-w-sm mx-auto font-medium leading-relaxed">
                        Şu anda sistemde onayınızı bekleyen hiçbir kullanıcı bulunmuyor. Yeni kayıtlar geldiğinde burada listelenecektir.
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        if(typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>
@endsection