<?php

class Test_Sensei_Course_Video_Settings extends WP_UnitTestCase {
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
}
