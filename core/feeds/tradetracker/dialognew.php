<?php

  /********************************************************************
  Version 2.0
    Front Page Dialog for TradeTrackerFeed
	  Copyright 2014 Purple Turtle Productions. All rights reserved.
		license	GNU General Public License version 3 or later; see GPLv3.txt
	By: Sushma 2017-02-07

  ********************************************************************/

class EXCPF_TRadeTRackerDlg extends EXCPF_PBaseFeedDialog {

	function __construct() {
		parent::__construct();
		$this->service_name = 'TradeTracker';
		$this->service_name_long = 'TradeTracker Products XML Export';
		$this->blockCategoryList = true;
		// $this->doc_link = "https://www.exportfeed.com/documentation/";
	}

	function convert_option($option) {
		return strtolower(str_replace(" ", "_", $option));
	}

}
