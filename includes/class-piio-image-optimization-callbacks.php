<?php

/**
* Has callbacks helpers for the plugin.
*
* @link       https://piio.co
* @since      0.9.8
*
* @package    Piio_Image_Optimization
* @subpackage Piio_Image_Optimization/includes
*/

/**
* Has callbacks helpers for the plugin.
*
* @package    Piio_Image_Optimization
* @subpackage Piio_Image_Optimization/includes
* @author     Piio, Inc. <support@piio.co>
*/
if (!class_exists('Piio_Image_Optimization_Callbacks')) {
    class Piio_Image_Optimization_Callbacks
    {
        private $api_key;
        private $params;

        public function __construct($api_key, $params)
        {
            $this->api_key = $api_key;
            $this->params = $params;
        }

        public function callback_background_image($matches)
        {
            $srcAttr = urlencode($matches[3]);
            return $matches[1] . "background-image:url(https://pcdn.piiojs.com/i/" . $this->api_key . (($this->params != '') ? '/' . $this->params : '') . "/" . $srcAttr . ");" . $matches[4];
        }

        public function callback_background($matches)
        {
            $srcAttr = urlencode($matches[2]);
            return $matches[1] . "https://pcdn.piiojs.com/i/" . $this->api_key . (($this->params != '') ? '/' . $this->params : '') . "/" . $srcAttr . $matches[3];
        }
    }
}
