<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( defined( 'ENV' ) && ENV === 'development' ) {
	ini_set( 'display_startup_errors', 1 );
	ini_set( 'display_errors', 1 );
	error_reporting( E_ALL );
}
// Exit if accessed directly

/********************************************************************
 * Version 2.0
 * This gets a little complex. See design-document -> ProductCategoryExport
 * Copyright 2014 Purple Turtle Productions. All rights reserved.
 * license    GNU General Public License version 3 or later; see GPLv3.txt
 * By: Keneto 2014-05-14
 ********************************************************************/
class EXCPF_PAProductW extends EXCPF_PAProduct {


}

class EXCPF_PMasterAttributeList {


	protected $children;

	public function __construct() {
		$this->children = array();
	}

	public function add( $child ) {
		$this->children[] = $child;
	}

	public function findByName( $name ) {
		foreach ( $this->children as $child ) {
			if ( $child->name == $name ) {
				return $child;
			}
		}

		return null;
	}

	public function findBySlug( $slug ) {
		foreach ( $this->children as $mainChild ) {
			foreach ( $mainChild->children as $termChild ) {
				if ( $termChild->slug == $slug ) {
					return $termChild;
				}
			}
		}

		return null;
	}

}

class EXCPF_PTaxonomyList {


	public $children;

	public function __construct() {
		$this->children = array();
	}

	public function add( $child ) {
		$this->children[] = $child;
	}

	public function findByName( $name ) {
		foreach ( $this->children as $child ) {
			if ( $child->name == $name ) {
				return $child;
			}
		}

		return null;
	}

	public function findBySlug( $slug ) {
		foreach ( $this->children as $child ) {
			if ( $child->slug == $slug ) {
				return $child;
			}
		}

		return null;
	}

}

class EXCPF_PProductList {

	public $AttributeCategory;
	public $exclude_variable_attributes;
	public $masterAttributeList;
	public $products = null;
	public $cpf_custom_local_cats = array();
	public $cpf_custom_remote_cats = array();

	// public $tag_list;

	public $relatedData;
	public $lowerWcVersion;

	public function __construct() {
		$this->AttributeCategory = array();
		$this->relatedData       = array();
		$this->loadMasterAttributeList();
		$this->woocommerce_manage_stock = get_option( 'woocommerce_manage_stock' );
	}

	public function applyWCAttributes( $listitem ) {

		if ( ! isset( $listitem->wc_attributes ) || gettype( $listitem->wc_attributes ) != 'array' || ! is_array( $listitem->wc_attributes ) || count( $listitem->wc_attributes ) == 0 ) {
			return;
		}

		if ( is_array( $listitem->wc_attributes ) ) {
			foreach ( $listitem->wc_attributes as $index => $thisAttribute ) {
				$value = $thisAttribute['value'];
				if ( $thisAttribute['is_taxonomy'] == 1 ) {
					$taxo = ! empty( $listitem->taxonomies->findByName( substr( $thisAttribute['name'], 3 ) ) ) ? $listitem->taxonomies->findByName( substr( $thisAttribute['name'], 3 ) ) : null;
					if ( $taxo != null ) {
						if ( isset( $taxo->value ) ) {
							$value                                                = $taxo->value;
							$listitem->attributes[ '_' . $thisAttribute['name'] ] = $taxo->slug;
						}
					}
				}
				// Insert directly
				if ( strlen( $value ) == 0 ) {
					continue;
				}

				$listitem->attributes[ $thisAttribute['name'] ] = $value;
				// Insert pa_xyz as xyz
				$pos = strpos( $thisAttribute['name'], 'pa_' );
				if ( $pos !== false && $pos == 0 ) {
					$name = substr( $thisAttribute['name'], 3 );
					if ( ! isset( $listitem->attributes[ $name ] ) || strlen( $listitem->attributes[ $name ] ) == 0 ) {
						$listitem->attributes[ $name ] = $value;
					}
				}
			}
		}

	}

	public function applyWCAttributesPost( $listitem ) {
		// System was designed to applyWCAttributes() after postmetaLookup() but in the case of
		// variations parent's applyWCAttributes() can mess this up, so patching
		if ( is_array( $listitem->wc_attributes ) ) {
			foreach ( $listitem->wc_attributes as $index => $thisAttribute ) {
				// For Attributes not in pa_XYZ but in attribute_XYZ exists in listitem->attributes, trim the word attribute
				$custom_index = 'attribute_' . strtolower( $thisAttribute['name'] );
				if ( isset( $listitem->attributes[ $custom_index ] ) ) {
					$listitem->attributes[ strtolower( $thisAttribute['name'] ) ] = $listitem->attributes[ $custom_index ];
					unset( $listitem->attributes[ $custom_index ] );
				}
			}
		}

	}

	/*
	CustomFields and max_custom_field are ignored anyway, so removing
	public function get_customfields($parent) {
	if ($parent->max_custom_field == 0)
	$this->custom_fields = array();
	else {
	global $wpdb;
	$sql = "SELECT post_id, meta_key, meta_value FROM $wpdb->postmeta WHERE meta_key NOT LIKE '\_%' LIMIT 0, " . $parent->max_custom_field;
	$this->custom_fields = $wpdb->get_results($sql);
	}
	 */

	public function loadProducts( $parent ) {

		global $wpdb;

		// ********************************************************************
		// Check that the DB can handle big queries
		// ********************************************************************

		$settings = $wpdb->get_results( "SHOW VARIABLES LIKE 'SQL_BIG_SELECTS'" );

		if ( is_array( $settings ) ) {
			foreach ( $settings as $this_setting ) {
				if ( ( $this_setting->Variable_name == 'sql_big_selects' ) && ( $this_setting->Value == 'OFF' ) ) {
					$wpdb->get_results( 'SET SQL_BIG_SELECTS=1' );
				}
			}
		}

		/********************************************************************
		 *Initialization*
		 */

		$extra_sql_query = '';
		$extra_sql_part  = '';

		if ( ! isset( $this->relatedData['brand'] ) && $parent->allowRelatedData ) {
			$this->relatedData['brand'] = new EXCPF_PProductSupplementalData( 'product_brand' );
		}

		if ( ! isset( $this->relatedData['tag'] ) && $parent->allowRelatedData ) {
			$this->relatedData['tag'] = new EXCPF_PProductSupplementalData( 'product_tag' );
		}

		// Load up the meta attributes which will tell us the custom fields
		// $this->get_customfields($parent);

		$this->gmc_active = false;
		if ( ( $parent->gmc_enabled ) && ( is_plugin_active( 'woocommerce-google-merchant-center-feed/woocommerce-google-merchant-center-feed.php' ) ) ) {
			// In this case, attributes may be hidden in strange places. Adjust SQL statement to catch those
			$this->gmc_active = true;
			$extra_sql_query  .= ', gmc_attributes.meta_value as gmc_value';
			$extra_sql_part   .= "
                #Merchant Center catches
                LEFT JOIN
                    (
                        SELECT post_id, meta_value
                        FROM $wpdb->postmeta
                        WHERE (meta_key = 'wc_gmcf')
                    ) as gmc_attributes ON gmc_attributes.post_id = $wpdb->posts.ID
            ";
		}

		// ********************************************************************
		// Load the products
		// ********************************************************************

		if ( $parent->has_product_range ) {
			$limit = 'LIMIT ' . $parent->product_limit_low . ', ' . ( $parent->product_limit_high - $parent->product_limit_low );
		} else {
			$limit = '';
		}

		$attribute_select   = '';
		$attribute_sql_part = '';

		if ( $parent->allow_attribute_details ) {
			$attribute_select   .= 'attributes.meta_value as attribute_list, ';
			$attribute_sql_part .= "
                #Attribute_list
                LEFT JOIN
                    (
                        SELECT post_id, meta_value
                        FROM $wpdb->postmeta
                        WHERE $wpdb->postmeta.meta_key = '_product_attributes'
                    ) as attributes ON attributes.post_id = $wpdb->posts.ID";
		}

		if ( $parent->allow_attributes ) {
			$attribute_select   .= 'attribute_details.attribute_details, ';
			$attribute_sql_part .= "
                #Attributes in detail
                LEFT JOIN
                    (
                        SELECT a.ID, GROUP_CONCAT(CONCAT(c.taxonomy, '=', d.slug, '=', d.name)) as attribute_details
                        FROM $wpdb->posts a
                        LEFT JOIN $wpdb->term_relationships b ON (a.ID = b.object_id)
                        LEFT JOIN $wpdb->term_taxonomy c ON (b.term_taxonomy_id = c.term_taxonomy_id)
                        LEFT JOIN $wpdb->terms d ON (c.term_id = d.term_id)
                        WHERE c.taxonomy LIKE 'pa\_%'
                        GROUP BY a.ID
                    ) as attribute_details ON attribute_details.ID = $wpdb->posts.ID";
		}

		$sql            = "
                SELECT
                  $wpdb->posts.ID, $wpdb->posts.post_date, $wpdb->posts.post_title, $wpdb->posts.post_content, $wpdb->posts.post_excerpt, $wpdb->posts.post_name,
                    tblCategories.category_names, tblCategories.category_ids,
                    details.name as product_type,
                    $attribute_select
                    variation_id_table.variation_ids as variation_ids
                    #, stock_levels.parent_manage_stock
                    $extra_sql_query
                FROM $wpdb->posts

                #Categories
                LEFT JOIN
                    (
                        SELECT postsAsTaxo.ID, GROUP_CONCAT(category_terms.name) as category_names, GROUP_CONCAT(category_terms.term_id) as category_ids
                        FROM $wpdb->posts postsAsTaxo
                        LEFT JOIN $wpdb->term_relationships category_relationships ON (postsAsTaxo.ID = category_relationships.object_id)
                        LEFT JOIN $wpdb->term_taxonomy category_taxonomy ON (category_relationships.term_taxonomy_id = category_taxonomy.term_taxonomy_id)
                        LEFT JOIN $wpdb->terms category_terms ON (category_taxonomy.term_id = category_terms.term_id)
                        WHERE category_taxonomy.taxonomy = 'product_cat' # AND category_terms.term_id IN (" . $parent->categories->asCSV() . ")
                        GROUP BY postsAsTaxo.ID
                    ) as tblCategories ON tblCategories.ID = $wpdb->posts.ID

                #Link in product type
                LEFT JOIN
                    (
                        SELECT a.ID, d.name FROM $wpdb->posts a
                        LEFT JOIN $wpdb->term_relationships b ON (a.ID = b.object_id)
                        LEFT JOIN $wpdb->term_taxonomy c ON (b.term_taxonomy_id = c.term_taxonomy_id)
                        LEFT JOIN $wpdb->terms d ON (c.term_id = d.term_id)
                        WHERE c.taxonomy = 'product_type'
                    ) as details ON details.ID = $wpdb->posts.ID

                $attribute_sql_part

                #variations
                LEFT JOIN
                    (
                        SELECT GROUP_CONCAT(postvars.id) as variation_ids, postvars.post_parent
                        FROM $wpdb->posts postvars
                        WHERE (postvars.post_type = 'product_variation') AND (postvars.post_status = 'publish')
                        GROUP BY postvars.post_parent
                    ) as variation_id_table on variation_id_table.post_parent = $wpdb->posts.ID

                $extra_sql_part

                WHERE $wpdb->posts.post_status = 'publish' AND $wpdb->posts.post_type = 'product'
                ORDER BY post_date ASC

                $limit
                    ";
		$this->products = $wpdb->get_results( $sql );

		if ( is_array( $this->products ) && count( $this->products ) == 0 ) {
			$parent->message .= 'ProductList returned Zero results';
		}
	} //loadProducts

	protected function loadMasterAttributeList() {
		$this->masterAttributeList = new EXCPF_PMasterAttributeList();
		global $wpdb;

		// Load the main attributes
		$sql  = '
            SELECT attribute_name as name, attribute_type as type
            FROM ' . $wpdb->prefix . 'woocommerce_attribute_taxonomies';
		$data = $wpdb->get_results( $sql );
		foreach ( $data as $datum ) {
			$thisAttribute           = new stdClass();
			$thisAttribute->name     = $datum->name;
			$thisAttribute->type     = $datum->type;
			$thisAttribute->children = array();
			$this->masterAttributeList->add( $thisAttribute );
		}

		// Load the taxonomies
		$sql  = "
            SELECT taxo.taxonomy, terms.name, terms.slug
            FROM $wpdb->term_taxonomy taxo
            LEFT JOIN $wpdb->terms terms ON (terms.term_id = taxo.term_id)
            WHERE taxo.taxonomy LIKE 'pa\_%'
        ";
		$data = $wpdb->get_results( $sql );

		// Load the taxonomies in as child terms
		foreach ( $data as $datum ) {
			$thisAttribute = $this->masterAttributeList->findByName( substr( $datum->taxonomy, 3 ) );
			if ( $thisAttribute != null ) {
				$term                      = new stdClass();
				$term->taxonomy            = $thisAttribute->name;
				$term->name                = $datum->name;
				$term->slug                = $datum->slug;
				$thisAttribute->children[] = $term;
			}
		}

	}

	public function getProductByCategory( $parent, $remotecat ) {
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
			$categories = array();
			foreach ( $parent->categories->categories as $key => $cat ) {
				/*
				if (property_exists($cat, 'parent_category') && is_object($cat->parent_category) && property_exists($cat->parent_category, 'title')) {
				if (!in_array($cat->parent_category->id, $categories)) {
				array_push($categories, $cat->id);
				}
				} else {*/
				if ( ! in_array( $cat->id, $categories ) ) {
					array_push( $categories, $cat->id );
					$taxonomies = array(
						'taxonomy' => 'product_cat',
					);

					$args = array( 'child_of' => $cat->id );

					$childCategories = get_terms( $taxonomies, $args );

					if ( is_array( $childCategories ) && count( $childCategories ) > 0 ) {
						foreach ( $childCategories as $key => $value ) {
							array_push( $categories, $value->term_id );
						}
					}
				}
			}
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
		$where      .= " AND P.post_type='product' AND P.post_status ='publish' AND (tax.taxonomy='category' OR tax.taxonomy='product_cat')";
		$SQL        = "SELECT P.ID,GROUP_CONCAT(T.name) as category, GROUP_CONCAT(T.slug) as category_slug, GROUP_CONCAT(tax.taxonomy) as taxtype {$postmetaSelect} FROM {$table} P
               LEFT JOIN {$relationTable} rel ON rel.object_id = P.id
               LEFT JOIN {$taxonomyTable} tax ON tax.term_taxonomy_id = rel.term_taxonomy_id
               LEFT JOIN {$termTable} T ON T.term_id = tax.term_id
               {$postmetaJoin}
               {$where} GROUP BY P.ID {$limitQuery}";
		$Products   = $wpdb->get_results( $SQL );

		/*
		=========================================================================================================================================================================================
		 * $args = array(
		'limit' => -1,
		'status' => 'publish',
		'category' => $categories,
		);
		if ($parent->has_product_range) {
		$args['limit'] = $parent->product_limit_high - $parent->product_limit_low;
		$args['offset'] = $parent->product_limit_low;
		}
		$products = wc_get_products($args);
		=========================================================================================================================================================================================*/

		foreach ( $Products as $index => $productId ) {
			if ( $index % 100 == 0 ) {
				$parent->logActivity( 'Converting master product list...' . round( $index / count( $Products ) * 100 ) . '%' );
			}
			/**
			 * @Info: If Product Limit reaches then don't insert more product in feed
			 */
			if ( $parent->no_more_product_in_feed == true ) {
				return true;
			}
			/*
			 echo "<pre>";
			print_r(wp_get_post_terms($productId->ID, 'product_brand'));
			echo "</pre>";
			exit();*/
			$productData                               = $this->getWooProductData( $productId->ID );
			$item                                      = new EXCPF_PAProductW(); // extends PAProduct (basicfeed.php)
			$item->id                                  = $productData->get_id();
			$item->wc_attributes                       = array();
			$item->attributes['id']                    = $item->id;
			$item->attributes['item_group_id']         = ( $productData->get_parent_id() == 0 ) ? null : $productData->get_parent_id();
			$item->attributes['title']                 = $productData->get_name();
			$item->attributes['sku']                   = $productData->get_sku();
			$item->attributes['isVariable']            = $productData->is_type( 'variable' );
			$item->attributes['isVariation']           = ( $productData->get_parent_id() == 0 ) ? false : true;
			$item->description_long                    = substr( strip_shortcodes( strip_tags( $productData->get_description() ) ), 0, 5000 );
			$item->description_short                   = substr( strip_shortcodes( strip_tags( $productData->get_short_description() ) ), 0, 5000 );
			$item->attributes['description_real']      = $productData->get_short_description();
			$item->attributes['description_real_long'] = $productData->get_description();
			$item->attributes['description_short']     = substr( strip_shortcodes( strip_tags( $productData->get_short_description() ) ), 0, 5000 );
			$item->attributes['description_long']      = substr( strip_shortcodes( strip_tags( $productData->get_description() ) ), 0, 5000 );
			$item->attributes['currency']              = $parent->currency;
			$item->attributes['weight_unit']           = $parent->weight_unit;
			$item->attributes['dimension_unit']        = $parent->dimension_unit;
			$item->attributes['link']                  = get_permalink( $item->id );
			$item->attributes['thumb_ID']              = get_post_thumbnail_id( $item->id );
			$item->attributes['category_ids']          = $productData->get_category_ids();
			$item->attributes['category_id']           = $item->attributes['category_ids'][0];
			$item->attributes['localCategory']         = strip_tags( wc_get_product_category_list( $item->id ) );
			$thumb                                     = wp_get_attachment_image_src( $item->attributes['thumb_ID'], 'small-feature' );
			$item->attributes['feature_imgurl']        = $thumb['0'];
			$item->attributes['condition']             = 'New';
			$item->attributes['regular_price']         = $productData->get_regular_price();
			$item->attributes['sale_price']            = $productData->get_sale_price();
			$item->attributes['has_sale_price']        = $productData->get_sale_price() ? 0 : 1;
			$item->attributes['stock_status']          = $productData->get_stock_status();
			$item->attributes['stock_quantity']        = $productData->get_stock_quantity(); // needs a non-zero-length -K
			$item->attributes['manage_stock']          = $productData->get_manage_stock();
			$item->attributes['backorders']            = $productData->get_backorders();
			$item->attributes['weight']                = null; // should not be 0
			$item->attributes['valid']                 = true;
			$item->attributes['sale_price_dates_to']   = $productData->get_date_on_sale_to();
			$item->attributes['sale_price_dates_from'] = $productData->get_date_on_sale_from();

			foreach ( $productData->get_attributes() as $key => $value ) {
				$ValueOnFly                                                       = wc_get_product_terms( $item->id, $value->get_name(), array( 'fields' => 'names' ) );
				$item->attributes[ str_replace( 'pa_', '', $value->get_name() ) ] = $ValueOnFly[0];
			}

			/*
			 * @Info : get the value of product attributes given from command
			 * @function getValue();
			 * @class Parent Class
			 */

			if ( is_array( $parent->attributeDefaults ) ) {
				foreach ( $parent->attributeDefaults as $thisDefault ) {
					if ( $thisDefault->stage == 0 && ! $thisDefault->isRuled && ! isset( $item->attributes[ $thisDefault->attributeName ] ) ) {
						$item->attributes[ $thisDefault->attributeName ] = $thisDefault->getValue( $item );
					}
				}
			}
			// Assign related data (like brands and tags)
			if ( is_array( $this->relatedData ) ) {
				foreach ( $this->relatedData as $index => $relation ) {
					$relation->check( $index, $item );
				}
			}

			// API calls now optional... disabled by default

			if ( $parent->force_wc_api ) {
				if ( $this->lowerWcVersion == true ) {
					$attachments = $productData->get_gallery_attachment_ids();
				} else {
					$attachments = $productData->get_gallery_image_ids();
				}

				$attachments = array_diff( $attachments, array( $item->attributes['thumb_ID'] ) );
				if ( $attachments && is_array( $attachments ) ) {
					foreach ( $attachments as $attachment ) {
						$thumb  = ( wp_get_attachment_image_src( $attachment, 'small-feature' ) );
						$imgurl = $thumb['0'];
						if ( strlen( $imgurl ) > 0 ) {
							$item->imgurls[] = $imgurl;
						}
					}
				}
				$this->loadProductFromWCAPI( $parent, $item, $productData );
			}

			/**
			 * @Info : Extract GMC Value here if the product has it
			 * If woocommerce-google-merchant-center, we have to do extra work to extract data
			 */
			if ( ( property_exists( $this, 'gmc_active' ) ) && property_exists( $productData, 'gmc_value' ) && ( strlen( $productData->gmc_value ) > 0 ) ) {
				$gmc_attributes = unserialize( $productData->gmc_value );
				if ( is_array( $gmc_attributes ) ) {
					foreach ( $gmc_attributes as $key => $this_attribute ) {
						// Use this_attribute if no overrides force us to use any attributes OR if there are overrides and in_array()
						if ( ( count( $parent->gmc_attributes ) == 0 ) || ( in_array( $key, $parent->gmc_attributes ) ) ) {
							switch ( $key ) {
								case 'description':
									if ( strlen( $item->description_short ) == 0 ) {
										$item->description_short = $this_attribute;
									}
									if ( strlen( $item->description_long ) == 0 ) {
										$item->description_long = $this_attribute;
									}
									break;
								case 'product_type':
									$item->attributes['product_type'] = $this_attribute;
									break;
								case 'category':
									$item->attributes['localCategory'] = $this_attribute;
									break;
								case 'condition':
									$item->attributes['condition'] = $this_attribute;
									break;
								case 'shipping_weight':
									$item->attributes['weight'] = $this_attribute;
									break;
								default:
									$item->attributes[ $key ] = $this_attribute;
							}
						}
					}
				}
			} /* GMC WORK ENDS */

			// Fetch any default attributes Stage 5 (Mapping 3.1)
			if ( is_array( $parent->attributeDefaultStages ) && $parent->attributeDefaultStages[5] > 0 ) {
				foreach ( $parent->attributeDefaults as $thisDefault ) {
					if ( $thisDefault->stage == 5 && ! $thisDefault->isRuled ) {
						$item->attributes[ $thisDefault->attributeName ] = $thisDefault->getValue( $item );
					}
				}
			}

			$item->attribute_details = array();
			$item->taxonomies        = new EXCPF_PTaxonomyList();

			if ( is_array( $item->attribute_details ) ) {
				foreach ( $item->attribute_details as $this_attribute ) {
					$this_attribute = explode( '=', $this_attribute );
					if ( count( $this_attribute ) > 1 ) {
						$taxonomy        = new stdClass();
						$taxonomy->name  = substr( $this_attribute[0], 3 );
						$taxonomy->slug  = $this_attribute[1];
						$taxonomy->value = $this_attribute[2];
						$item->taxonomies->add( $taxonomy );
					}
				}
			}

			$this->postmetalookup( $item, $parent );
			if ( isset( $item->attributes['to_ls_product_json'] ) ) {
				$to_ls_product_json = maybe_unserialize( $item->attributes['to_ls_product_json'] );
				if ( isset( $to_ls_product_json['response'] ) ) {
					if ( isset( $to_ls_product_json['response']['brands'][0] ) ) {
						$brands                    = $to_ls_product_json['response']['brands'][0]['name'];
						$item->attributes['brand'] = $brands;
					}
				}
			}

			/*
			 *
			 * @Info: Hanlde Each Items and Write in Feed File
			 * @function Class EXCPF_PBasicFeed->handleproduct()
			 * @param $item : Preapred products data
			 * */
			$item->variation_ids = $productData->get_children();
			if ( $item->attributes['isVariable'] && property_exists( $parent, 'no_variation' ) && $parent->no_variation == false ) {
				$cloned = clone $item;
				if ( $parent->exclude_parent == false ) {
					$this->hideStockValid( $item );
					$parent->handleProduct( $item );
				}
				$this->applyWCAttributes( $item );
				$this->expandProduct( $cloned, $parent, $productData->get_available_variations() );
				$cloned = null;
				unset( $cloned );
			} else {

				if ( is_array( $productData->get_attributes() ) && count( $productData->get_attributes() ) > 0 ) {
					foreach ( $productData->get_attributes() as $key => $attributevalue ) {
						$value = $productData->get_attribute( $key );
						if ( strpos( $key, 'pa_' ) == 0 ) {
							$attribute = str_replace( 'pa_', '', $key );
						} elseif ( strpos( $key, 'pa_attribute' ) == 0 ) {
							$attribute = str_replace( 'pa_attribute', '', $key );
						} else {
							$attribute = str_replace( 'pa_', '', $key );
						}
						$item->attributes[ $attribute ] = $value;

					}
				}

				/*
				 If product has variable attribute */
				/*
				if (is_array($productData->get_attributes()) && count($productData->get_attributes()) > 0 && $productData->get_parent_id() > 0) {
				foreach ($productData->get_attributes() as $key => $attributevalue) {
				$value = $productData->get_attribute($key);
				if (strpos($key, 'pa_') == 0) {
				$attribute = str_replace("pa_", "", $key);
				} elseif (strpos($key, 'pa_attribute') == 0) {
				$attribute = str_replace("pa_attribute", "", $key);
				} else {
				$attribute = str_replace("pa_", "", $key);
				}
				$item->attributes[$attribute] = $value;

				}
				} else {
				if (is_array($productData->get_attributes()) && count($productData->get_attributes()) > 0) {
				foreach ($productData->get_attributes() as $key => $attributevalue) {
				$value = $productData->get_attribute($key);
				if (strpos($key, 'pa_') == 0) {
				$attribute = str_replace("pa_", "", $key);
				} elseif (strpos($key, 'pa_attribute') == 0) {
				$attribute = str_replace("pa_attribute", "", $key);
				} else {
				$attribute = str_replace("pa_", "", $key);
				}
				$item->attributes[$attribute] = $value;

				}
				}

				}*/

				$item->parent_manage_stock = 'no';
				$this->applyWCAttributes( $item );
				$this->hideStockValid( $item );
				$parent->handleProduct( $item );
			}
		} /* Foreach Ends Here */

		return true;
	}

	public function loadProductsCustom( $parent ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'cpf_customfeeds';

		if ( $parent->has_product_range ) {
			$limit = 'LIMIT ' . $parent->product_limit_low . ', ' . ( $parent->product_limit_high - $parent->product_limit_low );
		} else {
			$limit = '';
		}

		$sql = "
            SELECT cpf.product_id as ID , cpf.product_title as post_title  , cpf.category_name , cpf.product_type , cpf.product_attributes , cpf.product_variation_ids as variation_ids , cpf.remote_category , cpf.category as category_ids,posts.post_content
             FROM {$table_name} as cpf
             LEFT JOIN {$wpdb->prefix}posts as posts ON posts.ID  = cpf.ID
             {$limit}
              ";

		$this->products = $wpdb->get_results( $sql );
		if ( count( $this->products ) == 0 ) {
			$parent->message .= 'ProductList returned Zero results';
		}
	} //loadProducts

	/*
	 *
	 * @Info : This function structure will be used in later version
	 *
	 */
	public function getCustomProductIds( $parent, $feed_id ) {
		if ( $parent->has_product_range ) {
			$limit = 'LIMIT ' . $parent->product_limit_low . ', ' . ( $parent->product_limit_high - $parent->product_limit_low );
		} else {
			$limit = '';
		}
		global $wpdb;
		$table_name = $wpdb->prefix . 'cpf_customfeeds';
		$productIds = $wpdb->get_results( $wpdb->prepare( "SELECT product_id FROM {$table_name} WHERE feed_identifier = %s $limit", array( $feed_id ) ) );
		if ( is_array( $productIds ) && count( $productIds ) > 0 ) {
			return $productIds;
		} else {
			return null;
		}
	}

	public function getWooProductData( $id ) {
		global $woocommerce;
		if ( $woocommerce != null ) {
			$wc_version = explode( '.', $woocommerce->version );
			if ( ( $wc_version[0] <= 2 ) ) {
				$this->lowerWcVersion = true;
				$productDataByID      = get_product( $id );
				if ( is_object( $productDataByID ) && ! empty( $productDataByID ) ) {
					return $productDataByID;
				}

				return null;
			} else {
				$this->lowerWcVersion = false;
				$productDataByID      = wc_get_product( $id );
				if ( is_object( $productDataByID ) && ! empty( $productDataByID ) ) {
					return $productDataByID;
				}

				return null;
			}
		}

		return null;
	}

	public function getCustomProducts( $parent, $remote_category, $feed_id ) {
		global $wpdb, $woocommerce, $pfcore;
		$parent->logActivity( 'Retrieving product list from database' );
		$master_product_list                      = array();
		$this->woocommerce_manage_stock           = get_option( 'woocommerce_manage_stock' );
		$this->woocommerce_notify_no_stock_amount = get_option( 'woocommerce_notify_no_stock_amount' );

		$productIdArray              = $this->getCustomProductIds( $parent, $feed_id );
		$this->cpf_custom_local_cats = array();
		if ( is_array( $productIdArray ) && count( $productIdArray ) > 0 ) {
			foreach ( $productIdArray as $index => $productID ) {
				if ( $index % 100 == 0 ) {
					$parent->logActivity( 'Converting master product list...' . round( $index / count( $productIdArray ) * 100 ) . '%' );
				}
				array_push( $this->cpf_custom_local_cats, $productID->product_id );
				$productData = $this->getWooProductData( $productID->product_id );

				$item                                  = new EXCPF_PAProductW(); // extends PAProduct (basicfeed.php)
				$item->id                              = $productID->product_id;
				$item->wc_attributes                   = array();
				$item->attributes['id']                = $productID->product_id;
				$item->attributes['item_group_id']     = ( $productData->get_parent_id() == 0 ) ? null : $productData->get_parent_id();
				$item->attributes['title']             = $productData->get_name();
				$item->attributes['sku']               = $productData->get_sku();
				$item->attributes['isVariable']        = $productData->is_type( 'variable' );
				$item->attributes['isVariation']       = ( $productData->get_parent_id() == 0 ) ? false : true;
				$item->attributes['description_long']  = $productData->get_description();
				$item->description_long                = $productData->get_description();
				$item->description_short               = $productData->get_short_description();
				$item->attributes['description_short'] = $productData->get_short_description();
				$item->attributes['currency']          = $parent->currency;
				$item->attributes['weight_unit']       = $parent->weight_unit;
				$item->attributes['dimension_unit']    = $parent->dimension_unit;
				$item->attributes['link']              = get_permalink( $productID->product_id );
				$item->attributes['thumb_ID']          = get_post_thumbnail_id( $productID->product_id );
				$thumb                                 = wp_get_attachment_image_src( $item->attributes['thumb_ID'], 'small-feature' );
				$item->attributes['feature_imgurl']    = $thumb['0'];

				$item->attributes['condition']      = 'New';
				$item->attributes['regular_price']  = $productData->get_regular_price() ? $productData->get_regular_price() : 0;
				$item->attributes['sale_price']     = $productData->get_sale_price() ? $productData->get_sale_price() : 0;
				$item->attributes['has_sale_price'] = $productData->get_sale_price() ? 1 : 0;
				$item->attributes['stock_status']   = $productData->get_stock_status();
				$item->attributes['stock_quantity'] = $productData->get_stock_quantity(); // needs a non-zero-length -K
				$item->attributes['manage_stock']   = $productData->get_manage_stock();
				$item->attributes['backorders']     = $productData->get_backorders();
				$item->attributes['weight']         = null; // should not be 0
				$item->attributes['valid']          = true;

				$item->attributes['sale_price_dates_to']   = $productData->get_date_on_sale_to();
				$item->attributes['sale_price_dates_from'] = $productData->get_date_on_sale_from();

				/*
				 * @Info : get the value of product attributes given from command
				 * @function getValue();
				 * @class Parent Class
				 */
				if ( is_array( $parent->attributeDefaults ) ) {
					foreach ( $parent->attributeDefaults as $thisDefault ) {
						if ( $thisDefault->stage == 0 && ! $thisDefault->isRuled && ! isset( $item->attributes[ $thisDefault->attributeName ] ) ) {
							$item->attributes[ $thisDefault->attributeName ] = $thisDefault->getValue( $item );
						}
					}
				}

				// Assign related data (like brands and tags)
				if ( is_array( $this->relatedData ) ) {
					foreach ( $this->relatedData as $index => $relation ) {
						$relation->check( $index, $item );
					}
				}

				// API calls now optional... disabled by default
				if ( $parent->force_wc_api ) {
					if ( $this->lowerWcVersion == true ) {
						$attachments = $productData->get_gallery_attachment_ids();
					} else {
						$attachments = $productData->get_gallery_image_ids();
					}

					$attachments = array_diff( $attachments, array( $thumb_ID ) );
					if ( $attachments && is_array( $attachments ) ) {
						foreach ( $attachments as $attachment ) {
							$thumb  = ( wp_get_attachment_image_src( $attachment, 'small-feature' ) );
							$imgurl = $thumb['0'];
							if ( strlen( $imgurl ) > 0 ) {
								$item->imgurls[] = $imgurl;
							}
						}
					}
					$this->loadProductFromWCAPI( $parent, $item, $productData );
				}

				/* If product has variable attribute */
				if ( is_array( $productData->get_attributes() ) && count( $productData->get_attributes() ) > 0 && $productData->get_parent_id() > 0 ) {
					foreach ( $productData->get_attributes() as $key => $attributevalue ) {
						$value = $productData->get_attribute( $key );
						if ( strpos( $key, 'pa_' ) == 0 ) {
							$attribute = str_replace( 'pa_', '', $key );
						} elseif ( strpos( $key, 'pa_attribute' ) == 0 ) {
							$attribute = str_replace( 'pa_attribute', '', $key );
						} else {
							$attribute = str_replace( 'pa_', '', $key );
						}
						$item->attributes[ $attribute ] = $value;

					}
				} else {
					if ( is_array( $productData->get_attributes() ) && count( $productData->get_attributes() ) > 0 ) {
						foreach ( $productData->get_attributes() as $key => $attributevalue ) {
							$value = $productData->get_attribute( $key );
							if ( strpos( $key, 'pa_' ) == 0 ) {
								$attribute = str_replace( 'pa_', '', $key );
							} elseif ( strpos( $key, 'pa_attribute' ) == 0 ) {
								$attribute = str_replace( 'pa_attribute', '', $key );
							} else {
								$attribute = str_replace( 'pa_', '', $key );
							}
							$item->attributes[ $attribute ] = $value;

						}
					}
				}

				/*
				 * @Info : Extract GMC Value here if the product has it
				 *
				 */
				// For now escape it and do it later

				// Fetch any default attributes Stage 5 (Mapping 3.1)
				if ( is_array( $parent->attributeDefaultStages ) && $parent->attributeDefaultStages[5] > 0 ) {
					foreach ( $parent->attributeDefaults as $thisDefault ) {
						if ( $thisDefault->stage == 5 && ! $thisDefault->isRuled ) {
							$item->attributes[ $thisDefault->attributeName ] = $thisDefault->getValue( $item );
						}
					}
				}

				$item->attribute_details = array();

				$item->taxonomies = new EXCPF_PTaxonomyList();

				if ( is_array( $item->attribute_details ) ) {
					foreach ( $item->attribute_details as $this_attribute ) {
						$this_attribute = explode( '=', $this_attribute );
						if ( count( $this_attribute ) > 1 ) {
							$taxonomy        = new stdClass();
							$taxonomy->name  = substr( $this_attribute[0], 3 );
							$taxonomy->slug  = $this_attribute[1];
							$taxonomy->value = $this_attribute[2];
							$item->taxonomies->add( $taxonomy );
						}
					}
				}

				$this->postmetalookup( $item, $parent );

				/* Hanlde */
				$item->variation_ids = $productData->get_children();

				if ( $item->attributes['isVariable'] && property_exists( $parent, 'no_variation' ) && $parent->no_variation == false ) {
					$this->applyWCAttributes( $item );
					// $this->expandProduct($item, $parent);
					$this->expandProduct( $item, $parent, $productData->get_available_variations() );
				} else {
					$item->parent_manage_stock = 'no';
					$this->applyWCAttributes( $item );
					$this->hideStockValid( $item );
					$parent->handleProductCustom( $item );
				}
			}
		}
	}

	public function getProductList( $parent, $remote_category = null ) {
		global $wpdb;
		global $pfcore;

		$parent->logActivity( 'Retrieving product list from database' );
		if ( $pfcore->feedType ) {
			$this->getProductListCustom( $parent, $remote_category );
		} else {
			$this->getProductListDefault( $parent, $remote_category );
		}
	}

	public function getProductListDefault( $parent, $remote_category = null ) {
		global $woocommerce;
		if ( $this->products == null ) {
			$this->loadProducts( $parent );
		}
		$master_product_list                      = array();
		$this->woocommerce_manage_stock           = get_option( 'woocommerce_manage_stock' );
		$this->woocommerce_notify_no_stock_amount = get_option( 'woocommerce_notify_no_stock_amount' );

		// ********************************************************************
		// Convert the WP_Product List into a Cart-Product Master List (ListItems)
		// ********************************************************************
		if ( is_array( $this->products ) ) {
			foreach ( $this->products as $index => $prod ) {

				if ( $index % 100 == 0 ) {
					$parent->logActivity( 'Converting master product list...' . round( $index / count( $this->products ) * 100 ) . '%' );
				}

				// if skip product with non-matching category
				$category_ids = explode( ',', $prod->category_ids );
				$skip         = true;
				if ( is_array( $category_ids ) ) {
					foreach ( $category_ids as $id ) {
						if ( $parent->categories->containsCategory( $id ) ) {
							$skip = false;
							break;
						}
					}
				}

				if ( $parent->force_all_categories ) {
					$skip = false;
				}

				if ( $skip ) {
					continue;
				}

				$category_names = explode( ',', $prod->category_names );

				// if parent has variations, do we need it?
				if ( $parent->categories->containsCategory( $prod->variation_ids ) ) {
					continue;
				}

				// Duplicate check
				if ( $parent->ignoreDuplicates ) {
					$skip_this_item                   = isset( $master_product_list[ $prod->ID ] );
					$master_product_list[ $prod->ID ] = 1;
					if ( $skip_this_item ) {
						continue;
					}
				}

				// Prepare the item
				$item = new EXCPF_PAProductW(); // extends PAProduct (basicfeed.php)

				// Basics
				$item->id                      = $prod->ID;
				$item->attributes['id']        = $prod->ID;
				$item->attributes['title']     = $prod->post_title;
				$item->attributes['post_date'] = $prod->post_date;

				$item->taxonomy                  = $category_names;
				$item->attributes['isVariable']  = strtolower( $prod->product_type ) == 'variable';
				$item->attributes['isVariation'] = false;
				$item->description_short         = substr( strip_shortcodes( strip_tags( $prod->post_excerpt ) ), 0, 5000 );
				if ( isset( $item->description_short ) ) {
					$item->attributes['description_short'] = $item->description_short;
				}

				$item->description_long = substr( strip_shortcodes( strip_tags( $prod->post_content ) ), 0, 5000 );
				if ( isset( $item->description_long ) ) {
					$item->attributes['description_long'] = $item->description_long;
				}

				$item->wc_attributes       = array(); // This will almost always get replaced by postmeta
				$item->attributes['valid'] = true;

				// Fetch any default attributes Stage 0 (Mapping 3.0)
				if ( is_array( $parent->attributeDefaults ) ) {
					foreach ( $parent->attributeDefaults as $thisDefault ) {
						if ( $thisDefault->stage == 0 && ! $thisDefault->isRuled && ! isset( $item->attributes[ $thisDefault->attributeName ] ) ) {
							$item->attributes[ $thisDefault->attributeName ] = $thisDefault->getValue( $item );
						}
					}
				}

				// Assign related data (like brands and tags)
				if ( is_array( $this->relatedData ) ) {
					foreach ( $this->relatedData as $index => $relation ) {
						$relation->check( $index, $item );
					}
				}

				$item->attributes['currency']       = $parent->currency;
				$item->attributes['weight_unit']    = $parent->weight_unit;
				$item->attributes['dimension_unit'] = $parent->dimension_unit;

				$item->attributes['category']       = str_replace( '.and.', ' & ', str_replace( '.in.', ' > ', $remote_category ) );
				$item->attributes['product_type']   = str_replace( '.and.', ' & ', str_replace( '.in.', ' > ', $remote_category ) );
				$item->attributes['localCategory']  = str_replace( '.and.', ' & ', str_replace( '.in.', ' > ', $category_names[0] ) );
				$item->attributes['localCategory']  = str_replace( '|', '>', $item->attributes['localCategory'] );
				$item->attributes['category_id']    = $category_ids[0];
				$item->attributes['category_ids']   = $category_ids;
				$item->attributes['link']           = get_permalink( $prod->ID );
				$item->attributes['thumb_ID']       = get_post_thumbnail_id( $prod->ID );
				$thumb                              = wp_get_attachment_image_src( $item->attributes['thumb_ID'], 'small-feature' );
				$item->attributes['feature_imgurl'] = $thumb['0'];

				$item->attributes['condition']      = 'New';
				$item->attributes['regular_price']  = 0;
				$item->attributes['has_sale_price'] = false;
				$item->attributes['stock_status']   = 1;
				$item->attributes['stock_quantity'] = 0; // needs a non-zero-length -K
				$item->attributes['weight']         = null; // should not be 0

				// API calls now optional... disabled by default
				if ( $parent->force_wc_api ) {
					if ( $woocommerce != null ) {
						$wc_version = explode( '.', $woocommerce->version );
						if ( ( $wc_version[0] <= 2 ) ) {
							$product     = get_product( $prod->ID ); // WooCommerce - get product by id
							$attachments = $product->get_gallery_attachment_ids();
						} else {
							$product     = new WC_Product( $prod->ID );
							$attachments = $product->get_gallery_image_ids();
						}
					}
					// $attachments = array_diff($attachments, array($thumb_ID));
					if ( $attachments && is_array( $attachments ) ) {
						foreach ( $attachments as $attachment ) {
							$thumb  = ( wp_get_attachment_image_src( $attachment, 'small-feature' ) );
							$imgurl = $thumb['0'];
							if ( strlen( $imgurl ) > 0 ) {
								$item->imgurls[] = $imgurl;
							}
						}
					}
					$this->loadProductFromWCAPI( $parent, $item, $product );
				}

				// Attributes (General) (Deprecated)
				if ( ! $parent->allow_attribute_details || strlen( $prod->attribute_list ) == 0 ) {
					$attributes = array();
				} else {
					$attributes = unserialize( $prod->attribute_list );
				}

				if ( is_array( $attributes ) ) {
					foreach ( $attributes as $attindex => $this_attribute ) {
						if ( isset( $this_attribute['name'] ) && ( strlen( $this_attribute['name'] ) > 0 ) && ( strpos( $this_attribute['name'], 'pa_' ) === false ) ) {
							$item->attributes[ $this_attribute['name'] ] = $this_attribute['value'];
						}
					}
				}

				/*
				//Attributes (Detailed)
				//The attribute_details in the form:
				//  pa_attribute=slug=example,pa_attribute2=slug2=example2,pa_attribute3=slug3=example3
				//convert to:
				//  item->attributes[attribute]=example; item->attributes[attribute2]=example2; etc
				if ($parent->allow_attributes)
				$item->attribute_details = explode(',', $prod->attribute_details);
				else
				$item->attribute_details = array();

				foreach($item->attribute_details as $this_attribute) {
				$this_attribute = explode('=', $this_attribute);
				if (count($this_attribute) > 1) {
				$item->attributes[substr($this_attribute[0], 3)] = $this_attribute[2];
				//if ($parent->create_attribute_slugs)
				$item->attributes['_' . $this_attribute[0]] = $this_attribute[1];
				}
				 */

				if ( $parent->allow_attributes ) {
					$item->attribute_details = explode( ',', $prod->attribute_details );
				} else {
					$item->attribute_details = array();
				}

				$item->taxonomies = new EXCPF_PTaxonomyList();
				if ( is_array( $item->attribute_details ) ) {
					foreach ( $item->attribute_details as $this_attribute ) {
						$this_attribute = explode( '=', $this_attribute );
						if ( is_array( $this_attribute ) && count( $this_attribute ) > 1 ) {
							$taxonomy        = new stdClass();
							$taxonomy->name  = substr( $this_attribute[0], 3 );
							$taxonomy->slug  = isset( $this_attribute[1] ) ? $this_attribute[1] : null;
							$taxonomy->value = isset( $this_attribute[2] ) ? $this_attribute[2] : null;
							$item->taxonomies->add( $taxonomy );
						}
					}
				}

				$this->postmetaLookup( $item, $parent );
				// $this->getTerms($item->id, $item->wc_attributes);

				$item->variation_ids = $prod->variation_ids;

				// $item->fetch_meta_attributes(); //Old

				/*
				CustomFields and max_custom_field are ignored anyway, so removing
				foreach($this->custom_fields as $custom_field)
				if ($custom_field->post_id == $item->id)
				$item->attributes[$custom_field->meta_key] = $custom_field->meta_value;
				 */

				// If woocommerce-google-merchant-center, we have to do extra work to extract data
				if ( ( $this->gmc_active ) && ( strlen( $prod->gmc_value ) > 0 ) ) {

					$gmc_attributes = unserialize( $prod->gmc_value );
					if ( is_array( $gmc_attributes ) ) {
						foreach ( $gmc_attributes as $key => $this_attribute ) {
							// Use this_attribute if no overrides force us to use any attributes OR if there are overrides and in_array()
							if ( ( count( $parent->gmc_attributes ) == 0 ) || ( in_array( $key, $parent->gmc_attributes ) ) ) {
								switch ( $key ) {
									case 'description':
										if ( strlen( $item->description_short ) == 0 ) {
											$item->description_short = $this_attribute;
										}

										if ( strlen( $item->description_long ) == 0 ) {
											$item->description_long = $this_attribute;
										}

										break;
									case 'product_type':
										$item->attributes['product_type'] = $this_attribute;
										break;
									case 'category':
										$item->attributes['localCategory'] = $this_attribute;
										break;
									case 'condition':
										$item->attributes['condition'] = $this_attribute;
										break;
									case 'shipping_weight':
										$item->attributes['weight'] = $this_attribute;
										break;
									default:
										$item->attributes[ $key ] = $this_attribute;
								}
							}
						}
					}
				}

				// Fetch any default attributes Stage 5 (Mapping 3.1)
				if ( is_array( $parent->attributeDefaultStages ) && $parent->attributeDefaultStages[5] > 0 ) {
					foreach ( $parent->attributeDefaults as $thisDefault ) {
						if ( $thisDefault->stage == 5 && ! $thisDefault->isRuled ) {
							$item->attributes[ $thisDefault->attributeName ] = $thisDefault->getValue( $item );
						}
					}
				}
				$this->applyWCAttributes( $item );
				$this->getTerms( $item );
				/*
				if($item->id==526){
					echo "<pre>";
					print_r($item);
					echo "</pre>";
					exit;
				}*/

				$is_ammoaggxml = $parent->aggregateProviders[0]->providerName == 'AmmoAggXml';
				if ( $item->attributes['isVariable'] ) {
					$this->expandProductv3( $item, $parent );
				} else {
					$item->parent_manage_stock = 'no';
					$this->hideStockValid( $item );
					if ( $is_ammoaggxml ) {
						$args = array(
							'check'           => $parent->aggregateProviders[0]->check,
							'ammo_first_time' => $parent->aggregateProviders[0]->first_time,
						);
						$parent->handleProduct( $item, $args );
					} else {
						$parent->handleProduct( $item );
					}
					if ( is_array( $item->attributes ) ) {
						foreach ( $item->attributes as &$x ) {
							unset( $x );
						}
					}

					unset( $item );
				}
			}
		}

		$parent->logActivity( 'ProductList successfully generated.' );

	}

	public function getProductListCustom( $parent, $remote_category ) {

		global $wpdb, $woocommerce, $pfcore;
		$parent->logActivity( 'Retrieving product list from database' );
		if ( $this->products == null ) {
			$this->loadProductsCustom( $parent );
		}

		$master_product_list = array();

		$this->woocommerce_manage_stock           = get_option( 'woocommerce_manage_stock' );
		$this->woocommerce_notify_no_stock_amount = get_option( 'woocommerce_notify_no_stock_amount' );

		// ********************************************************************
		// Convert the WP_Product List into a Cart-Product Master List (ListItems)
		// ********************************************************************

		if ( is_array( $this->products ) ) {
			foreach ( $this->products as $index => $prod ) {
				$this->cpf_custom_local_cats[]  = $prod->category_ids;
				$this->cpf_custom_remote_cats[] = $prod->remote_category;
				if ( $index % 100 == 0 ) {
					$parent->logActivity( 'Converting master product list...' . round( $index / count( $this->products ) * 100 ) . '%' );
				}

				$product_description = $wpdb->get_row( $wpdb->prepare( "SELECT post_content,post_excerpt from {$wpdb->prefix}posts where ID = " . $prod->ID ) );

				// $category_ids = $prod->category_ids;
				$category_ids = explode( ',', $prod->category_ids );
				// $category_names = $prod->category_name;
				$category_names = explode( ',', $prod->category_name );
				// Prepare the item

				$item = new EXCPF_PAProductW(); // extends PAProduct (basicfeed.php)

				// Basics
				$item->id                  = $prod->ID;
				$item->attributes['id']    = $prod->ID;
				$item->attributes['title'] = $prod->post_title;
				// $item->attributes['post_date'] = $prod->post_date;
				$item->taxonomy                  = $category_names;
				$item->attributes['isVariable']  = $prod->product_type == 'variable';
				$item->attributes['isVariation'] = false;
				$item->description_short         = substr( strip_shortcodes( strip_tags( $product_description->post_excerpt ) ), 0, 5000 );
				// $item->description_short = "short Test description";
				if ( isset( $item->description_short ) ) {
					$item->attributes['description_short'] = $item->description_short;
				}

				$item->description_long = substr( strip_shortcodes( strip_tags( $product_description->post_content ) ), 0, 5000 );
				// $item->description_long ="Long test descritpion";
				if ( isset( $item->description_long ) ) {
					$item->attributes['description_long'] = $item->description_long;
				}

				$item->wc_attributes       = array(); // This will almost always get replaced by postmeta
				$item->attributes['valid'] = true;
				// Fetch any default attributes Stage 0 (Mapping 3.0)
				if ( is_array( $parent->attributeDefaults ) ) {
					foreach ( $parent->attributeDefaults as $thisDefault ) {
						if ( $thisDefault->stage == 0 && ! $thisDefault->isRuled && ! isset( $item->attributes[ $thisDefault->attributeName ] ) ) {
							$item->attributes[ $thisDefault->attributeName ] = $thisDefault->getValue( $item );
						}
					}
				}

				// Assign related data (like brands and tags)
				if ( is_array( $this->relatedData ) ) {
					foreach ( $this->relatedData as $index => $relation ) {
						$relation->check( $index, $item );
					}
				}

				$item->attributes['currency']       = $parent->currency;
				$item->attributes['weight_unit']    = $parent->weight_unit;
				$item->attributes['dimension_unit'] = $parent->dimension_unit;

				$item->attributes['product_type'] = str_replace( '.and.', ' & ', str_replace( '.in.', ' > ', $prod->remote_category ) );
				$item->attributes['category']     = str_replace( '.and.', ' & ', str_replace( '.in.', ' > ', $prod->remote_category ) );
				/*
				$item->attributes['category'] = $prod->remote_category;
				$item->attributes['product_type'] = $prod->remote_category;*/
				$item->attributes['localCategory']  = str_replace( '.and.', ' & ', str_replace( '.in.', ' > ', $category_names[0] ) );
				$item->attributes['localCategory']  = str_replace( '|', '>', $item->attributes['localCategory'] );
				$item->attributes['category_id']    = $category_ids[0];
				$item->attributes['category_ids']   = $category_ids;
				$item->attributes['link']           = get_permalink( $prod->ID );
				$item->attributes['thumb_ID']       = get_post_thumbnail_id( $prod->ID );
				$thumb                              = wp_get_attachment_image_src( $item->attributes['thumb_ID'], 'small-feature' );
				$item->attributes['feature_imgurl'] = $thumb['0'];

				$item->attributes['condition']      = 'New';
				$item->attributes['regular_price']  = 0;
				$item->attributes['has_sale_price'] = false;
				$item->attributes['stock_status']   = 1;
				$item->attributes['stock_quantity'] = 0; // needs a non-zero-length -K
				$item->attributes['weight']         = null; // should not be 0
				// API calls now optional... disabled by default
				if ( $parent->force_wc_api ) {
					if ( $woocommerce != null ) {
						$wc_version = explode( '.', $woocommerce->version );
						if ( ( $wc_version[0] <= 2 ) ) {
							$product     = get_product( $prod->ID ); // WooCommerce - get product by id
							$attachments = $product->get_gallery_attachment_ids();
						} else {
							$product     = new WC_Product( $prod->ID );
							$attachments = $product->get_gallery_image_ids();
						}
					}
					$attachments = array_diff( $attachments, array( $thumb_ID ) );
					if ( $attachments && is_array( $attachments ) ) {
						foreach ( $attachments as $attachment ) {
							$thumb  = ( wp_get_attachment_image_src( $attachment, 'small-feature' ) );
							$imgurl = $thumb['0'];
							if ( strlen( $imgurl ) > 0 ) {
								$item->imgurls[] = $imgurl;
							}
						}
					}
					$this->loadProductFromWCAPI( $parent, $item, $product );
				}
				// Attributes (General) (Deprecated)
				if ( ! $parent->allow_attribute_details || strlen( $prod->attribute_list ) == 0 ) {
					$attributes = array();
				} else {
					$attributes = unserialize( $prod->attribute_list );
				}
				if ( is_array( $attributes ) ) {
					foreach ( $attributes as $attindex => $this_attribute ) {
						if ( isset( $this_attribute['name'] ) && ( strlen( $this_attribute['name'] ) > 0 ) && ( strpos( $this_attribute['name'], 'pa_' ) === false ) ) {
							$item->attributes[ $this_attribute['name'] ] = $this_attribute['value'];
						}
					}
				}

				// Attributes (Detailed)
				// The attribute_details in the form:
				// pa_attribute=slug=example,pa_attribute2=slug2=example2,pa_attribute3=slug3=example3
				// convert to:
				// item->attributes[attribute]=example; item->attributes[attribute2]=example2; etc
				if ( $parent->allow_attributes ) {
					$item->attribute_details = isset( $prod->attribute_details ) ? explode( ',', $prod->attribute_details ) : array();
				} else {
					$item->attribute_details = array();
				}

				if ( is_array( $item->attribute_details ) ) {
					foreach ( $item->attribute_details as $this_attribute ) {
						$this_attribute = explode( '=', $this_attribute );
						if ( count( $this_attribute ) > 1 ) {
							$item->attributes[ substr( $this_attribute[0], 3 ) ] = $this_attribute[2];
							// if ($parent->create_attribute_slugs)
							$item->attributes[ '_' . $this_attribute[0] ] = $this_attribute[1];
						}
					}
				}

				if ( $parent->allow_attributes && property_exists( $prod, 'attribute_details' ) ) {
					$item->attribute_details = explode( ',', $prod->attribute_details );
				} else {
					$item->attribute_details = array();
				}

				$item->taxonomies = new EXCPF_PTaxonomyList();

				if ( is_array( $item->attribute_details ) ) {
					foreach ( $item->attribute_details as $this_attribute ) {
						$this_attribute = explode( '=', $this_attribute );
						if ( count( $this_attribute ) > 1 ) {
							$taxonomy        = new stdClass();
							$taxonomy->name  = substr( $this_attribute[0], 3 );
							$taxonomy->slug  = $this_attribute[1];
							$taxonomy->value = $this_attribute[2];
							$item->taxonomies->add( $taxonomy );
						}
					}
				}

				$this->postmetaLookup( $item, $parent );

				$item->variation_ids = $prod->variation_ids;
				// $item->fetch_meta_attributes(); //Old

				/*
				CustomFields and max_custom_field are ignored anyway, so removing
				foreach($this->custom_fields as $custom_field)
				if ($custom_field->post_id == $item->id)
				$item->attributes[$custom_field->meta_key] = $custom_field->meta_value;
				 */

				// If woocommerce-google-merchant-center, we have to do extra work to extract data
				if ( property_exists( $this, 'gmc_active' ) && ( $this->gmc_active ) && ( strlen( $prod->gmc_value ) > 0 ) ) {

					$gmc_attributes = unserialize( $prod->gmc_value );
					if ( is_array( $gmc_attributes ) ) {
						foreach ( $gmc_attributes as $key => $this_attribute ) {
							// Use this_attribute if no overrides force us to use any attributes OR if there are overrides and in_array()
							if ( ( count( $parent->gmc_attributes ) == 0 ) || ( in_array( $key, $parent->gmc_attributes ) ) ) {
								switch ( $key ) {
									case 'description':
										if ( strlen( $item->description_short ) == 0 ) {
											$item->description_short = $this_attribute;
										}

										if ( strlen( $item->description_long ) == 0 ) {
											$item->description_long = $this_attribute;
										}

										break;
									case 'product_type':
										$item->attributes['product_type'] = $this_attribute;
										break;
									case 'category':
										$item->attributes['localCategory'] = $this_attribute;
										break;
									case 'condition':
										$item->attributes['condition'] = $this_attribute;
										break;
									case 'shipping_weight':
										$item->attributes['weight'] = $this_attribute;
										break;
									default:
										$item->attributes[ $key ] = $this_attribute;
								}
							}
						}
					}
				}

				// Fetch any default attributes Stage 5 (Mapping 3.1)
				if ( is_array( $parent->attributeDefaultStages ) && $parent->attributeDefaultStages[5] > 0 ) {
					foreach ( $parent->attributeDefaults as $thisDefault ) {
						if ( $thisDefault->stage == 5 && ! $thisDefault->isRuled ) {
							$item->attributes[ $thisDefault->attributeName ] = $thisDefault->getValue( $item );
						}
					}
				}
				if ( $item->attributes['isVariable'] ) {
					$this->applyWCAttributes( $item );
					$this->expandProduct( $item, $parent );
				} else {
					$item->parent_manage_stock = 'no';
					$this->applyWCAttributes( $item );
					$this->hideStockValid( $item );
					$parent->handleProductCustom( $item );

					/*
					  foreach($item->attributes as &$x)
					unset($x);
					unset($item);
					*/
				}
			}
		}

		$parent->logActivity( 'ProductList successfully generated.' );

	}

	public function hideStockValid( $item ) {
		global $pfcore;
		// Hide out of stock
		// if ( ($pfcore->manage_stock) )
		if ( ( $pfcore->hide_outofstock ) && ( $item->attributes['stock_quantity'] == 0 ) ) {
			if ( $item->attributes['stock_status'] <= 0 ) {
				$item->attributes['valid'] = false;
			}
		}

		// Reformat "valid" if necessary
		if ( isset( $item->attributes['valid'] ) && ( strcmp( $item->attributes['valid'], 'false' ) == 0 ) ) {
			$item->attributes['valid'] = false;
		}

	}

	public function expandProduct( $listitem, $parent, array $variations ) {
		global $wpdb;
		global $etcore;
		$childItem = null;
		/*
		=============================================================================================================*
		Deprecated, Newer version won't be done this way, you can remove it later after testing
		 *=============================================================================================================*
		$resultlist = array();

		 **************************** Insert any WooCommerce "Official" Variations **************************************

		$variations = explode(",", $listitem->variation_ids);
		foreach ($variations as $variation) {
		$this->insertVariation($variation, $listitem, $resultlist, $parent);
		}
		if (count($resultlist) == 0) {
		//If item has no variations, use the parent
		$listitem->parent_manage_stock = 'no';
		$resultlist[] = $listitem;
		$this->applyWCAttributes($listitem);
		}
		 ***************************************** Process the results **************************************/
		/*
		foreach ($resultlist as $index => $item) {
		if ($parent->force_wc_api) { //expand product
		//STOCK CODE
		======================================================================================================
		 *  if ( $item->attributes['product_is_in_stock'] && $item->attributes['product_stock_qty'] > 0 )
		$item->attributes['stock_status'] = 1;
		else if ($item->attributes['product_stock_qty'] == '')
		$item->attributes['stock_status'] = $item->attributes['product_is_in_stock']; //use parent's stock status if qty is blank
		else
		$item->attributes['stock_status'] = 0;

		$item->attributes['stock_status'] = $item->attributes['product_is_in_stock'];
		$item->attributes['stock_quantity'] = $item->attributes['product_stock_qty'];
		=====================================================================================================

		unset($item->attributes['product_stock_qty']); //This was causing confusion among users
		unset($item->attributes['product_is_in_stock']); //This was causing confusion among users
		} */

		// Hide out of stock
		/*
		=========================================================================================================
		 * if (($etcore->manage_stock) && ($etcore->hide_outofstock) && ($item->attributes['stock_status'] == 0))
		$item->attributes['valid'] = false;
		 ******************************************* Reformat "valid" if necessary *********************************
		if (isset($item->attributes['valid']) && (strcmp($item->attributes['valid'], 'false') == 0) )
		$item->attributes['valid'] = false;
		=========================================================================================================*/

		/*
		=============================================================================================================*
		This is totally new implementation of variation handling
		 *============================================================================================================*/
		if ( $variations && count( $variations ) > 0 ) {
			foreach ( $variations as $key => $variation ) {
				$childItem            = clone $listitem;
				$additionalAttributes = $this->getAdditionalProductAttributes( $variation['variation_id'] );

				foreach ( $additionalAttributes as $k => $value ) {

					if ( stripos( $k, '_cpf_' ) !== false && isset( $value ) ) {
						$cKey                           = str_replace( '_cpf_', '', $k );
						$childItem->attributes[ $cKey ] = $value;
					}
					$childItem->attributes[ $k ] = $value;

				}

				$childItem->id                                     = $variation['variation_id'];
				$childItem->attributes['id']                       = $variation['variation_id'];
				$childItem->attributes['sku']                      = $variation['sku'];
				$childItem->attributes['is_variation']             = true;
				$childItem->attributes['isVariation']              = true;
				$childItem->attributes['item_group_id']            = $listitem->id;
				$childItem->attributes['price']                    = $listitem->attributes['price'];
				$childItem->attributes['has_sale_price']           = $variation['display_price'] ? true : false;
				$childItem->attributes['sale_price']               = $variation['display_price'] ? $variation['display_price'] : 0.00;
				$childItem->attributes['regular_price']            = $variation['display_regular_price'] ? $variation['display_regular_price'] : 0.00;
				$childItem->attributes['variation_images_details'] = $variation['image'];
				$childItem->attributes['variation_image_id']       = $variation['image_id'];
				$childItem->attributes['in_stock']                 = ( $variation['is_in_stock'] == 1 ) ? 'in stock' : 'out of stock';
				$childItem->attributes['stock_status']             = $variation['is_in_stock'];
				if ( $childItem->attributes['manage_stock'] == 'yes' ) {
					$childItem->attributes['stock_quantity'] = $variation['max_qty'] ? $variation['max_qty'] : '';
				} else {
					$childItem->attributes['stock_quantity'] = $variation['max_qty'];
				}
				$childItem->attributes['variation_is_active']  = $variation['variation_is_active'];
				$childItem->attributes['variation_is_visible'] = $variation['variation_is_visible'];
				if ( is_array( $variation['attributes'] ) ) {
					foreach ( $variation['attributes'] as $key => $attr_values ) {
						if ( stripos( $key, 'attribute_pa_' ) !== false ) {
							$attr = str_replace( 'attribute_pa_', '', $key );
						} elseif ( stripos( $key, 'attribute_' ) !== false ) {
							$attr = str_replace( 'attribute_', '', $key );
						} else {
							$attr = $key;
						}

						/*
						if($variation['variation_id'] == 21031){
						$term_details = get_term_by('slug', $attr_values, $attr);
						echo "<pre>";
						print_r($variation['attributes']);
						var_dump($term_details);
						print_r($attr_values);
						echo "</pre>";
						exit;
						}*/

						/*
						$terms = wc_get_product_terms($listitem->id, str_replace('attribute_', '', $key), array(
						'fields' => 'all',
						));*/

						$childItem->attributes['variation_attributes'][ $attr ] = $attr_values;
						if ( empty( $term_details ) ) {
							$childItem->attributes[ $attr ] = $attr_values;
						} else {
							$childItem->attributes[ $attr ] = $term_details->name;
						}
					}
				}
				// Fetch any default attributes Stage 5 (Mapping 3.1)
				if ( $parent->attributeDefaultStages[5] > 0 ) {
					foreach ( $parent->attributeDefaults as $thisDefault ) {
						if ( $thisDefault->stage == 5 && ! $thisDefault->isRuled ) {
							$childItem->attributes[ $thisDefault->attributeName ] = $thisDefault->getValue( $childItem );
						}
					}
				}

				if ( $childItem->attributes['valid'] === 'false' ) {
					$childItem->attributes['valid'] = false;
				}

				$insertion = $parent->handleProduct( $childItem );
				$childItem = null;
				unset( $childItem );
			}
		}

		return $insertion;
	}

	public function getAdditionalProductAttributes( $id ) {
		$attributes = array();
		foreach ( get_post_meta( $id ) as $key => $value ) {
			foreach ( $value as $k => $val ) {
				$attributes[ $key ] = $val;
			}
		}

		return $attributes;

	}

	public function expandProductv3( $listitem, $parent ) {
		// if variable product
		global $wpdb;
		global $pfcore;

		$resultlist = array();
		// Insert any WooCommerce "Official" Variations
		if ( is_array( $listitem->variation_ids ) ) {
			$variations = $listitem->variation_ids;
		} else {
			$variations = explode( ',', $listitem->variation_ids );
		}

		if ( is_array( $variations ) && count( $variations ) > 0 ) {
			foreach ( $variations as $variation ) {
				$this->insertVariation( $variation, $listitem, $resultlist, $parent );
			}
		}

		if ( is_array( $resultlist ) && count( $resultlist ) == 0 ) {
			// If item has no variations, use the parent
			$listitem->parent_manage_stock = 'no';
			$resultlist[]                  = $listitem;
			$this->applyWCAttributes( $listitem );
		}
		// Process the results

		if ( is_array( $resultlist ) ) {
			foreach ( $resultlist as $index => $item ) {

				if ( $parent->force_wc_api ) {
					// expand product
					// STOCK CODE
					// if ( $item->attributes['product_is_in_stock'] && $item->attributes['product_stock_qty'] > 0 )
					// $item->attributes['stock_status'] = 1;
					// else if ($item->attributes['product_stock_qty'] == '')
					// $item->attributes['stock_status'] = $item->attributes['product_is_in_stock']; //use parent's stock status if qty is blank
					// else
					// $item->attributes['stock_status'] = 0;

					// $item->attributes['stock_status'] = $item->attributes['product_is_in_stock'];
					// $item->attributes['stock_quantity'] = $item->attributes['product_stock_qty'];

					unset( $item->attributes['product_stock_qty'] ); // This was causing confusion among users
					unset( $item->attributes['product_is_in_stock'] ); // This was causing confusion among users
				}

				$this->hideStockValid( $item );
				// Hide out of stock
				// if (($pfcore->manage_stock) && ($pfcore->hide_outofstock) && ($item->attributes['stock_status'] == 0))
				// $item->attributes['valid'] = false;

				// Reformat "valid" if necessary
				// if (isset($item->attributes['valid']) && (strcmp($item->attributes['valid'], 'false') == 0) )
				// $item->attributes['valid'] = false;

				// Fetch any default attributes (Mapping 3.0)
				foreach ( $parent->attributeDefaults as $thisDefault ) {
					if ( $thisDefault->stage == 1 && ! $thisDefault->isRuled ) {
						$item->attributes[ $thisDefault->attributeName ] = $thisDefault->getValue( $item );
					}
				}

				if ( empty( $item->attributes['brand'] ) && isset( $listitem->attributes['brand'] ) ) {
					$item->attributes['brand'] = $listitem->attributes['brand'];
				}

				// Send this item out for Feed processing
				$is_ammoaggxml = $parent->aggregateProviders[0]->providerName == 'AmmoAggXml';
				if ( $is_ammoaggxml ) {
					$args = array(
						'check'           => $parent->aggregateProviders[0]->check,
						'ammo_first_time' => $parent->aggregateProviders[0]->first_time,
					);
					$parent->handleProduct( $item, $args );
				} else {
					$parent->handleProduct( $item );
				}

				// Free resources
				foreach ( $item->attributes as &$x ) {
					unset( $x );
				}

				unset( $item );

				if ( ! $listitem->attributes['isVariable'] ) {
					break;
				}
			}
		}

	}

	public function insertVariation( $id, $parentItem, &$resultlist, $parent ) {

		// ********************************************************************
		// Variable attributes occur when the user has fully defined the variations and attributes.
		// In woocommerce, a true variation appears in wp_posts.post_type = product_variation
		// with post_parent pointed to the original product. So it's like a sub-product or a sub-post.
		// ********************************************************************

		if ( strlen( $id ) == 0 ) {
			return;
		}

		global $wpdb, $woocommerce;

		// Copy item
		$item         = clone $parentItem;
		$resultlist[] = $item;

		$item->attributes['parent_title'] = $item->attributes['title']; // This is for eBay feed only, and could otherwise be deleted

		// Special Variations settings
		$item->item_group_id               = $parentItem->id;
		$item->id                          = $id;
		$item->attributes['isVariation']   = true;
		$item->attributes['id']            = $id;
		$item->attributes['item_group_id'] = $parentItem->id;

		$item->attributes['parent_sku'] = isset( $parentItem->attributes['sku'] ) ? $parentItem->attributes['sku'] : ''; // get parent sku
		// Some basics
		// $product = get_product($id);
		// $item->product = $product; //Save for meta-data iteration

		// $item->attributes['condition'] = 'New';
		// $item->attributes['has_sale_price'] = false;
		// $item->attributes['stock_status'] = 1;
		// $item->attributes['stock_quantity'] = 1;

		// API calls now optional... disabled by default
		if ( $parent->force_wc_api ) {
			if ( $woocommerce != null ) {
				// making version backward compatibilty
				$wc_version = explode( '.', $woocommerce->version );
				if ( ( $wc_version[0] <= 2 ) ) {
					$product = get_product( $id ); // WooCommerce - get product by id
				} else {
					$product = wc_get_product( $id ); // not used WC_Product because its variable duh!
				}
			}
			$this->loadProductFromWCAPI( $parent, $item, $product ); // variation call
		}

		$this->postmetaLookup( $item, $parent ); // variations

		$this->applyWCAttributesPost( $item ); // variations

		// If no permutations allowed, we're done
		if ( ! $parent->allow_variation_permutations ) {
			return;
		}

		// Add to permutations any piped attributes that are marked "used for variations"
		if ( is_array( $item->wc_attributes ) ) {
			foreach ( $item->wc_attributes as $thisAttribute ) {
				if ( $thisAttribute['is_variation'] ) {
					if ( isset( $item->attributes[ $thisAttribute['name'] ] ) && strlen( $item->attributes[ $thisAttribute['name'] ] ) > 0 ) {
						$name = $thisAttribute['name'];
						$data = $item->attributes[ $name ];
						if ( strpos( $data, '|' ) === false ) {
							continue;
						}
						$terms = explode( '|', $data );
						// Look for this term among the permutations
						$exists = false;
						foreach ( $this->permutations as $permutation ) {
							if ( $permutation->name == $name ) {
								$exists = true;
								// The global piped attribute overrides the local.
								// It may be desirable to one day clone the global attribute and put the local values into the clone
								break;
							}
						}

						if ( ! $exists ) {
							// Establish a new temporary MasterAttribute
							$thisAttribute       = new stdClass();
							$thisAttribute->name = $name;
							// $thisAttribute->type = ???;
							$thisAttribute->children = array();
							$this->permutations[]    = $thisAttribute;
							// Shove local values into the temporary MasterAttribute
							foreach ( $terms as $datum ) {
								$datum                     = trim( $datum );
								$term                      = new stdClass();
								$term->taxonomy            = $name;
								$term->name                = $datum;
								$term->slug                = $datum;
								$thisAttribute->children[] = $term;
							}
						}
					}
				}
			}
		}

		// If no permutations exist, we're done
		if ( is_array( $this->permutations ) && count( $this->permutations ) == 0 ) {
			return;
		}

		// The presence of permutations forcefully invalidate existing items
		if ( is_array( $resultlist ) ) {
			foreach ( $resultlist as $thisItem ) {
				if ( $thisItem->id == $id ) {
					$thisItem->attributes['valid'] = false;
				}
			}
		}

		$permutationTrackerObject                      = new stdClass();
		$permutationTrackerObject->id                  = $id;
		$permutationTrackerObject->parent_feed         = $parent;
		$permutationTrackerObject->countOfPermutations = 0;

		// Now, add the permutations
		$this->permute( $permutationTrackerObject, 0, $resultlist );

	}

	public function loadProductFromWCAPI( $parent, $item, $product ) {

		$item->attributes['sku'] = $product->sku;

		// $item->attributes['regular_price'] = $product->get_regular_price();
		// $item->attributes['sale_price'] = $product->get_sale_price();
		if ( strlen( $item->attributes['sale_price'] ) > 0 && $item->attributes['sale_price'] > 0 ) {
			$item->attributes['has_sale_price'] = true;
		} else {
			$item->attributes['has_sale_price'] = false;
		}
		// $item->attributes['weight'] = $product->get_weight();
		// $this->scpf_get_tax_rates($item,$product);

		if ( $parent->get_wc_shipping_attributes ) {
			$this->scpf_get_dimension_values( $item, $product );
		}

		if ( $parent->get_wc_shipping_class && ( strlen( $item->attributes['shipping_class'] ) == 0 ) ) {
			$this->scpf_get_shipping_classes( $item, $product );
		}

		if ( $parent->variation_images ) {
			$this->scpf_get_wc_variation_images( $item, $product );
		}

		// if ($parent->get_wc_tax_rates)
		// $this->scpf_get_tax_rates($item,$product);

		// if ( isset($product->vendor) )
		// $item->attributes['vendor'] = $product->vendor;
		// if ( isset($product->model) )
		// $item->attributes['model'] = $product->model;
		// if ( isset($product->size) )
		// $item->attributes['size'] = $product->size;

		$item->parent_manage_stock               = true; // Temp... testing Calvin's new code
		$item->attributes['product_is_in_stock'] = $product->is_in_stock(); // already checks for global manage stock and product inventory stock_status

		// NEW STOCK CODE (includes check for notify_no_stock_amount)
		// $item->attributes['product_is_in_stock'] = $product->get_availability()['class'];
		// //if not managing stock, but status is in stock...
		// if ( $item->attributes['product_is_in_stock'] == '' )
		// $item->attributes['product_is_in_stock'] = 'in-stock';
		// //convert to 1/0
		// $item->attributes['product_is_in_stock'] = $item->attributes['product_is_in_stock'] == 'in-stock' ? 1 : 0;
		// $item->attributes['stock_status'] = $item->attributes['product_is_in_stock'];

		// //if not managing stock, returns ''
		$item->attributes['product_stock_qty'] = $product->get_stock_quantity();
		// //if in-stock but quantity is blank, set to 1
		// if ( $item->attributes['product_is_in_stock'] === 1 && $item->attributes['product_stock_qty'] == '' )
		// $item->attributes['product_stock_qty'] = 1;
		// //if not managing stock and out of stock
		// elseif ( $item->attributes['product_is_in_stock'] === 0 && $item->attributes['product_stock_qty'] == '')
		// $item->attributes['product_stock_qty'] = 0;

		// $item->attributes['stock_quantity'] = $item->attributes['product_stock_qty'];
	}

	// currently it triggers on piped values in the MasterAttributeTable (which comes from the central table). Soon it will come from the product's attribute value
	public function permute( $tracker, $index, &$resultlist, $stack = array() ) {
		if ( is_array( $this->permutations[ $index ]->children ) ) {
			foreach ( $this->permutations[ $index ]->children as $thisChild ) {
				if ( $index == count( $this->permutations ) - 1 ) {
					// At the end of the permutation chain, insert
					foreach ( $resultlist as $listitem ) {
						if ( $listitem->id == $tracker->id && ! $listitem->attributes['valid'] ) {
							$item                      = clone $listitem;
							$item->attributes['id']    = $tracker->parent_feed->permutation_base_id + $item->attributes['id'] * $tracker->parent_feed->permutation_variant_multiplier + $tracker->countOfPermutations;
							$item->attributes['valid'] = true;
							$item->attributes['sku']   .= '-' . sprintf( "%'03d", $tracker->countOfPermutations );
							$resultlist[]              = $item;
							$newStack                  = $stack;
							$newStack[]                = $thisChild;
							// Debug
							// $a = '';
							// foreach($newStack as $term) $a .= $term->taxonomy . '=' . $term->name . ' ';
							// error_log($tracker->id . ' end of stack ' . $a);
							foreach ( $newStack as $term ) {
								$item->attributes[ $term->taxonomy ] = $term->name;
							}

							$tracker->countOfPermutations ++;
						}
					}
				} else {
					// Not the end of permutation chain means keep looking
					$newStack   = $stack;
					$newStack[] = $thisChild;
					$this->permute( $tracker, $index + 1, $resultlist, $newStack );
				}
			}
		}

	}

	public function postmetaLookup( $item, $parent ) {

		global $wpdb;

		// ********************************************************************
		// Go find the Attributes
		// ********************************************************************
		$attributes = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT meta_key, meta_value FROM $wpdb->postmeta
		WHERE post_id = " . $item->attributes['id']
			)
		);
		// ********************************************************************
		// Parse
		// ********************************************************************
		$this->permutations = array();
		if ( is_array( $attributes ) ) {
			foreach ( $attributes as $this_attribute ) {

				$key   = $this_attribute->meta_key;
				$value = $this_attribute->meta_value;

				if ( strlen( $value ) == 0 ) {
					$termcat = $this->masterAttributeList->findByName( substr( $key, 13 ) );
					if ( $termcat != null ) {
						$this->permutations[] = $termcat;
					}

					continue;
				}

				// Special Case: Variation Attributes
				if ( strpos( $key, 'attribute_pa' ) !== false ) {
					// meta_value takes the slug from wp_terms instead of the name
					$key  = substr( $key, 13 );
					$slug = '';
					// Convert from Slug to Term
					$term = $this->masterAttributeList->findBySlug( $value );
					if ( $term != null ) {
						$value = $term->name;
						// error_log(json_encode($term));
						$item->attributes[ '_' . $key ] = $term->slug;
					}
					// If no value provided it means ANY of this attribute. Mark it for future permutation
					if ( strlen( $value ) == 0 ) {
						// It is dubious the computer will reach this case of len($value) == 0
						$termcat = $this->masterAttributeList->findByName( $key );
						if ( $termcat != null ) {
							$this->permutations[] = $termcat;
						}
					} else {
						$item->attributes[ $key ] = $value;
					}

					continue;
				}

				// Special Case: Attribute Definitions
				if ( $key == '_product_attributes' ) {
					$item->wc_attributes = unserialize( $value );
					// $item->attributes[$key] = $value;
					continue;
				}

				// Generic Case
				if ( $parent->attribute_granularity < 5 ) {
					switch ( $key ) {
						case '_backorders':
							$item->attributes['backorders'] = $value;
							break;
						case '_height':
							$item->attributes['height'] = $value;
							break;
						case '_length':
							$item->attributes['length'] = $value;
							break;
						case '_manage_stock':
							$item->attributes['manage_stock'] = $value;
							break;
						case '_model':
							$item->attributes['model'] = $value;
							break;
						case '_price':
							$item->attributes['price'] = $value; // sale or regular price, whichever is lower
							break;
						case '_product_image_gallery':
							$item->attributes['product_image_gallery'] = explode( ',', $value );
							break;
						case '_regular_price':
							$item->attributes['regular_price']  = $value;
							$item->attributes['_regular_price'] = $value;
							break;
						case '_sale_price':
							if ( $value > 0 ) {
								$item->attributes['sale_price']     = $value;
								$item->attributes['_sale_price']    = $value;
								$item->attributes['has_sale_price'] = true;
							} else {
								$item->attributes['sale_price']     = $value;
								$item->attributes['_sale_price']    = $value;
								$item->attributes['has_sale_price'] = false;
							}

							break;
						case '_sale_price_dates_from':
							$item->attributes['sale_price_dates_from'] = $value;
							break;
						case '_sale_price_dates_to':
							$item->attributes['sale_price_dates_to'] = $value;
							break;
						case '_sku':
							$item->attributes['sku'] = $value;
							break;
						case '_size':
							$item->attributes['size'] = $value;
							break;
						case '_stock':
							$item->attributes['stock_quantity'] = $value;
							break;
						case '_stock_status':
							$item->attributes['stock_status'] = $value;
							break;
						case '_vendor':
							$item->attributes['vendor'] = $value;
							break;
						case '_weight':
							if ( ! empty( $value ) ) {
								$item->attributes['weight'] = $value;
							}

							break;
						case '_width':
							$item->attributes['width'] = $value;
							break;
						case '_bto_data':
							$item->bto_data = $value;
							break;
						case '_cpf_brand':
							if ( ! empty( $value ) ) {
								$item->attributes['brand'] = $value;
							}

							break;
						case '_cpf_mpn':
							if ( ! empty( $value ) ) {
								$item->attributes['mpn'] = $value;
							}

							break;
						case '_cpf_description':
							if ( ! empty( $value ) ) {
								$item->attributes['description'] = $value;
							}

							break;
						case '_cpf_ean':
							if ( ! empty( $value ) ) {
								$item->attributes['ean'] = $value;
							}

							break;
						case '_cpf_upc':
							if ( ! empty( $value ) ) {
								$item->attributes['upc'] = $value;
							}

							break;
						case '_cpf_valid':
							if ( ! empty( $value ) ) {
								$item->attributes['valid'] = $value;
							}

							break;
						default:
							if ( strlen( $key ) > 0 && $key[0] != '_' ) {
								$item->attributes[ $key ] = $value;
							}
					}
				} else {
					switch ( $key ) {
						case '_bto_data':
							$item->bto_data = $value;
							break;
						case '_product_image_gallery':
							$item->attributes['product_image_gallery'] = explode( ',', $value );
							break;
						case '_sale_price':
							if ( $value > 0 ) {
								$item->attributes['sale_price']     = $value;
								$item->attributes['_sale_price']    = $value;
								$item->attributes['has_sale_price'] = true;
							} else {
								$item->attributes['sale_price']     = $value;
								$item->attributes['_sale_price']    = $value;
								$item->attributes['has_sale_price'] = false;
							}
							break;
						case '_stock':
							$item->attributes['stock_quantity'] = $value;
							break;
						case '_weight':
							if ( ! empty( $value ) ) {
								$item->attributes['weight'] = $value;
							}

							break;
						case '_cpf_brand':
							if ( ! empty( $value ) ) {
								$item->attributes['brand'] = $value;
							}

							break;
						case '_cpf_mpn':
							if ( ! empty( $value ) ) {
								$item->attributes['mpn'] = $value;
							}

							break;
						case '_cpf_description':
							if ( ! empty( $value ) ) {
								$item->attributes['description'] = $value;
							}

							break;
						case '_cpf_ean':
							if ( ! empty( $value ) ) {
								$item->attributes['ean'] = $value;
							}

							break;
						case '_cpf_upc':
							if ( ! empty( $value ) ) {
								$item->attributes['upc'] = $value;
							}

							break;
						case '_cpf_valid':
							if ( ! empty( $value ) ) {
								$item->attributes['valid'] = $value;
							}

							break;
						default:
							if ( strlen( $key ) > 0 ) {
								if ( $key[0] == '_' ) {
									$item->attributes[ substr( $key, 1 ) ] = $value;
								} //will include all remaining postmeta.. including regular price
								else {
									$item->attributes[ $key ] = $value;
								}
							}
					}
				}
			}
		}

		// ********************************************************************
		// Stock
		// ********************************************************************

		if ( ! isset( $item->attributes['manage_stock'] ) || $item->attributes['manage_stock'] == 'no' || $this->woocommerce_manage_stock !== 'yes' ) {
			$managing_stock = false;
		} else {
			$managing_stock = true;
		}

		$backorders_allowed = false;
		if ( isset( $item->attributes['backorders'] ) ) {
			$backorders_allowed = ( $item->attributes['backorders'] === 'yes' || $item->attributes['backorders'] === 'notify' ? true : false );
		}

		if ( $managing_stock && $backorders_allowed ) {
			$item->attributes['stock_status'] = 1;
		} elseif ( $managing_stock && $item->attributes['stock_quantity'] <= $this->woocommerce_notify_no_stock_amount ) {
			$item->attributes['stock_status'] = 0;
		} else {
			if ( $item->attributes['stock_status'] == 'instock' || $item->attributes['stock_status'] == '1' ) {
				$item->attributes['stock_status'] = 1;
			} else {
				$item->attributes['stock_status'] = 0;
			}
		}
		if ( $item->attributes['stock_quantity'] === 0 ) {
			$item->attributes['stock_quantity'] = 0;
		}

		$item->attributes['stock_quantity'] = (int) $item->attributes['stock_quantity'];

		// ********************************************************************
		// Gallery Images
		// ********************************************************************
		if ( ! isset( $item->attributes['product_image_gallery'] ) ) {
			// Backwards compat
			$attachment_ids                            = get_posts( 'post_parent=' . $item->attributes['id'] . '&numberposts=-1&post_type=attachment&orderby=menu_order&order=ASC&post_mime_type=image&fields=ids&meta_key=_woocommerce_exclude_image&meta_value=0' );
			$item->attributes['product_image_gallery'] = array_diff( $attachment_ids, array( get_post_thumbnail_id( $item->attributes['id'] ) ) );
		}
		if ( isset( $item->attributes['thumb_ID'] ) ) {
			$item->attributes['product_image_gallery'] = array_diff( $item->attributes['product_image_gallery'], array( $item->attributes['thumb_ID'] ) );
		}

		if ( is_array( $item->attributes['product_image_gallery'] ) && count( $item->attributes['product_image_gallery'] ) > 0 && ! $item->attributes['isVariation'] ) {
			foreach ( $item->attributes['product_image_gallery'] as $gid ) {
				$thumb  = ( wp_get_attachment_image_src( $gid, 'small-feature' ) );
				$imgurl = $thumb['0'];
				if ( strlen( $imgurl ) > 0 ) {
					$item->imgurls[] = $imgurl;
				}
			}
		}
		if ( $item->attributes['isVariation'] ) {
			$image_id = get_post_thumbnail_id( $item->attributes['id'] );
			if ( $image_id ) {
				$var_image = wp_get_attachment_image_src( $image_id, 'full' );
				if ( $var_image[0] ) {
					$item->attributes['variation_imgurl'] = $var_image[0];
				}
				// use wc variation image
			}
		}

		// by default: if the variant image is present, the plugin will use it
		// set force_featured_image to true: this will force all variations to use the featured image (rather than the variation image)
		if ( ! $parent->force_featured_image ) {
			if ( isset( $item->attributes['variation_imgurl'] ) ) {
				$item->attributes['feature_imgurl'] = $item->attributes['variation_imgurl'];
			}
		}

		// Basic Tax Rates: Does not handle multiple rates per class
		if ( $parent->get_tax_rates ) {
			$tax_status = $item->attributes['tax_status']; // taxable, shipping, none
			if ( $tax_status == 'taxable' ) {
				$tax_rate_class               = $item->attributes['tax_class']; // '' (standard), reduced-rate, other user defined classes
				$tax_rate                     = $parent->taxData->loadTaxationDataW( $parent, $tax_status, $tax_rate_class );
				$item->attributes['tax_rate'] = $tax_rate;

				$tax                               = ( $item->attributes['regular_price'] * $tax_rate ) / 100;
				$item->attributes['regular_price'] = $item->attributes['regular_price'] + $tax;
				if ( $item->attributes['has_sale_price'] ) {
					$tax                            = ( $item->attributes['sale_price'] * $tax_rate ) / 100;
					$item->attributes['sale_price'] = $item->attributes['sale_price'] + $tax;
				}
			}
		}

	} //postmetalookup


	public function getTheTerms( $pid, $taxonomy ) {
		if ( is_array( get_the_terms( $pid, $taxonomy ) ) && count( $terms = get_the_terms( $pid, $taxonomy ) ) > 0 ) {
			return $terms;
		} else {
			return null;
		}
	}

	protected function getTerms( $item ) {
		$attributes = array();
		if ( is_array( $item->wc_attributes ) && count( $item->wc_attributes ) > 0 ) {
			foreach ( $item->wc_attributes as $key => $taxonomy ) {
				$taxonomy = $this->getTheTerms( $item->id, $taxonomy['name'] );
				if ( is_array( $taxonomy ) ) {
					$values = end( $taxonomy );
					if ( preg_match( '/brand/', $key ) ) {
						$item->attributes['brand'] = $values->name;
					} else {
						$item->attributes[ $key ] = $values->name;
					}
				}
			}
		}
	}

	// Pulls dimension values into respective attributes
	public function scpf_get_dimension_values( $item, $product ) {
		$item->attributes['length'] = $product->length;
		$item->attributes['width']  = $product->width;
		$item->attributes['height'] = $product->height;
	}

	// Pull WooCommerce shipping classes
	public function scpf_get_shipping_classes( $item, $product ) {
		$slug                               = $product->get_shipping_class();
		$slug_object                        = get_term_by( 'slug', $slug, 'product_shipping_class' );
		$item->attributes['shipping_class'] = $slug_object->name;
	}

	public function scpf_get_wc_variation_images( $item, $product ) {
		// variation images
		$var_image = wp_get_attachment_image_src( $product->get_image_id(), 'full' ); // full size... rather than 'shop_thumbnail'
		if ( $var_image[0] ) {
			$item->attributes['feature_imgurl'] = $var_image[0];
		}
		// use wc variation image
	}

	// pull WooCommerce tax rates (should be a db call?)
	// if VAT/TAX enabled (checkbox in plugin). Move to rules?
	// function scpf_get_tax_rates($item,$product) {
	// $item->attributes['regular_price_including_tax'] = $product->get_price_including_tax('1',$item->attributes['regular_price']);
	// if ( isset($item->attributes['regular_price_including_tax']) )
	// $item->attributes['regular_price'] = $item->attributes['regular_price_including_tax'];
	// $item->attributes['sale_price_including_tax'] = $product->get_price_including_tax('1',$item->attributes['sale_price']);
	// if ( isset($item->attributes['sale_price_including_tax']) )
	// $item->attributes['sale_price'] = $item->attributes['sale_price_including_tax'];
	// }
}
