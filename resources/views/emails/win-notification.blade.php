<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
</head>
<body style="font-family: 'Helvetica Neue', Arial, sans-serif; background-color: #f5f0e8; margin: 0; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden;">
        <div style="background-color: #2B1D18; padding: 20px; text-align: center;">
            <h1 style="color: #DFD6C1; margin: 0; font-size: 24px;">Mokador ti porta in vacanza</h1>
        </div>
        <div style="padding: 30px;">
            <h2 style="color: #4F3328; text-align: center;">Complimenti {{ $user->name }}!</h2>
            <p style="color: #614637; font-size: 16px; text-align: center;">Hai vinto:</p>
            <div style="background-color: #DFD6C1; padding: 20px; border-radius: 8px; text-align: center; margin: 20px 0;">
                <h3 style="color: #4F3328; margin: 0;">{{ $prize->name }}</h3>
                <p style="color: #9D4A15; font-weight: bold; margin: 10px 0 0;">Valore: &euro;{{ number_format($prize->value, 2, ',', '.') }}</p>
            </div>
            <p style="color: #614637; font-size: 14px;">Per ricevere il tuo premio dovrai inviare una copia dei tuoi documenti <strong>entro 5 giorni</strong> a <a href="mailto:concorso@mokador.it" style="color: #9D4A15;">concorso@mokador.it</a>.</p>
            <p style="color: #614637; font-size: 14px;">Ricordati di <strong>conservare lo scontrino originale</strong>: ti verrà richiesto per la convalida del premio.</p>
        </div>
        <div style="background-color: #2B1D18; padding: 15px; text-align: center;">
            <p style="color: #C3B598; margin: 0; font-size: 12px;">&copy; 2026 Mokador - Tutti i diritti riservati</p>
        </div>
    </div>
</body>
</html>
