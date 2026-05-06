<x-mail::message>
    {{-- Greeting --}}
    @if (!empty($greeting))
        # {{ $greeting }}
    @else
        @if (isset($level) && $level === 'error')
            # Eyvah Bir Hata Oluştu!
        @else
            # Merhaba!
        @endif
    @endif

    {{-- Intro Lines --}}
    @isset($introLines)
        @foreach ($introLines as $line)
            {{ $line }}
        @endforeach
    @endisset

    {{-- Action Button --}}
    @isset($actionText)
        @php
            $color = 'primary';
            if (isset($level) && ($level === 'success' || $level === 'error')) {
                $color = $level;
            }
        @endphp
        <x-mail::button :url="$actionUrl ?? ''" :color="$color">
            {{ $actionText }}
        </x-mail::button>
    @endisset

    {{-- Outro Lines --}}
    @isset($outroLines)
        @foreach ($outroLines as $line)
            {{ $line }}
        @endforeach
    @endisset

    {{-- Salutation --}}
    @if (!empty($salutation))
        {{ $salutation }}
    @else
        İyi çalışmalar dileriz,<br>
        {{ config('app.name') }}
    @endif

    {{-- Subcopy --}}
    @isset($actionText)
        <x-slot:subcopy>
            "{{ $actionText }}" butonuna tıklamakta sorun yaşıyorsanız, aşağıdaki bağlantıyı kopyalayıp web tarayıcınıza
            yapıştırın:

            <span class="break-all">[{{ $actionUrl ?? '' }}]({{ $actionUrl ?? '' }})</span>
        </x-slot:subcopy>
    @endisset
</x-mail::message>
