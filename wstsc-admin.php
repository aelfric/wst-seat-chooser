<?php

if ( ! class_exists( 'WSTSC_Admin' ) ) {
    class WSTSC_Admin {
        public static function add_menu() {
            add_options_page( 'WST Seat Chooser Settings', 'WST Seat Chooser', 'manage_options', 'wst_seat_chooser', 'WSTSC_Admin::plugin_settings_page'  );
            add_menu_page('WST Performance Seating Chart', 'WST Seat Chooser', 'manage_options', 'wst_seat_chooser','WSTSC_Admin::display_performance_seating_chart');
        }

		public static function init_settings() {
			register_setting( 'wst_seat_chooser-group', 'seating_chart' );
			register_setting( 'wst_seat_chooser-group', 'reserved_seats' );
			register_setting( 'wst_seat_chooser-group', 'category_name' );
		}

		public static function plugin_settings_page() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
			}

			// Render the settings template
			include sprintf( '%s/settings/settings.php', dirname( __FILE__ ) );
        }

        public static function display_performance_seating_chart() {
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
            }

            // Render the settings template
            //include(sprintf("%s/settings/seating-chart.php", dirname(__FILE__)));
            //attach your function to the posts_where filter
            add_filter(
                'posts_where', function ( $where ) {
                    global $wpdb;
                    $t_posts = $wpdb->posts;
                    $t_order_items = $wpdb->prefix . 'woocommerce_order_items';
                    $t_order_itemmeta = $wpdb->prefix . 'woocommerce_order_itemmeta';
                    $product_variation = 11;
                    return $where .= " AND $product_variation IN (SELECT $t_order_itemmeta.meta_value FROM $t_order_items LEFT JOIN $t_order_itemmeta on $t_order_itemmeta.order_item_id=$t_order_items.order_item_id WHERE $t_order_items.order_item_type='line_item' AND $t_order_itemmeta.meta_key='_variation_id' AND $t_posts.ID=$t_order_items.order_id)";
                    // return $where . " AND $product IN (SELECT $t_order_itemmeta.meta_value"
                    //     ." FROM $t_order_items "
                    //     ."LEFT JOIN $t_order_itemmeta "
                    //     ."on $t_order_itemmeta.order_item_id=$t_order_items.order_item_id "
                    //     ."WHERE $t_order_items.order_item_type='line_item' "
                    //     ."AND $t_order_itemmeta.meta_key='_variation_id' "
                    //     ."AND $t_posts.ID=$t_order_items.order_id)";
                }
            );

            //get posts AND make sure filters are NOT suppressed
            $posts = get_posts(
                array(
                    'post_type' => 'shop_order',
                    'post_status' => array( 'wc-processing', 'wc-completed' ),
                    'suppress_filters' => false,
                )
            );
            global $woocommerce;
			global $wst_seat_chooser;
            $factory = new WC_Order_factory();
            $box_offic_chart_data = array();

            foreach($posts as $post){
                echo "<div>";
                $order = $factory->get_order($post->ID);
                echo "<div>";
//                print_r($order);
                echo "</div>";
                $meta = get_post_meta($post->ID);
                echo "<div>";
//                print_r($meta);
                echo "</div>";
                $name = $order->get_formatted_billing_full_name();
                echo "<p>".$name."</p>";
                $items = $order->get_items();
                foreach($items as $item){
                    $item_meta = $item["item_meta"];
                    if($wst_seat_chooser->is_enabled($item["product_id"]) && isset($item_meta["seats"])){
                    echo "<div>";
                    echo $item_meta["seats"][0];
                    foreach(explode(",", $item_meta["seats"][0]) as $seat){
                        $box_offic_chart_data[$seat] = $name;
                    }
                    echo "</div>";
                    }
                }
                echo "</div>";
            }
                print_r($box_offic_chart_data);
        }

        public static function get_reservation_name_data($variation_id){
            add_filter(
                'posts_where', function ( $where ) use ($variation_id) {
                    global $wpdb;
                    $t_posts = $wpdb->posts;
                    $t_order_items = $wpdb->prefix . 'woocommerce_order_items';
                    $t_order_itemmeta = $wpdb->prefix . 'woocommerce_order_itemmeta';
                    $where .= " AND $t_posts.ID IN (SELECT
                        $t_order_items.order_id FROM $t_order_items LEFT
                        JOIN $t_order_itemmeta on
                        $t_order_itemmeta.order_item_id=$t_order_items.order_item_id
                        WHERE $t_order_items.order_item_type='line_item' AND
                        $t_order_itemmeta.meta_key='_variation_id' AND
                        $t_order_itemmeta.meta_value='$variation_id')";
                    return $where;
                }
            );

            //get posts AND make sure filters are NOT suppressed
            $posts = get_posts(
                array(
                    'post_type' => 'shop_order',
                    'post_status' => array( 'wc-processing', 'wc-completed' ),
                    'suppress_filters' => false,
                    'numberposts' => -1
                )
            );
            global $woocommerce;
			global $wst_seat_chooser;
            $factory = new WC_Order_factory();
            $box_offic_chart_data = array();

            foreach($posts as $post){
                $order = $factory->get_order($post->ID);
                $name = $order->get_formatted_billing_full_name();
                foreach($order->get_items() as $item){
                    $item_meta = $item["item_meta"];
                    if(isset($item_meta["seats"])){
                        foreach(explode(",", $item_meta["seats"][0]) as $seat){
                            $box_offic_chart_data[$seat] = $name;
                        }
                    }
                }
            }
            return $box_offic_chart_data;
        }
    }
}
