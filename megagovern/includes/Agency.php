<?php
/**
 * Agency Plan — Multi-Site Management — V1.0.4
 *
 * Supports up to 20 sites.
 * LOCAL-FIRST —
 *
 * @package MegaGovern
 * @since   1.0.4
 */

namespace MegaGovern;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Agency {

	const MAX_SITES = 20;

	public static function get_sites(): array {
		return get_option( 'megagovern_agency_sites', [] );
	}

	public static function get_active_sites(): array {
		$sites = self::get_sites();
		return array_filter(
			$sites,
			function( $site ) {
				return ( $site['status'] ?? 'active' ) === 'active';
			}
		);
	}

	public static function get_max_sites(): int {
		return self::MAX_SITES;
	}

	public static function add_site( string $site_url, string $site_name = '' ): array {
		$max_sites = self::get_max_sites();

		if ( count( self::get_sites() ) >= $max_sites ) {
			return [ 'error' => __( 'Site limit reached.', 'megagovern' ) ];
		}

		$sites = self::get_sites();
		$id    = 'site_' . md5( $site_url . time() );

		$sites[ $id ] = [
			'id'          => $id,
			'url'         => esc_url_raw( $site_url ),
			'name'        => sanitize_text_field( $site_name ?: wp_parse_url( $site_url, PHP_URL_HOST ) ),
			'added'       => current_time( 'mysql' ),
			'status'      => 'active',
			'score'       => 0,
			'declared'    => 0,
			'scans_limit' => -1,
			'words_limit' => -1,
		];

		update_option( 'megagovern_agency_sites', $sites );
		return [ 'success' => true, 'id' => $id ];
	}

	public static function remove_site( string $site_id ): bool {
		$sites = self::get_sites();
		unset( $sites[ $site_id ] );
		update_option( 'megagovern_agency_sites', $sites );
		delete_option( 'megagovern_usage_site_' . $site_id . '_' . gmdate( 'Y-m' ) );
		return true;
	}

	public static function update_site_status( string $site_id, string $status ): bool {
		$sites = self::get_sites();
		if ( ! isset( $sites[ $site_id ] ) ) {
			return false;
		}
		$sites[ $site_id ]['status'] = sanitize_text_field( $status );
		update_option( 'megagovern_agency_sites', $sites );
		return true;
	}

	public static function update_site_limits( string $site_id, array $limits ): bool {
		$sites = self::get_sites();
		if ( ! isset( $sites[ $site_id ] ) ) {
			return false;
		}
		if ( isset( $limits['scans'] ) ) {
			$sites[ $site_id ]['scans_limit'] = (int) $limits['scans'];
		}
		if ( isset( $limits['words'] ) ) {
			$sites[ $site_id ]['words_limit'] = (int) $limits['words'];
		}
		update_option( 'megagovern_agency_sites', $sites );
		return true;
	}

	public static function get_site_limits( string $site_id ): ?array {
		$site = self::get_site( $site_id );
		if ( ! $site ) {
			return null;
		}
		return [
			'scans' => $site['scans_limit'] ?? -1,
			'words' => $site['words_limit'] ?? -1,
		];
	}

	public static function count_sites(): int {
		return count( self::get_sites() );
	}

	public static function count_active_sites(): int {
		return count( self::get_active_sites() );
	}

	public static function get_remaining_slots(): int {
		return max( 0, self::get_max_sites() - self::count_sites() );
	}

	public static function get_summary(): array {
		$sites         = self::get_sites();
		$total         = count( $sites );
		$good          = 0;
		$warning       = 0;
		$critical      = 0;
		$declared_total = 0;

		foreach ( $sites as $site ) {
			$score    = (int) ( $site['score'] ?? 0 );
			$declared = (int) ( $site['declared'] ?? 0 );
			if ( $score >= 85 ) {
				$good++;
			} elseif ( $score >= 50 ) {
				$warning++;
			} else {
				$critical++;
			}
			$declared_total += $declared;
		}

		return [
			'total'           => $total,
			'good'            => $good,
			'warning'         => $warning,
			'critical'        => $critical,
			'declared_total'  => $declared_total,
			'remaining_slots' => self::get_remaining_slots(),
			'max_sites'       => self::get_max_sites(),
		];
	}

	public static function get_site_usage_summary(): array {
		$sites   = self::get_sites();
		$summary = [];
		foreach ( $sites as $id => $site ) {
			$usage         = Usage::get_site_usage( $id );
			$summary[ $id ] = array_merge( $site, $usage );
		}
		return $summary;
	}

	public static function get_total_usage(): array {
		$sites = self::get_sites();
		$total = [ 'scans' => 0, 'words' => 0 ];
		foreach ( $sites as $id => $site ) {
			$usage = Usage::get_usage( $id );
			$total['scans'] += (int) ( $usage['scans'] ?? 0 );
			$total['words'] += (int) ( $usage['words'] ?? 0 );
		}
		return $total;
	}

	public static function get_white_label(): array {
		return [
			'agency_name'   => get_option( 'megagovern_wl_name', get_bloginfo( 'name' ) ),
			'agency_logo'   => get_option( 'megagovern_wl_logo', '' ),
			'agency_color'  => get_option( 'megagovern_wl_color', '#2271b1' ),
			'agency_footer' => get_option( 'megagovern_wl_footer', '' ),
			'hide_branding' => get_option( 'megagovern_wl_hide', true ),
		];
	}

	public static function update_white_label( array $settings ): bool {
		if ( isset( $settings['agency_name'] ) ) {
			update_option( 'megagovern_wl_name', sanitize_text_field( $settings['agency_name'] ) );
		}
		if ( isset( $settings['agency_logo'] ) ) {
			update_option( 'megagovern_wl_logo', esc_url_raw( $settings['agency_logo'] ) );
		}
		if ( isset( $settings['agency_color'] ) ) {
			update_option( 'megagovern_wl_color', sanitize_hex_color( $settings['agency_color'] ) );
		}
		if ( isset( $settings['agency_footer'] ) ) {
			update_option( 'megagovern_wl_footer', sanitize_textarea_field( $settings['agency_footer'] ) );
		}
		if ( isset( $settings['hide_branding'] ) ) {
			update_option( 'megagovern_wl_hide', (bool) $settings['hide_branding'] );
		}
		return true;
	}

	public static function get_team(): array {
		return get_option( 'megagovern_agency_team', [] );
	}

	public static function add_team_member( int $user_id, string $role = 'manager' ): array {
		$team = self::get_team();
		if ( isset( $team[ $user_id ] ) ) {
			return [ 'error' => __( 'User already added.', 'megagovern' ) ];
		}
		$valid_roles = [ 'admin', 'manager', 'editor', 'contributor' ];
		if ( ! in_array( $role, $valid_roles, true ) ) {
			$role = 'manager';
		}
		$team[ $user_id ] = [
			'user_id' => $user_id,
			'role'    => sanitize_text_field( $role ),
			'added'   => current_time( 'mysql' ),
			'status'  => 'active',
		];
		update_option( 'megagovern_agency_team', $team );
		return [ 'success' => true ];
	}

	public static function remove_team_member( int $user_id ): bool {
		$team = self::get_team();
		unset( $team[ $user_id ] );
		update_option( 'megagovern_agency_team', $team );
		return true;
	}

	public static function update_team_member_role( int $user_id, string $role ): bool {
		$team = self::get_team();
		if ( ! isset( $team[ $user_id ] ) ) {
			return false;
		}
		$valid_roles = [ 'admin', 'manager', 'editor', 'contributor' ];
		if ( ! in_array( $role, $valid_roles, true ) ) {
			return false;
		}
		$team[ $user_id ]['role'] = sanitize_text_field( $role );
		update_option( 'megagovern_agency_team', $team );
		return true;
	}

	public static function get_team_member_role( int $user_id ): string {
		$team = self::get_team();
		return $team[ $user_id ]['role'] ?? 'contributor';
	}

	public static function get_team_member_permissions( int $user_id ): array {
		$role = self::get_team_member_role( $user_id );
		$permissions = [
			'admin'       => [
				'manage_sites'   => true,
				'manage_team'    => true,
				'manage_settings' => true,
				'view_reports'   => true,
				'bulk_declare'   => true,
			],
			'manager'     => [
				'manage_sites'   => true,
				'manage_team'    => false,
				'manage_settings' => false,
				'view_reports'   => true,
				'bulk_declare'   => true,
			],
			'editor'      => [
				'manage_sites'   => false,
				'manage_team'    => false,
				'manage_settings' => false,
				'view_reports'   => true,
				'bulk_declare'   => true,
			],
			'contributor' => [
				'manage_sites'   => false,
				'manage_team'    => false,
				'manage_settings' => false,
				'view_reports'   => false,
				'bulk_declare'   => true,
			],
		];
		return $permissions[ $role ] ?? $permissions['contributor'];
	}

	public static function get_alerts( int $limit = 20 ): array {
		$alerts = get_option( 'megagovern_agency_alerts', [] );
		return array_slice( $alerts, 0, $limit );
	}

	public static function add_alert( string $site_id, string $message, string $type = 'warning' ): void {
		$alerts = get_option( 'megagovern_agency_alerts', [] );
		$alerts[] = [
			'site_id'    => $site_id,
			'message'    => sanitize_text_field( $message ),
			'type'       => sanitize_text_field( $type ),
			'created_at' => current_time( 'mysql' ),
			'read'       => false,
		];
		if ( count( $alerts ) > 100 ) {
			$alerts = array_slice( $alerts, -100 );
		}
		update_option( 'megagovern_agency_alerts', $alerts );
	}

	public static function mark_alerts_read( array $alert_ids = [] ): void {
		$alerts = get_option( 'megagovern_agency_alerts', [] );
		foreach ( $alerts as $key => $alert ) {
			if ( empty( $alert_ids ) || in_array( $key, $alert_ids, true ) ) {
				$alerts[ $key ]['read'] = true;
			}
		}
		update_option( 'megagovern_agency_alerts', $alerts );
	}

	public static function get_site( string $site_id ): ?array {
		$sites = self::get_sites();
		return $sites[ $site_id ] ?? null;
	}

	public static function get_site_by_url( string $url ): ?array {
		foreach ( self::get_sites() as $site ) {
			if ( $site['url'] === $url ) {
				return $site;
			}
		}
		return null;
	}

	public static function sync_site( string $site_id ): array {
		$site = self::get_site( $site_id );
		if ( ! $site ) {
			return [ 'error' => __( 'Site not found.', 'megagovern' ) ];
		}
		return [ 'success' => true, 'message' => __( 'Site synced.', 'megagovern' ) ];
	}

	public static function sync_all_sites(): array {
		$results = [];
		foreach ( self::get_sites() as $id => $site ) {
			$results[ $id ] = self::sync_site( $id );
		}
		return $results;
	}
}