<?php

/********************************************************************
 * Version 2.0
 * Get a feed
 * Copyright 2014 Purple Turtle Productions. All rights reserved.
 * license    GNU General Public License version 3 or later; see GPLv3.txt
 * By: Keneto 2014-07-01
 ********************************************************************/
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
	if ( isset( $_POST[ $index ] ) && is_array( $_POST[ $index ] ) ) {
		return array_map( 'sanitize_text_field', wp_unslash( $_POST[ $index ] ) );
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

add_action( 'get_feed_main_hook', 'excpf_get_feed_main' );
do_action( 'get_feed_main_hook' );

function excpf_upload_links( $provider ) {

	$upload_links = array(
		'google'             => 'https://www.exportfeed.com/documentation/upload-data-feeds-via-automatic-upload-to-google-merchant-center/',
		'amazonsc'           => 'http://www.exportfeed.com/documentation/amazon-feed-installation-feed-creation-manual/',
		'ebayseller'         => 'http://www.exportfeed.com/documentation/ebay-seller-guide-2/',
		'facebookxml'        => 'https://www.exportfeed.com/documentation/facebook-dynamic-product-ads/',
		'bing'               => 'https://www.exportfeed.com/documentation/bing-product-ads-guide/',
		'miinto'             => 'http://www.exportfeed.com/documentation/miinto-guide/',
		'miintobrand'        => 'http://www.exportfeed.com/documentation/miinto-guide/',
		'pricerunner'        => 'http://www.exportfeed.com/documentation/price-runner-guide/',
		'bonanza'            => 'http://www.exportfeed.com/documentation/bonanza/',
		'become'             => 'http://www.exportfeed.com/documentation/become-integration-guide/',
		'ebay'               => 'http://www.exportfeed.com/documentation/ebay-commerce-network-integration-guide/',
		'houzz'              => 'http://www.exportfeed.com/documentation/houzz-export-guide/',
		'newegg'             => 'http://www.exportfeed.com/documentation/newegg-integration-guide/',
		'nextag'             => 'http://www.exportfeed.com/documentation/nextag-integration-guide/',
		'pronto'             => 'http://www.exportfeed.com/documentation/pronto-integration-guide/',
		'rakuten'            => 'http://www.exportfeed.com/documentation/rakuten/',
		'rakutennewsku'      => 'http://www.exportfeed.com/documentation/rakuten/',
		'rakutenuK'          => 'http://www.exportfeed.com/documentation/rakuten/',
		'kelkoo'             => 'http://www.exportfeed.com/documentation/kelkoo-guide/',
		'shopping.com'       => 'http://www.exportfeed.com/documentation/shopping-com-integration-guide/',
		'pricegrabber'       => 'http://www.exportfeed.com/documentation/pricegrabber-com-integration-guide/',
		'shopzilla'          => 'https://www.exportfeed.com/documentation/shopzilla-upload-guide/',
		'ammoseek'           => 'https://www.exportfeed.com/documentation/ammoseek-integration-guide/',
		'affiliatewindow'    => 'https://www.exportfeed.com/documentation/affiliate-windows-feed-guide/',
		'affiliatewindowxml' => 'https://www.exportfeed.com/documentation/affiliate-windows-feed-guide/',
		'gpanalysis'         => 'https://www.exportfeed.com/documentation/gpanalysis-merchant-integration-guide/',
		'avantlink'          => 'https://www.exportfeed.com/documentation/avantlink-integration-guide/',
		'webgains'           => 'https://www.exportfeed.com/documentation/webgains-integration-guide/',
		'shareasale'         => 'https://www.exportfeed.com/documentation/shareasale-integration-guide/',
		'rakuten'            => 'https://www.exportfeed.com/documentation/rakuten/',
		'hardwareinfo'       => 'https://www.exportfeed.com/documentation/merchant-integration-guide-hardware-info/',
		'admarkt'            => 'https://www.exportfeed.com/documentation',
	);
	return isset( $upload_links[ $provider ] ) ? $upload_links[ $provider ] : '';
}

function excpf_get_feed_main() {
	$requestCode         = excpf_safeGetPostData( 'provider' );
	$local_category      = excpf_safeGetPostData( 'local_category' );
	$remote_category     = excpf_safeGetPostData( 'remote_category' );
	$remote_sub_category = excpf_safeGetPostData( 'remote_sub_category' );
	$file_name           = excpf_safeGetPostData( 'file_name' );
	$feedIdentifier      = excpf_safeGetPostData( 'feed_identifier' );
	$saved_feed_id       = excpf_safeGetPostData( 'feed_id' );
	$miinto_country_code = excpf_safeGetPostData( 'country_code' ); // For Miinto save country code for further use in edit feed section

	if ( $miinto_country_code == '' ) {
		$miinto_country_code = null;
	}
	$feed_list = excpf_safeGetPostData( 'feed_ids' ); // For Aggregate Feed Provider

	if ( $remote_sub_category ) {
		update_option( 'cpf_remote_sub_category_' . $feedIdentifier, $remote_sub_category );
	}

	$output      = new stdClass();
	$output->url = '';

	if ( strlen( $requestCode ) * strlen( $local_category ) == 0 ) {
		$output->errors = 'Error: error in AJAX request. Insufficient data or categories supplied.';
		excpf_doOutput( $output );
		return;
	}

	if ( strlen( $remote_category ) == 0 ) {
		$output->errors = 'Error: Insufficient data. Please fill in "' . $requestCode . ' category"';
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
	if ( ( strlen( $saved_feed_id ) > 0 ) && ( $saved_feed_id > -1 ) ) {
		require_once dirname( __FILE__ ) . '/../../data/savedfeed.php';
		$saved_feed = new EXCPF_PSavedFeed( $saved_feed_id );
	}

	$providerClass = 'EXCPF_P' . $requestCode . 'Feed';
	$x             = new $providerClass();
	$x->feed_list  = $feed_list; // For Aggregate Provider only
	if ( strlen( $feedIdentifier ) > 0 ) {
		$x->activityLogger = new EXCPF_PFeedActivityLog( $feedIdentifier );
	}
	$x->getFeedData( $local_category, $remote_category, $file_name, $saved_feed, $miinto_country_code );

	if ( $x->success ) {
		$upload_path         = excpf_upload_links( strtolower( $requestCode ) );
		$output->upload_path = $upload_path;
		$output->url         = EXCPF_PFeedFolder::uploadURL() . $x->providerName . '/' . $file_name . '.' . $x->fileformat;
	}

	$output->feed_is_deleted = false;
	$output->errors          = $x->getErrorMessages();

	excpf_doOutput( $output );
}
function excpf_delete_the_feed_with_url( $url ) {
	global $wpdb;
	$wpdb->delete( $wpdb->prefix . 'cp_feeds', array( 'url' => $url ) );
	@unlink( $url );

}

