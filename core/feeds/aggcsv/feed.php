<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
/********************************************************************
 * Version 3
 * A Product List CSV Feed
 * Copyright 2014 Purple Turtle Productions. All rights reserved.
 * license    GNU General Public License version 3 or later; see GPLv3.txt
 * By: Keneto 2014-11
 */

require_once dirname( __FILE__ ) . '/../basicfeed.php';

class EXCPF_PAggCsvFeed extends EXCPF_PAggregateFeed {


	public $shopID = 0;

	function __construct( $saved_feed = null ) {

		parent::__construct();

		$this->needsHeader   = true;
		$this->providerName  = 'AggCsv';
		$this->providerNameL = 'aggcsv';
		$this->fileformat    = 'csv';
		$this->providerType  = 1;

		global $pfcore;
		$loadInitialSettings = 'loadInitialSettings' . $pfcore->callSuffix;
		$this->$loadInitialSettings( $saved_feed );

	}

	function getFeedData( $category, $remote_category, $file_name, $saved_feed = null, $miinto_country_code = null ) {

		$this->logActivity( 'Initializing...' );

		global $message;
		global $pfcore;
		$providers = new EXCPF_PProviderList();

		$this->logActivity( 'Loading paths...' );
		if ( ! $this->checkFolders() ) {
			return;
		}

		$file_url  = EXCPF_PFeedFolder::uploadFolder() . $this->providerName . '/' . $file_name . '.' . $this->fileformat;
		$file_path = EXCPF_PFeedFolder::uploadURL() . $this->providerName . '/' . $file_name . '.' . $this->fileformat;

		// Special (WordPress): where admin is https and site is http, path to wp-uploads works out incorrectly as https
		// we check the content_url() for https... if not present, patch the file_path
		if ( ( $pfcore->cmsName == 'WordPress' ) && ( strpos( $file_path, 'https://' ) !== false ) && ( strpos( content_url(), 'https' ) === false ) ) {
			$file_path = str_replace( 'https://', 'http://', $file_path );
		}

		// Create the Feed
		$this->logActivity( 'Creating feed data' );
		$this->filename     = $file_url;
		$this->productCount = 0;

		$content = 'Place-Holder File, This file will be replaced next refresh, To manually fill this data, go to manage feeds and click [Update Now]';
		file_put_contents( $this->filename, $content );

		$this->logActivity( 'Updating Feed List' );
		EXCPF_PFeedActivityLog::updateFeedList( $category, $remote_category, $file_name, $file_path, $this->providerName, $this->productCount );

		// Save the feedlist
		$id = EXCPF_PFeedActivityLog::feedDataToID( $file_name, $this->providerName );
		$pfcore->settingSet( 'cpf_aggrfeedlist_' . $id, implode( ',', $this->feed_list ) );

		if ( $this->productCount == 0 ) {
			// $this->message .= '<br>No products returned';
			// return;
		}

		$this->success = true;
	}

	function finalizeAggregateFeed() {
		// $content = '';
		// file_put_contents($this->filename, $content, FILE_APPEND);
		global $pfcore;
		if ( $this->shopID > 0 ) {
			$pfcore->shopID = $this->shopID;
		}

		EXCPF_PFeedActivityLog::updateFeedList( 'n/a', 'n/a', $this->file_name_short, $this->file_url, $this->providerName, $this->productCount );
	}

	function aggregateHeaderWrite( $id, $headerString ) {
		if ( $this->needsHeader && isset( $this->feeds[ $id ] ) ) {
			$savedData = file_get_contents( $this->filename );
			file_put_contents( $this->filename, $headerString . "\r\n" . $savedData );
			$this->needsHeader = false;
		}
	}

	function aggregateProductSave( $id, $product, $product_text ) {
		if ( isset( $this->feeds[ $id ] ) ) {
			file_put_contents( $this->filename, $product_text, FILE_APPEND );
			$this->productCount++;
		}
	}

	function initializeAggregateFeed( $id, $file_name ) {

		parent::initializeAggregateFeed( $id, $file_name );

		// Erase file
		$content = '';
		file_put_contents( $this->filename, $content );

	}

	function loadInitialSettingsW( $saved_feed ) {
	}

	function loadInitialSettingsWe( $saved_feed ) {
	}

}
