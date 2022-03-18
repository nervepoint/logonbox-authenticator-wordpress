<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://example.com
 * @since             1.0.0
 * @package           Logonbox_Authenticator
 *
 * @wordpress-plugin
 * Plugin Name:       Logonbox Authenticator
 * Plugin URI:        http://example.com/plugin-name-uri/
 * Description:       Provides second factor authentication by LogonBox.
 * Version:           1.0.0
 * Author:            LogonBox
 * Author URI:        http://example.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       logonbox-authenticator
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	exit();
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
const PLUGIN_NAME_VERSION = '1.0.0';

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-logonbox-authenticator-activator.php
 */
function activate_plugin_name() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-logonbox-authenticator-activator.php';
	Logonbox_Authenticator_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-logonbox-authenticator-deactivator.php
 */
function deactivate_plugin_name() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-logonbox-authenticator-deactivator.php';
	Logonbox_Authenticator_Deactivator::deactivate();
}

/**
 * On activation, plugins can run a routine to add rewrite rules, add custom database tables, or set default option values.
 */
register_activation_hook( __FILE__, 'activate_plugin_name' );

/**
 * On deactivation, plugins can run a routine to remove temporary data such as cache and temp files and directories.
 * The deactivation hook is sometimes confused with the uninstall hook. The uninstall hook is best suited to delete all data permanently such as deleting plugin options and custom tables,
 */
register_deactivation_hook( __FILE__, 'deactivate_plugin_name' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-logonbox-authenticator.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_plugin_name() {

	$plugin = new Logonbox_Authenticator();
	$plugin->run();

}
run_plugin_name();
