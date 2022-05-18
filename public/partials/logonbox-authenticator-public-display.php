<?php
/**
 * The page that verifies returned signature.
 *
 * @since      1.0.0
 *
 */

use Authenticator\AuthenticatorClient;
use Authenticator\AuthenticatorRequest;
use RemoteService\RemoteServiceImpl;

    /**
     * An 'authenticate' filter callback that authenticates the user using only the username.
     *
     * To avoid potential security vulnerabilities, this should only be used in the context of a programmatic login,
     * and unhooked immediately after it fires.
     *
     * @param WP_User $user
     * @param string $username
     * @param string $password
     * @return bool|WP_User a WP_User object if the username matched an existing user, or false if it didn't
     */
    if (!function_exists("allow_programmatic_login")) {
        function allow_programmatic_login($user, $username, $password)
        {
          //  var_dump($username);
            return get_user_by('login', $username);
        }
    }

    global $wp_version;
    if(version_compare($wp_version, "3.3", "<=")){
        echo '<link rel="stylesheet" type="text/css" href="' . admin_url('css/login.css') . '" />';
    }
    else if(version_compare($wp_version, "3.7", "<=")){
        echo '<link rel="stylesheet" type="text/css" href="' . admin_url('css/wp-admin.css') . '" />';
        echo '<link rel="stylesheet" type="text/css" href="' . admin_url('css/colors-fresh.css') . '" />';
    }
    else if(version_compare($wp_version, "3.8", "<=")){
        echo '<link rel="stylesheet" type="text/css" href="' . admin_url('css/wp-admin.css') . '" />';
        echo '<link rel="stylesheet" type="text/css" href="' . admin_url('css/colors.css') . '" />';
    }
    else {
        echo '<link rel="stylesheet" type="text/css" href="' . admin_url('css/login.min.css') . '" />';
    }

    try {

        if(session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION[Logonbox_Authenticator_Constants::SESSION_ENCODED_PAYLOAD])) {
            $response = $_GET["response"];

            $encodedPayload = $_SESSION[Logonbox_Authenticator_Constants::SESSION_ENCODED_PAYLOAD];
            $data = Logonbox_Authenticator_Util::recover_session_payload($encodedPayload);

            $target_user = $data->username;

            Logonbox_Authenticator_Util::info_log("Attempting verification for user " . $target_user);

            unset($_SESSION[Logonbox_Authenticator_Constants::SESSION_ENCODED_PAYLOAD]);

            $host = parse_url(Logonbox_Authenticator_Util::logonbox_authenticator_host_option(), PHP_URL_HOST);
            $port = parse_url(Logonbox_Authenticator_Util::logonbox_authenticator_host_option(), PHP_URL_PORT);

            $remoteService = new RemoteServiceImpl($host, $port, Logonbox_Authenticator_Util::logger());
            $authenticatorClient = new AuthenticatorClient($remoteService, Logonbox_Authenticator_Util::logger());

            $authenticatorRequest = new AuthenticatorRequest($authenticatorClient, $data->encoded_payload);
            $authenticatorResponse = $authenticatorRequest->processResponse($response);


            $verify = $authenticatorResponse->verify();
            Logonbox_Authenticator_Util::info_log("The verification result => " . $verify);

            if ($verify) {

                Logonbox_Authenticator_Util::info_log("Response verified attempting WP login.");

                wp_logout();

                add_filter( "authenticate", "allow_programmatic_login", 5, 3 );

                Logonbox_Authenticator_Util::info_log("Attempting fetching wp user by email " . $target_user);

                $user_from_source = get_user_by('email', $target_user);

                Logonbox_Authenticator_Util::info_log("wp user from source username found as " . $user_from_source->user_login);

                $user = wp_signon( array( "user_login" => $user_from_source->user_login ) );

                remove_filter( "authenticate", "allow_programmatic_login", 5);

                if ( is_a( $user, "WP_User" ) ) {
                    wp_set_current_user($user->ID, $user->user_login);
                }

                if ( is_user_logged_in() ) {
                    Logonbox_Authenticator_Util::info_log("WP login successful.");
                }

                Logonbox_Authenticator_Util::info_log("Marking session authorized.");
                $_SESSION[Logonbox_Authenticator_Constants::SESSION_MARK_USER_AUTHORIZED] = "true";

                Logonbox_Authenticator_Util::debug_log("Redirecting to URI " . $data->redirect_uri . ".");
                wp_redirect($data->redirect_uri);
            } else {
                Logonbox_Authenticator_Util::info_log("Response could not be verified clearing session.");
                wp_logout();
            }
        } else {
            Logonbox_Authenticator_Util::info_log("No payload data found in session clearing session.");
            wp_logout();

            wp_redirect(wp_login_url("", true));
        }

        exit();

    } catch (Exception $e) {
        if ($e->getMessage() == "The keystore rejected the signature request.") {

            Logonbox_Authenticator_Util::info_log("The keystore rejected the signature request.");
            wp_logout();

            $_SESSION[Logonbox_Authenticator_Constants::SESSION_REJECT_FLASH_MESSAGE] = "true";
            wp_redirect(wp_login_url("", true));

        } else {
            $tracker = Logonbox_Authenticator_Util::tracker_code();
            Logonbox_Authenticator_Util::error_log("Tracker: " . $tracker . " : " . $e, $e);

            echo "<style>.parent {
              top: 50%;
              position: relative;
            }
            .child {
              position: absolute;
              top: 50%;
              left: 50%;
              transform: translate(-50%, -50%);
            }</style>";
            echo "<div class='parent'>
                    <div class='child'><div>Problem in verifying signature. Please contact administrator.</div><div><strong>Track Id:</strong> $tracker</div></div>
              </div>";
        }
    }



