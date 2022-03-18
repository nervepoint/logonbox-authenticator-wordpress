<?php

/**
 * Fired during plugin deactivation
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Plugin_Name
 * @subpackage Plugin_Name/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 */
class Logonbox_Authenticator_Deactivator {

	/**
	 * Marks plugin as inactive.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
        update_option(Logonbox_Authenticator_Constants::OPTIONS_ACTIVE, false);
	}

}
