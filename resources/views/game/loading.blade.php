@extends('layouts.app')

@section('title', 'Caricamento - Mokador ti porta in vacanza')

@push('head')
    <script src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.9.3/dist/dotlottie-wc.js" type="module"></script>
@endpush

@section('content')
    @include('partials.teaser', ['title' => 'Gioca ora'])

    <section>
        <div class="container">
            <div class="loading-container">
                <dotlottie-wc src="https://lottie.host/cb3fb4f3-7c08-4eca-a800-6a754924d310/scyp2Ubr5x.lottie" class="lottie-animation" autoplay loop></dotlottie-wc>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
<script>
    setTimeout(function() {
        window.location.href = "{{ $redirectUrl }}";
    }, 3000);
</script>
@endpush
