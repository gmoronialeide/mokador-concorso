<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
</head>
<body style="font-family: 'Helvetica Neue', Arial, sans-serif; background-color: #f5f0e8; margin: 0; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden;">

        <div style="background-color: #2B1D18; padding: 24px; text-align: center;">
            <img src="{{ asset('img/mokador-concorso-logo.svg') }}" alt="Mokador ti porta in vacanza" style="max-width: 250px; height: auto;">
        </div>

        <div style="padding: 30px;">
            <h2 style="color: #4F3328; text-align: center; margin-top: 0;">Ciao {{ $user->name }}!</h2>
            <p style="color: #614637; font-size: 16px; text-align: center;">Grazie per esserti registrato al concorso <strong>Mokador ti porta in vacanza</strong>.</p>

            <div style="background-color: #DFD6C1; padding: 20px; border-radius: 8px; margin: 24px 0;">
                <h3 style="color: #4F3328; margin: 0 0 12px; font-size: 16px;">I tuoi dati di accesso:</h3>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="color: #614637; padding: 4px 0; font-weight: bold; width: 100px;">Email:</td>
                        <td style="color: #614637; padding: 4px 0;">{{ $user->email }}</td>
                    </tr>
                    <tr>
                        <td style="color: #614637; padding: 4px 0; font-weight: bold;">Password:</td>
                        <td style="color: #614637; padding: 4px 0;">{{ $plainPassword ?? '********' }}</td>
                    </tr>
                </table>
            </div>

            <p style="color: #614637; font-size: 16px; text-align: center;">Per attivare il tuo account e iniziare a giocare, clicca sul pulsante qui sotto:</p>

            <div style="text-align: center; margin: 28px 0;">
                <a href="{{ $verificationUrl }}" style="display: inline-block; background-color: #9D4A15; color: #ffffff; padding: 14px 32px; text-decoration: none; border-radius: 4px; font-size: 16px; font-weight: bold;">Conferma la tua email</a>
            </div>

            <p style="color: #999; font-size: 13px; text-align: center;">Se non riesci a cliccare il pulsante, copia e incolla questo link nel browser:</p>
            <p style="color: #9D4A15; font-size: 12px; text-align: center; word-break: break-all;">{{ $verificationUrl }}</p>

            <hr style="border: none; border-top: 1px solid #f0ebe0; margin: 24px 0;">

            <p style="color: #999; font-size: 13px; text-align: center;">Se non hai creato un account, ignora questa email.</p>
        </div>

        <div style="background-color: #2B1D18; padding: 15px; text-align: center;">
            <p style="color: #C3B598; margin: 0; font-size: 12px;">&copy; 2026 Mokador - Tutti i diritti riservati</p>
        </div>
    </div>
</body>
</html>
