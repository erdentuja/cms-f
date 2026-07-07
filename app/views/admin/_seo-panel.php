<?php
/* Megosztott SEO-panel a poszt- és oldalszerkesztőhöz.
   Elvárt: $seoRow (tömb a seo_* mezőkkel), $seoTitleHint, $seoDescHint, $seoImageHint (feliratok) */
?>
<div class="panel side-panel">
    <h3>SEO</h3>
    <label class="field">
        <span>SEO cím <em class="muted">(<?= e($seoTitleHint) ?>)</em></span>
        <input class="input" type="text" name="seo_title" maxlength="70" value="<?= e($seoRow['seo_title'] ?? '') ?>">
    </label>
    <label class="field">
        <span>Meta leírás<?= $seoDescHint !== '' ? ' <em class="muted">(' . e($seoDescHint) . ')</em>' : '' ?></span>
        <textarea class="input" name="seo_description" rows="3" maxlength="160"><?= e($seoRow['seo_description'] ?? '') ?></textarea>
    </label>
    <label class="field">
        <span>OG kép<?= $seoImageHint !== '' ? ' <em class="muted">(' . e($seoImageHint) . ')</em>' : '' ?></span>
        <input class="input" type="text" name="seo_image" id="seoImageInput" value="<?= e($seoRow['seo_image'] ?? '') ?>" placeholder="uploads/... vagy https://...">
    </label>
    <button class="btn btn-ghost btn-block" type="button" onclick="openMediaPicker(url => document.getElementById('seoImageInput').value = relUrl(url))">OG kép választása</button>
    <label class="field">
        <span>Canonical URL <em class="muted">(üresen automatikus)</em></span>
        <input class="input" type="url" name="seo_canonical" value="<?= e($seoRow['seo_canonical'] ?? '') ?>">
    </label>
    <label class="field">
        <span>Robots</span>
        <?php $seoRobots = $seoRow['seo_robots'] ?? 'index,follow'; ?>
        <select class="input" name="seo_robots">
            <?php foreach (['index,follow', 'noindex,follow', 'index,nofollow', 'noindex,nofollow'] as $opt): ?>
            <option value="<?= e($opt) ?>" <?= $seoRobots === $opt ? 'selected' : '' ?>><?= e($opt) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
</div>
