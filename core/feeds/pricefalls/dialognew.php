<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
/********************************************************************
 * Version 2.0
 * Front Page Dialog for GoogleFeed
 * Copyright 2014 Purple Turtle Productions. All rights reserved.
 * license    GNU General Public License version 3 or later; see GPLv3.txt
 * By: Keneto 2014-05-05
 ********************************************************************/
class EXCPF_PricefallsDlg extends EXCPF_PBaseFeedDialog {


	function __construct() {
		parent::__construct();
		$this->service_name      = 'Pricefalls';
		$this->service_name_long = 'Pricefalls Products TXT Export';
		$this->doc_link          = 'https://www.exportfeed.com/documentation/pricefalls-com-integration-guide/';
	}

	function convert_option( $option ) {
		return strtolower( str_replace( ' ', '_', $option ) );
	}
}
