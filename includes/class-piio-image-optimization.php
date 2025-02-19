<?php

/**
* The file that defines the core plugin class
*
* A class definition that includes attributes and functions used across both the
* public-facing side of the site and the admin area.
*
* @link       https://piio.co
* @since      0.9.0
*
* @package    Piio_Image_Optimization
* @subpackage Piio_Image_Optimization/includes
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
* @since      0.9.0
* @package    Piio_Image_Optimization
* @subpackage Piio_Image_Optimization/includes
* @author     Piio, Inc. <support@piio.co>
*/
if (!class_exists('Piio_Image_Optimization')) {
    class Piio_Image_Optimization
    {

        /**
        * The loader that's responsible for maintaining and registering all hooks that power
        * the plugin.
        *
        * @since    0.9.0
        * @access   protected
        * @var      Piio_Image_Optimization_Loader    $loader    Maintains and registers all hooks for the plugin.
        */
        protected $loader;

        /**
        * The unique identifier of this plugin.
        *
        * @since    0.9.0
        * @access   protected
        * @var      string    $plugin_name    The string used to uniquely identify this plugin.
        */
        protected $plugin_name;

        /**
        * The current version of the plugin.
        *
        * @since    0.9.0
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
        * @since    0.9.0
        */
        public function __construct()
        {
            if (defined('PIIO_IMAGE_OPTIMIZATION_VERSION')) {
                $this->version = PIIO_IMAGE_OPTIMIZATION_VERSION;
            } else {
                $this->version = '0.9.18';
            }
            $this->plugin_name = 'piio-image-optimization';

            $this->load_dependencies();
            $this->set_locale();
            if (is_admin()) {
                $this->define_admin_hooks();
            }
            $this->define_public_hooks();
        }

        /**
        * Load the required dependencies for this plugin.
        *
        * Include the following files that make up the plugin:
        *
        * - Piio_Image_Optimization_Loader. Orchestrates the hooks of the plugin.
        * - Piio_Image_Optimization_i18n. Defines internationalization functionality.
        * - Piio_Image_Optimization_Admin. Defines all hooks for the admin area.
        * - Piio_Image_Optimization_Public. Defines all hooks for the public side of the site.
        *
        * Create an instance of the loader which will be used to register the hooks
        * with WordPress.
        *
        * @since    0.9.0
        * @access   private
        */
        private function load_dependencies()
        {
            /**
            * The class responsible for orchestrating the actions and filters of the
            * core plugin.
            */
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-piio-image-optimization-loader.php';

            /**
            * The class is a helper to deal with urls
            */
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-piio-image-optimization-url-helper.php';

            /**
            * The class responsible for defining internationalization functionality
            * of the plugin.
            */
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-piio-image-optimization-i18n.php';

            /**
            * The class responsible for defining all actions that occur in the admin area.
            */
            require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-piio-image-optimization-admin.php';

            /**
            * The class responsible for defining all actions that occur in the public-facing
            * side of the site.
            */
            require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-piio-image-optimization-public.php';

            $this->loader = new Piio_Image_Optimization_Loader();
        }

        /**
        * Define the locale for this plugin for internationalization.
        *
        * Uses the Piio_Image_Optimization_i18n class in order to set the domain and to register the hook
        * with WordPress.
        *
        * @since    0.9.0
        * @access   private
        */
        private function set_locale()
        {
            $plugin_i18n = new Piio_Image_Optimization_i18n();

            $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
        }

        /**
        * Register all of the hooks related to the admin area functionality
        * of the plugin.
        *
        * @since    0.9.0
        * @access   private
        */
        private function define_admin_hooks()
        {
            $plugin_admin = new Piio_Image_Optimization_Admin($this->get_plugin_name(), $this->get_version());
            $this->loader->add_action('admin_menu', $plugin_admin, 'display_admin_page');
            $this->loader->add_action('admin_init', $plugin_admin, 'setup_sections');
            $this->loader->add_action('admin_init', $plugin_admin, 'setup_fields');
            $this->loader->add_action('admin_notices', $plugin_admin, 'check_piio_incompatibility');
            $this->loader->add_action('admin_notices', $plugin_admin, 'check_consumption_status');
            $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
            $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        }

        /**
        * Register all of the hooks related to the public-facing functionality
        * of the plugin.
        *
        * @since    0.9.0
        * @access   private
        */
        private function define_public_hooks()
        {
            $plugin_public = new Piio_Image_Optimization_Public($this->get_plugin_name(), $this->get_version());
            $piio_enabled_option = get_option('piio_imageopt_enabled');
            $is_piio_enabled = (isset($piio_enabled_option[0])) ? ($piio_enabled_option[0] === "1") : false;

            if ($is_piio_enabled) {
                // Start capturing buffer
                $this->loader->add_action('template_redirect', $plugin_public, 'start_output_buffer', -100);

                // Disable lazy loading for wp rocket
                add_filter('do_rocket_lazyload', '__return_false');

                // Disable lazy loading for jetpack
                add_filter('lazyload_is_enabled', '__return_false');
                
                //Disable default lazyloading
                add_filter( 'wp_lazy_loading_enabled', '__return_false' );

                // Disable srcset from wp
                add_filter('wp_calculate_image_srcset', '__return_empty_array');

                $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
            }


        }

        /**
        * Run the loader to execute all of the hooks with WordPress.
        *
        * @since    0.9.0
        */
        public function run()
        {
            $this->loader->run();
        }

        /**
        * The name of the plugin used to uniquely identify it within the context of
        * WordPress and to define internationalization functionality.
        *
        * @since     0.9.0
        * @return    string    The name of the plugin.
        */
        public function get_plugin_name()
        {
            return $this->plugin_name;
        }

        /**
        * The reference to the class that orchestrates the hooks with the plugin.
        *
        * @since     0.9.0
        * @return    Piio_Image_Optimization_Loader    Orchestrates the hooks of the plugin.
        */
        public function get_loader()
        {
            return $this->loader;
        }

        /**
        * Retrieve the version number of the plugin.
        *
        * @since     0.9.0
        * @return    string    The version number of the plugin.
        */
        public function get_version()
        {
            return $this->version;
        }
    }
}
