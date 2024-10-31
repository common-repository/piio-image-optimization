<?php

/**
* Fired when the plugin is uninstalled.
*
* When populating this file, consider the following flow
* of control:
*
* - This method should be static
* - Check if the $_REQUEST content actually is the plugin name
* - Run an admin referrer check to make sure it goes through authentication
* - Verify the output of $_GET makes sense
* - Repeat with other user roles. Best directly by using the links/query string parameters.
* - Repeat things for multisite. Once for a single site in the network, once sitewide.
*
* This file may be updated more in future version of the Boilerplate; however, this is the
* general skeleton and outline for how the file should work.
*
* For more information, see the following discussion:
* https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
*
* @link       https://piio.co
* @since      0.9.0
*
* @package    Piio_Image_Optimization
*/

// If uninstall not called from WordPress, then exit.
if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

$group = 'piio_imageopt_menu';
$options = array(
    'piio_imageopt_enabled',
    'piio_imageopt_api_key',
    'piio_imageopt_optimize_bck',
    'piio_imageopt_optimization',
    'piio_imageopt_script_position',
    'piio_imageopt_enable_webp',
    'piio_imageopt_consumption_last_check',
    'piio_imageopt_consumption_status',
    'piio_imageopt_lazy',
    'piio_imageopt_optimize_editors'
);
foreach ($options as $option_name) {
    delete_option($option_name);
}
