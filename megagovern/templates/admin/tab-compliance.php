<?php
/**
 * Governance Center — Tab: Compliance Hub
 *
 * @package MegaGovern
 * @since   1.0.4
 * @var array $args
 */
if ( ! defined( 'WPINC' ) ) {
    die;
}
use MegaGovern\Helpers;
use MegaGovern\Score;
// ─── Variable Declaration with Proper Prefix ──────────────────────
$megagovern_alerts       = isset( $args['alerts'] ) ? (array) $args['alerts'] : array();
$megagovern_read         = isset( $args['read'] ) ? (array) $args['read'] : array();
$megagovern_unread_count = isset( $args['unread_count'] ) ? (int) $args['unread_count'] : 0;
$megagovern_is_free      = isset( $args['is_free'] ) ? (bool) $args['is_free'] : true;
$megagovern_upgrade_url  = isset( $args['upgrade_url'] ) ? esc_url( $args['upgrade_url'] ) : '';
$megagovern_ajax_nonce   = isset( $args['ajax_nonce'] ) ? sanitize_text_field( wp_unslash( $args['ajax_nonce'] ) ) : '';
$megagovern_has_pro      = ( isset( $args['has_pro'] ) && $args['has_pro'] ) || ( isset( $args['is_agency'] ) && $args['is_agency'] );
$megagovern_alert_count   = count( $megagovern_alerts );
$megagovern_latest_alerts = array_slice( $megagovern_alerts, 0, 5 );
$megagovern_eu_coverage   = Score::eu_act_coverage();
/**
 * Get a governance tab URL.
 *
 * @param string $gtab  Tab identifier.
 * @param array  $extra Extra query args.
 * @return string Escaped URL.
 */
function megagovern_gov_url( string $gtab, array $extra = array() ): string {
    return esc_url( add_query_arg(
        array_merge(
            array( 'page' => 'megagovern-governance', 'gtab' => $gtab ),
            $extra
        ),
        admin_url( 'admin.php' )
    ) );
}
?>
<div class="mga-gov-cols">
    <!-- LEFT COLUMN: Regulatory Intelligence -->
    <div class="mga-gov-col">
        <div class="mga-card">
            <div class="mga-card-header">
                <h3 class="mga-card-title">
                    <span class="dashicons dashicons-megaphone"></span>
                    <?php esc_html_e( 'Regulatory Intelligence', 'megagovern' ); ?>
                </h3>
                <span class="mga-pill mga-pill--ok"><?php esc_html_e( 'Live', 'megagovern' ); ?></span>
            </div>
            <div class="mga-gov-stats-mini">
                <div>
                    <div class="stat-label"><?php esc_html_e( 'Total Directives', 'megagovern' ); ?></div>
                    <div class="stat-val"><?php echo esc_html( (string) $megagovern_alert_count ); ?></div>
                </div>
                <div>
                    <div class="stat-label"><?php esc_html_e( 'Jurisdictions', 'megagovern' ); ?></div>
                    <div class="stat-val">3</div>
                </div>
                <div>
                    <div class="stat-label"><?php esc_html_e( 'Source Tier', 'megagovern' ); ?></div>
                    <div class="stat-val" style="color: var(--mga-success);"><?php esc_html_e( 'Verified', 'megagovern' ); ?></div>
                </div>
                <?php if ( $megagovern_unread_count > 0 ) : ?>
                    <div>
                        <div class="stat-label"><?php esc_html_e( 'Unread', 'megagovern' ); ?></div>
                        <div class="stat-val" style="color: #3b82f6;" id="mga-unread-count-sidebar"><?php echo esc_html( (string) $megagovern_unread_count ); ?></div>
                    </div>
                <?php endif; ?>
            </div>
            <!-- Latest Directives -->
            <?php if ( ! empty( $megagovern_latest_alerts ) ) : ?>
                <div class="mga-alerts-feed">
                    <div class="mga-alerts-feed-title">
                        <?php esc_html_e( 'Latest Directives', 'megagovern' ); ?>
                    </div>
                    <?php foreach ( $megagovern_latest_alerts as $megagovern_alert ) :
                        $megagovern_aid          = \MegaGovern\Alerts::get_alert_uid( $megagovern_alert );
                        $megagovern_is_read      = in_array( $megagovern_aid, $megagovern_read, true );
                        $megagovern_jurisdiction = isset( $megagovern_alert['jurisdiction'] ) ? $megagovern_alert['jurisdiction'] : 'global';
                        $megagovern_flag         = Helpers::jurisdiction_flag( $megagovern_jurisdiction );
                        $megagovern_source_url   = isset( $megagovern_alert['source_url'] ) ? $megagovern_alert['source_url'] : ( isset( $megagovern_alert['url'] ) ? $megagovern_alert['url'] : '' );
                        $megagovern_alert_title  = isset( $megagovern_alert['title'] ) ? $megagovern_alert['title'] : '';
                    ?>
                        <div class="mga-alert-row <?php echo ! $megagovern_is_read ? 'mga-alert-row--unread' : ''; ?>" data-alert-id="<?php echo esc_attr( $megagovern_aid ); ?>">
                            <span class="mga-alert-row__flag"><?php echo esc_html( $megagovern_flag ); ?></span>
                            <a href="<?php echo esc_url( $megagovern_source_url ?: '#' ); ?>"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="mga-alert-row__link <?php echo ! $megagovern_is_read ? 'mga-alert-row__link--unread' : ''; ?>"
                               <?php echo $megagovern_source_url ? '' : 'onclick="return false;"'; ?>>
                                <?php echo esc_html( mb_strimwidth( $megagovern_alert_title, 0, 80, '...' ) ); ?>
                            </a>
                            <?php if ( ! $megagovern_is_read ) : ?>
                                <span class="mga-alert-row__dot"></span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <p class="mga-empty" style="padding: 16px;"><?php esc_html_e( 'No regulatory alerts yet.', 'megagovern' ); ?></p>
            <?php endif; ?>
            <?php if ( $megagovern_unread_count > 0 ) : ?>
                <div class="mga-alerts-feed-action">
                    <button type="button" class="button button-small" id="mga-mark-all-read-compliance">
                        <?php esc_html_e( 'Mark All as Read', 'megagovern' ); ?>
                    </button>
                </div>
            <?php endif; ?>
        </div>
        <?php if ( $megagovern_is_free ) : ?>
            <div class="mga-card mga-card--premium" style="margin-top: 0;">
                <h4><?php esc_html_e( 'Full Regulatory Feed', 'megagovern' ); ?></h4>
                <p><?php esc_html_e( 'Unlock live jurisdiction filtering, verified source feeds, and automated alert tracking.', 'megagovern' ); ?></p>
                <?php if ( $megagovern_upgrade_url ) : ?>
                    <a href="<?php echo esc_url( $megagovern_upgrade_url ); ?>" class="mga-btn-upgrade">
                        <?php esc_html_e( 'Upgrade to Pro', 'megagovern' ); ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <!-- RIGHT COLUMN: EU AI Act Coverage -->
    <div class="mga-gov-col">
        <div class="mga-card">
            <div class="mga-card-header">
                <h3 class="mga-card-title">
                    <span class="dashicons dashicons-book-alt"></span>
                    <?php esc_html_e( 'EU AI Act — Article Coverage', 'megagovern' ); ?>
                </h3>
            </div>
            <div class="mga-eu-score">
                <?php if ( is_array( $megagovern_eu_coverage ) ) : ?>
                    <div class="mga-eu-score__num"><?php echo isset( $megagovern_eu_coverage['percentage'] ) ? esc_html( (string) $megagovern_eu_coverage['percentage'] ) : '0'; ?>%</div>
                    <div class="mga-eu-score__label"><?php esc_html_e( 'Overall Coverage Score', 'megagovern' ); ?></div>
                    <div class="mga-eu-score__detail">
                        <?php
                        printf(
                            /* translators: %1$d: covered count, %2$d: total articles */
                            esc_html__( '%1$d of %2$d directives covered', 'megagovern' ),
                            isset( $megagovern_eu_coverage['covered_count'] ) ? (int) $megagovern_eu_coverage['covered_count'] : 0,
                            isset( $megagovern_eu_coverage['total_articles'] ) ? (int) $megagovern_eu_coverage['total_articles'] : 0
                        );
                        ?>
                    </div>
                    <?php if ( ! empty( $megagovern_eu_coverage['missing'] ) && is_array( $megagovern_eu_coverage['missing'] ) ) : ?>
                        <div class="mga-eu-score__action">
                            <?php esc_html_e( '⚠️ Action Item:', 'megagovern' ); ?>
                            <?php echo isset( $megagovern_eu_coverage['missing'][0]['title'] ) ? esc_html( $megagovern_eu_coverage['missing'][0]['title'] ) : esc_html__( 'Review uncovered directives', 'megagovern' ); ?>
                        </div>
                    <?php endif; ?>
                <?php else : ?>
                    <p><?php esc_html_e( 'Coverage data unavailable.', 'megagovern' ); ?></p>
                <?php endif; ?>
            </div>
            <div class="mga-eu-checklist">
                <?php if ( isset( $megagovern_eu_coverage['articles'] ) && is_array( $megagovern_eu_coverage['articles'] ) ) : ?>
                    <?php foreach ( $megagovern_eu_coverage['articles'] as $megagovern_art ) : ?>
                        <div class="mga-eu-item">
                            <span class="mga-eu-item__icon"><?php echo isset( $megagovern_art['covered'] ) && $megagovern_art['covered'] ? '✅' : '⚪'; ?></span>
                            <div class="mga-eu-item__content">
                                <div class="mga-eu-item__article"><?php echo isset( $megagovern_art['article'] ) ? esc_html( $megagovern_art['article'] ) : ''; ?></div>
                                <div class="mga-eu-item__title"><?php echo isset( $megagovern_art['title'] ) ? esc_html( $megagovern_art['title'] ) : ''; ?></div>
                                <div class="mga-eu-item__service <?php echo isset( $megagovern_art['covered'] ) && $megagovern_art['covered'] ? 'mga-eu-item__service--covered' : ''; ?>">
                                    <?php
                                    if ( isset( $megagovern_art['covered'] ) && $megagovern_art['covered'] ) {
                                        echo '✓ ' . ( isset( $megagovern_art['service'] ) ? esc_html( $megagovern_art['service'] ) : '' );
                                    } else {
                                        echo '— ' . ( isset( $megagovern_art['service'] ) ? esc_html( $megagovern_art['service'] ) : '' );
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <p><?php esc_html_e( 'No articles available.', 'megagovern' ); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script>
(function() {
    var nonce = <?php echo wp_json_encode( $megagovern_ajax_nonce ); ?>;
    var ajaxUrl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
    function updateUnreadUI(count) {
        var sidebar = document.getElementById('mga-unread-count-sidebar');
        if (sidebar) {
            sidebar.textContent = count;
            if (count === 0 && sidebar.parentElement) sidebar.parentElement.style.display = 'none';
        }
        var tabBadge = document.getElementById('mga-tab-unread-count');
        if (tabBadge) {
            tabBadge.textContent = count;
            tabBadge.style.display = count > 0 ? '' : 'none';
        }
    }
    // Mark all as read
    var markAllBtn = document.getElementById('mga-mark-all-read-compliance');
    if (markAllBtn) {
        markAllBtn.addEventListener('click', function() {
            var data = new FormData();
            data.append('action', 'megagovern_mark_all_alerts_read');
            data.append('nonce', nonce);
            fetch(ajaxUrl, { method: 'POST', body: data, credentials: 'same-origin' })
                .then(function(r) { return r.json(); })
                .then(function(result) {
                    if (result.success) { updateUnreadUI(0); location.reload(); }
                })
                .catch(function() {
                    // Silent fail - user can reload manually
                });
        });
    }
    // Mark single alert as read on click
    document.querySelectorAll('.mga-alert-row__link').forEach(function(link) {
        link.addEventListener('click', function() {
            var row = this.closest('.mga-alert-row');
            if (!row) return;
            row.classList.remove('mga-alert-row--unread');
            this.classList.remove('mga-alert-row__link--unread');
            var dot = row.querySelector('.mga-alert-row__dot');
            if (dot) dot.remove();
            var data = new FormData();
            data.append('action', 'megagovern_mark_alert_read');
            data.append('alert_id', row.dataset.alertId);
            data.append('nonce', nonce);
            fetch(ajaxUrl, { method: 'POST', body: data, credentials: 'same-origin' })
                .catch(function() {
                    // Silent fail - UI already updated
                });
        });
    });
})();
</script>