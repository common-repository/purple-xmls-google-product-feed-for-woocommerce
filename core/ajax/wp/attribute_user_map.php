<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
if ( ! is_admin() ) {
	die( 'Permission Denied!' );
}
if ( isset( $_REQUEST['security'] ) ) {
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['security'] ) ), 'cpf_nonce' ) ) {
		die( 'Permission denied' );
	}
}
$map_string = get_option( 'cpf_attribute_user_map_' . isset( $_POST['service_name'] ) ? sanitize_text_field( wp_unslash( $_POST['service_name'] ) ) : '' );

if ( strlen( $map_string ) === 0 ) {
	$map_ = array();
} else {
	$map_ = json_decode( $map_string );
	$map_ = get_object_vars( $map_ );
}

$attr           = isset( $_POST['attribute'] ) ? sanitize_text_field( wp_unslash( $_POST['attribute'] ) ) : '';
$mapto          = isset( $_POST['mapto'] ) ? sanitize_text_field( wp_unslash( $_POST['mapto'] ) ) : '';
$map_[ $mapto ] = $attr;

if ( '(Reset)' === $attr ) {
	$new_map = array();
	foreach ( $map_ as $index => $item ) {
		if ( $index !== $mapto ) {
			$new_map[ $index ] = is_array( $item ) ? array_map( 'sanitize_text_field', wp_unslash( $item ) ) : sanitize_text_field( wp_unslash( $item ) );
		}
	}
	$map_ = $new_map;
}

update_option( 'cpf_attribute_user_map_' . sanitize_text_field( wp_unslash( $_POST['service_name'] ) ), json_encode( $map_ ) );
