<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class Sensei_Bootstrap loads the functionality needed for Sensei_Main to initialize properly
 * @package Core
 */
class Sensei_Bootstrap {
    /**
     * @var Sensei_Bootstrap
     */
    private static $instance;

    /**
     * @var null|Sensei_Autoloader
     */
    private $autoloader = null;

    /**
     * @var bool
     */
    private $is_bootstrapped = false;

    private function __construct() {
    }

    public function bootstrap() {
        if ( $this->is_bootstrapped ) {
            return $this;
        }
        $this->init_autoloader();
        $this->init_must_have_includes();

        $this->is_bootstrapped = true;
        return $this;
    }

    private function init_autoloader() {
        require_once( 'class-sensei-autoloader.php' );
        $this->autoloader = new Sensei_Autoloader();
    }

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function init_must_have_includes() {
        require_once( 'lib/woo-functions.php' );
        require_once( 'sensei-functions.php' );
    }
}