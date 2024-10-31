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

class EXCPF_PZomatoFeed extends EXCPF_PXMLFeed {

	public $post_date;
	function __construct() {
		parent::__construct();
		$this->providerName  = 'Zomato';
		$this->providerNameL = 'zomato';

		$this->addAttributeMapping( 'post_date', 'date', false, true );
		$this->addAttributeMapping( 'title', 'name', false, true );
		$this->addAttributeMapping( 'description', 'description', true, true );
		$this->addAttributeMapping( 'price', 'price', false, true );
		// $this->addAttributeMapping('item_group_id', 'date');

		$this->productLevelElement = 'daily_menu';

	}

	function formatProduct( $product ) {
		$output = '
        <' . $this->productLevelElement . '>';

		// ********************************************************************
		// Add attributes (Mapping 3.0)
		// ********************************************************************

		// $output .= '<date>'.date('d.m.Y' ,strtotime($product->attributes['post_date'])).'</date>';
		/*
		if($product->attributes['post_date'] == '26.12.2017'){
			$output.= '<meal>';
			$output .= '<name>'.$product->attributes['title'].'</name>';
			$output .= '<des>'.$product->attributes['title'].'</des>';
			$output .= '</meal>';
			$output .= '
			</' . $this->productLevelElement . '>';
		}else{

		}*/
		$output .= '<date>' . date( 'd.m.Y', strtotime( $product->attributes['post_date'] ) ) . '</date>';
		$output .= '<meal>';
		foreach ( $this->attributeMappings as $key => $thisAttributeMapping ) {
			if ( $thisAttributeMapping->enabled && ! $thisAttributeMapping->deleted && isset( $product->attributes[ $thisAttributeMapping->attributeName ] ) ) {
				if ( $thisAttributeMapping->attributeName != 'post_date' ) {
					$output .= $this->formatLine( $thisAttributeMapping->mapTo, $product->attributes[ $thisAttributeMapping->attributeName ], $thisAttributeMapping->usesCData );
				}
			}
		}
		$output .= '</meal>';
		$output .= '
            </' . $this->productLevelElement . '>';

		return $output;
		// return parent::formatProduct($product);
	}

	public function makeList( $attribute, $value ) {
		$arr[][ $attribute ] = $value;
		return $arr;
	}

	function getFeedFooter( $file_name, $file_path ) {
		$output = '
  </daily_menu_list>
';
		return $output;
	}

	function getFeedHeader( $file_name, $file_path ) {
		$output = '
  <daily_menu_list>';
		return $output;
	}

}
