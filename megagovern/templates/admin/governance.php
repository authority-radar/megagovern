<?php
/**
 * MegaGovern Transparency Center — V1.0.4
 * Tabbed Interface with cached common data.
 *
 * @package MegaGovern
 * @since   1.0.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MegaGovern\Registry;
use MegaGovern\Governance;
use MegaGovern\License;
use MegaGovern\Crawler;
use MegaGovern\Alerts;

// ═══════════════════════════════
// 1. Capability Check
// ═══════════════════════════════
if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'megagovern_manage_compliance' ) ) {
	wp_die( esc_html__( 'Access Denied', 'megagovern' ), esc_html__( 'Access Denied', 'megagovern' ), array( 'response' => 403 ) );
}

// ═══════════════════════════════
// 2. Tab Routing — NEW STRUCTURE
// ═══════════════════════════════
$megagovern_tab = 'catalog';
if ( isset( $_GET['gtab'] ) ) {
	$megagovern_tab_raw = sanitize_text_field( wp_unslash( $_GET['gtab'] ) );
	$megagovern_tab_allowed = array( 'catalog', 'transparency', 'content-labels', 'ai-notice', 'archive' );
	if ( in_array( $megagovern_tab_raw, $megagovern_tab_allowed, true ) ) {
		$megagovern_tab = $megagovern_tab_raw;
	}
}

// ═══════════════════════════════
// 3. License
// ═══════════════════════════════
$megagovern_flags     = License::get_flags();
$megagovern_is_pro    = isset( $megagovern_flags['is_pro'] ) ? (bool) $megagovern_flags['is_pro'] : false;
$megagovern_is_agency = isset( $megagovern_flags['is_agency'] ) ? (bool) $megagovern_flags['is_agency'] : false;
$megagovern_is_free   = isset( $megagovern_flags['is_free'] ) ? (bool) $megagovern_flags['is_free'] : true;
$megagovern_plan_name = License::get_plan_name();
$megagovern_has_pro   = $megagovern_is_pro || $megagovern_is_agency;

// ═══════════════════════════════
// 4. Local Site ID Helper
// ═══════════════════════════════
if ( ! function_exists( 'megagovern_get_local_site_id' ) ) {
	/**
	 * Get or generate the local site ID.
	 *
	 * @return string Site ID.
	 */
	function megagovern_get_local_site_id(): string {
		$megagovern_site_id = get_option( 'megagovern_site_id', '' );
		if ( empty( $megagovern_site_id ) ) {
			$megagovern_site_id = 'mg_' . substr( md5( get_home_url() . wp_generate_uuid4() ), 0, 16 );
			update_option( 'megagovern_site_id', $megagovern_site_id );
		}
		return $megagovern_site_id;
	}
}

// ═══════════════════════════════
// 5. Lucide Icon Helper
// ═══════════════════════════════
if ( ! function_exists( 'megagovern_get_icon' ) ) {
	/**
	 * Get an inline SVG icon.
	 *
	 * @param string $name Icon identifier.
	 * @param string $size Width/height in pixels.
	 * @return string SVG markup.
	 */
	function megagovern_get_icon( string $name, string $size = '16' ): string {
		$megagovern_icons = array(
			'database' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14a9 3 0 0 0 18 0V5"/><path d="M3 12a9 3 0 0 0 18 0"/></svg>',
			'eye'      => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>',
			'archive'  => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="5" rx="1"/><path d="M4 9v9a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9"/><path d="M10 13h4"/></svg>',
			'tag'      => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2H2v10l9.29 9.29a1 1 0 0 0 1.41 0l7-7a1 1 0 0 0 0-1.41L12 2z"/><polyline points="7 7 7.01 7"/></svg>',
			'bell'     => '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>',
		);
		return isset( $megagovern_icons[ $name ] ) ? $megagovern_icons[ $name ] : '';
	}
}

// ═══════════════════════════════
// 6. URL Helper
// ═══════════════════════════════
if ( ! function_exists( 'megagovern_transparency_tab_url' ) ) {
	/**
	 * Get a tab URL for the transparency center.
	 *
	 * @param string $gtab Tab identifier.
	 * @return string Escaped URL.
	 */
	function megagovern_transparency_tab_url( string $gtab ): string {
		return esc_url( add_query_arg( array( 'page' => 'megagovern-transparency', 'gtab' => $gtab ), admin_url( 'admin.php' ) ) );
	}
}

// ═══════════════════════════════
// 7. Upgrade URL
// ═══════════════════════════════
$megagovern_upgrade_url = '';
if ( $megagovern_is_free && function_exists( 'mga_fs' ) ) {
	$megagovern_upgrade_url = mga_fs()->get_upgrade_url() ?: '';
}

// ═══════════════════════════════
// 8. CACHED Common Data (2 min)
// ═══════════════════════════════
$megagovern_cache_key = 'megagovern_transparency_common_' . get_current_user_id();
$megagovern_common    = get_transient( $megagovern_cache_key );

if ( false === $megagovern_common ) {
	$megagovern_stats   = Registry::get_stats();
	$megagovern_site_id = megagovern_get_local_site_id();

	$megagovern_crawler_health = array();
	$megagovern_health_pct     = 0;
	$megagovern_health_updated = __( 'Just now', 'megagovern' );
	if ( class_exists( '\MegaGovern\Crawler' ) ) {
		$megagovern_crawler        = new Crawler();
		$megagovern_crawler_health = $megagovern_crawler->get_health();
		$megagovern_health_pct     = isset( $megagovern_crawler_health['coverage'] ) ? (int) $megagovern_crawler_health['coverage'] : 0;
		$megagovern_health_updated = isset( $megagovern_crawler_health['last_updated'] ) ? $megagovern_crawler_health['last_updated'] : __( 'Just now', 'megagovern' );
	}

	$megagovern_common = array(
		'stats'          => $megagovern_stats,
		'site_id'        => $megagovern_site_id,
		'hash'           => substr( hash( 'sha256', $megagovern_site_id . ( isset( $megagovern_stats['last_updated'] ) ? $megagovern_stats['last_updated'] : '' ) ), 0, 16 ),
		'crawler_health' => $megagovern_crawler_health,
		'health_pct'     => $megagovern_health_pct,
		'health_updated' => $megagovern_health_updated,
		'registry_total' => isset( $megagovern_stats['total'] ) ? (int) $megagovern_stats['total'] : 0,
		'registry_last'  => isset( $megagovern_stats['last_updated'] ) ? $megagovern_stats['last_updated'] : __( 'Active', 'megagovern' ),
	);
	set_transient( $megagovern_cache_key, $megagovern_common, 120 );
}

// Extract from cache.
$megagovern_stats          = $megagovern_common['stats'];
$megagovern_site_id        = $megagovern_common['site_id'];
$megagovern_hash           = $megagovern_common['hash'];
$megagovern_health_pct     = $megagovern_common['health_pct'];
$megagovern_health_updated = $megagovern_common['health_updated'];
$megagovern_registry_total = $megagovern_common['registry_total'];
$megagovern_registry_last  = $megagovern_common['registry_last'];

// Nonces.
$megagovern_nonce_bulk    = wp_create_nonce( 'megagovern_bulk_declare' );
$megagovern_nonce_history = wp_create_nonce( 'megagovern_get_history' );
$megagovern_nonce_aitxt   = wp_create_nonce( 'megagovern_regenerate_aitxt' );

// Static URLs.
$megagovern_verify_url = home_url( '/transparency' );
$megagovern_aitxt_url  = home_url( '/ai.txt' );
$megagovern_policy_url = home_url( '/ai-policy' );

// Options.
$megagovern_label_pos   = get_option( 'megagovern_label_position', 'top' );
$megagovern_label_style = get_option( 'megagovern_label_style', '1' );
$megagovern_auto_aitxt  = (bool) get_option( 'megagovern_auto_aitxt', true );
$megagovern_auto_verify = (bool) get_option( 'megagovern_auto_verify', true );

// ═══════════════════════════════
// 9. Content Hub Query (only for catalog tab)
// ═══════════════════════════════
$megagovern_content_args = array();
if ( 'catalog' === $megagovern_tab ) {
	$megagovern_filter    = isset( $_GET['declaration_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['declaration_filter'] ) ) : 'all';
	$megagovern_post_type = isset( $_GET['post_type_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['post_type_filter'] ) ) : 'all';
	$megagovern_search    = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
	$megagovern_paged     = isset( $_GET['paged'] ) ? absint( wp_unslash( $_GET['paged'] ) ) : 1;

	// All post types for all plans.
	$megagovern_saved_types = get_option( 'megagovern_declaration_post_types', array( 'post', 'page' ) );

	// FIX: Include 'inherit' status for media (attachments)
	// When filtering by 'attachment' or 'all', include both publish and inherit status
	$megagovern_post_status = ( 'attachment' === $megagovern_post_type || 'all' === $megagovern_post_type )
		? array( 'publish', 'inherit' )
		: array( 'publish' );

	$megagovern_query_args = array(
		'post_type'              => ( 'all' === $megagovern_post_type ) ? $megagovern_saved_types : $megagovern_post_type,
		'post_status'            => $megagovern_post_status,
		'posts_per_page'         => 20,
		'paged'                  => $megagovern_paged,
		's'                      => $megagovern_search,
		'no_found_rows'          => false,
		'update_post_meta_cache' => true,
	);

	if ( 'all' !== $megagovern_filter ) {
		if ( 'undeclared' === $megagovern_filter ) {
			$megagovern_query_args['meta_query'] = array(
				'relation' => 'OR',
				array( 'key' => '_megagovern_declaration', 'compare' => 'NOT EXISTS' ),
				array( 'key' => '_megagovern_declaration', 'value' => '', 'compare' => '=' ),
			);
		} elseif ( in_array( $megagovern_filter, array( 'human', 'ai_assisted', 'ai_generated' ), true ) ) {
			$megagovern_query_args['meta_query'] = array( array( 'key' => '_megagovern_declaration', 'value' => $megagovern_filter ) );
		}
	}

	$megagovern_query = new WP_Query( $megagovern_query_args );

	$megagovern_content_args = array(
		'filter'        => $megagovern_filter,
		'post_type'     => $megagovern_post_type,
		'search'        => $megagovern_search,
		'paged'         => $megagovern_paged,
		'stats'         => $megagovern_stats,
		'undeclared'    => Registry::count_undeclared(),
		'saved_types'   => $megagovern_saved_types,
		'is_free'       => $megagovern_is_free,
		'has_pro'       => $megagovern_has_pro,
		'is_agency'     => $megagovern_is_agency,
		'nonce_bulk'    => $megagovern_nonce_bulk,
		'nonce_history' => $megagovern_nonce_history,
		'query'         => $megagovern_query,
	);
}

// ═══════════════════════════════
// 10. Common Args for Included Files
// ═══════════════════════════════
$megagovern_common_args = array(
	'is_free'      => $megagovern_is_free,
	'has_pro'      => $megagovern_has_pro,
	'is_agency'    => $megagovern_is_agency,
	'upgrade_url'  => $megagovern_upgrade_url,
	'verify_url'   => $megagovern_verify_url,
	'aitxt_url'    => $megagovern_aitxt_url,
	'hash'         => $megagovern_hash,
	'site_id'      => $megagovern_site_id,
);

// ═══════════════════════════════
// 11. Handle Nonce for Settings (if needed)
// ═══════════════════════════════
if ( isset( $_POST['megagovern_settings_nonce'] ) ) {
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['megagovern_settings_nonce'] ) ), 'megagovern_settings_action' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'megagovern' ) );
	}
}
?>

<div class="wrap megagovern-transparency">

	<hr class="wp-header-end">

	<nav class="mga-tabs">
		<a href="<?php echo esc_url( megagovern_transparency_tab_url( 'catalog' ) ); ?>" class="mga-tab <?php echo 'catalog' === $megagovern_tab ? 'mga-tab--active' : ''; ?>">
			<?php echo wp_kses_post( megagovern_get_icon( 'database', '16' ) ); ?>
			<?php esc_html_e( 'Content Hub', 'megagovern' ); ?>
		</a>
		<a href="<?php echo esc_url( megagovern_transparency_tab_url( 'content-labels' ) ); ?>" class="mga-tab <?php echo 'content-labels' === $megagovern_tab ? 'mga-tab--active' : ''; ?>">
			<?php echo wp_kses_post( megagovern_get_icon( 'tag', '16' ) ); ?>
			<?php esc_html_e( 'Content & Labels', 'megagovern' ); ?>
		</a>
		<a href="<?php echo esc_url( megagovern_transparency_tab_url( 'transparency' ) ); ?>" class="mga-tab <?php echo 'transparency' === $megagovern_tab ? 'mga-tab--active' : ''; ?>">
			<?php echo wp_kses_post( megagovern_get_icon( 'eye', '16' ) ); ?>
			<?php esc_html_e( 'Disclosures & AI Files', 'megagovern' ); ?>
		</a>
		<a href="<?php echo esc_url( megagovern_transparency_tab_url( 'ai-notice' ) ); ?>" class="mga-tab <?php echo 'ai-notice' === $megagovern_tab ? 'mga-tab--active' : ''; ?>">
			<?php echo wp_kses_post( megagovern_get_icon( 'bell', '16' ) ); ?>
			<?php esc_html_e( 'AI Notice', 'megagovern' ); ?>
		</a>
		<a href="<?php echo esc_url( megagovern_transparency_tab_url( 'archive' ) ); ?>" class="mga-tab <?php echo 'archive' === $megagovern_tab ? 'mga-tab--active' : ''; ?>">
			<?php echo wp_kses_post( megagovern_get_icon( 'archive', '16' ) ); ?>
			<?php esc_html_e( 'Legacy Archive', 'megagovern' ); ?>
		</a>
	</nav>

	<?php
	switch ( $megagovern_tab ) {
		case 'catalog':
			$megagovern_args = array_merge( $megagovern_common_args, $megagovern_content_args );
			include MEGAGOVERN_PATH . 'templates/admin/tab-content.php';
			break;

		case 'transparency':
			$megagovern_args = array_merge( $megagovern_common_args, array(
				'label_pos'      => $megagovern_label_pos,
				'label_style'    => $megagovern_label_style,
				'auto_aitxt'     => $megagovern_auto_aitxt,
				'auto_verify'    => $megagovern_auto_verify,
				'policy_url'     => $megagovern_policy_url,
				'health_pct'     => $megagovern_health_pct,
				'health_updated' => $megagovern_health_updated,
				'nonce_aitxt'    => $megagovern_nonce_aitxt,
			) );
			include MEGAGOVERN_PATH . 'templates/admin/tab-disclosures.php';
			break;

		case 'content-labels':
			$megagovern_args = array_merge( $megagovern_common_args, array(
				'is_pro'    => $megagovern_is_pro,
				'is_agency' => $megagovern_is_agency,
				'is_free'   => $megagovern_is_free,
			) );
			include MEGAGOVERN_PATH . 'templates/admin/tab-content-labels.php';
			break;

		case 'ai-notice':
			$megagovern_args = array_merge( $megagovern_common_args, array(
				'is_pro'      => $megagovern_is_pro,
				'is_agency'   => $megagovern_is_agency,
				'is_free'     => $megagovern_is_free,
				'upgrade_url' => $megagovern_upgrade_url,
			) );
			include MEGAGOVERN_PATH . 'templates/admin/tab-ai-notice.php';
			break;

		case 'archive':
			$megagovern_args = array_merge( $megagovern_common_args, array(
				'stats'          => $megagovern_stats,
				'registry_total' => $megagovern_registry_total,
				'registry_last'  => $megagovern_registry_last,
			) );
			include MEGAGOVERN_PATH . 'templates/admin/tab-archive.php';
			break;
	}
	?>
</div>