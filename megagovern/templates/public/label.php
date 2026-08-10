<?php
/**
 * Frontend Disclosure Label — V1.0.4
 * @package MegaGovern
 * @since   1.0.8.2
 * @var string $type human|ai_assisted|ai_generated|deepfake
 * @var array $args
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
use MegaGovern\Labels;
use MegaGovern\Helpers;
$megagovern_valid = [ 'human', 'ai_assisted', 'ai_generated', 'deepfake' ];
if ( ! in_array( $type, $megagovern_valid, true ) ) {
    return;
}
$megagovern_label = Helpers::declaration_type( $type );
if ( ! $megagovern_label ) {
    return;
}
$megagovern_style        = $args['style'] ?? get_option( 'megagovern_label_style', 'eu-icon' );
$megagovern_custom_text  = get_option( 'megagovern_custom_label_text', [] );
$megagovern_custom_label = $megagovern_custom_text[ $type ] ?? '';
$megagovern_is_pro       = class_exists( '\MegaGovern\License' ) && ( \MegaGovern\License::is_pro() || \MegaGovern\License::is_agency() );
// Respect image/content icon color setting for eu-icon too
$megagovern_icon_style_opt = get_option( 'megagovern_image_label_style', 'white-50' );
$megagovern_icon_color_pref = ( false !== strpos( $megagovern_icon_style_opt, 'white' ) || $megagovern_style === 'eu-icon' ) ? 'black' : 'black'; // Content labels always black for light bg
// For title badge we want black icon for visibility
$megagovern_icon_url    = Labels::get_eu_icon_url( $type, $megagovern_icon_color_pref, '100' );
$megagovern_aria_text   = $megagovern_label['eu_aria'] ?? $megagovern_label['label'] ?? $type;
$megagovern_label_text  = ( $megagovern_is_pro && ! empty( $megagovern_custom_label ) ) ? $megagovern_custom_label : ( $megagovern_label['label'] ?? $type );
$megagovern_label_color = $megagovern_label['color'] ?? '#0a4a70';
$megagovern_label_bg    = $megagovern_label['bg'] ?? '#f0f6fc';
// Safe border colors without color-mix
$megagovern_border_color = $megagovern_label_color . '33'; // 20% opacity hex
$megagovern_border_color_strong = $megagovern_label_color . '4D'; // 30%
$megagovern_classes = 'mga-eu-label mga-eu-label--' . esc_attr( $type ) . ' mga-eu-label--' . esc_attr( $megagovern_style );
?>
<?php if ( 'eu-icon' === $megagovern_style ) : ?>
    <span class="<?php echo esc_attr( $megagovern_classes ); ?>" role="img" aria-label="<?php echo esc_attr( $megagovern_aria_text ); ?>" data-ai-type="<?php echo esc_attr( $type ); ?>" style="display:inline-flex;align-items:center;gap:6px;vertical-align:middle;line-height:1;">
        <img src="<?php echo esc_url( $megagovern_icon_url ); ?>" alt="<?php echo esc_attr( $megagovern_aria_text ); ?>" width="20" height="20" style="width:20px;height:20px;display:inline-block;" loading="eager">
    </span>
<?php elseif ( 'pill' === $megagovern_style ) : ?>
    <span class="<?php echo esc_attr( $megagovern_classes ); ?>" data-ai-type="<?php echo esc_attr( $type ); ?>" style="display:inline-flex;align-items:center;gap:8px;padding:5px 14px;border-radius:100px;font-size:12px;font-weight:600;letter-spacing:0.3px;color:<?php echo esc_attr( $megagovern_label_color ); ?>;background:<?php echo esc_attr( $megagovern_label_bg ); ?>;border:1px solid <?php echo esc_attr( $megagovern_border_color ); ?>;">
        <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:<?php echo esc_attr( $megagovern_label_color ); ?>;flex-shrink:0;"></span>
        <span><?php echo esc_html( $megagovern_label_text ); ?></span>
    </span>
<?php elseif ( 'minimal' === $megagovern_style ) : ?>
    <span class="<?php echo esc_attr( $megagovern_classes ); ?>" data-ai-type="<?php echo esc_attr( $type ); ?>" style="display:inline-flex;align-items:center;gap:6px;padding:2px 0 2px 12px;border-left:3px solid <?php echo esc_attr( $megagovern_label_color ); ?>;font-size:11px;font-weight:500;letter-spacing:0.8px;text-transform:uppercase;color:<?php echo esc_attr( $megagovern_label_color ); ?>;">
        <span style="font-weight:700;opacity:0.7;">AI</span>
        <span style="opacity:0.85;"><?php echo esc_html( $megagovern_label_text ); ?></span>
    </span>
<?php elseif ( 'banner' === $megagovern_style ) : ?>
    <div class="<?php echo esc_attr( $megagovern_classes ); ?>" data-ai-type="<?php echo esc_attr( $type ); ?>" style="display:flex;align-items:center;gap:10px;padding:10px 16px;margin:16px 0;border-radius:6px;font-size:13px;font-weight:500;color:<?php echo esc_attr( $megagovern_label_color ); ?>;background:<?php echo esc_attr( $megagovern_label_bg ); ?>;border:1px solid <?php echo esc_attr( $megagovern_border_color ); ?>;">
        <span style="display:flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:6px;background:<?php echo esc_attr( $megagovern_label_color ); ?>;color:#fff;font-size:12px;font-weight:700;flex-shrink:0;">AI</span>
        <span><?php echo esc_html( $megagovern_label_text ); ?></span>
        <span style="margin-left:auto;font-size:10px;opacity:0.6;font-weight:400;white-space:nowrap;"><?php esc_html_e( 'EU AI Act Art. 50', 'megagovern' ); ?></span>
    </div>
<?php elseif ( 'outline' === $megagovern_style ) : ?>
    <div class="<?php echo esc_attr( $megagovern_classes ); ?>" data-ai-type="<?php echo esc_attr( $type ); ?>" style="display:flex;align-items:center;gap:12px;padding:12px 16px;margin:12px 0;border-radius:8px;font-size:13px;font-weight:500;color:<?php echo esc_attr( $megagovern_label_color ); ?>;background:#fff;border:2px solid <?php echo esc_attr( $megagovern_border_color_strong ); ?>;">
        <span style="display:inline-flex;align-items:center;padding:3px 10px;border-radius:4px;font-size:10px;font-weight:700;letter-spacing:0.5px;text-transform:uppercase;color:#fff;background:<?php echo esc_attr( $megagovern_label_color ); ?>;flex-shrink:0;"><?php echo esc_html( $type ); ?></span>
        <span><?php echo esc_html( $megagovern_label_text ); ?></span>
        <span style="margin-left:auto;font-size:10px;opacity:0.55;font-weight:400;white-space:nowrap;"><?php esc_html_e( 'EU AI Act Art. 50', 'megagovern' ); ?></span>
    </div>
<?php else : ?>
    <!-- Fallback for unknown style -->
    <span class="<?php echo esc_attr( $megagovern_classes ); ?>" data-ai-type="<?php echo esc_attr( $type ); ?>" style="display:inline-flex;align-items:center;gap:6px;padding:4px 8px;border-radius:4px;background:<?php echo esc_attr( $megagovern_label_bg ); ?>;border:1px solid <?php echo esc_attr( $megagovern_border_color ); ?>;font-size:12px;color:<?php echo esc_attr( $megagovern_label_color ); ?>;">
        <img src="<?php echo esc_url( $megagovern_icon_url ); ?>" alt="" width="18" height="18" style="width:18px;height:18px;">
        <?php echo esc_html( $megagovern_label_text ); ?>
    </span>
<?php endif; ?>