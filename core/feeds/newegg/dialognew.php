<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
  /********************************************************************
  Version 2.0
	Front Page Dialog for Newegg
	  Copyright 2014 Purple Turtle Productions. All rights reserved.
		license GNU General Public License version 3 or later; see GPLv3.txt
	By: Calv 2014-11-11
   ********************************************************************/

class EXCPF_NeweggDlg extends EXCPF_PBaseFeedDialog {

	function __construct() {
		parent::__construct();
		$this->service_name      = 'Newegg';
		$this->service_name_long = 'Newegg Products CSV Export';
		$this->options           = array();
		$this->doc_link          = 'https://www.exportfeed.com/documentation/newegg-integration-guide/';
	}

}
