<?php 
if ( ! class_exists( 'WSTSC_DAO' ) ) {
    class WSTSC_DAO {

        private static $table_name = 'wp_tmp_seat_reservations' ;

        public static function get_unavailable_seats( $show_id ) {
            global $wpdb;
			$unavailable_seats = explode( ',', get_option( 'reserved_seats' ) );
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT meta_value AS seats \n"
					. "FROM wp_woocommerce_order_itemmeta meta1 \n"
					. "WHERE meta1.order_item_id IN (\n"
					. "SELECT distinct order_item_id \n"
					. "FROM wp_woocommerce_order_itemmeta meta2\n"
					. "WHERE meta_key = '_variation_id' AND meta_value='%s') AND meta_key = 'seats'"
					. "UNION ALL\n"
					. "SELECT seats \n"
					. "FROM " . self::$table_name . "\n"
					. "WHERE variation_id = '%s'\n"
					. 'AND expiry > NOW()', $show_id, $show_id
				)
			);
			foreach ( $results as $key => $row ) {
				$unavailable_seats = array_merge( $unavailable_seats, explode( ',', $row->seats ) );
			}

			return $unavailable_seats;
		}

        public static function add_temp_reservation($seats, $show_id, $minutes ){
            global $wpdb;
			$wpdb->insert(
                self::$table_name,
				array(
					'expiry' => gmdate( 'Y-m-d H:i:s', time() + (60 * $minutes) ),
					'seats' => $seats,
					'variation_id' => $show_id,
				)
			);
        }

		public static function create_seat_timer_table() {
			global $wpdb;
			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE " . self::$table_name . " (
                id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
                expiry DATETIME,
                seats TEXT,
            variation_id MEDIUMINT(9),
            PRIMARY KEY (id)
        ) $charset_collate;";
			include_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
		}
    }
}
