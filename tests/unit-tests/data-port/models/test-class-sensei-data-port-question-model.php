<?php
/**
 * This file contains the Sensei_Data_Port_Question_Model_Test class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tests for Sensei_Data_Port_Question_Model class.
 *
 * @group data-port
 */
class Sensei_Data_Port_Question_Model_Test extends WP_UnitTestCase {
	/**
	 * Sensei factory object.
	 *
	 * @var Sensei_Factory
	 */
	private $factory;

	/**
	 * Setup function.
	 */
	public function setUp() {
		parent::setUp();

		$this->factory = new Sensei_Factory();
	}

	/**
	 * Check to make sure an existing post with a matching slug is matched up with the model.
	 */
	public function testExistingPostFound() {
		$data = [
			Sensei_Data_Port_Question_Model::COLUMN_QUESTION => 'Do you like dinosaurs?',
			Sensei_Data_Port_Question_Model::COLUMN_ANSWER => 'Right:Yes, Wrong: No',
			Sensei_Data_Port_Question_Model::COLUMN_SLUG   => 'do-you-like-dinosaurs',
		];

		$post_id = $this->factory->post->create(
			[
				'post_type' => 'question',
				'post_name' => $data[ Sensei_Data_Port_Question_Model::COLUMN_SLUG ],
			]
		);

		$model = Sensei_Data_Port_Question_Model::from_source_array( $data );

		$this->assertEquals( $post_id, $model->get_post_id() );
	}

	/**
	 * Check to make sure an existing post with a similar slug is not matched up with the model.
	 */
	public function testCloseExistingPostNotFound() {
		$data = [
			Sensei_Data_Port_Question_Model::COLUMN_QUESTION => 'Do you like dinosaurs?',
			Sensei_Data_Port_Question_Model::COLUMN_ANSWER => 'Right:Yes, Wrong: No',
			Sensei_Data_Port_Question_Model::COLUMN_SLUG   => 'do-you-like-dinosaurs',
		];

		$post_id = $this->factory->post->create(
			[
				'post_type' => 'question',
				'post_name' => $data[ Sensei_Data_Port_Question_Model::COLUMN_SLUG ] . '-2',
			]
		);

		$model = Sensei_Data_Port_Question_Model::from_source_array( $data );

		$this->assertEquals( null, $model->get_post_id() );
	}
}
