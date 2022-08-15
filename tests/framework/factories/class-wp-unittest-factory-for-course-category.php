<?php

class WP_UnitTest_Factory_For_Course_Category extends WP_UnitTest_Factory_For_Term {
	function __construct( $factory = null ) {
		parent::__construct( $factory, 'course-category' );
		$this->default_generation_definitions = array(
			'name'        => new WP_UnitTest_Generator_Sequence( 'Course Category %s' ),
			'taxonomy'    => 'course-category',
			'description' => new WP_UnitTest_Generator_Sequence( 'Course Category description %s' ),
		);
	}
}
