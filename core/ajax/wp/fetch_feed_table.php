<?php
/**
 * Created by PhpStorm.
 * User: subash
 * Date: 8/23/16
 * Time: 3:32 PM
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
if ( ! is_admin() ) {
	die( 'Permission Denied!' );
}
if ( isset( $_REQUEST['security'] ) ) {
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['security'] ) ), 'cpf_nonce' ) ) {
		die( 'Permission denied' );
	}
}
define( 'XMLRPC_REQUEST', true );
ob_start();
require_once dirname( __FILE__ ) . '/../../../cart-product-wpincludes.php';
global $cp_feed_order, $cp_feed_order_reverse;
require_once dirname( __FILE__ ) . '/../../../core/classes/dialogfeedsettings.php';
require_once dirname( __FILE__ ) . '/../../../core/data/savedfeed.php';

// ********************************************************************
// Load the products
// ********************************************************************
global $wpdb;

$feed_type = isset( $_POST['feed_type'] ) ? sanitize_text_field( wp_unslash( $_POST['feed_type'] ) ) : '';
if ( $feed_type == 1 ) {
	$where = ' WHERE feed_type = 1';
}
if ( $feed_type == 2 ) {
	$where = ' WHERE feed_type = 0';
}
if ( $feed_type == 0 ) {
	$where = 'WHERE feed_type IN (0,1)';
}

// The feeds table flat
global $wpdb;
$feed_table   = $wpdb->prefix . 'cp_feeds';
$providerList = new EXCPF_PProviderList();

// Read the feeds
$list_of_feeds = $wpdb->get_results( $wpdb->prepare( "SELECT f.*,description FROM $feed_table as f LEFT JOIN $wpdb->term_taxonomy on ( f.category=term_id and taxonomy='product_cat'  ) $where ORDER BY f.id" ), ARRAY_A );
// Find the ordering method
$reverse = false;
if ( isset( $_GET['order_by'] ) ) {
	$order_ = sanitize_text_field( wp_unslash( $_GET['order_by'] ) );
} else {
	$order_ = '';
}
if ( $order_ == '' ) {
	$order_  = get_option( 'cp_feed_order' );
	$reverse = get_option( 'cp_feed_order_reverse' );
} else {
	$old_order = get_option( 'cp_feed_order' );
	$reverse   = get_option( 'cp_feed_order_reverse' );
	if ( $old_order == $order_ ) {
		$reverse = ! $reverse;
	} else {
		$reverse = false;
	}
	update_option( 'cp_feed_order', $order_ );
	if ( $reverse ) {
		update_option( 'cp_feed_order_reverse', true );
	} else {
		update_option( 'cp_feed_order_reverse', false );
	}
}

if ( ! empty( $list_of_feeds ) ) {

	// Setup the sequence array
	$seq = false;
	$num = false;
	foreach ( $list_of_feeds as $this_feed ) {
		$this_feed_ex = new EXCPF_PSavedFeed( $this_feed['id'] );
		switch ( $order_ ) {
			case 'name':
				$seq[] = strtolower( stripslashes( $this_feed['filename'] ) );
				break;
			case 'description':
				$seq[] = strtolower( stripslashes( $this_feed_ex->local_category ) );
				break;
			case 'url':
				$seq[] = strtolower( $this_feed['url'] );
				break;
			case 'category':
				$seq[] = $this_feed['category'];
				$num   = true;
				break;
			case 'google_category':
				$seq[] = $this_feed['remote_category'];
				break;
			case 'type':
				$seq[] = $this_feed['type'];
				break;
			default:
				$seq[] = $this_feed['id'];
				$num   = true;
				break;
		}
	}

	// Sort the seq array
	if ( $num ) {
		asort( $seq, SORT_NUMERIC );
	} else {
		asort( $seq, SORT_REGULAR );
	}

	// Reverse ?
	if ( $reverse ) {
		$t   = $seq;
		$c   = count( $t );
		$tmp = array_keys( $t );
		$seq = false;
		for ( $i = $c - 1; $i >= 0; $i-- ) {
			$seq[ $tmp[ $i ] ] = '0';
		}
	}

	$image['down_arrow'] = '<img src="' . EX_CPF_URL . 'images/down.png" alt="down" style=" height:12px; position:relative; top:2px; " />';
	$image['up_arrow']   = '<img src="' . EX_CPF_URL . 'images/down.png" alt="up" style=" height:12px; position:relative; top:2px; " />';
	?>
	<!--    <div class="table_wrapper"> -->
	<table class="widefat" style="margin-top:12px;">
		<thead>
		<tr>
			<?php $url = get_admin_url() . 'admin.php?page=cart-product-feed-manage-page&amp;order_by='; ?>
			<th scope="col" style="min-width: 40px;padding-left: 2px;"><input type="checkbox" id="cpf_select_all_feed"
																			  onclick="cpf_check_all_feeds(this);"/>
			</th>
			<th scope="col" style="min-width: 40px;">
				<a href="<?php echo esc_url( $url . 'id' ); ?>">
					<?php
					esc_html_e( 'ID', 'cart-product-strings' );
					if ( $order_ == 'id' ) {
						if ( $reverse ) {
							echo esc_html( print( $image['up_arrow'] ) );
						} else {
							echo esc_html( print( $image['down_arrow'] ) );
						}
					}
					?>
				</a>
			</th>
			<th scope="col" style="min-width: 120px;">
				<a href="<?php echo esc_url( $url . 'name' ); ?>">
					<?php
					esc_html_e( 'Name', 'cart-product-strings' );
					if ( $order_ == 'name' ) {
						if ( $reverse ) {
							echo esc_html( print( $image['up_arrow'] ) );
						} else {
							echo esc_html( print( $image['down_arrow'] ) );
						}
					}
					?>
				</a>
			</th>
			<th scope="col">
				<a href="<?php echo esc_url( $url . 'category' ); ?>">
					<?php
					esc_html_e( 'Local category', 'cart-product-strings' );
					if ( $order_ == 'category' ) {
						if ( $reverse ) {
							echo esc_html( print( $image['up_arrow'] ) );
						} else {
							echo esc_html( print( $image['down_arrow'] ) );
						}
					}
					?>
				</a>
			</th>
			<th scope="col" style="min-width: 100px;">
				<a href="<?php echo esc_url( $url . 'google_category' ); ?>">
					<?php
					esc_html_e( 'Export category', 'cart-product-strings' );
					if ( $order_ == 'google_category' ) {
						if ( $reverse ) {
							echo esc_html( print( $image['up_arrow'] ) );
						} else {
							echo esc_html( print( $image['down_arrow'] ) );
						}
					}
					?>
				</a>
			</th>
			<th scope="col" style="min-width: 50px;">
				<a href="<?php echo esc_url( $url . 'type' ); ?>">
					<?php
					esc_html_e( 'Type', 'cart-product-strings' );
					if ( $order_ == 'type' ) {
						if ( $reverse ) {
							echo esc_html( print( $image['up_arrow'] ) );
						} else {
							echo esc_html( print( $image['down_arrow'] ) );
						}
					}
					?>
				</a>
			</th>
			<th scope="col" style="width: 120px;">
				<a href="<?php echo esc_url( $url . 'url' ); ?>">
					<?php
					esc_html_e( 'URL', 'cart-product-strings' );
					if ( $order_ == 'url' ) {
						if ( $reverse ) {
							echo esc_html( print( $image['up_arrow'] ) );
						} else {
							echo esc_html( print( $image['down_arrow'] ) );
						}
					}
					?>
				</a>
			</th>
			<th scope="col" style="width: 80px;"><?php esc_html_e( 'Last Updated', 'cart-product-strings' ); ?></th>
			<!-- <th scope="col" width="50px"><?php // _e( 'View', 'cart-product-strings' ); ?></th> -->
			<!-- <th scope="col" width="50px"><?php esc_html_e( 'Options', 'cart-product-strings' ); ?></th> -->
			<!-- <th scope="col" width="50px"><?php // esc_html_e( 'Delete', 'cart-product-strings' ); ?></th> -->
			<th scope="col"><?php esc_html_e( 'Products', 'cart-product-strings' ); ?></th>
		</tr>
		</thead>
		<tbody>
		<?php $alt = ' class="alternate" '; ?>

		<?php
		$idx = '0';
		foreach ( array_keys( $seq ) as $s ) {
			$this_feed    = $list_of_feeds[ $s ];
			$this_feed_ex = new EXCPF_PSavedFeed( $this_feed['id'] );
			$pendcount    = false;
			?>
			<tr 
			<?php
			echo( esc_html( $alt ) );
			if ( $pendcount ) {
				echo 'style="background-color:#ffdddd"';
			}
			?>
			>
				<td><input type="checkbox" class="cpf_select_feed"/></td>
				<td><?php echo esc_html( $this_feed['id'] ); ?></td>
				<td><?php echo esc_html( $this_feed['filename'] ); ?>
					<input type="hidden" class="cpf_hidden_feed_id" value="<?php echo esc_html( $this_feed['id'] ); ?>"/>
					<input type="hidden" class="cpf_hidden_feed_id" value="<?php echo esc_html( $this_feed['id'] ); ?>"/>
					<div class="row-actions"><span class="id">ID: <?php echo esc_html( $this_feed['id'] ); ?> | </span>
						<span class="purple_xmlsedit">
								 <a href="<?php echo esc_url( $this_feed['url'] ); ?>" target="_blank" title="View this Feed"
									rel="permalink">View</a> |
							</span>
						<?php $url_edit = get_admin_url() . 'admin.php?page=cart-product-feed-admin&action=edit&id=' . $this_feed['id'] . '&feed_type=' . $this_feed['feed_type']; ?>
						<span class="purple_xmlsedit">
								 <a href="<?php echo( esc_url( $url_edit ) ); ?>" target="_blank" title="Edit this Feed"
									rel="permalink">Edit</a> |
							</span>
						<?php $url = get_admin_url() . 'admin.php?page=cart-product-feed-manage-page&action=delete&id=' . $this_feed['id']; ?>
						<span class="delete">
								 <a href="<?php echo( esc_url( $url ) ); ?>" title="Delete this Feed">Delete</a> |
							</span>
					</div>
				</td>
				<td>
					<small><?php echo esc_attr( stripslashes( $this_feed_ex->local_category ) ); ?></small>
				</td>
				<td><?php echo esc_html( str_replace( '.and.', ' & ', str_replace( '.in.', ' > ', esc_attr( stripslashes( $this_feed['remote_category'] ) ) ) ) ); ?></td>
				<td><?php echo esc_html( $providerList->getPrettyNameByType( $this_feed['type'] ) ); ?></td>
				<td><?php echo esc_url( $this_feed['url'] ); ?></td>
				<?php // $url = get_admin_url() . 'admin.php?page=??? ( edit feed ) &amp;tab=edit&amp;edit_id=' . $this_feed['id']; ?>
				<td>
				<?php
					$ext       = '.' . $providerList->getExtensionByType( $this_feed['type'] );
					$feed_file = EXCPF_PFeedFolder::uploadFolder() . $this_feed['type'] . '/' . $this_feed['filename'] . $ext;
				if ( file_exists( $feed_file ) ) {
					echo esc_html( date( 'd-m-Y H:i:s', filemtime( $feed_file ) ) );
				} else {
					echo 'DNE';
				}
				?>
					</td>

				<!--  <td><a href="<?php echo esc_url( $this_feed['url'] ); ?>" target="_blank" class="purple_xmlsedit"><?php esc_html_e( 'View', 'cart-product-strings' ); ?></a></td>
						<?php $url_edit = get_admin_url() . 'admin.php?page=cart-product-feed-admin&action=edit&id=' . $this_feed['id']; ?>
						<td><a href="<?php echo( esc_url( $url_edit ) ); ?>" class="purple_xmlsedit"><?php esc_html_e( 'Edit', 'cart-product-strings' ); ?></a></td>
						<?php $url = get_admin_url() . 'admin.php?page=cart-product-feed-manage-page&action=delete&id=' . $this_feed['id']; ?>
						<td><a href="<?php echo( esc_url( $url ) ); ?>" class="purple_xmlsedit"><?php esc_html_e( 'Delete', 'cart-product-strings' ); ?></a></td>
						<?php if ( $this_feed['type'] == 'eBaySeller' ) : ?>
							<?php $upload_url = get_admin_url() . 'admin.php?page=cart-product-feed-admin&action=uploadFeed&id=' . $this_feed['id']; ?>
							<td><a href="<?php echo( esc_url( $upload_url ) ); ?>" class="purple_xmlsedit"><?php esc_html_e( 'Upload', 'cart-product-strings' ); ?></a></td>
						<?php endif; ?>     -->
				<td><?php echo esc_html( $this_feed['product_count'] ); ?></td>

			</tr>
			<?php
			if ( $alt == '' ) {
				$alt = ' class="alternate" ';
			} else {
				$alt = '';
			}

			$idx++;
		}
		?>
		</tbody>
		<tfoot>
		<tr>
			<?php
			$url    = get_admin_url() . 'admin.php?page=cart-product-manage-page&amp;order_by=';
			$order_ = '';
			?>
			<th scope="col" style="min-width: 40px;padding-left: 2px;"><input type="checkbox" id="cpf_select_all_feed_1"
																			  onclick="cpf_check_all_feeds_1(this);"/>
			</th>
			<th scope="col" style="min-width: 40px;">
				<a href="<?php echo esc_url( $url . 'id' ); ?>">
					<?php
					esc_html_e( 'ID', 'cart-product-strings' );
					if ( $order_ == 'id' ) {
						if ( $reverse ) {
							echo esc_html( print( $image['up_arrow'] ) );
						} else {
							echo esc_html( print( $image['down_arrow'] ) );
						}
					}
					?>
				</a>
			</th>
			<th scope="col" style="min-width: 120px;">
				<a href="<?php echo esc_url( $url . 'name' ); ?>">
					<?php
					esc_html_e( 'Name', 'cart-product-strings' );
					if ( $order_ == 'name' ) {
						if ( $reverse ) {
							echo esc_html( print( $image['up_arrow'] ) );
						} else {
							echo esc_html( print( $image['down_arrow'] ) );
						}
					}
					?>
				</a>
			</th>
			<th scope="col">
				<a href="<?php echo esc_url( $url . 'category' ); ?>">
					<?php
					esc_html_e( 'Local Category', 'cart-product-strings' );
					if ( $order_ == 'category' ) {
						if ( $reverse ) {
							echo esc_html( print( $image['up_arrow'] ) );
						} else {
							echo esc_html( print( $image['down_arrow'] ) );
						}
					}
					?>
				</a>
			</th>
			<th scope="col" style="min-width: 100px;">
				<a href="<?php echo esc_url( $url . 'google_category' ); ?>">
					<?php
					esc_html_e( 'Export category', 'cart-product-strings' );
					if ( $order_ == 'google_category' ) {
						if ( $reverse ) {
							echo esc_html( print( $image['up_arrow'] ) );
						} else {
							echo esc_html( print( $image['down_arrow'] ) );
						}
					}
					?>
				</a>
			</th>
			<th scope="col" style="min-width: 50px;">
				<a href="<?php echo esc_url( $url . 'type' ); ?>">
					<?php
					esc_html_e( 'Type', 'cart-product-strings' );
					if ( $order_ == 'type' ) {
						if ( $reverse ) {
							echo esc_html( print( $image['up_arrow'] ) );
						} else {
							echo esc_html( print( $image['down_arrow'] ) );
						}
					}
					?>
				</a>
			</th>
			<th scope="col" style="width: 120px;">
				<a href="<?php echo esc_url( $url . 'url' ); ?>">
					<?php
					esc_html_e( 'URL', 'cart-product-strings' );
					if ( $order_ === 'url' ) {
						if ( $reverse ) {
							echo esc_html( print( $image['up_arrow'] ) );
						} else {
							echo esc_html( print( $image['down_arrow'] ) );
						}
					}
					?>
				</a>
			</th>
			<th scope="col" style="width: 80px;"><?php esc_html_e( 'Last Updated', 'cart-product-strings' ); ?></th>
			<!--  <th scope="col"><?php // _e( 'View', 'cart-product-strings' ); ?></th> -->
			<!-- <th scope="col"><?php esc_html_e( 'Options', 'cart-product-strings' ); ?></th> -->
			<!-- <th scope="col"><?php // _e( 'Delete', 'cart-product-strings' ); ?></th> -->
			<th scope="col"><?php esc_html_e( 'Products', 'cart-product-strings' ); ?></th>
		</tr>
		</tfoot>

	</table>
	<!--    </div> -->
	<?php
} else {
	?>
	<p><?php esc_html_e( 'No feeds yet!', 'cart-product-strings' ); ?></p>
	<?php
}

