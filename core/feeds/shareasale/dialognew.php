<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
/********************************************************************
 * Version 2.0
 * Front Page Dialog for ShareASale Merchant
 * Copyright 2014 Purple Turtle Productions. All rights reserved.
 * license    GNU General Public License version 3 or later; see GPLv3.txt
 * By: Keneto 2014-08
 ********************************************************************/
class EXCPF_ShareASaleDlg extends EXCPF_PBaseFeedDialog {


	function __construct() {
		parent::__construct();
		$this->service_name      = 'ShareASale';
		$this->service_name_long = 'ShareASale Data Feed';
		$this->options           = array(
			'Commission',
			'SubCategory',
			'SearchTerms',
			'Custom 1',
			'Custom 2',
			'Custom 3',
			'Custom 4',
			'Custom 5',
			'Manufacturer',
			'PartNumber',
			'MerchantSubcategory',
			'ISBN',
			'UPC',
		);
		$this->doc_link          = 'https://www.exportfeed.com/documentation/shareasale-integration-guide/';
	}

	public function sas_categories_for_default_feed_creation( $initial_remote_category ) {
		return $this->categoryList( $initial_remote_category, 0 );
	}

	public function sas_sub_categories_for_default_feed_creation( $initial_remote_category ) {
		return $this->subCategoryList( $initial_remote_category, 0 );
	}

	public function sas_categories_for_custom_feed_creation( $initial_remote_category ) {
		return $this->categoryList( $initial_remote_category, 1 );
	}

	public function sas_sub_categories_for_custom_feed_creation( $initial_remote_category ) {
		return $this->subCategoryList( $initial_remote_category, 1 );
	}
	public function categoryList( $initial_remote_category, $feed_type = 0 ) {
		$main_categories = require 'categories.php';
		$optionList      = '<select name="sas_category_list" id="sas_category_list_' . $feed_type . '" onclick="fetch_sas_subcategories(' . $feed_type . ')" style="width: 250px">';
		$optionList     .= '<option value="0">Select Category</option>';
		$categories      = '';
		foreach ( $main_categories as $key => $category ) {
			$selected    = $key == $initial_remote_category ? 'selected' : '';
			$optionList .= '<option value=' . $key . ' ' . $selected . '>' . $category . '</option>';
		}
		$optionList .= '</select>';
		$categories .= '<div class="feed-left-row" id="cpf-shareasale-categorylist_' . $feed_type . '"><span class="label">ShareaSale Merchant Category: </span>';
		$categories .= $optionList;
		$categories .= '</div>';
		return $categories;

	}
	public function subCategoryList( $initial_remote_sub_category, $feed_type = 0 ) {
		$sub_categories  = '';
		$sub_categories .= '<span class="label" > ' . $this->service_name . ' Merchant Sub-Category : </span >';
		if ( $this->edit_feed ) {
			$sub_categories .= '<script>fetch_sas_subcategories(' . $feed_type . ', ' . "'$this->feed_identifier'" . '); jQuery("#shareasale-nav-outer").hide();</script>';
		}
		$sub_categories .= '<span id="cpf-sas-sub-category-list_' . $feed_type . '">Sub-categories will be listed once you select main category.</span >';
		return $sub_categories;
	}
}
