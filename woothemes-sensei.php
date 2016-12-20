<?php
/*
Plugin Name: Sensei
Plugin URI: http://www.woothemes.com/products/sensei/
Description: A course management plugin that offers the smoothest platform for helping you teach anything.
Version: 1.9.10
Author: WooThemes
Author URI: http://www.woothemes.com/
License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
Requires at least: 4.1
Tested up to: 4.7
Text Domain: woothemes-sensei
Domain path: /lang/
*/
/*  Copyright 2013  WooThemes  (email : info@woothemes.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

	if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

    require_once( 'includes/class-sensei-autoloader.php' );
    require_once( 'includes/lib/woo-functions.php' );
    require_once( 'includes/sensei-functions.php' );

    /**
     * Load Sensei Template Functions
     *
     * @since 1.9.8
     */
    function sensei_load_template_functions() {
        require_once( 'includes/template-functions.php' );
    }

    add_action( 'after_setup_theme', 'sensei_load_template_functions' );

    /**
     * Returns the global Sensei Instance.
     *
     * @since 1.8.0
     */
    function Sensei(){

        return Sensei_Main::instance();

    }

	// set the sensei version number
    Sensei()->version = '1.9.10';

    //backwards compatibility
    global $woothemes_sensei;
    $woothemes_sensei = Sensei();

    /**
    * Hook in WooCommerce functionality
    */
	add_action('init', array( 'Sensei_WC', 'load_woocommerce_integration_hooks' ) );

    /**
     * Load all Template hooks
    */
    if(! is_admin() ){

        require_once( 'includes/hooks/template.php' );

    }

    /**
     * Plugin updates
     * @since  1.0.1
     */
    woothemes_queue_update( plugin_basename( __FILE__ ), 'bad2a02a063555b7e2bee59924690763', 152116 );

    /**
     * Sensei Activation Hook registration
     * @since 1.8.0
     */
    register_activation_hook( __FILE__, 'activate_sensei' );

    /**
     * Activate_sensei
     *
     * All the activation checks needed to ensure Sensei is ready for use
     * @since 1.8.0
     */
    function activate_sensei () {

        // create the teacher role on activation and ensure that it has all the needed capabilities
        Sensei()->teacher->create_role();

        //Setup all the role capabilities needed
        Sensei()->updates->add_sensei_caps();
        Sensei()->updates->add_editor_caps();
        Sensei()->updates->assign_role_caps();

        //Flush rules
        add_action( 'activated_plugin' , array( 'Sensei_Main','activation_flush_rules' ), 10 );

        //Load the Welcome Screen
        add_action( 'activated_plugin' , array( 'Sensei_Welcome','redirect' ), 20 );

    }// end activate_sensei
