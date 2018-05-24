<?php

class WP_UnitTest_Factory_For_Module extends WP_UnitTest_Factory_For_Term {
	function __construct( $factory = null ) {
		parent::__construct( $factory, 'module' );
		$this->default_generation_definitions = array(
			'name'        => new WP_UnitTest_Generator_Sequence( 'Module %s' ),
			'taxonomy'    => 'module',
			'description' => new WP_UnitTest_Generator_Sequence( 'Module description %s' ),
		);
	}
}
