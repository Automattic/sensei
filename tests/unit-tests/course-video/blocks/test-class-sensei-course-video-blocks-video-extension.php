<?php

class Test_Sensei_Course_Video_Blocks_Video_Extension extends WP_UnitTestCase {
	public function test_wrap_video_block() {
		$video_extension = Sensei_Course_Video_Blocks_Video_Extension::instance();

		$result = $video_extension->wrap_video(
			'<figure class="wp-block-video"><video src="http://localhost/video"></video></figure>'
		);

		$expected = '<div class="sensei-course-video-container video-extension"><figure class="wp-block-video"><video src="http://localhost/video"></video></figure></div>';

		self::assertSame( $expected, $result );
	}
}
