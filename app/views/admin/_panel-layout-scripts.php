<script>
(() => {
    const form = document.getElementById('editForm');
    if (!form) return;
    const zones = [...form.querySelectorAll('[data-panel-zone]')];
    if (zones.length < 2) return;
    const storageKey = 'cms.panelLayout.' + location.pathname.replace(/\/\d+$/, '');
    const panels = () => zones.flatMap(z => [...z.querySelectorAll(':scope > .side-panel')]);
    const panelKey = (panel, i = 0) => {
        if (panel.dataset.panelId) return panel.dataset.panelId;
        const title = panel.querySelector('h3')?.textContent || ('panel-' + i);
        panel.dataset.panelId = title.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') || ('panel-' + i);
        return panel.dataset.panelId;
    };
    const updateEmpty = () => zones.forEach(z => z.classList.toggle('is-empty', !z.querySelector(':scope > .side-panel')));
    const save = () => {
        const state = {};
        zones.forEach(z => state[z.dataset.panelZone] = [...z.querySelectorAll(':scope > .side-panel')].map(p => p.dataset.panelId));
        localStorage.setItem(storageKey, JSON.stringify(state));
        updateEmpty();
    };
    const insertBeforePointer = (zone, panel, x, y) => {
        const siblings = [...zone.querySelectorAll(':scope > .side-panel:not(.dragging)')];
        const before = siblings.find(el => {
            const r = el.getBoundingClientRect();
            const centerY = r.top + r.height / 2;
            const centerX = r.left + r.width / 2;
            const sameRow = y >= r.top && y <= r.bottom;
            return y < centerY || (sameRow && x < centerX);
        });
        zone.insertBefore(panel, before || zone.querySelector('.panel-zone-empty'));
    };

    const preparePanels = () => panels().forEach((panel, i) => {
        panel.classList.add('sortable-panel');
        panel.draggable = true;
        panelKey(panel, i);
        const head = panel.querySelector('h3');
        if (head) head.draggable = true;
    });

    preparePanels();

    try {
        const state = JSON.parse(localStorage.getItem(storageKey) || '{}');
        Object.entries(state).forEach(([zoneName, ids]) => {
            const zone = zones.find(z => z.dataset.panelZone === zoneName);
            if (!zone || !Array.isArray(ids)) return;
            ids.forEach(id => {
                const panel = panels().find(p => p.dataset.panelId === id);
                if (panel) zone.insertBefore(panel, zone.querySelector('.panel-zone-empty'));
            });
        });
    } catch {}
    preparePanels();
    updateEmpty();

    let dragged = null;
    let dragHandlePanel = null;
    form.addEventListener('pointerdown', e => {
        dragHandlePanel = e.target.closest('.sortable-panel > h3')?.closest('.sortable-panel') || null;
    });
    form.addEventListener('dragstart', e => {
        const panel = e.target.closest('.sortable-panel');
        if (!panel || panel !== dragHandlePanel) {
            e.preventDefault();
            return;
        }
        dragged = panel;
        panel.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', panel.dataset.panelId);
        e.dataTransfer.setDragImage(panel, Math.min(160, panel.offsetWidth / 2), 22);
    });
    form.addEventListener('dragover', e => {
        if (!dragged) return;
        const zone = e.target.closest('[data-panel-zone]');
        if (!zone) return;
        e.preventDefault();
        zones.forEach(z => z.classList.toggle('drag-over', z === zone));
        zone.classList.add('drag-over');
        insertBeforePointer(zone, dragged, e.clientX, e.clientY);
    });
    form.addEventListener('dragleave', e => {
        const zone = e.target.closest('[data-panel-zone]');
        if (zone && !zone.contains(e.relatedTarget)) zone.classList.remove('drag-over');
    });
    form.addEventListener('drop', e => {
        if (!dragged) return;
        e.preventDefault();
        zones.forEach(z => z.classList.remove('drag-over'));
        save();
    });
    form.addEventListener('dragend', () => {
        dragged?.classList.remove('dragging');
        dragged = null;
        dragHandlePanel = null;
        zones.forEach(z => z.classList.remove('drag-over'));
        save();
    });
    document.addEventListener('drop', () => zones.forEach(z => z.classList.remove('drag-over')));
})();
</script>
