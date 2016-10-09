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

if ( ! class_exists( 'WST_Seat_Chooser' ) ) {
	require_once( 'wstsc-seat-choice.php' );
    require_once( 'wstsc-admin.php' );
    require_once( 'wstsc-dao.php');

	class WST_Seat_Chooser {

		/**
		 * Construct the plugin object
		 */
		public function __construct() {
			add_action( 'admin_init', 'WSTSC_Admin::init_settings' );
			add_action( 'admin_menu', 'WSTSC_Admin::add_menu' );
			add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'inject_modal' ) );
			add_action( 'init', array( $this, 'claimed_seats_endpoint' ) );
			add_action( 'template_redirect', array( $this, 'claimed_seats_endpoint_data' ) );
			//            add_action('woocommerce_after_checkout_validation', 'wst_seat_chooser_after_checkout_validation');
			add_action( 'woocommerce_add_to_cart', array( $this, 'start_seat_timer' ), 10, 6 );
			add_action( 'woocommerce_before_cart_contents', array( $this, 'wst_seat_chooser_after_checkout_validation' ) );

			add_filter( 'woocommerce_add_cart_item_data',
			'WST_Seat_Choice::save_to_cart_meta', 0, 2 );
			add_filter( 'woocommerce_add_order_item_meta',
			'WST_Seat_Choice::save_to_order' , 0, 3 );
			add_filter( 'woocommerce_get_cart_item_from_session',
			'WST_Seat_Choice::read_to_cart_from_session', 0, 2 );
			add_filter( 'woocommerce_get_item_data','WST_Seat_Choice::do_display_filter' , 0, 2 );
            add_filter(
                'woocommerce_cart_item_class', 
                function ( $default_class, $cart_item, $cart_item_key ) {
                    if ($this->is_enabled($cart_item["product_id"])){
                        return $default_class . ' wst_seat_choice_item';
                    } else {
                        return $default_class;
                    }
                }, 0, 3);

			add_action(
                'wp_head', function () {
				    wp_enqueue_script( 'wst_seat_chooser', plugins_url( '/js/bundle.js', __FILE__ ), array( 'jquery', 'woocommerce' ) );
                    wp_enqueue_style( 'wst_seat_chooser_style', plugins_url( '/css/style.css', __FILE__ ) );

					global $woocommerce;

                    $timer_expiration = $woocommerce->session->get('wst_seat_timer_expires');

                    date_default_timezone_set( 'America/New_York' );
                    if (null !== $timer_expiration) {
                        if ( strtotime($timer_expiration) > time() ){
                        wp_enqueue_script( 'wst-sc-timer', plugins_url( '/js/wst-sc-timer.js', __FILE__ ), array( 'jquery' ) );
                        echo "<script>var timer_expiration = Date.parse('" . $timer_expiration . "');</script>";
                        } else {
                            $woocommerce->cart->set_quantity(
                                $woocommerce->session->get('wst_seat_timer_cart_item'),
                                0
                            );
                        }
                    }
				}
			);
		}

		public static function activate() {
            WSTSC_DAO::create_seat_timer_table();
            file_put_contents(__DIR__.'/my_loggg.html', ob_get_contents());
		}

		public static function deactivate() {
		}

		public function inject_modal() {
			global $product;
			if($this->is_enabled( $product->get_id() ) ) {
				echo "<input type='hidden' id='seatsChosen' name='seatsChosen' />";
			}
		}


		public function claimed_seats_endpoint() {
			add_rewrite_tag( '%seating_chart%', '([^&]+)' );
			add_rewrite_rule( 'seating_chart/([^&]+)/?', 'index.php?seating_chart=$matches[1]', 'top' );
		}


		public function claimed_seats_endpoint_data() {
			global $wp_query;

            $seating_chart = array_map(
                function($x) { return explode(",", $x); },
                preg_split("/\\r\\n|\\r|\\n/",get_option( 'seating_chart' ))
            );

			if ( $wp_query->get( 'seating_chart' ) ) {
                $variation_id = -1;
                if(isset($_GET['variation_id'])){
                    $variation_id = $_GET['variation_id'];
                }
                $seating_data = WSTSC_DAO::get_unavailable_seats( $variation_id );
                wp_reset_query();
                wp_send_json( 
                    array(
                        'seating_chart' => $seating_chart,
                        'reserved_seats' => $seating_data 
                    )
                );
            }
		}

		public function start_seat_timer( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
            if( ! $this->is_enabled($product_id) ){
                return;
            }

			global $woocommerce;
            global $wpdb;
            $minutes = 3;

			// Display a timer to the user.
			date_default_timezone_set( 'America/New_York' );
            $woocommerce->session->set( 
                'wst_seat_timer_expires',
                date( 'm/d/Y h:i:s a', strtotime("+ $minutes minutes", time()) )
            );
            $woocommerce->session->set( 'wst_seat_timer_cart_item', $cart_item_key);
			// Record a timer in the system (add one more minute to be conservative)
            WSTSC_DAO::add_temp_reservation($cart_item_data["seats"], $variation_id, $minutes + 1);
		}

		public function is_enabled( $product_id ) {
			$terms = wp_get_post_terms( $product_id, 'product_cat' );
			if ( count( $terms ) > 0 ) {
				foreach ( $terms as $term ) {
					$categories[] = $term->slug;
				}
				if ( in_array( get_option( 'category_name' ), $categories ) ) {
					return true;
				}
			}
			return false;
		}
		public function wst_seat_chooser_after_checkout_validation() {
			global $woocommerce;
			foreach ( $cart = $woocommerce->cart->get_cart() as $key => $values ) {
				$unavailable_seats = WSTSC_DAO::get_unavailable_seats( $values['variation_id'] );
				if ( $this->is_enabled( $values['variation_id'] ) ) {
					$conflicts = array_intersect( explode( ',', $values['seats'] ), $unavailable_seats );
					if ( ! empty( $conflicts ) ) {
						wp_enqueue_style( 'wst_seat_chooser_style', plugins_url( '/css/style.css', __FILE__ ) );
						add_filter(
							'woocommerce_cart_item_class', function ( $default_class, $cart_item, $cart_item_key ) use ( &$key ) {
								if ( $cart_item_key == $key ) {
									return $default_class . ' duplicate_seat_order_error';
								} else {
									return $default_class;
								}
							}, 0, 3
						);
						wc_add_notice(
							__(
								'The seat(s) ' . implode( $conflicts, ', ' )
								. ' you selected for the performance of ' . $values['data']->get_title() . ' on '
								. $values['variation']['attribute_date'] . ' is no longer available.', 'woocommerce'
							), 'error'
						);
						return;
					}
				}
			}
		}


	}
}
if ( class_exists( 'WST_Seat_Chooser' ) ) {
	register_activation_hook( __FILE__, array( 'WST_Seat_Chooser', 'activate' ) );
	register_deactivation_hook( __FILE__, array( 'WST_Seat_Chooser', 'deactivate' ) );

	$wst_seat_chooser = new WST_Seat_Chooser();

	if ( isset( $wst_seat_chooser ) ) {
		function plugin_settings_link( $links ) {
			$settings_link = '<a href="options-general.php?page=wst_seat_chooser">Settings</a>';
			array_unshift( $links, $settings_link );
			return $links;
		}

		$plugin = plugin_basename( __FILE__ );
		add_filter( "plugin_action_links_$plugin", 'plugin_settings_link' );
	}
}
