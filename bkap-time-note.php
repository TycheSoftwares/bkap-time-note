<?php
/**
 * Plugin Name: Time Slot Note for Booking & Appointment Plugin for WooCommerce
 * Plugin URI: http://www.tychesoftwares.com/store/premium-plugins/woocommerce-booking-plugin
 * Description: This plugin will show timeslot note instead of the timeslot.
 * Version: 1.0
 * Author: Tyche Softwares
 * Author URI: http://www.tychesoftwares.com/
 * Text Domain: bkap-time-note
 * Requires PHP: 5.6
 * WC requires at least: 3.9
 * WC tested up to: 4.5
 *
 * @package  BKAP-Time-Note
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Bkap_TimeSlot_Note' ) ) {

	/**
	 * Class Bkap_Plugin_Meta.
	 *
	 * @since 1.0
	 */
	class Bkap_TimeSlot_Note {

		/**
		 * Bkap_TimeSlot_Note constructor.
		 */
		public function __construct() {
			if ( true ) {
				//add_filter( 'bkap_time_slot_filter', array( $this, 'bkap_time_slot_filter' ), 10, 2 );
				add_filter( 'bkap_time_slot_filter_after_chronological', array( $this, 'bkap_time_slot_filter' ), 10, 2 );
				add_filter( 'bkap_timeslot_key_or_value', array( $this, 'bkap_timeslot_key_or_value' ), 10, 2 );
				add_filter( 'bkap_get_item_data_time_slot', array( $this, 'bkap_get_item_data_time_slot' ), 10, 3 );
				add_action( 'bkap_update_item_meta', array( $this, 'bkap_update_item_meta' ), 10, 3 );
				add_filter( 'bkap_availability_message_display', array( $this, 'bkap_availability_message_display' ), 10, 4 );
				add_filter( 'bkap_update_order_item_meta_timeslot', array( $this, 'bkap_update_order_item_meta_timeslot' ), 10, 4 );
				add_filter( 'bkap_add_reschedule_link_booking_details', array( $this, 'bkap_add_reschedule_link_booking_details' ), 10, 3 );
				add_filter( 'bkap_update_item_bookings_timeslot', array( $this, 'bkap_update_item_bookings_timeslot' ), 10, 3 );
				add_filter( 'bkap_new_time_in_note_on_edit_booking', array( $this, 'bkap_new_time_in_note_on_edit_booking' ), 10, 3 );
				add_filter( 'bkap_edit_booking_time_change_note', array( $this, 'bkap_edit_booking_time_change_note' ), 10, 3 );
			}
		}

		/**
		 * This function will add the note information to its respective timeslots.
		 *
		 * @param array $time_drop_down_array Array of timeslots and its lockout. Key will be timeslot and it contains array with has lockout information.
		 * @param array $extra_information Additional Informations.
		 * @since 1.0
		 */
		public function bkap_time_slot_filter( $time_drop_down_array, $extra_information ) {

			$product_id                = $_POST['post_id'];
			$current_date              = sanitize_text_field( $_POST['current_date'] );
			$booking_date              = date( 'j-n-Y', strtotime( $current_date ) );
			$booking_times             = get_post_meta( $product_id, '_bkap_time_settings', true );
			$time_format_to_show       = $extra_information['time_format_to_show'];
			$time_drop_down_array_note = array();

			foreach ( $time_drop_down_array as $key => $value ) {
				$note                                 = bkap_get_timeslot_data( $product_id, $booking_date, $value, 'booking_notes', $booking_times );
				$time_drop_down_array_note[ $value ] = $note;
			}

			return $time_drop_down_array_note;
		}

		public function bkap_timeslot_key_or_value( $value, $key ) {
			return $key;
		}

		/**
		 * This function is responsible for showing the note information in Cart and Checkout.
		 *
		 * @param array $time_data Data contains array of Label and Value combination for the timeslot information.
		 * @param array $booking Booking Data.
		 * @param int   $product_id Product ID.
		 *
		 * @return array Updated array will be returned with the note information.
		 * @since 1.0
		 */
		public function bkap_get_item_data_time_slot( $time_data, $booking, $product_id ) {

			$note = bkap_get_timeslot_data( $product_id, $booking['hidden_date'], $booking['time_slot'] );
			if ( '' !== $note ) {
				$time_data['display'] = $note;
			}
			return $time_data;
		}

		/**
		 * Updating the timeslot information with note when updating the item meta.
		 *
		 * @param int   $item_id Item ID.
		 * @param int   $product_id Product ID.
		 * @param array $booking_data Booking Data.
		 *
		 * @since 1.0
		 */
		public function bkap_update_item_meta( $item_id, $product_id, $booking_data ) {

			if ( isset( $booking_data['time_slot_note'] ) && '' !== $booking_data['time_slot_note'] ) {
				$name_time_slot = get_option( 'book_item-meta-time' );
				$name_time_slot = ( '' === $name_time_slot ) ? __( 'Booking Time', 'woocommerce-booking' ) : $name_time_slot;
				wc_update_order_item_meta( $item_id, $name_time_slot, $booking_data['time_slot_note'], true );
			}
		}

		/**
		 * Updating the time infomration in the item meta according to the note when order is placed.
		 *
		 * @param string $time_slot_to_display Selected Time slot.
		 * @param int    $item_id Item ID.
		 * @param int    $product_id Product ID.
		 * @param array  $booking_data Booking Data.
		 *
		 * @since 1.0
		 */
		public function bkap_update_order_item_meta_timeslot( $time_slot_to_display, $item_id, $product_id, $booking_data ) {

			if ( isset( $booking_data['time_slot_note'] ) && '' !== $booking_data['time_slot_note'] ) {
				$time_slot_to_display = $booking_data['time_slot_note'];
			}
			return $time_slot_to_display;
		}

		/**
		 * Replacing the time with note in Booking data when rescheduling the Booking from My Account page.
		 * The infomration is used in the localized data to auto populate the correct time.
		 *
		 * @param array $booking_details Booking Data.
		 * @param obj   $item Item Object.
		 * @param obj   $order Order Objet.
		 *
		 * @since 1.0
		 */
		public function bkap_add_reschedule_link_booking_details( $booking_details, $item, $order ) {

			if ( isset( $booking_details['time_slot'] ) && '' !== $booking_details['time_slot'] ) {
				$product_id   = $item->get_product_id( 'view' );
				$time_setting = get_post_meta( $product_id, '_bkap_time_settings', true );
				$data         = bkap_get_timeslot_data( $product_id, $booking_details['hidden_date'], $booking_details['time_slot'], 'time', array(), 'booking_notes' );

				$booking_details['time_slot'] = $data;
			}
			return $booking_details;
		}

		/**
		 * Updating the time to note when rescheduling the booking details from My Account page.
		 *
		 * @param string $timeslot Timeslot.
		 * @param array  $booking_details Booking Details.
		 * @param int    $product_id Product ID.
		 *
		 * @since 1.0
		 */
		public function bkap_update_item_bookings_timeslot( $timeslot, $booking_details, $product_id ) {

			$timeslot = bkap_get_timeslot_data( $product_id, $booking_details['hidden_date'], $booking_details['time_slot'] );
			return $timeslot;
		}

		/**
		 * Updating the time to note when rescheduling the booking details from My Account page.
		 *
		 * @param string $avaiability_msg Availability Message.
		 * @param array  $booking_details Booking Details.
		 * @param int    $product_id Product ID.
		 * @param string $msg_format Message Format.
		 *
		 * @since 1.0
		 */
		public function bkap_availability_message_display( $avaiability_msg, $booking_details, $product_id, $msg_format ) {

			if ( '' !== $booking_details['time_slot'] ) {
				$timeslot          = bkap_get_timeslot_data( $product_id, $booking_details['date'], $booking_details['time_slot'] );
				$available_tickets = $booking_details['available'];
				$date_fld_val      = $booking_details['date_display'];
				$avaiability_msg   = str_replace( array( 'AVAILABLE_SPOTS', 'DATE', 'TIME' ), array( $available_tickets, $date_fld_val, $timeslot ), $msg_format );
			}

			return $avaiability_msg;
		}

		/**
		 * Edit Booking - Updating the time data by converting the note.
		 *
		 * @param string $time Timeslot.
		 * @param array  $booking_details Booking Details.
		 * @param int    $product_id Product ID.
		 *
		 * @since 1.0
		 */
		public function bkap_new_time_in_note_on_edit_booking( $time, $booking_details, $product_id ) {

			$time = bkap_get_timeslot_data( $product_id, $booking_details['hidden_date'], $time, 'booking_notes' );
			return $time;
		}

		/**
		 * Updating the order note based on the note of the timeslot.
		 *
		 * @param string $note Note.
		 * @param int    $product_id Product ID.
		 * @param array  $data Booking Information - OLD & NEW.
		 *
		 * @since 1.0
		 */
		public function bkap_edit_booking_time_change_note( $note, $product_id, $data ) {

			$old_start_display = $data['old_display_date'];
			$display_start     = $data['new_display_date'];
			$old_time          = bkap_get_timeslot_data( $product_id, date( 'j-n-Y', strtotime( $data['old_date'] ) ), $data['old_display_time'], 'booking_notes' );
			$new_time          = $data['new_display_time'];
			$current_user_name = $data['user_name'];
			$note              = "The booking details have been modified from $old_start_display, $old_time to $display_start, $new_time by $current_user_name";

			return $note;
		}
	}
	$bkap_timeslot_note = new Bkap_TimeSlot_Note();
}

/**
 * This function will return the note/time based on the data passed to it.
 *
 * @param int    $product_id Product ID.
 * @param string $date Selected Date.
 * @param string $time Could be timeslot or note.
 * @param string $data Possible string will be time and booking_notes. Depends on $time. if 10:00 - 11:00 passed then time | if morning passed then booking_notes.
 * @param array  $time_settings Time Settings.
 * @param string $passed_data If you want to convert $time to time then time else booking_notes.
 *
 * @since 1.0
 */
function bkap_get_timeslot_data( $product_id, $date, $time, $data = 'booking_notes', $time_settings = array(), $passed_data = 'time' ) {

	$information = '';
	$slots_list  = array();
	$time_format = bkap_common::bkap_get_time_format();
	if ( empty( $time_settings ) ) {
		$time_settings = get_post_meta( $product_id, '_bkap_time_settings', true );
	}

	if ( isset( $time_settings[ $date ] ) ) {
		$slots_list = $time_settings[ $date ];
	} else {
		// check for the weekday.
		$weekday         = date( 'w', strtotime( $date ) );
		$booking_weekday = "booking_weekday_$weekday";

		if ( is_array( $time_settings ) && count( $time_settings ) > 0 && array_key_exists( $booking_weekday, $time_settings ) ) {
			$slots_list = $time_settings[ $booking_weekday ];
		}
	}

	foreach ( $slots_list as $times ) {

		$from_time_check = date( $time_format, strtotime( $times['from_slot_hrs'] . ':' . $times['from_slot_min'] ) );
		$to_time_check   = date( $time_format, strtotime( $times['to_slot_hrs'] . ':' . $times['to_slot_min'] ) );

		if ( '' !== $to_time_check && '00:00' !== $to_time_check && '12:00 AM' !== $to_time_check ) {
			$time_check = "$from_time_check - $to_time_check";
		} else {
			$time_check = "$from_time_check";
		}

		switch ( $passed_data ) {
			case 'time':
				if ( $time_check === $time ) {
					$information = $times[ $data ];
					if ( '' === $information ) {
						$information = $time;
					}
				}
				break;
			case 'booking_notes':
				$note = $times['booking_notes'];
				if ( $note != '' ) {
					if ( $note === $time ) {
						$information = $time_check;
					}
				} else {
					$information = $time;
				}
				break;
		}		
	}

	return $information;
}
