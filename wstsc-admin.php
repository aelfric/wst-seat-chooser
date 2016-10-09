<?php

if ( ! class_exists( 'WSTSC_Admin' ) ) {
    class WSTSC_Admin {
        public static function add_menu() {
            add_options_page( 'WST Seat Chooser Settings', 'WST Seat Chooser', 'manage_options', 'wst_seat_chooser', array( &$this, 'plugin_settings_page' ) );
            add_menu_page('WST Performance Seating Chart', 'WST Seat Chooser', 'manage_options', 'wst_seat_chooser', array(&$this, 'display_performance_seating_chart'));
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
                    $product = 11;
                    return $where . " AND $product IN (SELECT $t_order_itemmeta.meta_value"
                        ." FROM $t_order_items "
                        ."LEFT JOIN $t_order_itemmeta "
                        ."on $t_order_itemmeta.order_item_id=$t_order_items.order_item_id "
                        ."WHERE $t_order_items.order_item_type='line_item' "
                        ."AND $t_order_itemmeta.meta_key='_variation_id' "
                        ."AND $t_posts.ID=$t_order_items.order_id)";
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

            print_r( $posts );
        }
    }
}
