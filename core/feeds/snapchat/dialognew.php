<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/********************************************************************
 * Version 2.0
 * Front Page Dialog for Amazon Seller Central
 * license    GNU General Public License version 3 or later; see GPLv3.txt
 * By: Suzan 2019-03
 ********************************************************************/
class EXCPF_SnapchatDlg extends EXCPF_PBaseFeedDialog {


	function __construct() {
		parent::__construct();
		$this->service_name      = 'Snapchat';
		$this->service_name_long = 'Snapchat Products Csv Export';
		$this->doc_link          = 'https://www.exportfeed.com/documentation/google-merchant-shopping-product-upload/';
	}

	function convert_option( $option ) {
		return strtolower( str_replace( ' ', '_', $option ) );
	}

}
