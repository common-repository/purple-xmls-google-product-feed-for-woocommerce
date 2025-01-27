<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
  /********************************************************************
  Version 2.0
	Front Page Dialog for GoDataFeed
	  Copyright 2014 Purple Turtle Productions. All rights reserved.
		license GNU General Public License version 3 or later; see GPLv3.txt
	By: Keneto 2014-09
   ********************************************************************/

class EXCPF_GoDataFeedDlg extends EXCPF_PBaseFeedDialog {

	function __construct() {
		parent::__construct();
		$this->service_name      = 'GoDataFeed';
		$this->service_name_long = 'GoDataFeed Product Feed';
		$this->options           = array(
			'brand',
			'keywords',
			'UPC',
			'mpn',
		);
		$this->blockCategoryList = true;
		// $this->doc_link = "https://www.exportfeed.com/documentation/";
	}

}
