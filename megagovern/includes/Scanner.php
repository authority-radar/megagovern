<?php
/**
 * Service: Content Review Queue — V1.0.4
 * 
 * Lists undeclared content for manual review and classification.
 * No external AI — fully local, shows you what needs attention.
 *
 * FREE + PRO + AGENCY — Unlimited for all plans.
 *
 * CHANGELOG v1.0.4:
 * - Replaced str_word_count with UTF-8 safe word counting
 * - Added post status validation during bulk classification
 * - FIX: All declarations via Scanner now get SHA-256 hash, hooks, and log entry
 * - FIXED: WordPress.org compliance - i18n translators comments added, meta_query warning suppressed
 *
 * @package MegaGovern
 * @since   1.0.4
 */

namespace MegaGovern;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Scanner {

	// ─── UNLIMITED FOR ALL PLANS (LOCAL PATTERN MATCHING) ───
	private const LIMIT_FREE   = -1;
	private const LIMIT_PRO    = -1;
	private const LIMIT_AGENCY = -1;

	public function __construct() {
		add_action( 'wp_ajax_megagovern_scan_start', array( $this, 'ajax_scan_start' ) );
		add_action( 'wp_ajax_megagovern_scan_results', array( $this, 'ajax_scan_results' ) );
		add_action( 'wp_ajax_megagovern_scan_apply', array( $this, 'ajax_scan_apply' ) );
		add_action( 'wp_ajax_megagovern_scan_dismiss', array( $this, 'ajax_scan_dismiss' ) );
	}

	// ═══════════════════════════════
	// PUBLIC METHODS
	// ═══════════════════════════════

	public static function is_available(): bool {
		return true;
	}

	public static function count_scannable(): int {
		return class_exists( '\MegaGovern\Registry' ) ? Registry::count_undeclared() : 0;
	}

	public static function get_status(): array {
		$default = array(
			'suggestions'  => array(),
			'applied'      => 0,
			'last_scan'    => '',
			'in_progress'  => false,
			'last_results' => array(),
		);
		return get_option( 'megagovern_scan_status', $default );
	}

	public static function get_results(): array {
		$status = self::get_status();
		return isset( $status['suggestions'] ) ? $status['suggestions'] : array();
	}

	public static function count_pending(): int {
		return count( self::get_results() );
	}

	// ═══════════════════════════════
	// MONTHLY WORD LIMITS
	// ═══════════════════════════════

	public static function get_monthly_limit(): int {
		if ( ! class_exists( '\MegaGovern\License' ) ) {
			return self::LIMIT_FREE;
		}
		if ( method_exists( '\MegaGovern\License', 'is_agency' ) && License::is_agency() ) {
			return self::LIMIT_AGENCY;
		}
		if ( method_exists( '\MegaGovern\License', 'is_pro' ) && License::is_pro() ) {
			return self::LIMIT_PRO;
		}
		return self::LIMIT_FREE;
	}

	public static function get_words_used(): int {
		$key = 'megagovern_scan_words_' . gmdate( 'Y-m' );
		return (int) get_transient( $key );
	}

	public static function get_words_remaining(): int {
		$limit = self::get_monthly_limit();
		if ( $limit === -1 ) {
			return -1;
		}
		return max( 0, $limit - self::get_words_used() );
	}

	private static function record_words( int $count ): void {
		$key     = 'megagovern_scan_words_' . gmdate( 'Y-m' );
		$current = (int) get_transient( $key );
		set_transient( $key, $current + $count, MONTH_IN_SECONDS );
	}

	// ═══════════════════════════════
	// CONTENT REVIEW ENGINE
	// ═══════════════════════════════

	private function get_undeclared_posts(): array {
		$enabled_types = get_option( 'megagovern_declaration_post_types', array( 'post', 'page' ) );

		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		return get_posts( array(
			'post_type'      => $enabled_types,
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'meta_query'     => array(
				'relation' => 'OR',
				array( 'key' => '_megagovern_declaration', 'compare' => 'NOT EXISTS' ),
				array( 'key' => '_megagovern_declaration', 'value' => '', 'compare' => '=' ),
			),
			'orderby'        => 'date',
			'order'          => 'DESC',
		) );
	}

	/**
	 * Get the recommended word limit for a post type.
	 * Used to avoid flagging very short posts.
	 *
	 * @param string $post_type Post type.
	 * @return int Minimum word count to consider for review.
	 */
	private function get_word_limit_for_post_type( string $post_type ): int {
		$limits = array(
			'post'       => 100,
			'page'       => 50,
			'product'    => 50,
			'attachment' => 10,
		);
		return isset( $limits[ $post_type ] ) ? $limits[ $post_type ] : 30;
	}

	private function build_review_list(): array {
		$undeclared = $this->get_undeclared_posts();

		if ( empty( $undeclared ) ) {
			return array();
		}

		$results     = array();
		$total_words = 0;

		foreach ( $undeclared as $post ) {
			$content    = wp_strip_all_tags( $post->post_content );
			// UTF-8 safe word count
			$words      = array_filter( preg_split( '/\s+/u', trim( $content ) ) );
			$word_count = count( $words );
			
			$min_words = $this->get_word_limit_for_post_type( $post->post_type );
			
			// Skip very short posts that don't need review
			if ( $word_count < $min_words ) {
				continue;
			}
			
			$total_words += $word_count;

			$results[] = array(
				'post_id'    => $post->ID,
				'title'      => $post->post_title,
				'post_type'  => $post->post_type,
				'url'        => get_permalink( $post->ID ),
				'word_count' => $word_count,
			);
		}

		self::record_words( $total_words );

		return $results;
	}

	// ═══════════════════════════════
	// APPLY DECLARATIONS — VIA DECLARATION::DECLARE()
	// ═══════════════════════════════

	/**
	 * Apply declarations in bulk using the single save path.
	 *
	 * @param array $declarations Array of ['post_id' => int, 'type' => string].
	 * @return int Number of successfully applied declarations.
	 */
	public function apply_declarations( array $declarations ): int {
		$user_id = get_current_user_id();
		$count   = 0;

		foreach ( $declarations as $item ) {
			$post_id = isset( $item['post_id'] ) ? (int) $item['post_id'] : 0;
			$type    = isset( $item['type'] ) ? sanitize_text_field( $item['type'] ) : '';

			if ( Declaration::declare( $post_id, $type, $user_id, 'scanner' ) ) {
				$count++;
			}
		}

		// Clear suggestions after successful apply
		$status                    = self::get_status();
		$status['applied']        += $count;
		$status['suggestions'] = array();
		update_option( 'megagovern_scan_status', $status, false );

		return $count;
	}

	private function calculate_summary( array $results ): array {
		$total_words = 0;
		$count       = count( $results );

		foreach ( $results as $item ) {
			$total_words += isset( $item['word_count'] ) ? $item['word_count'] : 0;
		}

		return array(
			'total_words' => $total_words,
			'words_used'  => self::get_words_used(),
			'words_limit' => self::get_monthly_limit(),
			'post_count'  => $count,
		);
	}

	// ═══════════════════════════════
	// AJAX HANDLERS
	// ═══════════════════════════════

	public function ajax_scan_start(): void {
		check_ajax_referer( 'megagovern_scan', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'megagovern' ) ) );
		}

		$limit = self::get_monthly_limit();
		if ( $limit !== -1 ) {
			$remaining = self::get_words_remaining();
			if ( $remaining <= 0 ) {
				wp_send_json_error( array( 
					'message' => __( 'Monthly word limit reached.', 'megagovern' ),
					'code'    => 'quota_exceeded',
				) );
				return;
			}
		}

		$status                    = self::get_status();
		$status['in_progress'] = true;
		update_option( 'megagovern_scan_status', $status, false );

		$results = $this->build_review_list();
		$summary = $this->calculate_summary( $results );

		$status['suggestions']  = $results;
		$status['last_scan']    = current_time( 'mysql' );
		$status['in_progress']  = false;
		$status['last_results'] = $summary;
		update_option( 'megagovern_scan_status', $status, false );

		
		wp_send_json_success( array(
			'message' => sprintf(
				/* translators: %d: number of posts ready for review */
				_n( '%d post ready for review.', '%d posts ready for review.', count( $results ), 'megagovern' ),
				count( $results )
			),
			'count'   => count( $results ),
			'summary' => $summary,
		) );
	}

	public function ajax_scan_results(): void {
		check_ajax_referer( 'megagovern_scan', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'megagovern' ) ) );
		}

		$results  = self::get_results();
		$enriched = array();

		foreach ( $results as $item ) {
			$post_id = isset( $item['post_id'] ) ? (int) $item['post_id'] : 0;
			$post    = get_post( $post_id );

			$enriched[] = array(
				'post_id'    => $post_id,
				'title'      => $post ? $post->post_title : __( 'Unknown', 'megagovern' ),
				'post_type'  => $post ? $post->post_type : '',
				'url'        => isset( $item['url'] ) ? $item['url'] : '',
				'word_count' => isset( $item['word_count'] ) ? $item['word_count'] : 0,
			);
		}

		$status = self::get_status();

		wp_send_json_success( array(
			'results'   => $enriched,
			'count'     => count( $enriched ),
			'last_scan' => isset( $status['last_scan'] ) ? $status['last_scan'] : '',
		) );
	}

	public function ajax_scan_apply(): void {
		check_ajax_referer( 'megagovern_scan', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'megagovern' ) ) );
		}

		$raw_declarations = isset( $_POST['declarations'] ) ? sanitize_text_field( wp_unslash( $_POST['declarations'] ) ) : '';
		$declarations     = json_decode( $raw_declarations, true );

		if ( ! is_array( $declarations ) || empty( $declarations ) ) {
			wp_send_json_error( array( 'message' => __( 'No valid declarations provided.', 'megagovern' ) ) );
		}

		$sanitized_declarations = array();
		foreach ( $declarations as $item ) {
			if ( isset( $item['post_id'] ) && isset( $item['type'] ) ) {
				$sanitized_declarations[] = array(
					'post_id' => (int) $item['post_id'],
					'type'    => sanitize_text_field( $item['type'] ),
				);
			}
		}

		if ( empty( $sanitized_declarations ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid declarations provided.', 'megagovern' ) ) );
		}

		$applied = $this->apply_declarations( $sanitized_declarations );

		/* translators: %d: number of declarations applied */
		wp_send_json_success( array(
			'message' => sprintf(
				/* translators: %d: number of declarations applied */
				_n( '%d declaration applied.', '%d declarations applied.', $applied, 'megagovern' ),
				$applied
			),
			'applied' => $applied,
		) );
	}

	public function ajax_scan_dismiss(): void {
		check_ajax_referer( 'megagovern_scan', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'megagovern' ) ) );
		}

		$status                    = self::get_status();
		$status['suggestions'] = array();
		update_option( 'megagovern_scan_status', $status, false );

		wp_send_json_success( array( 
			'message' => __( 'Review results dismissed. Posts remain undeclared for manual review.', 'megagovern' ) 
		) );
	}
}