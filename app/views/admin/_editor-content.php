<?php
/* Megosztott klasszikus/blokkszerkesztő UI — poszt- és oldalszerkesztőben egyaránt.
   Elvárt változók: $content (aktuális HTML), $blockTypes (tömb) */
?>
<div class="editor-tabs">
    <button type="button" class="tab-btn" data-tab="classic">Klasszikus szerkesztő</button>
    <button type="button" class="tab-btn" data-tab="builder">Blokkszerkesztő</button>
</div>

<div class="editor-wrap panel" id="classicPane">
    <div id="editor"><?= $content ?? '' ?></div>
    <textarea name="content" id="contentField" hidden></textarea>
</div>

<div class="panel builder-pane" id="builderPane">
    <div class="builder-toolbar">
        <span class="muted">Blokk hozzáadása:</span>
        <div class="block-add-buttons">
            <?php $icons = block_icons(); foreach ($blockTypes as $type => $label): ?>
                <button type="button" class="block-tile" data-add="<?= e($type) ?>" title="<?= e($label) ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="<?= $icons[$type] ?? 'M21 8l-9-5-9 5 9 5zM3 8v8l9 5 9-5V8M12 13v8' ?>"/></svg>
                    <span><?= e($label) ?></span>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
    <ul class="block-list" id="blockList"></ul>
    <p class="muted pad" id="blockEmpty">Még nincs blokk. Adj hozzá egyet a fenti gombokkal!</p>
</div>
<textarea name="blocks" id="blocksField" hidden></textarea>
