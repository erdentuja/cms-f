<div class="panel side-panel">
    <h3>Beszúrható modulok</h3>
    <p class="muted side-hint">Kattints egy shortcode-ra a másoláshoz, majd illeszd be a tartalomba — szöveg- vagy HTML-blokkban is működik.</p>
    <ul class="sc-list">
        <?php foreach (shortcodes_all() as $tag => $sc): ?>
        <li>
            <button type="button" class="sc-copy" data-code="<?= e($sc['example']) ?>" title="Másolás vágólapra"><code><?= e($sc['example']) ?></code></button>
            <?php if ($sc['desc'] !== ''): ?><span class="muted"><?= e($sc['desc']) ?></span><?php endif; ?>
        </li>
        <?php endforeach; ?>
    </ul>
</div>
<script>
document.querySelectorAll('.sc-copy').forEach(b => b.addEventListener('click', async () => {
    await navigator.clipboard.writeText(b.dataset.code);
    const c = b.querySelector('code'), t = c.textContent;
    c.textContent = 'Másolva!';
    setTimeout(() => { c.textContent = t; }, 1100);
}));
</script>
