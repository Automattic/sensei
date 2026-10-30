<?php

class Test_Sensei_Course_Video_Settings extends WP_UnitTestCase {
	public function setUp(): void {
		parent::setUp();

		$this->factory = new Sensei_Factory();
	}

	public function tearDown(): void {
		parent::tearDown();
		$this->factory->tearDown();
	}

	public function testMetaFieldsExist() {
		$settings = Sensei_Course_Video_Settings::instance();
		$settings->register_post_meta();
		$keys = get_registered_meta_keys( 'post', 'course' );
		$this->assertArrayHasKey( 'sensei_course_video_autocomplete', $keys, 'Autocomplete key was not found in meta' );
		$this->assertArrayHasKey( 'sensei_course_video_autopause', $keys, 'Autopause key was not found in meta' );
		$this->assertArrayHasKey( 'sensei_course_video_required', $keys, 'Required key was not found in meta' );
	}

	/**
	 * Tests if needed query args are added to the iframe src for YouTube videos.
	 *
	 * @dataProvider provider_enable_youtube_api
	 */
	public function test_enable_youtube_api( $iframe, $url, $expected ) {
		$settings = Sensei_Course_Video_Settings::instance();

		$result = $settings->enable_youtube_api( $iframe, $url );

		$expected = str_replace( 'http://example.org', home_url(), $expected );
		self::assertSame( $expected, $result );
	}

	public function provider_enable_youtube_api() {
		return array(
			'youtub.be'   => array(
				'<iframe src="https://www.youtube.com/embed/video-id"></iframe>',
				'https://youtu.be/video-id',
				'<iframe src="https://www.youtube.com/embed/video-id?enablejsapi=1&origin=http://example.org"></iframe>',
			),
			'youtube.com' => array(
				'<iframe src="https://www.youtube.com/embed/video-id"></iframe>',
				'https://www.youtube.com/watch?v=video-id',
				'<iframe src="https://www.youtube.com/embed/video-id?enablejsapi=1&origin=http://example.org"></iframe>',
			),
			'vimeo.com'   => array(
				'<iframe src="https://player.vimeo.com/video/video-id"></iframe>',
				'https://vimeo.com/video-id',
				'<iframe src="https://player.vimeo.com/video/video-id"></iframe>',
			),
		);
	}

	/**
	 * Each course video setting of the current lesson is read from the lesson's course.
	 *
	 * @covers Sensei_Course_Video_Settings::is_autocomplete_enabled
	 * @covers Sensei_Course_Video_Settings::is_autopause_enabled
	 * @covers Sensei_Course_Video_Settings::is_required
	 * @dataProvider providerCourseVideoSetting_WhenLessonCourseHadSettingEnabled_ReturnsCourseSetting
	 *
	 * @param string $meta_key The course setting meta key.
	 * @param string $method   The settings method that reads it.
	 */
	public function testCourseVideoSetting_WhenLessonCourseHadSettingEnabled_ReturnsCourseSetting( $meta_key, $method ) {
		/* Arrange */
		$course          = $this->factory->get_course_with_lessons(
			array(
				'question_count' => 0,
				'course_args'    => array( 'meta_input' => array( $meta_key => true ) ),
			)
		);
		$GLOBALS['post'] = get_post( $course['lesson_ids'][0] );

		/* Act */
		$result = Sensei_Course_Video_Settings::instance()->$method();

		/* Assert */
		self::assertSame( '1', $result );
	}

	public function providerCourseVideoSetting_WhenLessonCourseHadSettingEnabled_ReturnsCourseSetting(): array {
		return array(
			'autocomplete' => array( Sensei_Course_Video_Settings::COURSE_VIDEO_AUTOCOMPLETE, 'is_autocomplete_enabled' ),
			'autopause'    => array( Sensei_Course_Video_Settings::COURSE_VIDEO_AUTOPAUSE, 'is_autopause_enabled' ),
			'required'     => array( Sensei_Course_Video_Settings::COURSE_VIDEO_REQUIRED, 'is_required' ),
		);
	}
}
