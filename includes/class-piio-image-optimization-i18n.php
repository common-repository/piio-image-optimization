<?php

/**
* Define the internationalization functionality
*
* Loads and defines the internationalization files for this plugin
* so that it is ready for translation.
*
* @link       https://piio.co
* @since      0.9.0
*
* @package    Piio_Image_Optimization
* @subpackage Piio_Image_Optimization/includes
*/

/**
* Define the internationalization functionality.
*
* Loads and defines the internationalization files for this plugin
* so that it is ready for translation.
*
* @since      0.9.0
* @package    Piio_Image_Optimization
* @subpackage Piio_Image_Optimization/includes
* @author     Piio, Inc. <support@piio.co>
*/
if ( !class_exists( 'Piio_Image_Optimization_i18n' ) ) {
	class Piio_Image_Optimization_i18n {


		/**
		* Load the plugin text domain for translation.
		*
		* @since    0.9.0
		*/
		public function load_plugin_textdomain() {

			load_plugin_textdomain(
				'piio-image-optimization',
				false,
				dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
			);

		}		
	}
}
