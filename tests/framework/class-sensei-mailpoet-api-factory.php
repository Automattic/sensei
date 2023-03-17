<?php
// phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- Mocking an external library.
// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound -- Using PHPUnit conventions.

/**
 * File that adds mocks for mailpoet objects.
 *
 * @package sensei-tests
 */
/**
 * Stub to instantiate the MailPoet API object.
 *
 * @since $$next-version$$
 */
class Sensei_MailPoetAPIFactory {
	/**
	 * Instance of the current handler.
	 */
	private static $instance;
	/**
	 * Mock MP static method.
	 */
	public static function MP() {
		return self::get_instance();
	}

	/**
	 * Get the singleton instance of MailPoet API.
	 *
	 * @return Sensei_MailPoetMockAPI_Test
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new Sensei_MailPoetMockAPI_Test();
		}

		return self::$instance;
	}
}

/**
 * Stub to mock the MailPoet API object.
 *
 * @since $$next-version$$
 */
class Sensei_MailPoetMockAPI_Test {
	public $lists;
	public $subscribers;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->lists       = array(
			0 =>
				array(
					'id'          => '533',
					'name'        => 'Sensei LMS Course: A new course',
					'type'        => 'default',
					'description' => '',
					'created_at'  => '2023-03-14 13:42:54',
					'updated_at'  => '2023-03-14 13:42:54',
					'deleted_at'  => null,
				),
			1 =>
				array(
					'id'          => '534',
					'name'        => 'Sensei LMS Course: Becoming a Content Creator',
					'type'        => 'default',
					'description' => '',
					'created_at'  => '2023-03-14 13:42:54',
					'updated_at'  => '2023-03-14 13:42:54',
					'deleted_at'  => null,
				),
			2 =>
				array(
					'id'          => '536',
					'name'        => 'Sensei LMS Course: How to be famous',
					'type'        => 'default',
					'description' => '',
					'created_at'  => '2023-03-14 13:42:54',
					'updated_at'  => '2023-03-14 13:42:54',
					'deleted_at'  => null,
				),
			3 =>
				array(
					'id'          => '537',
					'name'        => 'Sensei LMS Course: Life 101',
					'type'        => 'default',
					'description' => '',
					'created_at'  => '2023-03-14 13:42:54',
					'updated_at'  => '2023-03-14 13:42:54',
					'deleted_at'  => null,
				),
		);
		$this->subscribers = array();
	}

	/**
	 * Mock isSetupComplete method.
	 */
	public function isSetupComplete() {
		return true;
	}

	/**
	 * Mock getLists method.
	 */
	public function getLists() {
		return $this->lists;
	}

	/**
	 * Mock addList method.
	 */
	public function addList( $list ) {
		$new_list      = array(
			'id'          => rand( 100, 500 ),
			'name'        => $list['name'],
			'description' => $list['description'],
		);
		$this->lists[] = $new_list;
		return $new_list;
	}

	/**
	 * Mock getSubscriber method.
	 */
	public function getSubscriber( $email ) {
		foreach ( $this->subscribers as $subscriber ) {
			if ( $subscriber['email'] == $email ) {
				return $subscriber;
			}
		}

		$id                       = rand( 10, 50 );
		$subscriber               = array(
			'id'         => $id,
			'email'      => $email,
			'first_name' => 'John',
			'last_name'  => 'Doe',
			'list_ids'   => array(),
		);
		$this->subscribers[ $id ] = $subscriber;
		return $subscriber;
	}

	/**
	 * Mock subscribeToList method.
	 */
	public function subscribeToList( $id, $list_id, $options ) {
		$this->subscribers[ $id ]['list_ids'][ $list_id ] = true;
		return true;
	}

	/**
	 * Mock unsubscribeFromList method.
	 */
	public function unsubscribeFromList( $id, $list_id ) {
		$this->subscribers[ $id ]['list_ids'][ $list_id ] = false;
		return true;
	}

	/**
	 * Mock getSubscribers method.
	 */
	public function getSubscribers( $args ) {
		$list_id     = $args['listId'];
		$subscribers = array();
		foreach ( $this->subscribers as $subscriber ) {
			if ( isset( $subscriber['list_ids'][ $list_id ] ) && $subscriber['list_ids'][ $list_id ] ) {
				$subscribers[] = $subscriber;
			}
		}
		return $subscribers;
	}
}
