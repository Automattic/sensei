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
	 * The course video settings printed for a lesson are read from the lesson's course.
	 *
	 * @covers Sensei_Course_Video_Settings::enqueue_frontend_scripts
	 */
	public function testEnqueueFrontendScripts_WhenLessonCourseHadVideoSettingsEnabled_AddsCourseSettings() {
		/* Arrange */
		$course          = $this->factory->get_course_with_lessons(
			array(
				'question_count' => 0,
				'course_args'    => array(
					'meta_input' => array(
						Sensei_Course_Video_Settings::COURSE_VIDEO_AUTOCOMPLETE => true,
						Sensei_Course_Video_Settings::COURSE_VIDEO_AUTOPAUSE    => true,
						Sensei_Course_Video_Settings::COURSE_VIDEO_REQUIRED     => true,
					),
				),
			)
		);
		$GLOBALS['post'] = get_post( $course['lesson_ids'][0] );

		/* Act */
		Sensei_Course_Video_Settings::instance()->enqueue_frontend_scripts();

		/* Assert */
		$inline_script = wp_scripts()->get_inline_script_data( 'sensei-course-video-blocks-extension', 'before' );
		self::assertStringContainsString( '{"courseVideoAutoComplete":true,"courseVideoAutoPause":true,"courseVideoRequired":true}', $inline_script );
	}
}
