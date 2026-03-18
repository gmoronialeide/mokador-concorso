<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
</head>
<body style="font-family: 'Helvetica Neue', Arial, sans-serif; background-color: #f5f0e8; margin: 0; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden;">
        @include('emails.partials.header')

        <div style="padding: 30px;">
            @yield('body')
        </div>

        @include('emails.partials.footer')
    </div>
</body>
</html>
