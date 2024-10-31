<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
// Checks cart-product-feed version
function excpf_check_version() {
	// taken from /include/update.php line 270
	$plugin_info = get_site_transient( 'update_plugins' );

	// we want to always display 'up to date', therefore we don't need the below check
	if ( ! isset( $plugin_info->response[ EXCPF_PLUGIN_BASENAME ] ) ) {
		return ' | You are up to date';
	}

	$CPF_WP_version = $plugin_info->response[ EXCPF_PLUGIN_BASENAME ]->new_version; // WordPress repository version
	// version_compare:
	// returns -1 if the first version is lower than the second,
	// 0 if they are equal,
	// 1 if the second is lower.
	$doUpdate = version_compare( $CPF_WP_version, EXCPF_FEED_PLUGIN_VERSION );
	// if current version is older than WordPress repo version
	if ( $doUpdate == 1 ) {
		return ' | <a href=\'plugins.php\'>Out of date - please update</a>';
	}
	// else, up to date
	return ' | You are up to date';
}

function excpf_print_info() {
	 $iconurl    = plugins_url( '/', __FILE__ ) . '/images/exf-sm-logo.png';
	$gts_iconurl = plugins_url( '/', __FILE__ ) . '/images/google-customer-review.png';
	echo '<div class="exf-logo-header">
		<div class="exf-logo-link">
	 		<a target="_blank" href="http://www.exportfeed.com"><img class="exf-logo-style" src=' . esc_url( $iconurl ) . ' alt="shopping cart logo"></a>
	 	</div>
	 	<div class=\'version-style\'>
	 		<a target="_blank" href="http://www.exportfeed.com/woocommerce-product-feed/">Product Site</a> | 
	 		<a target="_blank" href="http://www.exportfeed.com/faq/">FAQ/Help</a> 
	 		'// | <a target="_blank" href="http://www.exportfeed.com/?s=">SEARCH</a> <br>
		. '<br>Version: ' . esc_html( EXCPF_FEED_PLUGIN_VERSION ) . esc_html( excpf_check_version() ) . '<br>
	 	</div>
	 	<div class="gts-link">
	 		<a target="_blank" href="http://www.exportfeed.com/google-trusted-store-woocommerce/">Get the Google Customer Reviews Plugin<br>Sell More - Be placed 1st!</a>
	 	</div>
	 	<div class="gts-logo-link" >
	 		<a target="_blank" href="http://www.exportfeed.com/google-trusted-store/"><img class="gts-logo-style" src=' . esc_url( $gts_iconurl ) . ' alt="google trusted stores"></a>
	 	</div>
	 </div>
	';
}

function excpf_render_navigation() {
	$active_tab    = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'createfeed';
	$tutorials_url = site_url() . '/wp-admin/admin.php?page=cart-product-feed-tutorials-page';
	$url           = site_url() . '/wp-admin/admin.php?page=cart-product-feed-manage-page';

	?>
	<div class="nav-wrapper">
		<nav class="nav-tab-wrapper">
			<a href="<?php echo esc_url( site_url() ); ?>/wp-admin/admin.php?page=cart-product-feed-admin&tab=createfeed"
			   class="nav-tab <?php echo $active_tab == 'createfeed' ? 'nav-tab-active ' : ''; ?>"><?php esc_html_e( 'Create Feed', 'cart-product-strings' ); ?></a>
			<a href="<?php echo esc_url( $url ); ?>&tab=managefeed"
			   class="nav-tab <?php echo esc_html( $active_tab ) == 'managefeed' ? 'nav-tab-active ' : ''; ?>"><?php esc_html_e( 'Manage Feed', 'cart-product-strings' ); ?></a>
			<a href="http://www.exportfeed.com/contact/"
			   target="_blank"
			   class="nav-tab <?php echo esc_html( $active_tab ) == 'contactus' ? 'nav-tab-active ' : ''; ?>"><?php esc_html_e( 'Contact Us', 'cart-product-strings' ); ?></a>
			<a target="_blank" href="<?php echo esc_html( $tutorials_url ); ?>&tab=tutorials"
			   class="nav-tab <?php echo esc_html( $active_tab ) == 'tutorials' ? 'nav-tab-active ' : ''; ?>"><?php esc_html_e( 'Tutorials', 'cart-product-strings' ); ?></a>

			<?php
			require_once 'cart-product-wpincludes.php';
			$ifpremium = false;
			$reg       = new EXCPF_PLicense();
			if ( isset( $reg->results['status'] ) && $reg->results['status'] == 'Active' ) {
				$checklicense = $reg->results;
				$productname  = explode( ':', $checklicense['productname'] );
				if ( strpos( $productname[0], 'TRIAL' ) !== false ) {
					$ifpremium = false;
				} else {
					$ifpremium = true;
				}
			}
			if ( $ifpremium == false ) {
				?>
				<a href="http://www.exportfeed.com/woocommerce-product-feed/" target="_blank"
				   class="nav-tab"><?php esc_html_e( 'Go Pro', 'cart-product-strings' ); ?></a>

			<?php } ?>

			<ul class="subsubsub prem" style="float: right;">
				<?php if ( $ifpremium == false ) { ?>
				<li><a href="https://shop.exportfeed.com/cart.php?gid=8" target="_blank">Go Premium</a></li>
				<?php } ?>
				<!-- <li><a href="http://www.exportfeed.com/woocommerce-product-feed/" target="_blank">Product Site</a> |
				</li>
				<li><a href="http://www.exportfeed.com/faq/" target="_blank">FAQ/Help</a></li> -->
			</ul>
		</nav>
	</div>

	<?php
}
