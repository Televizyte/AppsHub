<script>
    document.addEventListener('DOMContentLoaded', function () {
        const drawer = document.querySelector('[data-dxm-preview-drawer]');
        const backdrop = document.querySelector('[data-dxm-preview-backdrop]');
        const toggles = document.querySelectorAll('[data-dxm-toggle-preview], [data-dxm-open-preview]');
        const closers = document.querySelectorAll('[data-dxm-close-preview]');
        const tabButtons = document.querySelectorAll('[data-dxm-preview-tab]');
        const panels = document.querySelectorAll('[data-dxm-preview-panel]');
        const titleTargets = document.querySelectorAll('[data-dxm-preview-title], [data-dxm-phone-title]');
        const mainView = document.querySelector('[data-dxm-preview-main]');
        const detailView = document.querySelector('[data-dxm-preview-detail]');
        const playerView = document.querySelector('[data-dxm-preview-player]');
        const backButtons = document.querySelectorAll('[data-dxm-preview-back]');
        const phoneContent = document.querySelector('.dxm-phone-content');

        function openPreview() {
            if (!drawer || !backdrop) return;
            drawer.classList.add('is-open');
            backdrop.classList.add('is-open');
            drawer.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        function closePreview() {
            if (!drawer || !backdrop) return;
            drawer.classList.remove('is-open');
            backdrop.classList.remove('is-open');
            drawer.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
            showMainView();
        }

        function togglePreview() {
            if (drawer && drawer.classList.contains('is-open')) {
                closePreview();
            } else {
                openPreview();
            }
        }

        function setTitle(label) {
            titleTargets.forEach(function (target) {
                target.textContent = label;
            });
        }

        function resetScroll() {
            if (phoneContent) {
                phoneContent.scrollTop = 0;
            }
        }

        function showMainView() {
            if (mainView) mainView.classList.remove('is-hidden');
            if (detailView) detailView.classList.remove('is-active');
            if (playerView) playerView.classList.remove('is-active');

            const activeButton = document.querySelector('[data-dxm-preview-tab].is-active');
            if (activeButton) {
                setTitle(activeButton.getAttribute('data-dxm-preview-label') || 'Preview');
            }

            resetScroll();
        }

        function switchPreviewTab(key, label) {
            showMainView();

            panels.forEach(function (panel) {
                panel.classList.toggle('is-active', panel.getAttribute('data-dxm-preview-panel') === key);
            });

            tabButtons.forEach(function (button) {
                button.classList.toggle('is-active', button.getAttribute('data-dxm-preview-tab') === key);
            });

            setTitle(label || key);
            resetScroll();
        }

        function routeToTab(route, routeKey) {
            const combined = ((route || '') + ' ' + (routeKey || '')).toLowerCase();

            if (combined.includes('/watch') || combined.includes('watch') || combined.includes('live') || combined.includes('commanding')) {
                switchPreviewTab('watch', 'Watch');
                return true;
            }

            if (combined.includes('/inspire') || combined.includes('sod') || combined.includes('article') || combined.includes('highlight') || combined.includes('wordification') || combined.includes('motivation')) {
                switchPreviewTab('inspire', 'Inspire');
                return true;
            }

            if (combined.includes('/explore') || combined.includes('quote_creator') || combined.includes('notes') || combined.includes('bible') || combined.includes('game')) {
                switchPreviewTab('explore', 'Explore');
                return true;
            }

            if (combined.includes('/more') || combined.includes('settings') || combined.includes('account') || combined.includes('saved')) {
                switchPreviewTab('more', 'More');
                return true;
            }

            return false;
        }

        function showDetailView(card) {
            const title = card.getAttribute('data-title') || '';
            const subtitle = card.getAttribute('data-subtitle') || '';
            const description = card.getAttribute('data-description') || '';
            const image = card.getAttribute('data-image') || '';
            const actionType = card.getAttribute('data-action-type') || '';
            const route = card.getAttribute('data-route') || '';
            const routeKey = card.getAttribute('data-route-key') || '';

            const imageEl = detailView.querySelector('[data-dxm-detail-image]');
            const imageEmpty = detailView.querySelector('[data-dxm-detail-image-empty]');
            const titleEl = detailView.querySelector('[data-dxm-detail-title]');
            const subtitleEl = detailView.querySelector('[data-dxm-detail-subtitle]');
            const descriptionEl = detailView.querySelector('[data-dxm-detail-description]');
            const actionEl = detailView.querySelector('[data-dxm-detail-action]');

            if (imageEl && imageEmpty) {
                if (image) {
                    imageEl.src = image;
                    imageEl.style.display = 'block';
                    imageEmpty.style.display = 'none';
                } else {
                    imageEl.removeAttribute('src');
                    imageEl.style.display = 'none';
                    imageEmpty.style.display = 'inline';
                }
            }

            if (titleEl) titleEl.textContent = title;
            if (subtitleEl) subtitleEl.textContent = subtitle;
            if (descriptionEl) descriptionEl.textContent = description;
            if (actionEl) actionEl.textContent = 'Action: ' + actionType + (routeKey ? ' · ' + routeKey : '') + (route ? ' · ' + route : '');

            mainView.classList.add('is-hidden');
            playerView.classList.remove('is-active');
            detailView.classList.add('is-active');
            setTitle('Detail');
            resetScroll();
        }

        function showPlayerView(card) {
            const title = card.getAttribute('data-title') || '';
            const subtitle = card.getAttribute('data-subtitle') || '';
            const engine = card.getAttribute('data-engine') || 'web';
            const url = card.getAttribute('data-url') || '';

            const titleEl = playerView.querySelector('[data-dxm-player-title]');
            const subtitleEl = playerView.querySelector('[data-dxm-player-subtitle]');
            const engineEl = playerView.querySelector('[data-dxm-player-engine]');
            const urlEl = playerView.querySelector('[data-dxm-player-url]');

            if (titleEl) titleEl.textContent = title;
            if (subtitleEl) subtitleEl.textContent = subtitle;
            if (engineEl) engineEl.textContent = 'Engine: ' + engine;
            if (urlEl) urlEl.textContent = url;

            mainView.classList.add('is-hidden');
            detailView.classList.remove('is-active');
            playerView.classList.add('is-active');
            setTitle('Player');
            resetScroll();
        }

        function handleCardClick(card) {
            const actionType = (card.getAttribute('data-action-type') || '').toLowerCase();
            const engine = card.getAttribute('data-engine') || '';
            const url = card.getAttribute('data-url') || '';
            const route = card.getAttribute('data-route') || '';
            const routeKey = card.getAttribute('data-route-key') || '';

            if (actionType === 'route' || route || routeKey) {
                if (routeToTab(route, routeKey)) {
                    return;
                }
            }

            if (actionType === 'engine' || engine || actionType === 'external_url') {
                showPlayerView(card);
                return;
            }

            if (url && !route) {
                showPlayerView(card);
                return;
            }

            showDetailView(card);
        }

        toggles.forEach(function (button) {
            button.addEventListener('click', togglePreview);
        });

        closers.forEach(function (button) {
            button.addEventListener('click', closePreview);
        });

        tabButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                switchPreviewTab(
                    button.getAttribute('data-dxm-preview-tab'),
                    button.getAttribute('data-dxm-preview-label')
                );
            });
        });

        document.querySelectorAll('[data-dxm-preview-card]').forEach(function (card) {
            card.addEventListener('click', function (event) {
                if (event.target.closest('[data-dxm-route-jump]')) {
                    return;
                }

                openPreview();
                handleCardClick(card);
            });
        });

        document.querySelectorAll('[data-dxm-route-jump]').forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.stopPropagation();
                openPreview();
                routeToTab(
                    '',
                    button.getAttribute('data-target-route-key') || button.getAttribute('data-target-tab') || ''
                );
            });
        });

        backButtons.forEach(function (button) {
            button.addEventListener('click', showMainView);
        });

        if (backdrop) {
            backdrop.addEventListener('click', closePreview);
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closePreview();
            }
        });
    });
</script>
