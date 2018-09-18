<?php

class WP_UnitTest_Factory_For_Message extends WP_UnitTest_Factory_For_Post_Sensei {
	function __construct( $factory = null ) {
		parent::__construct( $factory );
		$this->default_generation_definitions = array(
			'post_status'  => 'publish',
			'post_title'   => new WP_UnitTest_Generator_Sequence( 'Message title %s' ),
			'post_content' => new WP_UnitTest_Generator_Sequence( 'Message content %s' ),
			'post_type'    => 'sensei_message',
		);
	}
}
