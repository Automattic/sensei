<?php

class Test_Sensei_Course_Video_Blocks_Video_Extension extends WP_UnitTestCase {
	public function test_wrap_only_youtube_embed() {
		$settings        = $this->createMock( Sensei_Course_Video_Settings::class );
		$video_extension = Sensei_Course_Video_Blocks_Video_Extension::instance( $settings );

		$result = $video_extension->wrap_video(
			'<figure class="wp-block-video"><video src="http://localhost/video"></video></figure>',
			array(),
			new WP_Block(
				[
					'blockName'    => 'a',
					'innerBlocks'  => [],
					'innerHTML'    => '',
					'innerContent' => '',
				]
			)
		);

		$expected = '<div class="sensei-course-video-video-container"><figure class="wp-block-video"><video src="http://localhost/video"></video></figure></div>';

		self::assertSame( $expected, $result );
	}
}
