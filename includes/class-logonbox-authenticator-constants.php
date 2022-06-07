<?php

/**
 * Class Logonbox_Authenticator_Constants
 *
 * String constants in plugin.
 */
class Logonbox_Authenticator_Constants
{
    const SESSION_ENCODED_PAYLOAD = "logonbox_authenticator_encoded_payload";
    const SESSION_MARK_USER_AUTHORIZED = "logonbox_authenticator_is_authorized";
    const SESSION_RECORD_LAST_URI = "logonbox_authenticator_last_uri";
    const SESSION_REJECT_FLASH_MESSAGE = "logonbox_authenticator_reject_flash_message";

    const OPTIONS_MENU_SLUG = "logonbox_authenticator_main_menu";

    const OPTIONS_SECTION = "logonbox_authenticator_settings_section";

    const OPTIONS_GROUP = "logonbox-authenticator-settings-group";
    const OPTIONS_HOST = "logonbox_authenticator_host";
    const OPTIONS_PROMPT_TEXT = "logonbox_authenticator_prompt_text";
    const OPTIONS_AUTHORIZE_TEXT = "logonbox_authenticator_authorize_text";
    const OPTIONS_MISSING_ARTIFACTS = "logonbox_authenticator_missing_artifacts";
    const OPTIONS_ACTIVE = "logonbox_authenticator_active";
    const OPTIONS_USE_FOR_ADMINISTRATORS = "logonbox_authenticator_use_for_administrators";
    const OPTIONS_USE_FOR_USERS = "logonbox_authenticator_use_for_users";
    const OPTIONS_DEBUG = "logonbox_authenticator_debug";

    const SESSION_DATA_KEY_PAYLOAD = "encoded_payload";
    const SESSION_DATA_KEY_USERNAME = "username";
    const SESSION_DATA_KEY_REDIRECT_URI = "redirect_uri";
}