<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
/********************************************************************
 * Version 2.1
 * A Product List Raw Feed
 * Copyright 2014 Purple Turtle Productions. All rights reserved.
 * license    GNU General Public License version 3 or later; see GPLv3.txt
 * By: Keneto 2015-04
 */

require_once dirname( __FILE__ ) . '/../basicfeed.php';

class EXCPF_PProductlistrawFeed extends EXCPF_PBasicFeed {


	function __construct() {

		parent::__construct();
		$this->providerName  = 'Productlistraw';
		$this->providerNameL = 'productlistraw';
		$this->fileformat    = 'txt';

	}

	function formatProduct( $product ) {
	  /*****************************************************************/
      /*                  If all tag is needed                         */
      /*****************************************************************/

		if ( isset( $product->attributes['tag'] ) ) {
			 $tags  = null;
			 $terms = get_the_terms( $product->id, 'product_tag' );
			foreach ( $terms as $key => $value ) {
				if ( $tags !== null ) {
					$tags .= ',' . $value->name;
				} else {
					$tags = $value->name;
				}
			}
			 $product->attributes['tag'] = $tags;
		}

		/******************************************************************/

		// Images now soft-coded
		foreach ( $product->imgurls as $image_count => $imgurl ) {
			$product->attributes[ 'additional_image_link' . $image_count ] = $imgurl;
			if ( $image_count > 9 ) {
				break;
			}
		}

		$result = '
//********************************************************************
' . $product->attributes['title'] . '
//********************************************************************';

		foreach ( $product->attributes as $key => $value ) {
			if ( gettype( $value ) != 'array' ) {
				$result .= '
' . $key . ': ' . $value;
			}
		}

		return $result;

	}

}
