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
 * @since 4.13.0
 */
class Sensei_MailPoet_API_Factory {
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
 * @since 4.13.0
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
					'name'        => 'A new course',
					'type'        => 'default',
					'description' => '',
					'created_at'  => '2023-03-14 13:42:54',
					'updated_at'  => '2023-03-14 13:42:54',
					'deleted_at'  => null,
					'subscribers' => array(),
				),
			1 =>
				array(
					'id'          => '534',
					'name'        => 'Becoming a Content Creator',
					'type'        => 'default',
					'description' => '',
					'created_at'  => '2023-03-14 13:42:54',
					'updated_at'  => '2023-03-14 13:42:54',
					'deleted_at'  => null,
					'subscribers' => array(),
				),
			2 =>
				array(
					'id'          => '536',
					'name'        => 'How to be famous',
					'type'        => 'default',
					'description' => '',
					'created_at'  => '2023-03-14 13:42:54',
					'updated_at'  => '2023-03-14 13:42:54',
					'deleted_at'  => null,
					'subscribers' => array(),
				),
			3 =>
				array(
					'id'          => '537',
					'name'        => 'Life 101',
					'type'        => 'default',
					'description' => '',
					'created_at'  => '2023-03-14 13:42:54',
					'updated_at'  => '2023-03-14 13:42:54',
					'deleted_at'  => null,
					'subscribers' => array(),
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
			'id'          => intval( wp_unique_id() ),
			'name'        => $list['name'],
			'description' => $list['description'],
			'subscribers' => array(),
		);
		$this->lists[] = $new_list;

		return $new_list;
	}

	/**
	 * Mock getSubscriber method.
	 */
	public function getSubscriber( $email ) {
		foreach ( $this->subscribers as $subscriber ) {
			if ( $subscriber['email'] === $email ) {
				return $subscriber;
			}
		}

		$id                       = intval( wp_unique_id() );
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
		$list_index = $this->getListIndex( $list_id );

		if ( ! in_array( $id, $this->lists[ $list_index ]['subscribers'], true ) ) {
			$this->lists[ $list_index ]['subscribers'][] = $id;
		}

		return true;
	}

	/**
	 * Mock unsubscribeFromList method.
	 */
	public function unsubscribeFromList( $id, $list_id ) {
		$list_index  = $this->getListIndex( $list_id );
		$subscribers = $this->lists[ $list_index ]['subscribers'];
		foreach ( $subscribers as $index => $item ) {
			if ( $item === $id ) {
				unset( $this->lists[ $list_index ]['subscribers'][ $index ] );
			}
		}
		return true;
	}

	/**
	 * Mock getSubscribers method.
	 */
	public function getSubscribers( $args ) {
		$list_id    = $args['listId'];
		$list_index = $this->getListIndex( $list_id );

		return $this->lists[ $list_index ]['subscribers'];
	}

	/**
	 * Get the index of a list.
	 */
	public function getListIndex( $list_id ) {
		foreach ( $this->lists as $index => $list ) {
			if ( $list['id'] === $list_id ) {
				return $index;
			}
		}
		return 0;
	}
}
