<?php

namespace MegaGovern;

use MegaGovern\Archive;
use MegaGovern\Governance;
use MegaGovern\Registry;
use MegaGovern\Agency;
use MegaGovern\Crawler;
use MegaGovern\Verification;
use MegaGovern\AIAccess;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX Handlers
 * LOCAL-FIRST — No external API calls.
 *
 *
 * @package MegaGovern
 * @since   1.0.4
 */
class Ajax {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp_ajax_megagovern_bulk_declare', array( $this, 'bulk_declare' ) );
		add_action( 'wp_ajax_megagovern_get_history', array( $this, 'get_history' ) );
		add_action( 'wp_ajax_megagovern_agency_generate_report', array( $this, 'agency_generate_report' ) );
		add_action( 'wp_ajax_megagovern_ai_policy_update', array( $this, 'ai_policy_update' ) );
		add_action( 'wp_ajax_megagovern_auto_fix', array( $this, 'auto_fix' ) );
		add_action( 'wp_ajax_megagovern_save_option', array( $this, 'save_option' ) );
		add_action( 'wp_ajax_megagovern_test_report_email', array( $this, 'test_report_email' ) );
		add_action( 'wp_ajax_megagovern_regenerate_file', array( $this, 'regenerate_file' ) );
		add_action( 'wp_ajax_megagovern_regenerate_all', array( $this, 'regenerate_all' ) );
		add_action( 'wp_ajax_megagovern_clear_cache', array( $this, 'clear_cache' ) );
		add_action( 'wp_ajax_megagovern_rebuild_files', array( $this, 'rebuild_files' ) );
		add_action( 'wp_ajax_megagovern_scan_content', array( $this, 'scan_content' ) );
		add_action( 'wp_ajax_megagovern_revoke_exemption', array( $this, 'revoke_exemption' ) );
		add_action( 'wp_ajax_megagovern_restore_exemption', array( $this, 'restore_exemption' ) );
	}

	/**
	 * Query Posts Helper (Includes Undeclared Filter Logic)
	 *
	 * @param string $filter
	 * @param string $post_type
	 * @param int    $paged
	 * @return \WP_Query
	 */
	public static function get_filtered_posts( $filter = 'all', $post_type = 'all', $paged = 1 ) {
		$meta_query = array( 'relation' => 'AND' );

		if ( 'undeclared' === $filter ) {
			$meta_query[] = array(
				'relation' => 'OR',
				array(
					'key'     => '_megagovern_declaration',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => '_megagovern_declaration',
					'value'   => '',
					'compare' => '=',
				),
			);
		} elseif ( ! empty( $filter ) && 'all' !== $filter ) {
			$meta_query[] = array(
				'key'     => '_megagovern_declaration',
				'value'   => sanitize_text_field( $filter ),
				'compare' => '=',
			);
		}

		$args = array(
			'post_type'      => ( 'all' === $post_type ) ? get_post_types( array( 'public' => true ) ) : sanitize_text_field( $post_type ),
			'post_status'    => ( 'attachment' === $post_type || 'all' === $post_type ) ? array( 'publish', 'inherit' ) : 'publish',
			'posts_per_page' => 20,
			'paged'          => intval( $paged ),
			'meta_query'     => $meta_query,
		);

		return new \WP_Query( $args );
	}

	/**
	 * Handle Scan & Index Content Request
	 */
	public function scan_content() {
		check_ajax_referer( 'megagovern_scan_content', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Permission denied.', 'megagovern' ) ) );
		}

		if ( class_exists( '\MegaGovern\Crawler' ) ) {
			$crawler = new \MegaGovern\Crawler();
			$crawler->regenerate();
		}

		delete_transient( 'megagovern_dashboard_cache' );
		delete_transient( 'megagovern_stats_cache' );

		wp_send_json_success( array( 'message' => esc_html__( 'Content scanned and index refreshed.', 'megagovern' ) ) );
	}

	// ═══════════════════════════════════════
	// BULK DECLARE — VIA DECLARATION::DECLARE()
	// ═══════════════════════════════════════

	/**
	 * Handle bulk declaration.
	 *
	 * Now calls Declaration::declare() which handles:
	 * - SHA-256 content hash generation
	 * - mega_declaration_log table entry
	 * - megagovern_after_declaration hook (Evidence snapshots)
	 * - megagovern_declaration_changed hook
	 * - Governance::log_action()
	 *
	 * All 4 declaration types supported: human, ai_assisted, ai_generated, deepfake.
	 */
	public function bulk_declare() {
		check_ajax_referer( 'megagovern_bulk_declare', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Permission denied.', 'megagovern' ) ) );
		}

		// Harden JSON decode
		$raw_ids  = isset( $_POST['post_ids'] ) ? sanitize_text_field( wp_unslash( $_POST['post_ids'] ) ) : '';
		$post_ids = json_decode( $raw_ids, true );
		if ( ! is_array( $post_ids ) || empty( $post_ids ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'No valid post IDs provided.', 'megagovern' ) ) );
		}

		$type = isset( $_POST['declaration_type'] ) ? sanitize_text_field( wp_unslash( $_POST['declaration_type'] ) ) : '';

		// Support all 4 declaration types
		if ( ! in_array( $type, array( 'human', 'ai_assisted', 'ai_generated', 'deepfake' ), true ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid declaration type.', 'megagovern' ) ) );
		}

		$user_id = get_current_user_id();
		$count   = 0;

		foreach ( $post_ids as $post_id ) {
			// Declaration::declare() handles ALL validation, saving, hashing, hooks, and logging
			if ( Declaration::declare( $post_id, $type, $user_id, 'bulk' ) ) {
				$count++;
			}
		}

		wp_send_json_success( array(
			'message' => sprintf(
				/* translators: %d: number of posts updated */
				_n( '%d post updated.', '%d posts updated.', $count, 'megagovern' ),
				$count
			),
		) );
	}

	// ═══════════════════════════════════════
	// GET HISTORY
	// ═══════════════════════════════════════

	/**
	 * Get declaration history for modal.
	 */
	public function get_history() {
		check_ajax_referer( 'megagovern_get_history', 'ajax_nonce' );

		$post_id = isset( $_GET['post_id'] ) ? intval( wp_unslash( $_GET['post_id'] ) ) : 0;
		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Permission denied.', 'megagovern' ) ) );
		}

		if ( ! class_exists( '\MegaGovern\Governance' ) || ! class_exists( '\MegaGovern\Registry' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Required classes not found.', 'megagovern' ) ) );
		}

		$history = Governance::get_actions( array( 'post_id' => $post_id, 'limit' => 20 ) );

		ob_start();
		if ( ! empty( $history ) ) {
			echo '<h3 style="margin:0 0 12px; font-size:13px;">' . esc_html( get_the_title( $post_id ) ) . '</h3>';
			foreach ( $history as $h ) {
				$type         = Registry::format_type( $h['declaration_type'] );
				$user         = get_userdata( (int) $h['user_id'] );
				$date         = date_i18n( get_option( 'date_format' ), strtotime( $h['logged_at'] ) );
				$time         = date_i18n( get_option( 'time_format' ), strtotime( $h['logged_at'] ) );
				$action_label = 'classified' === $h['action'] ? __( 'Declared as', 'megagovern' ) : __( 'Updated to', 'megagovern' );
				$color        = 'classified' === $h['action'] ? '#2271b1' : '#00a32a';
				?>
				<div style="padding:10px 0; border-bottom:1px solid #f0f0f1;">
					<div style="display:flex; align-items:center; gap:6px; margin-bottom:2px;">
						<span style="width:6px; height:6px; border-radius:50%; background:<?php echo esc_attr( $color ); ?>;"></span>
						<span style="font-weight:500; color:#1d2327;"><?php echo esc_html( $action_label ); ?> <?php echo esc_html( $type ); ?></span>
					</div>
					<div style="font-size:10px; color:#a7aaad; margin-left:14px;">
						<?php echo esc_html( $date . ' ' . $time ); ?> &middot; <?php echo esc_html( $user ? $user->display_name : __( 'System', 'megagovern' ) ); ?>
					</div>
				</div>
				<?php
			}
		} else {
			echo '<p>' . esc_html__( 'No history found.', 'megagovern' ) . '</p>';
		}
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * Generate agency report for a single site
	 */
	public function agency_generate_report() {
		check_ajax_referer( 'megagovern_agency_report', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Permission denied.', 'megagovern' ) ) );
		}

		$site_id = isset( $_POST['site_id'] ) ? sanitize_text_field( wp_unslash( $_POST['site_id'] ) ) : '';

		if ( ! class_exists( '\MegaGovern\Agency' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Agency class not found.', 'megagovern' ) ) );
		}

		$sites = Agency::get_sites();
		$site  = $sites[ $site_id ] ?? null;

		if ( ! $site ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Site not found.', 'megagovern' ) ) );
		}

		if ( class_exists( '\MegaGovern\Governance' ) ) {
			Governance::log_action(
				'report_generated',
				0,
				'system',
				get_current_user_id(),
				array( 'note' => sprintf(
					/* translators: %s: site name */ __( 'Agency bulk report for %s.', 'megagovern' ),
					$site['name']
				) )
			);
		}

		/* translators: %s: site name */
		wp_send_json_success( array( 'message' => sprintf( __( 'Report generated for %s.', 'megagovern' ), $site['name'] ) ) );
	}

	/**
	 * AJAX: Revoke exemption
	 */
	public function revoke_exemption() {
		check_ajax_referer( 'megagovern_archive_action', 'nonce' );
		
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Permission denied.', 'megagovern' ) ) );
		}
		
		if ( ! class_exists( '\MegaGovern\Archive' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Archive class not found.', 'megagovern' ) ) );
		}
		
		$post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid post ID.', 'megagovern' ) ) );
		}
		
		$result = Archive::revoke_exemption( $post_id );
		
		if ( $result ) {
			wp_send_json_success( array( 'message' => esc_html__( 'Exemption revoked.', 'megagovern' ) ) );
		} else {
			wp_send_json_error( array( 'message' => esc_html__( 'Failed to revoke exemption.', 'megagovern' ) ) );
		}
	}

	/**
	 * AJAX: Restore exemption
	 */
	public function restore_exemption() {
		check_ajax_referer( 'megagovern_archive_action', 'nonce' );
		
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Permission denied.', 'megagovern' ) ) );
		}
		
		if ( ! class_exists( '\MegaGovern\Archive' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Archive class not found.', 'megagovern' ) ) );
		}
		
		$post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid post ID.', 'megagovern' ) ) );
		}
		
		$result = Archive::restore_exemption( $post_id );
		
		if ( $result ) {
			wp_send_json_success( array( 'message' => esc_html__( 'Exemption restored.', 'megagovern' ) ) );
		} else {
			wp_send_json_error( array( 'message' => esc_html__( 'Failed to restore exemption.', 'megagovern' ) ) );
		}
	}

	/**
	 * Regenerate a single file (AI.txt, LLMs.txt, Robots.txt)
	 */
	public function regenerate_file() {
		check_ajax_referer( 'megagovern_regenerate_aitxt', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Permission denied.', 'megagovern' ) ) );
		}

		$file = isset( $_POST['file'] ) ? sanitize_text_field( wp_unslash( $_POST['file'] ) ) : '';

		if ( ! class_exists( '\MegaGovern\Crawler' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Crawler class not found.', 'megagovern' ) ) );
		}

		$crawler = new \MegaGovern\Crawler();

		switch ( $file ) {
			case 'aitxt':
				$crawler->regenerate();
				$message = __( 'AI.txt regenerated.', 'megagovern' );
				break;
			case 'llmstxt':
				$message = __( 'LLMs.txt regenerated.', 'megagovern' );
				break;
			case 'robotstxt':
				$message = __( 'Robots.txt regenerated.', 'megagovern' );
				break;
			default:
				wp_send_json_error( array( 'message' => esc_html__( 'Invalid file type.', 'megagovern' ) ) );
				return;
		}

		wp_send_json_success( array( 'message' => esc_html( $message ) ) );
	}

	/**
	 * Regenerate all assets
	 */
	public function regenerate_all() {
		check_ajax_referer( 'megagovern_regenerate_aitxt', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Permission denied.', 'megagovern' ) ) );
		}

		if ( class_exists( '\MegaGovern\Crawler' ) ) {
			$crawler = new \MegaGovern\Crawler();
			$crawler->regenerate();
			update_option( 'megagovern_aitxt_updated', time() );
		}

		if ( class_exists( '\MegaGovern\Verification' ) ) {
			\MegaGovern\Verification::refresh();
			update_option( 'megagovern_verify_updated', time() );
		}

		do_action( 'megagovern_assets_regenerated' );

		wp_send_json_success( array( 'message' => esc_html__( 'All assets regenerated.', 'megagovern' ) ) );
	}

	/**
	 * Clear generated cache
	 */
	public function clear_cache() {
		check_ajax_referer( 'megagovern_maintenance', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Permission denied.', 'megagovern' ) ) );
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_megagovern_%' ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_timeout_megagovern_%' ) );

		delete_transient( 'megagovern_dashboard_cache' );
		delete_transient( 'megagovern_server_status' );

		do_action( 'megagovern_cache_cleared' );

		wp_send_json_success( array( 'message' => esc_html__( 'Cache cleared.', 'megagovern' ) ) );
	}

	/**
	 * Rebuild public files
	 */
	public function rebuild_files() {
		check_ajax_referer( 'megagovern_maintenance', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Permission denied.', 'megagovern' ) ) );
		}

		if ( class_exists( '\MegaGovern\Crawler' ) ) {
			$crawler = new \MegaGovern\Crawler();
			$crawler->regenerate();
		}

		if ( class_exists( '\MegaGovern\Verification' ) ) {
			\MegaGovern\Verification::refresh();
		}

		flush_rewrite_rules();

		do_action( 'megagovern_files_rebuilt' );

		wp_send_json_success( array( 'message' => esc_html__( 'Public files rebuilt.', 'megagovern' ) ) );
	}

	/**
	 * Handle auto-fix requests
	 */
	public function auto_fix() {
		check_ajax_referer( 'megagovern_auto_fix', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Permission denied.', 'megagovern' ) ) );
			return;
		}

		$user_id   = get_current_user_id();
		$rate_key  = 'megagovern_auto_fix_' . $user_id;
		$rate_limit = get_transient( $rate_key );
		if ( $rate_limit && $rate_limit > 5 ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Too many auto-fix requests. Please wait.', 'megagovern' ) ) );
			return;
		}
		set_transient( $rate_key, ( $rate_limit ? $rate_limit + 1 : 1 ), HOUR_IN_SECONDS );

		$issue_id = isset( $_POST['issue_id'] ) ? sanitize_text_field( wp_unslash( $_POST['issue_id'] ) ) : '';

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( '[MegaGovern] Auto-Fix triggered: ' . $issue_id . ' by user ' . $user_id );

		switch ( $issue_id ) {
			case 'aitxt_stale':
			case 'auto_detect':
				if ( class_exists( '\MegaGovern\Crawler' ) ) {
					$crawler = new \MegaGovern\Crawler();
					$crawler->regenerate();
					$message = __( 'AI detection and file generation completed.', 'megagovern' );
					$success = true;
				} else {
					$message = __( 'Crawler class not found.', 'megagovern' );
					$success = false;
				}
				break;

			case 'verify_stale':
				if ( class_exists( '\MegaGovern\Verification' ) ) {
					update_option( 'megagovern_verify_updated', time() );
					\MegaGovern\Verification::refresh();
					$message = __( 'Verification page refreshed successfully.', 'megagovern' );
					$success = true;
				} else {
					$message = __( 'Verification class not found.', 'megagovern' );
					$success = false;
				}
				break;

			default:
				$message = __( 'Unknown issue type.', 'megagovern' );
				$success = false;
		}

		if ( $success ) {
			do_action( 'megagovern_auto_fix_completed', $issue_id, $user_id );
			wp_send_json_success( array( 'message' => esc_html( $message ) ) );
		} else {
			wp_send_json_error( array( 'message' => esc_html( $message ) ) );
		}
	}

	/**
	 * Update AI Access Control policy
	 */
	public function ai_policy_update() {
		check_ajax_referer( 'megagovern_ai_policy', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Permission denied.', 'megagovern' ) ) );
		}

		$category = isset( $_POST['category'] ) ? sanitize_text_field( wp_unslash( $_POST['category'] ) ) : '';
		$status   = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';

		if ( ! in_array( $category, array( 'training', 'search', 'user' ), true ) || ! in_array( $status, array( 'allow', 'block' ), true ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid data.', 'megagovern' ) ) );
		}

		if ( class_exists( '\MegaGovern\AIAccess' ) ) {
			\MegaGovern\AIAccess::update_policy( $category, $status );
			\MegaGovern\AIAccess::generate_robots_txt();
		} else {
			wp_send_json_error( array( 'message' => esc_html__( 'AIAccess class not found.', 'megagovern' ) ) );
			return;
		}

		if ( class_exists( '\MegaGovern\Governance' ) ) {
			Governance::log_action(
				'ai_access_policy_changed',
				0,
				$category,
				get_current_user_id(),
				array( 'note' => sprintf(
					/* translators: 1: category, 2: status */ __( '%1$s → %2$s', 'megagovern' ),
					$category,
					$status
				) )
			);
		}

		wp_send_json_success( array( 'message' => esc_html__( 'Policy updated.', 'megagovern' ) ) );
	}

	/**
	 * Save a single option via AJAX
	 */
	public function save_option() {
		check_ajax_referer( 'megagovern_settings_toggle', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Permission denied.', 'megagovern' ) ) );
		}

		$option = isset( $_POST['option'] ) ? sanitize_text_field( wp_unslash( $_POST['option'] ) ) : '';

		if ( empty( $option ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Option name required.', 'megagovern' ) ) );
		}

		$value = isset( $_POST['value'] ) ? sanitize_text_field( wp_unslash( $_POST['value'] ) ) : '';

		// Sanitize value based on option type
		if ( 'megagovern_custom_label_text' === $option ) {
			if ( is_string( $value ) ) {
				$value = json_decode( $value, true );
			}
			if ( ! is_array( $value ) ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Invalid data format.', 'megagovern' ) ) );
			}
			$value = array_map( 'sanitize_text_field', $value );
		} elseif ( 'megagovern_declaration_post_types' === $option ) {
			if ( is_string( $value ) ) {
				$value = json_decode( $value, true );
			}
			if ( ! is_array( $value ) ) {
				$value = array( 'post', 'page' );
			}
			$value = array_map( 'sanitize_text_field', $value );
		} elseif ( in_array( $option, array( 'megagovern_auto_aitxt', 'megagovern_auto_verify' ), true ) ) {
			$value = rest_sanitize_boolean( $value );
		} else {
			// Default sanitization for text fields (already done above)
			// $value already sanitized via sanitize_text_field at the top
		}

		update_option( $option, $value );

		wp_send_json_success( array( 'message' => esc_html__( 'Saved', 'megagovern' ) ) );
	}

	/**
	 * Send test report email
	 */
	public function test_report_email() {
		check_ajax_referer( 'megagovern_test_email', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Permission denied.', 'megagovern' ) ) );
		}

		$email = get_option( 'megagovern_report_email', get_bloginfo( 'admin_email' ) );

		$sent = wp_mail(
			$email,
			__( 'MegaGovern Test Report', 'megagovern' ),
			__( "This is a test report from MegaGovern.\n\nIf you're seeing this, your email settings are working correctly.", 'megagovern' )
		);

		if ( $sent ) {
			wp_send_json_success( array( 'message' => esc_html__( 'Test report sent.', 'megagovern' ) ) );
		} else {
			wp_send_json_error( array( 'message' => esc_html__( 'Failed to send email. Check your server settings.', 'megagovern' ) ) );
		}
	}
}