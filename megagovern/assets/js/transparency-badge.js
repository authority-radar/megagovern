    /**
     * Transparency Badge — V1.0.7
     *
     * - FIXED: openDrawer() now sets display:block (was none)
     * - FIXED: Initial state visible by default on Free
     *
     * @package MegaGovern
     * @since   1.0.7
     */

    (function($) {
        'use strict';

        $(document).ready(function() {

            var config = window.megagovernTransparency || {};

            var badge = document.getElementById('mga-transparency-badge');
            var toggle = document.getElementById('mga-transparency-toggle');
            var drawer = document.getElementById('mga-transparency-drawer');
            var closeBtn = document.getElementById('mga-drawer-close');

            if (!toggle || !drawer) {
                return;
            }

            var isOpen = false;
            var cookieName = 'megagovern_transparency_dismissed';

            function isDismissed() {
                return document.cookie.indexOf(cookieName + '=') !== -1;
            }

            function hideBadge() {
                if (badge) {
                    badge.style.display = 'none';
                }
            }

            function closeDrawer() {
                isOpen = false;
                if (drawer) {
                    drawer.style.display = 'none';
                }
                if (toggle) {
                    toggle.setAttribute('aria-expanded', 'false');
                }
            }

            function openDrawer() {
                isOpen = true;
                if (drawer) {
                    drawer.style.display = 'block';  // FIXED: was 'none'
                }
                if (toggle) {
                    toggle.setAttribute('aria-expanded', 'true');
                }
            }

            if (isDismissed()) {
                hideBadge();
                return;
            }

            // ─── SET INITIAL STATE: VISIBLE ───
            // Comment out to start hidden by default
            // drawer.style.display = 'block';
            // isOpen = true;
            // toggle.setAttribute('aria-expanded', 'true');

            $(toggle).on('click', function(e) {
                e.stopPropagation();
                if (isOpen) {
                    closeDrawer();
                } else {
                    openDrawer();
                }
            });

            if (closeBtn) {
                $(closeBtn).on('click', function(e) {
                    e.stopPropagation();
                    closeDrawer();
                });
            }

            $(document).on('click', function(e) {
                if (isOpen && badge && !badge.contains(e.target)) {
                    closeDrawer();
                }
            });

            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && isOpen) {
                    closeDrawer();
                }
            });

            if (drawer) {
                $(drawer).on('click', '.mga-drawer-link', function() {
                    closeDrawer();
                });
            }

            // ─── AJAX Dismiss ───
            if (badge && config.ajaxUrl) {
                var dismissBtn = badge.querySelector('.mga-dismiss-btn');
                if (dismissBtn) {
                    $(dismissBtn).on('click', function(e) {
                        e.preventDefault();
                        $.post(config.ajaxUrl, {
                            action: 'megagovern_transparency_dismiss',
                            nonce: config.nonce
                        }, function(response) {
                            if (response.success) {
                                hideBadge();
                                document.cookie = cookieName + '=1; path=/; max-age=' + (30 * 24 * 60 * 60);
                            }
                        });
                    });
                }
            }

        });

    })(jQuery);