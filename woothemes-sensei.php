<?php
/*
Plugin Name: Sensei
Plugin URI: http://www.woothemes.com/products/sensei/
Description: A course management plugin that offers the smoothest platform for helping you teach anything.
Version: 1.7.4
Author: WooThemes
Author URI: http://www.woothemes.com/
License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
Requires at least: 4.0
Tested up to: 4.1.1
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

    require_once( 'classes/class-woothemes-sensei.php' );
    require_once( 'inc/woo-functions.php' );
    require_once( 'inc/woothemes-sensei-functions.php' );

    if ( ! is_admin() ) {
        require_once( 'inc/woothemes-sensei-template.php' );
    }

    global $woothemes_sensei;
    $woothemes_sensei = new WooThemes_Sensei( __FILE__ );
    $woothemes_sensei->version = '1.7.4';

    /**
     * Plugin updates
     * @since  1.0.1
     */
    woothemes_queue_update( plugin_basename( __FILE__ ), 'bad2a02a063555b7e2bee59924690763', 152116 );