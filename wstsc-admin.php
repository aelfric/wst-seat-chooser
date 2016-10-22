<?php

if ( ! class_exists( 'WSTSC_Admin' ) ) {
    class WSTSC_Admin {
        public static function add_menu() {
            add_options_page( 'WST Seat Chooser Settings', 
                'WST Seat Chooser', 
                'manage_options', 
                'wst_seat_chooser', 
                'WSTSC_Admin::plugin_settings_page'  );
            add_menu_page('WST Performance Seating Chart', 
                'WST Seat Chooser', 
                'manage_options', 
                'wst_seating_chart',
                'WSTSC_Admin::display_performance_seating_chart');
        }

		public static function init_settings() {
			register_setting( 'wst_seat_chooser-group', 'seating_chart' );
			register_setting( 'wst_seat_chooser-group', 'reserved_seats' );
			register_setting( 'wst_seat_chooser-group', 'category_name' );
			register_setting( 'wst_seat_chooser-group', 'order_timeout' );
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

            wp_enqueue_script( 'wst_seat_chooser', plugins_url( '/js/bundle.js', __FILE__ ), array( 'jquery' ) );
            wp_enqueue_style( 'wst_seat_chooser_style', plugins_url( '/css/style.css', __FILE__ ) );
            $args = array(
                'post_type'     => 'product_variation',
                'post_status'   => 'publish',
                'numberposts'   => -1,
                'orderby'       => 'menu_order',
                'order'         => 'asc',
                'post_parent'   => 8
            );
            $variations = get_posts( $args );
            echo '<select id="wst-show-select">';
            echo '<option>Please select one...</option>';
            foreach ( $variations as $variation ) {
                $variation_id = absint( $variation->ID );
                $variable_id = $this['variation_id'];
                $variation_post_status = esc_attr( $variation->post_status );
                $variation_data = get_post_meta( $variation_id );
                $variation_data['variation_post_id'] = $variation_id;
                $title = get_the_title( $variation_data['variation_post_id'] );
                echo '<option value="'.$variation_data['variation_post_id'].'">'.$title.'</option>';
            }
            echo '</select>';
            echo "<button id='btn-print'>Print Seating Chart</button>";
            echo "<div id='box-office-chart'></div>";
        }

        public static function get_reservation_name_data($variation_id){
            add_filter(
                'posts_where', function ( $where ) use ($variation_id) {
                    global $wpdb;
                    $t_posts = $wpdb->posts;
                    $t_order_items = $wpdb->prefix . 'woocommerce_order_items';
                    $t_order_itemmeta = $wpdb->prefix . 'woocommerce_order_itemmeta';
                    $where .= $wpdb->prepare(" AND $t_posts.ID IN (SELECT
                        $t_order_items.order_id FROM $t_order_items LEFT
                        JOIN $t_order_itemmeta on
                        $t_order_itemmeta.order_item_id=$t_order_items.order_item_id
                        WHERE $t_order_items.order_item_type='line_item' AND
                        $t_order_itemmeta.meta_key='_variation_id' AND
                        $t_order_itemmeta.meta_value='%d')",$variation_id);
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
