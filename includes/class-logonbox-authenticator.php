<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @since      1.0.0
 *
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 */
class Logonbox_Authenticator {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Logonbox_Authenticator_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( "PLUGIN_NAME_VERSION" ) ) {
			$this->version = PLUGIN_NAME_VERSION;
		} else {
			$this->version = "1.0.0";
		}
		$this->plugin_name = "logonbox-authenticator";

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

        /**
         * LogonBox second factor authenticator code loading.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . "vendor/autoload.php";

        /**
         * Plugin helper logger.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . "includes/class-logonbox-authenticator-logger.php";

        /**
         * String constants used in plugin.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . "includes/class-logonbox-authenticator-constants.php";

        /**
         * Util function for common task for plugin.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . "includes/class-logonbox-authenticator-util.php";

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . "includes/class-logonbox-authenticator-loader.php";

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . "includes/class-logonbox-authenticator-i18n.php";

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . "admin/class-logonbox-authenticator-admin.php";

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . "public/class-logonbox-authenticator-public.php";

        $this->loader = new Logonbox_Authenticator_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Plugin_Name_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Logonbox_Authenticator_i18n();

		$this->loader->add_action( "plugins_loaded", $plugin_i18n, "load_plugin_textdomain" );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Logonbox_Authenticator_Admin( $this->get_plugin_name(), $this->get_version() );

        $this->loader->add_action("init", $plugin_admin, "logonbox_authenticator_init", 10);
		$this->loader->add_action("admin_menu", $plugin_admin, "logonbox_authenticator_options");
		$this->loader->add_action("update_option_" . Logonbox_Authenticator_Constants::OPTIONS_DEBUG, $plugin_admin,"logonbox_authenticator_option_debug_updated", 1, 3);
        $this->loader->add_action("wp_logout", $plugin_admin, "logonbox_authenticator_wp_logout");

        $this->loader->add_filter("authenticate", $plugin_admin, "logonbox_custom_authenticator", 10, 3);

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Logonbox_Authenticator_Public( $this->get_plugin_name(), $this->get_version() );

        $this->loader->add_action ("pre_get_posts", $plugin_public, "attend_logonbox_authenticator_verify_signature_page");

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @return    Logonbox_Authenticator_Loader    Orchestrates the hooks of the plugin.
	 *@since     1.0.0
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
