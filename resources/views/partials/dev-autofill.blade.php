<div style="position:fixed;bottom:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:10px">
    <button type="button" id="dev-autofill-bad"
        style="background:#dc3545;color:#fff;border:none;border-radius:50%;width:56px;height:56px;font-size:24px;cursor:pointer;box-shadow:0 4px 12px rgba(0,0,0,.3);transition:transform .2s"
        onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'"
        title="Autofill dati ERRATI (test validazione)">
        <span style="pointer-events:none">&#x26a0;</span>
    </button>
    <button type="button" id="dev-autofill"
        style="background:#9D4A15;color:#fff;border:none;border-radius:50%;width:56px;height:56px;font-size:24px;cursor:pointer;box-shadow:0 4px 12px rgba(0,0,0,.3);transition:transform .2s"
        onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'"
        title="Autofill dati VALIDI">
        <span style="pointer-events:none">&#x1f3b2;</span>
    </button>
</div>

<script>
(function () {
    const pick = arr => arr[Math.floor(Math.random() * arr.length)];
    const rand = (min, max) => Math.floor(Math.random() * (max - min + 1)) + min;

    // Dati validi
    document.getElementById('dev-autofill').addEventListener('click', function () {
        const nomi = ['Mario','Luca','Giulia','Sara','Marco','Anna','Francesco','Elena','Alessandro','Chiara'];
        const cognomi = ['Rossi','Bianchi','Verdi','Russo','Ferrari','Esposito','Romano','Colombo','Ricci','Marino'];
        const citta = ['Bologna','Milano','Roma','Firenze','Napoli','Torino','Padova','Rimini','Cesena','Ravenna'];
        const province = ['BO','MI','RM','FI','NA','TO','PD','RN','FC','RA'];
        const vie = ['Via Roma','Via Garibaldi','Corso Italia','Via Dante','Via Mazzini','Via Verdi','Viale Europa'];
        const cityIdx = rand(0, citta.length - 1);

        document.getElementById('nome').value = pick(nomi);
        document.getElementById('cognome').value = pick(cognomi);

        const year = rand(1970, 2000);
        const month = String(rand(1, 12)).padStart(2, '0');
        const day = String(rand(1, 28)).padStart(2, '0');
        document.getElementById('data-di-nascita').value = year + '-' + month + '-' + day;

        document.getElementById('indirizzo').value = pick(vie) + ' ' + rand(1, 200);
        document.getElementById('citta').value = citta[cityIdx];
        document.getElementById('provincia').value = province[cityIdx];
        document.getElementById('cap').value = String(rand(10000, 99999));
        document.getElementById('cellulare').value = '33' + String(rand(10000000, 99999999));
        document.getElementById('email').value = 'g.moroni+' + rand(1000, 99999) + '@aleide.it';
        document.getElementById('password').value = 'Password1';
        document.getElementById('conferma-password').value = 'Password1';
        document.getElementById('privacy-consent').checked = true;
        document.getElementById('marketing-consent').checked = Math.random() > 0.5;
    });

    // Dati errati
    document.getElementById('dev-autofill-bad').addEventListener('click', function () {
        document.getElementById('nome').value = '';                          // vuoto
        document.getElementById('cognome').value = '';                       // vuoto
        document.getElementById('data-di-nascita').value = '2015-06-15';    // minorenne
        document.getElementById('indirizzo').value = '';                     // vuoto
        document.getElementById('citta').value = '';                         // vuoto
        document.getElementById('provincia').value = '';                      // nessuna selezione
        document.getElementById('cap').value = 'ABCDE';                      // non numerico
        document.getElementById('cellulare').value = '0000';                 // non è un cellulare valido
        document.getElementById('email').value = 'non-una-email';           // formato email invalido
        document.getElementById('password').value = 'abc';                  // troppo corta, no maiuscole, no numeri
        document.getElementById('conferma-password').value = 'xyz';         // non corrisponde
        document.getElementById('privacy-consent').checked = false;         // non accettata
        document.getElementById('marketing-consent').checked = false;
    });
})();
</script>
