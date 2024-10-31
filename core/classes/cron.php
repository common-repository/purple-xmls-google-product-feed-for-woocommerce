<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
/********************************************************************
 * Version 2.0
 * Handle the cron items
 * Copyright 2014 Purple Turtle Productions. All rights reserved.
 * license    GNU General Public License version 3 or later; see GPLv3.txt
 * By: Keneto 2014-05-12
 ********************************************************************/

// Create a custom refresh_interval so that scheduled events will be able to display
// in Cron job manager
function excpf_add_xml_refresh_interval() {
	$current_delay = get_option( 'cp_feed_delay' );

	/*
	 return array(
		'refresh_interval' => array('interval' => $current_delay, 'display' => 'XML refresh interval'),
	);*/

	$schedules['excpf_refresh_interval'] = array(
		'interval' => $current_delay,
		'display'  => __( 'XML refresh interval' ),
	);

	return $schedules;
}

class EXCPF_PCPCron {


	public static function doSetup() {
		add_filter( 'cron_schedules', 'excpf_add_xml_refresh_interval' );
		// Delete old (faulty) scheduled cron job from prior versions
		$next_refresh = wp_next_scheduled( 'the_name_of_my_custom_interval' );
		if ( $next_refresh ) {
			wp_unschedule_event( $next_refresh, 'the_name_of_my_custom_interval' );
		}
		$next_refresh = wp_next_scheduled( 'purple_xml_updatefeeds_hook' );
		if ( $next_refresh ) {
			wp_unschedule_event( $next_refresh, 'purple_xml_updatefeeds_hook' );
		}
	}

	public static function scheduleUpdate() {
		// Set the Cron job here. Params are (when, display, hook)
		$current_delay = get_option( 'cp_feed_delay' );
		$next_refresh  = wp_next_scheduled( 'excpf_update_cartfeeds_hook' );
		if ( ! $next_refresh ) {
			wp_schedule_event( strtotime( $current_delay . ' seconds' ), 'excpf_refresh_interval', 'excpf_update_cartfeeds_hook' );
		}
	}
	public static function scheduleAmmoseekUpdate() {
		// Set the Cron job here. Params are (when, display, hook)
		$current_delay = 60;
		$next_refresh  = wp_next_scheduled( 'excpf_update_ammoseek_hook' );
		if ( ! $next_refresh ) {
			wp_schedule_event( strtotime( $current_delay . ' seconds' ), 'excpf_refresh_interval', 'excpf_update_ammoseek_hook' );
		}
	}

}
