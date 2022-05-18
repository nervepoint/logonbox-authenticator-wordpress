<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 *
 */

use Authenticator\AuthenticatorClient;
use RemoteService\RemoteServiceImpl;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Plugin_Name
 * @subpackage Plugin_Name/admin
 * @author     Your Name <email@example.com>
 */
class Logonbox_Authenticator_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Plugin_Name_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Plugin_Name_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Plugin_Name_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Plugin_Name_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

	}

	public function logonbox_authenticator_options() {

	    // add a menu page
        add_menu_page( 'Logonbox Authenticator', 'Logonbox Authenticator',
            'manage_options', Logonbox_Authenticator_Constants::OPTIONS_MENU_SLUG, array($this, 'logonbox_authenticator_settings_page') );

        //call register settings function
        add_action( 'admin_init', array($this, 'logonbox_authenticator_register_settings') );
    }

    // HOOKS AND FILTERS

    public function logonbox_authenticator_init() {

        Logonbox_Authenticator_Util::safeCheckLogger();

        Logonbox_Authenticator_Util::debug_log("In init, checking LogonBox authentication is required.");

        $active = Logonbox_Authenticator_Util::logonbox_authenticator_active_option();

        if (!$active) {
            Logonbox_Authenticator_Util::debug_log("Logonbox authenticator not active.");
            return;
        }

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        if (!is_user_logged_in() && Logonbox_Authenticator_Util::is_login_page()) {
            if (isset($_GET["redirect_to"])) {
                $redirect_uri = $_GET["redirect_to"];
                Logonbox_Authenticator_Util::info_log("Recording redirect to post login URI as " . $redirect_uri . ".");
                $_SESSION[Logonbox_Authenticator_Constants::SESSION_RECORD_LAST_URI] = $redirect_uri;
            }
        }

        if (!is_user_logged_in() && isset($_SESSION[Logonbox_Authenticator_Constants::SESSION_MARK_USER_AUTHORIZED])) {
            Logonbox_Authenticator_Util::info_log("User is not logged in but session marker is still set, removing it.");
            unset($_SESSION[Logonbox_Authenticator_Constants::SESSION_MARK_USER_AUTHORIZED]);
        }

	    if (!isset($_SESSION[Logonbox_Authenticator_Constants::SESSION_MARK_USER_AUTHORIZED]) && is_user_logged_in()) {
            Logonbox_Authenticator_Util::info_log("User is logged in requires logonbox authentication.");
            $user = wp_get_current_user();
            try {
                $this->start_logonbox_authenticator_second_factor_process($user);
            } catch (Exception $e) {
                $tracker = Logonbox_Authenticator_Util::tracker_code();
                Logonbox_Authenticator_Util::info_log("Tracker: " . $tracker . " : " . $e);
            }
        }
    }

    public function logonbox_custom_authenticator($user="", $username="", $password="") {
        Logonbox_Authenticator_Util::info_log("Performing LogonBox authentication check.");

        $active = Logonbox_Authenticator_Util::logonbox_authenticator_active_option();

        if (!$active) {
            Logonbox_Authenticator_Util::debug_log("Logonbox authenticator not active.");
            return $user;
        }

        // play nicely with other plugins if they have higher priority than us
        if (is_a($user, "WP_User")) {
            Logonbox_Authenticator_Util::info_log("User object is set, returning " . $user->user_login);
            return $user;
        }

        if (strlen($username) > 0) {

            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }

            Logonbox_Authenticator_Util::info_log("Performing check for " . $username);
            // primary auth
            // Don't use get_user_by(). It doesn't return a WP_User object if wordpress version < 3.3
            $user = new WP_User(0, $username);
            if (!$user) {
                Logonbox_Authenticator_Util::info_log("Failed to retrieve WP user $username");
                return null;
            }

            remove_action("authenticate", "wp_authenticate_username_password", 20);
            $user = wp_authenticate_username_password(NULL, $username, $password);
            if (!is_a($user, "WP_User")) {
                // on error, return said error (and skip the remaining plugin chain)
                Logonbox_Authenticator_Util::info_log("Authentication call failed to return WP_User instance for " . $username);
                return $user;
            } else {
                Logonbox_Authenticator_Util::info_log("Primary auth succeeded, starting second factor for $username");
                try {
                    $allowed_user = $this->start_logonbox_authenticator_second_factor_process($user);
                    if (is_a($allowed_user, "WP_User")) {
                        return $allowed_user;
                    }
                } catch (Exception $exception) {
                    $tracker = Logonbox_Authenticator_Util::tracker_code();
                    Logonbox_Authenticator_Util::error_log("Tracker: " . $tracker . " : " . $exception, $exception);
                    return new WP_Error("LogonBox authentication_failed",
                        __("<strong>ERROR</strong>: Authentication failed, please contact administrator. Track Id: $tracker"));
                }

            }
        }
    }

    function logonbox_authenticator_option_debug_updated($old_value, $value, $option) {
	    if ($value == null) {
	        $value = false;
        }
        Logonbox_Authenticator_Util::info_log("Changing log level to " . $value . " from value " . $old_value . ".");
        Logonbox_Authenticator_Util::setUpLogger($value);
    }

    function logonbox_authenticator_option_active_updated($old_value, $value, $option) {
        if ($value) {
            // check user is logged and can activate plugin
            if (is_user_logged_in())
            {
                if(session_status() == PHP_SESSION_NONE) {
                    session_start();
                }

                $user = wp_get_current_user();
                $username = $user->user_login;

                if ($user->exists())
                {
                    Logonbox_Authenticator_Util::info_log("Marking session on active option update for user " . $username);
                    $_SESSION[Logonbox_Authenticator_Constants::SESSION_MARK_USER_AUTHORIZED] = "true";
                }
                else
                {
                    Logonbox_Authenticator_Util::info_log("User does not exists cannot mark session on plugin activation for user " . $username);
                }

            }
        }
    }

    function logonbox_authenticator_option_active_pre_update($new_value, $old_value) {
        
        $host = Logonbox_Authenticator_Util::logonbox_authenticator_host_option();

        Logonbox_Authenticator_Util::debug_log("The host information on pre update of active " . $host . " and new value is " . $new_value);

        if ($new_value && (empty($host) || trim($host) == "")) {
            return false;
        }

        return $new_value;
    }


    function logonbox_authenticator_wp_logout(int $user_id) {
        Logonbox_Authenticator_Util::info_log("Clearing session data for user with ID " . $user_id . ".");
	    Logonbox_Authenticator_Util::clear_all_session_data();
    }

    function logonbox_authenticator_login_message(): string
    {
        if (isset($_SESSION[Logonbox_Authenticator_Constants::SESSION_REJECT_FLASH_MESSAGE])) {
            unset($_SESSION[Logonbox_Authenticator_Constants::SESSION_REJECT_FLASH_MESSAGE]);
            return "<div id='login_error'>	<strong>ERROR</strong>: Request rejected.<br></div>";
        }

        return "";
    }

    // HELPER METHODS

    function logonbox_authenticator_settings_page() {
        echo  "<div class='wrap'>";
                settings_errors();

        if (is_multisite()) {
            echo "<form action='ms-options.php' method='post'>";
        } else {
            echo "<form method='POST' action='options.php'>";
        }

                settings_fields(Logonbox_Authenticator_Constants::OPTIONS_GROUP); // pass slug name of page, also referred to in Settings API as option group name
                do_settings_sections(Logonbox_Authenticator_Constants::OPTIONS_MENU_SLUG);  // pass slug name of page
                submit_button(); // submit button

        echo "</form>";
    }

    function logonbox_authenticator_register_settings() {
        add_settings_section(Logonbox_Authenticator_Constants::OPTIONS_SECTION, 'LogonBox Authenticator Settings', null,
            Logonbox_Authenticator_Constants::OPTIONS_MENU_SLUG);

        add_settings_field(Logonbox_Authenticator_Constants::OPTIONS_HOST, 'Host', array($this, 'logonbox_authenticator_settings_host'),
            Logonbox_Authenticator_Constants::OPTIONS_MENU_SLUG, Logonbox_Authenticator_Constants::OPTIONS_SECTION);

        add_settings_field(Logonbox_Authenticator_Constants::OPTIONS_MISSING_ARTIFACTS, 'Missing Artifacts', array($this, 'logonbox_authenticator_missing_artifacts'),
            Logonbox_Authenticator_Constants::OPTIONS_MENU_SLUG, Logonbox_Authenticator_Constants::OPTIONS_SECTION);

        add_settings_field(Logonbox_Authenticator_Constants::OPTIONS_ACTIVE, 'Active', array($this, 'logonbox_authenticator_settings_active'),
            Logonbox_Authenticator_Constants::OPTIONS_MENU_SLUG, Logonbox_Authenticator_Constants::OPTIONS_SECTION);

        add_settings_field(Logonbox_Authenticator_Constants::OPTIONS_DEBUG, 'Debug', array($this, 'logonbox_authenticator_settings_debug'),
            Logonbox_Authenticator_Constants::OPTIONS_MENU_SLUG, Logonbox_Authenticator_Constants::OPTIONS_SECTION);

        //register our settings
        register_setting( Logonbox_Authenticator_Constants::OPTIONS_GROUP, Logonbox_Authenticator_Constants::OPTIONS_HOST,
            array ("sanitize_callback" => array($this, 'logonbox_authenticator_sanitize_host')) );

        register_setting( Logonbox_Authenticator_Constants::OPTIONS_GROUP, Logonbox_Authenticator_Constants::OPTIONS_MISSING_ARTIFACTS);

        register_setting( Logonbox_Authenticator_Constants::OPTIONS_GROUP, Logonbox_Authenticator_Constants::OPTIONS_ACTIVE);

        register_setting( Logonbox_Authenticator_Constants::OPTIONS_GROUP, Logonbox_Authenticator_Constants::OPTIONS_DEBUG);
    }

    function logonbox_authenticator_settings_host() {
        $tag = Logonbox_Authenticator_Constants::OPTIONS_HOST;
        $host = esc_attr(Logonbox_Authenticator_Util::logonbox_authenticator_get_option($tag));
        echo "<input id='$tag' name='$tag' size='40' type='text' value='$host' />";
        echo "<br /> <small><i>Hostname (with port) to connect for keys of end users. e.g. my.company.directory or my.company.directory:8443</i></small>";
    }

    function logonbox_authenticator_missing_artifacts() {
        $tag = Logonbox_Authenticator_Constants::OPTIONS_MISSING_ARTIFACTS;
        $option = esc_attr(Logonbox_Authenticator_Util::logonbox_authenticator_get_option($tag));
         
        $allow = "";
        $deny = "";

        if ($option === "ALLOW_LOGIN" || $option == null || $option == "")
        {
            $allow = "selected='true'";
            $deny = "";
        }
        else 
        {
            
            $deny = "selected='true'";
            $allow = "";
        }
        
        echo "<select id='$tag' name='$tag'>";
        echo "<option value='ALLOW_LOGIN' $allow>Allow Login</option>";
        echo "<option value='DENY_LOGIN' $deny>Deny Login</option>";
        echo "</select>";
        echo "<br /> <small><i>Describes how authenticator should behave in case keys are not yet setup for an end user; cryptographic keys are required for basic functioning and authentication.</i></small><br /><small><strong>Note: If you choose 'deny' end users without keys would be locked out.</strong></small>";
    }

    function logonbox_authenticator_settings_active() {
        $tag = Logonbox_Authenticator_Constants::OPTIONS_ACTIVE;
        echo "<input name='$tag' id='$tag' type='checkbox' value='1' class='code' " . checked( 1, Logonbox_Authenticator_Util::logonbox_authenticator_get_option( $tag ), false ) . " /> <label for='$tag'>Active</label>";
        echo "<br /> <small><i>Activate LogonBox authenticator, please note if hostname, end user keys are not setup you would lock the system, you can allow end users without keys with missing artifacts option, which is set to allow with no keys by default. Once system is tested and setup you can change missing artifacts option to deny to disallow end users without key setup. On activation current session is still valid, before you log out, ensure system is setup properly.</i></small><br /> <small><i><strong>Note: If host option is not set properly this option will be reverted to disabled state. Setup host first then only activate plugin.</strong></i></small>";
    }

    function logonbox_authenticator_settings_debug() {
        $tag = Logonbox_Authenticator_Constants::OPTIONS_DEBUG;
        echo "<input name='$tag' id='$tag' type='checkbox' value='1' class='code' " . checked( 1, Logonbox_Authenticator_Util::logonbox_authenticator_get_option( $tag ), false ) . " /> <label for='$tag'>Debug</label>";
        echo "<br /> <small><i>Enable debug logs.</i></small>";
    }

    function logonbox_authenticator_sanitize_host( $input ) {
        
        $host = "https://" . $input;

        if (!Logonbox_Authenticator_Util::is_valid_host($host)) {
            add_settings_error(Logonbox_Authenticator_Constants::OPTIONS_HOST, "", "Invalid hostname.");
            return "";
        }

        Logonbox_Authenticator_Util::info_log("Checking hostname " . $host);

        $response = wp_remote_get($host . "/discover" );

        $http_code = wp_remote_retrieve_response_code( $response );

        Logonbox_Authenticator_Util::info_log("The status code is " . $http_code);

        if (!is_numeric($http_code) || intval($http_code) != 200) {
            add_settings_error(Logonbox_Authenticator_Constants::OPTIONS_HOST, "", "Cannot connect hostname.");
            return "";
        }

        return $input;
    }

    /**
     * @param WP_User $user
     * @throws Exception
     */
    private function start_logonbox_authenticator_second_factor_process(WP_User $user)
    {
        $username = $user->user_login;
        $email = $user->user_email;

        $host_url = Logonbox_Authenticator_Util::logonbox_authenticator_host_option_url();

        $host = parse_url($host_url, PHP_URL_HOST);
        $port = parse_url($host_url, PHP_URL_PORT);

         Logonbox_Authenticator_Util::debug_log("Remote host info [" . $host . "] [" . $port . "].");

        $remoteService = new RemoteServiceImpl($host,
            $port, Logonbox_Authenticator_Util::logger()
        );

        $authenticatorClient = new AuthenticatorClient($remoteService, Logonbox_Authenticator_Util::logger());

        $keys = $authenticatorClient->getUserKeys($email);
        $keys_length = count($keys);

        if ($keys_length == 0)
        {
            $missing_artifacts = Logonbox_Authenticator_Util::logonbox_authenticator_missing_artifacts();
                    

            if ($missing_artifacts == "ALLOW_LOGIN") 
            {
                Logonbox_Authenticator_Util::info_log("Allowing user with empty keys " . $username);
                return $user;
            }
        }


        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $redirect_uri = $_SESSION[Logonbox_Authenticator_Constants::SESSION_RECORD_LAST_URI] ?? admin_url();

        Logonbox_Authenticator_Util::info_log("Redirect to post login URI " . $redirect_uri . ".");

        wp_logout();

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $authenticatorRequest = $authenticatorClient
            ->generateRequest($email, get_site_url() . "/index.php/logonbox-authenticator-verify-signature?response={response}");

        $session_data = Logonbox_Authenticator_Util::setup_session_payload($email, $authenticatorRequest->getEncodedPayload(), $redirect_uri);

        $_SESSION[Logonbox_Authenticator_Constants::SESSION_ENCODED_PAYLOAD] = $session_data;

        Logonbox_Authenticator_Util::debug_log("Redirecting to " . $authenticatorRequest->getSignUrl() . ".");

        wp_redirect($authenticatorRequest->getSignUrl());

        exit();
    }
}
