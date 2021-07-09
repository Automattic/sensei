<?php
/**
 * Plugin Name: Sensei E2E
 * Description: Sensei LMS E2E test helper
 * Version: 3.2.0
 * Author: Automattic
 * Author URI: https://automattic.com
 * License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Requires at least: 5.0
 * Tested up to: 5.4
 * Requires PHP: 5.6
 */

/**
 * Copyright 2013-2020 Automattic
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

require_once __DIR__ . '/class-sensei-e2e-setup-wizard-mocks.php';


/**
 * Sensei E2E Test Helper
 *
 * @since 3.3
 */
class Sensei_E2E {
	/**
	 * Instance of class.
	 *
	 * @var Sensei_E2E instance
	 */
	protected static $instance = null;

	/**
	 * Set up mocking for the E2E testing environment.
	 *
	 * @since 3.3
	 */
	public function __construct() {

		$setup_wizard = new Sensei_E2E_Setup_Wizard_Mocks();
		$setup_wizard->register();

	}

	/**
	 * Get the single class instance.
	 *
	 * @return Sensei_E2E
	 * @since 3.2
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}

Sensei_E2E::instance();

