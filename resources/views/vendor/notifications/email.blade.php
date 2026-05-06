<x-mail::message>
    {{-- Greeting --}}
    @if (!empty($greeting))
        # {{ $greeting }}
    @else
        @if ($level === 'error')
            # Eyvah Bir Hata Oluştu!
        @else
            # Merhaba!
        @endif
    @endif

    {{-- Intro Lines --}}
    @foreach ($introLines as $line)
        {{ $line }}
    @endforeach

    {{-- Action Button --}}
    @isset($actionText)
        <?php
        $color = match ($level) {
            'success', 'error' => $level,
            default => 'primary',
        };
        ?>
        <x-mail::button :url="$actionUrl" :color="$color">
            {{ $actionText }}
        </x-mail::button>
    @endisset

    {{-- Outro Lines --}}
    @foreach ($outroLines as $line)
        {{ $line }}
    @endforeach

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

            <span class="break-all">[{{ $actionUrl }}]({{ $actionUrl }})</span>
        </x-slot:subcopy>
    @endisset
</x-mail::message>
