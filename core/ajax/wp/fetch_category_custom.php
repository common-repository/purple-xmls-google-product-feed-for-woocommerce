<?php

/********************************************************************
 * Version 2.0
 * Go get the category
 * Copyright 2014 Purple Turtle Productions. All rights reserved.
 * license    GNU General Public License version 3 or later; see GPLv3.txt
 * By: Keneto 2014-05-18
 ********************************************************************/
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
define( 'XMLRPC_REQUEST', true );

require_once dirname( __FILE__ ) . '/../../data/feedcore.php';

$service_name = isset( $_POST['service_name'] ) ? sanitize_text_field( wp_unslash( $_POST['service_name'] ) ) : '';

$data = '';
if ( class_exists( 'EXCPF_Taxonomy' ) ) {
	$data = EXCPF_Taxonomy::onLoadTaxonomy( strtolower( sanitize_text_field( wp_unslash( $_POST['service_name'] ) ) ) );
}

if ( strlen( $data ) == 0 ) {
	$filenametoget = dirname( __FILE__ ) . '/../../feeds/' . strtolower( $service_name ) . '/categories.txt';
	if ( file_exists( realpath( $filenametoget ) ) ) {
		$data = file_get_contents( $filenametoget );
	} else {
		$data = null;
	}
}

$data       = explode( "\n", $data );
$searchTerm = isset( $_POST['partial_data'] ) ? strtolower( sanitize_text_field( wp_unslash( $_POST['partial_data'] ) ) ) : '';
$count      = 0;
$canDisplay = true;
if ( ! empty( $searchTerm ) ) {
	foreach ( $data as $this_item ) {

		if ( strlen( $this_item ) * strlen( $searchTerm ) == 0 ) {
			continue;
		}

		if ( strpos( strtolower( $this_item ), $searchTerm ) !== false ) {

			// Transform item from chicken-scratch into something the system can recognize later
			$option = str_replace( ' & ', '.and.', str_replace( ' / ', '.in.', trim( $this_item ) ) );
			$option = str_replace( "'", '', $option );

			// Transform a category from chicken-scratch into something the user can read
			$text = htmlentities( trim( $this_item ) );

			if ( $canDisplay ) {
				echo '<div class="categoryItem" onclick="doSelectCategory_custom(this, \'' . esc_html( $option ) . '\', \'' . esc_html( $service_name ) . '\'),moveSelected(\'assigncategory\');">' . esc_html( $text ) . '</div>';
			}
			$count++;
			if ( ( strlen( $searchTerm ) < 3 ) && ( $count > 15 ) ) {
				$canDisplay = false;
			}
		}
	}
} else {
	foreach ( $data as $this_item ) {
		// Transform item from chicken-scratch into something the system can recognize later
		$option = str_replace( ' & ', '.and.', str_replace( ' / ', '.in.', trim( $this_item ) ) );
		$option = str_replace( "'", '', $option );

		// Transform a category from chicken-scratch into something the user can read
		$text = htmlentities( trim( $this_item ) );

		echo '<div class="categoryItem" onclick="doSelectCategory_custom(this, \'' . esc_html( $option ) . '\', \'' . esc_html( $service_name ) . '\'),moveSelected(\'assigncategory\');">' . esc_html( $text ) . '</div>';

	}
}

if ( ! $canDisplay ) {
	echo '<div class="categoryItem">(' . esc_html( $count ) . ' results)</div>';

}
