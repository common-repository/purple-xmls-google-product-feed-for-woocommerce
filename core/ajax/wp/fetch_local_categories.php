<?php

/********************************************************************
 * Version 2.0
 * List of local categories
 * Copyright 2014 Purple Turtle Productions. All rights reserved.
 * license    GNU General Public License version 3 or later; see GPLv3.txt
 * By: Keneto 2014-07-15
 ********************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
if ( ! is_admin() ) {
	die( 'Permission Denied!' );
}

define( 'XMLRPC_REQUEST', true );
ob_start( null );

require_once dirname( __FILE__ ) . '/../../data/feedcore.php';
require_once dirname( __FILE__ ) . '/../../data/productcategories.php';

ob_clean();
$result = null;
if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
		global $wp_version;
		$taxonomy_    = 'product_cat';
		$orderby_     = 'name';
		$show_count   = 0;      // 1 for yes, 0 for no
		$pad_counts   = 1;      // 1 for yes, 0 for no
		$hierarchical = 1;      // 1 for yes, 0 for no
		$title_       = '';
		$empty        = 0;

		$args           = array(
			'taxonomy'           => $taxonomy_,
			'orderby'            => $orderby_,
			'show_count'         => $show_count,
			'pad_counts'         => $pad_counts,
			'hierarchical'       => $hierarchical,
			'title_li'           => $title_,
			// 'suppress_filters' => true,
					'hide_empty' => $empty,
		);
		$all_categories = get_categories( $args );
		if ( is_array( $all_categories ) && count( $all_categories ) > 0 ) {
			$new           = new stdClass();
			$new->children = array();
			$data          = new stdClass();
			foreach ( $all_categories as $a ) {
				if ( $a->category_parent != 0 ) {
					$data->cat_ID                           = $a->cat_ID;
					$data->name                             = $a->name;
					$data->title                            = $a->name;
					$data->count                            = $a->count;
					$data->category_parent                  = 0;
					$new->children[ $a->category_parent ][] = $data;
					$data                                   = new stdClass();
				}
			}
			$result = createTree( $new->children, $all_categories );
		} else {
			$categoryList     = new EXCPF_PProductCategories();
			$result           = new stdClass();
			$result->children = array();
			foreach ( $categoryList->categories as $this_category ) {
				if ( ! isset( $this_category->parent_category ) ) {
					excpf_process_category( $result->children, $this_category );
				}
			}
		}
		echo json_encode( $result );
} else {
	$categoryList     = new EXCPF_PProductCategories();
	$result           = new stdClass();
	$result->children = array();
	foreach ( $categoryList->categories as $this_category ) {
		if ( ! isset( $this_category->parent_category ) ) {
			excpf_process_category( $result->children, $this_category );
		}
	}
	echo json_encode( $result );
}
function createTree( &$list, $parent ) {
	$tree           = new stdClass();
	$tree->children = array();
	foreach ( $parent as $k => $l ) {
		if ( $l->category_parent == 0 ) {
			$data        = new stdClass();
			$pid         = $l->cat_ID;
			$data->id    = $l->cat_ID;
			$data->title = $l->name;
			$data->tally = $l->count;
			if ( isset( $list[ $pid ] ) ) {
				$data->children = createTree( $list, $list[ $pid ] );
				$data->children = $data->children->children;
			} else {
				$data->children = array();
			}
			$tree->children[] = $data;
		}
	}
	return $tree;
}


function excpf_process_category( &$target_list, $this_category ) {
	$new_category           = new stdClass();
	$new_category->id       = $this_category->id;
	$new_category->title    = $this_category->title;
	$new_category->tally    = $this_category->tally;
	$new_category->children = array();
	$target_list[]          = $new_category;
	foreach ( $this_category->children as $child ) {
		excpf_process_category( $new_category->children, $child );
	}
}

/*
if($wp_version < 4.5.0){
	$args = array(
		'number'     => $number,
		'orderby'    => $orderby_,
		'order'      => $order,
		'hide_empty' => $hide_empty,
		'include'    => $ids
	);
	$product_categories = get_terms( 'product_cat', $args );
}else{
	// since WordPress 4.5.0
	$args = array(
		'taxonomy'   => "product_cat",
		'number'     => $number,
		'orderby'    => $orderby_,
		'order'      => $order,
		'hide_empty' => $hide_empty,
		'include'    => $ids
	);
	$product_categories = get_terms($args);
}*/

/*
foreach ($all_categories as $cat) {
	   if($cat->category_parent == 0) {
		   $category_id = $cat->term_id;
		   echo '<br /><a href="'. get_term_link($cat->slug, 'product_cat') .'">'. $cat->name .'</a>';

		   $args2 = array(
			   'taxonomy'     => $taxonomy_,
			   'child_of'     => 0,
			   'parent'       => $category_id,
			   'orderby'      => $orderby_,
			   'show_count'   => $show_count,
			   'pad_counts'   => $pad_counts,
			   'hierarchical' => $hierarchical,
			   'title_li'     => $title_,
			   'hide_empty'   => $empty
		   );
		   $sub_cats = get_categories( $args2 );
		   if($sub_cats) {
			   foreach($sub_cats as $sub_category) {
				   echo  $sub_category->name ;
			   }
		   }
	   }
   }*/

