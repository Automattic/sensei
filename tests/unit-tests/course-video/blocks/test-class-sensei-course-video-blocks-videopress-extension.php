<?php

class Test_Sensei_Course_Video_Blocks_VideoPress_Extension extends WP_UnitTestCase {
	/**
	 * Test if only VideoPress video embeds are wrapped in the block.
	 *
	 * @dataProvider provider_wraps_only_videopress_embeds
	 */
	public function test_wrap_only_videopress_embed( $iframe, $url, $expected ) {
		$videopress_extension = Sensei_Course_Video_Blocks_VideoPress_Extension::instance();

		$result = $videopress_extension->wrap_video( $iframe, $url );

		self::assertSame( $expected, $result );
	}

	public function provider_wraps_only_videopress_embeds() {
		return array(
			'videopress.com'      => array(
				'<iframe src="https://videopress.com/abc"></iframe>',
				'https://videopress.com/v/abc',
				'<div class=\'sensei-course-video-container videopress-extension\'><iframe src="https://videopress.com/abc"></iframe></div>',
			),
			'video.wordpress.com' => array(
				'<iframe src="https://videopress.com/abc"></iframe>',
				'https://video.wordpress.com/embed/video-id',
				'<div class=\'sensei-course-video-container videopress-extension\'><iframe src="https://videopress.com/abc"></iframe></div>',
			),
			'youtube.com'         => array(
				'<iframe src="https://www.youtube.com/embed/video-id"></iframe>',
				'https://www.youtube.com/watch?v=video-id',
				'<iframe src="https://www.youtube.com/embed/video-id"></iframe>',
			),
		);
	}
}
