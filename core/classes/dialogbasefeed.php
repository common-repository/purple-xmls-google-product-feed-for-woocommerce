<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// Exit if accessed directly
/********************************************************************
 * Version 2.0
 * Core functionality of a basic feed.
 * Copyright 2014 Purple Turtle Productions. All rights reserved.
 * license    GNU General Public License version 3 or later; see GPLv3.txt
 * By: Keneto 2014-05-05
 * 2014-10 Moved to template (.tpl) format for simplicity (hopefully) -Keneto
 */
require_once dirname( __FILE__ ) . '/../data/productcategories.php';
require_once dirname( __FILE__ ) . '/../data/attributesfound.php';
require_once dirname( __FILE__ ) . '/../data/feedfolders.php';

// echo admin_url().'includes/class-wp-list-table.php';

define( 'EXCPF_IMAGE_PATH', plugins_url( '/', __FILE__ ) . '../../images/' );
class EXCPF_PBaseFeedDialog {

	public $blockCategoryList = false;
	public $options; // Array to be filled by constructor of descendant
	public $service_name      = 'Google'; // Example only
	public $service_name_long = 'Google Products XML Export'; // Example only
	public $feed_config;
	public $edit_feed       = false;
	public $feed_identifier = null;
	public $miintobrandlocalcat;

	public function __construct() {
		$this->options = array();
	}

	public function createDropdown( $thisAttribute, $index ) {
		$found_options = new EXCPF_FoundOptions( $this->service_name, $thisAttribute );
		$output        = '
    <select class="attribute_select" id="attribute_select' . $index . '" onchange="setAttributeOption(\'' . $this->service_name . '\', \'' . $thisAttribute . '\', ' . $index . ')">
      <option value=""></option>';
		foreach ( $this->options as $option ) {
			if ( $option == $found_options->option_value ) {
				$selected = 'selected="selected"';
			} else {
				$selected = '';
			}

			$output .= '<option value="' . $this->convert_option( $option ) . '"' . $selected . '>' . $option . '</option>';
		}
		$output .= '
    </select>';
		return $output;
	}

	public function createDropdownAttr( $FoundAttributes, $defaultValue = '', $mapTo ) {
		$output = '
    <select class="attribute_select" service_name="' . $this->service_name . '"
        mapto="' . $mapTo . '"
        onchange="setAttributeOptionV2(this)" >
      <option value="">Select Attributes</option>
      <option value="(Reset)">(Reset)</option>';
		foreach ( $FoundAttributes->attributes as $attr ) {
			if ( $defaultValue == $attr->attribute_name ) {
				$selected = ' selected="true"';
			} else {
				$selected = '';
			}

			$output .= '<option value="' . $attr->attribute_name . '"' . $selected . '>' . $attr->attribute_name . '</option>';
		}
		$output .= '
        <option value="">--Common attributes--</option>
        <option value="brand">brand</option>
        <option value="description_short">description_short</option>
        <option value="id">id</option>
        <option value="regular_price">regular_price</option>
        <option value="sale_price">sale_price</option>
        <option value="sku">sku</option>
        <option value="tag">tag</option>
        <option value="title">title</option>
        <option value="">--CPF Additional Fields--</option>
        <option value="brand">brand</option>
        <option value="ean">ean</option>
        <option value="mpn">mpn</option>
        <option value="upc">upc</option>
        <option value="description">description</option>
        <option value="description_real">description_real</option>
        <option value="description_real_long">description_real_long</option>
        <option value="">--Dummy attributes--</option>
        <option value="default1">default1</option>
        <option value="default2">default2</option>
        <option value="default3">default3</option>
    </select>';
		return $output;
	}

	public function createDropdownAttrCustom( $FoundAttributes, $defaultValue = '', $mapTo ) {
		$defaultValue = explode( ':', $defaultValue );
		$output       = '
    <select class="attribute_select" service_name="' . $this->service_name . '"
        mapto="' . $mapTo . '"
        onchange="setAttributeOptionV2(this)" >
      <option value="">Select Attributes</option>
      <option value="(Reset)">(Reset)</option>';
		foreach ( $FoundAttributes->attributes as $attr ) {
			if ( $defaultValue == $attr->attribute_name ) {
				$selected = ' selected="true"';} else {
				$selected = '';
				}

				$output .= '<option value="' . $attr->attribute_name . '"' . $selected . '>' . $attr->attribute_name . '</option>';
		}
		$output .= '
        <option value="">--Common attributes--</option>
        <option value="brand">brand</option>
        <option value="description_short">description_short</option>
        <option value="id">id</option>
        <option value="regular_price">regular_price</option>
        <option value="sale_price">sale_price</option>
        <option value="sku">sku</option>
        <option value="tag">tag</option>
        <option value="title">title</option>
        <option value="">--CPF Additional Fields--</option>
        <option value="brand">brand</option>
        <option value="ean">ean</option>
        <option value="mpn">mpn</option>
        <option value="upc">upc</option>
        <option value="description">description</option>
        <option value="">--Dummy attributes--</option>
        <option value="default1">default1</option>
        <option value="default2">default2</option>
        <option value="default3">default3</option>
    </select>';
		return $output;
	}

	public function attributeMappings() {
		global $pfcore;
		$FoundAttributes             = new EXCPF_FoundAttribute();
		$savedAttributes             = $FoundAttributes->attributes;
		$FoundAttributes->attributes = array();
		foreach ( $savedAttributes as $attr ) {
			$FoundAttributes->attributes[] = $attr;
		}

		foreach ( $this->provider->attributeMappings as $thisAttributeMapping ) {
			// if empty mapping, don't add to drop down list
			if ( strlen( trim( $thisAttributeMapping->attributeName ) ) > 0 ) {
				$attr                          = new stdClass();
				$attr->attribute_name          = $thisAttributeMapping->attributeName;
				$FoundAttributes->attributes[] = $attr;
			}
		}
 
		if ( $this->service_name != 'Amazonsc' && $this->service_name != 'kelkoo' ) {?>
			<div class="clear"></div>
			<script type="text/javascript">
				jQuery(document).ready(function () {
					loadCustomFeedSection("<?php echo esc_html( $this->feed_type ); ?>");
				});
			</script>
			<?php
		}
		$style_css = '';
		if ( $this->service_name == 'Amazonsc' || $this->service_name == 'kelkoo' ) {
			$style_css = 'display : none ';
		}
		$default_css = "style='display:none;'";
		$output      = '';
		$output     .= '
                    <div class="feeds_by_category" id="cpf_feeds_by_category" ><div class="cpf_feed_by_category_left">
                        <p>Product attributes of your  are mapped automatically as per ' . $this->service_name . ' requirements. You can manually make changes <span class="cpf-danger" style="color:red;">if you are absolutely sure of what you are doing.</span><br>
                       </p>
                    <div class="attr-div required_">
                        <label style="text-align: center;display: block;" class="attributes-label" id="toggleRequiredAttributes" onclick="toggleRequiredAttributes(this);">Required Attributes</label>
                        <div class="required-attributes" id=\'required-attributes\'>
                        <table>
                            <tr><td>Attribute</td><td width="20"></td><td>' . $this->service_name . ' Attribute</td></tr>';
		foreach ( $this->provider->attributeMappings as $thisAttributeMapping ) {
			if ( $thisAttributeMapping->isRequired ) {
				$output .= '<tr><td>' . $this->createDropdownAttr( $FoundAttributes, $thisAttributeMapping->attributeName, $thisAttributeMapping->mapTo ) . '</td><td></td><td>' . $thisAttributeMapping->mapTo . '</td></tr>';
			}
		}

		$output .= '</table>
                    </div>
                    </div>
                    <div class="attr-div optional_">
              <label style="text-align: center;display: block;" class="attributes-label" id="toggleOptionalAttributes" onclick="toggleOptionalAttributes(this);">Additional Attributes</label>
              <div class="optional-attributes" id=\'optional-attributes\'>
              <table>
              <tr><td>Attribute</td><td width="20"></td><td>' . $this->service_name . ' Attribute</td></tr>';
		foreach ( $this->provider->attributeMappings as $thisAttributeMapping ) {
			if ( ! $thisAttributeMapping->isRequired ) {
				$output .= '<tr><td>' . $this->createDropdownAttr( $FoundAttributes, $thisAttributeMapping->attributeName, $thisAttributeMapping->mapTo ) . '</td><td></td><td>' . $thisAttributeMapping->mapTo . '</td></tr>';
			}
		}

		$output .= '
              </table>
              </div></div></div></div>';

		return $output;
	}

	public function loadCustomFeed() {
		global $pfcore;
		$edit                        = '';
		$disabled                    = '';
		$FoundAttributes             = new EXCPF_FoundAttribute();
		$savedAttributes             = $FoundAttributes->attributes;
		$FoundAttributes->attributes = array();
		foreach ( $savedAttributes as $attr ) {
			$FoundAttributes->attributes[] = $attr;
		}
		$output  = '';
		$output .= '<div class="cpf-custom_feed_generation" id="cpf-custom_feed_generation">
                    <form name = "cpf-custom_feed" id="cpf-custom-feed-form" method="POST">';

		// removed top search bar from table and moved into div
		// removed bottom table header and increased padding in bottom of table
		// changed search type of sku from sku to all
		// added padding to h3 title
		// changed percentages of table header to make it compatible with resizing
		$output .= '<h3 style="padding-left:15px;">Search product(s)</h3>
                    <span onclick="resetSubmitForm();" class="reset_cpf_cpfcustomfeed"><img src="' . EXCPF_IMAGE_PATH . '/reset.png" style="vertical-align:middle;">Reset Search</span>
                            <div class="form-search">
                                <div class="searchrow">
                                    <div class="col-skukey forminp" style="position:relitive;">
                                        <input type="search" id="cpf_sku_filter" name="cpf_sku_filter" placeholder="Search feed by SKU, Title" style="width:100%; height: 30px;">
                                        <div class="cpf-suggestion-box"></div>
                                    </div>
                                    <div class="col-catagory" id="cpf_localcategory_list">
                                    </div>
                                    <div class="col-price">
                                        <select name="cpf_price_filter_option" id="cpf_price_filter_option" style="width:100%; height: 30px;">
                                                <option value="">Select Price Range</option>
                                                <option value="less_than">Less than or equals to</option>
                                                <option value="more_than">Greater than or equals to</option>
                                                <option value="in_between">In Between</option>
                                        </select>
                                        <div id="cpf_price_selection_list_option">
                                            <input type="search" name="cpf_price_filter_less_than" placeholder="Enter Amount" id="cpf_price_filter_less_than" style="display: none; width:100%; height: 30px;">
                                            <input type="search" name="cpf_price_filter_more_than" placeholder="Enter Amount" id="cpf_price_filter_more_than" style="display: none;">
                                            <input type="search" name="cpf_price_filter_in_between_first" placeholder="Enter First Amount" id="cpf_price_filter_in_between_first" style="display: none;">
                                            <input type="search" name="cpf_price_filter_in_between_second" placeholder="Enter Second Amount" id="cpf_price_filter_in_between_second" style="display: none;">
                                        </div>
                                    </div>

                                    <div class="col-search">
                                        <input class="button-primary" title="This will search product list from above information you give and generate the result on search result section below." type="button" value="Search Product" id="submit_data" name="submit_data" onclick="submitForm(' . "'{$this->service_name}'" . ');"/>
                                    </div>
                                </div>
                            </div>

                            <table class="form-table">
                                <thead>
                                    <tr style="background-color: #8A9FAF;" valign="top">
                                        <th style="width:20px";><input type="checkbox" id="cpf_select_all_checkbox" onclick="selectAllProducts(this);"></th>
                                        <th style="width:25%;text-align:left;";>SKU</th>
                                        <th style="width:15%;text-align:left;";>Product Title</th>
                                        <th style="width:15%;text-align:left;";>Category</th>
                                        <th style="width:15%";>Price (' . get_woocommerce_currency() . ') </th>
                                        <th style="width:15%";>Quantity</th>
                                        <th>Merchant Category</th>
                                        <th style="width:10%";></th>
                                    </tr>
                                 </thead>


                                <tbody id="cpf-the-list" class="cpf-table-sortable">
                                    <tr id="no-product-tr">
                                        <td align="center" colspan="8">No Product Searched</td>
                                    </tr>
                                    </tbody>

                            <tfoot>
                                  <th colspan="3" id="bulk-action" style="display:none;">

                                        <span style="display:none;"> <input type="button" id="doaction2" class="button-primary action apply_btn" value="Apply" onclick="cpf_apply_action(this)">
                                        </span>';
		if ( array_key_exists( 'feed_type', $_REQUEST ) && array_key_exists( 'action', $_REQUEST ) ) {
			if ( sanitize_text_field( wp_unslash( $_REQUEST['feed_type'] ) ) == 1 && sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) == 'edit' ) {
				$edit     = true;
				$disabled = 'disabled';
			}
		} else {
			$edit     = false;
			$disabled = '';
		}

		$output .= '<a href="#submitpost"> <span class="move-search-products"><input class="button-primary" type="button" title="Click here to move the selected product list to SELECTED PRODUCTS section.SELECTED PRODUCT section prepare your search result to be included in your feed." id="cpf_move_selected" value="Move Selected" onclick ="saveSelectedProduct(this,' . $edit . ') ;" />
                                        </span>
                                        </a><p style="color:grey">Note: Only the product that you want on your feed should be selected and moved.</p>
                                    </th>

                                    <th id="show-more" colspan="4" style="text-align:right">
                                        <input type="hidden" id="cpf_page_hidden_page_item" value="10" />
                                        <input type="hidden" id="cpf_page_hidden_first" value="0" />
                                        <input type="hidden" id="cpf_page_hidden_second" value="10" />
                                        <a style="display:none;" class="button-primary" id="cpf_load_more_pagination" onclick="loadMore(this)">Show More..</a>
                                        <span class="spinner show-more"></span>
                                    </th>
                                </tfoot>
                        </table>
                        </form>
                    <div class="clear"></div>';

		$output .= '<div style="display:none;" id ="cpf-product-search-results">

            <div style="display:none;" class="wrap cpf-page">
                <div id="wrapperproductsearch">
                    <div id="post-body" class="metabox-holder columns-2">
                        <div id="postbox-container-1" class="postbox-container" style="margin-right: -381px; ">
                            <div id="side-sortables" class="meta-box" style="width: 558px;">
                                <div class="postbox" id="submitdiv">
                                    <h3><span>Selected Products: </span></h3>
                                    <div class="inside">
                                        <div id="submitpost" class="submitbox">
                                            <table class="cp-list-table widefat striped cpf-results" id="sort_1">
                                                <thead>
                                                <tr>
                                                     <th><input type="checkbox" id="cpf_select_all_checkbox_1" onclick="selectAllProducts_1(this);"/></th>
                                                     <th scope="col" id="product_name"
                                                        class="manage-column column-details column-primary">Product Name
                                                    </th>
                                                    <th scope="col" id="local_category_heading" class="manage-column column-user_name">Local
                                                        Category
                                                    </th>
                                                    <th></th>
                                                </tr>
                                                </thead>
                                                <tbody id="cpf-the-list_1" data-cpf-lists="list:cpf-search-list"
                                                       class="cpf-table-sortable ">

                                                <tr id="cpf-no-products" valign="top">
                                                    <td align="center" colspan="4">No Products Moved. Once you move products, they will be shown here.</td>
                                                </tr>


                                                   </tbody>
                                                 <tfoot>
                                                    <tr>
                                                        <td colspan="4" style="text-align: left;"><span class="delete-selected-products"><input class="button-primary" type="button"
                                                                              id="cpf_deleted_selected" value="Delete Selected" title="This will delete the product list from the feed."
                                                                            onclick ="deletedSelected(this) ;" /></span>
                                                        <br/>
                                                         <span id="cpf_deleted_selected_from_list" style="display: none;">Deleting product list....</span>
                                                        </td>

                                                    </tr>
                                                </tfoot>
                                                </table>

                                        </div>
                                    </div>
                                </div>';

		$output .= '
                </div>
            </div>
           </div> <!-- #post-body -->
        <br class="clear">
    </div> <!-- #poststuff -->
    </div>
</div>
<div class="clear"></div>
        <!-- #Feed Config !-->
        ';

		$this->loadFeedConfig();

		$output .= '
                       ';

		$output .= '  <!--#cpf_custom_feed_config -->
         ';
		if ( $this->service_name != 'Amazonsc' && $this->service_name != 'kelkoo' ) {
			$output .= '
             <table  id="cpf-generate-table" style="display:none;">
                <tbody>
                ';

			if ( $this->service_name == 'ShareASale' ) {
				$output .= '<tr valign="top"><td>' . $this->sas_categories_for_custom_feed_creation( $this->initial_remote_category ) . '</td></tr>';
				$output .= '<tr valign="top"><td>' . $this->sas_sub_categories_for_custom_feed_creation( $this->initial_remote_sub_category ) . '</td></tr>';
				$output .= '<input type="hidden" id="remotecategoryDisplayText" value="' . $this->initial_remote_category . '">';
				$output .= '<input type="hidden" id="remoteSubCategory" value="' . $this->initial_remote_sub_category . '">';
			} else {
				if ( $this->service_name != 'Slickguns' && $this->service_name != 'Webgains' && $this->service_name != 'Zomato' && $this->service_name != 'ElevenMain' && $this->service_name != 'Winesearcher' && $this->service_name != 'GPAnalysis' && $this->service_name != 'TradeTracker' ) {
					$output .= '
                    <tr valign="top">
                        <th style="line-height: 2em; float:left; width: 210px;" scope="row" class="titledesc"><label for="categoryDisplayText">' . $this->service_name . ' Merchant Category :</label><span style="padding : 8px 0 0 3px" class="cpf-help-tip" title="Enter the file name for the feed."></span></th>
                        ';
					if ( property_exists( $this, 'initial_remote_category' ) ) {
						$remotecat = $this->initial_remote_category;
					} else {
						$remotecat = '';
					}
					$output .= '<td class="forminp" style="float:left;">
                            <input style="width: 240px;" type="search" name="categoryDisplayText" class="text_big merchant-category" id="remotecategoryDisplayText" onclick="return checkPlaceholdervalue(this);" onkeyup="doFetchCategory_timed_custom(' . "'{$this->service_name}'" . ',this)" value="' . wp_kses( $remotecat, excpf_kses_criteria() ) . '" autocomplete="off" placeholder="Start typing merchant category...">
                            <div style="width:100%;color: grey;" class="categoryList"></div>
                        </td>';
				}
			}

			$output .= '
                    </tr>
                    <tr>
                    <td colspan="2">
                    <div id="KXTPPY">
                                    <label class="attr-desc label"><span class="label" style="display: block;font-weight: 600;">If you need to modify your product feed,
                                <a onclick="show_custom_attr(this)">click here to go to product feed customization options<span class="dashicons dashicons-arrow-down"></span></a>
                    </span></label>
                                </div>
                    <div id="feed-option-div" class="feed-options" style="display:none;">
            <span id="custom_feed_settingd"><a href="#cpf_custom_feed_config"><input class="button-primary" title="This will open feed config section below.You can provide suffix and prefix for the attribute to be included in feed." type="button" id="cpf_feed_config_link" value="Show Feed Config" onclick="toggleFeedSettings();"></a></span>
                            <span id="custom_feed_advance_section">
                        <a href="#cpf_advance_command_desc"><input class="button-primary" title="This will open advance command information." type="button" id="cpf_advance_section_link" value=" Feed Customization Options" onclick="toggleAdvanceCommandSection(this);"></a>
                    </span>
                            <div class="postbox" id="cpf_feed_config_desc" style="display:none;">
                                <ul style="list-style-type:disc;padding-left: 24px;">
                                    <b> Now you can set up feed with default merchant attributes or with your custom value.</b></br>
                                   <li> Select merchant attributes.</li>
                                        <b> For eg: g:brand </b><br/><br/>
                                   <li> Map attributes with merchant attributes or your custom value.</li>
                                        <b> For eg: Choose custom value </b> </br/><br/>
                                   <li> Enter your custom brand title.</li>
                                         <b> For eg: TestBrand </b><br/> <br/>
                                   <li> Add prefix to your custom brand name.</li>
                                         <b> For eg: p_</b> <br/><br/>
                                   <li> Add Suffix to your custom brand name.</li>
                                          <b> For eg: _s </b> <br/><br/>
                                   <li> Output limit helps to limit your product list on feed.</li>
                                         <b> For eg: 0-100 This will display 100 product list on your feed.</b> <br/>
                                 </ul>
                            </div>
                            <div id="cpf_custom_feed_config" class="cpf_custom_feed_config" style="display: none;">
            <div class="postbox" style="overflow: hidden;box-sizing: border-box;padding: 0 12px;">
                <h3 style="float: left;"><span>Feed Config: </span></h3>
                <span class="update_cpf_config" style="float: right;padding-top: 16px;">
                <button class="button-primary" id="bFeedSetting" name="bFeedSetting" onclick="doCustomFeedSetting(this);" title="This will update your feed config with the data that you enter in feed config section."> Update</button>
                <input type=hidden name="cpf_custom_merchant_type" value="cp_advancedFeedSetting-' . $this->service_name . '" />
                    <div id="updateCustomSettingMessage">&nbsp;</div><span class="spinner"></span>
                </span>
            </div>
            <table class="cpf-list-table widefat fixed striped cpf-results" id="cpf-sort_config" style="margin-top: -22px;">
                <thead>
                <tr>
                    <th scope="col" style="text-align: center">' . ( $this->provider->providerName ) . ' Attributes</pre></th>
                    <th scope="col" style="text-align: center">Type</th>
                    <th scope="col" style="text-align: center">Value</th>
                     <th scope="col" style="text-align: center">Prefix</th>
                    <th scope="col" style="text-align: center">Suffix</th>
                    <th scope="col" style="text-align: center">Output limit</th>
                    <th scope="col" style="width: 5%;text-align: center"></th>
                    <th scope="col" style="width: 5%;text-align: center"></th>
                </tr>
                </thead>
                <tbody id="cpf_custom_feed_config_body">';

			$output .= '  <tr>
                    <td style="text-align: center;width:100%">' . $this->merchantAttr( $defaultValue = '' ) . '</td>
                    <td style="text-align: center">';

			$output .= '<select name="cpf_type " class="cpf_change_type" onchange="cpf_changeType(this);"><option value="0" >Attributes</option><option value="1" >Custom Value</option></select></td>
                    <td style="text-align: center" id="cpf_attrdropdownlist" class="cpf_value_td">
                        <span class="cpf_default_attributes" >' . $this->createDropdownAttrCustom( $FoundAttributes, $defaultValue, '' ) . '</span>
                        <span class="cpf_custom_value_span" ><input type="text" style="display:none;" class="cpf_custom_value_attr" value= "" name="cpf_custom_value" style="width:100%" placeholder="Enter Custom Value"/></span>
                    </td>
                     <td style="text-align: center"><input type="text" class="cpf_prefix" name="cpf_prefix" style="width:100%" value= "" placeholder="Enter Prefix"/></td>
                    <td style="text-align: center"><input type="text" class="cpf_suffix" name="cpf_suffix" style="width:100%" value= "" placeholder="Enter Suffix"/></td>
                    <td style="text-align: center"><input type="text" id="cpf_feed_output_limit" name="cpf_feed_output_limit" style="width:100%" placeholder="Limit your feed list"/></td>
                    <td style="width: 5%;text-align: center"><span class="dashicons dashicons-plus" onclick="addRows(this);" title="Add rows."></span></td>
                    <td style="width: 5%;text-align: center"><span class="dashicons dashicons-trash" onclick="removeRows(this); title="Delete this rows."></span></td>
                </tr>';

			$output .= '</tbody>
            </table>
         </div>
         <div id="cpf_advance_command">
                <div id="cpf_advance_section" style="display: none;">
                            <div class="advanced-section-description" id="advanced_section_description" style="padding-left: 17px;">
                            <p>Advanced Commands grant you more control over your feeds. They provide a way to create your own attribute, map from non-standard ones or modify and delete feed data.</p>
                            <ul style="list-style: inherit;">
                                <li><a href="http://www.exportfeed.com/documentation/creating-attributes/#3_Creating_Defaults_using_Advanced_Commands" target="_blank">Creating Default Attributes with Advanced Commands</a></li>
                                <li><a href="http://www.exportfeed.com/documentation/mapping-attributes/#3_Mapping_from_8216setAttributeDefault8217_Advanced_Commands" target="_blank">Mapping/Remapping with Advanced Commands</a></li>
                                <li>Comprehensive Advanced Commands can be found here: <a title="mapping attributes - advanced commands" href="http://docs.shoppingcartproductfeed.com/AttributeMappingv3.1.pdf" target="_blank">More Advanced Commands</a> – *PDF</li>
                            </ul>
                        </div>
                            <div>
                            <label class="un_collapse_label" title="Click to open advance command field to customize your feed" ><input class="button-primary" type="button" id="toggleAdvancedSettingsButton" onclick="toggleAdvancedDialog()" value="Open Customization Commands"/></label>
                            <label class="un_collapse_label" title="This will erase your attribute mappings from the feed." id="erase_mappings" onclick="doEraseMappings(' . "'{$this->service_name}'" . ')"><input class="button-primary" type="button" value="Reset Attribute Mappings"  /></label>
                        </div>
                        </div>
                        <div class="feed-advanced" id="feed-advanced" >
                            <textarea class="feed-advanced-text" id="feed-advanced-text">' . "{$this->CustomadvancedSettings}" . '</textarea>
                            ' . "{$this->cbUnique}" . '
                            <input class="button-primary" type="button" id="bUpdateSetting" name="bUpdateSetting" title="Update Setting will update your feed data according to the advance command enter in advance command section." value="Update Settings" onclick="doUpdateSetting(\'feed-advanced-text\', \'cp_advancedFeedSetting-' . "{$this->service_name}" . '\',\'custom\'); return false;"  />
                            <div id="updateSettingMessage">&nbsp;</div>
                        </div>
                    </div>
                            </td>
                            </tr>
                    <tr valign="top">
                        <th style="line-height: 2em; float:left;width: 210px;" scope="row" class="titledesc"><label for="feed_filename">File name for feed :</label><span style="padding : 8px 0 0 3px" class="cpf-help-tip" title="Enter the file name for the feed."></span></th>
                        <td class="forminp" style="float:left">
                            <input type="search" style="width: 240px;" name="feed_filename" id="feed_filename" ' . $disabled . ' class="text_big" value="' . "$this->initial_filename" . '" autocomplete="off" placeholder="Enter file name">
                        </td>
                    </tr>
                    <tr>
                    <td><b>*Feed will open in new window. If not disabled the popup blocker.</b></td>
                    <td></td>
                    </tr>';

			if ( array_key_exists( 'id', $_REQUEST ) && sanitize_text_field( wp_unslash( $_REQUEST['id'] ) ) > 0 ) {
				$output .= '<tr>
                             <td><b>*While Editing Feed you can only add or delete poduct from the feed in custom feed.</b></td>
                            <td></td>
                            </tr>';
			} else {
				$output .= '<tr>
                             <td><b>*If you use an existing file name, the file will be overwritten.</b></td>
                            <td></td>
                            </tr>
                            <tr>
                             <td style="color:red"><b>*Once Feed is successfully generated, please reload the page or click <a id="createNextFeedLink" onclick="event.preventDefault(); generateNewIdentifier();" href="javascript:void(0)">Create Next Feed</a> to generate new feed. <br>Or Click <a id="amwscp_regenerateFeed" onclick="event.preventDefault(); amwscp_CustomfeedRegenerate();" href="javascript:void(0);">Regenerate</a> to regenerate the same feed</b></td>
                             <td></td>
                            </tr>';
			}

			$output .= '<tr>
                    <td style="text-align:right;">
                            <div class="feed-right-row" style="display:inline-block;">
                                <span class="spinner" style=";margin-left: 109px;""></span>
                                <input class="button-primary" type="button" id="cpf_get_custom_feed" onclick="doGetCustomFeed(' . "'{$this->service_name}'" . ' , this)" value="Get Feed">
                                 <br/><br/>
                                 <div id="feed-message-display">&nbsp;</div>
                                 <div id="cpf_feed_view"></div>
                                 <div id="feed-error-display">&nbsp;</div>
                                 <div id="feed-status-display">&nbsp;</div>
                            </div>
                        </td>
                    <td></td>
                    </tr>
                </tbody>
            </table>
            </div>';
		}
		$output .= '<input type="hidden" id="vsfeedid" name="secret_feed_id" value=""></div>';
		return $output;
	}

	public function loadFeedConfig() {
		global $wpdb;
		$this->feed_config = get_option( $this->service_name . '-cart-product-settings-custom' );
	}
	public function merchantAttr( $defaultValue ) {
		$merchantAttributes  = '';
		$merchantName        = $this->service_name;
		$merchantAttributes .= '<div id="cpf_merchantAttributes"><select name="cpf_merchantAttributes" class="cpf_merchantAttributes" style="width:100%;">';
		$merchantAttributes .= '<option value="">Select Merchant Attribute</option> ';
		$selected            = '';

		foreach ( $this->provider->attributeMappings as $key => $mappingData ) {
			if ( $defaultValue == $mappingData->mapTo ) {
				$selected = ' selected';
			}
			$merchantAttributes .= '<option value="' . $mappingData->mapTo . '" "' . $selected . '">' . $mappingData->mapTo . '</option>';
		}
		$merchantAttributes .= '</select></div>';
		return $merchantAttributes;

	}

	public function categoryList( $initial_remote_category ) {
		if ( $this->blockCategoryList ) {
			return '<input type = "hidden" id = "remote_category" name = "remote_category" value = "undefined" > ';
		} else {
			return '<span class="label" > ' . $this->service_name . ' Category : </span >

                      <span ><input type = "text" name = "categoryDisplayText" class="text_big cpf-createpage-input" onclick="atrributedisplay()" id = "categoryDisplayText"  onkeyup = "doFetchCategory_timed(\'' . $this->service_name . '\',  this.value)" value = "' . wp_kses( $initial_remote_category, excpf_kses_criteria()) . '" autocomplete = "off" placeholder = "Start typing for a category name" /></span >
                      <span id="rcems" class="label" style="width: 25%;"></span>
                      <div id = "categoryList" class="categoryList" ></div >
                      <input type = "hidden" id = "remote_category" name = "remote_category" value = "' . wp_kses( $initial_remote_category, excpf_kses_criteria()) . '" > ';
		}

	}

	public function getTemplateFile() {
		$filename = dirname( __FILE__ ) . '/../feeds/' . strtolower( $this->service_name ) . '/dialog.tpl.php';
		// $filename = dirname(__FILE__) . ' /../feeds/' . strtolower($this->service_name) . '/dialog.tpl.php';
		if ( ! file_exists( $filename ) ) {
			$filename = dirname( __FILE__ ) . '/dialogbasefeed.tpl.php';
			// require_once (dirname(__FILE).'dialogbasefeedcustom.tpl.php');
		}
		return $filename;
	}

	public function initializeProvider() {
		// Load the feed provider
		require_once dirname( __FILE__ ) . '/md5.php';
		$require = dirname( __FILE__ ) . '/../feeds/' . strtolower( $this->service_name ) . '/feed.php';

		if ( file_exists( $require ) ) {
			require_once $require;
		} else {
			die( esc_html( $require ) . " Doesn't Exixts." );
		}

		$providerName   = 'EXCPF_P' . $this->service_name . 'Feed';
		$this->provider = new $providerName();
		$this->provider->loadAttributeUserMap( 'cpf_attribute_user_map_' );
	}

	public function line2() {
		global $pfcore;
		if ( $pfcore->cmsPluginName != 'RapidCart' ) {
			return '';
		}

		$listOfShops = $pfcore->listOfRapidCartShops();
		$output      = ' < select class="text_big" id = "edtRapidCartShop" onchange = "doFetchLocalCategories()" > ';
		foreach ( $listOfShops as $shop ) {
			if ( $shop->id == $pfcore->shopID ) {
				$selected = ' selected = "selected"';
			} else {
				$selected = '';
			}

			$output .= ' < option value = "' . $shop->id . '"' . $selected . ' > ' . $shop->name . '</option > ';
		}
		$output .= '</select > ';
		return '
                <div class="feed-right-row" >
                  <span class="label" > Shop : </span >
    ' . $output . '
                </div > ';
	}

	public function mainDialog( $source_feed = null, $feed_type = null ) {
		global $pfcore;
		$this->advancedSettings       = $pfcore->settingGet( $this->service_name . '-cart-product-settings' );
		$this->CustomadvancedSettings = $pfcore->settingGet( $this->service_name . '-cart-product-settings-custom' );
		if ( $source_feed == null ) {
			$initial_local_category            = '';
			$this->initial_local_category_id   = '';
			$this->initial_remote_category     = '';
			$this->initial_remote_sub_category = '';
			$this->initial_filename            = '';
			$this->script                      = '';
			$this->cbUnique                    = '';
			$this->miinto_country_code         = '';
			$this->feed_identifier             = null;
		} else {
			if ( is_object( $source_feed ) ) {
				$initial_local_category            = property_exists( $source_feed, 'local_category' ) ? $source_feed->local_category : '';
				$this->miintobrandlocalcat         = property_exists( $source_feed, 'local_category' ) ? $source_feed->local_category : '';
				$this->initial_local_category_id   = property_exists( $source_feed, 'category_id' ) ? $source_feed->category_id : '';
				$this->initial_remote_sub_category = ! empty( get_option( 'cpf_remote_sub_category_' . $source_feed->feed_identifier ) ) ? get_option( 'cpf_remote_sub_category_' . $source_feed->feed_identifier ) : '';
				$this->initial_remote_category     = property_exists( $source_feed, 'remote_category' ) ? $source_feed->remote_category : '';
				$this->initial_filename            = property_exists( $source_feed, 'filename' ) ? $source_feed->filename : '';
				$this->miinto_country_code         = property_exists( $source_feed, 'miinto_country_code' ) ? $source_feed->miinto_country_code : '';
				$this->feed_identifier             = property_exists( $source_feed, 'feed_identifier' ) ? $source_feed->feed_identifier : '';
				$this->edit_feed                   = true;
				if ( property_exists( $source_feed, 'own_overrides' ) && $source_feed->own_overrides == 1 ) {
					$strChecked                   = 'checked = "checked" ';
					$this->advancedSettings       = $source_feed->feed_overrides;
					$this->CustomadvancedSettings = $source_feed->feed_overrides;
				} else {
					$strChecked = '';
				}
			} else {
				$initial_local_category          = null;
				$this->miintobrandlocalcat       = null;
				$this->initial_local_category_id = null;
				$initial_remote_category         = null;
				$this->initial_filename          = null;
				$this->miinto_country_code       = null;
				$strChecked                      = '';
			}
			$this->cbUnique = ' <div><label ><input type = "checkbox" id = "cbUniqueOverride" ' . $strChecked . ' />Advanced commands unique to this feed </label ></div > ';
		}

		$this->servName = strtolower( $this->service_name );
		$this->initializeProvider();
		$attrVal = array();

		$this->folders            = new EXCPF_PFeedFolder();
		$this->product_categories = new EXCPF_PProductCategories(); // used?

		$this->localCategoryList = '
            <input type = "text" name = "local_category_display" class="text_big cpf-createpage-input" id = "local_category_display"  onclick = "showLocalCategories(\'' . $this->service_name . '\')" value = "' . $initial_local_category . '" autocomplete = "off" readonly = "true" placeholder = "Click here to select your categories" />
            <input type = "hidden" name = "local_category" id = "local_category" value = "' . $this->initial_local_category_id . '" />';
		$this->source_feed       = $source_feed;
		$this->feed_type         = $feed_type;

		// Pass this to the template for processing
		if ( file_exists( $this->getTemplateFile() ) ) {
			include $this->getTemplateFile();
		} else {
			echo "Template File doesn't exists.";
			exit();
		}

	}

	// Strip special characters out of an option so it can safely go in a <select /> in the dialog
	public function convert_option( $option ) {
		// Some Feeds (like Google & eBay) need to modify this
		return $option;
	}

	public function MapMerchant( $merchant, $key ) {
		$merchant      = strtolower( $merchant );
		$merchantArray = array(
			'google'             => array(
				'h2'            => 'Product Feed Export for Google Shopping',
				'how'           => 'How to Generate Feed for Google Shopping',
				'prerequisites' => 'Find out Pre-Requisites to create the Google Feed',
			),
			'amazonsc'           => array(
				'h2'            => 'Product Feed Export for Amazon Seller Central',
				'how'           => 'How to Generate Feed for Amazon',
				'prerequisites' => 'Find out Pre-Requisites to create the Amazon Seller Central Feed',
			),
			'amazon'             => array(
				'h2'            => 'Product Feed Export for Amazon Products Ads',
				'how'           => 'How to Generate Feed for Products Ads',
				'prerequisites' => 'Find out Pre-Requisites to create the Amazon Products Ads Feed',
			),
			'amazonpauk'         => array(
				'h2'            => 'Product Feed Export for Amazon Products Ads UK',
				'how'           => 'How to Generate Feed for Amazon Products Ads UK',
				'prerequisites' => 'Find out Pre-Requisites to create the Amazon Products Ads UK Feed',
			),
			'ebayseller'         => array(
				'h2'            => 'Product Feed Export for ebayseller',
				'how'           => 'How to Generate Feed for ebayseller Merchant',
				'prerequisites' => 'Find out Pre-Requisites to create the ebayseller Feed',
			),
			'facebookxml'        => array(
				'h2'            => 'Product Feed Export for Facebookxml',
				'how'           => 'How to Generate Feed for Facebookxml',
				'prerequisites' => 'Find out Pre-Requisites to create the Facebookxml Feed',
			),
			'bing'               => array(
				'h2'            => 'Product Feed Export for Bing',
				'how'           => 'How to Generate Feed for Bing Merchant',
				'prerequisites' => 'Find out Pre-Requisites to create the Bing Feed',
			),
			'miinto'             => array(
				'h2'            => 'Product Feed Export for Miinto',
				'how'           => 'How to Generate Feed for Miinto Merchant',
				'prerequisites' => 'Find out Pre-Requisites to create the Miinto Feed',
			),
			'miintobrand'        => array(
				'h2'            => 'Product Feed Export for Miintobrand',
				'how'           => 'How to Generate Feed for Miintobrand Merchant',
				'prerequisites' => 'Find out Pre-Requisites to create the Miintobrand Feed',
			),
			'pricerunner'        => array(
				'h2'            => 'Product Feed Export for Pricerunner',
				'how'           => 'How to Generate Feed for Pricerunner Merchant',
				'prerequisites' => 'Find out Pre-Requisites to create the Pricerunner Feed',
			),
			'bonanza'            => array(
				'h2'            => 'Product Feed Export for Bonanza',
				'how'           => 'How to Generate Feed for Bonanza',
				'prerequisites' => 'Find out Pre-Requisites to create the Bonanza Feed',
			),
			'become'             => array(
				'h2'            => 'Product Feed Export for Become',
				'how'           => 'How to Generate Feed for Become',
				'prerequisites' => 'Find out Pre-Requisites to create the Become Feed',
			),
			'beslist'            => array(
				'h2'            => 'Product Feed Export for Beslist',
				'how'           => 'How to Generate Feed for Beslist',
				'prerequisites' => 'Find out Pre-Requisites to create the Beslist Feed',
			),
			'ebay'               => array(
				'h2'            => 'Product Feed Export for ebay',
				'how'           => 'How to Generate Feed for ebay Merchant',
				'prerequisites' => 'Find out Pre-Requisites to create the ebay Feed',
			),
			'houzz'              => array(
				'h2'            => 'Product Feed Export for Houzz',
				'how'           => 'How to Generate Feed for Houzz',
				'prerequisites' => 'Find out Pre-Requisites to create the Houzz Feed',
			),
			'newegg'             => array(
				'h2'            => 'Product Feed Export for Newegg',
				'how'           => 'How to Generate Feed for Newegg',
				'prerequisites' => 'Find out Pre-Requisites to create the Newegg Feed',
			),
			'nextag'             => array(
				'h2'            => 'Product Feed Export for nextag',
				'how'           => 'How to Generate Feed for nextag',
				'prerequisites' => 'Find out Pre-Requisites to create the nextag Feed',
			),
			'pronto'             => array(
				'h2'            => 'Product Feed Export for Pronto',
				'how'           => 'How to Generate Feed for Pronto',
				'prerequisites' => 'Find out Pre-Requisites to create the Pronto Feed',
			),
			'rakuten'            => array(
				'h2'            => 'Product Feed Export for Rakuten',
				'how'           => 'How to Generate Feed for Rakuten',
				'prerequisites' => 'Find out Pre-Requisites to create the Rakuten Feed',
			),
			'rakutennewsku'      => array(
				'h2'            => 'Product Feed Export for Rakutennewsku',
				'how'           => 'How to Generate Feed for Rakutennewsku',
				'prerequisites' => 'Find out Pre-Requisites to create the Rakutennewsku Feed',
			),
			'rakutenuK'          => array(
				'h2'            => 'Product Feed Export for RakutenuK',
				'how'           => 'How to Generate Feed for RakutenuK',
				'prerequisites' => 'Find out Pre-Requisites to create the RakutenuK Feed',
			),
			'kelkoo'             => array(
				'h2'            => 'Product Feed Export for Kelkoo',
				'how'           => 'How to Generate Feed for Kelkoo',
				'prerequisites' => 'Find out Pre-Requisites to create the Kelkoo Feed',
			),
			'shopping.com'       => array(
				'h2'            => 'Product Feed Export for shopping.com',
				'how'           => 'How to Generate Feed for shopping.com',
				'prerequisites' => 'Find out Pre-Requisites to create the shopping.com Feed',
			),
			'pricegrabber'       => array(
				'h2'            => 'Product Feed Export for Pricegrabber',
				'how'           => 'How to Generate Feed for Pricegrabber',
				'prerequisites' => 'Find out Pre-Requisites to create the Pricegrabber Feed',
			),
			'shopzilla'          => array(
				'h2'            => 'Product Feed Export for Shopzilla',
				'how'           => 'How to Generate Feed for Shopzilla',
				'prerequisites' => 'Find out Pre-Requisites to create the Shopzilla Feed',
			),
			'ammoseek'           => array(
				'h2'            => 'Product Feed Export for Ammoseek',
				'how'           => 'How to Generate Feed for Ammoseek',
				'prerequisites' => 'Find out Pre-Requisites to create the Ammoseek Feed',
			),
			'affiliatewindow'    => array(
				'h2'            => 'Product Feed Export for Affiliatewindow',
				'how'           => 'How to Generate Feed for  Affiliatewindow',
				'prerequisites' => 'Find out Pre-Requisites to create the Affiliatewindow Feed',
			),
			'affiliatewindowxml' => array(
				'h2'            => 'Product Feed Export for Affiliatewindowxml',
				'how'           => 'How to Generate Feed for Affiliatewindowxml',
				'prerequisites' => 'Find out Pre-Requisites to create the Affiliatewindowxml Feed',
			),
			'gpanalysis'         => array(
				'h2'            => 'Product Feed Export for Gpanalysis',
				'how'           => 'How to Generate Feed for Gpanalysis',
				'prerequisites' => 'Find out Pre-Requisites to create the Gpanalysis Feed',
			),
			'avantlink'          => array(
				'h2'            => 'Product Feed Export for Avantlink',
				'how'           => 'How to Generate Feed for Avantlink',
				'prerequisites' => 'Find out Pre-Requisites to create the Avantlink Feed',
			),
			'webgains'           => array(
				'h2'            => 'Product Feed Export for Webgains',
				'how'           => 'How to Generate Feed for Webgains',
				'prerequisites' => 'Find out Pre-Requisites to create the Webgains Feed',
			),
			'shareasale'         => array(
				'h2'            => 'Product Feed Export for Shareasale Merchant',
				'how'           => 'How to Generate Feed for Shareasale Merchant',
				'prerequisites' => 'Find out Pre-Requisites to create the Shareasale Feed',
			),
			'rakuten'            => array(
				'h2'            => 'Product Feed Export for Rakuten Merchant',
				'how'           => 'How to Generate Feed for Rakuten Merchant',
				'prerequisites' => 'Find out Pre-Requisites to create the Rakuten Feed',
			),
			'hardwareinfo'       => array(
				'h2'            => 'Product Feed Export for Hardwareinfo Merchant',
				'how'           => 'How to Generate Feed for Hardwareinfo Merchant',
				'prerequisites' => 'Find out Pre-Requisites to create the Hardwareinfo Feed',
			),
			'admarkt'            => array(
				'h2'            => 'Product Feed Export for Admark Merchant',
				'how'           => 'How to Generate Feed for Admarkt Merchant',
				'prerequisites' => 'Find out Pre-Requisites to create the Admarkt Feed',
			),
		);
		if ( isset( $merchantArray[ $merchant ][ $key ] ) ) {
			return $merchantArray[ $merchant ][ $key ];
		}
		return null;
	}

}
