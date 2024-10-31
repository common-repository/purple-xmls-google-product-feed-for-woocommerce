<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
/********************************************************************
 * Version 3.0
 * Export a Product List to XML format (Not for any particular destination)
 * Copyright 2014 Purple Turtle Productions. All rights reserved.
 * license    GNU General Public License version 3 or later; see GPLv3.txt
 * By: Calv 2014-11-28
 ********************************************************************/
class EXCPF_kelkooDlg extends EXCPF_PBaseFeedDialog {


	function __construct() {
		parent::__construct();
		$this->service_name      = 'kelkoo';
		$this->service_name_long = 'Kelkoo Product XML Export';
		$this->options           = array();
		$this->doc_link          = 'https://www.exportfeed.com/documentation/kelkoo-guide/';
	}

	function categoryList( $initial_remote_category ) {
		if ( $this->blockCategoryList ) {
			return '';
		} else {
			return '
			  <span class="label" for="categoryDisplayText" >Category : </span>
			  <span><input type="text" name="categoryDisplayText" class="text_big cpf-createpage-input" onclick="atrributedisplay(\'kelkoo\')" id="categoryDisplayText"  onkeyup="doFetchCategory_timed(\'' . $this->service_name . '\',  this.value);" value="' . $initial_remote_category . '" autocomplete="off" placeholder="Start typing category name" /></span>
			  <div id="categoryList" class="categoryList"></div>
			  <input type="hidden" id="remote_category" name="remote_category" value="' . $initial_remote_category . '">';
		}
	}
}
