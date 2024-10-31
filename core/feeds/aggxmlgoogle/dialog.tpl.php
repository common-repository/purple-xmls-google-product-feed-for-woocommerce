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
						   href='<?php echo esc_html( $this->doc_link ); ?>'><?php echo esc_html( $this->MapMerchant( $this->service_name, 'prerequisites' ) ); ?></a>
																																	<?php
																																}
				}
				?>
			</div>
			<div class="inside export-target">
				<!-- ***************
						RIGHT SIDE
						****************** -->

				<style type="text/css" >
					.w3-table, .w3-table-all {
						border-collapse: collapse;
						border-spacing: 0;
						width: 50%;
						display: table;
					}
					th {
						text-align: left;
					}
				</style>
				<div class="create-feed-container">
				<div class="feed-right ">
					<table class="w3-table" cellspacing="5">
						<tr>
							<th>id</th>
							<th>Select</th>
							<th>Type</th>
							<th>Filename</th>
							<th>#Products</th>
						</tr>
						<?php foreach ( $this->feeds as $index => $thisFeed ) : ?>
							<tr>
								<td><?php echo esc_html( $thisFeed->id ); ?></td>
								<td><input type="checkbox" class="feedSetting" name="feedChoice<?php echo esc_html( $index ); ?>" value="<?php echo esc_html( $thisFeed->id ); ?>" <?php echo esc_html( $thisFeed->checkedString ); ?> /></td>
								<td><?php echo esc_html( $thisFeed->type ); ?></td>
								<td><?php echo esc_html( $thisFeed->filename ); ?></td>
								<td><?php echo esc_html( $thisFeed->product_count ); ?></td>
							</tr>
						<?php endforeach; ?>
					</table>


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
							   onclick="doGetAlternateFeed('<?php echo esc_html( $this->servName ); ?>' , this)" value="Get Feed"/>
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
