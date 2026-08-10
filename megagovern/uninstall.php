<?php
/**
 * Uninstall MegaGovern — V1.0.4 LITE - WordPress.org Compliant
 *
 * @package MegaGovern
 * @since 1.0.4
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$megagovern_options = array(
	'megagovern_site_id',
	'megagovern_auto_aitxt',
	'megagovern_auto_verify',
	'megagovern_label_position',
	'megagovern_label_style',
	'megagovern_content_label_enabled',
	'megagovern_image_label_enabled',
	'megagovern_image_label_position',
	'megagovern_image_label_style',
	'megagovern_declaration_post_types',
	'megagovern_edit_threshold',
	'megagovern_region',
	'megagovern_policy_intro',
	'megagovern_policy_email',
	'megagovern_policy_contact_url',
	'megagovern_policy_page_id',
	'megagovern_report_schedule',
	'megagovern_report_email',
	'megagovern_setup_completed',
	'megagovern_setup_completed_at',
	'megagovern_setup_skipped',
	'megagovern_alerts',
	'megagovern_alerts_read',
	'megagovern_agency_sites',
	'megagovern_agency_alerts',
	'megagovern_agency_scheduled_reports',
	'megagovern_last_scan',
	'megagovern_verify_updated',
	'megagovern_aitxt_updated',
	'megagovern_aitxt_last_updated',
	'megagovern_db_version',
	'megagovern_flush_version',
	'megagovern_version',
);

foreach ( $megagovern_options as $megagovern_option ) {
	delete_option( $megagovern_option );
}

global $wpdb;

// Monthly usage
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
		$wpdb->esc_like( 'megagovern_usage_' ) . '%'
	)
);

// Transients
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
		$wpdb->esc_like( '_transient_megagovern_' ) . '%'
	)
);

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
		$wpdb->esc_like( '_transient_timeout_megagovern_' ) . '%'
	)
);

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
		$wpdb->esc_like( '_site_transient_megagovern_' ) . '%'
	)
);

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
		$wpdb->esc_like( '_site_transient_timeout_megagovern_' ) . '%'
	)
);

// Post meta
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s",
		$wpdb->esc_like( '_megagovern_' ) . '%'
	)
);

// User meta
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s",
		$wpdb->esc_like( 'megagovern_' ) . '%'
	)
);

// Drop custom tables
$megagovern_tables = array(
	$wpdb->prefix . 'mega_declarations',
	$wpdb->prefix . 'mega_declaration_log',
	$wpdb->prefix . 'megagovern_history',
	$wpdb->prefix . 'mega_content_snapshots',
);

foreach ( $megagovern_tables as $megagovern_table ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
	$wpdb->query( "DROP TABLE IF EXISTS `{$megagovern_table}`" );
}

// Clear scheduled hooks
wp_clear_scheduled_hook( 'megagovern_daily_report_check' );
wp_clear_scheduled_hook( 'megagovern_scheduled_report' );
wp_clear_scheduled_hook( 'megagovern_hourly_health_check' );
wp_clear_scheduled_hook( 'megagovern_daily_retention_cleanup' );
wp_clear_scheduled_hook( 'megagovern_weekly_alerts' );
wp_clear_scheduled_hook( 'megagovern_hourly_event' );