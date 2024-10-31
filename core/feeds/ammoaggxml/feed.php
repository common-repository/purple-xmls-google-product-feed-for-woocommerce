<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once dirname( __FILE__ ) . '/../basicfeed.php';

class EXCPF_PAmmoAggXmlFeed extends EXCPF_PAggregateFeed {
	public $shopID = 0;

	function __construct( $saved_feed = null ) {

		parent::__construct();

		$this->providerName  = 'AmmoAggXml';
		$this->providerNameL = 'ammoaggxml';
		$this->fileformat    = 'xml';
		$this->providerType  = 1;
		$this->check         = 0;
		$this->first_time    = 0;

		global $pfcore;
		$loadInitialSettings = 'loadInitialSettings' . $pfcore->callSuffix;
		$this->$loadInitialSettings( $saved_feed );

	}

	function loadInitialSettingsW( $saved_feed ) {
	}

	function getFeedData( $category, $remote_category, $file_name, $saved_feed = null, $miinto_country_code = null, $isupdate = false ) {

		$this->isupdate = $isupdate;
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

		$content = '<?xml version="1.0" encoding="UTF-8" ?>
			<messages>
				<message>Place-Holder File</message>
				<message>This file will be replaced next refresh</message>
				<message>To manually fill this data, go to manage feeds and click "Update Now"</message>
			</messages>
		';
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

	function initializeAggregateFeed( $id, $file_name ) {

		parent::initializeAggregateFeed( $id, $file_name );
//		$this->aggregrator_name = 'AmmoAggXml';


		$content = '<?xml version="1.0" encoding="UTF-8" ?>';

		file_put_contents( $this->filename, $content );

	}

	function aggregateProductSave( $id, $product, $product_text, $ammo_retail_name = null, $check = null, $ammo_first_time = null ) {

		// fwrite($this->fileHandle, $product_text);
		if ( isset( $this->feeds[ $id ] ) ) {
			if ( ! is_null( $ammo_retail_name ) ) {
				if ( $check == 0 && $ammo_first_time == 0 ) {
					file_put_contents( $this->filename, "\n" . '<productlist retailer = "' . $ammo_retail_name . '">', FILE_APPEND );
				}
				$this->check ++;
				$this->first_time ++;
			}

			file_put_contents( $this->filename, $product_text, FILE_APPEND );
			$this->productCount ++;
		}
	}

	function finalizeAggregateFeed() {
		$content = "\n" . '</productlist>' . "\n";
		file_put_contents( $this->filename, $content, FILE_APPEND );
		global $pfcore;
		if ( $this->shopID > 0 ) {
			$pfcore->shopID = $this->shopID;
		}

		EXCPF_PFeedActivityLog::updateFeedList( 'n/a', 'n/a', $this->file_name_short, $this->file_url, $this->providerName, $this->productCount );
	}
}