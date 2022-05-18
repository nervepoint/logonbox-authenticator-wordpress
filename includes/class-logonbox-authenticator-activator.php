<?php

/**
 * Fired during plugin activation
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Plugin_Name
 * @subpackage Plugin_Name/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 */
class Logonbox_Authenticator_Activator {

	/**
	 * Marks plugin as active.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

	    Logonbox_Authenticator_Util::safeCheckLogger();
        
	}

}
