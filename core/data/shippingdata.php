<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
/********************************************************************
 * Version 2.0
 * Shipping Data
 * Copyright 2014 Purple Turtle Productions. All rights reserved.
 * license    GNU General Public License version 3 or later; see GPLv3.txt
 * By:
 ********************************************************************/
// Retrieves user-defined shipping settings and saves into class-local variable
class EXCPF_PShippingData {


	function __construct( $parentfeed ) {
		global $pfcore;
		$loadProc = 'loadShippingData' . $pfcore->callSuffix;
		return $this->$loadProc( $parentfeed );
	}

	function loadShippingDataJ( $parentfeed ) {
	}

	function loadShippingDataJH( $parentfeed ) {
	}

	function loadShippingDataJS( $parentfeed ) {
	}

	function loadShippingDataW( $parentfeed ) {

	}

	function loadShippingDataWe( $parentfeed ) {
	}

}
