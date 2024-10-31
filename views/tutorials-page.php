<?php

class EXCPF_View {

	public function tutorial_page_view() {
		// require_once '/core/data/productlist.php';
		// $providers = new AMWSCP_PProviderList();
		require_once plugin_dir_path( __FILE__ ) . '../core/classes/providerlist.php';
		$providers   = new EXCPF_PProviderList();
		$embed_code  = wp_oembed_get( 'https://www.youtube.com/watch?v=QEHoUtlDN54&feature=youtu.be' );
		$embed_code1 = wp_oembed_get( 'https://www.youtube.com/watch?v=loeJuYLdVvQ&feature=youtu.be' );

		$selectOption = '';
		$arrayNeed    = array();
		foreach ( $providers->items as $key => $value ) {
			  $arrayNeed[ $value->name ] = $value->name;
			  $selectOption             .= '<option value="' . $value->name . '">' . $value->prettyName . '</option>';
		}

		/*
		incase of need
		<li>Webgains : <a href="https://www.exportfeed.com/documentation/webgains-integration-guide/">Merchant integration guide</a>
		: <a href="https://www.exportfeed.com/documentation/instructions-for-trial-and-new-customers/">Feed Creation Guide </a></li>
		<li>Avantlink : <a href="https://www.exportfeed.com/documentation/avantlink-integration-guide/">Merchant integration guide</a>
		: <a href="https://www.exportfeed.com/documentation/instructions-for-trial-and-new-customers/">Feed Creation Guide </a></li>
		<li>Pricefalls : <a href="https://www.exportfeed.com/documentation/pricefalls-com-integration-guide/">Merchant integration guide</a>
		: <a href="https://www.exportfeed.com/documentation/instructions-for-trial-and-new-customers/">Feed Creation Guide </a></li>
		<li>PriceGrabber : <a href="https://www.exportfeed.com/documentation/pricegrabber-com-integration-guide/">Merchant integration guide</a>
		: <a href="https://www.exportfeed.com/documentation/instructions-for-trial-and-new-customers/">Feed Creation Guide </a></li>*/

		$output = '
    <div class="tutorial-div">
         
<div id="default_div" class="cpf_tutorials_page">
   <div class="cpf_google_merchant_tutorials">
    <h2>Some of the popular merchants and the links to help you get information about how  to integrate and start creating feeds for them</h2>
    </div>

   <ul style="display: block;
    list-style-type: disc;
    line-height: 25px;
    margin-top: 1em;
    margin-bottom: 1 em;
    margin-left: 0;
    margin-right: 0;
    padding-left: 40px;">
      <li>Google Shopping : <a target="_blank" href="https://www.exportfeed.com/documentation/google-merchant-shopping-product-upload/">Merchant integration guide</a>  |  <a target="_blank" href="https://www.exportfeed.com/documentation/instructions-for-trial-and-new-customers/">Feed Creation Guide </a></li>
     <li>Amazon : <a target="_blank" href="https://www.exportfeed.com/documentation/amazon-seller-central-product-guide/">Merchant integration guide</a>  |  <a target="_blank" href="https://www.exportfeed.com/documentation/amazon-feed-installation-feed-creation-manual/">Feed Creation Guide </a></li>
     <li>eBay : <a target="_blank" href="http://www.exportfeed.com/documentation/ebay-seller-guide-2/">Merchant integration guide</a> 
      | <a target="_blank" href="https://www.exportfeed.com/documentation/instructions-for-trial-and-new-customers/">Feed Creation Guide </a></li>
     <li>Facebook : <a target="_blank" href="https://www.exportfeed.com/documentation/facebook-dynamic-product-ads/">Merchant integration guide</a> 
      | <a target="_blank" href="https://www.exportfeed.com/documentation/instructions-for-trial-and-new-customers/">Feed Creation Guide </a></li>
     <li>Bing : <a target="_blank" href="https://www.exportfeed.com/documentation/bing-product-ads-guide/">Merchant integration guide</a> 
      | <a target="_blank" href="https://www.exportfeed.com/documentation/instructions-for-trial-and-new-customers/">Feed Creation Guide </a></li>
     <li>Etsy : <a target="_blank" href="https://www.exportfeed.com/documentation/etsy-feed-installation-feed-creation/">Merchant integration guide</a> 
      | <a target="_blank" href="https://www.exportfeed.com/documentation/instructions-for-trial-and-new-customers/">Feed Creation Guide </a></li>
     <li>Houzz : <a target="_blank" href="https://www.exportfeed.com/documentation/houzz-export-guide/">Merchant integration guide</a> 
      | <a target="_blank" href="https://www.exportfeed.com/documentation/instructions-for-trial-and-new-customers/">Feed Creation Guide </a></li>
     <li>ShareASale : <a target="_blank" href="https://www.exportfeed.com/documentation/shareasale-integration-guide/">Merchant integration guide</a> 
      | <a target="_blank" href="https://www.exportfeed.com/documentation/instructions-for-trial-and-new-customers/">Feed Creation Guide </a></li>
     <li>Bonanza : <a target="_blank" href="https://www.exportfeed.com/documentation/bonanza/">Merchant integration guide</a>
      | <a target="_blank" href="https://www.exportfeed.com/documentation/instructions-for-trial-and-new-customers/">Feed Creation Guide </a></li>
     <li>Rakuten : <a target="_blank" href="https://www.exportfeed.com/documentation/rakuten/">Merchant integration guide</a>
      | <a target="_blank" href="https://www.exportfeed.com/documentation/instructions-for-trial-and-new-customers/">Feed Creation Guide </a></li>
    </ul>
   
   
</div>

<div style="display:none;" id="for_amzon"  class="cpf_tutorials_page" style="margin-top: 59px;">
                <div id="for_amzon" class="cpf_google_merchant_tutorials">
                    <h2 id="tutorial_title" > ExportFeed : Amazon Marketplace Feed Creation Tutorials</h2>
                </div>' . $embed_code . '</div>
 <div style="display:none;" id="for_google" class="cpf_tutorials_page" style="margin-top: 59px;">
                <div id="for_google" class="cpf_google_merchant_tutorials">
                    <h2 id="tutorial_title" > ExportFeed : Google Feed Creation Tutorials</h2>
                </div>' . $embed_code1 . '</div>
 <div style="display:none;" id="for_other" class="cpf_tutorials_page" style="margin-top: 59px;">
                <div id="for_other" class="cpf_google_merchant_tutorials">
                    <h2 id="tutorial_title_other" ></h2>
                </div><div id="doc_link">Video is not available. <span id="inner_doc_link"></span> is the detail documentation for it.</div></div>


<div class="clear"></div>
<div class="cpf_tutorials_page" style="margin-top: 59px;">
<p><b>Was this helpful ? For further query, please contact us <a target="_blank" href="http://www.exportfeed.com/contact/">here</a></b></p>
</div>

</div> ';
		echo wp_kses( $output, excpf_kses_criteria() );
	}
}
$view = new EXCPF_View();

