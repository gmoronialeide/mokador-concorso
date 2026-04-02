@php
    $url = $url ?? null;
    $alt = $alt ?? 'Scontrino';
    $size = $size ?? 'sm';
@endphp

@if ($url)
    <div x-data="{ open: false }" style="display: inline-block;">
        {{-- Thumbnail --}}
        <img
            src="{{ $url }}"
            alt="{{ $alt }}"
            x-on:click="open = true"
            style="
                border-radius: 6px;
                cursor: pointer;
                object-fit: cover;
                {{ $size === 'md' ? 'max-height: 200px; width: auto;' : 'width: 64px; height: 64px;' }}
            "
        >

        {{-- Lightbox overlay --}}
        <template x-teleport="body">
            <div
                x-show="open"
                x-transition.opacity
                x-on:keydown.escape.window="open = false"
                x-on:click="open = false"
                style="position: fixed; inset: 0; z-index: 9999; background: rgba(0, 0, 0, 0.8);"
            >
                <div style="display: flex; justify-content: center; align-items: center; width: 100%; height: 100%;">
                    {{-- Close button --}}
                    <button
                        x-on:click="open = false"
                        style="
                            position: absolute;
                            top: 16px;
                            right: 16px;
                            color: white;
                            font-size: 2rem;
                            line-height: 1;
                            background: none;
                            border: none;
                            cursor: pointer;
                            z-index: 10000;
                        "
                        aria-label="Chiudi"
                    >&times;</button>

                    {{-- Full image --}}
                    <img
                        src="{{ $url }}"
                        alt="{{ $alt }}"
                        x-on:click.stop
                        style="max-width: 90vw; max-height: 90vh; object-fit: contain;"
                    >
                </div>
            </div>
        </template>
    </div>
@else
    <div style="
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        background: #f3f4f6;
        {{ $size === 'sm' ? 'width: 64px; height: 64px;' : 'width: auto; height: 200px;' }}
    ">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#9ca3af" style="width: 24px; height: 24px;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" />
        </svg>
    </div>
@endif
