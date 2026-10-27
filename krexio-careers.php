<?php
/**
 * Plugin Name: Krexio Careers
 * Description: Job postings and job application form for the careers page. Post jobs from the admin, list them on the front end, and collect applications into the database.
 * Version: 1.1.0
 * Author: Krexio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'KREXIO_CAREERS_PATH', plugin_dir_path( __FILE__ ) );
define( 'KREXIO_CAREERS_URL', plugin_dir_url( __FILE__ ) );
define( 'KREXIO_CAREERS_DB_VERSION', '1.1' );

require_once KREXIO_CAREERS_PATH . 'includes/class-job-post-type.php';
require_once KREXIO_CAREERS_PATH . 'includes/class-application-form.php';
require_once KREXIO_CAREERS_PATH . 'includes/class-admin-applications.php';

function krexio_careers_applications_table() {
	global $wpdb;
	return $wpdb->prefix . 'krexio_job_applications';
}

/**
 * Finds the front-end "Apply" page (the page containing the
 * [krexio_job_application] shortcode) so links can point to it
 * without the URL being hardcoded anywhere.
 */
function krexio_get_apply_page_url() {
	$page = get_page_by_path( 'apply' );

	if ( ! $page ) {
		$found = get_posts( array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			's'              => 'krexio_job_application',
		) );
		$page = $found ? $found[0] : null;
	}

	return $page ? get_permalink( $page ) : home_url( '/apply/' );
}

/**
 * Renders the same hero-banner treatment used on Home/About/Services
 * (pill badge + large heading + subtitle, on the site's banner background)
 * for plugin-rendered pages that aren't built in Elementor directly
 * (e.g. the dynamic single Job page).
 */
function krexio_render_banner( $badge, $title, $subtitle = '' ) {
	wp_enqueue_style( 'krexio-careers' );

	$bg_url = wp_get_attachment_image_url( 37, 'full' );
	if ( ! $bg_url ) {
		$bg_url = home_url( '/wp-content/uploads/2026/03/Group-17.png' );
	}

	$html  = '<div class="krexio-banner alignfull" style="background-image:url(' . esc_url( $bg_url ) . ');">';
	$html .= '<div class="krexio-banner-inner">';
	if ( $badge ) {
		$html .= '<span class="krexio-badge">' . esc_html( $badge ) . '</span>';
	}
	$html .= '<h1 class="krexio-banner-title">' . esc_html( $title ) . '</h1>';
	if ( $subtitle ) {
		$html .= '<p class="krexio-banner-subtitle">' . esc_html( $subtitle ) . '</p>';
	}
	$html .= '</div></div>';

	return $html;
}

function krexio_careers_run_migrations() {
	global $wpdb;

	$table_name      = krexio_careers_applications_table();
	$charset_collate = $wpdb->get_charset_collate();

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$sql = "CREATE TABLE $table_name (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		job_id BIGINT UNSIGNED NOT NULL,
		first_name VARCHAR(100) NOT NULL,
		middle_name VARCHAR(100) NOT NULL DEFAULT '',
		last_name VARCHAR(100) NOT NULL,
		address VARCHAR(255) NOT NULL,
		email VARCHAR(190) NOT NULL DEFAULT '',
		phone VARCHAR(30) NOT NULL,
		created_at DATETIME NOT NULL,
		PRIMARY KEY  (id),
		KEY job_id (job_id)
	) $charset_collate;";

	dbDelta( $sql );

	$wpdb->query( "ALTER TABLE $table_name DROP COLUMN IF EXISTS birthday" );

	update_option( 'krexio_careers_db_version', KREXIO_CAREERS_DB_VERSION );
}

function krexio_careers_activate() {
	krexio_careers_run_migrations();
	Krexio_Job_Post_Type::register();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'krexio_careers_activate' );

add_action( 'plugins_loaded', function () {
	if ( get_option( 'krexio_careers_db_version' ) !== KREXIO_CAREERS_DB_VERSION ) {
		krexio_careers_run_migrations();
	}

	Krexio_Job_Post_Type::init();
	Krexio_Application_Form::init();
	Krexio_Admin_Applications::init();
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_register_style( 'krexio-careers', KREXIO_CAREERS_URL . 'assets/css/career.css', array(), filemtime( KREXIO_CAREERS_PATH . 'assets/css/career.css' ) );
} );
