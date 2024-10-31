<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
if ( ! is_admin() ) {
	die( 'Permission Denied!' );
}
/**
 * Created by PhpStorm.
 * User: subash
 * Date: 8/2/16
 * Time: 12:45 PM
 */

/********************************************************************
 * Version 2.0
 * Get a feed
 * Copyright 2014 Purple Turtle Productions. All rights reserved.
 * license    GNU General Public License version 3 or later; see GPLv3.txt
 * By: Keneto 2014-07-01
 */

define( 'XMLRPC_REQUEST', true );
// ob_start(null, 0, PHP_OUTPUT_HANDLER_FLUSHABLE | PHP_OUTPUT_HANDLER_CLEANABLE);
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

function excpf_doOutput( $output ) {
	ob_clean();
	echo json_encode( $output );
}

require_once dirname( __FILE__ ) . '/../../../cart-product-wpincludes.php';

do_action( 'load_cpf_modifiers' );
global $pfcore;
$pfcore->trigger( 'cpf_init_feeds' );

add_action( 'save_selected_feed_hook', 'excpf_save_selected_feed' );
do_action( 'save_selected_feed_hook' );

function excpf_save_selected_feed() {
	if ( isset( $_REQUEST['security'] ) ) {
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['security'] ) ), 'cpf_nonce' ) ) {
			die( 'Permission denied' );
		}
	}
	 echo '<pre>';
	$sub = array();
	if ( isset( $_POST['a'] ) ) {
		foreach ( array_map( 'sanitize_text_field', wp_unslash( $_POST['a'] ) ) as $k => $v ) {
			foreach ( $v as $z => $x ) {
				$new_ey       = sanitize_text_field( $x['name'] );
				$x[ $newKey ] = sanitize_text_field( $x['value'] );
				unset( $x['name'] );
				unset( $x['value'] );
				print_r( $v );
				/*$sub[]  =  $x;*/
				/** $wpdb->insert(
				 * $wpdb->prefix.'cpf_selected_product_list',
				 * array(
				 * 'product_id' => $y['cpf_product_id'],
				 * 'product_name' => $y['cpf_product_name'],
				 * 'local_category' => $y['cpf_local_category'],
				 * 'remote_category' => $y['cpf_remote_category'],
				 * 'description' => $y['cpf_description']
				 * )
				 * );*/
			}
		}
	}
	/**foreach ($sub as $w => $y){
	 * print_r($y);
	 * global $wpdb;
	 * $wpdb->insert(
	 * $wpdb->prefix.'cpf_selected_product_list',
	 * array(
	 * 'product_id' => $y['cpf_product_id'],
	 * 'product_name' => $y['cpf_product_name'],
	 * 'local_category' => $y['cpf_local_category'],
	 * 'remote_category' => $y['cpf_remote_category'],
	 * 'description' => $y['cpf_description']
	 * )
	 * );
	 * }*/

	echo '</pre>';

	/**$requestCode = excpf_safeGetPostData('provider');
	 * $local_category =    excpf_safeGetPostData('local_category');
	 * $remote_category = excpf_safeGetPostData('remote_category');
	 * $file_name = excpf_safeGetPostData('file_name');
	 * $feedIdentifier = excpf_safeGetPostData('feed_identifier');
	 * $saved_feed_id = excpf_safeGetPostData('feed_id');
	 * $feed_list = excpf_safeGetPostData('feed_ids'); //For Aggregate Feed Provider
	 *
	 * $output = new stdClass();
	 * $output->url = '';
	 *
	 * if (strlen($requestCode) * strlen($local_category) == 0) {
	 * $output->errors = 'Error: error in AJAX request. Insufficient data or categories supplied.';
	 * excpf_doOutput($output);
	 * return;
	 * }*/

}
