<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * One-click admin action that gives the Career and Apply pages the same
 * hero-banner treatment already used on Home/About/Services (pill badge,
 * big heading, subtitle, on the same banner background image) — built in
 * Elementor's own data format, so it looks native to the site.
 */
class Krexio_Banner_Styling {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_post_krexio_apply_banner_styling', array( __CLASS__, 'handle_apply' ) );
	}

	public static function add_menu() {
		add_submenu_page(
			'edit.php?post_type=' . Krexio_Job_Post_Type::POST_TYPE,
			'Banner Styling',
			'Banner Styling',
			'manage_options',
			'krexio-banner-styling',
			array( __CLASS__, 'render_page' )
		);
	}

	private static function find_page_by_shortcode( $shortcode_tag ) {
		$pages = get_posts( array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		) );

		foreach ( $pages as $page ) {
			if ( has_shortcode( $page->post_content, $shortcode_tag ) ) {
				return $page;
			}
		}

		return null;
	}

	/**
	 * Finds the background image already used by an existing banner section
	 * (on Home/About/Services, whichever has one) so the new pages reuse the
	 * exact same asset instead of guessing an attachment ID or URL.
	 */
	private static function find_banner_background_image() {
		$pages = get_posts( array(
			'post_type'      => array( 'page' ),
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_key'       => '_elementor_data',
		) );

		foreach ( $pages as $page ) {
			$raw = get_post_meta( $page->ID, '_elementor_data', true );
			if ( ! $raw ) {
				continue;
			}

			$data = json_decode( $raw, true );
			if ( ! is_array( $data ) ) {
				continue;
			}

			foreach ( $data as $element ) {
				if (
					isset( $element['elType'], $element['settings']['css_classes'], $element['settings']['background_image']['url'] )
					&& 'container' === $element['elType']
					&& false !== strpos( $element['settings']['css_classes'], 'banner' )
				) {
					return $element['settings']['background_image'];
				}
			}
		}

		return null;
	}

	private static function id() {
		return substr( md5( uniqid( '', true ) ), 0, 7 );
	}

	private static function badge_widget( $text ) {
		return array(
			'id'         => self::id(),
			'elType'     => 'widget',
			'widgetType' => 'heading',
			'settings'   => array(
				'title'                       => $text,
				'typography_typography'       => 'custom',
				'typography_font_family'      => 'eudoxus sans',
				'typography_font_size'        => array( 'unit' => 'px', 'size' => 14, 'sizes' => array() ),
				'typography_font_weight'      => '500',
				'title_color'                 => '#FFFFFF',
				'_margin'                     => array( 'unit' => 'px', 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'isLinked' => false ),
				'_padding'                    => array( 'unit' => 'px', 'top' => '12', 'right' => '16', 'bottom' => '12', 'left' => '16', 'isLinked' => false ),
				'_background_background'     => 'gradient',
				'_background_color'          => '#B392FF',
				'_background_color_b'        => '#86A8FF',
				'_background_gradient_angle' => array( 'unit' => 'deg', 'size' => 90, 'sizes' => array() ),
				'_border_radius'              => array( 'unit' => 'px', 'top' => '100', 'right' => '100', 'bottom' => '100', 'left' => '100', 'isLinked' => true ),
			),
			'elements' => array(),
		);
	}

	private static function heading_widget( $text ) {
		return array(
			'id'         => self::id(),
			'elType'     => 'widget',
			'widgetType' => 'heading',
			'settings'   => array(
				'title'                       => $text,
				'align'                       => 'center',
				'typography_typography'       => 'custom',
				'typography_font_family'      => 'Molend',
				'typography_font_weight'      => '400',
				'typography_font_size'        => array( 'unit' => 'px', 'size' => 48, 'sizes' => array() ),
				'typography_font_size_tablet' => array( 'unit' => 'px', 'size' => 36, 'sizes' => array() ),
				'typography_font_size_mobile' => array( 'unit' => 'px', 'size' => 26, 'sizes' => array() ),
				'typography_line_height'      => array( 'unit' => 'em', 'size' => 1.2, 'sizes' => array() ),
				'title_color'                 => '#1B2A4A',
			),
			'elements' => array(),
		);
	}

	private static function subtitle_widget( $text ) {
		return array(
			'id'         => self::id(),
			'elType'     => 'widget',
			'widgetType' => 'text-editor',
			'settings'   => array(
				'editor'                 => '<p>' . esc_html( $text ) . '</p>',
				'align'                  => 'center',
				'typography_typography'  => 'custom',
				'typography_font_family' => 'eudoxus sans',
				'typography_font_size'   => array( 'unit' => 'px', 'size' => 18, 'sizes' => array() ),
				'typography_font_weight' => '500',
				'text_color'             => '#2F2C3D',
			),
			'elements' => array(),
		);
	}

	private static function build_page_data( $badge, $title, $subtitle, $shortcode, $background_image ) {
		$banner_settings = array(
			'flex_direction'   => 'column',
			'flex_align_items' => 'center',
			'padding'          => array( 'unit' => 'px', 'top' => '220', 'right' => '0', 'bottom' => '180', 'left' => '0', 'isLinked' => false ),
			'padding_tablet'   => array( 'unit' => 'px', 'top' => '150', 'right' => '0', 'bottom' => '140', 'left' => '0', 'isLinked' => false ),
			'padding_mobile'   => array( 'unit' => 'px', 'top' => '130', 'right' => '10', 'bottom' => '80', 'left' => '10', 'isLinked' => false ),
			'css_classes'      => 'banner',
			'min_height'       => array( 'unit' => 'vh', 'size' => 100, 'sizes' => array() ),
		);

		if ( $background_image ) {
			$banner_settings['background_background'] = 'classic';
			$banner_settings['background_image']       = $background_image;
			$banner_settings['background_size']         = 'cover';
		}

		return array(
			array(
				'id'       => self::id(),
				'elType'   => 'container',
				'settings' => $banner_settings,
				'elements' => array(
					self::badge_widget( $badge ),
					self::heading_widget( $title ),
					self::subtitle_widget( $subtitle ),
				),
				'isInner'  => false,
			),
			array(
				'id'       => self::id(),
				'elType'   => 'container',
				'settings' => array(
					'content_width' => 'boxed',
					'padding'       => array( 'unit' => 'px', 'top' => '80', 'right' => '20', 'bottom' => '120', 'left' => '20', 'isLinked' => false ),
				),
				'elements' => array(
					array(
						'id'         => self::id(),
						'elType'     => 'widget',
						'widgetType' => 'shortcode',
						'settings'   => array( 'shortcode' => $shortcode ),
						'elements'   => array(),
					),
				),
				'isInner'  => false,
			),
		);
	}

	private static function apply_to_page( $page, $badge, $title, $subtitle, $background_image ) {
		$shortcode = trim( $page->post_content );
		$data      = self::build_page_data( $badge, $title, $subtitle, $shortcode, $background_image );

		update_post_meta( $page->ID, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
		update_post_meta( $page->ID, '_elementor_edit_mode', 'builder' );
		update_post_meta( $page->ID, '_elementor_template_type', 'wp-page' );
		update_post_meta( $page->ID, '_elementor_version', defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '3.35.9' );
		update_post_meta( $page->ID, '_elementor_page_settings', array( 'hide_title' => 'yes' ) );
	}

	public static function render_page() {
		$career_page = self::find_page_by_shortcode( 'krexio_career_listings' );
		$apply_page  = self::find_page_by_shortcode( 'krexio_job_application' );
		$bg_image    = self::find_banner_background_image();
		?>
		<div class="wrap">
			<h1>Banner Styling</h1>
			<p>This gives the Career and Apply pages the same hero-banner look as Home/About/Services (pill badge, heading, background), reusing the same background image automatically.</p>

			<table class="widefat" style="max-width:700px;margin-bottom:1.5em;">
				<tr>
					<td>Career page</td>
					<td><?php echo $career_page ? esc_html( get_the_title( $career_page ) ) . ' (found)' : '<strong style="color:#a00;">Not found</strong> — make sure a published page contains [krexio_career_listings]'; ?></td>
				</tr>
				<tr>
					<td>Apply page</td>
					<td><?php echo $apply_page ? esc_html( get_the_title( $apply_page ) ) . ' (found)' : '<strong style="color:#a00;">Not found</strong> — make sure a published page contains [krexio_job_application]'; ?></td>
				</tr>
				<tr>
					<td>Banner background image</td>
					<td><?php echo $bg_image ? 'Found an existing banner to match' : '<strong style="color:#a00;">Not found</strong> — no existing page has a banner section yet, so a plain gradient will be used instead'; ?></td>
				</tr>
			</table>

			<?php if ( $career_page || $apply_page ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('This replaces the current content of the Career and Apply pages with the banner layout. Continue?');">
					<?php wp_nonce_field( 'krexio_apply_banner_styling' ); ?>
					<input type="hidden" name="action" value="krexio_apply_banner_styling">
					<button type="submit" class="button button-primary">Apply Banner Styling</button>
				</form>
			<?php endif; ?>
		</div>
		<?php
	}

	public static function handle_apply() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'krexio_apply_banner_styling' ) ) {
			wp_die( 'Security check failed.' );
		}

		$bg_image = self::find_banner_background_image();

		$career_page = self::find_page_by_shortcode( 'krexio_career_listings' );
		if ( $career_page ) {
			self::apply_to_page(
				$career_page,
				'Careers',
				'Join Our Team',
				'We are always looking for talented people to help us build intelligent solutions that drive real results. Explore our open positions below.',
				$bg_image
			);
		}

		$apply_page = self::find_page_by_shortcode( 'krexio_job_application' );
		if ( $apply_page ) {
			self::apply_to_page(
				$apply_page,
				'Careers',
				'Apply Now',
				'Fill out the form below and our team will be in touch.',
				$bg_image
			);
		}

		if ( class_exists( '\Elementor\Plugin' ) ) {
			\Elementor\Plugin::$instance->files_manager->clear_cache();
		}

		wp_safe_redirect( admin_url( 'edit.php?post_type=' . Krexio_Job_Post_Type::POST_TYPE . '&page=krexio-banner-styling&krexio_banner_applied=1' ) );
		exit;
	}
}
