<?php

class WP_UnitTest_Factory_For_Question_Category extends WP_UnitTest_Factory_For_Term {
	function __construct( $factory = null ) {
		parent::__construct( $factory, 'question-category' );
		$this->default_generation_definitions = array(
			'name'        => new WP_UnitTest_Generator_Sequence( 'Question Category %s' ),
			'taxonomy'    => 'question-category',
			'description' => new WP_UnitTest_Generator_Sequence( 'Question Category description %s' ),
		);
	}
}
