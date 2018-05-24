<?php

class WP_UnitTest_Factory_For_Quiz extends WP_UnitTest_Factory_For_Post_Sensei {
	function __construct( $factory = null ) {
		parent::__construct( $factory );
		$this->default_generation_definitions = array(
			'post_status'  => 'publish',
			'post_title'   => new WP_UnitTest_Generator_Sequence( 'Quiz title %s' ),
			'post_content' => new WP_UnitTest_Generator_Sequence( 'Quiz content %s' ),
			'post_excerpt' => new WP_UnitTest_Generator_Sequence( 'Quiz excerpt %s' ),
			'post_type'    => 'quiz',
		);
	}
}
