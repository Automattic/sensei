<?php
/*
Plugin Name: Sensei
Plugin URI: http://www.woothemes.com/products/sensei/
Description: A course management plugin that offers the smoothest platform for helping you teach anything.
Version: 1.9.12
Author: WooThemes
Author URI: http://www.woothemes.com/
License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
Requires at least: 4.1
Tested up to: 4.7.2
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

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

require_once( 'includes/class-sensei-bootstrap.php' );

Sensei_Bootstrap::get_instance()->bootstrap();

/**
 * Returns the global Sensei Instance.
 *
 * @since 1.8.0
 */
function Sensei() {
    return Sensei_Main::instance( array( 'version' => '1.9.12' ) );
}

//backwards compatibility
global $woothemes_sensei;
$woothemes_sensei = Sensei();

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
function activate_sensei() {
    Sensei()->activate();
}
