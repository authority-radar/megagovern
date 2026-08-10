<?php
/**
 * Service: Score — V1.0.4
 *
 * Dedicated score calculator extracted from Issues.php.
 * Handles score calculation, breakdown.
 *
 * @package MegaGovern
 * @since   1.0.4
 */

namespace MegaGovern;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Score {
	/**
	 * Cache TTL for score calculation (2 minutes).
	 *
	 * @var int
	 */
	const CACHE_TTL = 120;

	/**
	 * Get Lucide icon SVG.
	 *
	 * @param string $name Icon name.
	 * @param string $size Icon size in pixels.
	 * @return string SVG markup.
	 */
	private static function get_icon( string $name, string $size = '24' ): string {
		$icons = [
			'score'        => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>',
			'check-circle' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="9 12 11 14 15 10"/></svg>',
			'file-text'    => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
			'shield'       => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
			'globe'        => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>',
			'bar-chart'    => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></svg>',
		];

		return $icons[ $name ] ?? '';
	}

	/**
	 * Check if current plan is Pro or Agency.
	 *
	 * @return bool
	 */
	private static function is_pro(): bool {
		return class_exists( '\MegaGovern\License' ) && ( License::is_pro() || License::is_agency() );
	}

	/**
	 * Get default weights.
	 *
	 * @return array
	 */
	private static function get_weights(): array {
		return apply_filters(
			'megagovern_score_weights',
			[
				'declaration'  => 30,
				'transparency' => 20,
				'ai_files'     => 15,
				'ai_access'    => 15,
				'verification' => 10,
				'reports'      => 10,
			]
		);
	}

	/**
	 * Calculate governance score (0-100).
	 *
	 * Results cached for 2 minutes.
	 *
	 * @param bool $bypass_cache Whether to bypass cache.
	 * @return int
	 */
	public static function calculate( bool $bypass_cache = false ): int {
		$cache_key = 'megagovern_score_calculated';

		if ( ! $bypass_cache ) {
			$cached = get_transient( $cache_key );
			if ( false !== $cached && is_int( $cached ) ) {
				return $cached;
			}
		}

		// FIX: Check if Registry and Issues classes exist.
		if ( ! class_exists( '\MegaGovern\Registry' ) ) {
			return 0;
		}

		$stats       = Registry::get_stats();
		$total       = (int) ( $stats['total'] ?? 0 );
		$undeclared  = Registry::count_undeclared();
		$all_content = max( $total, Registry::count_total_content() );
		$coverage    = $all_content > 0 ? round( ( $total / $all_content ) * 100, 1 ) : 0;

		$label_enabled  = (bool) get_option( 'megagovern_label_position' );
		$aitxt_enabled  = (bool) get_option( 'megagovern_auto_aitxt', true );
		$verify_enabled = (bool) get_option( 'megagovern_auto_verify', true );
		$is_pro         = self::is_pro();

		$weights = self::get_weights();

		$score  = round( ( $coverage / 100 ) * $weights['declaration'] );
		$score += $label_enabled ? $weights['transparency'] : (int) ( $weights['transparency'] * 0.25 );
		$score += $aitxt_enabled ? $weights['ai_files'] : 0;
		$score += $is_pro ? $weights['ai_access'] : (int) ( $weights['ai_access'] * 0.33 );
		$score += $verify_enabled ? $weights['verification'] : 0;
		$score += $is_pro ? $weights['reports'] : (int) ( $weights['reports'] * 0.2 );

		// FIX: Check if Issues class exists.
		$issues_count = 0;
		if ( class_exists( '\MegaGovern\Issues' ) ) {
			$issues = Issues::check_all();
			$issues_count = count( $issues );
			$score -= $issues_count * 3;
		}

		$score = max( 0, min( 100, (int) $score ) );

		$score = apply_filters(
			'megagovern_final_score',
			$score,
			[
				'coverage'       => $coverage,
				'label_enabled'  => $label_enabled,
				'aitxt_enabled'  => $aitxt_enabled,
				'verify_enabled' => $verify_enabled,
				'is_pro'         => $is_pro,
				'issues_count'   => $issues_count,
			]
		);

		set_transient( $cache_key, $score, self::CACHE_TTL );

		return $score;
	}

	/**
	 * Get score color.
	 *
	 * @param int|null $score Score value.
	 * @return string
	 */
	public static function color( ?int $score = null ): string {
		if ( null === $score ) {
			$score = self::calculate();
		}
		return Helpers::score_color( $score );
	}

	/**
	 * Get score label.
	 *
	 * @param int|null $score Score value.
	 * @return string
	 */
	public static function label( ?int $score = null ): string {
		if ( null === $score ) {
			$score = self::calculate();
		}
		return Helpers::score_label( $score );
	}

	/**
	 * Get score icon.
	 *
	 * @param int|null $score Score value.
	 * @return string
	 */
	public static function icon( ?int $score = null ): string {
		if ( null === $score ) {
			$score = self::calculate();
		}

		if ( $score >= 80 ) {
			return self::get_icon( 'check-circle', '24' );
		}
		if ( $score >= 50 ) {
			return self::get_icon( 'shield', '24' );
		}
		return self::get_icon( 'bar-chart', '24' );
	}

	/**
	 * Get full score breakdown for dashboard.
	 * Cached for 2 minutes.
	 *
	 * @param bool $bypass_cache Whether to bypass cache.
	 * @return array
	 */
	public static function breakdown( bool $bypass_cache = false ): array {
		$cache_key = 'megagovern_score_breakdown';

		if ( ! $bypass_cache ) {
			$cached = get_transient( $cache_key );
			if ( false !== $cached && is_array( $cached ) ) {
				return $cached;
			}
		}

		// FIX: Check if Registry class exists.
		if ( ! class_exists( '\MegaGovern\Registry' ) ) {
			$default_breakdown = [
				'total'           => 0,
				'color'           => '#ef4444',
				'label'           => 'Unknown',
				'icon'            => '',
				'coverage'        => 0,
				'total_declared'  => 0,
				'total_content'   => 0,
				'undeclared'      => 0,
				'label_enabled'   => false,
				'aitxt_enabled'   => false,
				'is_pro'          => false,
				'issues_count'    => 0,
				'potential_score' => 0,
				'weights'         => self::get_weights(),
				'components'      => [],
			];
			return $default_breakdown;
		}

		$score = self::calculate( true );

		$stats         = Registry::get_stats();
		$total         = (int) ( $stats['total'] ?? 0 );
		$undeclared    = Registry::count_undeclared();
		$all_content   = max( $total, Registry::count_total_content() );
		$coverage      = $all_content > 0 ? round( ( $total / $all_content ) * 100, 1 ) : 0;
		$label_enabled = (bool) get_option( 'megagovern_label_position' );
		$aitxt_enabled = (bool) get_option( 'megagovern_auto_aitxt', true );
		$is_pro        = self::is_pro();

		// FIX: Check if Issues class exists.
		$issues = [];
		if ( class_exists( '\MegaGovern\Issues' ) ) {
			$issues = Issues::check_all();
		}

		$weights = self::get_weights();

		$result = [
			'total'           => $score,
			'color'           => self::color( $score ),
			'label'           => self::label( $score ),
			'icon'            => self::icon( $score ),
			'coverage'        => $coverage,
			'total_declared'  => $total,
			'total_content'   => $all_content,
			'undeclared'      => $undeclared,
			'label_enabled'   => $label_enabled,
			'aitxt_enabled'   => $aitxt_enabled,
			'is_pro'          => $is_pro,
			'issues_count'    => count( $issues ),
			'potential_score' => min( 88, $score + ( $is_pro ? 0 : 27 ) ),
			'weights'         => $weights,
			'components'      => [
				'declaration'  => [
					'label'  => __( 'Content Declaration', 'megagovern' ),
					'icon'   => self::get_icon( 'file-text', '16' ),
					'pct'    => $coverage,
					'weight' => $weights['declaration'],
					'score'  => round( ( $coverage / 100 ) * $weights['declaration'] ),
				],
				'transparency' => [
					'label'  => __( 'Transparency Labels', 'megagovern' ),
					'icon'   => self::get_icon( 'shield', '16' ),
					'pct'    => $label_enabled ? 100 : 25,
					'weight' => $weights['transparency'],
					'score'  => $label_enabled ? $weights['transparency'] : (int) ( $weights['transparency'] * 0.25 ),
				],
				'ai_files'     => [
					'label'  => __( 'AI.txt File', 'megagovern' ),
					'icon'   => self::get_icon( 'file-text', '16' ),
					'pct'    => $aitxt_enabled ? 100 : 0,
					'weight' => $weights['ai_files'],
					'score'  => $aitxt_enabled ? $weights['ai_files'] : 0,
				],
				'ai_access'    => [
					'label'  => __( 'Crawler Protection', 'megagovern' ),
					'icon'   => self::get_icon( 'shield', '16' ),
					'pct'    => $is_pro ? 100 : 40,
					'weight' => $weights['ai_access'],
					'score'  => $is_pro ? $weights['ai_access'] : (int) ( $weights['ai_access'] * 0.33 ),
				],
				'verification' => [
					'label'  => __( 'Verification Page', 'megagovern' ),
					'icon'   => self::get_icon( 'check-circle', '16' ),
					'pct'    => $is_pro ? 100 : ( $coverage > 0 ? min( 100, $coverage ) : 0 ),
					'weight' => $weights['verification'],
					'score'  => $is_pro ? $weights['verification'] : ( $coverage > 0 ? $weights['verification'] : 0 ),
				],
				'reports'      => [
					'label'  => __( 'Reports & Evidence', 'megagovern' ),
					'icon'   => self::get_icon( 'bar-chart', '16' ),
					'pct'    => $is_pro ? 100 : 20,
					'weight' => $weights['reports'],
					'score'  => $is_pro ? $weights['reports'] : (int) ( $weights['reports'] * 0.2 ),
				],
			],
		];

		set_transient( $cache_key, $result, self::CACHE_TTL );

		return $result;
	}

	/**
	 * Clear score cache.
	 */
	public static function clear_cache(): void {
		delete_transient( 'megagovern_score_calculated' );
		delete_transient( 'megagovern_score_breakdown' );
	}

	/**
	 * EU AI Act Article Coverage.
	 *
	 * @return array
	 */
	public static function eu_act_coverage(): array {
		$articles = [
			[
				'article' => 'Art. 50 (1)',
				'title'   => __( 'Transparency for AI-generated content', 'megagovern' ),
				'covered' => true,
				'service' => __( 'Content Declaration + Labels', 'megagovern' ),
				'icon'    => self::get_icon( 'check-circle', '16' ),
			],
			[
				'article' => 'Art. 50 (2)',
				'title'   => __( 'Disclosure of AI-manipulated media', 'megagovern' ),
				'covered' => true,
				'service' => __( 'C2PA Image Metadata (Enterprise)', 'megagovern' ),
				'icon'    => self::get_icon( 'check-circle', '16' ),
			],
			[
				'article' => 'Art. 50 (3)',
				'title'   => __( 'Emotion recognition & biometric disclosure', 'megagovern' ),
				'covered' => false,
				'service' => __( 'Not in scope', 'megagovern' ),
				'icon'    => '',
			],
			[
				'article' => 'Art. 50 (4)',
				'title'   => __( 'Machine-readable AI labeling', 'megagovern' ),
				'covered' => true,
				'service' => __( 'AI.txt Generator', 'megagovern' ),
				'icon'    => self::get_icon( 'check-circle', '16' ),
			],
			[
				'article' => 'Art. 52',
				'title'   => __( 'Transparency obligations for AI systems', 'megagovern' ),
				'covered' => true,
				'service' => __( 'Verification Page + Registry', 'megagovern' ),
				'icon'    => self::get_icon( 'check-circle', '16' ),
			],
		];

		// ✅ FIX: PHP 7.3 compatible array_filter.
		$covered = 0;
		foreach ( $articles as $a ) {
			if ( $a['covered'] ) {
				$covered++;
			}
		}

		$total = count( $articles );

		// ✅ FIX: PHP 7.3 compatible filtering.
		$missing = [];
		foreach ( $articles as $a ) {
			if ( ! $a['covered'] ) {
				$missing[] = $a;
			}
		}

		return [
			'articles'       => $articles,
			'covered_count'  => $covered,
			'total_articles' => $total,
			'percentage'     => $total > 0 ? round( ( $covered / $total ) * 100 ) : 0,
			'missing'        => $missing,
		];
	}
}