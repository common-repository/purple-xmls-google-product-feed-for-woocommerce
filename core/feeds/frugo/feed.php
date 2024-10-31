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

class EXCPF_PFrugoFeed extends EXCPF_PXMLFeed {


	public $productLevelElement = 'Product';
	public $topLevelElement     = 'Products';

	public $locale;

	public $language;

	function __construct() {
		parent::__construct();
		// $this->exclude_parent=true;
		$this->providerName  = 'Frugo';
		$this->providerNameL = 'frugo';
		$this->locale        = get_locale();

		// Create some attributes (Mapping 3.0) in the form (title, Google-title, CData, isRequired)
		// Note that isRequired is just to direct the plugin on where on the dialog to display

		// Basic product information
		$this->addAttributeMapping( 'id', 'ProductId', false, true );
		$this->addAttributeMapping( 'sku', 'SkuId', false, true );
		$this->addAttributeMapping( 'EAN', 'EAN', false, true );
		$this->addAttributeMapping( 'brand', 'Brand', false, true );
		$this->addAttributeMapping( 'brand', 'Manufacturer', false, true );
		$this->addAttributeMapping( 'current_category', 'Category', true, true );
		$this->addAttributeMapping( 'feature_imgurl', 'Imageurl1', true, true );
		$this->addAttributeMapping( 'stock_status', 'StockStatus', true, true );
		$this->addAttributeMapping( 'stock_quantity', 'StockQuantity', true, true );
		// $this->addAttributeMapping('title', 'Title', true, true);
		$this->addAttributeMapping( 'formatted_price', 'Price', false, true );
		$this->addAttributeMapping( 'formatted_description', 'Description', false, true );
		/*
		$this->addAttributeMapping('sale_price', 'NormalPriceWithoutVAT', false, true);
		$this->addAttributeMapping('price_with_vat', 'NormalPriceWithVAT', false, true);
		$this->addAttributeMapping('vat_rate', 'VATRate', false, true);*/
		$this->addAttributeMapping( 'weight', 'PackageWeight', false, true );
		// Tax & Shipping

		// Custom Label Attributes for Shopping Campaigns
		$this->addAttributeMapping( '', 'LeadTime', true, false );

		$this->google_combo_title = false;
		$this->google_exact_title = false;

		// automatic identifier_exists=false function.
		// set google_identifier to false to disable
		$this->google_identifier = true;

		// $this->addAttributeDefault('additional_images', 'none', 'PGoogleAdditionalImages');
		$this->addAttributeDefault( 'tax_country', 'US' );
		$this->addAttributeDefault( 'local_category', 'none', 'EXCPF_PCategoryTree' ); // store's local category tree

		for ( $image_count = 1; $image_count <= 5; $image_count++ ) {
			$this->addAttributeMapping( 'Imageurl' . $image_count, 'Imageurl' . $image_count, false, true );
		}

		// $this->addRule('price_standard', 'pricestandard'); //append currency
		$this->addRule( 'status_standard', 'statusstandard' ); // 'in stock' or 'out of stock'
		$this->addRule( 'price_rounding', 'pricerounding' ); // 2 decimals
		// shipping

		/*
		$this->addRule('weight_unit', 'weightunit');
		$this->addRule('length_unit', 'dimensionunit', array('length'));
		$this->addRule('width_unit', 'dimensionunit', array('width'));
		$this->addRule('height_unit', 'dimensionunit', array('height'));*/

		/*
		$this->addRule('google_exact_title', 'googleexacttitle'); //true disables ucowrds
		$this->addRule('google_combo_title', 'googlecombotitle');*/
	}

	function formatProduct( $product ) {
		// ********************************************************************
		// Prepare the Product Attributes
		// ********************************************************************
		global $pfcore;

		$this->language = explode( '_', $this->locale );
		if ( empty( $product->attributes['country'] ) ) {
			$product->attributes['country'] = $this->language[1];
		}
		if ( strlen( $product->attributes['current_category'] ) <= 5 ) {
			$product->attributes['current_category'] = $product->attributes['current_category'];
		}

		$image_count = 0;
		foreach ( $product->imgurls as $imgurl ) {
			$image_count++;
			if ( $image_count > 5 ) {
				break;
			} else {
				$product->attributes[ 'Imageurl' . $image_count ] = $imgurl;
			}
		}

		if ( ( $product->attributes['stock_quantity'] > 0 || $product->attributes['manage_stock'] == 'no' ) && $product->attributes['stock_status'] == 'out of stock' ) {
			$product->attributes['stock_status'] = 'INSTOCK';
		} elseif ( $product->attributes['stock_status'] == 'in stock' ) {
			$product->attributes['stock_status'] = 'INSTOCK';
		} elseif ( $product->attributes['stock_status'] == 'out of stock' ) {
			$product->attributes['stock_status'] = 'OUTOFSTOCK';
		} else {
			$product->attributes['stock_status'] = 'OUTOFSTOCK';
		}

		// ********************************************************************
		// Google date, ISO 8601 format.
		// Timezone Bug in WordPress: a manual offset, for example UTC+5:00 will show offset of 0
		// Fix: Select specific region, example: Toronto
		// ********************************************************************

		/* If sale price is empty, then it is populated with regular price in frugo */
		if ( empty( $product->attributes['sale_price'] ) ) {
			$product->attributes['sale_price'] = $product->attributes['regular_price'];
		}

		if ( isset( $product->attributes['sale_price_dates_from'] ) && isset( $product->attributes['sale_price_dates_to'] ) ) {
			$product->attributes['sale_price_dates_from'] = $pfcore->localizedDate( 'Y-m-d\TH:iO', $product->attributes['sale_price_dates_from'] );
			$product->attributes['sale_price_dates_to']   = $pfcore->localizedDate( 'Y-m-d\TH:iO', $product->attributes['sale_price_dates_to'] );

			if ( strlen( $product->attributes['sale_price_dates_from'] ) > 0 && strlen( $product->attributes['sale_price_dates_to'] ) > 0 ) {
				$product->attributes['sale_price_effective_date'] = $product->attributes['sale_price_dates_from'] . '/' . $product->attributes['sale_price_dates_to'];
			}
		}

		$product->attributes['formatted_description']  = '';
		$product->attributes['formatted_description'] .= '<Language>' . $this->language[0] . '</Language>';
		$product->attributes['formatted_description'] .= '<Title>' . $product->attributes['title'] . '</Title>';
		$product->attributes['formatted_description'] .= '<Description>' . $product->attributes['description'] . '</Description>';

		$product->attributes['formatted_description_attributes'] = '';
		if ( $product->attributes['is_variation'] ) {
			$product->attributes['id'] = $product->attributes['item_group_id'];
			if ( is_array( $product->attributes['variation_attributes'] ) && count( $product->attributes['variation_attributes'] ) > 0 ) {
				foreach ( $product->attributes['variation_attributes'] as $key => $variation_attribute ) {
					if ( isset( $product->attributes[ $key ] ) ) {
						$product->attributes['formatted_description_attributes'] .= '<Attribute' . ucfirst( $key ) . '>' . $product->attributes[ $key ] . '</Attribute' . ucfirst( $key ) . '>';
					}
				}
			}
		}

		if ( strlen( $product->attributes['formatted_description_attributes'] ) > 3 ) {
			$product->attributes['formatted_description'] .= $product->attributes['formatted_description_attributes'];
		}

		// $product->attributes['formatted_description'] .= '</Description>';

		// $product->attributes['formatted_price'] = '<Price>';
		if ( $product->attributes['regular_price'] == 0 || $product->attributes['regular_price'] == 0.00 ) {
			$product->attributes['regular_price'] = '';
		}

		if ( $product->attributes['sale_price'] == 0 || $product->attributes['sale_price'] == 0.00 ) {
			$product->attributes['sale_price'] = '';
		}

		$product->attributes['formatted_price']  = '<NormalPriceWithoutVAT>' . $product->attributes['regular_price'] . '</NormalPriceWithoutVAT>';
		$product->attributes['formatted_price'] .= '<DiscountPriceWithoutVAT>' . $product->attributes['sale_price'] . '</DiscountPriceWithoutVAT>';
		$product->attributes['formatted_price'] .= '<DiscountPriceStartDate>' . $product->attributes['sale_price_dates_from'] . '</DiscountPriceStartDate>';
		$product->attributes['formatted_price'] .= '<DiscountPriceEndDate>' . $product->attributes['sale_price_dates_to'] . '</DiscountPriceEndDate>';
		if ( $this->include_country == true ) {
			$product->attributes['formatted_price'] .= '<Country>' . $product->attributes['country'] . '</Country>';
		}
		$product->attributes['formatted_price'] .= '<VATRate>' . $product->attributes['vat_rate'] . '</VATRate>';
		// $product->attributes['formatted_price'] .= '</Price>';
		// ********************************************************************
		// Validation checks & Error messages
		// ********************************************************************
		$id_exists_count = 0; // number of identifiers that are set

		// loop through attributes to find the mapped-to attributes for g:brand, g:mpn and g:gtin
		// check if the attribute has a value. If so, increase the count variable.
		/*
		foreach ($this->attributeMappings as $thisAttributeMapping) {
			if ($thisAttributeMapping->mapTo == 'g:brand' || $thisAttributeMapping->mapTo == 'g:mpn' || $thisAttributeMapping->mapTo == 'g:gtin') {
				if (isset($product->attributes[$thisAttributeMapping->attributeName]))
					$id_exists_count++;
			}
		}*/

		$output = '<' . $this->productLevelElement . '>';

		// ********************************************************************
		// Add attributes (Mapping 3.0)
		// ********************************************************************

		foreach ( $this->attributeMappings as $thisAttributeMapping ) {
			if ( $thisAttributeMapping->enabled && ! $thisAttributeMapping->deleted && isset( $product->attributes[ $thisAttributeMapping->attributeName ] ) ) {
				if ( $thisAttributeMapping->attributeName == 'formatted_price' || $thisAttributeMapping->attributeName == 'formatted_description' ) {
					$output .= '<' . $thisAttributeMapping->mapTo . '>' . $product->attributes[ $thisAttributeMapping->attributeName ] . '</' . $thisAttributeMapping->mapTo . '>';
				} /*
				if ($thisAttributeMapping->mapTo == 'Price') {
					$output .= '<' . $thisAttributeMapping->mapTo . '>' . isset($product->attributes[$thisAttributeMapping->attributeName]) ? $product->attributes[$thisAttributeMapping->attributeName] : " " . '</' . $thisAttributeMapping->mapTo . '>';
				}elseif ($thisAttributeMapping->mapTo == 'Description') {
					$output .= '<' . $thisAttributeMapping->mapTo . '>' . isset($product->attributes[$thisAttributeMapping->attributeName]) ? $product->attributes[$thisAttributeMapping->attributeName] : " " . '</' . $thisAttributeMapping->mapTo . '>';
				} */
				else {
					if ( $thisAttributeMapping->attributeName == 'formatted_price' || $thisAttributeMapping->attributeName == 'formatted_description' ) {
						continue;
					}
					$output .= $this->formatLine( $thisAttributeMapping->mapTo, $product->attributes[ $thisAttributeMapping->attributeName ], $thisAttributeMapping->usesCData );
				}
			}
		}

		// ********************************************************************
		// Mapping 3.0 post processing
		// ********************************************************************

		foreach ( $this->attributeDefaults as $thisDefault ) {
			if ( $thisDefault->stage == 3 ) {
				$thisDefault->postProcess( $product, $output );
			}
		}

		$output .= '</' . $this->productLevelElement . '>';

		return $output;

		// return parent::formatProduct($product);
	}

	function getFeedFooter( $file_name, $file_path ) {
		$output = '</Products>';
		return $output;
	}

	function getFeedHeader( $file_name, $file_path ) {
		$output = '<?xml version="1.0" encoding="UTF-8"?><Products>';
		return $output;
	}

}
