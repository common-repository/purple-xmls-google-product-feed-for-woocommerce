<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
/********************************************************************
 * Version 2.1
 * A Google Feed
 * Copyright 2014 Purple Turtle Productions. All rights reserved.
 * license    GNU General Public License version 3 or later; see GPLv3.txt
 * By: Keneto 2014-05-08
 * 2014-09 Retired Attribute Mapping v2.0 (Keneto)
 * 2014-11 All required & optional parameters now show
 */

require_once dirname( __FILE__ ) . '/../basicfeed.php';

class EXCPF_PSnapchatFeed extends EXCPF_PCSVFeedEx {

	function __construct() {
		parent::__construct();
		$this->providerName  = 'Snapchat';
		$this->providerNameL = 'snapchat';
		$this->fileformat    = 'txt';
		// $this->fields = array();
		$this->fieldDelimiter = "\t";
		$this->stripHTML      = true;

		// Create some attributes (Mapping 3.0) in the form (title, Google-title, CData, isRequired)
		// Note that isRequired is just to direct the plugin on where on the dialog to display

		// Basic product information
		/* Required Attributes Snapchat */
		$this->addAttributeMapping( 'id', 'id', true, true );
		$this->addAttributeMapping( 'item_group_id', 'item_group_id', false, true );
		$this->addAttributeMapping( 'title', 'title', false, true );
		$this->addAttributeMapping( 'description', 'description', true, true );
		$this->addAttributeMapping( 'link', 'link', true, true );
		$this->addAttributeMapping( 'feature_imgurl', 'image_link', true, true );
		$this->addAttributeMapping( 'stock_status', 'availability', false, true );
		$this->addAttributeMapping( 'condition', 'condition', false, true );
		$this->addAttributeMapping( 'brand', 'brand', true, true );
		$this->addAttributeMapping( 'mpn', 'mpn', true, true );
		$this->addAttributeMapping( 'gtin', 'gtin', true, true );
		$this->addAttributeMapping( 'regular_price', 'price', false, true );

		/* Optional Attributes Snapchat */
		$this->addAttributeMapping( 'age_group', 'age_group', true, true );
		$this->addAttributeMapping( 'color', 'color', true, true );
		$this->addAttributeMapping( 'gender', 'gender', true, true );
		$this->addAttributeMapping( 'current_category', 'google_product_category', true, true );
		$this->addAttributeMapping( 'product_type', 'product_type', true, true );
		$this->addAttributeMapping( 'size', 'size', true, true );
		$this->addAttributeMapping( 'additional_image_link', 'additional_image_link' ); // Links to additional product images separated with comma (",").
		$this->addAttributeMapping( 'sale_price', 'sale_price', false, false );
		$this->addAttributeMapping( 'sale_price_effective_date', 'sale_price_effective_date', true, false );
		$this->addAttributeMapping( 'adult', 'adult', true, true );
		// Custom Label Attributes for Shopping Campaigns
		$this->addAttributeMapping( '', 'g:custom_label_0', true, false );
		$this->addAttributeMapping( '', 'g:custom_label_1', true, false );
		$this->addAttributeMapping( '', 'g:custom_label_2', true, false );
		$this->addAttributeMapping( '', 'g:custom_label_3', true, false );
		$this->addAttributeMapping( '', 'g:custom_label_4', true, false );

		/*Optional Product Metadata (App Install and Deep Link)*/
		$this->addAttributeMapping( 'icon_media_url', 'icon_media_url', true, true );
		$this->addAttributeMapping( 'ios_app_name', 'ios_app_name', true, true );
		$this->addAttributeMapping( 'ios_app_store_id', 'ios_app_store_id', true, true );
		$this->addAttributeMapping( 'ios_url', 'ios_url', true, true );
		$this->addAttributeMapping( 'android_app_name', 'android_app_name', true, true );
		$this->addAttributeMapping( 'android_package', 'android_package', true, true );
		$this->addAttributeMapping( 'android_url', 'android_url', true, true );
		$this->addAttributeMapping( 'mobile_link', 'mobile_link', true, true );

		$this->addRule( 'status_standard', 'statusstandard' ); // 'in stock' or 'out of stock'

	}

	function formatProduct( $product ) {
		global $pfcore;
		if ( strlen( $product->attributes['current_category'] ) <= 5 ) {
			$product->attributes['current_category'] = $product->attributes['current_category'];
		}
		$product->attributes['description'] = trim( preg_replace( '/\t+/', '', $product->attributes['description'] ) );

		$product->attributes['additional_image_link'] = implode( ',', $product->imgurls );
		if ( strlen( $product->attributes['additional_image_link'] ) > 2000 ) {
			$arrays = explode( ',', $product->attributes['additional_image_link'] );
			$images = '';
			$sn     = 0;
			foreach ( $arrays as $key => $value ) {
				/*
				if($sn == 0){
					$images .= $value.',';
				}else{
					$images .= ','.$value;
				}*/
				$nextimageLength = strlen( $images ) + strlen( $value );
				if ( $nextimageLength >= 2000 ) {
					continue;
				}
				$images .= $value . ',';
			}
			$product->attributes['additional_image_link'] = $images;
		}
		// ********************************************************************
		// Prepare the Product Attributes
		// ********************************************************************
		return parent::formatProduct( $product );

	}

}
