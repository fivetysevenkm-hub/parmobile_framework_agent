<div class="lot-verifikator-page">
    <style>
        .lot-verifikator-page{width:100%;max-width:none;margin:0;padding:0;font-family:Tahoma,Arial,sans-serif;color:var(--text-body)}
        .lot-verifikator-page .page-header{width:100%;margin:0 0 14px;padding:14px 18px;border:1px solid var(--line);border-radius:8px;background:var(--panel);color:var(--text-title);font-size:20px;font-weight:700;letter-spacing:.02em}
        .lot-verifikator-page .panel{width:100%;border:1px solid var(--line);border-radius:8px;background:var(--panel);padding:16px;margin-bottom:14px}
        .lot-verifikator-page .panel-title{margin:0 0 14px;color:var(--text-title);font-size:16px;font-weight:700}
        .lot-verifikator-page .scan-panel{background:color-mix(in srgb,var(--cyan) 12%,var(--panel));border-color:color-mix(in srgb,var(--cyan) 38%,var(--line))}
        .lot-verifikator-page .field-label{display:block;margin-bottom:6px;color:var(--text-body);font-size:14px;font-weight:700}
        .lot-verifikator-page .scan-control-row{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:12px;align-items:center}
        .lot-verifikator-page .lot-input{width:100%;height:47px;border:1px solid var(--line);border-radius:7px;background:var(--input-bg);color:var(--text-title);padding:8px 12px;font-size:22px;letter-spacing:.03em;outline:none}
        .lot-verifikator-page .lot-input:focus{border-color:var(--cyan);box-shadow:0 0 0 3px color-mix(in srgb,var(--cyan) 18%,transparent)}
        .lot-verifikator-page .scan-actions{display:flex;gap:8px;align-items:center}
        .lot-verifikator-page .scan-button,.lot-verifikator-page .clear-button{height:47px;padding:0 18px;border-radius:7px;font-family:Tahoma,Arial,sans-serif;font-size:14px;font-weight:700;cursor:pointer;white-space:nowrap}
        .lot-verifikator-page .scan-button{border:1px solid var(--cyan);background:var(--cyan);color:var(--text-button)}
        .lot-verifikator-page .clear-button{border:1px solid var(--line);background:transparent;color:var(--text-title)}
        .lot-verifikator-page .camera-button{height:47px;padding:0 18px;border-radius:7px;font-family:Tahoma,Arial,sans-serif;font-size:14px;font-weight:700;cursor:pointer;white-space:nowrap;border:1px solid color-mix(in srgb,var(--cyan) 55%,var(--line));background:color-mix(in srgb,var(--cyan) 14%,transparent);color:var(--cyan)}
        .lot-verifikator-page .camera-panel{display:none;margin-top:10px;padding:10px;border:1px solid var(--line);border-radius:8px;background:var(--legacy_091115)}.lot-verifikator-page .camera-panel.is-visible{display:block}.lot-verifikator-page video{display:block;width:100%;max-height:300px;min-height:180px;object-fit:cover;border-radius:5px;background:#000}.lot-verifikator-page .camera-actions{display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;margin-top:8px}.lot-verifikator-page .camera-status{color:var(--text-header);font-size:12px}.lot-verifikator-page .camera-buttons{display:flex;gap:7px;flex-wrap:wrap}.lot-verifikator-page .camera-action-button{min-height:36px;padding:8px 12px;border:1px solid var(--line);border-radius:6px;background:transparent;color:var(--text-header);font-family:Tahoma,Arial,sans-serif;font-size:12px;font-weight:700;cursor:pointer;white-space:nowrap}
        .lot-verifikator-page .hint{margin:8px 0 0;color:var(--text-footer);font-size:12px}
        .lot-verifikator-page .error{margin-top:7px;color:var(--danger);font-size:13px}
        .lot-verifikator-page .summary{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px}
        .lot-verifikator-page .summary-item{padding:7px 10px;border:1px solid var(--line);border-radius:6px;background:var(--panel-2);color:var(--text-body);font-size:13px}
        .lot-verifikator-page .summary-item strong{color:var(--text-title)}
        .lot-verifikator-page .flow-note{margin:0 0 14px;color:var(--text-body);font-size:15px;font-weight:700}
        .lot-verifikator-page .flow-empty{padding:25px 14px;text-align:center;border:1px dashed var(--line);border-radius:8px;color:var(--text-footer);font-size:14px}
        .lot-verifikator-page .finished{padding:12px 14px;border:1px solid var(--success);border-radius:7px;background:color-mix(in srgb,var(--success) 12%,transparent);color:var(--success);font-size:14px;font-weight:700}
        .lot-verifikator-page .flow-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(145px,1fr));gap:12px}
        .lot-verifikator-page .process-box{min-height:76px;padding:12px 10px;border:1px solid color-mix(in srgb,var(--cyan) 30%,var(--line));border-radius:5px;background:var(--legacy_0879c7);color:var(--legacy_031a2b);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;text-align:center;font-family:Tahoma,Arial,sans-serif;font-size:14px;font-weight:700;line-height:1.25;cursor:not-allowed;opacity:.84;transition:transform .16s ease,filter .16s ease,box-shadow .16s ease}
        .lot-verifikator-page .process-box .seq{font-size:12px;opacity:.78}
        .lot-verifikator-page .process-box .next-label{font-size:10px;letter-spacing:.08em;text-transform:uppercase}
        .lot-verifikator-page .process-box.is-next{background:var(--cyan);color:var(--text-button);border-color:var(--text-header);box-shadow:0 0 0 2px color-mix(in srgb,var(--cyan) 22%,transparent),0 8px 18px color-mix(in srgb,var(--cyan) 18%,transparent);cursor:pointer;opacity:1}
        .lot-verifikator-page .process-box.is-next:hover{filter:brightness(1.08);transform:translateY(-2px)}
        .lot-verifikator-page .process-box.is-next:active{transform:scale(.98)}
        @media(max-width:760px){.lot-verifikator-page .page-header{padding:12px 14px;font-size:18px}.lot-verifikator-page .panel{padding:12px}.lot-verifikator-page .scan-control-row{grid-template-columns:1fr}.lot-verifikator-page .scan-actions{display:grid;grid-template-columns:repeat(3,1fr)}.lot-verifikator-page .scan-button,.lot-verifikator-page .clear-button,.lot-verifikator-page .camera-button{width:100%}.lot-verifikator-page .lot-input{font-size:20px}.lot-verifikator-page .flow-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:9px}.lot-verifikator-page .process-box{min-height:78px;padding:10px 7px}}
        @media(max-width:390px){.lot-verifikator-page .flow-grid{grid-template-columns:1fr}.lot-verifikator-page .scan-actions{grid-template-columns:1fr}}
    </style>

    <header class="page-header">Lot Verifikator</header>

    <section class="panel scan-panel">
        <h1 class="panel-title">Verifikasi No Lot</h1>
        <label class="field-label" for="lot-verifikator-input">No Lot</label>
        <form wire:submit.prevent="loadLot">
            <div class="scan-control-row">
                <input id="lot-verifikator-input" type="text" class="lot-input" wire:model="noLot" wire:keydown.enter="loadLot" autocomplete="off" autofocus placeholder="Scan barcode No Lot atau ketik lalu Enter">
                <div class="scan-actions">
                    <button type="button" class="camera-button" id="lotVerifikatorStartQr">CAM SCAN</button>
                    <button type="submit" class="scan-button" wire:loading.attr="disabled" wire:target="loadLot">TAMPILKAN FLOW</button>
                    <button type="button" class="clear-button" wire:click="clearLot">RESET</button>
                </div>
            </div>
            <div class="camera-panel" id="lotVerifikatorCameraPanel" wire:ignore><video id="lotVerifikatorQrVideo" playsinline muted></video><div class="camera-actions"><span class="camera-status" id="lotVerifikatorCameraStatus">Arahkan kamera ke QR Code.</span><div class="camera-buttons"><button type="button" class="camera-action-button" id="lotVerifikatorSwitchCamera">SWITCH KAMERA</button><button type="button" class="camera-action-button" id="lotVerifikatorStopCamera">TUTUP KAMERA</button></div></div></div>
            <p class="hint">Scanner barcode biasanya mengirimkan tombol Enter secara otomatis. Enter manual juga dapat digunakan.</p>
            @error('noLot')<div class="error">{{ $message }}</div>@enderror
        </form>
    </section>

    <section class="panel">
        <h2 class="panel-title">HASIL VERIFIKASI</h2>
        @if(! $hasSearched)
            <div class="flow-empty">Flow proses masih tersembunyi. Scan atau masukkan No Lot, lalu tekan Enter untuk menampilkan routing lot.</div>
        @elseif(($result['finished'] ?? false))
            <div class="summary"><span class="summary-item"><strong>No Lot:</strong> {{ $result['no_lot'] }}</span><span class="summary-item"><strong>No SRP:</strong> {{ $result['no_srp'] }}</span><span class="summary-item"><strong>Routing:</strong> {{ $result['id_routing'] }}</span></div>
            <div class="finished">{{ $message }}</div>
        @else
            <div class="summary"><span class="summary-item"><strong>No Lot:</strong> {{ $result['no_lot'] }}</span><span class="summary-item"><strong>No SRP:</strong> {{ $result['no_srp'] }}</span><span class="summary-item"><strong>Routing:</strong> {{ $result['id_routing'] }}</span><span class="summary-item"><strong>Total Flow:</strong> {{ $result['routing_count'] }}</span><span class="summary-item"><strong>Proses Berikutnya:</strong> {{ $result['next_process'] ?: 'Belum ditentukan' }}</span></div>
            <p class="flow-note">{{ $message ?: 'Pilih proses aktif (huruf lebih terang) untuk melanjutkan proses.' }}</p>
            @if($processes !== [])
                <div class="flow-grid">
                    @foreach($processes as $process)
                        <button type="button" class="process-box {{ ($process['clickable'] ?? false) ? 'is-next' : '' }}" wire:key="lot-flow-{{ $process['sequence_no'] }}-{{ $process['id_seq'] }}" @if($process['clickable'] ?? false) wire:click="openProcess({{ $process['sequence_no'] }})" @else disabled @endif>
                            <span class="seq">{{ $process['label'] }}</span><span>{{ $process['name'] }}</span>@if($process['clickable'] ?? false)<span class="next-label">Klik untuk lanjut</span>@endif
                        </button>
                    @endforeach
                </div>
            @else
                <div class="flow-empty">Tidak ada flow proses yang dapat ditampilkan.</div>
            @endif
            @error('process')<div class="error">{{ $message }}</div>@enderror
        @endif
    </section>
</div>

<script>
    (() => {
        let stream = null;
        let timer = null;
        let facingMode = 'environment';
        const panel = () => document.getElementById('lotVerifikatorCameraPanel');
        const video = () => document.getElementById('lotVerifikatorQrVideo');
        const status = (text) => { const el = document.getElementById('lotVerifikatorCameraStatus'); if (el) el.textContent = text; };
        const stop = (hide = true) => { if (timer) { clearInterval(timer); timer = null; } if (stream) { stream.getTracks().forEach(track => track.stop()); stream = null; } const v = video(); if (v) v.srcObject = null; const p = panel(); if (hide && p) p.classList.remove('is-visible'); };
        const setScanValue = (value) => { const input = document.getElementById('lot-verifikator-input'); if (!input) return; input.value = value; input.dispatchEvent(new Event('input', {bubbles:true})); status('QR terbaca: '+value); setTimeout(() => stop(true), 450); };
        const start = async () => { const p = panel(); const v = video(); if (!p || !v) return; p.classList.add('is-visible'); if (!window.isSecureContext && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') { status('Kamera memerlukan HTTPS atau localhost.'); return; } if (!navigator.mediaDevices?.getUserMedia) { status('Browser tidak mendukung akses kamera.'); return; } try { try { stream = await navigator.mediaDevices.getUserMedia({video:{facingMode:{exact:facingMode}},audio:false}); } catch (e) { stream = await navigator.mediaDevices.getUserMedia({video:{facingMode:{ideal:facingMode}},audio:false}); } v.srcObject = stream; await v.play(); status('Arahkan QR ke kamera '+(facingMode === 'environment' ? 'belakang' : 'depan')+'.'); if (!('BarcodeDetector' in window)) { status('Deteksi QR otomatis tidak didukung. Gunakan input manual.'); return; } const formats = await BarcodeDetector.getSupportedFormats(); if (!formats.includes('qr_code')) { status('Format QR belum didukung browser.'); return; } const detector = new BarcodeDetector({formats:['qr_code']}); timer = setInterval(async () => { try { const codes = await detector.detect(v); if (codes.length && codes[0].rawValue) setScanValue(codes[0].rawValue.trim()); } catch (e) { status('QR belum terbaca, arahkan kamera dengan stabil.'); } }, 250); } catch (e) { status('Akses kamera ditolak atau tidak tersedia. Izinkan kamera browser.'); } };
        const switchCamera = async () => { if (!stream) return; stop(false); facingMode = facingMode === 'environment' ? 'user' : 'environment'; await start(); };
        document.addEventListener('click', event => { const target = event.target; if (!(target instanceof Element)) return; if (target.closest('#lotVerifikatorStartQr')) start(); if (target.closest('#lotVerifikatorSwitchCamera')) switchCamera(); if (target.closest('#lotVerifikatorStopCamera')) stop(); });
        document.addEventListener('livewire:navigated', () => stop());
    })();
</script>
