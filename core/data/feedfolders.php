<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
/********************************************************************
 * Version 2.0
 * Centralize all those bazillion reference to wp-upload so we can change it
 * more easily
 * Copyright 2014 Purple Turtle Productions. All rights reserved.
 * license    GNU General Public License version 3 or later; see GPLv3.txt
 * By: Keneto 2014-05-23
 * 2014-06-16 Re-Added Joomla Support
 ********************************************************************/
class EXCPF_PFeedFolder {


	/********************************************************************
	 * feedURL is where the client should be sent to generate the new feed
	 * It's unclear if it's still used
	 ********************************************************************/

	public static function feedURL() {
		global $pfcore;
		$feedURL = 'feedURL' . $pfcore->callSuffix;
		return self::$feedURL();
	}

	private static function feedURLW() {
		global $pfcore;
		return $pfcore->siteHost;
	}

	private static function feedURLWe() {
		global $pfcore;
		return $pfcore->siteHost;
	}


	public static function uploadFolder() {
			 global $pfcore;
			$uploadFolder = 'uploadFolder' . $pfcore->callSuffix;
			return EXCPF_PFeedFolder::$uploadFolder();
	}

	private static function uploadFolderW() {
		$upload_dir = wp_upload_dir();
		return $upload_dir['basedir'] . '/cart_product_feeds/';
	}

	private static function uploadFolderWe() {
		$upload_dir = wp_upload_dir();
		return $upload_dir['basedir'] . '/cart_product_feeds/';
	}

	/********************************************************************
	 * uploadRoot is where the plugin should make the file (same as uploadFolder)
	 * but no "cart_product_feeds". Useful for ensuring folder exists
	 ********************************************************************/

	public static function uploadRoot() {
		global $pfcore;
		$uploadRoot = 'uploadRoot' . $pfcore->callSuffix;
		return self::$uploadRoot();
	}
	private static function uploadRootW() {
		$upload_dir = wp_upload_dir();
		return $upload_dir['basedir'];
	}

	private static function uploadRootWe() {
		$upload_dir = wp_upload_dir();
		return $upload_dir['basedir'];
	}

	/********************************************************************
	 * URL we redirect the client to in order for the user to see the feed
	 ********************************************************************/

	public static function uploadURL() {
		global $pfcore;
		$uploadURL = 'uploadURL' . $pfcore->callSuffix;
		return self::$uploadURL();
	}

	private static function uploadURLW() {
		$upload_dir = wp_upload_dir();
		return $upload_dir['baseurl'] . '/cart_product_feeds/';
	}

	private static function uploadURLWe() {
		$upload_dir = wp_upload_dir();
		return $upload_dir['baseurl'] . '/cart_product_feeds/';
	}

}
