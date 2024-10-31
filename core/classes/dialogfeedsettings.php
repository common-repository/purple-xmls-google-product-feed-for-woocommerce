<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// Exit if accessed directly

/********************************************************************
 * Version 2.0
 * Settings for feeds
 * Copyright 2014 Purple Turtle Productions. All rights reserved.
 * license    GNU General Public License version 3 or later; see GPLv3.txt
 * By: Keneto 2014-05-05
 * Note: Joomla version does not use this part of the core... it defines its own component screens
 ********************************************************************/
class EXCPF_PFeedSettingsDialogs {


	public static function formatIntervalOption( $value, $descriptor, $current_delay ) {
		$selected = '';
		if ( $value == $current_delay ) {
			$selected = ' selected="selected"';
		}
		return '<option value="' . $value . '"' . $selected . '>' . $descriptor . '</option>';
	}

	public static function fetchRefreshIntervalSelect() {
		$current_delay = get_option( 'cp_feed_delay' );
		global $wpdb;
		$table                 = $wpdb->prefix . 'cp_feeds';
		$ammoSeekFeed          = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM $table WHERE type='AmmoSeek'" ) );
		$feedintervalSettings  = '';
		$feedintervalSettings .= '
            <select name="delay" class="select_medium" id="selectDelay">' . "\r\n" .
			self::formatIntervalOption( 604800, '1 Week', $current_delay ) . "\r\n" .
			self::formatIntervalOption( 86400, '24 Hours', $current_delay ) . "\r\n" .
			self::formatIntervalOption( 43200, '12 Hours', $current_delay ) . "\r\n" .
			self::formatIntervalOption( 21600, '6 Hours', $current_delay ) . "\r\n" .
			self::formatIntervalOption( 3600, '1 Hour', $current_delay ) . "\r\n" .
			self::formatIntervalOption( 900, '15 Minutes', $current_delay ) . "\r\n";
		if ( ! is_null( $ammoSeekFeed ) ) {
			$feedintervalSettings .= self::formatIntervalOption( 300, '5 Minutes', $current_delay ) . "\r\n";
		}

		$feedintervalSettings .= '</select>';
		return $feedintervalSettings;
	}

	public static function refreshTimeOutDialog() {
		define( 'EXCPF_IMAGE_PATH', plugins_url( '/', __FILE__ ) . '../../images/' );
		global $wpdb;
		return '
          <div id="poststuff">
          <div class="postbox" style="padding:10px;">
              <div class="logo-am" style="vertical-align: middle; display: inline-block;">
                    <h4 class="icon-margin">Get standalone plugin for</h4>
                    <div class="upsell-icon-logo">
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
                </div>

                <div class="clear"></div>

            </div>
              <div id="post-body" class="metabox-holder columns-2">
                <div id="postbox-container-2" class="postbox-container">
                    <div class="postbox">
                        <h3 class="hndle">Interval at which feed auto-refreshes</h3>
                        <div class="inside export-target">
                            <table class="form-table update-table">
                                <tbody>
                                    <tr>
                                        <th style="width:5%"><label>Interval:</label></th>
                                        <td style="width:15%;">' . self::fetchRefreshIntervalSelect() . '</td>
                                        <td>
                                        <span>
                                        <input class="button-primary" type="submit" value="Update Interval" id="submit" name="submit" onclick="doUpdateSetting(\'selectDelay\', \'cp_feed_delay\')"><div id="updateSettingMessage"></div></td>
                                        </span>

                                    </tr>
                                </tbody>
                            </table>
                            <div id="postbox-container-1" class="postbox-container desc-update">
                                <div class="postbox description">
                                    <div class="inside export-target">
                                        <span class="dashicons dashicons-arrow-right"></span><b>ExportFeed will automatically update your product information in feeds after you set the update  interval here.</b>
                                        <br/><br/>
                                        <span class="dashicons dashicons-arrow-right"></span><b>Your feed will be updated automatically after <span id="set_interval_time"></span>.</b>
                                    </div>
                                </div>
                            </div>
                            <div class="manual-update"><label class="upd-txt">Made recent changes to your products?</label><input style="margin-left: 25px;" class="button-primary" type="submit" value="Update Now" id="submit" name="submit" onclick="doUpdateAllFeeds(this,\'up\')">
                        <div id="update-message" style="display:none;">&nbsp;</div>
                        <div class = "update-feed" style="display:none;">&nbsp;</div>
                    </div>
                        </div>
                    </div>
                </div>
               </div>
          </div> <div class="clear"></div>';

	}

	public static function filterProductDialog() {
		global $wpdb;
		return '
      <div id="cpf_filter_poststuff">
        <div class="postbox">
          <h3 class="hndle" style="font-size: 14px;padding-left: 13px;">Select Feed type you want to display.</h3>
          <div class="inside export-target" style="padding-left: 9px;">
          <label for="cpf_filter_product_feed"><b>Feed Type:</b></label>
          <select name="cpf_filter_product_feed" id="cpf_filter_product_feed" style="width: 22%;margin-left: 9px;">
                        <option value="0">Select Feed Type</option>
                        <option value="1">Custom product feed</option>
                        <option value="2">Feed by Category</option>
                </select>
                <span class="spinner" style="float: none;"></span>
          </div>
         </div>
      </div>';
	}
}

?>
<script type="text/javascript">
	jQuery(document).ready(function () {
		jQuery("#cpf_filter_product_feed").change(function () {
			jQuery('#cpf_filter_poststuff').find('.spinner').css('visibility', 'visible');
			var feed_type = jQuery("#cpf_filter_product_feed").val();
			jQuery.ajax({
				type: "POST",
				url: "<?php echo esc_url( EXCPF_URL . 'core/ajax/wp/fetch_feed_table.php' ); ?>",
				data: {feed_type: feed_type},
				success: function (res) {
					jQuery('#cpf_filter_poststuff').find('.spinner').css('visibility', 'hidden');
					jQuery("#cpf_manage_table_originals").html(res);
				}
			});
		});

		jQuery("#set_interval_time").html(jQuery("#selectDelay option:selected").html());

	});

</script>
