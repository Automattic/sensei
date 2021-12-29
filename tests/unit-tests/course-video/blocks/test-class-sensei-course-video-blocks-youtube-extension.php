<?php

class Test_Sensei_Course_Video_Blocks_Youtube_Extension extends WP_UnitTestCase {
	/**
	 * Test if only YouTube video embeds are wrapped in the block.
	 *
	 * @dataProvider provider_wraps_only_youtube_embeds
	 */
	public function test_wrap_only_youtube_embed( $iframe, $url, $expected ) {
		$youtube_extension = Sensei_Course_Video_Blocks_Youtube_Extension::instance();

		$result = $youtube_extension->wrap_video( $iframe, $url );

		self::assertSame( $expected, $result );
	}

	public function provider_wraps_only_youtube_embeds() {
		return array(
			'youtub.be'   => array(
				'<iframe src="https://www.youtube.com/embed/video-id"></iframe>',
				'https://youtu.be/video-id',
				'<div class=\'sensei-course-video-container youtube-extension\'><iframe src="https://www.youtube.com/embed/video-id"></iframe></div>',
			),
			'youtube.com' => array(
				'<iframe src="https://www.youtube.com/embed/video-id"></iframe>',
				'https://www.youtube.com/watch?v=video-id',
				'<div class=\'sensei-course-video-container youtube-extension\'><iframe src="https://www.youtube.com/embed/video-id"></iframe></div>',
			),
			'vimeo.com'   => array(
				'<iframe src="https://player.vimeo.com/video/video-id"></iframe>',
				'https://vimeo.com/video-id',
				'<iframe src="https://player.vimeo.com/video/video-id"></iframe>',
			),
		);
	}

	/**
	 * Tests if needed query args are added to the iframe src for YouTube videos.
	 *
	 * @dataProvider provider_replace_iframe_url
	 */
	public function test_replace_iframe_url( $iframe, $url, $expected ) {
		$youtube_extension = Sensei_Course_Video_Blocks_Youtube_Extension::instance();

		$result = $youtube_extension->replace_iframe_url( $iframe, $url );

		self::assertSame( $expected, $result );
	}

	public function provider_replace_iframe_url() {
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
