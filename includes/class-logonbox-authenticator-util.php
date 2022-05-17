<?php

use Logger\LoggerService;

class Logonbox_Authenticator_Util {

    // LOGGER
    private static $logger;

    public static function safeCheckLogger() {
        if (self::$logger == null) {
            self::setUpLogger(self::logonbox_authenticator_debug_option());
        }
    }

    public static function setUpLogger($debug = false) {
        self::$logger = new Logonbox_Authenticator_Logger();
        self::$logger->enableDebug($debug);
    }

    public static function logger(): LoggerService {
        return self::$logger;
    }

    static function debug_log($message) {
        if (self::$logger->isDebug()) {
            self::$logger->info("Logonbox Authenticator: " . $message);
        }
    }

    static function info_log($message) {
        self::$logger->info("Logonbox Authenticator: " . $message);
    }

    static function error_log($message, $exception) {
        self::$logger->error("Logonbox Authenticator: " . $message, $exception);
    }

    // SESSION

    static function setup_session_payload(string $username, string $encoded_payload, string $redirect_uri = null): string
    {
        if ($redirect_uri == null) {
            $redirect_uri = admin_url();
        }
        $session_data = [
            Logonbox_Authenticator_Constants::SESSION_DATA_KEY_PAYLOAD => $encoded_payload,
            Logonbox_Authenticator_Constants::SESSION_DATA_KEY_USERNAME => $username,
            Logonbox_Authenticator_Constants::SESSION_DATA_KEY_REDIRECT_URI => $redirect_uri
        ];

        $session_data_json = json_encode($session_data);

        return base64_encode($session_data_json);
    }

    static function recover_session_payload(string $payload) {
        $payload_json = base64_decode($payload);

        return json_decode($payload_json);
    }

    static function clear_all_session_data() {
        unset($_SESSION[Logonbox_Authenticator_Constants::SESSION_MARK_USER_AUTHORIZED]);
        unset($_SESSION[Logonbox_Authenticator_Constants::SESSION_ENCODED_PAYLOAD]);
        unset($_SESSION[Logonbox_Authenticator_Constants::SESSION_RECORD_LAST_URI]);
    }

    // URI/URL
    static function logonbox_authenticator_get_uri(): string
    {
        // Workaround for IIS which may not set REQUEST_URI, or QUERY parameters
        if (!isset($_SERVER["REQUEST_URI"]) ||
            (!empty($_SERVER["QUERY_STRING"]) && !strpos($_SERVER["REQUEST_URI"], "?", 0))) {
            $current_uri = substr($_SERVER["PHP_SELF"],1);
            if (isset($_SERVER["QUERY_STRING"]) AND $_SERVER["QUERY_STRING"] != "") {
                $current_uri .= "?".$_SERVER["QUERY_STRING"];
            }

            if (is_bool($current_uri)) {
                $current_uri_return = $current_uri ? "true" : "false";
            } else {
                $current_uri_return = strval($current_uri);
            }
            return $current_uri_return;
        }
        else {
            return strval($_SERVER["REQUEST_URI"]);
        }
    }

    // OPTIONS

    static function logonbox_authenticator_get_option($key, $default="") {
        if (is_multisite()) {
            return get_site_option($key, $default);
        }
        else {
            return get_option($key, $default);
        }
    }

    static function logonbox_authenticator_host_option() : string {
        return self::logonbox_authenticator_get_option(Logonbox_Authenticator_Constants::OPTIONS_HOST);
    }

    static function logonbox_authenticator_missing_artifacts() : string {
        return self::logonbox_authenticator_get_option(Logonbox_Authenticator_Constants::OPTIONS_MISSING_ARTIFACTS, "ALLOW_LOGIN");
    }

    static function logonbox_authenticator_active_option() : bool {
        return boolval(self::logonbox_authenticator_get_option(Logonbox_Authenticator_Constants::OPTIONS_ACTIVE));
    }

    static function logonbox_authenticator_debug_option() : bool {
        return boolval(self::logonbox_authenticator_get_option(Logonbox_Authenticator_Constants::OPTIONS_DEBUG));
    }

    // VALIDATION

    static function is_valid_host($host): bool
    {
        $result = filter_var($host, FILTER_VALIDATE_URL);
        return !is_bool($result) && $result == $host;
    }

    // TRACKER CODE
    static function tracker_code() {
        return str_replace(".", "_", uniqid("LB-", true));
    }

    // URI

    static function is_login_page(): bool
    {
        return parse_url(self::logonbox_authenticator_get_uri(), PHP_URL_PATH) ==
            parse_url(wp_login_url(), PHP_URL_PATH);
    }
}