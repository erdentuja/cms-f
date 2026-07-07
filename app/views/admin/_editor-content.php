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
            <?php foreach ($blockTypes as $type => $label): ?>
                <button type="button" class="btn btn-ghost btn-sm" data-add="<?= e($type) ?>"><?= e($label) ?></button>
            <?php endforeach; ?>
        </div>
    </div>
    <ul class="block-list" id="blockList"></ul>
    <p class="muted pad" id="blockEmpty">Még nincs blokk. Adj hozzá egyet a fenti gombokkal!</p>
</div>
<textarea name="blocks" id="blocksField" hidden></textarea>
