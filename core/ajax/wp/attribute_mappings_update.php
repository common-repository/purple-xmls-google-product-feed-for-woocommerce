<?php
/********************************************************************
 * Version 2.0
 * Save a change in attribute mappings
 * Copyright 2014 Purple Turtle Productions. All rights reserved.
 * license    GNU General Public License version 3 or later; see GPLv3.txt
 * By: Keneto 2014-05-13
 * 2014-11 Note: This format is possibly to be phased out in favour of attribute_user_map
 ********************************************************************/

if ( isset( $_REQUEST['security'] ) ) {
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['security'] ) ), 'cpf_nonce' ) ) {
		die( 'Permission denied' );
	}
}
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
if ( ! is_admin() ) {
	die( 'Permission Denied!' );
}
update_option( ( isset( $_POST['service_name'] ) ? sanitize_text_field( wp_unslash( $_POST['service_name'] ) ) : '' . '_cp_' . isset( $_POST['attribute'] ) ) ? sanitize_text_field( wp_unslash( $_POST['attribute'] ) ) : '', isset( $_POST['mapto'] ) ? sanitize_text_field( wp_unslash( $_POST['mapto'] ) ) : '' );
