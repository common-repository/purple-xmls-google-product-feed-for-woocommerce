<?php
require_once dirname( __FILE__ ) . '/Extendables.php';

class EXCPF_DataStore extends EXCPF_Extendables {


	function __construct() {
		parent::__construct();
	}

	public function getProducts( $parent, $remotecat ) {
		global $wpdb, $amwcore, $woocommerce;
		if ( is_object( $woocommerce ) && property_exists( $woocommerce, 'version' ) ) {
			$wc_version = explode( ',', $woocommerce->version );
		} else {
			$wc_version = explode( ',', '3.0.0' );
		}
		$table          = $wpdb->prefix . 'posts';
		$relationTable  = $wpdb->prefix . 'term_relationships';
		$taxonomyTable  = $wpdb->prefix . 'term_taxonomy';
		$termTable      = $wpdb->prefix . 'terms';
		$postMetatable  = $wpdb->prefix . 'postmeta';
		$postmetaSelect = '';
		$postmetaJoin   = '';
		$parent->logActivity( 'Retrieving product list from database' );
		$this->woocommerce_manage_stock           = get_option( 'woocommerce_manage_stock' );
		$this->woocommerce_notify_no_stock_amount = get_option( 'woocommerce_notify_no_stock_amount' );

		if ( is_array( $parent->categories->categories ) && count( $parent->categories->categories ) > 0 ) {
			$categories = $this->getCategories( $parent );
		}
		if ( $parent->has_product_range ) {
			$limit      = $parent->product_limit_high - $parent->product_limit_low;
			$offset     = $parent->product_limit_low;
			$limitQuery = "LIMIT $offset,$limit";
		} else {
			$limitQuery = '';
		}
		$categories = implode( ',', $categories );
		$where      = "WHERE T.term_id IN ({$categories}) ";
		$where     .= " AND P.post_type='product' AND P.post_status ='publish' AND (tax.taxonomy='category' OR tax.taxonomy='product_cat')";
		$SQL        = "SELECT DISTINCT P.*,GROUP_CONCAT(T.name) as category, GROUP_CONCAT(T.slug) as category_slug, GROUP_CONCAT(tax.taxonomy) as taxtype {$postmetaSelect} FROM {$table} P LEFT JOIN {$relationTable} rel ON rel.object_id = P.id LEFT JOIN {$taxonomyTable} tax ON tax.term_taxonomy_id = rel.term_taxonomy_id LEFT JOIN {$termTable} T ON T.term_id = tax.term_id {$postmetaJoin} {$where} GROUP BY P.ID {$limitQuery}";

		$Products = $wpdb->get_results( $SQL );
		if ( is_array( $Products ) && count( $Products ) > 0 ) {
			/**
			 * gathers all attributes needed in to create feed
			 * Communicates with getProductMeta(), getProductType(), getTerms() for the processing
			 */
			foreach ( $Products as $key => $product ) {
				$product = $this->integrateAttributes( $parent, $product );

				if ( strtolower( $product->_product_type ) === 'variable' ) {

				} else {

				}
				return true;
			}
		}

	}

	public function integrateAttributes( $parent, $product ) {
		$product                = $this->getProductMeta( $product->ID, $product );
		$product->_product_type = $this->getProductType( $product->ID );
		// see if the product consists any attributes
		if ( isset( $product->_product_attributes ) ) {
			$product->_product_attributes = $this->getTerms( $product->ID, $product->_product_attributes );
		}
		$product->_tags = get_the_terms( $product->ID, 'product_tag' );

		/**
		 * If use site has installed any google merchant related plugin
		 *   use the value from that particular attributes
		 */
		if ( isset( $parent->gmc_active ) ) {
			$product = $this->handleGMCThing( $parent, $product );
		}

		$this->hideStockValid( $product );
	}


	public function getCategories( $parent ) {
		$categories = array();
		foreach ( $parent->categories->categories as $key => $cat ) {
			if ( ! in_array( $cat->id, $categories ) ) {
				array_push( $categories, $cat->id );
				$taxonomies      = array( 'taxonomy' => 'product_cat' );
				$args            = array( 'child_of' => $cat->id );
				$childCategories = get_terms( $taxonomies, $args );
				if ( is_array( $childCategories ) && count( $childCategories ) > 0 ) {
					foreach ( $childCategories as $key => $value ) {
						array_push( $categories, $value->term_id );
					}
				}
			}
		}
		return $categories;
	}

	private function handleGMCThing( $parent, $product ) {
		if ( property_exists( $product, 'gmc_value' ) && ( strlen( $product->gmc_value ) > 0 ) ) {
			$gmc_attributes = unserialize( $product->gmc_value );
			if ( is_array( $gmc_attributes ) ) {
				foreach ( $gmc_attributes as $key => $this_attribute ) {
					// Use this_attribute if no overrides force us to use any attributes OR if there are overrides and in_array()
					if ( ( count( $parent->gmc_attributes ) == 0 ) || ( in_array( $key, $parent->gmc_attributes ) ) ) {
						switch ( $key ) {
							case 'description':
								if ( strlen( $product->description_short ) == 0 ) {
									$product->description_short = $this_attribute;
								}
								if ( strlen( $product->description_long ) == 0 ) {
									$product->description_long = $this_attribute;
								}
								break;
							case 'product_type':
								$product->attributes['product_type'] = $this_attribute;
								break;
							case 'category':
								$product->attributes['localCategory'] = $this_attribute;
								break;
							case 'condition':
								$product->attributes['condition'] = $this_attribute;
								break;
							case 'shipping_weight':
								$product->attributes['weight'] = $this_attribute;
								break;
							default:
								$product->attributes[ $key ] = $this_attribute;
						}
					}
				}
			}

			return $product;

		} /* GMC WORK ENDS */
	}


}
