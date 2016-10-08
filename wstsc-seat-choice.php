<?php
require_once( 'wst-seat-chooser.php' );

if ( ! class_exists( 'WST_Seat_Choice' ) ) {
	class WST_Seat_Choice {
		public static function save_to_cart_meta( $cart_item_meta, $product_id ) {
			global $wst_seat_chooser;
			$flag = $wst_seat_chooser->is_enabled( $product_id );
			if ( $flag ) {
				$cart_item_meta['seats'] = $_POST['seatsChosen'];
				return $cart_item_meta;
			}
		}

		public static function read_to_cart_from_session( $cart_item, $values ) {
			if ( array_key_exists( 'seats', $values ) ) {
				$cart_item['seats'] = $values['seats'];
			}
			return $cart_item;
		}
		public static function save_to_order( $item_id, $values, $key ) {
			if ( isset( $values['seats'] ) ) {
				wc_add_order_item_meta( $item_id, 'seats', $values['seats'] );
			}
		}

		public static function do_display_filter( $cart_item, $values ) {
			if ( array_key_exists( 'seats', $values ) ) {
				$cart_item['seats'] = array(
					'name' => 'Seats',
					'value' => $values['seats'],
				);
			}
			return $cart_item;
		}
	}
}

