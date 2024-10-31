<?php
/********************************************************************
 * Version 2.0
 * Get a feed's generation Status
 * Copyright 2014 Purple Turtle Productions. All rights reserved.
 * license    GNU General Public License version 3 or later; see GPLv3.txt
 * By: Keneto 2014-07-02
 ********************************************************************/
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
if ( ! is_admin() ) {
	die( 'Permission Denied!' );
}

define( 'XMLRPC_REQUEST', true );
ob_start( null );

function excpf_safeGetPostData( $index ) {
	if ( isset( $_REQUEST['security'] ) ) {
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['security'] ) ), 'cpf_nonce' ) ) {
			die( 'Permission denied' );
		}
	}
	if ( isset( $_POST[ $index ] ) ) {
		return sanitize_text_field( wp_unslash( $_POST[ $index ] ) );
	} else {
		return '';
	}
}

$feedIdentifier = excpf_safeGetPostData( 'feed_identifier' );

ob_clean();
echo wp_kses( get_option( 'cp_feedActivity_' . $feedIdentifier ), excpf_kses_criteria() );
