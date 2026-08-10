<?php
/**
 * Core Plugin Bootstrapper — V1.0.4
 *
 * @package MegaGovern
 * @since   1.0.4
 */

namespace MegaGovern;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Core {

	public static function boot(): void {
		// WordPress 4.6+ automatically loads translations for .org-hosted plugins.
		// No need to call load_plugin_textdomain() manually.
		add_action( 'plugins_loaded', array( __CLASS__, 'init' ), 20 );
		add_action( 'init', array( __CLASS__, 'register_meta' ) );
	}

	public static function init(): void {
		require_once MEGAGOVERN_PATH . 'includes/Helpers.php';
		require_once MEGAGOVERN_PATH . 'includes/Db.php';
		require_once MEGAGOVERN_PATH . 'includes/License.php';

		$megagovern_usage_file = MEGAGOVERN_PATH . 'includes/Usage.php';
		if ( file_exists( $megagovern_usage_file ) ) {
			require_once $megagovern_usage_file;
		}

		// LAYER 1: Declaration System
		$megagovern_declaration_file = MEGAGOVERN_PATH . 'includes/Declaration.php';
		if ( file_exists( $megagovern_declaration_file ) ) {
			require_once $megagovern_declaration_file;
			if ( class_exists( '\MegaGovern\Declaration' ) ) {
				new \MegaGovern\Declaration();
			}
		}

		$megagovern_declaration_dir = MEGAGOVERN_PATH . 'includes/Declaration/';
		if ( is_dir( $megagovern_declaration_dir ) ) {
			$megagovern_declaration_files = array( 'Manager.php', 'MetaBox.php', 'Bulk.php', 'History.php', 'Media.php', 'ActionReviews.php' );
			foreach ( $megagovern_declaration_files as $megagovern_file ) {
				$megagovern_file_path = $megagovern_declaration_dir . $megagovern_file;
				if ( file_exists( $megagovern_file_path ) ) {
					require_once $megagovern_file_path;
				}
			}
			if ( class_exists( '\MegaGovern\Declaration\MetaBox' ) ) {
				new \MegaGovern\Declaration\MetaBox();
			}
			if ( class_exists( '\MegaGovern\Declaration\Bulk' ) ) {
				new \MegaGovern\Declaration\Bulk();
			}
			if ( class_exists( '\MegaGovern\Declaration\Media' ) ) {
				new \MegaGovern\Declaration\Media();
			}
		}

		// LAYER 2: Core Services
		require_once MEGAGOVERN_PATH . 'includes/Registry.php';
		require_once MEGAGOVERN_PATH . 'includes/Governance.php';
		new Governance();

		// LAYER 3: Disclosure Services
		require_once MEGAGOVERN_PATH . 'includes/Crawler.php';
		new Crawler();

		require_once MEGAGOVERN_PATH . 'includes/Labels.php';
		new Labels();

		require_once MEGAGOVERN_PATH . 'includes/Verification.php';
		new Verification();

		require_once MEGAGOVERN_PATH . 'includes/Policy.php';
		new Policy();

		$megagovern_media_file = MEGAGOVERN_PATH . 'includes/Media.php';
		if ( file_exists( $megagovern_media_file ) ) {
			require_once $megagovern_media_file;
			if ( class_exists( '\MegaGovern\Media' ) ) {
				new \MegaGovern\Media();
			}
		}

		// LAYER 4: Intelligence Services
		require_once MEGAGOVERN_PATH . 'includes/Issues.php';
		require_once MEGAGOVERN_PATH . 'includes/Score.php';
		require_once MEGAGOVERN_PATH . 'includes/Alerts.php';
		new Alerts();
		require_once MEGAGOVERN_PATH . 'includes/Reports.php';
		new Reports();
		require_once MEGAGOVERN_PATH . 'includes/Evidence.php';
		new Evidence();
		require_once MEGAGOVERN_PATH . 'includes/Ajax.php';
		new Ajax();

		$megagovern_scanner_file = MEGAGOVERN_PATH . 'includes/Scanner.php';
		if ( file_exists( $megagovern_scanner_file ) ) {
			require_once $megagovern_scanner_file;
			if ( class_exists( '\MegaGovern\Scanner' ) ) {
				new \MegaGovern\Scanner();
			}
		}

		require_once MEGAGOVERN_PATH . 'includes/Agency.php';

		$megagovern_cron_file = MEGAGOVERN_PATH . 'includes/Cron.php';
		if ( file_exists( $megagovern_cron_file ) ) {
			require_once $megagovern_cron_file;
			if ( class_exists( '\MegaGovern\Cron' ) ) {
				new \MegaGovern\Cron();
			}
		}

		if ( is_admin() ) {
			require_once MEGAGOVERN_PATH . 'includes/Admin.php';
			new Admin();
		}

		$megagovern_badge_file = MEGAGOVERN_PATH . 'includes/TransparencyBadge.php';
		if ( file_exists( $megagovern_badge_file ) ) {
			require_once $megagovern_badge_file;
			new TransparencyBadge();
		}

		// LAYER 5: Archive Services
		$megagovern_archive_file = MEGAGOVERN_PATH . 'includes/Archive.php';
		if ( file_exists( $megagovern_archive_file ) ) {
			require_once $megagovern_archive_file;
		}

		// LAYER 6: Cron Hooks
		add_action( 'megagovern_daily_report_check', array( '\MegaGovern\Reports', 'send_scheduled_report' ) );
		add_action( 'megagovern_scheduled_report', array( '\MegaGovern\Reports', 'send_scheduled_report' ) );

		$megagovern_flushed_version = get_option( 'megagovern_flush_version', '0' );
		if ( version_compare( $megagovern_flushed_version, MEGAGOVERN_VERSION, '<' ) ) {
			add_action( 'init', function() {
				if ( is_admin() ) {
					flush_rewrite_rules();
				}
			}, 99 );
			update_option( 'megagovern_flush_version', MEGAGOVERN_VERSION );
		}

		do_action( 'megagovern_loaded' );
	}

	public static function register_meta(): void {
		$megagovern_meta_keys = array(
			'_megagovern_declaration' => array(
				'type'          => 'string',
				'default'       => '',
				'single'        => true,
				'show_in_rest'  => true,
				'auth_callback' => function() { return current_user_can( 'edit_posts' ); },
			),
			'_megagovern_declared_by' => array(
				'type'          => 'integer',
				'default'       => 0,
				'single'        => true,
				'show_in_rest'  => true,
				'auth_callback' => function() { return current_user_can( 'edit_posts' ); },
			),
			'_megagovern_declared_at' => array(
				'type'          => 'string',
				'default'       => '',
				'single'        => true,
				'show_in_rest'  => true,
				'auth_callback' => function() { return current_user_can( 'edit_posts' ); },
			),
			'_megagovern_stale' => array(
				'type'          => 'boolean',
				'default'       => false,
				'single'        => true,
				'show_in_rest'  => true,
				'auth_callback' => function() { return current_user_can( 'edit_posts' ); },
			),
			'_megagovern_content_hash' => array(
				'type'          => 'string',
				'default'       => '',
				'single'        => true,
				'show_in_rest'  => true,
				'auth_callback' => function() { return current_user_can( 'edit_posts' ); },
			),
			'_megagovern_legacy_exempt' => array(
				'type'          => 'boolean',
				'default'       => false,
				'single'        => true,
				'show_in_rest'  => true,
				'auth_callback' => function() { return current_user_can( 'edit_posts' ); },
			),
			'_megagovern_exempt_revoked' => array(
				'type'          => 'string',
				'default'       => '',
				'single'        => true,
				'show_in_rest'  => true,
				'auth_callback' => function() { return current_user_can( 'edit_posts' ); },
			),
			'_megagovern_action_dismissed' => array(
				'type'          => 'boolean',
				'default'       => false,
				'single'        => true,
				'show_in_rest'  => true,
				'auth_callback' => function() { return current_user_can( 'edit_posts' ); },
			),
			'_megagovern_action_pending' => array(
				'type'          => 'boolean',
				'default'       => false,
				'single'        => true,
				'show_in_rest'  => true,
				'auth_callback' => function() { return current_user_can( 'edit_posts' ); },
			),
		);
		foreach ( $megagovern_meta_keys as $megagovern_key => $megagovern_args ) {
			register_meta( 'post', $megagovern_key, $megagovern_args );
		}
	}

	public static function activate(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		if ( class_exists( '\MegaGovern\Db' ) ) {
			Db::create_tables();
		}

		$megagovern_defaults = array(
			'megagovern_auto_aitxt'             => true,
			'megagovern_auto_verify'            => true,
			'megagovern_label_position'         => 'top',
			'megagovern_label_style'            => 'eu-icon',
			'megagovern_content_label_enabled'  => true,
			'megagovern_image_label_enabled'    => true,
			'megagovern_image_label_position'   => 'bottom-right',
			'megagovern_image_label_style'      => 'white-50',
			'megagovern_declaration_post_types' => array( 'post', 'page' ),
			'megagovern_edit_threshold'         => 15,
		);

		foreach ( $megagovern_defaults as $megagovern_key => $megagovern_value ) {
			if ( false === get_option( $megagovern_key ) ) {
				update_option( $megagovern_key, $megagovern_value );
			}
		}

		$megagovern_current_style = get_option( 'megagovern_label_style' );
		if ( '1' === $megagovern_current_style || empty( $megagovern_current_style ) ) {
			update_option( 'megagovern_label_style', 'eu-icon' );
		}

		if ( class_exists( '\MegaGovern\Cron' ) && method_exists( '\MegaGovern\Cron', 'schedule' ) ) {
			\MegaGovern\Cron::schedule();
		} else {
			if ( ! wp_next_scheduled( 'megagovern_daily_report_check' ) ) {
				wp_schedule_event( time(), 'daily', 'megagovern_daily_report_check' );
			}
			if ( ! wp_next_scheduled( 'megagovern_hourly_health_check' ) ) {
				wp_schedule_event( time(), 'hourly', 'megagovern_hourly_health_check' );
			}
		}

		if ( ! wp_next_scheduled( 'megagovern_daily_retention_cleanup' ) ) {
			wp_schedule_event( time(), 'daily', 'megagovern_daily_retention_cleanup' );
		}

		if ( false === get_option( 'megagovern_setup_completed' ) ) {
			set_transient( 'megagovern_show_wizard', true, 5 * MINUTE_IN_SECONDS );
		}

		flush_rewrite_rules();
	}

	public static function deactivate(): void {
		if ( ! current_user_can( 'deactivate_plugins' ) ) {
			return;
		}

		wp_clear_scheduled_hook( 'megagovern_daily_report_check' );
		wp_clear_scheduled_hook( 'megagovern_scheduled_report' );
		wp_clear_scheduled_hook( 'megagovern_hourly_health_check' );
		wp_clear_scheduled_hook( 'megagovern_daily_retention_cleanup' );

		delete_transient( 'megagovern_assessment_cache' );
		delete_transient( 'megagovern_dashboard_cache' );
		delete_transient( 'megagovern_health_check_lock' );
		delete_transient( 'megagovern_report_sending_lock' );

		flush_rewrite_rules();
	}
}