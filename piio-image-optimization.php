<?php

/**
* Piio Image Optimization Plugin
*
*
* @link              https://piio.co
* @since             0.9.0
* @package           Piio_Image_Optimization
*
* @wordpress-plugin
* Plugin Name:       Piio Image Optimization
* Plugin URI:        https://piio.co/wordpress
* Description:       Generates responsive and optimized images, so you don't have to.
* Version:           0.9.29
* Author:            Piio, Inc.
* Author URI:        https://piio.co
* License:           GPL-2.0+
* License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
* Text Domain:       piio-image-optimization
* Domain Path:       /languages
*/

// If this file is called directly, abort.
if (! defined('WPINC')) {
    die;
}

/**
* Currently plugin version.
*/
define('PIIO_IMAGE_OPTIMIZATION_VERSION', '0.9.29');

/**
* The code that runs during plugin activation.
* This action is documented in includes/class-piio-image-optimization-activator.php
*/
if (!function_exists('activate_piio_image_optimization')) {
    function activate_piio_image_optimization()
    {
        require_once plugin_dir_path(__FILE__) . 'includes/class-piio-image-optimization-activator.php';
        Piio_Image_Optimization_Activator::activate();
    }
}

/**
* The code that runs during plugin deactivation.
* This action is documented in includes/class-piio-image-optimization-deactivator.php
*/
if (!function_exists('deactivate_piio_image_optimization')) {
    function deactivate_piio_image_optimization()
    {
        require_once plugin_dir_path(__FILE__) . 'includes/class-piio-image-optimization-deactivator.php';
        Piio_Image_Optimization_Deactivator::deactivate();
    }
}

register_activation_hook(__FILE__, 'activate_piio_image_optimization');
register_deactivation_hook(__FILE__, 'deactivate_piio_image_optimization');

/**
* The core plugin class that is used to define internationalization,
* admin-specific hooks, and public-facing site hooks.
*/
require plugin_dir_path(__FILE__) . 'includes/class-piio-image-optimization.php';

if (!function_exists('plugin_add_settings_link')) {
    function plugin_add_settings_link($links)
    {
        $settings_link = '<a href="admin.php?page=piio-options-menu">' . __('Settings') . '</a>';
        array_push($links, $settings_link);
        return $links;
    }
}
$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'plugin_add_settings_link');

/**
* Begins execution of the plugin.
*
* Since everything within the plugin is registered via hooks,
* then kicking off the plugin from this point in the file does
* not affect the page life cycle.
*
* @since    0.9.0
*/
if (!function_exists('run_piio_image_optimization')) {
    function run_piio_image_optimization()
    {
        $plugin = new Piio_Image_Optimization();
        $plugin->run();
    }
}
run_piio_image_optimization();
