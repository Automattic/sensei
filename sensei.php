<?php
/*
 * Plugin Name: Sensei
 * Plugin URI: https://woocommerce.com/products/sensei/
 * Description: Share your knowledge, grow your network, and strengthen your brand by launching an online course.
 * Version: 2.0.0-beta.1
 * Author: Automattic
 * Author URI: https://automattic.com
 * License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Requires at least: 4.1
 * Tested up to: 4.9
 * Text Domain: sensei
 * Domain path: /lang/
 */

/*
  Copyright 2013-2018 Automattic

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

require_once dirname( __FILE__ ) . '/includes/class-sensei-bootstrap.php';

Sensei_Bootstrap::get_instance()->bootstrap();

/**
 * Returns the global Sensei Instance.
 *
 * @since 1.8.0
 */
function Sensei() {
	return Sensei_Main::instance( array( 'version' => '2.0.0-beta.1' ) );
}

// backwards compatibility
global $woothemes_sensei;
$woothemes_sensei = Sensei();

/**
 * Sensei Activation Hook registration
 *
 * @since 1.8.0
 */
register_activation_hook( __FILE__, 'activate_sensei' );

/**
 * Activate_sensei
 *
 * All the activation checks needed to ensure Sensei is ready for use
 *
 * @since 1.8.0
 */
function activate_sensei() {
	Sensei()->activate();
}
