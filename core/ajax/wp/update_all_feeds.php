<?php

/********************************************************************
 * Version 2.1
 * Update all the Feeds at once instead of having to wait for a Cron job
 * Copyright 2014 Purple Turtle Productions. All rights reserved.
 * license    GNU General Public License version 3 or later; see GPLv3.txt
 * By: Keneto 2014-05-23
 * 2014-07-09 Edited to add "successful" message -Keneto
 ********************************************************************/
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
if ( ! is_admin() ) {
	die( 'Permission Denied!' );
}
if ( isset( $_REQUEST['security'] ) ) {
	$nonce = sanitize_text_field( wp_unslash( $_REQUEST['security'] ) );
	if ( ! wp_verify_nonce( $nonce, 'cpf_nonce' ) ) {
		die( 'Permission denied' );
	}
}
$feed_id = isset( $_POST['feed_id'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['feed_id'] ) ) : '';

if ( isset( $_POST['deleteaction'] ) && sanitize_text_field( wp_unslash( $_POST['deleteaction'] ) ) === 'true' ) {
	global $wpdb;
	$table = $wpdb->prefix . 'cp_feeds';

	foreach ( $feed_id as $key => $value ) {
		$trans = $wpdb->query( $wpdb->prepare( "DELETE FROM $table WHERE id = %d ", array( $value ) ) );
		if ( ! $trans ) {
			echo esc_html( 'There was problem in deleting product with id ' . $value );
			exit;
		}
	}
	$response = array(
		'msg'    => 'Selected feed deleted successfully',
		'result' => 'success',
	);
	echo json_encode( $response );
	exit;

} else {
	excpf_update_all_cart_feeds( false, $feed_id );
	echo 'Update successful';
}
