<?php

/*
 * Class EXCPF_Extendables
 * Contains methods reqiured for child class
 **/

class EXCPF_Extendables {


	function __construct() {
		global $wpdb;
	}

	/**
	 * Serves to provide terms from db of particular object
	 *
	 * @since 3.2.3.0
	 * @access protected
	 *
	 * @postID id of a product
	 * @data taxonomies attributes
	 *
	 * @return array
	 */
	protected function getTerms( $postID, $data ) {
		$attributes = array();
		if ( is_array( $data ) && count( $data ) > 0 ) {
			foreach ( $data as $key => $taxonomy ) {
				$attributes[ $key ] = $this->getTheTerms( $postID, $taxonomy['name'] );
			}
		} else {
			$attributes[ $data ] = $this->getTheTerms( $postID, $data );
		}
		return $attributes;
	}

	/**
	 * Same as getTerms(), but it deals for the taxonomy (specifically)
	 *
	 * @pid product id , @taxonomy the taxonomy term
	 *
	 * returns null or array
	 * */
	public function getTheTerms( $pid, $taxonomy ) {
		if ( is_array( get_the_terms( $pid, $taxonomy ) ) && count( $terms = get_the_terms( $pid, $taxonomy ) ) > 0 ) {
			return $terms;
		} else {
			return null;
		}
	}

	public function getAttributes( $pid ) {

	}

	public function getProductMeta( $pid, $product ) {
		foreach ( get_post_meta( $pid ) as $key => $value ) {
			if ( $key == '_product_attributes' ) {
				$value = maybe_unserialize( end( $value ) );
			} else {
				$value = end( $value );
			};
			$product->$key = $value;
		}
		return $product;
	}

	public function getProductType( $pid ) {
		$taxonomies = get_the_terms( $pid, 'product_type' );
		if ( is_array( $taxonomies ) && count( $taxonomies ) > 0 ) {
			return $taxonomies[0]->slug;
		}
		return false;
	}

	public function hideStockValid( $product ) {
		global $pfcore;
		// Hide out of stock
		// if ( ($pfcore->manage_stock) )
		if ( ( $pfcore->hide_outofstock ) && ( $product->attributes['stock_quantity'] == 0 ) ) {
			if ( $product->attributes['stock_status'] <= 0 ) {
				$product->attributes['valid'] = false;
			}
		}

		// Reformat "valid" if necessary
		if ( isset( $product->attributes['valid'] ) && ( strcmp( $product->attributes['valid'], 'false' ) == 0 ) ) {
			$product->attributes['valid'] = false;
		}

		return $product;

	}


}
