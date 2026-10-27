<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Krexio_Admin_Applications {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_post_krexio_delete_application', array( __CLASS__, 'handle_delete' ) );
	}

	public static function add_menu() {
		add_submenu_page(
			'edit.php?post_type=' . Krexio_Job_Post_Type::POST_TYPE,
			'Applications',
			'Applications',
			'edit_posts',
			'krexio-job-applications',
			array( __CLASS__, 'render_page' )
		);
	}

	public static function render_page() {
		global $wpdb;
		$table = krexio_careers_applications_table();

		$job_filter = isset( $_GET['job_id'] ) ? absint( $_GET['job_id'] ) : 0;

		$jobs = get_posts( array(
			'post_type'      => Krexio_Job_Post_Type::POST_TYPE,
			'post_status'    => 'any',
			'posts_per_page' => -1,
		) );

		if ( $job_filter ) {
			$applications = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE job_id = %d ORDER BY created_at DESC", $job_filter ) );
		} else {
			$applications = $wpdb->get_results( "SELECT * FROM $table ORDER BY created_at DESC" );
		}

		?>
		<div class="wrap">
			<h1>Job Applications</h1>

			<form method="get" style="margin: 1em 0;">
				<input type="hidden" name="post_type" value="<?php echo esc_attr( Krexio_Job_Post_Type::POST_TYPE ); ?>">
				<input type="hidden" name="page" value="krexio-job-applications">
				<label for="job_id">Filter by job:</label>
				<select name="job_id" id="job_id" onchange="this.form.submit()">
					<option value="0">All jobs</option>
					<?php foreach ( $jobs as $job ) : ?>
						<option value="<?php echo esc_attr( $job->ID ); ?>" <?php selected( $job_filter, $job->ID ); ?>>
							<?php echo esc_html( get_the_title( $job ) ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</form>

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th>Applied</th>
						<th>Job</th>
						<th>Name</th>
						<th>Email</th>
						<th>Phone</th>
						<th>Address</th>
						<th>Action</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $applications ) ) : ?>
						<tr><td colspan="7">No applications yet.</td></tr>
					<?php else : ?>
						<?php foreach ( $applications as $app ) : ?>
							<tr>
								<td><?php echo esc_html( mysql2date( 'M j, Y g:i a', $app->created_at ) ); ?></td>
								<td><?php echo esc_html( get_the_title( $app->job_id ) ?: '(deleted job)' ); ?></td>
								<td><?php echo esc_html( trim( $app->first_name . ' ' . $app->middle_name . ' ' . $app->last_name ) ); ?></td>
								<td><?php echo esc_html( $app->email ); ?></td>
								<td><?php echo esc_html( $app->phone ); ?></td>
								<td><?php echo esc_html( $app->address ); ?></td>
								<td>
									<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=krexio_delete_application&id=' . $app->id ), 'krexio_delete_application_' . $app->id ) ); ?>"
									   onclick="return confirm('Delete this application?');">Delete</a>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	public static function handle_delete() {
		$id = absint( $_GET['id'] ?? 0 );

		if ( ! current_user_can( 'edit_posts' ) ||
			! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'krexio_delete_application_' . $id ) ) {
			wp_die( 'Security check failed.' );
		}

		global $wpdb;
		$wpdb->delete( krexio_careers_applications_table(), array( 'id' => $id ), array( '%d' ) );

		wp_safe_redirect( admin_url( 'edit.php?post_type=' . Krexio_Job_Post_Type::POST_TYPE . '&page=krexio-job-applications' ) );
		exit;
	}
}
