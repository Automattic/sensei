<?php
/**
 * This file contains the Sensei_Data_Port_Course_Model_Test class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tests for Sensei_Data_Port_Course_Model class.
 *
 * @group data-port
 */
class Sensei_Data_Port_Course_Model_Test extends WP_UnitTestCase {
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
	 * Get line data for courses.
	 */
	public function lineData() {
		return [
			[
				[
					Sensei_Data_Port_Course_Model::COLUMN_ID                => '<tag>id</tag>',
					Sensei_Data_Port_Course_Model::COLUMN_COURSE            => 'Course <randomtag>content</randomtag>',
					Sensei_Data_Port_Course_Model::COLUMN_SLUG              => '<randomtag>slug</randomtag>',
					Sensei_Data_Port_Course_Model::COLUMN_DESCRIPTION       => '<randomtag>description</randomtag>',
					Sensei_Data_Port_Course_Model::COLUMN_EXCERPT           => '<randomtag>excerpt</randomtag>',
					Sensei_Data_Port_Course_Model::COLUMN_TEACHER_USERNAME  => '<p>username@#</p>',
					Sensei_Data_Port_Course_Model::COLUMN_TEACHER_EMAIL     => 'em\<ail#@host.com',
					Sensei_Data_Port_Course_Model::COLUMN_MODULES           => '<randomtag>   First,Second   </randomtag>',
					Sensei_Data_Port_Course_Model::COLUMN_PREREQUISITE      => '<randomtag>prerequisite</randomtag>',
					Sensei_Data_Port_Course_Model::COLUMN_FEATURED          => '<randomtag>featured</randomtag>',
					Sensei_Data_Port_Course_Model::COLUMN_CATEGORIES        => '<randomtag>   First,Second   </randomtag>',
					Sensei_Data_Port_Course_Model::COLUMN_IMAGE             => 'http://randomurl<>.com/nice%20image.png',
					Sensei_Data_Port_Course_Model::COLUMN_VIDEO             => '<randomtag>video</randomtag>',
					Sensei_Data_Port_Course_Model::COLUMN_NOTIFICATIONS     => '<randomtag>notifications</randomtag>',
				],
				[
					Sensei_Data_Port_Course_Model::COLUMN_ID                => 'id',
					Sensei_Data_Port_Course_Model::COLUMN_COURSE            => 'Course content',
					Sensei_Data_Port_Course_Model::COLUMN_SLUG              => 'slug',
					Sensei_Data_Port_Course_Model::COLUMN_DESCRIPTION       => 'description',
					Sensei_Data_Port_Course_Model::COLUMN_EXCERPT           => 'excerpt',
					Sensei_Data_Port_Course_Model::COLUMN_TEACHER_USERNAME  => 'username@#',
					Sensei_Data_Port_Course_Model::COLUMN_TEACHER_EMAIL     => 'email#@host.com',
					Sensei_Data_Port_Course_Model::COLUMN_MODULES           => 'First,Second',
					Sensei_Data_Port_Course_Model::COLUMN_PREREQUISITE      => 'prerequisite',
					Sensei_Data_Port_Course_Model::COLUMN_FEATURED          => true,
					Sensei_Data_Port_Course_Model::COLUMN_CATEGORIES        => 'First,Second',
					Sensei_Data_Port_Course_Model::COLUMN_IMAGE             => 'http://randomurl.com/nice%20image.png',
					Sensei_Data_Port_Course_Model::COLUMN_VIDEO             => 'video',
					Sensei_Data_Port_Course_Model::COLUMN_NOTIFICATIONS     => true,
				],
			],
			[
				[
					Sensei_Data_Port_Course_Model::COLUMN_COURSE            => 'Course <p>content</p>',
					Sensei_Data_Port_Course_Model::COLUMN_DESCRIPTION       => '<p>description</p>',
					Sensei_Data_Port_Course_Model::COLUMN_EXCERPT           => '<p>excerpt</p>',
					Sensei_Data_Port_Course_Model::COLUMN_IMAGE             => 'localfilename.png',
					Sensei_Data_Port_Course_Model::COLUMN_VIDEO             => '<video autoplay>video</video>',
				],
				[
					Sensei_Data_Port_Course_Model::COLUMN_COURSE            => 'Course <p>content</p>',
					Sensei_Data_Port_Course_Model::COLUMN_DESCRIPTION       => '<p>description</p>',
					Sensei_Data_Port_Course_Model::COLUMN_EXCERPT           => '<p>excerpt</p>',
					Sensei_Data_Port_Course_Model::COLUMN_FEATURED          => false,
					Sensei_Data_Port_Course_Model::COLUMN_IMAGE             => 'localfilename.png',
					Sensei_Data_Port_Course_Model::COLUMN_VIDEO             => '<video autoplay>video</video>',
					Sensei_Data_Port_Course_Model::COLUMN_NOTIFICATIONS     => false,
				],
			],
		];
	}

	/**
	 * Make sure that input coming from the CSV file is sanitized properly.
	 *
	 * @dataProvider lineData
	 */
	public function testInputIsSanitized( $input_line, $expected_model_content ) {
		$model         = Sensei_Data_Port_Course_Model::from_source_array( $input_line, 1 );
		$tested_fields = [
			Sensei_Data_Port_Course_Model::COLUMN_ID,
			Sensei_Data_Port_Course_Model::COLUMN_COURSE,
			Sensei_Data_Port_Course_Model::COLUMN_SLUG,
			Sensei_Data_Port_Course_Model::COLUMN_DESCRIPTION,
			Sensei_Data_Port_Course_Model::COLUMN_EXCERPT,
			Sensei_Data_Port_Course_Model::COLUMN_TEACHER_USERNAME,
			Sensei_Data_Port_Course_Model::COLUMN_TEACHER_EMAIL,
			Sensei_Data_Port_Course_Model::COLUMN_MODULES,
			Sensei_Data_Port_Course_Model::COLUMN_PREREQUISITE,
			Sensei_Data_Port_Course_Model::COLUMN_FEATURED,
			Sensei_Data_Port_Course_Model::COLUMN_CATEGORIES,
			Sensei_Data_Port_Course_Model::COLUMN_IMAGE,
			Sensei_Data_Port_Course_Model::COLUMN_VIDEO,
			Sensei_Data_Port_Course_Model::COLUMN_NOTIFICATIONS,
		];

		foreach ( $tested_fields as $tested_field ) {
			if ( isset( $expected_model_content[ $tested_field ] ) ) {
				$this->assertEquals( $expected_model_content[ $tested_field ], $model->get_value( $tested_field ) );
			}
		}
	}
}
