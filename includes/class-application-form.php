<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Krexio_Application_Form {

	public static function init() {
		add_shortcode( 'krexio_career_listings', array( __CLASS__, 'render_listings' ) );
		add_shortcode( 'krexio_job_application', array( __CLASS__, 'render_application_form' ) );
		add_action( 'init', array( __CLASS__, 'maybe_handle_submission' ) );
	}

	private static function enqueue_style() {
		wp_enqueue_style( 'krexio-careers' );
	}

	/**
	 * [krexio_career_listings]
	 * Lists open job positions by title only; each links to the job's
	 * single page for the full description and the Apply button.
	 */
	public static function render_listings( $atts ) {
		self::enqueue_style();

		$jobs = get_posts( array(
			'post_type'      => Krexio_Job_Post_Type::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'     => '_krexio_job_open',
					'value'   => '0',
					'compare' => '!=',
				),
			),
		) );

		if ( empty( $jobs ) ) {
			return '<p class="krexio-no-jobs">There are no open positions right now. Please check back soon.</p>';
		}

		ob_start();
		echo '<div class="krexio-career-listings">';
		foreach ( $jobs as $job ) {
			?>
			<div class="krexio-job-card">
				<h3 class="krexio-job-title">
					<a href="<?php echo esc_url( get_permalink( $job ) ); ?>"><?php echo esc_html( get_the_title( $job ) ); ?></a>
				</h3>
			</div>
			<?php
		}
		echo '</div>';
		return ob_get_clean();
	}

	/**
	 * [krexio_job_application]
	 * Reads ?job_id= from the URL and renders the application form for that job.
	 */
	public static function render_application_form( $atts ) {
		self::enqueue_style();

		$job_id = isset( $_GET['job_id'] ) ? absint( $_GET['job_id'] ) : 0;
		$job    = $job_id ? get_post( $job_id ) : null;

		if ( ! $job || $job->post_type !== Krexio_Job_Post_Type::POST_TYPE || $job->post_status !== 'publish' ) {
			return '<p class="krexio-error">We could not find that job posting. Please go back to the careers page and choose a position.</p>';
		}

		if ( isset( $_GET['krexio_applied'] ) ) {
			return '<p class="krexio-success">Thank you for applying for <strong>' . esc_html( get_the_title( $job ) ) . '</strong>. We have received your application and will be in touch.</p>';
		}

		$errors = get_transient( 'krexio_app_errors_' . get_current_user_id() . '_' . $job_id );
		delete_transient( 'krexio_app_errors_' . get_current_user_id() . '_' . $job_id );

		ob_start();
		?>
		<div class="krexio-page-content krexio-application-form">
			<h3>Apply for: <?php echo esc_html( get_the_title( $job ) ); ?></h3>

			<?php if ( ! empty( $errors ) ) : ?>
				<div class="krexio-error">
					<ul>
						<?php foreach ( $errors as $error ) : ?>
							<li><?php echo esc_html( $error ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<form method="post">
				<?php wp_nonce_field( 'krexio_job_application_submit', 'krexio_job_application_nonce' ); ?>
				<input type="hidden" name="krexio_job_id" value="<?php echo esc_attr( $job_id ); ?>">
				<input type="hidden" name="krexio_apply_page" value="<?php echo esc_url( get_permalink() ); ?>">
				<input type="hidden" name="action" value="krexio_submit_application">

				<p>
					<label for="krexio_first_name">First Name *</label><br>
					<input type="text" id="krexio_first_name" name="krexio_first_name" required>
				</p>
				<p>
					<label for="krexio_middle_name">Middle Name</label><br>
					<input type="text" id="krexio_middle_name" name="krexio_middle_name">
				</p>
				<p>
					<label for="krexio_last_name">Last Name *</label><br>
					<input type="text" id="krexio_last_name" name="krexio_last_name" required>
				</p>
				<p>
					<label for="krexio_address">Address *</label><br>
					<input type="text" id="krexio_address" name="krexio_address" required>
				</p>
				<p>
					<label for="krexio_phone">Phone Number *</label><br>
					<input type="tel" id="krexio_phone" name="krexio_phone" required>
				</p>
				<p>
					<label for="krexio_email">Email *</label><br>
					<input type="email" id="krexio_email" name="krexio_email" required>
				</p>

				<p>
					<button type="submit" class="krexio-apply-btn">Submit Application</button>
				</p>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function maybe_handle_submission() {
		if ( empty( $_POST['action'] ) || $_POST['action'] !== 'krexio_submit_application' ) {
			return;
		}

		if ( ! isset( $_POST['krexio_job_application_nonce'] ) ||
			! wp_verify_nonce( $_POST['krexio_job_application_nonce'], 'krexio_job_application_submit' ) ) {
			wp_die( 'Security check failed. Please go back and try again.' );
		}

		$job_id = absint( $_POST['krexio_job_id'] ?? 0 );
		$job    = get_post( $job_id );

		if ( ! $job || $job->post_type !== Krexio_Job_Post_Type::POST_TYPE || $job->post_status !== 'publish' ) {
			wp_die( 'Invalid job posting.' );
		}

		$first_name  = sanitize_text_field( $_POST['krexio_first_name'] ?? '' );
		$middle_name = sanitize_text_field( $_POST['krexio_middle_name'] ?? '' );
		$last_name   = sanitize_text_field( $_POST['krexio_last_name'] ?? '' );
		$address     = sanitize_text_field( $_POST['krexio_address'] ?? '' );
		$phone       = sanitize_text_field( $_POST['krexio_phone'] ?? '' );
		$email       = sanitize_email( $_POST['krexio_email'] ?? '' );

		$errors = array();

		if ( '' === $first_name ) {
			$errors[] = 'First name is required.';
		}
		if ( '' === $last_name ) {
			$errors[] = 'Last name is required.';
		}
		if ( '' === $address ) {
			$errors[] = 'Address is required.';
		}
		if ( '' === $phone ) {
			$errors[] = 'Phone number is required.';
		}
		if ( '' === $email || ! is_email( $email ) ) {
			$errors[] = 'Please provide a valid email address.';
		}

		$apply_page = wp_validate_redirect( $_POST['krexio_apply_page'] ?? '', home_url( '/' ) );
		$redirect   = add_query_arg( 'job_id', $job_id, $apply_page );

		if ( ! empty( $errors ) ) {
			set_transient( 'krexio_app_errors_' . get_current_user_id() . '_' . $job_id, $errors, 60 );
			wp_safe_redirect( $redirect );
			exit;
		}

		global $wpdb;
		$wpdb->insert(
			krexio_careers_applications_table(),
			array(
				'job_id'      => $job_id,
				'first_name'  => $first_name,
				'middle_name' => $middle_name,
				'last_name'   => $last_name,
				'address'     => $address,
				'phone'       => $phone,
				'email'       => $email,
				'created_at'  => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		wp_safe_redirect( add_query_arg( 'krexio_applied', '1', $redirect ) );
		exit;
	}
}
