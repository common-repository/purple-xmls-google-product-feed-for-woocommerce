<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
/********************************************************************
 * Version 2.0
 * Front Page Dialog for Shopzilla
 * Copyright 2014 Purple Turtle Productions. All rights reserved.
 * license    GNU General Public License version 3 or later; see GPLv3.txt
 * By: Keneto 2014-05-05
 ********************************************************************/
class EXCPF_ShopzillaDlg extends EXCPF_PBaseFeedDialog {


	function __construct() {
		parent::__construct();
		$this->service_name      = 'Shopzilla';
		$this->service_name_long = 'Shopzilla Export';
		$this->options           = array(
			'Manufacturer',
			'Bid',
			'Promotional Code',
		);
		// $this->doc_link = "https://www.exportfeed.com/documentation/";
	}

}
