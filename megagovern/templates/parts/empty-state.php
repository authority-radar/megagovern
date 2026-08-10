<?php
/**
 * Empty State Template Part
 *
 * @package MegaGovern
 * @since   1.0.4
 */
if ( ! defined( 'WPINC' ) ) {
    die;
}
?>
<div class="megagovern-empty-state" style="text-align:center; padding:48px 24px; max-width:480px; margin:0 auto;">
    <div style="margin-bottom:16px;">
        <span class="dashicons <?php echo esc_attr( $icon ); ?>"
              style="font-size:48px; width:48px; height:48px; color:#a7aaad;">
        </span>
    </div>
    <h3 style="margin:0 0 8px; font-size:16px; color:#1d2327; font-weight:600;">
        <?php echo esc_html( $title ); ?>
    </h3>
    <p style="margin:0 0 24px; color:#646970; font-size:13px; line-height:1.6;">
        <?php echo esc_html( $description ); ?>
    </p>
    <?php if ( ! empty( $action_url ) && ! empty( $action_text ) ) : ?>
        <a href="<?php echo esc_url( $action_url ); ?>" class="button button-primary">
            <?php echo esc_html( $action_text ); ?>
        </a>
    <?php endif; ?>
</div>