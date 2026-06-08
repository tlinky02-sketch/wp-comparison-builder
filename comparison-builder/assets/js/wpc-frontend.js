/**
 * WPC Frontend Vanilla JS
 * Lightweight alternative to React for SSR pages
 * Handles: Filters, Search, Comparison Selection, Load More
 */
(function () {
    'use strict';

    // State
    const wpc = {
        selectedIds: [],
        maxCompare: 4,
        filters: {
            category: 'all',
            features: [],
            search: ''
        }
    };

    // Initialize when DOM ready
    document.addEventListener('DOMContentLoaded', function () {
        initFilters();
        initSearch();
        initCompareSelection();
        initLoadMore();
    });

    /**
     * Filter Functionality
     */
    function initFilters() {
        // Category filter buttons
        document.querySelectorAll('[data-wpc-filter-cat]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const cat = this.dataset.wpcFilterCat;
                wpc.filters.category = cat;

                // Update active state
                document.querySelectorAll('[data-wpc-filter-cat]').forEach(function (b) {
                    b.classList.remove('wpc-filter-active');
                });
                this.classList.add('wpc-filter-active');

                applyFilters();
            });
        });

        // Feature filter checkboxes
        document.querySelectorAll('[data-wpc-filter-feat]').forEach(function (chk) {
            chk.addEventListener('change', function () {
                const feat = this.dataset.wpcFilterFeat;
                if (this.checked) {
                    if (wpc.filters.features.indexOf(feat) === -1) {
                        wpc.filters.features.push(feat);
                    }
                } else {
                    wpc.filters.features = wpc.filters.features.filter(function (f) { return f !== feat; });
                }
                applyFilters();
            });
        });
    }

    /**
     * Search Functionality
     */
    function initSearch() {
        const searchInput = document.querySelector('[data-wpc-search]');
        if (!searchInput) return;

        let debounceTimer;
        searchInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function () {
                wpc.filters.search = searchInput.value.toLowerCase().trim();
                applyFilters();
            }, 200);
        });
    }

    /**
     * Apply All Filters
     */
    function applyFilters() {
        const cards = document.querySelectorAll('[data-wpc-card]');
        let visibleCount = 0;

        cards.forEach(function (card) {
            const cardCats = (card.dataset.wpcCats || '').toLowerCase().split(',');
            const cardFeats = (card.dataset.wpcFeats || '').toLowerCase().split(',');
            const cardName = (card.dataset.wpcName || '').toLowerCase();

            let show = true;

            // Category filter
            if (wpc.filters.category !== 'all') {
                if (cardCats.indexOf(wpc.filters.category.toLowerCase()) === -1) {
                    show = false;
                }
            }

            // Feature filter (must have ALL selected features)
            if (wpc.filters.features.length > 0) {
                for (let i = 0; i < wpc.filters.features.length; i++) {
                    if (cardFeats.indexOf(wpc.filters.features[i].toLowerCase()) === -1) {
                        show = false;
                        break;
                    }
                }
            }

            // Search filter
            if (wpc.filters.search && cardName.indexOf(wpc.filters.search) === -1) {
                show = false;
            }

            card.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });

        // Update count display
        const countEl = document.querySelector('[data-wpc-count]');
        if (countEl) {
            countEl.textContent = visibleCount + ' ' + (visibleCount === 1 ? 'item' : 'items');
        }
    }

    /**
     * Comparison Selection
     */
    function initCompareSelection() {
        document.querySelectorAll('[data-wpc-compare-btn]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();

                const id = this.dataset.wpcCompareBtn;
                const name = this.dataset.wpcName || 'Item';

                toggleCompareSelection(id, name, this);
            });
        });
    }

    function toggleCompareSelection(id, name, btn) {
        const idx = wpc.selectedIds.indexOf(id);

        if (idx > -1) {
            // Remove
            wpc.selectedIds.splice(idx, 1);
            btn.classList.remove('wpc-compare-selected');
            btn.querySelector('.wpc-compare-text').textContent = 'Compare';
        } else {
            // Add (max check)
            if (wpc.selectedIds.length >= wpc.maxCompare) {
                showToast('Maximum ' + wpc.maxCompare + ' items can be compared');
                return;
            }
            wpc.selectedIds.push(id);
            btn.classList.add('wpc-compare-selected');
            btn.querySelector('.wpc-compare-text').textContent = 'Selected ✓';
        }

        updateCompareBar();
    }

    function updateCompareBar() {
        const bar = document.querySelector('[data-wpc-compare-bar]');
        if (!bar) return;

        if (wpc.selectedIds.length > 0) {
            bar.style.display = 'flex';
            bar.querySelector('[data-wpc-selected-count]').textContent = wpc.selectedIds.length;

            // Update names
            const namesContainer = bar.querySelector('[data-wpc-selected-names]');
            if (namesContainer) {
                namesContainer.innerHTML = '';
                wpc.selectedIds.forEach(function (id) {
                    const card = document.querySelector('[data-wpc-card="' + id + '"]');
                    if (card) {
                        const name = card.dataset.wpcName || 'Item';
                        const pill = document.createElement('span');
                        pill.className = 'wpc-selected-pill';
                        pill.innerHTML = name + ' <button data-wpc-remove="' + id + '">×</button>';
                        namesContainer.appendChild(pill);
                    }
                });

                // Bind remove buttons
                namesContainer.querySelectorAll('[data-wpc-remove]').forEach(function (removeBtn) {
                    removeBtn.addEventListener('click', function () {
                        const removeId = this.dataset.wpcRemove;
                        const cardBtn = document.querySelector('[data-wpc-compare-btn="' + removeId + '"]');
                        if (cardBtn) {
                            toggleCompareSelection(removeId, '', cardBtn);
                        }
                    });
                });
            }
        } else {
            bar.style.display = 'none';
        }
    }

    /**
     * Compare Now Action
     */
    window.wpcCompareNow = function () {
        if (wpc.selectedIds.length < 2) {
            showToast('Select at least 2 items to compare');
            return;
        }

        // Show comparison table
        const tableContainer = document.querySelector('[data-wpc-compare-table]');
        if (tableContainer) {
            // Load comparison table via AJAX
            loadComparisonTable(wpc.selectedIds);
        }
    };

    function loadComparisonTable(ids) {
        const container = document.querySelector('[data-wpc-compare-table]');
        if (!container) return;

        container.innerHTML = '<div class="wpc-loading">Loading comparison...</div>';
        container.style.display = 'block';

        // AJAX call to get comparison table HTML
        const xhr = new XMLHttpRequest();
        xhr.open('POST', wpcSettings.ajaxUrl);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function () {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        container.innerHTML = response.data.html;
                    } else {
                        container.innerHTML = '<p>Error loading comparison</p>';
                    }
                } catch (e) {
                    container.innerHTML = '<p>Error loading comparison</p>';
                }
            }
        };
        xhr.send('action=wpc_get_comparison_table&ids=' + ids.join(',') + '&nonce=' + wpcSettings.nonce);
    }

    /**
     * Load More
     */
    function initLoadMore() {
        const loadMoreBtn = document.querySelector('[data-wpc-load-more]');
        if (!loadMoreBtn) return;

        loadMoreBtn.addEventListener('click', function () {
            const hiddenCards = document.querySelectorAll('[data-wpc-card][data-wpc-hidden="true"]');
            const showCount = parseInt(this.dataset.wpcLoadMore) || 4;

            let shown = 0;
            hiddenCards.forEach(function (card) {
                if (shown < showCount) {
                    card.removeAttribute('data-wpc-hidden');
                    card.style.display = '';
                    shown++;
                }
            });

            // Hide button if no more hidden
            const remaining = document.querySelectorAll('[data-wpc-card][data-wpc-hidden="true"]');
            if (remaining.length === 0) {
                loadMoreBtn.style.display = 'none';
            }
        });
    }

    /**
     * Toast Notification
     */
    function showToast(message) {
        const existing = document.querySelector('.wpc-toast');
        if (existing) existing.remove();

        const toast = document.createElement('div');
        toast.className = 'wpc-toast';
        toast.textContent = message;
        toast.style.cssText = 'position:fixed;bottom:20px;right:20px;background:#1f2937;color:white;padding:12px 20px;border-radius:8px;font-size:14px;z-index:10000;animation:wpcFadeIn 0.3s;';
        document.body.appendChild(toast);

        setTimeout(function () {
            toast.style.opacity = '0';
            setTimeout(function () { toast.remove(); }, 300);
        }, 3000);
    }

    /**
     * Copy to Clipboard
     */
    window.wpcCopyToClipboard = function (text, btn) {
        navigator.clipboard.writeText(text).then(function () {
            const original = btn.innerHTML;
            btn.innerHTML = '✓ Copied!';
            setTimeout(function () { btn.innerHTML = original; }, 2000);
        });
    };

    // Expose for external use
    window.wpcState = wpc;
    window.wpcApplyFilters = applyFilters;

    /**
     * Category Tab Switcher
     */
    window.wpcSwitchTab = function (containerId, slug) {
        var container = document.getElementById(containerId);
        if (!container) return;

        // hide all contents
        var contents = container.querySelectorAll('.wpc-tab-content');
        contents.forEach(function (content) {
            content.style.display = 'none';
        });

        // show selected
        var selected = container.querySelector('.wpc-tab-content[data-tab="' + slug + '"]');
        if (selected) {
            selected.style.display = 'block';
        }

        // update tabs styling
        var buttons = container.querySelectorAll('.wpc-tab-btn');
        buttons.forEach(function (btn) {
            if (btn.dataset.tab === slug) {
                btn.style.color = 'hsl(var(--foreground))';
                btn.style.borderBottom = '2px solid hsl(var(--accent))';
                btn.style.opacity = '1';
                btn.dataset.active = "true";
            } else {
                btn.style.color = 'hsl(var(--muted-foreground))';
                btn.style.borderBottom = '2px solid transparent';
                btn.style.opacity = '0.8';
                delete btn.dataset.active;
            }
        });
    };
})();
