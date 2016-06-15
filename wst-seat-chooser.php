<?php
/*
Plugin Name: WST Seat Chooser
Plugin URI:  http://www.github.com/aelfric/wst-seat-chooser/
Description: A WooCommerce Plugin for choosing theater seats.
Version:     1.0
Author:      Frank Riccobono
Author URI:  http://www.frankriccobono.com
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if(!class_exists('WST_Seat_Chooser')){
    class WST_Seat_Chooser {
        /** 
         * Construct the plugin object
         */
        public function __construct(){
            add_action('admin_init', array(&$this, 'admin_init'));
            add_action('admin_menu', array(&$this, 'add_menu'));
        }

        public static function activate(){
        }

        public static function deactivate(){
        }

        public function admin_init(){
            $this->init_settings();
        }

        public function init_settings(){
            register_setting('wst_seat_chooser-group', 'seating_chart');
            register_setting('wst_seat_chooser-group','reserved_seats');
        }

        public function add_menu(){
            add_options_page('WST Seat Chooser Settings', 'WST Seat Chooser', 'manage_options', 'wst_seat_chooser', array(&$this, 'plugin_settings_page'));
        }
        public function plugin_settings_page()
        {
            if(!current_user_can('manage_options'))
            {
                wp_die(__('You do not have sufficient permissions to access this page.'));
            }

            // Render the settings template
            include(sprintf("%s/settings/settings.php", dirname(__FILE__)));
        }
    }
}

if(class_exists('WST_Seat_Chooser')){
    register_activation_hook(__FILE__, array('WST_Seat_Chooser','activate'));
    register_deactivation_hook(__FILE__, array('WST_Seat_Chooser', 'deactivate'));

    $wst_seat_chooser = new WST_Seat_Chooser();

    if(isset($wst_seat_chooser)){
        function plugin_settings_link($links){
            $settings_link = '<a href="options-general.php?page=wst_seat_chooser">Settings</a>';
            array_unshift($links, $settings_link);
            return $links;
        }

        $plugin = plugin_basename(__FILE__);
        add_filter("plugin_action_links_$plugin", 'plugin_settings_link');
    }
}
