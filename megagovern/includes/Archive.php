<?php
/**
 * Service: Legacy Archive — Logic Layer
 *
 * Handles legal cutoff dates, grandfathering exemptions under Article 50,
 * edit distance/threshold triggers, and performant post querying.
 *
 * @package MegaGovern
 * @since   1.0.5
 */

namespace MegaGovern;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Archive {
	/**
	 * Official EU AI Act Enforcement & Legacy Cutoff Date
	 * (Article 50 transparency obligations kick in August 2, 2026)
	 */
	const DEFAULT_CUTOFF = '2026-08-02';

	/**
	 * Get the current cutoff date
	 *
	 * @return string ISO format YYYY-MM-DD
	 */
	public static function get_cutoff_date(): string {
		return get_option( 'megagovern_legacy_cutoff_date', self::DEFAULT_CUTOFF );
	}

	/**
	 * Save the cutoff date
	 *
	 * @param string $date ISO format YYYY-MM-DD
	 * @return bool
	 */
	public static function save_cutoff_date( string $date ): bool {
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return false;
		}
		return update_option( 'megagovern_legacy_cutoff_date', $date );
	}

	/**
	 * Get the edit threshold percentage (substantial modification)
	 *
	 * @return int
	 */
	public static function get_edit_threshold(): int {
		return (int) get_option( 'megagovern_edit_threshold', 15 );
	}

	/**
	 * Save the edit threshold
	 *
	 * @param int $threshold Percentage from 0 to 100
	 * @return bool
	 */
	public static function save_edit_threshold( int $threshold ): bool {
		$threshold = max( 0, min( 100, $threshold ) );
		return update_option( 'megagovern_edit_threshold', $threshold );
	}

	/**
	 * Get legacy posts published before cutoff date requiring classification
	 *
	 * @param array $args Custom WP_Query overrides
	 * @return \WP_Query
	 */
	public static function get_legacy_query( array $args = [] ): \WP_Query {
		$cutoff_date = self::get_cutoff_date();

		$defaults = [
			'post_type'      => 'any',
			'post_status'    => 'publish',
			'posts_per_page' => 10,
			'paged'          => 1,
			'date_query'     => [
				[
					'before'    => $cutoff_date . ' 00:00:00',
					'inclusive' => false,
				],
			],
			'meta_query'     => [
				'relation' => 'AND',
				[
					'relation' => 'OR',
					[
						'key'     => '_megagovern_declaration',
						'compare' => 'NOT EXISTS',
					],
					[
						'key'     => '_megagovern_declaration',
						'value'   => '',
						'compare' => '=',
					],
				],
				[
					'relation' => 'OR',
					[
						'key'     => '_megagovern_legacy_exempt',
						'compare' => 'NOT EXISTS',
					],
					[
						'key'     => '_megagovern_legacy_exempt',
						'value'   => '0',
						'compare' => '=',
					],
				],
			],
			'no_found_rows'  => false,
		];

		$query_args = wp_parse_args( $args, $defaults );

		return new \WP_Query( $query_args );
	}

	/**
	 * Get exempt posts (marked as legacy exempt and not revoked)
	 *
	 * @param int $limit
	 * @return array Array of WP_Post objects
	 */
	public static function get_exempt_posts( int $limit = 10 ): array {
		return get_posts(
			[
				'post_type'      => 'any',
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
				'meta_query'     => [
					'relation' => 'AND',
					[
						'key'     => '_megagovern_legacy_exempt',
						'value'   => '1',
						'compare' => '=',
					],
					[
						'relation' => 'OR',
						[
							'key'     => '_megagovern_exempt_revoked',
							'compare' => 'NOT EXISTS',
						],
						[
							'key'     => '_megagovern_exempt_revoked',
							'value'   => '0',
							'compare' => '=',
						],
					],
				],
				'orderby'        => 'date',
				'order'          => 'DESC',
				'fields'         => 'all',
			]
		);
	}

	/**
	 * Count total active exempt posts using direct field queries
	 *
	 * @return int
	 */
	public static function count_exempt_posts(): int {
		$query = new \WP_Query(
			[
				'post_type'      => 'any',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => false,
				'meta_query'     => [
					'relation' => 'AND',
					[
						'key'     => '_megagovern_legacy_exempt',
						'value'   => '1',
						'compare' => '=',
					],
					[
						'relation' => 'OR',
						[
							'key'     => '_megagovern_exempt_revoked',
							'compare' => 'NOT EXISTS',
						],
						[
							'key'     => '_megagovern_exempt_revoked',
							'value'   => '0',
							'compare' => '=',
						],
					],
				],
			]
		);

		return (int) $query->found_posts;
	}

	/**
	 * Revoke exemption for a post (e.g., post updated past modification threshold)
	 *
	 * @param int $post_id
	 * @return bool
	 */
	public static function revoke_exemption( int $post_id ): bool {
		$success = update_post_meta( $post_id, '_megagovern_exempt_revoked', '1' );
		if ( $success ) {
			delete_post_meta( $post_id, '_megagovern_legacy_exempt' );
		}
		return (bool) $success;
	}

	/**
	 * Restore exemption for a post
	 *
	 * @param int $post_id
	 * @return bool
	 */
	public static function restore_exemption( int $post_id ): bool {
		delete_post_meta( $post_id, '_megagovern_exempt_revoked' );
		return (bool) update_post_meta( $post_id, '_megagovern_legacy_exempt', '1' );
	}

	/**
	 * Check if a post is actively exempt from AI Act declarations
	 *
	 * @param int $post_id
	 * @return bool
	 */
	public static function is_exempt( int $post_id ): bool {
		$exempt  = get_post_meta( $post_id, '_megagovern_legacy_exempt', true );
		$revoked = get_post_meta( $post_id, '_megagovern_exempt_revoked', true );
		return ( '1' === (string) $exempt ) && ( '1' !== (string) $revoked );
	}

	/**
	 * Count total legacy content needing action
	 *
	 * Optimized query fetching count without memory overhead of loading post bodies.
	 *
	 * @return int
	 */
	public static function count_legacy_content(): int {
		$query = self::get_legacy_query(
			[
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => false,
			]
		);
		return (int) $query->found_posts;
	}

	/**
	 * Get the cutoff date formatted for local admin display
	 *
	 * @return string
	 */
	public static function get_formatted_cutoff_date(): string {
		$date = self::get_cutoff_date();
		return date_i18n( get_option( 'date_format', 'M j, Y' ), strtotime( $date ) );
	}

	/**
	 * Get days elapsed since or remaining until the cutoff date
	 *
	 * @return int Positive if past cutoff, negative if cutoff is in the future
	 */
	public static function days_since_cutoff(): int {
		$cutoff_timestamp   = strtotime( self::get_cutoff_date() . ' 00:00:00' );
		$current_timestamp = current_time( 'timestamp' );
		return (int) round( ( $current_timestamp - $cutoff_timestamp ) / DAY_IN_SECONDS );
	}
}