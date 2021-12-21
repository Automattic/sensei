<?php

class Test_Sensei_Course_Video_Blocks_Youtube_Extension extends WP_UnitTestCase {
	/**
	 * Test if only YouTube video embeds are wrapped in the block.
	 *
	 * @dataProvider provider_wraps_only_youtube_embeds
	 */
	public function test_wrap_only_youtube_embed( $iframe, $url, $expected ) {
		$settings          = $this->createMock( Sensei_Course_Video_Settings::class );
		$youtube_extension = Sensei_Course_Video_Blocks_Youtube_Extension::instance( $settings );

		$result = $youtube_extension->wrap_youtube( $iframe, $url, array() );

		self::assertSame( $expected, $result );
	}

	public function provider_wraps_only_youtube_embeds() {
		return array(
			'youtub.be'   => array(
				'<iframe src="https://www.youtube.com/embed/video-id"></iframe>',
				'https://youtu.be/video-id',
				'<div class="sensei-course-video-youtube-container"><iframe src="https://www.youtube.com/embed/video-id?enablejsapi=1&origin=http://example.org"></iframe></div>',
			),
			'youtube.com' => array(
				'<iframe src="https://www.youtube.com/embed/video-id"></iframe>',
				'https://www.youtube.com/watch?v=video-id',
				'<div class="sensei-course-video-youtube-container"><iframe src="https://www.youtube.com/embed/video-id?enablejsapi=1&origin=http://example.org"></iframe></div>',
			),
			'vimeo.com'   => array(
				'<iframe src="https://player.vimeo.com/video/video-id"></iframe>',
				'https://vimeo.com/video-id',
				'<iframe src="https://player.vimeo.com/video/video-id"></iframe>',
			),
		);
	}
}
