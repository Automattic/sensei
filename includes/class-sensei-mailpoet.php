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
	exit;
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
				add_action( 'plugins_loaded', array( $this, 'sync_subscribers' ) );
			}
		}
	}

	/**
	 * Sync MailPoet list students with Sensei site courses and groups students.
	 */
	public function sync_subscribers() {
		$sensei_lists   = $this->get_sensei_lists();
		$mailpoet_lists = $this->get_mailpoet_lists();

		foreach ( $sensei_lists as $list ) {
			// find list in mailpoet lists array. if not exists, create one.
			if ( ! array_key_exists( $list['name'], $mailpoet_lists ) ) {
				$new_list = array(
					'name'        => $list['name'],
					'description' => $list['description'],
				);
				try {
					$new_list_resp = $this->mailpoet_api->addList( $new_list );
					if ( isset( $new_list_resp['id'] ) ) {
						$mp_list_id = $new_list_resp['id'];
					}
				} catch ( \MailPoet\API\MP\v1\APIException $exception ) {
					if ( $exception->getCode() !== 15 ) {
						// 15 means list already exists, other codes mean list was not inserted.
						// see https://github.com/mailpoet/mailpoet/blob/trunk/doc/api_methods/AddList.md#error-handling.
						continue;
					}
				}
			} else {
				$mp_list_id = $mailpoet_lists[ $list['name'] ]['id'];
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

				$args = array(
					'include' => $student_ids,
					'fields'  => array( 'id', 'user_email', 'display_name', 'user_nicename' ),
				);
				return get_users( $args );
			}
		}
		if ( 'course' === $post_type ) {
			$students_count = get_comments(
				array(
					'post_id' => $id,
					'type'    => 'sensei_course_status',
					'status'  => 'any',
				),
				true
			);
		}
		return array();
	}

	/**
	 * Add students as subscribers to lists on MailPoet for courses/groups.
	 *
	 * @param array      $subscribers A list of students belonging to this group/course.
	 *
	 * @param string|int $list_id ID of the list on MailPoet.
	 * @return void
	 */
	private function add_subscribers( $subscribers, $list_id ) {
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
}
