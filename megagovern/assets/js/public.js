/**
 * MegaGovern — EU Label Interactive Handler
 * Handles: BEM classes (new) + legacy classes (old)
 */
(function() {
    'use strict';

    // ── Constants ──
    var LAYER_CLASS  = 'megagovern-eu-info-layer';
    var TRIGGER_NEW  = 'megagovern-eu-label__trigger';   // BEM
    var TRIGGER_OLD  = 'megagovern-eu-label-trigger';    // Legacy
    var CLOSE_NEW    = 'megagovern-eu-info-layer__close'; // BEM
    var CLOSE_OLD    = 'megagovern-eu-info-close';        // Legacy

    // ── Find trigger button (supports both BEM and legacy) ──
    function findTrigger(el) {
        return el.querySelector('.' + TRIGGER_NEW) || 
               el.querySelector('.' + TRIGGER_OLD);
    }

    // ── Close all layers ──
    function closeAllLayers() {
        document.querySelectorAll('.' + LAYER_CLASS).forEach(function(layer) {
            layer.style.display = 'none';
        });
        document.querySelectorAll('.' + TRIGGER_NEW + ', .' + TRIGGER_OLD).forEach(function(btn) {
            btn.setAttribute('aria-expanded', 'false');
        });
    }

    // ── Toggle layer ──
    function toggleLayer(trigger) {
        var label = trigger.closest('.megagovern-eu-label');
        if (!label) return;

        var layer = label.nextElementSibling;
        if (!layer || !layer.classList.contains(LAYER_CLASS)) return;

        var isOpen = layer.style.display === 'block';

        closeAllLayers();

        if (!isOpen) {
            layer.style.display = 'block';
            trigger.setAttribute('aria-expanded', 'true');
        }
    }

    // ── Close single layer ──
    function closeLayer(closeBtn) {
        var layer = closeBtn.closest('.' + LAYER_CLASS);
        if (!layer) return;

        layer.style.display = 'none';

        var label = layer.previousElementSibling;
        if (label) {
            var trigger = findTrigger(label);
            if (trigger) trigger.setAttribute('aria-expanded', 'false');
        }
    }

    // ── Event: Click ──
    document.addEventListener('click', function(e) {

        // Trigger click (BEM or legacy)
        var trigger = e.target.closest('.' + TRIGGER_NEW + ', .' + TRIGGER_OLD);
        if (trigger) {
            e.preventDefault();
            toggleLayer(trigger);
            return;
        }

        // Close button click (BEM or legacy)
        var closeBtn = e.target.closest('.' + CLOSE_NEW + ', .' + CLOSE_OLD);
        if (closeBtn) {
            e.preventDefault();
            closeLayer(closeBtn);
            return;
        }

        // Click outside — close all
        if (!e.target.closest('.megagovern-eu-label') && 
            !e.target.closest('.' + LAYER_CLASS)) {
            closeAllLayers();
        }
    });

    // ── Event: Escape key ──
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeAllLayers();
        }
    });

})();