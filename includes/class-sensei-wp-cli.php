<?php

class Sensei_WP_Cli {
    function __construct() {
        if ( defined( 'WP_CLI' ) && WP_CLI ) {
            $this->register();
        }
    }

    private function register() {
       WP_CLI::add_command( 'sensei course new', array( $this, 'create_course' ), array(
           'shortdesc' => 'Creates a new Sensei course with an empty body',
           'synopsis' => array(
               array(
                   'type'     => 'positional',
                   'name'     => 'name',
                   'optional' => false,
                   'multiple' => false,
               ),
               array(
                   'type'     => 'assoc',
                   'name'     => 'course_content',
                   'optional' => true,
                   'default'  => '',
               ),
               array(
                   'type'     => 'assoc',
                   'name'     => 'course_excerpt',
                   'optional' => true,
                   'default'  => '',
               ),
               array(
                   'type'     => 'assoc',
                   'name'     => 'course_video',
                   'optional' => true,
                   'default'  => '',
                   'options'  => array( 'success', 'error' ),
               ),
               array(
                   'type'     => 'assoc',
                   'name'     => 'course_teacher',
                   'optional' => true,
                   'default'  => null,
               ),
               array(
                   'type'     => 'assoc',
                   'name'     => 'is_featured',
                   'optional' => true,
                   'default'  => false,
                   'options'  => array( true, false ),
               ),
           ),
           'when' => 'after_wp_load',
       ) );
    }

    public function create_course( $args, $assoc_args ) {

    }
}