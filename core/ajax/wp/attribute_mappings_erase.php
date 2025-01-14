<?php
/********************************************************************
 * Version 2.0
 * Erase Attribute Mappings that may be hidden away in the options table
 * because they were removed from Woocommerce before being removed from
 * the plugin. ONE DAY these options probably need to be given their own
 * table that the user can edit
 * Copyright 2014 Purple Turtle Productions. All rights reserved.
 * license    GNU General Public License version 3 or later; see GPLv3.txt
 * By: Keneto 2014-05-23
 * 2014-06-08 feedcore now loads wp-load.php and handles other init tasks
 ********************************************************************/
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
if ( ! is_admin() ) {
	die( 'Permission Denied!' );
}

require_once dirname( __FILE__ ) . '/../../data/feedcore.php';
if ( isset( $_REQUEST['security'] ) ) {
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['security'] ) ), 'cpf_nonce' ) ) {
		die( 'Permission denied' );
	}
}

global $wpdb;
$providerName = isset( $_POST['service_name'] ) ? sanitize_text_field( wp_unslash( $_POST['service_name'] ) ) : '';

$mappings = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT * FROM $wpdb->options 
        WHERE $wpdb->options.option_name LIKE %s",
		$wpdb->esc_like( $providerName ) . '_cp_%'
	)
);
foreach ( $mappings as $this_option ) {
	delete_option( $this_option->option_name );
}

echo '1';
