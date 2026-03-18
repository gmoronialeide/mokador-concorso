<x-filament-panels::page.simple>
    {{ $this->content }}

    <div
        wire:ignore
        class="flex justify-center"
        x-data="{
            init() {
                const render = () => {
                    if (typeof turnstile !== 'undefined') {
                        turnstile.render(this.$refs.widget, {
                            sitekey: '{{ config('turnstile.turnstile_site_key') }}',
                            theme: 'light',
                            language: 'it',
                            callback: (token) => {
                                $wire.set('turnstileToken', token);
                            }
                        });
                    } else {
                        setTimeout(render, 100);
                    }
                };
                render();
            }
        }"
    >
        <div x-ref="widget"></div>
    </div>
</x-filament-panels::page.simple>

@push('scripts')
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit" async></script>
@endpush
