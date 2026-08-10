<?php
/**
 * Plugin Name: MegaGovern Lite — AI Transparency Toolkit
 * Plugin URI: https://megagovern.com/
 * Description: AI transparency, disclosure labels, AI documentation, AI.txt generation, verification pages, and audit logs. Local-first.
 * Version: 1.0.4
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Meganova Agency
 * Author URI: https://meganova.agency/
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: megagovern
 * Domain Path: /languages
 *
 * @package MegaGovern
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MEGAGOVERN_VERSION', '1.0.4' );
define( 'MEGAGOVERN_PATH', plugin_dir_path( __FILE__ ) );
define( 'MEGAGOVERN_URL', plugin_dir_url( __FILE__ ) );
define( 'MEGAGOVERN_BASENAME', plugin_basename( __FILE__ ) );
define( 'MEGAGOVERN_FILE', __FILE__ );

if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
	add_action(
		'admin_notices',
		function() {
			echo '<div class="notice notice-error"><p>';
			echo '<strong>' . esc_html__( 'MegaGovern Lite', 'megagovern' ) . '</strong> — ';
			echo esc_html__( 'requires PHP 7.4 or higher.', 'megagovern' );
			echo '</p></div>';
		}
	);

	return;
}

require_once MEGAGOVERN_PATH . 'includes/Core.php';

if ( class_exists( '\\MegaGovern\\Core' ) ) {
	\MegaGovern\Core::boot();

	register_activation_hook(
		__FILE__,
		array( '\\MegaGovern\\Core', 'activate' )
	);

	register_deactivation_hook(
		__FILE__,
		array( '\\MegaGovern\\Core', 'deactivate' )
	);
}