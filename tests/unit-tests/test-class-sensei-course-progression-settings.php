<?php

class Test_Sensei_Course_Progression_Settings extends WP_UnitTestCase {
	public function testMetaFieldsExist() {
		$settings = Sensei_Course_Progression_Settings::instance();
		$settings->register_post_meta();
		$keys = get_registered_meta_keys( 'post', 'course' );
		$this->assertArrayHasKey( '_video_course_autocomplete', $keys );
		$this->assertArrayHasKey( '_video_course_autopause', $keys );
		$this->assertArrayHasKey( '_video_course_required', $keys );
	}
}
