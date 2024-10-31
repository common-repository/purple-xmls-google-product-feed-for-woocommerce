<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/********************************************************************
 * Version 1.2
 * Modified: 2014-05-01 Now Product Categories can export to both XML and TXT ( CSV or Tabbed )
 * Copyright 2015 WRI HK LTD. All rights reserved.
 * license    GNU General Public License version 3 or later; see GPLv3.txt
 * By: Keneto
 */


/*
 * Required admin files
 *
 */
require_once 'cart-product-setup.php';
/**
 * Hooks for adding admin specific styles and scripts
 */
function excpf_register_cart_product_styles_and_scripts( $hook ) {
	if ( ! strchr( $hook, 'cart-product-feed' ) ) {
		return;
	}

	wp_register_style( 'cart-product-style', plugins_url( 'css/cart-product.css', __FILE__ ), '', EXCPF_FEED_PLUGIN_VERSION );
	wp_enqueue_style( 'cart-product-style' );

	wp_register_style( 'cpf-datatable-css', plugins_url( 'css/DataTables/datatables.css', __FILE__ ) );
	wp_enqueue_style( 'cpf-datatable-css' );

	wp_register_style( 'cart-product-colorstyle', plugins_url( 'css/colorbox.css', __FILE__ ), '', EXCPF_FEED_PLUGIN_VERSION );
	wp_enqueue_style( 'cart-product-colorstyle' );

	wp_register_script( 'cart-product-script', plugins_url( 'js/cart-product.js', __FILE__ ), array( 'jquery' ), EXCPF_FEED_PLUGIN_VERSION );
	wp_enqueue_script( 'cart-product-script' );

	wp_register_script( 'cart-product-colorbox', plugins_url( 'js/jquery.colorbox-min.js', __FILE__ ), array( 'jquery' ) );
	wp_enqueue_script( 'cart-product-colorbox' );

	/* Datatables attributes */
	wp_register_script( 'cpf-datatable-script', plugins_url( 'css/DataTables/datatables.js', __FILE__ ), array( 'jquery' ) );
	wp_enqueue_script( 'cpf-datatable-script' );

	wp_localize_script(
		'cart-product-script',
		'cpf',
		array(
			'cpf_nonce' => wp_create_nonce( 'cpf_nonce' ),
			'action'    => 'cpf_cart_product',
		)
	);
}

/*
 * ajax handles
 * */
if ( isset( $_REQUEST['action'] ) && ( sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) == 'cpf_cart_product' ) ) {
	add_action( 'wp_ajax_cpf_cart_product', 'excpf_all_ajax_handles' );
}

add_action( 'admin_enqueue_scripts', 'excpf_register_cart_product_styles_and_scripts' );

/*
 * ajax handle function
 * */
function excpf_all_ajax_handles() {
	$nonce = isset( $_REQUEST['security'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['security'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'cpf_nonce' ) ) {
		die( 'Permission denied' );
	} else {
		$feedpath = isset( $_REQUEST['feedpath'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['feedpath'] ) ) : '';
		include_once plugin_dir_path( __FILE__ ) . $feedpath;
	}
	die;
}

	/**
	 * Add menu items to the admin
	 */
function excpf_cart_product_admin_menu() {

	/* add new top level */
	add_menu_page(
		esc_html__( 'Product Feed', 'cart-product-strings' ),
		esc_html__( 'Product Feed', 'cart-product-strings' ),
		'manage_options',
		'cart-product-feed-admin',
		'excpf_cart_product_feed_admin_page',
		plugins_url( '/', __FILE__ ) . '/images/xml-icon.png'
	);

	/* add the submenus */
	add_submenu_page(
		'cart-product-feed-admin',
		esc_html__( 'Create New Feed', 'cart-product-strings' ),
		esc_html__( 'Create New Feed', 'cart-product-strings' ),
		'manage_options',
		'cart-product-feed-admin',
		'excpf_cart_product_feed_admin_page'
	);

	add_submenu_page(
		'cart-product-feed-admin',
		esc_html__( 'Manage Feeds', 'cart-product-strings' ),
		esc_html__( 'Manage Feeds', 'cart-product-strings' ),
		'manage_options',
		'cart-product-feed-manage-page',
		'excpf_cart_product_feed_manage_page'
	);

	add_submenu_page(
		'cart-product-feed-admin',
		esc_html__( 'Tutorials', 'cart-product-strings' ),
		esc_html__( 'Tutorials', 'cart-product-strings' ),
		'manage_options',
		'cart-product-feed-tutorials-page',
		'excpf_cart_product_feed_tutorials_page'
	);
}

	add_action( 'admin_menu', 'excpf_cart_product_admin_menu' );
	add_action( 'cpf_init_pageview', 'excpf_cart_product_feed_admin_page_action' );
	add_action( 'wp_enqueue_scripts', 'wpb_adding_scripts' );

function wpd_adding_scripts() {

}

function excpf_cart_product_feed_admin_page() {

	require_once 'cart-product-wpincludes.php';
	require_once 'core/classes/dialoglicensekey.php';
	include_once 'core/classes/dialogfeedpage.php';
	require_once 'core/feeds/basicfeed.php';

	global $pfcore;
	$pfcore->trigger( 'cpf_init_feeds' );

	do_action( 'cpf_init_pageview' );
}

	// include_once('cart-product-version-check.php');
	/**
	 * Create news feed page
	 */
function excpf_cart_product_feed_admin_page_action() {

	echo "<div class='wrap'>";
	// echo  "<div class='cpf-header'>";
	echo '<h2>Create Product Feed';
	$url = site_url() . '/wp-admin/admin.php?page=cart-product-feed-manage-page';
	echo '<input style="margin-top:12px;" type="button" class="add-new-h2" onclick="document.location=\'' . esc_url( $url ) . '\';" value="' . esc_html__( 'Manage Feeds', 'cart-product-strings' ) . '" />
    </h2>';
	// echo    '</div>';
	// prints logo/links header info: also version number/check
	excpf_print_info();
	// prints navigation bar
	excpf_render_navigation( $url );

	$action         = '';
	$source_feed_id = - 1;
	$feed_type      = - 1;

	$message2    = null;
	$icon_image2 = plugins_url( '/', __FILE__ ) . '/images/BuyLicenseButton.png';

	// check action
	if ( isset( $_POST['action'] ) ) {
		$action = sanitize_text_field( wp_unslash( $_POST['action'] ) );
	}
	if ( isset( $_GET['action'] ) ) {
		$action = sanitize_text_field( wp_unslash( $_GET['action'] ) );
	}
	$feed_identifier = null;
	switch ( $action ) {
		case 'update_license':
			// I think this is AJAX only now -K
			// No... it is still used (2014/08/25) -K
			if ( isset( $_POST['license_key'] ) ) {
				$licence_key = sanitize_text_field( wp_unslash( $_POST['license_key'] ) );
				if ( $licence_key != '' ) {
					update_option( 'cp_licensekey', $licence_key );
				}
			}
			break;
		case 'reset_attributes':
			// I don't think this is used -K
			global $wpdb, $woocommerce;
			$attr_table = $wpdb->prefix . 'woocommerce_attribute_taxonomies';
			$attributes = $wpdb->get_results( $wpdb->prepare( 'SELECT attribute_name FROM `%1s` WHERE 1', array( $attr_table ) ) );
			foreach ( $attributes as $attr ) {
				delete_option( $attr->attribute_name );
			}
			break;
		case 'edit':
			$action          = '';
			$source_feed_id  = isset( $_GET['id'] ) ? sanitize_text_field( wp_unslash( $_GET['id'] ) ) : '';
			$feed_type       = isset( $_GET['feed_type'] ) ? sanitize_text_field( wp_unslash( $_GET['feed_type'] ) ) : '';
			$feed_identifier = array_key_exists( 'identifier', $_GET ) ? sanitize_text_field( wp_unslash( $_GET['identifier'] ) ) : null;
			break;
	}

	if ( isset( $action ) && ( strlen( $action ) > 0 ) ) {
		echo "<script> window.location.assign( '" . esc_url( admin_url() ) . "admin.php?page=cart-product-feed-admin' );</script>";
	}

	if ( isset( $_GET['debug'] ) ) {
		$debug = sanitize_text_field( wp_unslash( $_GET['debug'] ) );
		if ( $debug === 'phpinfo' ) {
			phpinfo( INFO_GENERAL + INFO_CONFIGURATION + INFO_MODULES );

			return;
		}
		if ( $debug === 'reg' ) {
			echo "<pre>\r\n";
			new EXCPF_PLicense( true );
			echo "</pre>\r\n";
		}
	}

	// Get Variables from storage ( retrieve from wherever it's stored - DB, file, etc... )

	$reg = new EXCPF_PLicense();
	global $wpdb;
	// Main content
	echo '
	<script type="text/javascript">
    jQuery( document ).ready( function( $ ) {
        ajaxhost = "' . esc_url( plugins_url( '/', __FILE__ ) ) . '";
        jQuery( "#selectFeedType" ).val( "&nbsp;" );
        doFetchLocalCategories();
        doFetchLocalCategories_custom();
        feed_id = ' . esc_html( $source_feed_id ) . ';
        window.feed_type = ' . esc_html( $feed_type ) . ' ;
        identifier = "' . esc_html( $feed_identifier ) . '";
        if(identifier){
            window.feedUniqueIdentifier = identifier;
        }
        if(feed_id > 0  && feed_type == 1){
            fetchSavedSelectedProducts(identifier,true);
            window.edit = true;
        }else{
            window.edit = false;
        }
        /*jQuery(".myselect").select2();*/
    });
    </script>';

	// WordPress Header ( May contain a message )

	global $message;
	$installtion_date            = get_option( 'cart-product-feed-installation-date' );
	$add_days                    = 4;
	$fourth_date_of_installation = date( 'Y-m-d', strtotime( $installtion_date . ' +' . $add_days . ' days' ) );
	$now                         = date( 'Y-m-d' );
	if ( $now == $fourth_date_of_installation ) {
		$message = 'Are you stuck on feed setup? We will create a complimentary feed according to your needs. Contact us for more details. <a target=\'_blank\' href = \'http://www.exportfeed.com/contact/\'>exportfeed.com</a>';
	}

	if ( strlen( $message ) > 0 && strlen( $reg->error_message ) > 0 ) {
		$message .= '<br>';
	} //insert break after local message (if present)
	$message .= $reg->error_message;
	if ( strlen( $message ) > 0 ) {
		// echo '<div id="setting-error-settings_updated" class="error settings-error">'
		echo '<div id="setting-error-settings_updated" class="notice notice-error">
			  <p>' . wp_kses(
			$message,
			array(
				'div',
				'a' => array(
					'href'   => array(),
					'target' => array(),
				),
				'p',
			)
		) . '</p>
			  </div>';
	}

	if ( $source_feed_id == - 1 ) {
		$wpdb->query( "TRUNCATE {$wpdb->prefix}cpf_custom_products" );
		// Page Header
		echo wp_kses( EXCPF_PFeedPageDialogs::pageHeader(), excpf_kses_criteria() );
		// Page Body
		echo wp_kses( EXCPF_PFeedPageDialogs::pageBody(), excpf_kses_criteria() );

	} else {
		require_once dirname( __FILE__ ) . '/core/classes/dialogeditfeed.php';
		echo wp_kses( EXCPF_PEditFeedDialog::pageBody( $source_feed_id, $feed_type ), excpf_kses_criteria() );

	}
}

	/**
	 * Display the manage feed page
	 */

	add_action( 'cpf_init_pageview_manage', 'excpf_cart_product_feed_manage_page_action' );
	add_action( 'cpf_init_pageview_tutorials', 'excpf_cart_product_feed_tutorials_page_action' );

function excpf_cart_product_feed_manage_page() {

	require_once 'cart-product-wpincludes.php';
	require_once 'core/classes/dialoglicensekey.php';
	include_once 'core/classes/dialogfeedpage.php';

	global $pfcore;
	$pfcore->trigger( 'cpf_init_feeds' );

	do_action( 'cpf_init_pageview_manage' );

}

function excpf_cart_product_feed_tutorials_page() {
	do_action( 'cpf_init_pageview_tutorials' );
}
function excpf_cart_product_feed_tutorials_page_action() {

	echo "<div class='wrap'>";
	$_GET['tab'] = 'tutorials';
	excpf_print_info();
	excpf_render_navigation();
	$path = plugin_dir_path( __FILE__ ) . 'views/tutorials-page.php';
	require_once $path;
	$view_obj = new EXCPF_View();
	$view_obj->tutorial_page_view();

	echo '</div>';
}

function excpf_cart_product_feed_manage_page_action() {

	$reg         = new EXCPF_PLicense();
	$_GET['tab'] = 'managefeed';
	require_once 'cart-product-manage-feeds.php';

}

	/*
	* Custom widget for admin dashboard
	* Created by: Manoj
	* Created date: 30 January, 2018
	*/
	add_action( 'wp_dashboard_setup', 'excpf_exportfeed_dashboard_widgets' );

function excpf_exportfeed_dashboard_widgets() {
	global $wpdb;
	$showPlugin = true;

	$table_name = $wpdb->prefix . 'cp_feeds';
	$result     = $wpdb->get_results( $wpdb->prepare( 'SELECT COUNT(id) as feed_count FROM `%1s`', "{$wpdb->prefix}cp_feeds" ) );

	if ( is_array( $result ) && $result[0]->feed_count > 0 ) {
		$showPlugin = false;
	} else {
		$table_name = $wpdb->prefix . 'cpf_custom_products';
		$result     = $wpdb->get_results( $wpdb->prepare( 'SELECT COUNT(id) as feed_count FROM `%1s`', "{$wpdb->prefix}cp_feeds" ) );
		if ( is_array( $result ) && $result[0]->feed_count > 0 ) {
			$showPlugin = false;
		}
	}

	if ( $showPlugin ) {
		global $wp_meta_boxes;
		wp_add_dashboard_widget( 'custom_help_widget', 'ExportFeed - Add more sales channels', 'excpf_custom_dashboard_help' );
	}

}

function excpf_custom_dashboard_help() {
	$create_feed_url = admin_url() . 'admin.php?page=cart-product-feed-admin';
	$display         = '<a class="button button-primary" href="' . $create_feed_url . '" target="_blank">Create your First Feed</a>';
	$imgUrl          = plugin_dir_url( __FILE__ ) . 'images/exf-sm-logo.png';
	echo '
		<div style="margin-bottom: 6px;">
		<img src="' . esc_url( $imgUrl ) . '">
		</div>';

	echo '
		<h3>Welcome to ExportFeed!</h3> 
		<p>Don\'t limit your product sales to your site only. Add more sales channels like Google Shopping, Amazon, eBay & Etsy. </p>
		<p>' . wp_kses(
		$display,
		array(
			'div',
			'a'      => array(
				'href'   => array(),
				'target' => array(),
				'class'  => array(),
			),
			'p',
			'button' => array(
				'class' => array(),
			),
		)
	) . '</p>
		<p>Need help? <a href="http://www.exportfeed.com/faq/" target="_blank">Read our help section <span class="dashicons dashicons-external"></span></a></p>
		<hr>
		<p><strong>Still confused? </strong><a href="http://www.exportfeed.com/contact/" target="_blank">Request for Free feed setup <span class="dashicons dashicons-external"></span></a></p>';
}

	// Uncomment the following if you need to show create or manage feed link dynamically
	/*
	function excpf_custom_dashboard_help() {
	global $wpdb;
	$showPlugin = true;
	$create_feed_url = admin_url() . 'admin.php?page=cart-product-feed-admin';
	$manage_feed_url = admin_url() . 'admin.php?page=cart-product-feed-manage-page';

	$table_name = $wpdb->prefix . "cp_feeds";
	$result = $wpdb->get_results("SELECT COUNT(id) as feed_count FROM $table_name");

	if(is_array($result) && $result[0]->feed_count > 0) {
		$showPlugin = false;
	} else {
		$table_name = $wpdb->prefix . "cpf_custom_products";
		$result = $wpdb->get_results("SELECT COUNT(id) as feed_count FROM $table_name");
		if(is_array($result) && $result[0]->feed_count > 0) {
			$showPlugin = false;
		}
	}

	if($showPlugin) {
		$display = '<a href="' . $create_feed_url . '" target="_blank">creating your first feed</a>';
	} else {
		$display = '<a href="' . $manage_feed_url . '" target="_blank">managing your feeds</a>';
	}

	echo '<h3>Welcome to ExportFeed!</h3>
			<p><strong>Start ' . $display .'.</strong></p>
			<p>Need help? <a href="http://www.exportfeed.com/faq/" target="_blank">Read our help section here</a>.</p>
			<p>Still confused? Request for <a href="http://www.exportfeed.com/contact/" target="_blank">free feed setup</a>';
	}*/
	// End of custom widget

	/*
	* Custom widget for admin pages
	* Created by: Manoj
	* Created date: 30 March, 2018
	*/

function excpf_exportfeed_admin_widget() {
	global $wpdb;
	$showPlugin = true;

	$notCreateFeedPage = ! stripos( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), 'cart-product-feed-admin' );

	$create_feed_url = admin_url() . 'admin.php?page=cart-product-feed-admin';

	$imgUrl = plugin_dir_url( __FILE__ ) . 'images/exf-sm-logo.png';

	$table_name = $wpdb->prefix . 'cp_feeds';
	$result     = $wpdb->get_results( $wpdb->prepare( 'SELECT COUNT(id) as feed_count FROM `%1s`', $table_name ) );

	if ( is_array( $result ) && $result[0]->feed_count > 0 ) {
		$showPlugin = false;
	} else {
		$table_name = $wpdb->prefix . 'cpf_custom_products';
		$result     = $wpdb->get_results( $wpdb->prepare( 'SELECT COUNT(id) as feed_count FROM  `%1s`', $table_name ) );
		if ( is_array( $result ) && $result[0]->feed_count > 0 ) {
			$showPlugin = false;
		}
	}

	$display = '<a href="' . $create_feed_url . '">Click here to create your first feed now.</a>';

	// Show on all admin pages only if no feed has been created
	// 'admin.php' === $pagenow (global $pagenow)

	if ( $showPlugin && $notCreateFeedPage ) {
		echo '<div class="notice notice-info is-dismissible">'
		. '<p>'
		. '<img style="vertical-align:top;" height=20 src="' . esc_url( $imgUrl ) . '">'
		. '<span style="vertical-align:bottom; margin-left:12px" >'
		. 'Howdy! Ready to sell through high-performing merchants? '
		. wp_kses(
			$display,
			array(
				'div',
				'a' => array(
					'href'   => array(),
					'target' => array(),
				),
				'p',
			)
		)
		. '</span></p>'
		. '</div>';
	}
}

add_action( 'admin_notices', 'excpf_exportfeed_admin_widget' );
