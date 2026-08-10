<?php
/**
 * Usage Tracking — V1.0.4
 *
 * Tracks scans and words used per month.
 *
 * @package MegaGovern
 * @since   1.0.4
 */

namespace MegaGovern;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Usage {

	/**
	 * Cache TTL for usage data (5 minutes).
	 *
	 * @var int
	 */
	const CACHE_TTL = 300;

	/**
	 * Get current month usage.
	 *
	 * @param int $site_id Optional site ID for agency.
	 * @return array
	 */
	public static function get_usage( int $site_id = 0 ): array {
		$month = current_time( 'Y-m' );
		$key   = $site_id > 0 ? 'megagovern_usage_site_' . $site_id . '_' . $month : 'megagovern_usage_' . $month;
		$usage = get_option(
			$key,
			[
				'scans'     => 0,
				'words'     => 0,
				'last_scan' => '',
			]
		);

		return $usage;
	}

	/**
	 * Get usage summary for display.
	 * Cached for 5 minutes.
	 *
	 * @param int  $site_id       Optional site ID for agency.
	 * @param bool $bypass_cache  Whether to bypass cache.
	 * @return array
	 */
	public static function get_usage_summary( int $site_id = 0, bool $bypass_cache = false ): array {
		$cache_key = 'megagovern_usage_summary_' . $site_id;

		if ( ! $bypass_cache ) {
			$cached = get_transient( $cache_key );
			if ( false !== $cached && is_array( $cached ) ) {
				return $cached;
			}
		}

		$usage = self::get_usage( $site_id );

		$data = [
			'scans_used'   => (int) ( $usage['scans'] ?? 0 ),
			'words_used'   => (int) ( $usage['words'] ?? 0 ),
			'is_unlimited' => true,
			'last_scan'    => $usage['last_scan'] ?? '',
		];

		set_transient( $cache_key, $data, self::CACHE_TTL );

		return $data;
	}

	/**
	 * Record a scan.
	 *
	 * @param int $word_count Words scanned.
	 * @param int $site_id    Optional site ID for agency.
	 * @return bool
	 */
	public static function record_scan( int $word_count = 0, int $site_id = 0 ): bool {
		$month = current_time( 'Y-m' );
		$key   = $site_id > 0 ? 'megagovern_usage_site_' . $site_id . '_' . $month : 'megagovern_usage_' . $month;
		$usage = get_option(
			$key,
			[
				'scans'     => 0,
				'words'     => 0,
				'last_scan' => '',
			]
		);

		$usage['scans']     = (int) ( $usage['scans'] ?? 0 ) + 1;
		$usage['words']     = (int) ( $usage['words'] ?? 0 ) + $word_count;
		$usage['last_scan'] = current_time( 'mysql' );

		$updated = update_option( $key, $usage );

		delete_transient( 'megagovern_usage_summary_' . $site_id );

		return $updated;
	}

	/**
	 * Get site usage for agency dashboard.
	 *
	 * @param int $site_id Site ID.
	 * @return array
	 */
	public static function get_site_usage( int $site_id ): array {
		$usage = self::get_usage( $site_id );

		return [
			'site_id'    => $site_id,
			'scans_used' => (int) ( $usage['scans'] ?? 0 ),
			'words_used' => (int) ( $usage['words'] ?? 0 ),
			'last_scan'  => $usage['last_scan'] ?? '',
		];
	}

	/**
	 * Get formatted usage string.
	 *
	 * @param int $site_id Optional site ID for agency.
	 * @return string
	 */
	public static function get_usage_string( int $site_id = 0 ): string {
		$usage = self::get_usage( $site_id );

		return sprintf(
			'%d scans · %s words',
			(int) ( $usage['scans'] ?? 0 ),
			number_format_i18n( (int) ( $usage['words'] ?? 0 ) )
		);
	}

	/**
	 * Get usage HTML for dashboard display.
	 *
	 * @param int $site_id Optional site ID.
	 * @return string
	 */
	public static function get_usage_html( int $site_id = 0 ): string {
		$data = self::get_usage_summary( $site_id );

		$html  = '<div class="mga-usage-bar">';
		$html .= '<div class="mga-usage-label">' . esc_html__( 'Scans', 'megagovern' ) . ': ' . esc_html( (string) $data['scans_used'] ) . '</div>';
		$html .= '<div class="mga-usage-label">' . esc_html__( 'Words', 'megagovern' ) . ': ' . esc_html( number_format_i18n( $data['words_used'] ) ) . '</div>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Get Lucide icon SVG for usage type.
	 *
	 * @param string $type Icon type.
	 * @return string SVG markup.
	 */
	public static function get_usage_icon( string $type = 'scans' ): string {
		$icons = [
			'scans'     => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>',
			'words'     => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
			'unlimited' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>',
		];

		return $icons[ $type ] ?? '';
	}

	/**
	 * Clear usage cache.
	 *
	 * @param int $site_id Optional site ID.
	 */
	public static function clear_cache( int $site_id = 0 ): void {
		delete_transient( 'megagovern_usage_summary_' . $site_id );
	}
}