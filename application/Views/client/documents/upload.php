<div class="tn-screen" style="max-width:860px">
    <div style="margin-bottom:24px">
        <p style="margin:0;color:#61756e;font-size:14.5px">Select or drag files to upload securely to TriNova Accounting.</p>
    </div>

    <div style="background:#fff;border-radius:24px;padding:32px;box-shadow:0 1px 2px rgba(16,54,45,.04),0 14px 34px -24px rgba(16,54,45,.4)">
        <form action="/client/documents/upload" method="POST" enctype="multipart/form-data" data-ajax-form>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Application\Core\Session::csrfToken()) ?>">
            <?php if(!empty($selectedRequest)): ?><input type="hidden" name="request_id" value="<?= (int)$selectedRequest['id'] ?>"><?php endif; ?>
            
            <div id="dropzone" style="border:2px dashed #0d9488;border-radius:20px;padding:40px 24px;text-align:center;background:#f6faf8;cursor:pointer;transition:all .2s;margin-bottom:24px" onclick="document.getElementById('fileInput').click()">
                <div style="width:56px;height:56px;border-radius:16px;background:#dff1ee;color:#0d9488;display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="17 8 12 3 7 8"></polyline>
                        <line x1="12" y1="3" x2="12" y2="15"></line>
                    </svg>
                </div>
                <h3 style="margin:0 0 6px;font-size:17px;font-weight:700;color:#213330">Click to choose or drag & drop files here</h3>
                <p style="margin:0;color:#7d8e88;font-size:13.5px">Supports PDF, Word, Excel, CSV, PNG, JPG, ZIP (up to 25MB)</p>
                <div id="selectedFileName" style="margin-top:14px;font-weight:700;color:#0d9488;font-size:14px"></div>
                <input type="file" id="fileInput" name="file" required style="display:none" onchange="document.getElementById('selectedFileName').innerText = 'Selected: ' + this.files[0].name">
            </div>

            <div style="margin-bottom:18px">
                <label style="display:block;font-size:13.5px;font-weight:700;color:#3a4d47;margin-bottom:8px">Company or personal record</label>
                <select name="entity_id" required style="width:100%;padding:14px 16px;border:1.5px solid #e0e9e5;border-radius:15px;background:#fbfdfc">
                    <option value="">-- Select record --</option>
                    <?php foreach (($entities ?? []) as $entity): ?><option value="<?= (int)$entity['id'] ?>" <?= !empty($selectedRequest) && (int)$selectedRequest['entity_id']===(int)$entity['id']?'selected':'' ?>><?= htmlspecialchars($entity['company_name']) ?> — <?= htmlspecialchars(ucfirst($entity['entity_scope'])) ?></option><?php endforeach; ?>
                </select>
            </div>

            <div style="margin-bottom:24px">
                <label style="display:block;font-size:13.5px;font-weight:700;color:#3a4d47;margin-bottom:8px">Plain-Text Description (Optional)</label>
                <textarea name="description" rows="3" placeholder="e.g. June bank statements for Example Test Company Ltd" style="width:100%;padding:14px 16px;border:1.5px solid #e0e9e5;border-radius:15px;font-size:14px;background:#fbfdfc;resize:vertical"></textarea>
            </div>

            <div style="display:flex;align-items:center;justify-content:space-between">
                <div style="display:flex;align-items:center;gap:8px;color:#7d8e88;font-size:12.5px">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                    <span>Stored outside public web root with TLS encryption</span>
                </div>
                <button type="submit" data-loading-text="Uploading…" style="background:#0d9488;color:#fff;border:none;padding:14px 28px;border-radius:14px;font-weight:700;font-size:15px;cursor:pointer;box-shadow:0 8px 18px -8px rgba(13,148,136,.7)">Upload Document</button>
            </div>
        </form>
    </div>
</div>

<script>
(() => {
const dropzone = document.getElementById('dropzone');
const fileInput = document.getElementById('fileInput');

dropzone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropzone.style.background = '#e6f4f1';
    dropzone.style.borderColor = '#0f766e';
});

dropzone.addEventListener('dragleave', (e) => {
    e.preventDefault();
    dropzone.style.background = '#f6faf8';
    dropzone.style.borderColor = '#0d9488';
});

dropzone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropzone.style.background = '#f6faf8';
    dropzone.style.borderColor = '#0d9488';
    if (e.dataTransfer.files.length > 0) {
        fileInput.files = e.dataTransfer.files;
        document.getElementById('selectedFileName').innerText = 'Selected: ' + fileInput.files[0].name;
    }
});
})();
</script>
