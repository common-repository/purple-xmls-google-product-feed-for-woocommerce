<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// Exit if accessed directly
require_once 'core/classes/cron.php';
require_once ABSPATH . 'wp-admin/includes/upgrade.php';
// callback function
function excpf_cart_product_activate_plugin() {

	global $wpdb;
	$activation_date = date( 'Y-m-d' );
	update_option( 'cart-product-feed-installation-date', $activation_date );

	$charset_collate = $wpdb->get_charset_collate();

	$table_name = $wpdb->prefix . 'cp_feeds';
	$sql        = "
          CREATE TABLE $table_name (
          `id` bigint(20) NOT NULL AUTO_INCREMENT,
          `category` varchar(250) NOT NULL,
          `remote_category` TEXT NOT NULL,
          `filename` varchar(250) NOT NULL,
          `url` varchar(500) NOT NULL,
          `type` varchar(50) NOT NULL DEFAULT 'google',
          `own_overrides` int(10),
          `feed_overrides` text,
          `product_count` int,
          `feed_errors` text,
          `feed_title` varchar(250),
          `feed_identifier` varchar(250),
          `feed_type` INT(10) DEFAULT '0',
          `product_details` TEXT DEFAULT NULL,
          `miinto_country_code` VARCHAR(5) DEFAULT NULL,
          `bonanza_feed_id` varchar(50) DEFAULT NULL,
          `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
           PRIMARY KEY (`id`)
    ) $charset_collate";
	dbDelta( $sql );

	$table_name = $wpdb->prefix . 'cpf_custom_products';
	if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) == $table_name ) {
		$sql = "DROP TABLlE $table_name";
	}
	$sql = "
        CREATE TABLE $table_name
        (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `product_title` longtext,
          `category_name` varchar(255) DEFAULT NULL,
          `product_type` varchar(255) DEFAULT NULL,
          `product_attributes` text DEFAULT NULL,
          `product_variation_ids` varchar(255) DEFAULT NULL,
          `remote_category` longtext,
          `category` int(11) DEFAULT NULL,
          `product_id` int(11) DEFAULT NULL,
          `own_overides` int(11) DEFAULT NULL,
          `feed_overides` blob,
          PRIMARY KEY (`id`),
          UNIQUE KEY `cpf_custom` (`category`,`remote_category`(128),`product_id`)
        ) $charset_collate";

	dbDelta( $sql );

	$table_name = $wpdb->prefix . 'cpf_customfeeds';
	$sql        = "CREATE TABLE `$table_name` (
                 `id` int(11) PRIMARY KEY AUTO_INCREMENT NOT NULL,
                  `feed_id` int(11) NOT NULL,
                  `product_id` int(11) NOT NULL,
                  `feed_identifier` varchar(255) NOT NULL,
                  `status` enum('1','0') NOT NULL DEFAULT '0' COMMENT '1=Created',
                  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                  UNIQUE KEY `cpf_customfeeds` (`product_id`,`feed_identifier`(128))
                ) $charset_collate";
	dbDelta( $sql );

	$table_name = $wpdb->prefix . 'cpf_resolved_product_data';
	$sql        = "CREATE TABLE `$table_name` (
         `id` int(11) PRIMARY KEY AUTO_INCREMENT NOT NULL,
          `feed_id` int(11) NOT NULL,
          `product_id` int(11) NOT NULL,
          `attribute` varchar(255) NOT NULL,
          `value` text NOT NULL,
          `error_code` int(11) NOT NULL
        ) $charset_collate";
	dbDelta( $sql );

	$table_name = $wpdb->prefix . 'cpf_feedproducts';
	$sql        = "CREATE TABLE `$table_name` (
         `id` int(11) PRIMARY KEY AUTO_INCREMENT NOT NULL,
          `p_id` int(11) NOT NULL,
          `parent_id` int(11) DEFAULT NULL,
          `sku` varchar(255) DEFAULT NULL,
          `p_name` varchar(255) DEFAULT NULL,
          `error_status` enum('1','0','-1','2') NOT NULL DEFAULT '1' COMMENT '1=successful, -1=fatalerror, 0=warning,2=Resolved',
          `error_code` int(11) NOT NULL,
          `prod_categories` varchar(300) NOT NULL,
          `feed_id` varchar(300) NOT NULL,
          `message` varchar(255) NOT NULL
        ) $charset_collate";
	dbDelta( $sql );

}

function excpf_cart_product_deactivate_plugin() {
	$next_refresh = wp_next_scheduled( 'excpf_update_cartfeeds_hook' );
	if ( $next_refresh ) {
		wp_unschedule_event( $next_refresh, 'excpf_update_cartfeeds_hook' );
	}

}
