<?php

class Test_Sensei_Course_Progression_Settings extends WP_UnitTestCase {
	public function testMetaFieldsExist() {
		$settings = Sensei_Course_Progression_Settings::instance();
		$settings->register_post_meta();
		$keys = get_registered_meta_keys( 'post', 'course' );
		$this->assertArrayHasKey( 'sensei_course_video_autocomplete', $keys, 'Autocomplete key was not found in meta' );
		$this->assertArrayHasKey( 'sensei_course_video_autopause', $keys, 'Autopause key was not found in meta' );
		$this->assertArrayHasKey( 'sensei_course_video_required', $keys, 'Required key was not found in meta' );
	}
}
