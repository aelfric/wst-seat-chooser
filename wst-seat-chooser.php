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
            add_action( 'init', 'claimed_seats_endpoint' );
            add_action( 'template_redirect', 'claimed_seats_endpoint_data' );
            add_action('woocommerce_after_checkout_validation', 'wst_seat_chooser_after_checkout_validation');
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
            //add_menu_page('WST Performance Seating Chart', 'WST Seat Chooser', 'manage_options', 'wst_seat_chooser', array(&$this, 'display_performance_seating_chart'));
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
            global $product;
            $flag = wst_seat_chooser_enabled($product->get_id());
            if($flag){
                echo "<input type='hidden' id='seatsChosen' name='seatsChosen' />";
                echo "<div id='seat-data' data-seating-chart='" . esc_js(get_option('seating_chart')). "' data-reserved-seats='" . esc_js(get_option('reserved_seats')) ."'></div>";
                wp_enqueue_style('wst_seat_chooser_style', plugins_url('/css/style.css', __FILE__));
                wp_enqueue_script('wst_seat_chooser',plugins_url('/js/bundle.js', __FILE__),array('jquery')); 
            }
        }

        public function record_seat_choices($cart_item_meta, $product_id){
            $flag = wst_seat_chooser_enabled($product_id);
            if($flag){
                $cart_item_meta['seats'] = $_POST['seatsChosen'];
                return $cart_item_meta;
            }
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

        public function display_performance_seating_chart()
        {
            if(!current_user_can('manage_options'))
            {
                wp_die(__('You do not have sufficient permissions to access this page.'));
            }

            // Render the settings template
            //include(sprintf("%s/settings/seating-chart.php", dirname(__FILE__)));
            //attach your function to the posts_where filter
            add_filter( 'posts_where' , function($where){
      global $wpdb;
      $t_posts = $wpdb->posts;
      $t_order_items = $wpdb->prefix . "woocommerce_order_items";  
      $t_order_itemmeta = $wpdb->prefix . "woocommerce_order_itemmeta";
            $product = 8; 
            return $where . " AND $product IN (SELECT $t_order_itemmeta.meta_value FROM $t_order_items LEFT JOIN $t_order_itemmeta on $t_order_itemmeta.order_item_id=$t_order_items.order_item_id WHERE $t_order_items.order_item_type='line_item' AND $t_order_itemmeta.meta_key='_product_id' AND $t_posts.ID=$t_order_items.order_id)";
            } );

            //get posts AND make sure filters are NOT suppressed
            $posts = get_posts( array( 
                'post_type' => 'shop_order', 
                'post_status' => array( 'wc-processing', 'wc-completed' ),
                'suppress_filters' => FALSE ) );

            print_r($posts);
        }
    }
    
    function claimed_seats_endpoint() {
        add_rewrite_tag( '%seating_chart%', '([^&]+)' );
        add_rewrite_rule( 'seating_chart/([^&]+)/?', 'index.php?seating_chart=$matches[1]', 'top' );
    }

    function get_unavailable_seats($show_id){
        global $wpdb;
        $unavailable_seats = explode(",",get_option('reserved_seats')); 
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "select meta_value \n"
                ."from wp_woocommerce_order_itemmeta meta1 \n"
                ."where meta1.order_item_id in (\n"
                ."SELECT distinct order_item_id \n"
                ."from wp_woocommerce_order_itemmeta meta2\n"
                ."where meta_key = '_variation_id' and meta_value='%s') and meta_key = 'seats'",
            $_GET["variation_id"]));
        foreach($results as $key => $row){
            $unavailable_seats = array_merge($unavailable_seats, explode(",", $row->meta_value));
        }

        return $unavailable_seats;
    }

    function claimed_seats_endpoint_data(){
        global $wp_query;

        $seat_tag = $wp_query->get("seating_chart");
        if (!$seat_tag){
            return;
        }

        $seating_data = get_unavailable_seats("");
        wp_reset_query();

        wp_send_json( $seating_data );
    }

    function wst_seat_chooser_after_checkout_validation( $posted ) {
        $unavailable_seats = get_unavailable_seats("");
        global $woocommerce;
        foreach($cart = $woocommerce->cart->get_cart() as $cart_item_key => $values){
            if(!empty(array_intersect(explode(",",$values["seats"]), $unavailable_seats))){
                wc_add_notice( __( "One or more of the seats you selected is no longer available.", 'woocommerce' ), 'error' );
                return;
            }
        }
    }

    function wst_seat_chooser_enabled($product_id){
        $terms = wp_get_post_terms( $product_id, 'product_cat' );
        if(count($terms) > 0){
            foreach ( $terms as $term ){
                $categories[] = $term->slug;
            }
            if ( in_array( 'tickets', $categories ) ) {
                return true;
            } 
        }
        return false; 
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
