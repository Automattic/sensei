<?php
/**
 * File containing the Submission_Repository_Factory class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Quiz_Submission\Submission\Repositories;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Submission_Repository_Factory.
 *
 * @internal
 *
 * @since 4.7.2
 */
class Submission_Repository_Factory {
	/**
	 * Create a repository for the quiz submissions.
	 *
	 * @internal
	 *
	 * @return Submission_Repository_Interface
	 */
	public function create(): Submission_Repository_Interface {
		global $wpdb;

		return new Aggregate_Submission_Repository(
			new Comments_Based_Submission_Repository(),
			new Tables_Based_Submission_Repository( $wpdb ),
			true
		);
	}
}
