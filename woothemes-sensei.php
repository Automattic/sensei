<?php
/*
Plugin Name: Sensei
Plugin URI: http://woothemes.com/
Description: Sensei by WooThemes is the best Learning Management System ever!
Version: 1.3.7
Author: WooThemes
Author URI: http://woothemes.com/
License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
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
	if ( ! is_admin() ) require_once( 'inc/woothemes-sensei-template.php' );
	global $woothemes_sensei;
	$woothemes_sensei = new WooThemes_Sensei( __FILE__ );
	$woothemes_sensei->version = '1.3.7';

    /**
     * Plugin updates
     * @since  1.0.1
     */
    woothemes_queue_update( plugin_basename( __FILE__ ), 'bad2a02a063555b7e2bee59924690763', 152116 );
?>