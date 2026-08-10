/**
 * MegaGovern Admin Scripts — V1.0.4
 * @package MegaGovern
 * @since 1.0.4
 */
( function( $ ) {
    'use strict';

    // ═══════════════════════════════════════
    // CONFIG & FALLBACKS
    // ═══════════════════════════════════════
    var dashboardConfig = window.megaGovernDashboard || {};
    var govConfig       = window.megaGovernanceConfig || {};
    var mgaVars         = window.megagovern_vars || {};

    var AJAX_URL       = dashboardConfig.ajaxUrl || govConfig.ajaxUrl || window.ajaxurl || '';
    var AUTO_FIX_NONCE = mgaVars.autofix_nonce || dashboardConfig.autoFixNonce || '';
    var SCAN_NONCE     = mgaVars.scan_nonce || '';
    var CACHE_NONCE    = dashboardConfig.cacheNonce || '';

    var I18N = dashboardConfig.i18n || {};
    I18N.fixing    = I18N.fixing    || 'Fixing...';
    I18N.done      = I18N.done      || 'Done';
    I18N.error     = I18N.error     || 'Error - Click to retry';
    I18N.refreshing = I18N.refreshing || 'Refreshing dashboard...';
    I18N.syncing   = I18N.syncing   || 'Syncing...';

    // ═══════════════════════════════════════
    // UTILITY: AJAX helper
    // ═══════════════════════════════════════
    function megaAjax( action, data, nonce ) {
        var formData = new FormData();
        formData.append( 'action', action );
        for ( var key in data ) {
            if ( data.hasOwnProperty( key ) ) {
                formData.append( key, data[ key ] );
            }
        }
        if ( nonce ) {
            formData.append( '_ajax_nonce', nonce );
        }
        return fetch( AJAX_URL, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then( function( res ) {
            if ( ! res.ok ) throw new Error( 'HTTP ' + res.status );
            return res.json();
        });
    }

    // ═══════════════════════════════════════
    // Clean URL after refresh
    // ═══════════════════════════════════════
    function cleanRefreshUrl() {
        if ( window.history.replaceState && window.location.search.indexOf( 'refresh=1' ) !== -1 ) {
            var clean = window.location.href.replace( '&refresh=1', '' ).replace( '?refresh=1', '' );
            clean = clean.replace( /&_wpnonce=[a-zA-Z0-9]+/g, '' ).replace( /\?_wpnonce=[a-zA-Z0-9]+&?/g, '?' );
            window.history.replaceState( {}, document.title, clean );
        }
    }

    // ═══════════════════════════════════════
    // Direct Scan & Fix Button Handlers
    // ═══════════════════════════════════════
    function initDirectActionButtons() {
        $( '#mga_autofix_btn' ).on( 'click', function( e ) {
            e.preventDefault();
            var $btn = $( this );
            $btn.prop( 'disabled', true ).css( 'opacity', '0.6' );

            megaAjax( 'megagovern_auto_fix', { 
                issue_id: 'auto_detect',
                _wpnonce: AUTO_FIX_NONCE 
            }, AUTO_FIX_NONCE )
                .then( function( res ) {
                    alert( res.data ? res.data.message : 'Auto-Fix completed.' );
                    location.reload();
                })
                .catch( function( err ) {
                    console.error( 'MegaGovern: Auto-Fix failed', err );
                    alert( 'Auto-Fix failed.' );
                    $btn.prop( 'disabled', false ).css( 'opacity', '1' );
                });
        });
    }

    // ═══════════════════════════════════════
    // Auto-Fix buttons (.auto-fix-btn)
    // ═══════════════════════════════════════
    function initAutoFixButtons() {
        var $buttons = $( '.auto-fix-btn' );
        if ( ! $buttons.length ) return;

        $buttons.on( 'click', function() {
            var $btn = $( this );
            var issueId = $btn.data( 'issue-id' );
            var originalText = $btn.text();
            if ( $btn.prop( 'disabled' ) ) return;

            $btn.text( I18N.fixing );
            $btn.prop( 'disabled', true );
            $btn.addClass( 'mga-loading' );

            megaAjax( 'megagovern_auto_fix', { issue_id: issueId }, AUTO_FIX_NONCE )
                .then( function( response ) {
                    if ( response.success ) {
                        megaAjax( 'megagovern_clear_dashboard_cache', {}, CACHE_NONCE ).catch( function() {} );
                        $btn.text( I18N.done );
                        $btn.removeClass( 'mga-loading' ).addClass( 'mga-success' );
                        setTimeout( function() { window.location.reload(); }, 800 );
                    } else {
                        throw new Error( response.data && response.data.message ? response.data.message : 'Unknown error' );
                    }
                })
                .catch( function( err ) {
                    console.error( 'MegaGovern: Auto-fix failed', err );
                    $btn.text( I18N.error );
                    $btn.removeClass( 'mga-loading' ).addClass( 'mga-error' );
                    $btn.prop( 'disabled', false );
                    setTimeout( function() {
                        $btn.text( originalText );
                        $btn.removeClass( 'mga-error' );
                    }, 3000 );
                });
        });
    }

    // ═══════════════════════════════════════
    // Copy to clipboard
    // ═══════════════════════════════════════
    function initCopyButtons() {
        $( '.mga-copy-btn' ).on( 'click', function() {
            var $btn = $( this );
            var text = $btn.data( 'copy' );
            var label = $btn.text();
            if ( ! text ) return;

            if ( navigator.clipboard && navigator.clipboard.writeText ) {
                navigator.clipboard.writeText( text ).then( function() {
                    $btn.text( 'Copied' );
                    setTimeout( function() { $btn.text( label ); }, 1500 );
                });
            } else {
                var $temp = $( '<textarea>' );
                $( 'body' ).append( $temp );
                $temp.val( text ).select();
                document.execCommand( 'copy' );
                $temp.remove();
                $btn.text( 'Copied' );
                setTimeout( function() { $btn.text( label ); }, 1500 );
            }
        });
    }

    // ═══════════════════════════════════════
    // Governance Center — Bulk Actions & History
    // ═══════════════════════════════════════
    function initGovernance() {
        if ( ! $( '.megagovern-transparency' ).length && ! $( '.megagovern-governance' ).length ) return;

        function updateGovCount() {
            var count = $( '.dec_checkbox:checked' ).length;
            $( '#selected_count' ).text( count );
        }

        $( document ).on( 'change', '#select_all', function() {
            $( '.dec_checkbox' ).prop( 'checked', this.checked );
            updateGovCount();
        });

        $( document ).on( 'change', '.dec_checkbox', function() {
            updateGovCount();
        });

        $( document ).on( 'click', '#bulk_execute_btn', function() {
            var actionType = $( '#bulk_action' ).val();
            var $btn = $( this );
            var selectMsg = ( govConfig.i18n && govConfig.i18n.selectClassification ) ? govConfig.i18n.selectClassification : 'Please select a classification.';
            var itemMsg   = ( govConfig.i18n && govConfig.i18n.selectItem ) ? govConfig.i18n.selectItem : 'Please select at least one item.';
            var procMsg   = ( govConfig.i18n && govConfig.i18n.processing ) ? govConfig.i18n.processing : 'Processing...';

            if ( ! actionType ) {
                alert( selectMsg );
                return;
            }
            var ids = [];
            $( '.dec_checkbox:checked' ).each( function() { ids.push( this.value ); });
            if ( ! ids.length ) {
                alert( itemMsg );
                return;
            }
            var $result = $( '#bulk_result' );
            $result.show().css( 'color', 'var(--mga-muted)' ).text( procMsg );
            $btn.prop( 'disabled', true ).text( procMsg );

            var formData = new FormData();
            formData.append( 'action', 'megagovern_bulk_declare' );
            formData.append( '_ajax_nonce', govConfig.nonceBulk );
            formData.append( 'post_ids', JSON.stringify( ids ) );
            formData.append( 'declaration_type', actionType );

            fetch( AJAX_URL, { method: 'POST', body: formData, credentials: 'same-origin' })
                .then( function( r ) { return r.json(); })
                .then( function( d ) {
                    if ( d.success ) {
                        $result.css( 'color', 'var(--mga-success)' ).text( d.data.message || ( govConfig.i18n && govConfig.i18n.done ? govConfig.i18n.done : 'Done' ) );
                        setTimeout( function() { location.reload(); }, 1000 );
                    } else {
                        $result.css( 'color', 'var(--mga-danger)' ).text( govConfig.i18n && govConfig.i18n.error ? govConfig.i18n.error : 'Error.' );
                        $btn.prop( 'disabled', false ).text( 'Classify Selected' );
                    }
                })
                .catch( function() {
                    $result.css( 'color', 'var(--mga-danger)' ).text( govConfig.i18n && govConfig.i18n.failed ? govConfig.i18n.failed : 'Failed.' );
                    $btn.prop( 'disabled', false ).text( 'Classify Selected' );
                });
        });

        // History modal
        $( document ).on( 'click', '.mga-history-btn', function() {
            var postId = $( this ).data( 'post-id' );
            var $modal = $( '#history_modal' );
            var $content = $( '#history_content' );
            var loadingMsg = ( window.govConfig && govConfig.i18n && govConfig.i18n.loading ) ? govConfig.i18n.loading : 'Loading...';

            $modal.css( 'display', 'flex' );
            $content.html( '<p style="text-align:center;color:var(--mga-muted);">' + loadingMsg + '</p>' );

            var noHistoryMsg = ( window.govConfig && govConfig.i18n && govConfig.i18n.noHistory ) ? govConfig.i18n.noHistory : 'No history found.';
            var failedLoadMsg = ( window.govConfig && govConfig.i18n && govConfig.i18n.failedLoad ) ? govConfig.i18n.failedLoad : 'Failed to load.';
            var nonceVal = ( window.govConfig && govConfig.nonceHistory ) ? govConfig.nonceHistory : '';

            fetch( AJAX_URL + '?action=megagovern_get_history&post_id=' + encodeURIComponent( postId ) + '&_ajax_nonce=' + nonceVal )
                .then( function( r ) { return r.json(); })
                .then( function( d ) {
                    var htmlContent = ( d.success && d.data && d.data.html ) ? d.data.html : '<p style="text-align:center;color:var(--mga-muted);">' + noHistoryMsg + '</p>';
                    $content.html( htmlContent );
                })
                .catch( function() {
                    $content.html( '<p style="text-align:center;color:var(--mga-danger);">' + failedLoadMsg + '</p>' );
                });
        });

        $( document ).on( 'click', '#history_close_btn', function() { $( '#history_modal' ).hide(); });
        $( '#history_modal' ).on( 'click', function( e ) { if ( e.target === this ) $( this ).hide(); });
        $( document ).on( 'keydown', function( e ) { if ( e.key === 'Escape' ) $( '#history_modal' ).hide(); });

        updateGovCount();
    }

    // ═══════════════════════════════════════
    // Scan Results — Review & Apply Modal
    // ═══════════════════════════════════════
    function initScanResults() {
        var $scanBtn = $( '.mga-scan-load-btn' );
        if ( ! $scanBtn.length ) return;

        $scanBtn.on( 'click', function() {
            var $btn = $( this );
            var $container = $( '#mga-scan-results-container' );
            $btn.prop( 'disabled', true ).text( 'Loading...' );
            $container.html( '<p style="text-align:center;padding:20px;color:var(--mga-muted);">Loading results...</p>' );

            megaAjax( 'megagovern_scan_results', {}, SCAN_NONCE || $( this ).data( 'nonce' ) )
                .then( function( response ) {
                    if ( ! response.success || ! response.data || ! response.data.results ) {
                        $container.html( '<p style="text-align:center;padding:20px;color:var(--mga-muted);">No results found.</p>' );
                        $btn.prop( 'disabled', false ).text( 'Refresh' );
                        return;
                    }
                    var results = response.data.results;
                    if ( ! results.length ) {
                        $container.html( '<p style="text-align:center;padding:20px;color:var(--mga-success);">All content declared.</p>' );
                        $btn.prop( 'disabled', false ).text( 'Refresh' );
                        return;
                    }
                    var html = '<table class="mga-table"><thead><tr><th>Title</th><th>Type</th><th>Words</th><th>Action</th></tr></thead><tbody>';
                    results.forEach( function( item ) {
                        var title = ( item.title || '(No title)' ).replace( /"/g, '&quot;' );
                        var postType = item.post_type || 'post';
                        var words = item.word_count || 0;
                        html += '<tr>';
                        html += '<td><strong>' + title + '</strong></td>';
                        html += '<td>' + postType + '</td>';
                        html += '<td>' + words + '</td>';
                        html += '<td><select class="mga-scan-declare-type"><option value="human">Human</option><option value="ai_assisted">AI Assisted</option><option value="ai_generated">AI Generated</option></select></td>';
                        html += '</tr>';
                    });
                    html += '</tbody></table>';
                    html += '<button class="button button-primary mga-scan-apply-all" style="margin-top:12px;">Apply All</button>';
                    $container.html( html );
                    $btn.prop( 'disabled', false ).text( 'Refresh' );
                })
                .catch( function() {
                    $container.html( '<p style="text-align:center;padding:20px;color:var(--mga-danger);">Failed to load results.</p>' );
                    $btn.prop( 'disabled', false ).text( 'Retry' );
                });
        });
    }

    // ═══════════════════════════════════════
    // INIT
    // ═══════════════════════════════════════
    $( document ).ready( function() {
        cleanRefreshUrl();
        initDirectActionButtons();
        initAutoFixButtons();
        initCopyButtons();
        initGovernance();
        initScanResults();
    });

} )( jQuery );