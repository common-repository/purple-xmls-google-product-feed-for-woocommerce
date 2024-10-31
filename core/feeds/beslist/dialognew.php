<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
/********************************************************************
 * Version 2.0
 * Front Page Dialog for Beslist
 * Copyright 2014 Purple Turtle Productions. All rights reserved.
 * license    GNU General Public License version 3 or later; see GPLv3.txt
 * By: Calvin 2014-09-09
 ********************************************************************/
class EXCPF_BeslistDlg extends EXCPF_PBaseFeedDialog {


	function __construct() {
		parent::__construct();
		$this->service_name      = 'Beslist';
		$this->service_name_long = 'Beslist XML Export';
		$this->doc_link          = 'https://www.exportfeed.com/documentation/beslist-integration-guide/';
	}

	function convert_option( $option ) {
		return strtolower( str_replace( ' ', '_', $option ) );
	}
}
