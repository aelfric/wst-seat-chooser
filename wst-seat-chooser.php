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
            add_action('woocommerce_before_add_to_cart_button', array(&$this, 'enqueue_scripts'));
            add_filter('woocommerce_add_cart_item_data', array(&$this, 'record_seat_choices'), 0, 2);
            add_filter('woocommerce_get_cart_item_from_session', array(&$this, 'read_seat_choices'),0,2);
            add_filter('woocommerce_get_item_data', array(&$this, 'display_seat_choices'),0,2);
            add_filter('woocommerce_add_order_item_meta', array(&$this, 'order_seat_choices'),0,3);
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
        public function enqueue_scripts(){
            echo "<input type='hidden' id='seatsChosen' name='seatsChosen' />";
            echo "<div id='seat-data' data-seating-chart='" . esc_js(get_option('seating_chart')). "' data-reserved-seats='" . esc_js(get_option('reserved_seats')) ."'></div>";
            wp_enqueue_style('wst_seat_chooser_style', plugins_url('/css/style.css', __FILE__));
            wp_enqueue_script('wst_seat_chooser',plugins_url('/js/bundle.js', __FILE__),array('jquery')); 
        }

        public function record_seat_choices($cart_item_meta, $product_id){
            $cart_item_meta['seats'] = $_POST['seatsChosen'];
            return $cart_item_meta;
        }

        public function read_seat_choices($cart_item, $values){
            if(array_key_exists('seats',$values)){
                $cart_item['seats'] = $values['seats'];
            }
            return $cart_item;
        }
        public function display_seat_choices($cart_item, $values){
            if(array_key_exists('seats',$values)){
                $cart_item['seats'] = array(
                    'name' => 'Seats', 
                    "value" => $values['seats']);
            }
            return $cart_item;
        }

        public function order_seat_choices($itemId, $values, $key){
            if( isset($values['seats'] ) ){
                wc_add_order_item_meta($itemId, 'seats', $values['seats'] );
            }
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
