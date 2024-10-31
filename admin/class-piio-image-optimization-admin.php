<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link  https://piio.co
 * @since 0.9.0
 *
 * @package    Piio_Image_Optimization
 * @subpackage Piio_Image_Optimization/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Piio_Image_Optimization
 * @subpackage Piio_Image_Optimization/admin
 * @author     Piio, Inc. <support@piio.co>
 */
if (!class_exists('Piio_Image_Optimization_Admin')) {
    class Piio_Image_Optimization_Admin
    {

        /**
         * The ID of this plugin.
         *
         * @since  0.9.0
         * @access private
         * @var    string    $plugin_name    The ID of this plugin.
         */
        private $plugin_name;

        private $incompatible_plugin;

        /**
         * The version of this plugin.
         *
         * @since  0.9.0
         * @access private
         * @var    string    $version    The current version of this plugin.
         */
        private $version;

        /**
         * Initialize the class and set its properties.
         *
         * @since 0.9.0
         * @param string $plugin_name The name of this plugin.
         * @param string $version     The version of this plugin.
         */
        public function __construct($plugin_name, $version)
        {
            $this->plugin_name = $plugin_name;
            $this->version = $version;
            add_action('wp_ajax_piio_get_consumption', array($this, 'piio_ajax_get_consumption'));
        }

        public function piio_ajax_get_consumption()
        {
            delete_option('piio_imageopt_consumption_status');
            delete_option('piio_imageopt_consumption_last_check');
            wp_die(); // this is required to return a proper result
        }

        public function check_piio_incompatibility()
        {
            $piio_enabled_option = get_option('piio_imageopt_enabled');
            $is_piio_enabled = isset($piio_enabled_option[0]) ? ($piio_enabled_option[0] === "1") : false;

            if ($is_piio_enabled) {
                $incompatible_plugins = array(
                    // 'wp-hummingbird/wp-hummingbird.php'   => 'Hummingbird Cache',
                    // 'hummingbird-performance/wp-hummingbird.php' => 'Hummingbird Cache',
                    // 'filename-based-asset-cache-busting' => 'Filename-based asset cache busting'
                );

                foreach ($incompatible_plugins as $plugin => $name) {
                    if (in_array($plugin, get_option('active_plugins', array()))) {
                        $this->incompatible_plugin = $name;
                        $this->_show_incompatibility_error();
                        break;
                    }
                }
            }
        }

        private function _show_incompatibility_error()
        {
            // Show error only to admins
            if (current_user_can('manage_options')) {
                ?>
                <div class="error notice piio-notice is-dismissible">
                    <p>
                        You're using <b><?php echo $this->incompatible_plugin ?></b>, we found that is not compatible with the <span class="text-piio">Piio Image Optimization Plugin</span>. Please consider changing to <a href="https://es.wordpress.org/plugins/wp-super-cache/" target="_blank">WP Super Cache</a>, it's fully compatible with Piio and it's installed in more than 2 million websites, or just disable <b><?php echo $this->incompatible_plugin ?></b>.
                    </p>
                </div>
                <?php
            }
        }

        public function check_consumption_status()
        {
            $piio_enabled_option = get_option('piio_imageopt_enabled');
            $is_piio_enabled = isset($piio_enabled_option[0]) ? ($piio_enabled_option[0] === "1") : false;

            if ($is_piio_enabled) {
                $this->_show_consumption();
            }
        }

        private function _show_consumption()
        {
            $consumption_status = get_option('piio_imageopt_consumption_status');
            if ($consumption_status && $consumption_status !== 'success') {
                switch ($consumption_status) {
                    case 'danger':
                        $consumption_status_text = "Monthly consumption exausted for the domain <b>" . site_url() . "</b>, your images are not being optimized. Please update your plan at <a href='https://app.piio.co' target='_blank'>https://app.piio.co</a>";
                        break;
                    case 'warning':
                        $consumption_status_text = "Monthly consumption almost exausted for the domain <b>" . site_url() . "</b>, Please consider updating your plan at <a href='https://app.piio.co' target='_blank'>https://app.piio.co</a>";
                        break;
                }
                $check_again_link = "<a class='piio-link' href='#' id='piio_check_consumption_link'>Click here to check again</a>";
                $consumption_css_class = $consumption_status === 'danger' ? 'error' : $show_consumption; ?>
                <div class="notice piio-notice consumption notice-<?php echo $consumption_css_class ?> is-dismissible">
                    <p>
                        <?= $consumption_status_text ?>
                        <br>
                        <?= $check_again_link ?>
                    </p>
                </div>
                <?php
            }
        }


        public function display_admin_page()
        {
            add_menu_page('Piio Options', 'Piio', 'manage_options', 'piio-options-menu', array($this, 'show_page'), 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMjUiIGhlaWdodD0iMTQ0IiB2aWV3Qm94PSIwIDAgMTI1IDE0NCI+CiAgPHBhdGggZmlsbD0iI0ZGRiIgZmlsbC1ydWxlPSJldmVub2RkIiBkPSJNMTI1LDEuMDQyNzI2NjUgQzEwOS4wNjUwNzYsOC45ODUwMTc4NiAxMTYuNjE2NDMsMzguMjY1OTI0NSAxMTYuNTAwMDk4LDQ3LjY2MTA4NTEgQzExNi40NzY2NDcsNDkuNjkyNzYyNCAxMTYuMjA0MTc5LDUxLjYxMDgzMzMgMTE1LjY4NTc3OSw1My40MTI1MjY4IEMxMTUuMDAwMTM0LDU2LjA3ODQyOTggMTEyLjk1Mzk5OCw1OC45OTg2Mzg4IDEwOS4wNDQ3MSw2Mi42NDcyODM4IEwxNi4wNjA0ODgxLDE0MiBMNjAuNjE0NzcyMSw3MS42NTkxMzgxIEw3OC43MTkyNjAzLDYxLjc4ODMwODIgTDEyLDc2LjM3NDg4MzEgQzEyLDc2LjM3NDg4MzEgNTQuNTczMjU3LDQ2LjUzODI1OTMgNzIuNTkxMzQ1MywzNS4yODIyOTI5IEM3Ny43MzgzMTI1LDMyLjA2NzEzODEgODAuMzU2ODQ3NSwzMi42OTEyMDM5IDg0Ljk3ODYyNjcsMzMuMjAyODk0NyBMOTMuNDcxNzM5OCwxOS41MjMwMTYyIEw3OS40MTY5Mzk3LDIwLjY4MzQwMjkgTDEwOC44MDMwOTksMi40NjAxOTAyNiBDMTA5LjgwOTA0MSwxLjc3ODg1OTQ2IDExMC44ODY1NzEsMS4yMDQ5Nzc1OCAxMTIuMDM1NjksMC44MTE4MTkyMzggQzExNi4yNjgzNjEsLTAuNjM2NzM5OTAyIDExOS44MzI5NzYsMC4xMjg5NDkwNjMgMTI0LjY1NjU2LDAuOTgwNTM1NTg0IEwxMjUsMS4wNDI3MjY2NSIvPgo8L3N2Zz4K', 200);
        }

        public function show_page()
        {
            include plugin_dir_path(__FILE__) . 'partials/piio-image-optimization-admin-display.php';
        }

        public function setup_sections()
        {
            add_settings_section('piio_imageopt_options', false, false, 'piio_imageopt_menu');
        }

        public function setup_fields()
        {
            $fields = array(
                array(
                    'uid' => 'piio_imageopt_enabled',
                    'label' => 'Images Optimize Enabled',
                    'type' => 'select',
                    'options' => array(
                        '0' => 'No',
                        '1' => 'Yes'
                    ),
                    'default' =>  array('0'),
                    'supplimental' => 'Enable this option once you configured your plugin properly. Detailed setup at <a href="https://app.piio.co" target="_blank">https://app.piio.co</a>'
                ),
                array(
                    'uid' => 'piio_imageopt_api_key',
                    'label' => 'Domain Key',
                    'type' => 'text',
                    'placeholder' => 'Piio Domain Key',
                    'default' =>  '',
                    'supplimental' => 'You need to get your Domain Key for your domain here: <a href="https://app.piio.co" target="_blank">https://app.piio.co</a>',
                    'validate' => 'Please provide the Domain Key'
                ),
                array(
                    'uid' => 'piio_imageopt_optimize_bck',
                    'label' => 'Optimize Background Images',
                    'type' => 'select',
                    'options' => array(
                        '0' => 'No',
                        '1' => 'Yes'
                    ),
                    'default' =>  array('1'),
                    'supplimental' => 'Depending on your theme, some images may be included as inline styles. This will enable image optimization for those cases.'
                ),
                array(
                    'uid' => 'piio_imageopt_script_position',
                    'label' => 'Script Position',
                    'type' => 'select',
                    'options' => array(
                        '0' => 'Body End',
                        '1' => 'Body Start'
                    ),
                    'default' =>  array('0'),
                ),
                array(
                    'uid' => 'piio_imageopt_optimize_editors',
                    'label' => 'Optimize For Editors',
                    'type' => 'select',
                    'options' => array(
                        '0' => 'No',
                        '1' => 'Yes'
                    ),
                    'supplimental' => 'Some plugins allow editors to modify content in frontend, setting this to <i>NO</i>, will prevent Piio from optimizing images to a user under editor permissions.',
                    'default' =>  array('1'),
                ),
                // array(
                //     'uid' => 'piio_imageopt_sw_enabled',
                //     'label' => 'Enable service worker',
                //     'type' => 'select',
                //     'options' => array(
                //         '0' => 'No',
                //         '1' => 'Yes'
                //     ),
                //     'default' =>  array('0'),
                //     'supplimental' => 'You can enable the use of a Service Worker to optimize images that are not reachable by Piio normal implemantation.'
                // )
            );
            foreach ($fields as $field) {
                add_settings_field($field['uid'], $field['label'], array($this, 'piio_field_callback'), 'piio_imageopt_menu', 'piio_imageopt_options', $field);
                switch ($field['type']) {
                case 'text':
                    register_setting('piio_imageopt_menu', $field['uid'], array($this, 'sanitize_text_callback'));
                    break;
                case 'select':
                    register_setting('piio_imageopt_menu', $field['uid'], array($this, 'sanitize_select_callback'));
                    break;
                    }
                if ($field['type'] == 'text') {
                }
            }
        }

        public function piio_field_callback($arguments)
        {
            $value = get_option($arguments['uid']);
            if (! $value) {
                $value = $arguments['default'];
            }
            switch ($arguments['type']) {
            case 'text':
            case 'password':
            case 'number':
                    printf('<input name="%1$s" id="%1$s" type="%2$s" placeholder="%3$s" value="%4$s" maxlength="100" pattern="[a-zA-Z0-9]+"/>', esc_attr($arguments['uid']), $arguments['type'], esc_attr($arguments['placeholder']), esc_attr($value));
                break;
            case 'textarea':
                        printf('<textarea name="%1$s" id="%1$s" placeholder="%2$s" rows="5" cols="50">%3$s</textarea>', esc_attr($arguments['uid']), $arguments['placeholder'], esc_attr($value));
                break;
            case 'select':
            case 'multiselect':
                if (! empty($arguments['options']) && is_array($arguments['options'])) {
                    $attributes = '';
                    $options_markup = '';
                    foreach ($arguments['options'] as $key => $label) {
                        $options_markup .= sprintf('<option value="%s" %s>%s</option>', esc_attr($key), selected($value[ array_search($key, $value, true) ], $key, false), esc_attr($label));
                    }
                    if ($arguments['type'] === 'multiselect') {
                        $attributes = ' multiple="multiple" ';
                    }
                    printf('<select name="%1$s[]" id="%1$s" %2$s>%3$s</select>', esc_attr($arguments['uid']), $attributes, $options_markup);
                }
                break;
            case 'radio':
            case 'checkbox':
                if (! empty($arguments['options']) && is_array($arguments['options'])) {
                    $options_markup = '';
                    $iterator = 0;
                    foreach ($arguments['options'] as $key => $label) {
                        $iterator++;
                        $options_markup .= sprintf('<label for="%1$s_%6$s"><input id="%1$s_%6$s" name="%1$s[]" type="%2$s" value="%3$s" %4$s /> %5$s</label><br/>', esc_attr($arguments['uid']), $arguments['type'], esc_attr($key), checked($value[ array_search($key, $value, true) ], $key, false), esc_attr($label), $iterator);
                    }
                    printf('<fieldset>%s</fieldset>', $options_markup);
                }
                break;
            }
            if (isset($arguments['validate']) && $validate = $arguments['validate']) {
                printf('<span class="piio-error" id="%s_error"> %s</span>', esc_attr($arguments['uid']), $validate);
            }
            if (isset($arguments['supplimental']) && $supplimental = $arguments['supplimental']) {
                printf('<p class="description">%s</p>', $supplimental);
            }
        }

        public function sanitize_text_callback($input)
        {
            $sanitized = sanitize_text_field($input);
            if (strlen($sanitized) > 8) {
                $sanitized = substr($sanitized, 0, 8);
            }
            return $sanitized;
        }

        public function sanitize_select_callback($input)
        {
            if (!isset($input) || !is_array($input) || empty($input) || ($input[0] != '0' && $input[0] != '1')) {
                $input = array('0');
            }
            return $input;
        }

        /**
         * Register the stylesheets for the admin area.
         *
         * @since 0.9.0
         */
        public function enqueue_styles()
        {
            wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/piio-image-optimization-admin.css', array(), $this->version, 'all');
        }

        /**
         * Register the scripts for the admin area.
         *
         * @since 0.9.0
         */
        public function enqueue_scripts()
        {
            wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/piio-image-optimization-admin.js', array( 'jquery' ), $this->version, false);
        }
    }
}
