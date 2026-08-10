<?php
/**
 * Service: AI Transparency Policy Generator (Declare)
 *
 * Auto-generates a professional, legally-structured AI Transparency Policy
 *
 * Free: View only (static template)
 * Pro/Agency: Full customization
 *
 * @package MegaGovern
 * @since   1.0.4
 */
namespace MegaGovern;
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
class Policy {
    public function __construct() {
        add_action( 'init', array( $this, 'register_rewrite' ) );
        add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
        add_action( 'template_redirect', array( $this, 'serve_policy_page' ) );
        add_shortcode( 'megagovern_policy', array( $this, 'policy_shortcode' ) );
    }
    public function register_rewrite() {
        add_rewrite_rule( 'ai-policy/?$', 'index.php?megagovern_policy=1', 'top' );
    }
    public function add_query_vars( $vars ) {
        $vars[] = 'megagovern_policy';
        return $vars;
    }
    public function serve_policy_page() {
        if ( get_query_var( 'megagovern_policy' ) ) {
            status_header( 200 );
            include MEGAGOVERN_PATH . 'templates/public/policy-page.php';
            exit;
        }
    }
    public function policy_shortcode() {
        ob_start();
        $this->render_policy_content();
        return '<div class="megagovern-policy-wrapper">' . ob_get_clean() . '</div>';
    }
    /**
     * Get allowed SVG HTML attributes for wp_kses().
     *
     * @return array
     */
    private static function get_allowed_svg_html(): array {
        return array(
            'svg' => array(
                'xmlns' => true,
                'width' => true,
                'height' => true,
                'viewBox' => true,
                'fill' => true,
                'stroke' => true,
                'stroke-width' => true,
                'stroke-linecap' => true,
                'stroke-linejoin' => true,
                'aria-hidden' => true,
            ),
            'path' => array(
                'd' => true,
                'fill' => true,
                'stroke' => true,
                'stroke-width' => true,
                'stroke-linecap' => true,
                'stroke-linejoin' => true,
            ),
            'circle' => array(
                'cx' => true,
                'cy' => true,
                'r' => true,
                'fill' => true,
                'stroke' => true,
            ),
            'rect' => array(
                'x' => true,
                'y' => true,
                'width' => true,
                'height' => true,
                'rx' => true,
                'ry' => true,
                'fill' => true,
                'stroke' => true,
            ),
            'polyline' => array(
                'points' => true,
                'fill' => true,
                'stroke' => true,
            ),
            'line' => array(
                'x1' => true,
                'y1' => true,
                'x2' => true,
                'y2' => true,
                'stroke' => true,
            ),
            'polygon' => array(
                'points' => true,
                'fill' => true,
                'stroke' => true,
            ),
        );
    }
    /**
     * Check if policy is editable (Pro/Agency only).
     *
     * @return bool
     */
    public static function is_editable(): bool {
        if ( class_exists( '\MegaGovern\License' ) ) {
            return License::is_pro() || License::is_agency();
        }
        return false;
    }
    /**
     * Get SVG icon inline with fallback.
     *
     * @param string $name Icon filename without extension.
     * @param int    $size Width/height in pixels.
     * @return string SVG markup or empty string.
     */
    private static function icon( $name, $size = 24 ) {
        $path = MEGAGOVERN_PATH . 'assets/images/' . $name . '.svg';
        if ( file_exists( $path ) ) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
            $svg = @file_get_contents( $path );
            if ( false !== $svg ) {
                $svg = str_replace( '<svg ', '<svg width="' . esc_attr( (string) $size ) . '" height="' . esc_attr( (string) $size ) . '" ', $svg );
                return $svg;
            }
        }
        return '';
    }
    /**
     * Generate all policy data.
     *
     * @return array Policy data.
     */
    public static function generate_data() {
        $stats     = Registry::get_stats();
        $total     = isset( $stats['total'] ) ? (int) $stats['total'] : 0;
        $human     = isset( $stats['human'] ) ? (int) $stats['human'] : 0;
        $assisted  = isset( $stats['ai_assisted'] ) ? (int) $stats['ai_assisted'] : 0;
        $generated = isset( $stats['ai_generated'] ) ? (int) $stats['ai_generated'] : 0;
        $last      = isset( $stats['last_updated'] ) ? $stats['last_updated'] : current_time( 'mysql' );
        $first     = self::get_first_declaration_date();
        $site_id   = self::get_local_site_id();
        $types     = get_option( 'megagovern_declaration_post_types', array( 'post', 'page' ) );
        $type_names = array();
        foreach ( (array) $types as $pt ) {
            $obj = get_post_type_object( $pt );
            $type_names[] = $obj ? $obj->labels->name : $pt;
        }
        $is_white_label = false;
        if ( class_exists( '\MegaGovern\License' ) && method_exists( '\MegaGovern\License', 'is_white_label' ) ) {
            $is_white_label = License::is_white_label();
        }
        return array(
            'site_name'        => get_bloginfo( 'name' ),
            'site_url'         => home_url(),
            'site_id'          => $site_id,
            'contact_email'    => get_option( 'megagovern_policy_email', get_bloginfo( 'admin_email' ) ),
            'contact_page'     => get_option( 'megagovern_policy_contact_url', home_url( '/contact' ) ),
            'total'            => $total,
            'human'            => $human,
            'human_pct'        => $total > 0 ? round( ( $human / $total ) * 100 ) : 0,
            'ai_assisted'      => $assisted,
            'assisted_pct'     => $total > 0 ? round( ( $assisted / $total ) * 100 ) : 0,
            'ai_generated'     => $generated,
            'generated_pct'    => $total > 0 ? round( ( $generated / $total ) * 100 ) : 0,
            'last_updated'     => $last,
            'first_date'       => $first,
            'registry_since'   => $first ?: get_option( 'megagovern_setup_completed_at', current_time( 'mysql' ) ),
            'verify_url'       => home_url( '/transparency' ),
            'aitxt_url'        => home_url( '/ai.txt' ),
            'custom_intro'     => get_option( 'megagovern_policy_intro', '' ),
            'is_white_label'   => $is_white_label,
            'post_types'       => $type_names,
            'version'          => MEGAGOVERN_VERSION,
            'next_review'      => date_i18n( get_option( 'date_format' ), strtotime( '+90 days' ) ),
        );
    }
    /**
     * Get local site ID — no external API calls.
     *
     * @return string
     */
    private static function get_local_site_id(): string {
        $site_id = get_option( 'megagovern_site_id', '' );
        if ( empty( $site_id ) ) {
            $site_id = 'mg_' . substr( md5( get_home_url() . wp_generate_uuid4() ), 0, 16 );
            update_option( 'megagovern_site_id', $site_id );
        }
        return $site_id;
    }
    /**
     * Get the date of the first declaration.
     *
     * @return string|null First declaration date or null.
     */
    private static function get_first_declaration_date() {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        return $wpdb->get_var(
            "SELECT meta_value FROM {$wpdb->postmeta} 
             WHERE meta_key = '_megagovern_declared_at' 
             ORDER BY meta_value ASC LIMIT 1"
        );
    }
    /**
     * Render the full policy HTML.
     */
    public function render_policy_content() {
        $d = self::generate_data();
        $wl = $d['is_white_label'];
        $allowed_svg = self::get_allowed_svg_html();
        ?>
        <!-- Header -->
        <div class="mg-policy-header">
            <div class="mg-policy-shield"><?php echo wp_kses( self::icon( 'icon-shield', 48 ), $allowed_svg ); ?></div>
            <h1><?php esc_html_e( 'AI Transparency Policy', 'megagovern' ); ?></h1>
            <p class="mg-policy-site"><?php echo esc_html( $d['site_name'] ); ?></p>
            <div class="mg-policy-meta">
                <span>
                    <strong><?php esc_html_e( 'Last Updated:', 'megagovern' ); ?></strong>
                    <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $d['last_updated'] ) ) ); ?>
                </span>
                <span>
                    <strong><?php esc_html_e( 'Registry Since:', 'megagovern' ); ?></strong>
                    <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $d['registry_since'] ) ) ); ?>
                </span>
            </div>
        </div>
        <!-- Stats Cards -->
        <div class="mg-policy-stats">
            <div class="mg-stat-card">
                <div class="mg-stat-number"><?php echo esc_html( (string) $d['human'] ); ?></div>
                <div class="mg-stat-label"><?php echo wp_kses( self::icon( 'icon-human', 20 ), $allowed_svg ); ?> <?php esc_html_e( 'Human Written', 'megagovern' ); ?></div>
                <div class="mg-stat-pct"><?php echo esc_html( (string) $d['human_pct'] ); ?>%</div>
            </div>
            <div class="mg-stat-card">
                <div class="mg-stat-number"><?php echo esc_html( (string) $d['ai_assisted'] ); ?></div>
                <div class="mg-stat-label"><?php echo wp_kses( self::icon( 'icon-ai-assisted', 20 ), $allowed_svg ); ?> <?php esc_html_e( 'AI Assisted', 'megagovern' ); ?></div>
                <div class="mg-stat-pct"><?php echo esc_html( (string) $d['assisted_pct'] ); ?>%</div>
            </div>
            <div class="mg-stat-card">
                <div class="mg-stat-number"><?php echo esc_html( (string) $d['ai_generated'] ); ?></div>
                <div class="mg-stat-label"><?php echo wp_kses( self::icon( 'icon-ai-generated', 20 ), $allowed_svg ); ?> <?php esc_html_e( 'AI Generated', 'megagovern' ); ?></div>
                <div class="mg-stat-pct"><?php echo esc_html( (string) $d['generated_pct'] ); ?>%</div>
            </div>
        </div>
        <!-- Our Commitment -->
        <div class="mg-policy-section">
            <h2><?php esc_html_e( 'Our Commitment', 'megagovern' ); ?></h2>
            <p>
                <?php
                /* translators: %s: site name */
                printf( esc_html__( '%s is committed to full transparency about how artificial intelligence is used in our content creation process. This policy reflects our declared practices, maintained through a verifiable Transparency Registry, and is aligned with EU AI Act Article 50 transparency obligations.', 'megagovern' ), esc_html( $d['site_name'] ) );
                ?>
            </p>
        </div>
        <!-- 1. Classification -->
        <div class="mg-policy-section">
            <h2>1. <?php esc_html_e( 'How We Classify Content', 'megagovern' ); ?></h2>
            <table class="mg-policy-table">
                <thead>
                    <tr><th><?php esc_html_e( 'Classification', 'megagovern' ); ?></th><th><?php esc_html_e( 'Definition', 'megagovern' ); ?></th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo wp_kses( self::icon( 'icon-human', 18 ), $allowed_svg ); ?> <?php esc_html_e( 'Human Written', 'megagovern' ); ?></td>
                        <td><?php esc_html_e( 'Created entirely by humans. No AI involvement in drafting or editing.', 'megagovern' ); ?></td>
                    </tr>
                    <tr>
                        <td><?php echo wp_kses( self::icon( 'icon-ai-assisted', 18 ), $allowed_svg ); ?> <?php esc_html_e( 'AI Assisted', 'megagovern' ); ?></td>
                        <td><?php esc_html_e( 'Human-led creation supported by AI tools. All content undergoes human editorial review before publication.', 'megagovern' ); ?></td>
                    </tr>
                    <tr>
                        <td><?php echo wp_kses( self::icon( 'icon-ai-generated', 18 ), $allowed_svg ); ?> <?php esc_html_e( 'AI Generated', 'megagovern' ); ?></td>
                        <td><?php esc_html_e( 'Primarily produced by AI systems with human oversight and final approval.', 'megagovern' ); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <!-- 2. Disclosure Practice -->
        <div class="mg-policy-section">
            <h2>2. <?php esc_html_e( 'Our Disclosure Practice', 'megagovern' ); ?></h2>
            <ul>
                <li><?php esc_html_e( 'Every published piece of content carries a visible disclosure label.', 'megagovern' ); ?></li>
                <li><?php esc_html_e( 'Labels appear at the point of interaction — not buried in terms or footnotes.', 'megagovern' ); ?></li>
                <li><?php esc_html_e( 'All declarations are timestamped and recorded in our Transparency Registry.', 'megagovern' ); ?></li>
                <?php if ( ! $wl ) : ?>
                <li><?php esc_html_e( 'The Registry is maintained by a neutral third party (MegaGovern) and cannot be altered retroactively.', 'megagovern' ); ?></li>
                <?php endif; ?>
                <li>
                    <?php
                    /* translators: %s: formatted date of first AI use */
                    printf( esc_html__( 'AI use on this site began on: %s', 'megagovern' ), esc_html( date_i18n( get_option( 'date_format' ), strtotime( $d['first_date'] ) ) ) );
                    ?>
                </li>
            </ul>
        </div>
        <!-- 3. Editorial Responsibility -->
        <div class="mg-policy-section">
            <h2>3. <?php esc_html_e( 'Editorial Responsibility', 'megagovern' ); ?></h2>
            <p>
                <?php
                /* translators: %s: site name */
                printf( esc_html__( 'All AI Assisted and AI Generated content published on %s undergoes human editorial review prior to publication. No AI-generated content is published without human approval.', 'megagovern' ), esc_html( $d['site_name'] ) );
                ?>
            </p>
            <p><?php esc_html_e( 'This editorial review process supports the human-review exemption under EU AI Act Article 50(4).', 'megagovern' ); ?></p>
        </div>
        <!-- 4. Current Statistics -->
        <div class="mg-policy-section">
            <h2>4. <?php esc_html_e( 'Current Statistics', 'megagovern' ); ?></h2>
            <p class="mg-policy-live"><?php esc_html_e( 'Live data from Transparency Registry — updated automatically', 'megagovern' ); ?></p>
            <table class="mg-policy-table">
                <tbody>
                    <tr><td><?php esc_html_e( 'Total Declared Posts', 'megagovern' ); ?></td><td><strong><?php echo esc_html( (string) $d['total'] ); ?></strong></td></tr>
                    <tr><td><?php echo wp_kses( self::icon( 'icon-human', 16 ), $allowed_svg ); ?> <?php esc_html_e( 'Human Written', 'megagovern' ); ?></td><td><?php echo esc_html( (string) $d['human'] ); ?> (<?php echo esc_html( (string) $d['human_pct'] ); ?>%)</td></tr>
                    <tr><td><?php echo wp_kses( self::icon( 'icon-ai-assisted', 16 ), $allowed_svg ); ?> <?php esc_html_e( 'AI Assisted', 'megagovern' ); ?></td><td><?php echo esc_html( (string) $d['ai_assisted'] ); ?> (<?php echo esc_html( (string) $d['assisted_pct'] ); ?>%)</td></tr>
                    <tr><td><?php echo wp_kses( self::icon( 'icon-ai-generated', 16 ), $allowed_svg ); ?> <?php esc_html_e( 'AI Generated', 'megagovern' ); ?></td><td><?php echo esc_html( (string) $d['ai_generated'] ); ?> (<?php echo esc_html( (string) $d['generated_pct'] ); ?>%)</td></tr>
                    <tr><td><?php esc_html_e( 'Registry Active Since', 'megagovern' ); ?></td><td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $d['registry_since'] ) ) ); ?></td></tr>
                    <tr><td><?php esc_html_e( 'Last Declaration', 'megagovern' ); ?></td><td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $d['last_updated'] ) ) ); ?></td></tr>
                </tbody>
            </table>
        </div>
        <!-- 5. Scope -->
        <div class="mg-policy-section">
            <h2>5. <?php esc_html_e( 'Scope', 'megagovern' ); ?></h2>
            <p>
                <?php
                /* translators: %s: site URL */
                printf( esc_html__( 'This policy applies to all published content on %s including:', 'megagovern' ), esc_html( $d['site_url'] ) );
                ?>
            </p>
            <ul>
                <?php foreach ( (array) $d['post_types'] as $pt ) : ?>
                    <li><?php echo esc_html( $pt ); ?></li>
                <?php endforeach; ?>
            </ul>
            <p><?php esc_html_e( 'It does not apply to user-generated comments or third-party embedded content.', 'megagovern' ); ?></p>
        </div>
        <!-- 6. Verification -->
        <div class="mg-policy-section">
            <h2>6. <?php esc_html_e( 'Verification', 'megagovern' ); ?></h2>
            <p><?php esc_html_e( 'Our transparency declarations are publicly verifiable:', 'megagovern' ); ?></p>
            <div class="mg-verify-cards">
                <a href="<?php echo esc_url( $d['verify_url'] ); ?>" class="mg-verify-card">
                    <span class="mg-verify-icon"><?php echo wp_kses( self::icon( 'icon-verify', 24 ), $allowed_svg ); ?></span>
                    <strong><?php esc_html_e( 'Public Verification Page', 'megagovern' ); ?></strong>
                    <span><?php echo esc_html( $d['verify_url'] ); ?></span>
                </a>
                <a href="<?php echo esc_url( $d['aitxt_url'] ); ?>" class="mg-verify-card">
                    <span class="mg-verify-icon"><?php echo wp_kses( self::icon( 'icon-file', 24 ), $allowed_svg ); ?></span>
                    <strong><?php esc_html_e( 'Machine-Readable File', 'megagovern' ); ?></strong>
                    <span><?php echo esc_html( $d['aitxt_url'] ); ?></span>
                </a>
                <div class="mg-verify-card">
                    <span class="mg-verify-icon"><?php echo wp_kses( self::icon( 'icon-standard', 24 ), $allowed_svg ); ?></span>
                    <strong><?php esc_html_e( 'Governance Standard', 'megagovern' ); ?></strong>
                    <span><?php esc_html_e( 'EU AI Act Article 50', 'megagovern' ); ?></span>
                </div>
                <?php if ( ! $wl ) : ?>
                <div class="mg-verify-card">
                    <span class="mg-verify-icon"><?php echo wp_kses( self::icon( 'icon-registry', 24 ), $allowed_svg ); ?></span>
                    <strong><?php esc_html_e( 'Registry Provider', 'megagovern' ); ?></strong>
                    <span>MegaGovern</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <!-- 7. Contact -->
        <div class="mg-policy-section">
            <h2>7. <?php esc_html_e( 'Contact', 'megagovern' ); ?></h2>
            <p><?php esc_html_e( 'For questions about this policy or our AI content practices:', 'megagovern' ); ?></p>
            <p>
                <strong><?php echo esc_html( $d['site_name'] ); ?></strong><br>
                <?php esc_html_e( '📧', 'megagovern' ); ?> <a href="mailto:<?php echo esc_attr( $d['contact_email'] ); ?>"><?php echo esc_html( $d['contact_email'] ); ?></a><br>
                <?php esc_html_e( '🌐', 'megagovern' ); ?> <a href="<?php echo esc_url( $d['contact_page'] ); ?>"><?php echo esc_html( $d['contact_page'] ); ?></a>
            </p>
        </div>
        <!-- 8. Updates -->
        <div class="mg-policy-section">
            <h2>8. <?php esc_html_e( 'Updates', 'megagovern' ); ?></h2>
            <p><?php esc_html_e( 'This policy updates automatically whenever new content declarations are made in our Transparency Registry.', 'megagovern' ); ?></p>
            <table class="mg-policy-table">
                <tbody>
                    <tr><td><?php esc_html_e( 'Policy Version', 'megagovern' ); ?></td><td><?php echo esc_html( $d['version'] ); ?></td></tr>
                    <tr><td><?php esc_html_e( 'Last Updated', 'megagovern' ); ?></td><td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $d['last_updated'] ) ) ); ?></td></tr>
                    <tr><td><?php esc_html_e( 'Next Scheduled Review', 'megagovern' ); ?></td><td><?php echo esc_html( $d['next_review'] ); ?></td></tr>
                </tbody>
            </table>
        </div>
        <!-- Disclaimer -->
        <div class="mg-policy-disclaimer">
            <h3><?php echo wp_kses( self::icon( 'icon-warning', 20 ), $allowed_svg ); ?> <?php esc_html_e( 'Disclaimer', 'megagovern' ); ?></h3>
            <p><?php esc_html_e( 'This policy was automatically generated based on content source declarations made by the site owner. It reflects declared practices only.', 'megagovern' ); ?></p>
            <p><?php esc_html_e( 'It does not constitute legal advice and does not guarantee regulatory compliance with any law or regulation. The site owner is solely responsible for the accuracy of declarations and for ensuring their website meets applicable legal obligations.', 'megagovern' ); ?></p>
            <p><?php esc_html_e( 'For legal obligations specific to your jurisdiction, consult a qualified attorney.', 'megagovern' ); ?></p>
        </div>
        <!-- Footer -->
        <?php if ( ! $wl ) : ?>
        <div class="mg-policy-footer">
            <p><?php echo wp_kses( self::icon( 'icon-shield', 18 ), $allowed_svg ); ?> <?php esc_html_e( 'This policy is powered by', 'megagovern' ); ?> <strong>MegaGovern</strong> — <?php esc_html_e( 'AI Content Governance & Transparency Platform', 'megagovern' ); ?></p>
            <p style="font-size:11px;"><?php esc_html_e( 'Verified Registry ID:', 'megagovern' ); ?> <?php echo esc_html( $d['site_id'] ); ?> | <a href="<?php echo esc_url( $d['verify_url'] ); ?>"><?php echo esc_html( $d['verify_url'] ); ?></a></p>
        </div>
        <?php endif; ?>
        <?php
    }
}