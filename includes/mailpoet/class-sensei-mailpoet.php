<?php
/**
 * File containing the class Sensei_MailPoet.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( \MailPoet\API\API::class ) ) {
	return;
}

/**
 * MailPoet integration class.
 *
 * Handles the integration with the MailPoet plugin,
 * creates a list for each course and group, adds enrolled students.
 *
 * @package Core
 * @since $$next-version$$
 */
class Sensei_MailPoet {

	/**
	 * MailPoet API handle.
	 *
	 * @var object
	 */
	private $mailpoet_api;

	/**
	 * Instance of the current handler.
	 *
	 * @var Sensei_MailPoet
	 */
	private static $instance;

	/**
	 * A list of Sensei Courses and Groups.
	 *
	 * @var array
	 */
	private $sensei_lists;

	/**
	 * All lists on MailPoet.
	 *
	 * @var array
	 */
	private $mail_poet_lists;

	/**
	 * Constructor
	 *
	 * @since 1.9.0
	 */
	public function __construct() {
		if ( class_exists( \MailPoet\API\API::class ) ) {
			$this->mailpoet_api = \MailPoet\API\API::MP( 'v1' );
			if ( $this->mailpoet_api->isSetupComplete() ) {
				add_action( 'init', array( $this, 'maybe_schedule_cron_job' ), 101 );

				add_action( 'sensei_pro_student_groups_group_student_added', array( $this, 'add_student_subscriber' ), 10, 2 );
				add_action( 'sensei_pro_student_groups_group_students_removed', array( $this, 'remove_student_subscribers' ), 10, 2 );

				add_action( 'sensei_course_enrolment_status_changed', array( $this, 'maybe_add_student_course_subscriber' ), 10, 3 );
				add_action( 'sensei_admin_enrol_user', array( $this, 'add_student_subscriber' ), 10, 2 );
				add_action( 'sensei_manual_enrolment_learner_enrolled', array( $this, 'add_student_course_subscriber' ), 10, 2 );
				add_action( 'sensei_manual_enrolment_learner_withdrawn', array( $this, 'remove_student_course_subscriber' ), 10, 2 );
			}
		}
	}

	/**
	 * Get the instance of the class.
	 *
	 * @return Sensei_MailPoet
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Attach job to cron.
	 *
	 * @since $$next-version$$
	 *
	 * @access private
	 * @return void
	 */
	public function maybe_schedule_cron_job() {
		Sensei_Scheduler::instance()->schedule_job( new Sensei_MailPoet_Sync_Job() );
	}

	/**
	 * Sync MailPoet list students with Sensei site courses and groups students.
	 */
	public function sync_subscribers() {
		$sensei_lists   = $this->get_sensei_lists();
		$mailpoet_lists = $this->get_mailpoet_lists();

		foreach ( $sensei_lists as $list ) {
			$list_name = $this->get_list_name( $list['name'], $list['post_type'] );
			// find list in MailPoet lists array. if not exists, create one.
			if ( ! array_key_exists( $list_name, $mailpoet_lists ) ) {
				$mp_list_id = $this->create_list( $list_name, $list['description'] );
			} else {
				$mp_list_id = $mailpoet_lists[ $list_name ]['id'];
			}

			if ( ! empty( $mp_list_id ) ) {
				$students = $this->get_students( $list['id'], $list['post_type'] );
				$this->add_subscribers( $students, $mp_list_id );
			}
		}
	}

	/**
	 * Get all groups and courses in Sensei.
	 */
	public function get_sensei_lists() {
		if ( empty( $this->sensei_lists ) ) {
			$args = array(
				'post_type'        => array( 'group', 'course' ),
				'posts_per_page'   => -1,
				'orderby'          => 'title',
				'order'            => 'ASC',
				'post_status'      => 'any',
				'suppress_filters' => 0,
			);

			$wp_query_obj       = new WP_Query( $args );
			$this->sensei_lists = array_map(
				static function( $post ) {
					return array(
						'id'          => $post->ID,
						'post_type'   => $post->post_type,
						'name'        => $post->post_title,
						'description' => $post->post_excerpt,
					);
				},
				$wp_query_obj->posts
			);
		}

		return $this->sensei_lists;
	}

	/**
	 * Get all lists in MailPoet and use list name as index for easy local searching.
	 */
	public function get_mailpoet_lists() {
		if ( empty( $this->mail_poet_lists ) ) {
			$lists                 = $this->mailpoet_api->getLists();
			$this->mail_poet_lists = array();
			foreach ( $lists as $list ) {
				$this->mail_poet_lists[ $list['name'] ] = $list;
			}
		}
		return $this->mail_poet_lists;
	}

	/**
	 * Get all enrolled students in a course or group.
	 *
	 * @param int    $id The post ID.
	 * @param string $post_type The post type.
	 *
	 * @return array
	 */
	public function get_students( $id, $post_type ) {
		global $wpdb;
		if ( 'group' === $post_type ) {
			if ( class_exists( 'Sensei_Pro_Student_Groups\Repositories\Group_Student_Repository', true ) ) {
				$group_student_repo = new Sensei_Pro_Student_Groups\Repositories\Group_Student_Repository( $wpdb );
				$student_ids        = $group_student_repo->find_group_students( $id );
			}
		}
		if ( 'course' === $post_type ) {
			$students    = get_comments(
				array(
					'post_id' => $id,
					'type'    => 'sensei_course_status',
					'status'  => 'any',
				)
			);
			$student_ids = array_map(
				static function( $student ) {
					return $student->user_id;
				},
				$students
			);
		}
		if ( ! $student_ids ) {
			return array();
		}

		$args = array(
			'include' => $student_ids,
			'fields'  => array( 'id', 'user_email', 'display_name', 'user_nicename' ),
		);
		return get_users( $args );
	}

	/**
	 * Add students as subscribers to lists on MailPoet for courses/groups.
	 *
	 * @param array      $subscribers A list of students belonging to this group/course.
	 *
	 * @param string|int $list_id ID of the list on MailPoet.
	 * @return void
	 */
	public function add_subscribers( $subscribers, $list_id ) {
		$options = array(
			'send_confirmation_email'      => false,
			'schedule_welcome_email'       => false,
			'skip_subscriber_notification' => false,
		);
		foreach ( $subscribers as $subscriber ) {
			$subscriber_data = array(
				'email'      => $subscriber->user_email,
				'first_name' => $subscriber->display_name,
			);

			try {
				// All WordPress users are already on a list 'WordPress Users' on MailPoet.
				$mp_subscriber = $this->mailpoet_api->getSubscriber( $subscriber->user_email );
				$this->mailpoet_api->subscribeToList( $mp_subscriber['id'], $list_id, $options );
			} catch ( \MailPoet\API\MP\v1\APIException $exception ) {
				if ( 4 === $exception->getCode() ) {
					// subscriber does not exist.
					$this->mailpoet_api->addSubscriber( $subscriber_data, array( $list_id ), $options );
				}
			}
		}
	}

	/**
	 * Remove students as subscribers from lists on MailPoet for courses/groups.
	 *
	 * @param array      $subscribers A list of students to remove.
	 *
	 * @param string|int $list_id ID of the list on MailPoet.
	 * @return void
	 */
	private function remove_subscribers( $subscribers, $list_id ) {
		foreach ( $subscribers as $subscriber ) {
			try {
				$mp_subscriber = $this->mailpoet_api->getSubscriber( $subscriber->user_email );
				$this->mailpoet_api->unsubscribeFromList( $mp_subscriber['id'], $list_id );
			} catch ( \MailPoet\API\MP\v1\APIException $exception ) {
				continue;
			}
		}
	}

	/**
	 * Delete lists on MailPoet for courses/groups that don't exist on Sensei anymore.
	 *
	 * @param array $list_ids An array of list IDs to be removed from MailPoet.
	 *
	 * @return void
	 */
	private function delete_lists( $list_ids ) {
		foreach ( $list_ids as $list_id ) {
			$this->mailpoet_api->deleteList( $list_id );
		}
	}

	/**
	 * Generates a Sensei LMS prefixed name for a MailPoet list.
	 *
	 * @param string $name The name of the course or group.
	 *
	 * @param string $post_type The post type: course or group.
	 *
	 * @return string
	 */
	public function get_list_name( $name, $post_type ) {
		return 'Sensei LMS ' . ucfirst( $post_type ) . ': ' . $name;
	}

	/**
	 * Creates a Sensei LMS MailPoet list with name and description.
	 *
	 * @param string $list_name The name of the list.
	 * @param string $list_description The description of the list.
	 *
	 * @return int|string|null
	 */
	public function create_list( $list_name, $list_description ) {
		$new_list = array(
			'name'        => $list_name,
			'description' => $list_description,
		);
		try {
			$new_list = $this->mailpoet_api->addList( $new_list );
			return $new_list['id'];
		} catch ( \MailPoet\API\MP\v1\APIException $exception ) {
			// see https://github.com/mailpoet/mailpoet/blob/trunk/doc/api_methods/AddList.md#error-handling.
			return null;
		}
	}

	/**
	 * Add a student as a subscriber to a list on MailPoet for a course or group.
	 * If the list does not exist, it will be created.
	 *
	 * @param int $post_id Post ID.
	 * @param int $user_id User ID.
	 *
	 * @return void
	 */
	public function add_student_subscriber( $post_id, $user_id ) {
		$student = get_user_by( 'id', $user_id );
		$post    = get_post( $post_id );
		if ( ! $student || ! $post ) {
			return;
		}

		$mailpoet_lists = $this->get_mailpoet_lists();
		$list_name      = $this->get_list_name( $post->post_title, 'group' );
		if ( ! array_key_exists( $list_name, $mailpoet_lists ) ) {
			$mp_list_id = $this->create_list( $list_name, $post->post_excerpt );
		} else {
			$mp_list_id = $mailpoet_lists[ $list_name ]['id'];
		}
		if ( null !== $mp_list_id ) {
			$this->add_subscribers( array( $student ), $mp_list_id );
		}
	}

	/**
	 * Remove students as subscribers from lists on MailPoet for courses/groups.
	 * This function is used when a student is removed from a group or course.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $user_ids Array of User IDs.
	 *
	 * @return void
	 */
	public function remove_student_subscribers( $post_id, $user_ids ) {
		$students = get_users( array( 'include' => $user_ids ) );
		$post     = get_post( $post_id );
		if ( ! $students || ! $post ) {
			return;
		}

		$mailpoet_lists = $this->get_mailpoet_lists();
		$list_name      = $this->get_list_name( $post->post_title, 'group' );

		if ( ! array_key_exists( $list_name, $mailpoet_lists ) ) {
			$mp_list_id = $this->create_list( $list_name, $post->post_excerpt );
		} else {
			$mp_list_id = $mailpoet_lists[ $list_name ]['id'];
		}
		if ( null !== $mp_list_id ) {
			$this->remove_subscribers( $students, $mp_list_id );
		}
	}

	/**
	 * Decides whether to add or remove a student as subscriber to a course list on MailPoet. A proxy to SenseiMailPoet::add_student_subscriber and SenseiMailPoet::remove_student_subscribers.
	 *
	 * @param int  $user_id User ID.
	 * @param int  $course_id Post ID.
	 * @param bool $is_enrolled Enrollment status.
	 *
	 * @return void
	 */
	public function maybe_add_student_course_subscriber( $user_id, $course_id, $is_enrolled ) {
		if ( $is_enrolled ) {
			$this->add_student_subscriber( $course_id, $user_id );
		} else {
			$this->remove_student_subscribers( $course_id, array( $user_id ) );
		}
	}

	/**
	 * Remove student as subscriber to a course list on MailPoet. A proxy to SenseiMailPoet::remove_student_subscribers.
	 *
	 * @param int $user_id User ID.
	 * @param int $course_id Post ID.
	 *
	 * @return void
	 */
	public function remove_student_course_subscriber( $user_id, $course_id ) {
		$this->remove_student_subscribers( $course_id, array( $user_id ) );
	}

	/**
	 * Add student as subscriber to a course list on MailPoet. A proxy to SenseiMailPoet::add_student_subscriber.
	 *
	 * @param int $user_id User ID.
	 * @param int $course_id Post ID.
	 *
	 * @return void
	 */
	public function add_student_course_subscriber( $user_id, $course_id ) {
		$this->add_student_subscriber( $course_id, $user_id );
	}
}
