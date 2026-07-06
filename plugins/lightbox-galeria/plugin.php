<?php
declare(strict_types=1);

/* Lightbox galéria — a frontend képeire kattintva teljes képernyős nézet */

add_action('front_footer', function (): void { ?>
<style>
.lb-overlay {
    position: fixed; inset: 0; z-index: 100;
    background: rgb(10 10 16 / .92);
    display: grid; place-items: center;
    opacity: 0; pointer-events: none; transition: opacity .22s;
}
.lb-overlay.open { opacity: 1; pointer-events: auto; }
.lb-overlay img {
    max-width: min(92vw, 1400px); max-height: 88vh;
    border-radius: 10px; box-shadow: 0 20px 80px rgb(0 0 0 / .5);
}
.lb-btn {
    position: absolute; border: 0; cursor: pointer;
    background: rgb(255 255 255 / .12); color: #fff;
    width: 44px; height: 44px; border-radius: 50%;
    font-size: 1.3rem; line-height: 1; display: grid; place-items: center;
    transition: background .15s;
}
.lb-btn:hover { background: rgb(255 255 255 / .25); }
.lb-close { top: 18px; right: 18px; }
.lb-prev { left: 14px; top: 50%; transform: translateY(-50%); }
.lb-next { right: 14px; top: 50%; transform: translateY(-50%); }
.lb-count {
    position: absolute; bottom: 18px; left: 50%; transform: translateX(-50%);
    color: rgb(255 255 255 / .75); font-size: .85rem;
}
.gal img, .block-image img, .prose img { cursor: zoom-in; }
</style>
<script>
(function () {
    const SELECTOR = '.gal img, .block-image img, .prose img';
    let items = [], idx = 0;

    const overlay = document.createElement('div');
    overlay.className = 'lb-overlay';
    overlay.innerHTML = '<img alt="">' +
        '<button class="lb-btn lb-close" aria-label="Bezárás">✕</button>' +
        '<button class="lb-btn lb-prev" aria-label="Előző">‹</button>' +
        '<button class="lb-btn lb-next" aria-label="Következő">›</button>' +
        '<span class="lb-count"></span>';
    document.body.appendChild(overlay);
    const img = overlay.querySelector('img');
    const count = overlay.querySelector('.lb-count');

    function show(i) {
        idx = (i + items.length) % items.length;
        img.src = items[idx].currentSrc || items[idx].src;
        count.textContent = items.length > 1 ? (idx + 1) + ' / ' + items.length : '';
        overlay.querySelector('.lb-prev').hidden = overlay.querySelector('.lb-next').hidden = items.length < 2;
    }
    function close() {
        overlay.classList.remove('open');
        document.body.style.overflow = '';
    }

    document.addEventListener('click', e => {
        const target = e.target.closest(SELECTOR);
        if (target) {
            if (target.closest('a')) return; // linkelt kép: hagyjuk a linket érvényesülni
            items = [...document.querySelectorAll(SELECTOR)].filter(el => !el.closest('a'));
            overlay.classList.add('open');
            document.body.style.overflow = 'hidden';
            show(items.indexOf(target));
            return;
        }
        if (e.target.closest('.lb-close') || e.target === overlay) close();
        if (e.target.closest('.lb-prev')) show(idx - 1);
        if (e.target.closest('.lb-next')) show(idx + 1);
    });
    document.addEventListener('keydown', e => {
        if (!overlay.classList.contains('open')) return;
        if (e.key === 'Escape') close();
        if (e.key === 'ArrowLeft') show(idx - 1);
        if (e.key === 'ArrowRight') show(idx + 1);
    });
})();
</script>
<?php });
