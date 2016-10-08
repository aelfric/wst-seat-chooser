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
//            add_action('woocommerce_after_checkout_validation', 'wst_seat_chooser_after_checkout_validation');
            add_action('woocommerce_add_to_cart', 'start_seat_timer', 10, 6);
            add_action('woocommerce_before_cart_contents', 'wst_seat_chooser_after_checkout_validation');
            add_filter('woocommerce_add_cart_item_data', array(&$this, 'record_seat_choices'), 0, 2);
            add_filter('woocommerce_get_cart_item_from_session', array(&$this, 'read_seat_choices'),0,2);
            add_filter('woocommerce_get_item_data', array(&$this, 'display_seat_choices'),0,2);
            add_filter('woocommerce_add_order_item_meta', array(&$this, 'order_seat_choices'),0,3);
            add_action('wp_head', function(){
                global $woocommerce;
                $timer = '';
                if(!is_admin()){
                    $timer = $woocommerce->session->get('wst_seat_timer_expires');
                }
                echo "<script>var timer = (Date.parse('".$timer."') - Date.now()) / (1000);\n";
                echo <<<EOT
jQuery(document).ready(function(){
var div = document.createElement("div");
div.style.position = 'fixed';
div.style.top = '0';
document.body.appendChild(div);
setInterval(function() {
    var minutes = parseInt(timer / 60, 10);
    var seconds = parseInt(timer % 60, 10);
    seconds = seconds < 10 ? '0' + seconds : seconds;
    --timer;
    div.textContent = minutes + ':' + seconds;
}, 1000)
});
</script>
EOT;
            });
        }

        public static function activate(){
          create_seat_timer_table();  
        }

        public static function deactivate(){
        }

        public function admin_init(){
            $this->init_settings();
        }

        public function init_settings(){
            register_setting('wst_seat_chooser-group', 'seating_chart');
            register_setting('wst_seat_chooser-group','reserved_seats');
            register_setting('wst_seat_chooser-group','category_name');
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
            $show_id));
        foreach($results as $key => $row){
            $unavailable_seats = array_merge($unavailable_seats, explode(",", $row->meta_value));
        }
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "select seats \n"
                ."from wp_tmp_seat_reservations \n"
                ."where variation_id = '%s'\n"
                ."AND expiry > NOW()",$show_id));
        foreach($results as $key => $row){
            $unavailable_seats = array_merge($unavailable_seats, explode(",", $row->seats));
        }

        return $unavailable_seats;
    }

    function claimed_seats_endpoint_data(){
        global $wp_query;

        $seat_tag = $wp_query->get("seating_chart");
        if (!$seat_tag){
            return;
        }

        $seating_data = get_unavailable_seats($_GET['variation_id']);
        wp_reset_query();

        wp_send_json( $seating_data );
    }

    function start_seat_timer( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data){
        global $woocommerce;
        global $wpdb;

        date_default_timezone_set('America/New_York');
        // Display a five minute timer to the user.
        $date = date('m/d/Y h:i:s a', time()+(60*5));
        // Record a six minute timer in the system to be conservative
        $dbDate = gmdate('Y-m-d H:i:s', time()+(60*6));
        $woocommerce->session->set( 'wst_seat_timer_expires',  $date);
        $wpdb->insert(
            "wp_tmp_seat_reservations",
            array(
                'expiry' => $dbDate,
                'seats' => $cart_item_data["seats"],
                'variation_id' => $variation_id
            )
        );

    }

    function wst_seat_chooser_after_checkout_validation() {
        global $woocommerce;
        foreach($cart = $woocommerce->cart->get_cart() as $key => $values){
            $unavailable_seats = get_unavailable_seats($values["variation_id"]);
            if(wst_seat_chooser_enabled($values["variation_id"])){
                $conflicts = array_intersect(explode(",",$values["seats"]), $unavailable_seats);
                if(!empty($conflicts)){
                    wp_enqueue_style('wst_seat_chooser_style', plugins_url('/css/style.css', __FILE__));
                    add_filter('woocommerce_cart_item_class', function($default_class, $cart_item, $cart_item_key) use (&$key){
                        if($cart_item_key == $key){
                            return $default_class." duplicate_seat_order_error";
                        } else {
                            return $default_class;
                        }
                    }, 0, 3);
                    wc_add_notice( __( "The seat(s) ".implode($conflicts,", ")
                        ." you selected for the performance of ".$values["data"]->get_title()." on "
                        .$values["variation"]["attribute_date"]." is no longer available.", 'woocommerce' ), 'error' );
                    return;
                }
            }
        }
    }

    function wst_seat_chooser_enabled($product_id){
        $terms = wp_get_post_terms( $product_id, 'product_cat' );
        if(count($terms) > 0){
            foreach ( $terms as $term ){
                $categories[] = $term->slug;
            }
            if ( in_array( get_option('category_name'), $categories ) ) {
                return true;
            } 
        }
        return false; 
    }

    function create_seat_timer_table(){
        global $wpdb;
        $table_name = $wpdb->prefix . "wst_tmp_seat_reservations";
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            expiry datetime,
            seats varchar(max),
            variation_id mediumint(9),
            PRIMARY KEY (id)
            ) $charset_collate;";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
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
