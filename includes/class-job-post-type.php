<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Krexio_Job_Post_Type {

	const POST_TYPE = 'krexio_job';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_box' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save_meta' ) );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( __CLASS__, 'admin_columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'admin_column_content' ), 10, 2 );
		add_filter( 'the_content', array( __CLASS__, 'render_single_job_content' ) );
		add_filter( 'hello_elementor_page_title', array( __CLASS__, 'maybe_hide_default_title' ) );
	}

	/**
	 * The theme's generic "page-header" title is replaced by the branded
	 * banner below, so hide it on single job pages.
	 */
	public static function maybe_hide_default_title( $show ) {
		if ( is_singular( self::POST_TYPE ) ) {
			return false;
		}
		return $show;
	}

	/**
	 * On the single job page: show a hero banner matching the rest of the
	 * site (Home/About/Services), then the description and an Apply button.
	 */
	public static function render_single_job_content( $content ) {
		if ( ! is_singular( self::POST_TYPE ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$post_id    = get_the_ID();
		$location   = get_post_meta( $post_id, '_krexio_job_location', true );
		$department = get_post_meta( $post_id, '_krexio_job_department', true );
		$job_type   = get_post_meta( $post_id, '_krexio_job_type', true );
		$apply_url  = add_query_arg( 'job_id', $post_id, krexio_get_apply_page_url() );

		wp_enqueue_style( 'krexio-careers' );

		$meta_line = implode( ' · ', array_filter( array( $department, $location, $job_type ) ) );

		$html  = krexio_render_banner( $department ?: 'Careers', get_the_title( $post_id ), $meta_line );
		$html .= '<div class="krexio-page-content krexio-job-single">';
		$html .= '<div class="krexio-job-desc">' . $content . '</div>';
		$html .= '<a class="krexio-apply-btn" href="' . esc_url( $apply_url ) . '">Apply Now</a>';
		$html .= '</div>';

		return $html;
	}

	public static function register() {
		register_post_type( self::POST_TYPE, array(
			'labels'       => array(
				'name'               => 'Job Postings',
				'singular_name'      => 'Job Posting',
				'add_new_item'       => 'Add New Job Posting',
				'edit_item'          => 'Edit Job Posting',
				'all_items'          => 'All Job Postings',
				'menu_name'          => 'Jobs',
			),
			'public'       => true,
			'has_archive'  => false,
			'rewrite'      => array( 'slug' => 'job' ),
			'menu_icon'    => 'dashicons-businessman',
			'supports'     => array( 'title', 'editor' ),
			'show_in_rest' => true,
		) );
	}

	public static function add_meta_box() {
		add_meta_box(
			'krexio_job_details',
			'Job Details',
			array( __CLASS__, 'render_meta_box' ),
			self::POST_TYPE,
			'side',
			'default'
		);
	}

	public static function render_meta_box( $post ) {
		wp_nonce_field( 'krexio_job_details_save', 'krexio_job_details_nonce' );

		$location   = get_post_meta( $post->ID, '_krexio_job_location', true );
		$department = get_post_meta( $post->ID, '_krexio_job_department', true );
		$job_type   = get_post_meta( $post->ID, '_krexio_job_type', true );
		$is_open    = get_post_meta( $post->ID, '_krexio_job_open', true );
		if ( '' === $is_open ) {
			$is_open = '1';
		}
		?>
		<p>
			<label for="krexio_job_location"><strong>Location</strong></label><br>
			<input type="text" id="krexio_job_location" name="krexio_job_location" class="widefat" value="<?php echo esc_attr( $location ); ?>">
		</p>
		<p>
			<label for="krexio_job_department"><strong>Department</strong></label><br>
			<input type="text" id="krexio_job_department" name="krexio_job_department" class="widefat" value="<?php echo esc_attr( $department ); ?>">
		</p>
		<p>
			<label for="krexio_job_type"><strong>Job Type</strong></label><br>
			<select id="krexio_job_type" name="krexio_job_type" class="widefat">
				<?php foreach ( array( 'Full-time', 'Part-time', 'Contract', 'Internship' ) as $type ) : ?>
					<option value="<?php echo esc_attr( $type ); ?>" <?php selected( $job_type, $type ); ?>><?php echo esc_html( $type ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label>
				<input type="checkbox" name="krexio_job_open" value="1" <?php checked( $is_open, '1' ); ?>>
				Currently accepting applications
			</label>
		</p>
		<?php
	}

	public static function save_meta( $post_id ) {
		if ( ! isset( $_POST['krexio_job_details_nonce'] ) ||
			! wp_verify_nonce( $_POST['krexio_job_details_nonce'], 'krexio_job_details_save' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		update_post_meta( $post_id, '_krexio_job_location', sanitize_text_field( $_POST['krexio_job_location'] ?? '' ) );
		update_post_meta( $post_id, '_krexio_job_department', sanitize_text_field( $_POST['krexio_job_department'] ?? '' ) );
		update_post_meta( $post_id, '_krexio_job_type', sanitize_text_field( $_POST['krexio_job_type'] ?? '' ) );
		update_post_meta( $post_id, '_krexio_job_open', isset( $_POST['krexio_job_open'] ) ? '1' : '0' );
	}

	public static function admin_columns( $columns ) {
		$columns['krexio_job_location'] = 'Location';
		$columns['krexio_job_type']     = 'Type';
		$columns['krexio_job_open']     = 'Open?';
		return $columns;
	}

	public static function admin_column_content( $column, $post_id ) {
		switch ( $column ) {
			case 'krexio_job_location':
				echo esc_html( get_post_meta( $post_id, '_krexio_job_location', true ) );
				break;
			case 'krexio_job_type':
				echo esc_html( get_post_meta( $post_id, '_krexio_job_type', true ) );
				break;
			case 'krexio_job_open':
				echo get_post_meta( $post_id, '_krexio_job_open', true ) === '0' ? 'Closed' : 'Open';
				break;
		}
	}
}
