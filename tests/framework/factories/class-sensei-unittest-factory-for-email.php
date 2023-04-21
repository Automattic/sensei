<?php

use Sensei\Internal\Emails\Email_Post_Type;

class Sensei_UnitTest_Factory_For_Email extends WP_UnitTest_Factory_For_Post_Sensei {
	public function __construct( $factory = null ) {
		parent::__construct( $factory );
		$this->default_generation_definitions = array(
			'post_status'  => 'publish',
			'post_title'   => new WP_UnitTest_Generator_Sequence( 'Email title %s' ),
			'post_content' => new WP_UnitTest_Generator_Sequence( 'Email content %s' ),
			'post_type'    => Email_Post_Type::POST_TYPE,
		);
	}
}
