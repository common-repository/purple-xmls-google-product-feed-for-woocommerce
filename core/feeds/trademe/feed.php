<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// Exit if accessed directly
/********************************************************************
 * Version 2.1
 * A Trademe Feed
 * Copyright 2014 Purple Turtle Productions. All rights reserved.
 * license    GNU General Public License version 3 or later; see GPLv3.txt
 * By: Sushma 2017-11-14
 * 2017-11 Retired Attribute Mapping v2.0 (Keneto)
 * 2017-11 All required & optional parameters now show
 */

require_once dirname( __FILE__ ) . '/../basicfeed.php';

class EXCPF_PTrademeFeed extends EXCPF_PXMLFeed {

	public function __construct() {
		 parent::__construct();
		$this->providerName        = 'Trademe';
		$this->providerNameL       = 'trademe';
		$this->productLevelElement = 'tm:product';
		$this->topLevelElement     = 'tm:products';

		// Create some attributes (Mapping 3.0) in the form (title, Google-title, CData, isRequired)
		// Note that isRequired is just to direct the plugin on where on the dialog to display

		// Basic product information
		$this->addAttributeMapping( 'sku', 'tm:sku', true, true );
		$this->addAttributeMapping( 'title', 'tm:title', true, true );
		$this->addAttributeMapping( 'subtitle', 'tm:subtitle', false, true );
		$this->addAttributeMapping( 'description', 'tm:description', true, true );
		$this->addAttributeMapping( 'current_category', 'tm:categoryName', true, true );
		$this->addAttributeMapping( 'regular_price', 'tm:buyNowPrice', true, true );
		$this->addAttributeMapping( 'reserve_price', 'tm:reservePrice', true, true );
		$this->addAttributeMapping( 'listing_duration', 'tm:listingDuration', true, true );
		$this->addAttributeMapping( 'shipping_options', 'tm:shippingOptions', true, true );
		$this->addAttributeMapping( 'stock_quantity', 'tm:stockLevel', false, true );

		$this->addAttributeMapping( '', 'tm:allowPickups', true, false ); // integer
		$this->addAttributeMapping( '', 'tm:isGallery', true, false ); // TRUE or FALSE
		$this->addAttributeMapping( '', 'tm:isCombo', true, false ); // TRUE or FALSE

		// $this->addAttributeMapping('', 'g:adwords_grouping', true, false); //deprecated
		// $this->addAttributeMapping('', 'g:adwords_labels', true, false); //deprecated
		$this->addAttributeMapping( '', 'tm:allowBankTransfer', true, false );

		$this->addAttributeMapping( '', 'tm:sendPaymentInstructions', true, false );
		$this->addAttributeMapping( '', 'tm:unit_pricing_base_measure', true, false );
		$this->addAttributeMapping( '', 'tm:imageFileNames', true, false );

		// $this->productLevelElement = 'item';

		$this->addAttributeDefault( 'tax_country', 'US' );
		$this->addAttributeDefault( 'local_category', 'none', 'EXCPF_PCategoryTree' ); // store's local category tree

		$this->addRule( 'price_standard', 'pricestandard' ); // append currency
		$this->addRule( 'status_standard', 'statusstandard' ); // 'in stock' or 'out of stock'
		$this->addRule( 'price_rounding', 'pricerounding' ); // 2 decimals

		$this->addRule( 'google_exact_title', 'googleexacttitle' ); // true disables ucowrds
		$this->addRule( 'google_combo_title', 'googlecombotitle' );

	}

	public function formatProduct( $product ) {
		 global $pfcore;
		return parent::formatProduct( $product );

	}

	public function getFeedFooter( $file_name, $file_path ) {
		$output  = '</tm:supplier>';
		$output .= '</tm:supplier>';
		$output .= '</tm:feed>';

		return $output;
	}

	public function getFeedHeader( $file_name, $file_path ) {
		$output  = '<tm:feed xmlns:tm="http://www.trademe.co.nz/schemas/feeds/newGoods/2.0">';
		$output .= '<tm:suppliers>';
		$output .= '<tm:supplier>';
		$output .= '<tm:memberId>' . $this->member_id . '</tm:memberId>';

		return $output;
	}

}
