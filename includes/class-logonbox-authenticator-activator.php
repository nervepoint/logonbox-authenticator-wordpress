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

        update_option(Logonbox_Authenticator_Constants::OPTIONS_ACTIVE, true);

        // check user is logged and can activate plugin
        if (is_user_logged_in() && current_user_can( "activate_plugins" ) )
        {
            if(session_status() == PHP_SESSION_NONE) {
                session_start();
            }

            $user = wp_get_current_user();
            $username = $user->user_login;

            if ($user->exists())
            {
                Logonbox_Authenticator_Util::info_log("Marking session on plugin activation for user " . $username);
                $_SESSION[Logonbox_Authenticator_Constants::SESSION_MARK_USER_AUTHORIZED] = "true";
            }
            else
            {
                Logonbox_Authenticator_Util::info_log("User does not exists cannot mark session on plugin activation for user " . $username);
            }

        }
	}

}
