<?php
/***********************************************************
 * Plugin Name: Product Feed on WooCommerce for Google, Awin, Shareasale, Bing, and More
 * Plugin URI: www.exportfeed.com
 * Description: WooCommerce Product Feed Export :: <a target="_blank" href="http://www.exportfeed.com/tos/">How-To Click Here</a>
 * Author: ExportFeed.com
 * Version: 3.5.7
 * Author URI: www.exportfeed.com
 * Authors: Haris, Keneto (May2014)
 * Note: The "core" folder is shared to the Joomla component.
 * Changes to the core, especially /core/data, should be considered carefully
 * Note: "purple" term exists from legacy plugin name. Classnames in "P" for the same reason
 * WC requires at least: 3.5
 * WC tested up to: 6.0
 * Copyright 2015 WRI HK Ltd. All rights reserved.
 * license GNU General Public License version 3 or later; see GPLv3.txt
 ***********************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once ABSPATH . 'wp-admin/includes/plugin.php';
$plugin_version_data = get_plugin_data( __FILE__ );
// current version: used to show version throughout plugin pages
define( 'EXCPF_FEED_PLUGIN_VERSION', $plugin_version_data['Version'] );
define( 'EXCPF_PLUGIN_BASENAME', plugin_basename( __FILE__ ) ); // cart-product-feed/cart-product-feed.php
define( 'EXCPF_PATH', realpath( dirname( __FILE__ ) ) );
define( 'EXCPF_URL', plugins_url() . '/' . basename( dirname( __FILE__ ) ) . '/' );
// functions to display cart-product-feed version and checks for updates
require_once 'cart-product-information.php';
require_once 'cart-product-setup.php';
// action hook for plugin activation
register_activation_hook( __FILE__, 'excpf_cart_product_activate_plugin' );
register_deactivation_hook( __FILE__, 'excpf_cart_product_deactivate_plugin' );

add_action( 'cpf_plugins_loaded', 'excpf_cart_product_activate_plugin' );
$CPFDBVERSION = get_option( 'CPFDBVERSION' );

if ( $CPFDBVERSION !== EXCPF_FEED_PLUGIN_VERSION ) {
	do_action( 'cpf_plugins_loaded' );
	update_option( 'CPFDBVERSION', EXCPF_FEED_PLUGIN_VERSION );
}

global $cp_feed_order, $cp_feed_order_reverse;

require_once 'core/classes/cron.php';
require_once 'core/data/feedfolders.php';
require_once 'core/helpers.php';

if ( get_option( 'cp_feed_order_reverse' ) == '' ) {
	add_option( 'cp_feed_order_reverse', false );
}

if ( get_option( 'cpf_custom_attribute_user_map' ) == '' ) {
	add_option( 'cpf_custom_attribute_user_map', false );
}

if ( get_option( 'cp_feed_order' ) == '' ) {
	add_option( 'cp_feed_order', 'id' );
}

if ( get_option( 'cp_feed_delay' ) == '' ) {
	add_option( 'cp_feed_delay', '43200' );
}

if ( get_option( 'cp_licensekey' ) == '' ) {
	add_option( 'cp_licensekey', 'none' );
}

if ( get_option( 'cp_localkey' ) == '' ) {
	add_option( 'cp_localkey', 'none' );
}

// ***********************************************************
// cron schedules for Feed Updates
// ***********************************************************

EXCPF_PCPCron::doSetup();
EXCPF_PCPCron::scheduleUpdate();
EXCPF_PCPCron::scheduleAmmoseekUpdate();

// ***********************************************************
// Update Feeds (Cron)
// 2014-05-09 Changed to now update all feeds... not just Google Feeds
// ***********************************************************

add_action( 'excpf_update_cartfeeds_hook', 'excpf_update_all_cart_feeds' );
add_action( 'excpf_update_ammoseek_hook', 'excpf_update_ammoseek_feeds' );

function excpf_update_all_cart_feeds( $doRegCheck = true, $feed_id = array() ) {
	require_once 'cart-product-wpincludes.php'; // The rest of the required-files moved here
	require_once 'core/data/savedfeed.php';

	$reg = new EXCPF_PLicense();
	if ( $doRegCheck && ( $reg->results['status'] != 'Active' ) ) {
		return;
	}

	do_action( 'load_cpf_modifiers' );
	add_action( 'get_feed_main_hook', 'excpf_update_all_cart_feeds_step_2' );
	do_action( 'get_feed_main_hook', $feed_id );
}

function excpf_update_all_cart_feeds_step_2( $feed_id ) {
	global $wpdb;
	$feed_table = $wpdb->prefix . 'cp_feeds';
	$where      = '';
	if ( is_array( $feed_id ) && ! empty( $feed_id ) ) {
		$feed_id = implode( ',', $feed_id );
		$where   = ' WHERE id IN ' . '(' . $feed_id . ') ';
		$limit   = '';
	} else {
		$limit = ' ORDER BY updated_at ASC limit 100';
	}
	$sql              = 'SELECT id, type, filename,product_count FROM ' . $feed_table . $where . $limit;
	$feed_ids         = $wpdb->get_results( $sql );
	$savedProductList = null;

	// ***********************************************************
	// Build stack of aggregate providers
	// ***********************************************************
	$aggregateProviders = array();
	foreach ( $feed_ids as $this_feed_id ) {
		if ( $this_feed_id->type == 'AggXml' || $this_feed_id->type == 'AggXmlGoogle' || $this_feed_id->type == 'AggCsv' || $this_feed_id->type == 'AggTxt' || $this_feed_id->type == 'AggTsv' || $this_feed_id->type == 'AmmoAggXml' ) {
			$providerName = $this_feed_id->type;
			$providerFile = 'core/feeds/' . strtolower( $providerName ) . '/feed.php';
			if ( ! file_exists( dirname( __FILE__ ) . '/' . $providerFile ) ) {
				continue;
			}

			require_once $providerFile;

			// Initialize provider data
			$providerClass = 'EXCPF_P' . $providerName . 'Feed';
			$x             = new $providerClass( null );
			$x->initializeAggregateFeed( $this_feed_id->id, $this_feed_id->filename );
			$aggregateProviders[] = $x;
		}
	}

	// ***********************************************************
	// Main
	// ***********************************************************
	if ( count( $feed_ids ) > 0 ) {
		ini_set( 'max_execution_time', 0 );
		ini_set( 'memory_limit', '512M' );
	}

	foreach ( $feed_ids as $index => $this_feed_id ) {

		$saved_feed   = new EXCPF_PSavedFeed( $this_feed_id->id );
		$providerName = $saved_feed->provider;

		// Skip any Aggregate Types
		if ( $providerName == 'AggXml' || $providerName == 'AggXmlGoogle' || $providerName == 'AggCsv' || $providerName == 'AggTxt' || $providerName == 'AggTsv' || $this_feed_id->type == 'AmmoAggXml' ) {
			continue;
		}

		// Make sure someone exists in the core who can provide the feed
		$providerFile = 'core/feeds/' . strtolower( $providerName ) . '/feed.php';
		if ( ! file_exists( dirname( __FILE__ ) . '/' . $providerFile ) ) {
			continue;
		}

		require_once $providerFile;

		// Initialize provider data
		$providerClass         = 'EXCPF_P' . $providerName . 'Feed';
		$x                     = new $providerClass();
		$x->aggregateProviders = $aggregateProviders;
		$x->savedFeedID        = $saved_feed->id;
		$miinto_country_code   = $saved_feed->miinto_country_code;

		$x->productList = $savedProductList;

		if ( $saved_feed->feed_type != 1 ) {
			/**
			 * $x is a class which extends basicfeed
			 * $x->getFeedData($category, $remote_category, $file_name, $saved_feed = null, $miinto_country_code = null, $isupdate=false)
			 * returns boolean
			 */
			$x->getFeedData( $saved_feed->category_id, $saved_feed->remote_category, $saved_feed->filename, $saved_feed, $miinto_country_code, true );

		} else {
			/**
			 * Its same like $x->getFeedData except this is for custom feed
			 * */
			if ( $saved_feed->feed_type == 1 ) {
				$x->getCustomFeedData( $saved_feed->filename, $saved_feed, $saved_feed->id, $miinto_country_code = 'update', $saved_feed->remote_category, $saved_feed->feed_identifier );
			}

			$feed_table = $wpdb->prefix . 'cp_feeds';
			$wpdb->query( $wpdb->prepare( "UPDATE $feed_table SET `updated_at` = date('Y-m-d H:i:s') WHERE `id`=$this_feed_id->id" ) );
			continue;
		}

		$savedProductList = $x->productList;
		$x->products      = null;

	}

	foreach ( $aggregateProviders as $thisAggregateProvider ) {
		$thisAggregateProvider->finalizeAggregateFeed();
	}

}

function excpf_update_ammoseek_feeds() {
	require_once 'cart-product-wpincludes.php';
	require_once 'core/data/savedfeed.php';

	global $wpdb;
	$feed_table = $wpdb->prefix . 'cp_feeds';
	// $del = $wpdb->query("DELETE FROM $feed_table WHERE id=79");
	// echo $del;
	$feed_ids         = $wpdb->get_results( $wpdb->prepare( "SELECT id, type, filename,product_count FROM $feed_table WHERE type = 'AmmoSeek' ORDER BY updated_at ASC" ) );
	$savedProductList = null;
	if ( $feed_ids ) {
		// ***********************************************************
		// Build stack of aggregate providers
		// ***********************************************************
		$aggregateProviders = array();
		foreach ( $feed_ids as $this_feed_id ) {
			if ( $this_feed_id->type == 'AggXml' || $this_feed_id->type == 'AggXmlGoogle' || $this_feed_id->type == 'AggCsv' || $this_feed_id->type == 'AggTxt' || $this_feed_id->type == 'AggTsv' || $this_feed_id->type == 'AmmoAggXml' ) {
				$providerName = $this_feed_id->type;
				$providerFile = 'core/feeds/' . strtolower( $providerName ) . '/feed.php';
				if ( ! file_exists( dirname( __FILE__ ) . '/' . $providerFile ) ) {
					continue;
				}

				require_once $providerFile;

				// Initialize provider data
				$providerClass = 'EXCPF_P' . $providerName . 'Feed';
				$x             = new $providerClass( null );
				$x->initializeAggregateFeed( $this_feed_id->id, $this_feed_id->filename );
				$aggregateProviders[] = $x;
			}
		}
		// ***********************************************************
		// Main
		// ***********************************************************
		if ( count( $feed_ids ) > 0 ) {
			ini_set( 'max_execution_time', 0 );
			ini_set( 'memory_limit', '512M' );
		}

		foreach ( $feed_ids as $index => $this_feed_id ) {

			$saved_feed   = new EXCPF_PSavedFeed( $this_feed_id->id );
			$providerName = $saved_feed->provider;

			// Make sure someone exists in the core who can provide the feed
			$providerFile = 'core/feeds/' . strtolower( $providerName ) . '/feed.php';
			if ( ! file_exists( dirname( __FILE__ ) . '/' . $providerFile ) ) {
				continue;
			}

			require_once $providerFile;

			// Initialize provider data
			$providerClass         = 'EXCPF_P' . $providerName . 'Feed';
			$x                     = new $providerClass();
			$x->aggregateProviders = $aggregateProviders;
			$x->savedFeedID        = $saved_feed->id;
			$miinto_country_code   = $saved_feed->miinto_country_code;

			$x->productList = $savedProductList;

			if ( $saved_feed->feed_type != 1 ) {
				$x->getFeedData( $saved_feed->category_id, $saved_feed->remote_category, $saved_feed->filename, $saved_feed, $miinto_country_code );
			} else {
				if ( $saved_feed->feed_type == 1 ) {
					$x->getCustomFeedData( $saved_feed->filename, $saved_feed, $saved_feed->id, $miinto_country_code = 'update', $saved_feed->remote_category, $saved_feed->feed_identifier );
				}
			}

			$feed_table = $wpdb->prefix . 'cp_feeds';
			$wpdb->query(
				$wpdb->prepare(
					"
			UPDATE $feed_table
			SET
				`updated_at` = date('Y-m-d H:i:s')
			WHERE `id`=$this_feed_id->id"
				)
			);
			continue;

			$savedProductList = $x->productList;
			$x->products      = null;

		}

		foreach ( $aggregateProviders as $thisAggregateProvider ) {
			$thisAggregateProvider->finalizeAggregateFeed();
		}
	}
}

// ***********************************************************
// Links From the Install Plugins Page (WordPress)
// ***********************************************************

if ( is_admin() ) {
	require_once 'cart-product-feed-admin.php';
	$plugin_ = plugin_basename( __FILE__ );
	add_filter( 'plugin_action_links_' . $plugin_, 'excpf_cart_product_manage_feeds_link' );
}

// ***********************************************************
// Function to create feed generation link  in installed plugin page
// ***********************************************************
function excpf_cart_product_manage_feeds_link( $links ) {

	$settings_link = '<a href="admin.php?page=cart-product-feed-manage-page">Manage Feeds</a>';
	array_unshift( $links, $settings_link );

	return $links;

}
