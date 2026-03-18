@push('scripts')
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit" async></script>
@endpush

<div class="flex justify-center py-2">
    <div
        id="admin-turnstile-widget"
        x-data="{
            init() {
                const render = () => {
                    if (typeof turnstile !== 'undefined') {
                        turnstile.render('#admin-turnstile-widget', {
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
    ></div>
</div>
