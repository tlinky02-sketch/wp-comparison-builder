(function () {
    var selectedItems = {}; // id: name

    window.toggleCompareDropdown = function (id) {
        var dropdown = document.getElementById(id);
        if (dropdown) {
            dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
        }
    };

    window.toggleItemSelection = function (dropdownId, id, name, primaryId) {
        var errorDiv = document.getElementById(dropdownId + '-error');
        if (selectedItems[id]) {
            delete selectedItems[id];
            if (errorDiv) errorDiv.style.display = 'none';
        } else {
            if (Object.keys(selectedItems).length >= 3) {
                if (errorDiv) {
                    errorDiv.style.display = 'block';
                    setTimeout(function () { errorDiv.style.display = 'none'; }, 4000);
                }
                return;
            }
            selectedItems[id] = name;
        }
        updateSelectionUI(dropdownId, primaryId);
    };

    function updateSelectionUI(dropdownId, primaryId) {
        var options = document.querySelectorAll('.compare-option-' + dropdownId);
        var mobileBtn = document.querySelector('#' + dropdownId + ' .mobile-compare-btn');
        var ids = Object.keys(selectedItems);

        if (mobileBtn) mobileBtn.style.display = ids.length > 0 ? 'block' : 'none';

        // Update Dropdown Items visual state
        options.forEach(function (opt) {
            var id = opt.dataset.id;
            var checkmark = opt.querySelector('.checkmark');
            var chevron = opt.querySelector('.chevron');
            if (selectedItems[id]) {
                opt.style.background = '#f8fafc';
                if (checkmark) checkmark.style.display = 'block';
                if (chevron) chevron.style.display = 'none';
            } else {
                opt.style.background = 'white';
                if (checkmark) checkmark.style.display = 'none';
                if (chevron) chevron.style.display = 'block';
            }
        });

        // Dispatch to React (Live update for the main selection bar)
        var allIds = [String(primaryId)].concat(ids);

        // Dispatch new event
        // Dispatch new event
        var event = new CustomEvent('wpcCompareSelect', {
            detail: { providerIds: allIds, autoShow: false, source: 'external-button' }
        });
        window.dispatchEvent(event);

        // Legacy Event for compatibility
        var legacyEvent = new CustomEvent('ecommerceCompareSelect', {
            detail: { providerIds: allIds, autoShow: false, source: 'external-button' }
        });
        window.dispatchEvent(legacyEvent);
    }

    window.handleFinalCompare = function (dropdownId, primaryId) {
        var ids = Object.keys(selectedItems);
        if (ids.length === 0) return;

        // Close dropdown
        var dropdown = document.getElementById(dropdownId);
        if (dropdown) dropdown.style.display = 'none';

        // Dispatch to React with autoShow: true to trigger the table
        var allIds = [String(primaryId)].concat(ids);

        var event = new CustomEvent('wpcCompareSelect', {
            detail: { providerIds: allIds, autoShow: true, source: 'external-button' }
        });
        window.dispatchEvent(event);

        // Legacy Event
        var legacyEvent = new CustomEvent('ecommerceCompareSelect', {
            detail: { providerIds: allIds, autoShow: true, source: 'external-button' }
        });
        window.dispatchEvent(legacyEvent);
    };

    window.filterCompareOptions = function (id) {
        var input = document.getElementById(id + '-search');
        if (!input) return;
        var filter = input.value.toLowerCase();
        var options = document.querySelectorAll('.compare-option-' + id);
        options.forEach(function (option) {
            var name = option.getAttribute('data-name');
            if (name) {
                option.style.display = name.includes(filter) ? 'flex' : 'none';
            }
        });
    };

    // Close dropdown when clicking outside
    document.addEventListener('click', function (event) {
        if (!event.target.closest('.wpc-compare-button-wrapper')) {
            var dropdowns = document.querySelectorAll('.wpc-compare-dropdown');
            dropdowns.forEach(function (d) { d.style.display = 'none'; });
        }
    });

    // Check availability hook
    window.wpcCompareButtonPresent = true;
})();
