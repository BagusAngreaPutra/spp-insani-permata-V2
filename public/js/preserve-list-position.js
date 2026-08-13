(function () {
    'use strict';

    const body = document.body;
    const context = body?.dataset.listContext || '';
    const isListPage = body?.dataset.listPage === 'true';

    if (!context || !isListPage || !window.sessionStorage) {
        return;
    }

    const locationKey = `${window.location.pathname}${window.location.search}`;
    const storageKey = `permata:list-scroll:${context}:${locationKey}`;
    let restorationComplete = false;

    const savePosition = () => {
        if (!restorationComplete) return;

        const scrollTop = window.scrollY
            || document.documentElement.scrollTop
            || document.body.scrollTop
            || 0;

        try {
            window.sessionStorage.setItem(storageKey, String(Math.max(0, Math.round(scrollTop))));
        } catch (error) {
            // Penyimpanan posisi bersifat tambahan; navigasi utama tetap berjalan.
        }
    };

    const restorePosition = () => {
        let storedPosition = null;

        try {
            storedPosition = window.sessionStorage.getItem(storageKey);
        } catch (error) {
            restorationComplete = true;
            return;
        }

        if (storedPosition !== null) {
            const top = Number.parseInt(storedPosition, 10);

            if (Number.isFinite(top) && top > 0) {
                window.requestAnimationFrame(() => {
                    window.requestAnimationFrame(() => window.scrollTo({ top, left: 0, behavior: 'auto' }));
                });
            }
        }

        restorationComplete = true;
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', restorePosition, { once: true });
    } else {
        restorePosition();
    }

    window.addEventListener('pagehide', savePosition);
    document.addEventListener('click', (event) => {
        if (event.target.closest('a[href], button[type="submit"], input[type="submit"]')) {
            savePosition();
        }
    }, true);
})();
