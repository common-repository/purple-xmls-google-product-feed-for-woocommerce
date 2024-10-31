<?php

/********************************************************************
Version 3.0
A Rakuten Feed
Copyright 2014 Purple Turtle Productions. All rights reserved.
license GNU General Public License version 3 or later; see GPLv3.txt
By: Keneto 2014-07-24
2014-09 Moved to 100% Mapping v3 compliance
Note: Due to camel-case in folder name, the plugin will be unable to find this provider on *nix systems
 ********************************************************************/

require_once dirname( __FILE__ ) . '/../basicfeed.php';

class EXCPF_PRakutenNewSkuFeed extends EXCPF_PCSVFeedEx {

	function __construct() {
		parent::__construct();
		$this->providerName   = 'RakutenNewSku';
		$this->providerNameL  = 'rakutennewsku';
		$this->fileformat     = 'txt';
		$this->fieldDelimiter = "\t";
		$this->fields         = array();
		// $this->fields = array('Seller-id', 'gtin', 'mfg-name', 'mfg-part-number', 'Seller-sku', 'title', 'description', 'main-image', 'additional-images', 'weight', 'category-id', 'product-set-id', 'listing-price');

		$this->addAttributeMapping( 'seller_id', 'seller-id', true, true ); // assigned by Rakuten.com Shopping
		$this->addAttributeMapping( '', 'gtin', true, true ); // upc or ean
		$this->addAttributeMapping( '', 'isbn', true );
		$this->addAttributeMapping( 'brand', 'mfg-name', true, true );
		$this->addAttributeMapping( '', 'mfg-part-number', true, true ); // may selet id or sku
		$this->addAttributeMapping( '', 'asin' );
		$this->addAttributeMapping( 'sku', 'seller-sku', true, true ); // seller-sku must be unique in the feed
		$this->addAttributeMapping( 'title', 'title', true, true );
		$this->addAttributeMapping( 'description', 'description', true, true );
		$this->addAttributeMapping( 'feature_imgurl', 'main-image', true, true );
		$this->addAttributeMapping( 'additional-images', 'additional-images' );
		$this->addAttributeMapping( 'weight', 'weight', true, true );
		$this->addAttributeMapping( '', 'features', true );
		$this->addAttributeMapping( 'price', 'listing-price', true, true );
		$this->addAttributeMapping( 'price', 'msrp', true );
		$this->addAttributeMapping( 'current_category', 'category-id', true );
		$this->addAttributeMapping( '', 'keywords', true ); // separated by |
		$this->addAttributeMapping( 'item_group_id', 'product-set-id' );
		// $this->addAttributeMapping('Material' , 'material');
		// $this->addAttributeMapping('Type' , 'type');

		// Optional
		$this->addAttributeMapping( '', 'AC Type', false, false );
		$this->addAttributeMapping( '', 'Age Range', false, false );
		$this->addAttributeMapping( '', 'Age Segment', false, false );
		$this->addAttributeMapping( '', 'Applicane Color', false );
		$this->addAttributeMapping( '', 'Appliance Configuration', false );
		$this->addAttributeMapping( '', 'Appliance Power', false );
		$this->addAttributeMapping( '', 'Assembled Dimension Height (in)', false );
		$this->addAttributeMapping( '', 'Assembled Dimension Length (in)', false ); // separated by |
		$this->addAttributeMapping( '', 'Assembled Dimension Width (in)', false );
		$this->addAttributeMapping( 'assembled_weight', 'Assembled Weight (lbs)', false );
		$this->addAttributeMapping( '', 'Assembly Level', false );
		$this->addAttributeMapping( '', 'Assembly Required', false, false );
		$this->addAttributeMapping( '', 'Automatic Shut-off', false, false );
		$this->addAttributeMapping( '', 'Bed Frame Size', false, false );
		$this->addAttributeMapping( '', 'Bed Size', false );
		$this->addAttributeMapping( '', 'Beer Glass Type', false );
		$this->addAttributeMapping( '', 'Bottle Count', true );
		$this->addAttributeMapping( '', 'Bowl Type', true );
		$this->addAttributeMapping( '', 'BTU', true ); // separated by |
		$this->addAttributeMapping( '', 'BTU range', true );
		$this->addAttributeMapping( '', 'Burn Time', true );
		$this->addAttributeMapping( '', 'Cabinets & Drawers Type', true );
		$this->addAttributeMapping( '', 'Capacity', true );
		$this->addAttributeMapping( '', 'Chair Type', true );
		$this->addAttributeMapping( '', 'Character / Series Type', true );
		$this->addAttributeMapping( '', 'Clamp Type', true );
		$this->addAttributeMapping( '', 'Cleaning Glove Type', true );
		$this->addAttributeMapping( '', 'Clock Type', true );
		$this->addAttributeMapping( '', 'Coffee Machine Type', true );
		$this->addAttributeMapping( 'color', 'Color', true ); // separated by |
		$this->addAttributeMapping( '', 'Color Class', true );
		$this->addAttributeMapping( 'Material', 'Cookware Material', true );
		$this->addAttributeMapping( '', 'Corkscrew Type', true );
		$this->addAttributeMapping( '', 'Cover Size', true );
		$this->addAttributeMapping( '', 'Coverage Area', true );
		$this->addAttributeMapping( '', 'Cutlery Type', true );
		$this->addAttributeMapping( '', 'Detergent & Cleaner Type', true );
		$this->addAttributeMapping( '', 'Detergent Type', true );
		$this->addAttributeMapping( '', 'Disposable', true );
		$this->addAttributeMapping( '', 'Disposer Horsepower', true );
		$this->addAttributeMapping( '', 'Electric Power Supply', true ); // separated by |
		$this->addAttributeMapping( '', 'Energy Star Compliant', true );
		$this->addAttributeMapping( '', 'Fabric Color', true );
		$this->addAttributeMapping( '', 'Faucet Type', true );
		$this->addAttributeMapping( '', 'Finish', true );
		$this->addAttributeMapping( '', 'Finish Color', true );
		$this->addAttributeMapping( '', 'Firmness', true );
		$this->addAttributeMapping( '', 'Fitted Sheet', true );
		$this->addAttributeMapping( '', 'Flexible', true );
		$this->addAttributeMapping( '', 'Freezer Type', true );
		$this->addAttributeMapping( '', 'Fuel Type', true );
		$this->addAttributeMapping( '', 'Furniture Finish', true ); // separated by |
		$this->addAttributeMapping( '', 'Furniture Material', true );
		$this->addAttributeMapping( '', 'Furniture Style', true );
		$this->addAttributeMapping( '', 'Gender', true );
		$this->addAttributeMapping( '', 'Gift Card Amount', true );
		$this->addAttributeMapping( '', 'Gift Card Amount Range', true );
		$this->addAttributeMapping( '', 'Gift Card Department', true );
		$this->addAttributeMapping( '', 'Gift Card Design', true );
		$this->addAttributeMapping( '', 'Gift Occasion', true );
		$this->addAttributeMapping( '', 'Gift Recipient', true );
		$this->addAttributeMapping( '', 'Glass Type', true );
		$this->addAttributeMapping( '', 'Heater Design', true ); // separated by |
		$this->addAttributeMapping( '', 'Heater Type', true );
		$this->addAttributeMapping( '', 'Ice Maker', true );
		$this->addAttributeMapping( '', 'Ice/Water Dispenser', true );
		$this->addAttributeMapping( '', 'Indoor/Outdoor', true );
		$this->addAttributeMapping( '', 'Lamp Type', true );
		$this->addAttributeMapping( '', 'Legal Notice', true );
		$this->addAttributeMapping( '', 'Length Range', true );
		$this->addAttributeMapping( '', 'Loading Direction', true );
		$this->addAttributeMapping( '', 'Lumber Dimension', true );
		$this->addAttributeMapping( '', 'Lumber Length (feet)', true );
		$this->addAttributeMapping( '', 'Manufacturer Suggested Age', true ); // separated by |
		$this->addAttributeMapping( '', 'Manufacturer Suggested Age Max', true );
		$this->addAttributeMapping( '', 'Mat Type', true );
		$this->addAttributeMapping( '', 'Mattress Type', true );
		$this->addAttributeMapping( '', 'Metal', true );
		$this->addAttributeMapping( '', 'MLB Team', true );
		$this->addAttributeMapping( '', 'MLS Team', true );
		$this->addAttributeMapping( '', 'NASCAR Driver', true );
		$this->addAttributeMapping( '', 'NBA Team', true );
		$this->addAttributeMapping( '', 'NCAA Team', true );
		$this->addAttributeMapping( '', 'NFL Team', true );
		$this->addAttributeMapping( '', 'NHL Team', true ); // separated by |
		$this->addAttributeMapping( '', 'Pan Type', true );
		$this->addAttributeMapping( '', 'Pattern    Pest Control Type', true );
		$this->addAttributeMapping( '', 'Plate Type', true );
		$this->addAttributeMapping( '', 'Power/Fuel Type', true );
		$this->addAttributeMapping( '', 'Remote Control', true );
		$this->addAttributeMapping( '', 'Room', true );
		$this->addAttributeMapping( '', 'Scent', true );
		$this->addAttributeMapping( '', 'Scent Class', true );
		$this->addAttributeMapping( '', 'Sink Type', true );
		$this->addAttributeMapping( 'size', 'Size', true );
		$this->addAttributeMapping( '', 'Sports', true ); // separated by |
		$this->addAttributeMapping( '', 'Sports League', true );
		$this->addAttributeMapping( '', 'Stainless', true );
		$this->addAttributeMapping( '', 'Steamer Type', true );
		$this->addAttributeMapping( '', 'Table Type', true );
		$this->addAttributeMapping( '', 'Tablecloth Shape', true );
		$this->addAttributeMapping( '', 'Theme', true );
		$this->addAttributeMapping( '', 'Thread Count', true );
		$this->addAttributeMapping( '', 'Towel Type', true );
		$this->addAttributeMapping( '', 'Vacuum Cleaner Type', true );
		$this->addAttributeMapping( '', 'Washer & Dryer Type', true );
		$this->addAttributeMapping( '', 'Water Dispenser Function', true ); // separated by |
		$this->addAttributeMapping( '', 'Water Dispenser Type', true );
		$this->addAttributeMapping( '', 'Wax Type', true );
		$this->addAttributeMapping( '', 'Wick Type', true );
		$this->addAttributeMapping( '', 'Wine Cellar Type', true );
		$this->addAttributeMapping( '', 'Wine Rack Type', true );
		$this->addAttributeMapping( '', 'WNBA Team', true );

		// user may add more attributes via mapAttribute attLocal to attributeName
		$this->addAttributeDefault( 'price', 'none', 'EXCPF_PSalePriceIfDefined' );
		$this->addAttributeDefault( 'local_category', 'none', 'EXCPF_PCategoryTree' ); // store's local category tree
		$this->addRule( 'price_rounding', 'pricerounding' ); // 2 decimals
		$this->addRule( 'description', 'description', array( 'max_length=8000', 'strict' ) );
		// $this->addRule( 'csv_standard', 'CSVStandard',array('description') );
		// $this->addRule( 'csv_standard', 'CSVStandard',array('title','100') ); //title char limit
		$this->addRule( 'substr', 'substr', array( 'title', '0', '100', true ) ); // 100 length

	}

	function getFeedHeader( $file_name, $file_path ) {
		// Rakuten header line 1
		foreach ( $this->attributeMappings as $thisMapping ) {
			if ( $thisMapping->enabled && ! $thisMapping->deleted ) {
				$output .= $thisMapping->mapTo . $this->fieldDelimiter;
			}
		}
		return substr( $output, 0, -1 ) . "\r\n";
	}

	function getFeedFooter( $file_name, $file_path ) {
		// Override parent and do nothing
	}

	function formatProduct( $product ) {

		// if product weight is in ounces, convert to lbs
		if ( $this->weight_unit == 'oz' || $this->weight_unit == 'ounces' ) {
			$product_weight_in_lbs         = $product->attributes['weight'] * 0.0625;
			$product->attributes['weight'] = sprintf( '%0.2f', $product_weight_in_lbs );
		}
		// additional-images
		if ( $this->allow_additional_images && ( count( $product->imgurls ) > 0 ) ) {
			$product->attributes['additional-images'] = implode( '|', $product->imgurls );
		}
		// category-id
		$product->attributes['current_category'] = explode( "\t", $product->attributes['current_category'] )[0];
		// seller id
		if ( property_exists( $this, 'seller_id' ) ) {
			$product->attributes['seller_id'] = $this->seller_id;
		}
		// result code notificaitons
		if ( strlen( $product->attributes['seller_id'] ) == 0 ) {
			$this->addErrorMessage( 1000, 'seller-id not configured. Add advanced command: $seller-id = ....', true, $product->attributes['title'] );
			$this->productCount--; // Make sure the parent class knows we failed to make a product
			return '';
		}

		// if (!isset($this->seller_id) && !isset($product->attributes['seller-id'])) {
		// $this->addErrorMessage(1000, 'Seller ID not configured. Need advanced command: $seller-id = ....');
		// $this->addErrorMessage(1001, '*Note: Seller ID is the number in the upper right hand corner of your Rakuten Merchant Tools Page.');
		// $this->productCount--; //Make sure the parent class knows we failed to make a product
		// return '';
		// }
		// Prepare input (New Rakuten SKU Feed)
		// if (isset($this->seller_id))
		// $product->attributes['seller-id'] = $this->seller_id;
		// $product->attributes['gtin'] = $product->attributes['sku'];
		// while (strlen($product->attributes['gtin']) < 12)
		// $product->attributes['gtin'] = '0' . $product->attributes['gtin'];
		// $product->attributes['listing_price'] = sprintf($this->currency_format, $product->attributes['regular_price']);

		return parent::formatProduct( $product );
	}

}
