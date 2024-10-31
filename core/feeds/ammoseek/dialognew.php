<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
/********************************************************************
 * Version 2.0
 * Front Page Dialog for Ammo Seek
 * Copyright 2014 Purple Turtle Productions. All rights reserved.
 * license    GNU General Public License version 3 or later; see GPLv3.txt
 * By: Keneto 2014-08-19
 ********************************************************************/
class EXCPF_AmmoSeekDlg extends EXCPF_PBaseFeedDialog {


	function __construct() {
		parent::__construct();
		$this->service_name      = 'AmmoSeek';
		$this->service_name_long = 'AmmoSeek.com';
		$this->options           = explode( ',', 'manufacturer,numrounds,caliber,grains,type,shot_size,shell_length,gun' );
		$this->doc_link          = 'https://www.exportfeed.com/documentation/ammoseek-integration-guide/';
	}

	function categoryList( $initial_remote_category ) {
		return '
				<span class="label">Product Type : </span>
				<span><input type="text" name="categoryDisplayText" class="text_big cpf-createpage-input" id="categoryDisplayText"  onkeyup="doFetchCategory_timed(\'' . $this->service_name . '\',  this.value)" value="' . $initial_remote_category . '" autocomplete="off" placeholder="start typing..." /></span>
				<div id="categoryList" class="categoryList"></div>
				<input type="hidden" id="remote_category" name="remote_category" value="' . $initial_remote_category . '">';
	}

}
