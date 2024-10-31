<?php global $pfcore; ?>
<div class="attributes-mapping">
	<div id="poststuff">
		<div class="postbox" style="width: 98%;">

			<!-- ***************
					Page Header
					****************** -->
			<div class="service_name_long hndle">
				<h2>Product Feed Export for <?php echo esc_html( $this->service_name ); ?></h2>
				<?php if ( $this->MapMerchant( $this->service_name, 'how' ) != '' ) { ?>
					<a target="blank" title="Generate Merchant Feed"
					   href="https://www.exportfeed.com/documentation/instructions-for-trial-and-new-customers/'"><?php echo esc_html( $this->MapMerchant( $this->service_name, 'how' ) ); ?></a> 
																															 <?php
																																if ( $this->doc_link && strlen( $this->doc_link ) > 0 ) {
																																	?>
							|
						<a target=\'_blank\'
						   href='<?php echo esc_url( $this->doc_link ); ?>'><?php echo esc_html( $this->MapMerchant( $this->service_name, 'prerequisites' ) ); ?></a>
																																	<?php
																																}
				}
				?>
			</div>
			<div class="inside export-target">

				<!-- ***************
						LEFT SIDE
					  ****************** -->


				<!-- ***************
						RIGHT SIDE
					  ****************** -->
				<div class="create-feed-container">
					<div class="feed-right">

						<!-- ROW 1: Local Categories -->
						<div class="feed-right-row">
							<span class="label"><?php echo esc_html( $pfcore->cmsPluginName ); ?> Category : </span>
							<?php echo wp_kses( $this->localCategoryList, excpf_kses_criteria() ); ?>
							<span id="lcems" class="label"></span>
						</div>

						<!-- ROW 2: Remote Categories -->
						<?php echo esc_html( $this->line2() ); ?>
						<div class="feed-right-row">
							<?php echo wp_kses( $this->categoryList( $initial_remote_category ), excpf_kses_criteria() ); ?>
						</div>

						<div id="KXTPPY">
							<label class="attr-desc label">
								<span class="label" style="display: block;font-weight: 600;">If you need to modify your product feed,
								<a onclick="toggle_advanced_command(this)">click here to go to product feed customization options<span
											class="dashicons dashicons-arrow-down"></span></a>
								</span>
							</label>
						</div>

						<div class="feed-left" id="attributeMappings" style="display: none;">
							<div id="cpf_advance_command_default">
							<span id="cpf_advance_command_settings">
								<a href="#cpf_advance_command_desc"><input class="button-primary"
																		   title="This will open advance command information."
																		   style="font-weight: bold;" type="button"
																		   id="cpf_feed_config_link_default"
																		   value=" Feed Customization Options"
																		   onclick="toggleAdvanceCommandSectionDefault(this);"></a>
							</span>
								<div id="cpf_advance_section_default" style="display: none;">
									<div class="advanced-section-description" id="advanced_section_description_default"
										 style="padding-left: 17px;">
										<p>Feed Customization option grant you more control over your feeds. They
											provide a
											way to create your own attribute, map from non-standard ones or modify and
											delete feed data.</p>
										<ul style="list-style: inherit;">
											<li><a target="_blank"
												   href="http://www.exportfeed.com/documentation/creating-attributes/#3_Creating_Defaults_using_Advanced_Commands">Creating
													Default Attributes with Feed Customization option</a></li>
											<li><a target="_blank"
												   href="http://www.exportfeed.com/documentation/mapping-attributes/#3_Mapping_from_8216setAttributeDefault8217_Advanced_Commands">Mapping/Remapping
													with Feed Customization option</a></li>
											<li>Comprehensive Feed Customization option can be found here: <a
														title="mapping attributes - Feed Customization option"
														href="http://docs.shoppingcartproductfeed.com/AttributeMappingv3.1.pdf"
														target="_blank">More Feed Customization option</a> – *PDF
											</li>
											<li>Example:</li>
											<table class="adv-cmd-exmple">
												<tr>
													<th>Command</th>
													<th>Description</th>
												</tr>
												<tr>
													<td>setAttributeDefault brand as "Your Store Name"</td>
													<td>Sets all items to ‘Your Brand’</td>
												</tr>
												<tr>
													<td>rule discount(0.95, *, p)</td>
													<td>Take 95% of price (5% discount)</td>
												</tr>
												<tr>
													<td>rule discount(0.95, *, s)</td>
													<td>Take 95% of sale price (5% discount)</td>
												</tr>
											</table>
										</ul>
									</div>
									<div>
										<label class="un_collapse_label"
											   title="Click to open advance command field to customize your feed"><input
													class="button-primary" type="button"
													id="toggleAdvancedSettingsButtonDefault"
													onclick="toggleAdvancedDialogDeafult();"
													value="Open Customization Commands"/></label>
										<label class="un_collapse_label"
											   title="This will erase your attribute mappings from the feed."
											   id="erase_mappings_default"
											   onclick="doEraseMappings('<?php echo esc_html( $this->service_name ); ?>')"><input
													class="button-primary" type="button"
													value="Reset Attribute Mappings"/></label>
									</div>
								</div>
								<div class="feed-advanced" id="feed-advanced-default">
									<textarea <textarea class="feed-advanced-text"
														id="feed-advanced-text-default"><?php echo wp_kses( $this->advancedSettings, excpf_kses_criteria() ); ?></textarea>
									<?php echo wp_kses( $this->cbUnique, excpf_kses_criteria()); ?>
									<input class="button-primary" type="button" id="bUpdateSettingDefault"
										   name="bUpdateSettingDefault"
										   title="Update Setting will update your feed data according to the advance command enter in advance command section."
										   value="Update Settings"
										   onclick="doUpdateSetting('feed-advanced-text-default', 'cp_advancedFeedSetting-<?php echo esc_html( $this->service_name ); ?>'); return false;"/>
									<div id="updateSettingMsg">&nbsp;</div>
								</div>
							</div>
						</div>

						<!-- ROW 3: Filename -->
						<div class="feed-right-row feed-section">
							<span class="label">File name for feed : </span>
							<span><input onclick="return ValidateFilename();" type="text" name="feed_filename"
										 id="feed_filename_default" class="text_big input-big cpf-createpage-input"
										 value="<?php echo esc_html( $this->initial_filename ); ?>"/></span>
							<span id="mfefn" class="label"></span>
						</div>
						<div class="feed-right-row">
							<label><span style="color: red">*</span> Use alpha-numeric values for the filename.<br>If
								you use an existing file name, the file will be overwritten.</label>
						</div>

						<!-- ROW 4: Get Feed Button -->
						<!-- ROW 4: Get Feed Button -->
						<div class="feed-right-row">
							<input class="button-primary" style="font-weight: bold;" type="button"
								   onclick="doGetFeed('<?php echo esc_html( $this->service_name ); ?>' , this)" value="Get Feed"/>
							<div class="message-display-div" id="feed-message-display"
								 style="padding-top: 6px; color: red; margin:10px 0;">&nbsp;
							</div>
							<div class="message-display-div"
								 style="display: none;padding-top: 6px; color:  red; margin:10px 0;"
								 id="feed-error-message-display">&nbsp;
							</div>
							<div class="message-display-div"
								 style="display: none;padding-top: 6px; color:  #289c2c; margin:10px 0;"
								 id="feed-success-message-display">&nbsp;
							</div>
							<div class="message-display-div"
								 style="display: none;padding-top: 6px; color:  #FF8C00; margin:10px 0;"
								 id="warning-display-div">&nbsp;
							</div>
							<div class="message-display-div" id="cpf_feed_view"></div>
							<div class="message-display-div" id="feed-error-display">&nbsp;</div>
							<div class="message-display-div" id="feed_status__log"></div>
							<div class="message-display-div" id="feed-status-display">&nbsp;</div>
						</div>
					</div>

					<!-- ***************
							Termination DIV
							****************** -->

					<div style="clear: both;">&nbsp;</div>

					<!-- ***************
							FOOTER
							****************** -->
				</div>
			</div>
		</div>
	</div>
</div>
<script>
	function toggleAdvanceCommandSection(event) {
		var feed_config = jQuery("#cpf_custom_feed_config").css('display');
		var feed_config_button = jQuery("#cpf_feed_config_link");

		//First slideUp feed config section if displayed
		if (feed_config == "block") {
			jQuery("#cpf_custom_feed_config").slideUp();
			jQuery("#cpf_feed_config_desc").slideUp();
			jQuery(feed_config_button).attr('title', 'This will open feed config section below.You can provide suffix and prefix for the attribute to be included in feed.');
			jQuery(feed_config_button).val('Show Feed Config');
		}

		var display = jQuery("#cpf_advance_section").css('display');
		if (display == 'none') {
			jQuery("#cpf_advance_section").slideDown();
			jQuery(event).val('Hide Advance Section');
			jQuery(event).attr('title', 'Hide Feed config section');
			/* var divPosition = jQuery("#cpf_custom_feed_config").offset();
			 jQuery('#custom_feed_settingd').animate({scrollBottom: divPosition.top}, "slow");*/
		}
		if (display == 'block') {
			jQuery("#cpf_advance_section").slideUp();
			jQuery("#feed-advanced").slideUp();
			// jQuery("#bUpdateSetting").slideUp();
			jQuery(event).attr('title', 'This will open feed advance command section where you can customize your feed using advanced command.');
			jQuery(event).val('Feed Customization Options');
		}
	}

	function toggleAdvanceCommandSectionDefault(event) {
		var display = jQuery("#cpf_advance_section_default").css('display');
		if (display == 'none') {
			jQuery("#cpf_advance_section_default").slideDown();
			jQuery(event).val('Hide Advance Section');
			jQuery(event).attr('title', 'Hide Feed config section');
			/* var divPosition = jQuery("#cpf_custom_feed_config").offset();
			 jQuery('#custom_feed_settingd').animate({scrollBottom: divPosition.top}, "slow");*/
		}
		if (display == 'block') {
			jQuery("#cpf_advance_section_default").slideUp();
			jQuery("#feed-advanced-default").slideUp();
			// jQuery("#bUpdateSetting").slideUp();
			jQuery(event).attr('title', 'This will open feed advance command section where you can customize your feed using advanced command.');
			jQuery(event).val('Feed Customization Options');
		}
	}

	function toggle_advanced_command(selector) {
		jQuery('#attributeMappings').toggle();
		jQuery('#cpf_feed_config_link_default').hide();
		jQuery('#cpf_feed_config_link_default').trigger('click');
	}
</script>
