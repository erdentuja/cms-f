<div class="modal-backdrop" id="mediaPicker" hidden>
    <div class="modal">
        <div class="modal-head">
            <h3>Médiatár</h3>
            <div class="modal-actions">
                <label class="btn btn-ghost btn-sm">
                    Feltöltés
                    <input type="file" id="pickerUpload" accept="image/*" hidden>
                </label>
                <button class="icon-btn" type="button" onclick="closeMediaPicker()" aria-label="Bezárás">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>
        </div>
        <div class="picker-grid" id="pickerGrid">
            <p class="muted pad">Betöltés…</p>
        </div>
    </div>
</div>
<script>
window.CMS_BASE = <?= json_encode(base_url('/')) ?>;
window.CSRF = <?= json_encode(csrf_token()) ?>;
</script>
