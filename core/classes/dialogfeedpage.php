<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
/********************************************************************
 * Version 2.1
 * For the main feed page
 * Copyright 2014 Purple Turtle Productions. All rights reserved.
 * license    GNU General Public License version 3 or later; see GPLv3.txt
 * By: Keneto 2014-05-05
 ********************************************************************/
class EXCPF_PFeedPageDialogs {


	public static function pageHeader() {
		define( 'EXCPF_IMAGE_PATH', plugins_url( '/', __FILE__ ) . '../../images/' );
		global $pfcore;

		$gap            = '
			<div style="float:left; width: 50px;">
				&nbsp;
			</div>';
		$style_lic_text = '';
		if ( $pfcore->cmsName == 'WordPress' ) {
			$reg = new EXCPF_PLicense();
			if ( $reg->valid ) {
				$lic            = '<div class="logo-am" style="vertical-align: middle; display: inline-block;">
				<h4 class="icon-margin">Get standalone plugin for</h4>
				<div class="upsell-icon-logo">
				<div class="logo amazon" style="display:inline-block;">
					<div class="amazon">

						<a value="" href="https://www.exportfeed.com/woocommerce-product-feed/woocommerce-product-feeds-on-amazon-seller-central/" target="_blank">

							<img src="' . EXCPF_IMAGE_PATH . 'amazon.png">
						</a>
						<span class="plugin-link"><a href="https://www.exportfeed.com/woocommerce-product-feed/woocommerce-product-feeds-on-amazon-seller-central/" target="_blank">Get Amazon plugin</a></span>
						<span class="plugin-desc">Manage bulk products + order & inventory sync</span>
					</div> 
				</div>
				<div class="logo ebay" style="display:inline-block;">
					<div class="ebay">
						<a value="" href="https://www.exportfeed.com/woocommerce-product-feed/send-woocommerce-data-feeds-to-ebay-seller/" target="_blank">

							<img src="' . EXCPF_IMAGE_PATH . 'ebay.png">
						</a>
						<span class="plugin-link"><a href="https://www.exportfeed.com/woocommerce-product-feed/send-woocommerce-data-feeds-to-ebay-seller/" target="_blank">Get eBay plugin</a></span>
						<span class="plugin-desc">Bulk upload products and variations to eBay</span>
					</div> 
				</div>

				<div class="logo etsy" style="display:inline-block;">

					<div class="etsy">
						<a value="" href="https://www.exportfeed.com/woocommerce-product-feed/woocommerce-product-feeds-to-etsy/" target="_blank">

							<img src="' . EXCPF_IMAGE_PATH . '/etsy.png">
					</a>
					<span class="plugin-link"><a href="https://www.exportfeed.com/woocommerce-product-feed/woocommerce-product-feeds-to-etsy/" target="_blank">Get Etsy plugin</a></span>
					<span class="plugin-desc">Bulk products upload with multiple images</span>
					</div> 
				</div>
				</div>
				
				<div class="clear"></div>
				
			</div>';
				$style_lic_text = 'display:none';
			} else {
				$lic = EXCPF_PLicenseKeyDialog::small_registration_dialog( '' );
			}
		} else {
			$lic = '';
		}
		$providers = new EXCPF_PProviderList();
		if ( isset( $_GET['page'] ) && sanitize_text_field( wp_unslash( $_GET['page'] ) ) == 'eBay_settings_tabs' ) {
			$style = 'display : none';
		} else {
			$style = 'display : inline-block;';
		}
		$output = '
			<div class="postbox" style="width:100%;">
			
				<div class="inside-export-target upsell-section">
					<div class="select-merchant-cpf-dropdown" style = "' . $style . '">
						<h4>Select Merchant Type</h4>
						<select id="selectFeedType" onchange="doSelectFeed(this.value);">
						<option></option>' .
						 $providers->asOptionList() . '
						</select>
						<br>
						<ul class="subsubsub" >
						<li><a target="_blank" href= "http://www.exportfeed.com/supported-merchants/" class="support-channel-list">List of our supported Merchants</a></li>
					</div>				
					' . $lic . '
				</div>
				
			</ul>
			</div>
			<div style="display: none;" id="ajax-loader-cat-import" ><span id="gif-message-span"></span></div>
			<div class="clear"></div>';

		return $output;

	}

	public static function pageBody() {
		$output  = '<div id="feedPageBody" class="cpf-pagebody-holder-p1" style="width: 100%;float: left;display:none;">';
		$output .= '<div class="inside export-target">';
		$output .= '<h4>Select a merchant type.</h4>';
		$output .= '<hr /></div></div>';
		return $output;
	}

}


