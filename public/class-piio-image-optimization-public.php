<?php

/**
* The public-facing functionality of the plugin.
*
* @link       https://piio.co
* @since      0.9.0
*
* @package    Piio_Image_Optimization
* @subpackage Piio_Image_Optimization/public
*/

/**
* The public-facing functionality of the plugin.
*
* Defines the plugin name, version, and two examples hooks for how to
* enqueue the public-facing stylesheet and JavaScript.
*
* @package    Piio_Image_Optimization
* @subpackage Piio_Image_Optimization/public
* @author     Piio, Inc. <support@piio.co>
*/
if (!class_exists('Piio_Image_Optimization_Public')) {
    class Piio_Image_Optimization_Public
    {

        /**
        * The ID of this plugin.
        *
        * @since    0.9.0
        * @access   private
        * @var      string    $plugin_name    The ID of this plugin.
        */
        private $plugin_name;

        /**
        * The version of this plugin.
        *
        * @since    0.9.0
        * @access   private
        * @var      string    $version    The current version of this plugin.
        */
        private $version;

        /**
        * Initialize the class and set its properties.
        *
        * @since    0.9.0
        * @param      string    $plugin_name       The name of the plugin.
        * @param      string    $version    The version of this plugin.
        */
        public function __construct($plugin_name, $version)
        {
            $this->plugin_name = $plugin_name;
            $this->version = $version;

            /**
            * The class responsible for defining callbacks for replacing
            * of the plugin.
            */
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-piio-image-optimization-callbacks.php';
        }

        public function start_output_buffer()
        {
            if (!defined('DOING_AJAX') || !DOING_AJAX) {
                ob_start(array($this, 'output_buffer_callback'));
            }
        }

        public function output_buffer_callback($buffer, $phase)
        {
            if ($phase & PHP_OUTPUT_HANDLER_FINAL || $phase & PHP_OUTPUT_HANDLER_END) {
                return $this->filter_images($buffer);
            }

            return $buffer;
        }

        public function get_largest_image_src($imgTag)
        {
            // If tag contains srcset
            if (preg_match('/\bsrcset[\s\r\n]*=[\s\r\n]*[\'"]?(.*?)[\'">\s\r\n]/xis', $imgTag, $matches)) {
                // Get all sources
                $sources = array_map('trim', explode(',', $matches[1]));
                $size = 0;
                foreach ($sources as $source) {
                    // Extract url and size
                    $attr = array_map('trim', explode(' ', $source));
                    // Check if we have size with unit
                    if ($attr[1]) {
                        // url = $attr[0], size with unit = $attr[1]
                        // remove unit (w or x)
                        $attr[1] = intval(substr($attr[1], 0, strlen($attr[1]) - 1));
                        if ($attr[1] > $size) {
                            $retSrc = $attr[0];
                            $size = $attr[1];
                        }
                    }
                }
                if (isset($retSrc)) {
                    return Piio_Image_Optimization_File_Helper::url_to_absolute($retSrc);
                }
            }
            // Else just return src
            preg_match('/\bsrc[\s\r\n]*=[\s\r\n]*[\'"]?(.*?)[\'">\s\r\n]/xis', $imgTag, $matches);
            return (isset($matches[1])) ? Piio_Image_Optimization_File_Helper::url_to_absolute($matches[1]) : null;
        }

        public function filter_images($HTMLContent)
        {
            // Get consumption
            $consumption = $this->_check_consumption();

            // Check for standard or adv optimization
            $optimization = get_option('piio_imageopt_optimization');
            $adv_optimization = isset($optimization[0]) ? ($optimization[0] === "1") : true;

            // Replace tags if adv optimization (js will take care of it) or consumption is not in danger
            $replace_tags = $adv_optimization || ($consumption !== 'danger');

            // Check if optimize for editors is set
            $optimize_editors_opt = get_option('piio_imageopt_optimize_editors');
            $optimize_editors = isset($optimize_editors_opt[0]) ? ($optimize_editors_opt[0] === "1") : true;

            $editor = !$optimize_editors && (current_user_can('edit_others_posts') || current_user_can('edit_others_pages'));

            // If in wp admin pages
            $is_admin = is_admin();

            if ($replace_tags && !$is_admin && !$editor) {
                // Get api key
                $api_key = get_option('piio_imageopt_api_key');
                // Check if webP is enabled
                $enable_webp = get_option('piio_imageopt_enable_webp');
                $enable_webp = isset($enable_webp[0]) ? ($enable_webp[0] === "1") : true;
                // Check if browser accepts webP
                $client_accept_webp = strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false;

                if(!empty(trim($api_key))) {
                    $HTMLContent = $this->_replace_img_tags($HTMLContent, $adv_optimization, $api_key, $enable_webp, $client_accept_webp);
                }

                // Check if we have to optimize background images
                $optimize_bck = get_option('piio_imageopt_optimize_bck');
                $optimize_bck = isset($optimize_bck[0]) ? ($optimize_bck[0] === "1") : true;

                if ((!empty(trim($api_key))) && $optimize_bck) {
                    $HTMLContent = $this->_replace_bck_styles($HTMLContent, $adv_optimization, $api_key, $enable_webp, $client_accept_webp);
                }
            }
            return $HTMLContent;
        }

        /**
        * Register the JavaScript for the public-facing side of the site.
        *
        * @since    0.9.0
        */
        public function enqueue_scripts()
        {
            $api_key = get_option('piio_imageopt_api_key');
            $position = get_option('piio_imageopt_script_position');
            $sw = 0;//get_option('piio_imageopt_sw_enabled');

            $lazy_mode = get_option('piio_imageopt_lazy');
            $lazy_mode = (isset($lazy_mode[0]) && ($lazy_mode[0] === "1")) ? 'strict' : 'friendly';

            $enable_webp = get_option('piio_imageopt_enable_webp');
            $enable_webp = isset($enable_webp[0]) ? ($enable_webp[0] === "1")  : false;

            $in_footer = isset($position[0]) ? ($position[0] === "0") : false;
            $sw_enabled = isset($sw[0]) ? ($sw[0] === "1") : false;
            $consumption = $this->_check_consumption();

            add_action('wp_head', function() use ($api_key,$sw_enabled,$consumption) {
                    echo  '<link rel="preconnect" href="//pcdn.piiojs.com" crossorigin>';
                    echo  '<link rel="preload" as="script" href="//pcdn.piiojs.com/'.$api_key.'/image.min.js">';
                    if($sw_enabled){
                        if($consumption !== "danger"){
                            echo  "<script>";
                            echo "if ('serviceWorker' in navigator) {window.addEventListener('load', function() {";
                            echo "navigator.serviceWorker.register('".get_site_url()."/piio-service-worker.js', {scope: './'
                            }).then(function(registration) {initValues(registration);});";
                            echo "function initValues(){if(navigator.serviceWorker.controller){navigator.serviceWorker.controller.postMessage({'screenWidth':window.innerWidth,'webp':(canUseWebP()?1:0)});}}";
                            echo "function canUseWebP() {var elem = document.createElement('canvas');if (!!(elem.getContext && elem.getContext('2d'))) {return elem.toDataURL('image/webp').indexOf('data:image/webp') == 0;}return false;}";
                            echo "})}";
                            echo "</script>";
                        }else{
                            echo  "<script>";
                            echo "if ('serviceWorker' in navigator) {window.addEventListener('load', function() {";
                            echo "navigator.serviceWorker.getRegistrations().then(function(registrations) {for(let registration of registrations) {var regExp = new RegExp('piio');if(regExp.exec(registration.active.scriptURL)){registration.unregister();}}});";
                            echo "});";
                            echo "};";
                            echo "</script>";
                        }
                    }
            });

            $piio_script = "(function(i,m,a,g,e) {
                          e = i.getElementsByTagName(m)[0], (g = i.createElement(m)).src = \"//pcdn.piiojs.com/\"+a+\"/image.min.js\",
                          g.onerror = function() {
                            (g = i.createElement(m)).src = \"https://fs.piio.co/image-failover.min.js\",
                            e.parentNode.insertBefore(g, e);
                          }, e.parentNode.insertBefore(g, e);
                        }(document, \"script\", \"".$api_key."\"));";



            wp_register_script('piio.js', '', array(), false, $in_footer);
            wp_add_inline_script('piio.js', $piio_script ,'after');
            wp_enqueue_script('piio.js');


        }

        private function _check_consumption()
        {
            $piio_consumption_last_check = get_option('piio_imageopt_consumption_last_check');
            $retrieve = false;
            $now = date("Y-m-d");

            // If no option or more than one day, check again
            if (!$piio_consumption_last_check) {
                $retrieve = true;
            } else {
                $diffInDays = intval($this->_date_difference($piio_consumption_last_check, $now), 10);
                $retrieve = $diffInDays > 1;
            }

            if ($retrieve) {
                // Retrieve consumption from server
                $this->_retrieve_consumption();
            }

            return get_option('piio_imageopt_consumption_status');
        }

        private function _retrieve_consumption()
        {
            // Request Piio for consumption
            $api_key = get_option('piio_imageopt_api_key');
            if ($api_key) {
                $url = "https://app.piio.co/consumptionStatus/" . $api_key;
                $response = wp_remote_get($url);
                if (!is_wp_error($response) && isset($response['body'])) {
                    $response = json_decode($response['body']);
                    $now = date("Y-m-d");
                    update_option('piio_imageopt_consumption_last_check', $now);
                    if ($response->status == 200) {
                        update_option('piio_imageopt_consumption_status', $response->message);
                    }
                }
            }
        }

        private function _date_difference($date_1, $date_2)
        {
            $datetime1 = date_create($date_1);
            $datetime2 = date_create($date_2);

            $interval = date_diff($datetime1, $datetime2);
            return $interval->days;
        }

        private function _replace_img_tags($HTMLContent, $adv_optimization, $api_key, $enable_webp, $client_accept_webp)
        {
            $placeholder = "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7";
            $img_tags_matches = array();
            preg_match_all('/<img[\s\r\n]+.*?>/is', $HTMLContent, $img_tags_matches);

            $search = array();
            $replace = array();

            $std_opt_img_added = false;

            foreach ($img_tags_matches[0] as $imgHTML) {
                // Get largest image src
                $src = $this->get_largest_image_src($imgHTML);

                // Don't to the replacement if the image is a data-uri or has class piio-skip
                if (!preg_match("/data:image/is", $src) && !preg_match("/class=(['\"]|(['\"][^'\"]*)\s)piio-skip(['\"]|\s([^'\"]*)['\"])/is", $imgHTML)  && !preg_match("/class=(['\"]|(['\"][^'\"]*)\s)wcuf_file_preview_list_item_image(['\"]|\s([^'\"]*)['\"])/is", $imgHTML)) {
                    if ($adv_optimization) {
                        // Advanced optimization enabled
                        $replaceHTML = $this->_advanced_mode($imgHTML, $src, $placeholder);
                    } else {
                        // Standard optimization enabled
                        $replaceHTML = $this->_standard_mode($imgHTML, $src, $api_key, $enable_webp, $client_accept_webp);

                        // Add img with data-piio to log user data for consumption
                        if (!$std_opt_img_added) {
                            $HTMLContent = $this->_add_data_piio_img($HTMLContent);
                            $std_opt_img_added = true;
                        }
                    }

                    array_push($search, $imgHTML);
                    array_push($replace, $replaceHTML);
                }
            }

            $search = array_unique($search);
            $replace = array_unique($replace);

            return str_replace($search, $replace, $HTMLContent);
        }

        private function _replace_bck_styles($HTMLContent, $adv_optimization, $api_key, $enable_webp, $client_accept_webp)
        {
            $matches = array();
            preg_match_all("/<[^>]*?\sstyle=['\"][^>]*?background(-image)?:.*?url\(\s*.*?\s*\);?.*?['\"].*?>/ismS", $HTMLContent, $matches);

            $search = array();
            $replace = array();

            $params = "";
            // Set wp param is webP is enabled
            if ($enable_webp && $client_accept_webp) {
                $params .= "wp,1";
            }
            $callback = new Piio_Image_Optimization_Callbacks($api_key, $params);

            foreach ($matches[0] as $bckHTML) {
                // Don't to the replacement if the image is a data-uri or has class piio-skip
                if (!preg_match("/url\(['\"]?data:image/xis", $bckHTML) && !preg_match("/class=(['\"]|(['\"][^'\"]*)\s)piio-skip(['\"]|\s([^'\"]*)['\"])/xis", $bckHTML)) {
                    $replaceHTML = '';

                    if ($adv_optimization) {
                        // Replace the background-image attribute from style and add the data-piio-bck attribute
                        $replaceHTML = preg_replace("/(\sstyle=['\"][^>]*?)(background-image:.*?url\(['\"]?(\s*.*?\s*)['\"]?\));?(.*?['\"])/xis", '$1$4 data-piio-bck=$3', $bckHTML);
                        $replaceHTML = preg_replace("/(\sstyle=['\"][^>]*?background:.*?)(url\(['\"]?(\s*.*?\s*)['\"]?)\);?(.*?['\"])/xis", '$1$4 data-piio-bck=$3', $replaceHTML);
                    } else {
                        // Using callback because of replace length
                        $replaceHTML = preg_replace_callback("/(\sstyle=['\"][^>]*?)(background-image:.*?url\(['\"]?(\s*.*?\s*)['\"]?\));?(.*?['\"])/is", array($callback, 'callback_background_image'), $bckHTML);
                        $replaceHTML = preg_replace_callback("/(\sstyle=['\"][^>]*?background:.*?url\(['\"]?)(\s*.*?\s*)(['\"]?\);?.*?['\"])/xis", array($callback, 'callback_background'), $replaceHTML);
                    }

                    array_push($search, $bckHTML);
                    array_push($replace, $replaceHTML);
                }
            }

            $search = array_unique($search);
            $replace = array_unique($replace);

            return str_replace($search, $replace, $HTMLContent);
        }

        private function _get_transparent_svg_with_piio()
        {
            return "<img src='data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7' data-piio='" . plugin_dir_url(__FILE__) . 'images/transparent.svg' . "' style='width:1px !important;height:1px !important;position:absolute !important;top:0 !important;left:-1px !important'>";
        }

        private function _advanced_mode($imgHTML, $src, $placeholder)
        {
            // Remove srcset and sizes attributes
            $replaceHTML = preg_replace('/<img(.*?)srcset=["\'].*?["\']/is', '<img$1', $imgHTML);
            $replaceHTML = preg_replace('/<img(.*?)sizes=["\'].*?["\']/is', '<img$1', $replaceHTML);

            // Replace the src with the data-piio attribute
            $replaceHTML = preg_replace('/<img(.*?)src=/is', '<img$1src="' . $placeholder . '" data-piio=', $replaceHTML);

            return $replaceHTML;
        }

        private function _standard_mode($imgHTML, $src, $api_key, $enable_webp, $client_accept_webp)
        {
            $srcAttr = urlencode($src);

            // Remove srcset and sizes attributes as we are adding our own
            $replaceHTML = preg_replace('/<img(.*?)srcset=["\'].*?["\']/is', '<img$1', $imgHTML);
            $replaceHTML = preg_replace('/<img(.*?)sizes=["\'].*?["\']/is', '<img$1', $replaceHTML);

            // Check if image is an svg
            if (pathinfo($srcAttr, PATHINFO_EXTENSION) == 'svg') {
                $replaceHTML = preg_replace('/<img(.*?)src=(["\']?).*?[\'">\s\r\n]/is', '<img$1src="' . 'https://pcdn.piiojs.com/i/' . $api_key . '/' . $srcAttr . '"', $replaceHTML);
            } else {
                $params = '';
                // Set wp param is webP is enabled
                if ($enable_webp && $client_accept_webp) {
                    $params .= 'wp,1,';
                }

                $breakpoints = array("576", "768", "992", "1200");
                $srcset_values = array();
                foreach ($breakpoints as $width) {
                    $local_params = $params . 'vw,' . $width;
                    array_push($srcset_values, 'https://pcdn.piiojs.com/i/' . $api_key . '/' . $local_params . '/' . $srcAttr . ' ' . $width . 'w');
                }
                // Replace src with largest image and add srcset
                $replaceHTML = preg_replace('/<img(.*?)src=(["\']?).*?[\'">\s\r\n]/is', '<img$1src="' . 'https://pcdn.piiojs.com/i/' . $api_key . (($params != '') ? '/' . $params : '') . '/' . $srcAttr . '" srcset="' . implode(', ', $srcset_values) . '"', $replaceHTML);
            }

            return $replaceHTML;
        }

        private function _add_data_piio_img($HTMLContent)
        {
            $body_matches = array();
            if (preg_match('/<body.*?>/is', $HTMLContent, $body_matches, PREG_OFFSET_CAPTURE) > 0) {
                // Get <body start position
                // Body_matches[0][0] contains string, body_matches[0][1] contains string index
                $body_end_pos = $body_matches[0][1] + strlen($body_matches[0][0]);
                $HTMLContent = substr_replace($HTMLContent, $this->_get_transparent_svg_with_piio(), $body_end_pos, 0);
            };
            return $HTMLContent;
        }
    }
}
