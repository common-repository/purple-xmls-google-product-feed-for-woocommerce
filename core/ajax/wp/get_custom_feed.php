<?php
/**
 * Created by PhpStorm.
 * User: subash
 * Date: 8/11/16
 * Time: 11:06 AM
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
if ( ! is_admin() ) {
	die( 'Permission Denied!' );
}

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
		return is_array( $_POST[ $index ] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST[ $index ] ) ) : sanitize_text_field( wp_unslash( $_POST[ $index ] ) );
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

add_action( 'get_feed_main_hook', 'excpf_get_feed_main' );
do_action( 'get_feed_main_hook' );

function excpf_get_feed_main() {
	if ( isset( $_REQUEST['security'] ) ) {
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['security'] ) ), 'cpf_nonce' ) ) {
			die( 'Permission denied' );
		}
	}

	$is_edit        = false;
	$requestCode    = excpf_safeGetPostData( 'provider' );
	$file_name      = excpf_safeGetPostData( 'file_name' );
	$feedIdentifier = array_key_exists( 'feed_identifier', $_POST ) ? excpf_safeGetPostData( 'feed_identifier' ) : '';
	$saved_feed_id  = excpf_safeGetPostData( 'feed_id' );
	$feed_list      = excpf_safeGetPostData( 'feed_ids' ); // For Aggregate Feed Provider
	$feedLimit      = excpf_safeGetPostData( 'feedLimit' );
	if ( array_key_exists( 'is_edit', $_POST ) ) {
		$is_edit = isset( $_POST['is_edit'] ) ? sanitize_text_field( wp_unslash( $_POST['is_edit'] ) ) : false;
	}
	$miintoCountryCode   = array_key_exists( 'country_code', $_POST ) ? excpf_safeGetPostData( 'country_code' ) : null;
	$remoteCat           = array_key_exists( 'remote_category', $_POST ) ? excpf_safeGetPostData( 'remote_category' ) : null;
	$remote_sub_category = array_key_exists( 'remote_sub_category', $_POST ) ? excpf_safeGetPostData( 'remote_sub_category' ) : null;

	$output      = new stdClass();
	$output->url = '';

	if ( $remote_sub_category ) {
		update_option( 'cpf_remote_sub_category_' . $feedIdentifier, $remote_sub_category );
	}

	if ( ! ( $file_name ) ) {
		$output->errors = 'Error: Please mention file name for the feed';
		excpf_doOutput( $output );
		return;
	}

	// Check if form was posted and select task accordingly
	$dir = EXCPF_PFeedFolder::uploadRoot();
	if ( ! is_writable( $dir ) ) {
		$output->errors = "Error: $dir should be writeable";
		excpf_doOutput( $output );
		return;
	}
	$dir = EXCPF_PFeedFolder::uploadFolder();
	if ( ! is_dir( $dir ) ) {
		mkdir( $dir );
	}
	if ( ! is_writable( $dir ) ) {
		$output->errors = "Error: $dir should be writeable";
		excpf_doOutput( $output );
		return;
	}

	$providerFile = 'feeds/' . strtolower( $requestCode ) . '/feed.php';

	if ( ! file_exists( dirname( __FILE__ ) . '/../../' . $providerFile ) ) {
		if ( ! class_exists( 'EXCPF_P' . $requestCode . 'Feed' ) ) {
			$output->errors = 'Error: Provider file not found.';
			excpf_doOutput( $output );
			return;
		}
	}

	$providerFileFull = dirname( __FILE__ ) . '/../../' . $providerFile;
	if ( file_exists( $providerFileFull ) ) {
		require_once $providerFileFull;
	}

	// Load form data
	$file_name = sanitize_title_with_dashes( $file_name );
	if ( $file_name == '' ) {
		$file_name = 'feed' . rand( 10, 1000 );
	}

	$saved_feed = null;
	if ( ( strlen( $saved_feed_id ) > 0 ) && ( $saved_feed_id > -1 ) && $is_edit !== 'false' ) {
		require_once dirname( __FILE__ ) . '/../../data/savedfeed.php';
		$saved_feed = new EXCPF_PSavedFeed( $saved_feed_id );
	}

	$providerClass = 'EXCPF_P' . $requestCode . 'Feed';
	$x             = new $providerClass();

	$x->feed_list = $feed_list; // For Aggregate Provider only
	if ( strlen( $feedIdentifier ) > 0 ) {
		$x->activityLogger = new EXCPF_PFeedActivityLog( $feedIdentifier );
	}
	$x->getCustomFeedData( $file_name, $saved_feed, $saved_feed_id, $miintoCountryCode, $remoteCat, $feedIdentifier );

	if ( $x->success ) {
		$output->url = EXCPF_PFeedFolder::uploadURL() . $x->providerName . '/' . $file_name . '.' . $x->fileformat;
	}
	$output->errors = $x->getErrorMessages();
	global $wpdb;
	$table_name = $wpdb->prefix . 'cpf_custom_products';
	$wpdb->query( $wpdb->prepare( "TRUNCATE TABLE $table_name" ) );

	excpf_doOutput( $output );
}

function getCustomProductFeed() {
	global $wpdb;
	$tableName = $wpdb->prefix . 'cpf_custom_products';
	return $wpdb->get_results(
		$wpdb->prepare(
			"
  SELECT category , product_title , category_name , remote_category
  FROM $tableName
"
		),
		ARRAY_A
	);
}
