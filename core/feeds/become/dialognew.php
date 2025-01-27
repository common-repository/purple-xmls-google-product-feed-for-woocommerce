<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
/********************************************************************
 * Version 3.0
 * Export a Become CSV data feed
 * Copyright 2014 Purple Turtle Productions. All rights reserved.
 * license    GNU General Public License version 3 or later; see GPLv3.txt
 * By: Calv 2015-05-22
 ********************************************************************/
class EXCPF_BecomeDlg extends EXCPF_PBaseFeedDialog {


	function __construct() {
		parent::__construct();
		$this->service_name      = 'Become';
		$this->service_name_long = 'Become Europe CSV Export';
		$this->blockCategoryList = false;
		$this->options           = array();
		$this->doc_link          = 'https://www.exportfeed.com/documentation/become-integration-guide/';
	}

}
