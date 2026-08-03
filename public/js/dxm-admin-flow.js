(() => {
    const pageKey = document.querySelector('[data-dxm-page-key]')?.getAttribute('data-dxm-page-key') || location.pathname;
    const scrollKey = `dxm.scroll.${pageKey}.${location.pathname}${location.search}`;

    let restoreTimer = null;

    const restoreScroll = () => {
        const raw = sessionStorage.getItem(scrollKey);
        if (!raw) return;

        const y = parseInt(raw, 10);
        if (Number.isFinite(y) && y > 0) {
            window.scrollTo({ top: y, behavior: 'auto' });
        }
    };

    window.addEventListener('beforeunload', () => {
        sessionStorage.setItem(scrollKey, String(window.scrollY || 0));
    });

    document.addEventListener('submit', () => {
        sessionStorage.setItem(scrollKey, String(window.scrollY || 0));
    }, true);

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[href]');
        const button = event.target.closest('button[type="submit"], input[type="submit"]');

        if (link || button) {
            sessionStorage.setItem(scrollKey, String(window.scrollY || 0));
        }

        const openPreview = event.target.closest('[data-dxm-open-side-preview]');
        if (openPreview) {
            document.documentElement.classList.add('dxm-side-preview-open');
            localStorage.setItem('dxm.sidePreview.open', '1');
        }

        const closePreview = event.target.closest('[data-dxm-close-side-preview]');
        if (closePreview) {
            document.documentElement.classList.remove('dxm-side-preview-open');
            localStorage.setItem('dxm.sidePreview.open', '0');
        }
    }, true);

    document.addEventListener('DOMContentLoaded', () => {
        clearTimeout(restoreTimer);
        restoreTimer = setTimeout(restoreScroll, 120);

        if (localStorage.getItem('dxm.sidePreview.open') === '1') {
            document.documentElement.classList.add('dxm-side-preview-open');
        }
    });

    window.addEventListener('load', () => {
        clearTimeout(restoreTimer);
        restoreTimer = setTimeout(restoreScroll, 180);
    });
})();
