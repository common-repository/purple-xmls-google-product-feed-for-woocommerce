<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
/********************************************************************
 * Version 2.1
 * List (local) product categories
 * Copyright 2014 Purple Turtle Productions. All rights reserved.
 * license    GNU General Public License version 3 or later; see GPLv3.txt
 * By: Keneto 2014-05-11
 * 2014-06-06 Added Joomla calls
 ********************************************************************/
class EXCPF_PProductCategories {
	function __construct( $restriction = null ) {
		$this->getList();
		if ( isset( $restriction ) ) {
			$this->isolateCategories( $restriction );
		}
	}

	public function asCSV() {
		$result = array();
		foreach ( $this->categories as $this_category ) {
			$this->categoryToCSV( $this_category, $result );
		}
		return implode( ',', $result );
	}

	private function categoryToCSV( $this_category, &$result ) {
		$result[] = $this_category->id;
		foreach ( $this_category->children as $child_category ) {
			$this->categoryToCSV( $child_category, $result );
		}
	}

	private function idToCategory_internal( $categories, $category_id ) {
		$result = null;
		if ( $categories->id == $category_id ) {
			$result = $categories;
			return $result;
		}
		foreach ( $categories->children as $child_category ) {
			$x = $this->idToCategory_internal( $child_category, $category_id );
			if ( $x != null ) {
				$result = $x;
				break;
			}
		}
		return $result;
	}

	public function idToCategory( $category_id ) {
		foreach ( $this->categories as $this_category ) {
			$result = $this->idToCategory_internal( $this_category, $category_id );
			if ( $result ) {
				return $result;
			}
		}
		return null;
	}

	public function containsCategory( $category_id ) {
		return $this->idToCategory( $category_id ) != null;
	}

	private function doTallies( $category, $depth = 0 ) {
		$result = 0;
		foreach ( $category->children as $child_category ) {
			$result += $this->doTallies( $child_category, $depth + 1 );
		}
		$category->tally += $result; // Save the subtally of direct_content + children
		$category->depth  = $depth;
		$result           = $category->tally;
		return $result;
	}

	private function interpretCategories( $links ) {
		// After pulling from db, we need do convert to categories

		// Prepare to convert categories into a hierarchical tree
		foreach ( $this->categories as $this_category ) {
			$this_category->children = array();
		}

		// Convert categories into the tree
		foreach ( $this->categories as $this_category ) {
			// find a parent id using links
			$parent_id = -1;
			foreach ( $links as $this_link ) {
				if ( $this_link->parent_category != $this_link->child_category ) {
					if ( $this_category->id == $this_link->child_category ) {
						$parent_id = $this_link->parent_category;
						break;
					}
				}
			}
			// convert the parent id into an object and link
			foreach ( $this->categories as $parent_category ) {
				if ( $parent_category->id == $parent_id ) {
					$parent_category->children[]    = $this_category;
					$this_category->parent_category = $parent_category;
					break;
				}
			}
		}

		// Take all top-level categories (those with no parent) and initiate recursive tally
		foreach ( $this->categories as $this_category ) {
			if ( ! isset( $this_category->parent_category ) ) {
				$this->doTallies( $this_category );
			}
		}

	}

	public function isolateCategories( $restriction ) {
		// Figure out what categories we're interested in keeping
		$categories_to_look_for = explode( ',', $restriction );
		// prepare the list of categories to keep
		$newcategories = array();
		// iterate the list and record them as needed
		foreach ( $categories_to_look_for as $category_to_consider ) {
			$keep = $this->idToCategory( $category_to_consider );
			if ( $keep ) {
				$newcategories[] = $keep;
			}
		}
		// Save the list of keepers back to category list
		$this->categories = $newcategories;
	}

	public function getList() {
		global $pfcore;
		$getListCall = 'getList' . $pfcore->callSuffix;
		return $this->$getListCall();
	}

	public function getListW() {

		global $wpdb;

		// Fetch: id, title, tally-of-products
		$source_categories = $wpdb->get_results(
			"
		SELECT taxo.term_id as id, term.name as title, taxo.count as tally 
		FROM $wpdb->term_taxonomy taxo
		INNER JOIN $wpdb->terms term ON taxo.term_id = term.term_id
		WHERE taxo.taxonomy = 'product_cat'"
		);

		// convert to objects
		$this->categories = array();
		foreach ( $source_categories as $a_source_category ) {

			$this_category        = new stdClass();
			$this_category->id    = $a_source_category->id;
			$this_category->title = $a_source_category->title;
			$this_category->tally = $a_source_category->tally;
			$this->categories[]   = $this_category;
		}

		// Fetch: parent_category, child_category
		$links = $wpdb->get_results(
			"
		SELECT taxo.term_id as child_category, taxo.parent as parent_category 
		FROM $wpdb->term_taxonomy taxo
		WHERE taxo.taxonomy = 'product_cat'"
		);

		$this->interpretCategories( $links );
	}

	public function getListWe() {

		global $wpdb;

		// Fetch: id, title, tally-of-products
		$source_categories = $wpdb->get_results(
			"
		SELECT taxo.term_id as id, term.name as title, taxo.count as tally 
		FROM $wpdb->term_taxonomy taxo
		LEFT JOIN $wpdb->terms term ON taxo.term_id = term.term_id
		WHERE taxo.taxonomy = 'wpsc_product_category'"
		);

		// convert to objects
		$this->categories = array();
		foreach ( $source_categories as $a_source_category ) {
			$this_category        = new stdClass();
			$this_category->id    = $a_source_category->id;
			$this_category->title = $a_source_category->title;
			$this_category->tally = $a_source_category->tally;
			$this->categories[]   = $this_category;
		}

		// Fetch: parent_category, child_category
		$links = $wpdb->get_results(
			"
		SELECT taxo.term_id as child_category, taxo.parent as parent_category 
		FROM $wpdb->term_taxonomy taxo
		WHERE taxo.taxonomy = 'wpsc_product_category'"
		);

		$this->interpretCategories( $links );
	}

	protected function indent( $depth ) {
		if ( $depth == 0 ) {
			return '';
		} else {
			return str_repeat( '-', $depth );
		}
	}

	public function getOptionList() {
		$opts = '<option value="0">----- Select a Category -----</option>';
		foreach ( $this->categories as $this_category ) {
			$opts .= '<option value="' . $this_category->id . '">' . $this->indent( $this_category->depth ) . ' ' . $this_category->title . ' (' . $this_category->tally . ')';
		}
		return $opts;
	}

}
