<?php
/**
 * Setup Wizard — V1.0.4 LITE - WordPress.org Compliant
 * @package MegaGovern
 * @since 1.0.4
 */
namespace MegaGovern;
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
class SetupWizard {
    private const TOTAL_STEPS = 3;
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'register_wizard_page' ] );
        add_action( 'admin_init', [ $this, 'maybe_redirect_to_wizard' ] );
        add_action( 'admin_post_megagovern_save_wizard', [ $this, 'save_wizard' ] );
        add_action( 'admin_post_megagovern_skip_wizard', [ $this, 'skip_wizard' ] );
    }
    public function register_wizard_page(): void {
        add_submenu_page(
            null,
            __( 'MegaGovern Setup', 'megagovern' ),
            __( 'MegaGovern Setup', 'megagovern' ),
            'manage_options',
            'megagovern-setup',
            [ $this, 'render_wizard' ]
        );
    }
    public function maybe_redirect_to_wizard(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        if ( get_option( 'megagovern_setup_completed' ) ) {
            return;
        }
        if ( ! get_transient( 'megagovern_show_wizard' ) ) {
            return;
        }
        if ( isset( $_GET['activate-multi'] ) ) {
            return;
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $current_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
        if ( 'megagovern-setup' === $current_page ) {
            return;
        }
        if ( wp_doing_ajax() || wp_doing_cron() || defined( 'DOING_AJAX' ) ) {
            return;
        }
        $request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';
        if ( 'POST' === $request_method ) {
            return;
        }
        delete_transient( 'megagovern_show_wizard' );
        wp_safe_redirect( esc_url_raw( admin_url( 'admin.php?page=megagovern-setup' ) ) );
        exit;
    }
    public function skip_wizard(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission.', 'megagovern' ) );
        }
        check_admin_referer( 'megagovern_skip_wizard' );
        update_option( 'megagovern_setup_completed', true );
        update_option( 'megagovern_setup_skipped', true );
        delete_transient( 'megagovern_show_wizard' );
        wp_safe_redirect( esc_url_raw( admin_url( 'admin.php?page=megagovern' ) ) );
        exit;
    }
    private function get_icon( string $name, string $size = '24' ): string {
        $icons = [
            'shield'     => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10z"/></svg>',
            'check'      => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>',
            'arrow-up'   => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/></svg>',
            'arrow-down' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="5 12 12 19 19 12"/></svg>',
            'arrows-h'   => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="12" x2="20" y2="12"/><polyline points="8 8 4 12 8 16"/><polyline points="16 8 20 12 16 16"/></svg>',
            'globe'      => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>',
            'flag'       => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="21" x2="4" y2="3"/><path d="M4 3h13l-3 6 3 6H4z"/></svg>',
            'info'       => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>',
        ];
        return $icons[ $name ] ?? '';
    }
    public function render_wizard(): void {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $current_step = isset( $_GET['step'] ) ? absint( wp_unslash( $_GET['step'] ) ) : 1;
        $total_steps  = self::TOTAL_STEPS;
        ?>
        <div class="wrap megagovern-setup-wizard" style="max-width:720px; margin:40px auto; background:#fff; border:1px solid #c3c4c7; border-radius:4px; overflow:hidden;">
            <div style="text-align:right; padding:12px 20px; background:#fff;">
                <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=megagovern_skip_wizard' ), 'megagovern_skip_wizard' ) ); ?>" style="color:#646970; text-decoration:none; font-size:13px;"><?php esc_html_e( 'Skip setup → Go to Dashboard', 'megagovern' ); ?></a>
            </div>
            <div style="text-align:center; padding:32px 40px 24px; background:#f0f6fc; border-bottom:1px solid #c3c4c7;">
                <span style="color:#2271b1; display:inline-block;"><?php echo wp_kses_post( $this->get_icon( 'shield', '48' ) ); ?></span>
                <h1 style="margin:16px 0 8px; font-size:24px; color:#1d2327;"><?php esc_html_e( 'Welcome to MegaGovern', 'megagovern' ); ?></h1>
                <p style="color:#646970; font-size:14px; margin:0;"><?php esc_html_e( 'AI Transparency & Disclosure Governance Platform', 'megagovern' ); ?></p>
            </div>
            <div style="display:flex; align-items:center; justify-content:center; padding:24px 40px; border-bottom:1px solid #f0f0f1;">
                <?php for ( $i = 1; $i <= $total_steps; $i++ ) : ?>
                    <div style="display:flex; flex-direction:column; align-items:center; gap:8px;">
                        <div style="width:36px; height:36px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:600; font-size:14px; <?php echo $i === $current_step ? 'background:#2271b1; color:#fff; border:2px solid #2271b1;' : ( $i < $current_step ? 'background:#00a32a; color:#fff; border:2px solid #00a32a;' : 'background:#f0f0f1; color:#646970; border:2px solid #c3c4c7;' ); ?>">
                            <?php echo $i < $current_step ? wp_kses_post( $this->get_icon( 'check', '16' ) ) : esc_html( (string) $i ); ?>
                        </div>
                        <span style="font-size:12px; color:<?php echo $i === $current_step ? '#2271b1' : '#646970'; ?>; font-weight:<?php echo $i === $current_step ? '600' : '400'; ?>;">
                            <?php if ( 1 === $i ) esc_html_e( 'Region', 'megagovern' ); elseif ( 2 === $i ) esc_html_e( 'Display', 'megagovern' ); else esc_html_e( 'Ready', 'megagovern' ); ?>
                        </span>
                    </div>
                    <?php if ( $i < $total_steps ) : ?><div style="width:60px; height:2px; background:<?php echo $i < $current_step ? '#00a32a' : '#c3c4c7'; ?>; margin:0 8px 24px;"></div><?php endif; ?>
                <?php endfor; ?>
            </div>
            <div style="padding:40px;">
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <input type="hidden" name="action" value="megagovern_save_wizard">
                    <input type="hidden" name="step" value="<?php echo esc_attr( (string) $current_step ); ?>">
                    <?php wp_nonce_field( 'megagovern_setup_wizard' ); ?>
                    <?php if ( 1 === $current_step ) : ?>
                        <h2 style="margin:0 0 8px; font-size:18px;"><?php esc_html_e( 'Where do you operate?', 'megagovern' ); ?></h2>
                        <p style="color:#646970; margin:0 0 24px;"><?php esc_html_e( 'This helps us show you relevant regulatory alerts.', 'megagovern' ); ?></p>
                        <div style="display:flex; flex-direction:column; gap:12px;">
                            <label style="display:flex; align-items:center; gap:12px; padding:16px; border:2px solid #c3c4c7; border-radius:4px; cursor:pointer;"><input type="radio" name="megagovern_region" value="eu" <?php checked( get_option( 'megagovern_region', 'global' ), 'eu' ); ?>><span style="color:#2271b1;"><?php echo wp_kses_post( $this->get_icon( 'flag', '24' ) ); ?></span><div><strong><?php esc_html_e( 'European Union', 'megagovern' ); ?></strong><br><span style="font-size:12px; color:#646970;"><?php esc_html_e( 'EU AI Act compliance alerts', 'megagovern' ); ?></span></div></label>
                            <label style="display:flex; align-items:center; gap:12px; padding:16px; border:2px solid #c3c4c7; border-radius:4px; cursor:pointer;"><input type="radio" name="megagovern_region" value="us" <?php checked( get_option( 'megagovern_region', 'global' ), 'us' ); ?>><span style="color:#2271b1;"><?php echo wp_kses_post( $this->get_icon( 'flag', '24' ) ); ?></span><div><strong><?php esc_html_e( 'United States', 'megagovern' ); ?></strong><br><span style="font-size:12px; color:#646970;"><?php esc_html_e( 'Colorado AI Act & state law alerts', 'megagovern' ); ?></span></div></label>
                            <label style="display:flex; align-items:center; gap:12px; padding:16px; border:2px solid #c3c4c7; border-radius:4px; cursor:pointer;"><input type="radio" name="megagovern_region" value="global" <?php checked( get_option( 'megagovern_region', 'global' ), 'global' ); ?>><span style="color:#2271b1;"><?php echo wp_kses_post( $this->get_icon( 'globe', '24' ) ); ?></span><div><strong><?php esc_html_e( 'Global', 'megagovern' ); ?></strong><br><span style="font-size:12px; color:#646970;"><?php esc_html_e( 'All regulatory alerts', 'megagovern' ); ?></span></div></label>
                        </div>
                    <?php elseif ( 2 === $current_step ) : ?>
                        <h2 style="margin:0 0 8px; font-size:18px;"><?php esc_html_e( 'How should disclosure labels appear?', 'megagovern' ); ?></h2>
                        <p style="color:#646970; margin:0 0 24px;"><?php esc_html_e( 'Choose where the AI transparency label shows on your content.', 'megagovern' ); ?></p>
                        <div style="display:flex; flex-direction:column; gap:12px;">
                            <label style="display:flex; align-items:center; gap:12px; padding:16px; border:2px solid #c3c4c7; border-radius:4px; cursor:pointer;"><input type="radio" name="megagovern_label_position" value="top" <?php checked( get_option( 'megagovern_label_position', 'top' ), 'top' ); ?>><span style="color:#2271b1;"><?php echo wp_kses_post( $this->get_icon( 'arrow-up', '24' ) ); ?></span><div><strong><?php esc_html_e( 'Top of content', 'megagovern' ); ?></strong><br><span style="font-size:12px; color:#646970;"><?php esc_html_e( 'Label appears before the article', 'megagovern' ); ?></span></div></label>
                            <label style="display:flex; align-items:center; gap:12px; padding:16px; border:2px solid #c3c4c7; border-radius:4px; cursor:pointer;"><input type="radio" name="megagovern_label_position" value="bottom" <?php checked( get_option( 'megagovern_label_position', 'top' ), 'bottom' ); ?>><span style="color:#2271b1;"><?php echo wp_kses_post( $this->get_icon( 'arrow-down', '24' ) ); ?></span><div><strong><?php esc_html_e( 'Bottom of content', 'megagovern' ); ?></strong><br><span style="font-size:12px; color:#646970;"><?php esc_html_e( 'Label appears after the article', 'megagovern' ); ?></span></div></label>
                            <label style="display:flex; align-items:center; gap:12px; padding:16px; border:2px solid #c3c4c7; border-radius:4px; cursor:pointer;"><input type="radio" name="megagovern_label_position" value="both" <?php checked( get_option( 'megagovern_label_position', 'top' ), 'both' ); ?>><span style="color:#2271b1;"><?php echo wp_kses_post( $this->get_icon( 'arrows-h', '24' ) ); ?></span><div><strong><?php esc_html_e( 'Both top and bottom', 'megagovern' ); ?></strong><br><span style="font-size:12px; color:#646970;"><?php esc_html_e( 'Maximum visibility', 'megagovern' ); ?></span></div></label>
                        </div>
                    <?php else : ?>
                        <h2 style="margin:0 0 8px; font-size:18px;"><?php esc_html_e( 'Before you start', 'megagovern' ); ?></h2>
                        <div style="background:#f0f6fc; border:1px solid #c3c4c7; border-radius:4px; padding:24px;">
                            <h3 style="margin:0 0 16px; color:#2271b1;"><?php echo wp_kses_post( $this->get_icon( 'info', '20' ) ); ?> <?php esc_html_e( 'Important — Please Read', 'megagovern' ); ?></h3>
                            <p><strong><?php esc_html_e( 'What MegaGovern Does:', 'megagovern' ); ?></strong></p>
                            <ul style="margin:4px 0 12px 20px;"><li><?php esc_html_e( 'Document how AI is used in your content', 'megagovern' ); ?></li><li><?php esc_html_e( 'Publish transparency labels and AI.txt files', 'megagovern' ); ?></li><li><?php esc_html_e( 'Generate governance records and reports', 'megagovern' ); ?></li><li><?php esc_html_e( 'Alert you to major regulatory developments', 'megagovern' ); ?></li></ul>
                            <p><strong><?php esc_html_e( 'What MegaGovern Does NOT Do:', 'megagovern' ); ?></strong></p>
                            <ul style="margin:4px 0 12px 20px;"><li><?php esc_html_e( 'It does NOT detect AI-generated content', 'megagovern' ); ?></li><li><?php esc_html_e( 'It does NOT guarantee legal compliance', 'megagovern' ); ?></li><li><?php esc_html_e( 'It does NOT replace legal advice from a qualified attorney', 'megagovern' ); ?></li></ul>
                            <label style="display:block; margin-top:16px;"><input type="checkbox" name="megagovern_disclaimer_accepted" value="1" required> <strong><?php esc_html_e( 'I understand that MegaGovern is a documentation tool, not a compliance guarantee or legal advisor.', 'megagovern' ); ?></strong></label>
                        </div>
                    <?php endif; ?>
                    <div style="display:flex; justify-content:space-between; padding:24px 40px; border-top:1px solid #c3c4c7; background:#f0f0f1; margin:40px -40px -40px;">
                        <div><?php if ( $current_step > 1 ) : ?><a href="<?php echo esc_url( add_query_arg( 'step', $current_step - 1, admin_url( 'admin.php?page=megagovern-setup' ) ) ); ?>" class="button"><?php esc_html_e( '← Back', 'megagovern' ); ?></a><?php endif; ?></div>
                        <div><?php if ( $current_step < 3 ) : ?><button type="submit" class="button button-primary"><?php esc_html_e( 'Next Step →', 'megagovern' ); ?></button><?php else : ?><button type="submit" class="button button-primary" name="finish" value="1"><?php esc_html_e( 'Complete Setup', 'megagovern' ); ?></button><?php endif; ?></div>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }
    public function save_wizard(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission.', 'megagovern' ) );
        }
        check_admin_referer( 'megagovern_setup_wizard' );
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $step = isset( $_POST['step'] ) ? absint( wp_unslash( $_POST['step'] ) ) : 1;
        if ( 1 === $step && isset( $_POST['megagovern_region'] ) ) {
            update_option( 'megagovern_region', sanitize_text_field( wp_unslash( $_POST['megagovern_region'] ) ) );
        }
        if ( 2 === $step && isset( $_POST['megagovern_label_position'] ) ) {
            update_option( 'megagovern_label_position', sanitize_text_field( wp_unslash( $_POST['megagovern_label_position'] ) ) );
        }
        if ( isset( $_POST['finish'] ) ) {
            if ( empty( $_POST['megagovern_disclaimer_accepted'] ) ) {
                wp_die( esc_html__( 'You must accept the disclaimer to continue.', 'megagovern' ) );
            }
            update_option( 'megagovern_setup_completed', true );
            update_option( 'megagovern_setup_completed_at', current_time( 'mysql' ) );
            delete_transient( 'megagovern_show_wizard' );
            wp_safe_redirect( esc_url_raw( admin_url( 'admin.php?page=megagovern&welcome=1' ) ) );
            exit;
        }
        $next_step = $step + 1;
        wp_safe_redirect( esc_url_raw( add_query_arg( 'step', $next_step, admin_url( 'admin.php?page=megagovern-setup' ) ) ) );
        exit;
    }
}