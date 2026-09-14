{{--
    Global wiederverwendbarer QR-Scanner (Handy-Kamera): ein Trigger irgendwo auf der
    Seite ruft `$store.qrScanner.launch(callback)` auf, `callback` bekommt den
    dekodierten Text (i.d.R. die Scan-URL vom Lagerort-Etikett, siehe
    LagerortController::labelPdf()). Genau EIN `@include` dieser Datei pro Seite
    genügt (nicht je Zeile/Karte) – der Store ist global, das Modal existiert nur
    einmal im DOM. Läuft komplett ohne QR-Bibliothek, wenn Html5Qrcode (siehe
    resources/js/app.js) aus irgendeinem Grund fehlt – der Trigger-Button zeigt dann
    nur einen Hinweis statt eines Absturzes.
--}}
<div x-data x-cloak x-show="$store.qrScanner.open"
     class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 px-4">
    <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full p-5">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-gray-900"><i class="fa fa-qrcode mr-1.5"></i>QR-Code scannen</h3>
            <button type="button" @click="$store.qrScanner.close()" class="text-gray-300 hover:text-gray-600">
                <i class="fa fa-xmark"></i>
            </button>
        </div>

        <div id="qr-scanner-viewport" class="rounded-xl overflow-hidden bg-black min-h-[240px]"></div>

        <p class="text-xs text-red-500 mt-2" x-show="$store.qrScanner.error" x-text="$store.qrScanner.error"></p>
        <p class="text-xs text-gray-400 mt-2">Lagerort-Etikett vor die Kamera halten.</p>
    </div>
</div>

@script
<script>
    if (! Alpine.store('qrScanner')) {
        Alpine.store('qrScanner', {
            open: false,
            error: null,
            _scanner: null,
            _callback: null,

            launch(callback) {
                if (typeof window.loadHtml5Qrcode === 'undefined') {
                    this.error = 'QR-Scanner steht in diesem Browser nicht zur Verfügung.';
                    this.open = true;
                    return;
                }

                this._callback = callback;
                this.error = null;
                this.open = true;

                window.loadHtml5Qrcode()
                    .then((Html5Qrcode) => {
                        window.Html5Qrcode = Html5Qrcode;
                        setTimeout(() => this._start(), 50);
                    })
                    .catch(() => {
                        this.error = 'QR-Scanner konnte nicht geladen werden.';
                    });
            },

            _start() {
                const el = document.getElementById('qr-scanner-viewport');
                if (! el || this._scanner) {
                    return;
                }

                this._scanner = new window.Html5Qrcode('qr-scanner-viewport');
                this._scanner.start(
                    { facingMode: 'environment' },
                    { fps: 10, qrbox: { width: 230, height: 230 } },
                    (decodedText) => {
                        const cb = this._callback;
                        this.close();
                        if (cb) { cb(decodedText); }
                    },
                    () => { /* einzelne, erfolglose Frames ignorieren */ }
                ).catch((err) => {
                    this.error = 'Kamera konnte nicht gestartet werden: ' + err;
                });
            },

            close() {
                if (this._scanner) {
                    this._scanner.stop().then(() => this._scanner.clear()).catch(() => {});
                    this._scanner = null;
                }
                this.open = false;
                this._callback = null;
            },

            /** Letztes Pfadsegment einer Scan-URL (oder die rohe ID, falls direkt gescannt/eingegeben). */
            extractId(text) {
                const parts = String(text).split('/').filter(Boolean);
                return parts[parts.length - 1] ?? text;
            },
        });
    }
</script>
@endscript
