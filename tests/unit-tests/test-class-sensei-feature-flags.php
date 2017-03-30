<?php

class Sensei_Class_Feature_Flags_Test extends WP_UnitTestCase {

    /**
     * Constructor function
     */
    public function __construct(){
        parent::__construct();
    }

    /**
     * Test functionality
     */
    public function testFlags() {
        $flags = new Sensei_Feature_Flags();

        $this->assertFalse( $flags->is_enabled( 'rest_api_testharness' ) );
        $this->assertFalse( $flags->is_enabled( 'rest_api_v1' ) );
        $this->assertFalse( $flags->is_enabled( 'rest_api_v1_skip_permissions' ) );

        define( 'SENSEI_FEATURE_FLAG_REST_API_V1', true);
        define( 'SENSEI_FEATURE_FLAG_REST_API_V1_SKIP_PERMISSIONS', true);
        define( 'SENSEI_FEATURE_FLAG_REST_API_TESTHARNESS', true);

        $flags = new Sensei_Feature_Flags();

        $this->assertTrue( $flags->is_enabled( 'rest_api_v1' ) , 'overriden by define' );
        $this->assertTrue( $flags->is_enabled( 'rest_api_v1_skip_permissions' ) );
        $this->assertTrue( $flags->is_enabled( 'rest_api_testharness' ) );

        add_filter( 'sensei_feature_flag_rest_api_v1', '__return_false' );

        $this->assertFalse( $flags->is_enabled( 'rest_api_v1' ) , 'overriden by filter' );
    }

}// end test class