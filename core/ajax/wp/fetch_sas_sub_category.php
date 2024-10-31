<?php

/********************************************************************
 * Version 2.0
 * Get ShareASale sub categories
 * Copyright 2021 ExportFeed. All rights reserved.
 * license    GNU General Public License version 3 or later; see GPLv3.txt
 * By: Subash 2021-01-20
 ********************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// Exit if accessed directly
if ( ! is_admin() ) {
	die( 'Permission Denied!' );
}
if ( isset( $_REQUEST['security'] ) ) {
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['security'] ) ), 'cpf_nonce' ) ) {
		die( 'Permission denied' );
	}
}
define( 'XMLRPC_REQUEST', true );

$cat_id_         = isset( $_POST['categories'] ) ? sanitize_text_field( wp_unslash( $_POST['categories'] ) ) : '';
$feed_type       = isset( $_POST['feed_type'] ) ? sanitize_text_field( wp_unslash( $_POST['feed_type'] ) ) : '';
$feed_identifier = isset( $_POST['feed_identifier'] ) ? sanitize_text_field( wp_unslash( $_POST['feed_identifier'] ) ) : '';
switch ( $cat_id_ ) {
	case 1:
		$list = array(
			1 => 'Art',
			2 => 'Photography',
			3 => 'Posters/Prints',
			4 => 'Music',
			5 => 'Music Instruments',
		);
		break;
	case 2:
		$list = array(
			6  => 'Accessories',
			7  => 'Car Audio',
			8  => 'Cleaning / Care',
			9  => 'Motorcycles',
			10 => 'Misc',
			11 => 'Repair',
			12 => 'Parts',
		);
		break;
	// Books / Reading Subcategories
	case 3:
		$list = array(
			13  => 'Art',
			14  => 'Careers',
			15  => 'Business',
			16  => 'Childrens',
			17  => 'Computers',
			18  => 'Crafts',
			19  => 'Education',
			20  => 'Engineering',
			21  => 'Gifts',
			22  => 'Health',
			23  => 'History',
			24  => 'Fiction',
			25  => 'Law',
			26  => 'Magazines',
			27  => 'Financial',
			28  => 'Medical',
			29  => 'Office',
			30  => 'Real Estate',
			31  => 'Misc',
			164 => 'Religious',
			173 => 'Science',
		);
		break;
	// Business / Services Subcategories
	case 4:
		$list = array(
			32  => 'Advertising',
			33  => 'Motivational',
			34  => 'Coupons / Freebies',
			35  => 'Financial',
			36  => 'Loans',
			37  => 'Office',
			38  => 'Careers',
			39  => 'Mis',
			179 => 'Education',
		);
		break;

	// Computer Subcategories
	case 5:
		$list = array(
			40  => 'Hardware',
			41  => 'Software',
			42  => 'Instruction',
			43  => 'Handheld / Wireless',
			162 => 'Web Hosting',
		);
		break;

	// Electronics Subcategories
	case 6:
		$list = array(
			44 => 'Audio',
			45 => 'Video',
			46 => 'Camera',
			47 => 'Wireless',
		);
		break;

	// Entertainment Subcategories
	case 7:
		$list = array(
			48 => 'Audio',
			49 => 'Video',
			50 => 'DVD',
			51 => 'Laser Disc',
			52 => 'SheetMusic',
			53 => 'Crafts/ Hobbies',
		);
		break;

	// Fashion Subcategories
	case 8:
		$list = array(
			54  => 'Boys',
			55  => 'Clearance',
			56  => 'Vintage',
			57  => 'Girls',
			58  => 'Mens',
			59  => 'Womens',
			60  => 'Maternity',
			61  => 'Footware',
			62  => 'Accessories',
			63  => 'Baby/Infant',
			64  => 'Jewelry',
			65  => 'Lingerie',
			66  => 'Plus-Size',
			67  => 'Athletic',
			161 => 'T-Shirts',
			166 => 'Big An`d Tall',
			168 => 'Petite',
			169 => 'Unisex',
			172 => 'Costumes',
		);
		break;

	// Food / Beverage Subcategories
	case 9:
		$list = array(
			68  => 'Baked Goods',
			69  => 'Beverages',
			70  => 'Chocolate',
			71  => 'Cheese/ Condiments',
			72  => 'Coupons',
			73  => 'Diet',
			74  => 'Ethnic',
			75  => 'Gifts Gift Baskets',
			76  => 'Nuts',
			77  => 'Cookies / Desserts',
			78  => 'Organic',
			163 => 'Tobacco',
			176 => 'Gourmet',
			177 => 'Meals/ Complete Dishes',
			180 => 'Appetizers',
			181 => 'Soups',
		);
		break;

	// Gifts / Specialty Subcategories
	case 10:
		$list = array(
			79  => 'Anniversary',
			80  => 'Birthday',
			81  => 'Misc. Holiday',
			82  => 'Collectibles',
			83  => 'Coupons',
			84  => 'Executive Gift',
			85  => 'Flowers',
			86  => 'Baskets',
			87  => 'Greeting Card',
			88  => 'Baby / Infant',
			89  => 'Party',
			90  => 'Religious',
			91  => 'Sympathy',
			92  => 'Valentine\'s Day',
			93  => 'Wedding',
			170 => 'Personalized',
		);
		break;
	// Home / Family Subcategories
	case 11:
		$list = array(
			94  => 'Bed/Bath',
			95  => 'Garden',
			96  => 'Cleaning / Care',
			97  => 'Furniture',
			98  => 'Home D�cor',
			99  => 'Home Improvement',
			100 => ' Kitchen',
			101 => ' Pets',
		);
		break;

	// Personal Care Subcategories
	case 12:
		$list = array(
			102 => 'Cosmetics',
			103 => 'Exercise / Wellnes',
			104 => 'Safety',
			183 => 'Medical',
		);
		break;
	// Sports / Outdoors Subcategories
	case 13:
		$list = array(
			105 => 'Accessorie',
			106 => 'Auto',
			107 => 'Outdoors / Camping',
			108 => 'Parlor / Backyard Games',
			109 => 'Baseball / Softball',
			110 => 'Cricket',
			111 => 'Billiards',
			112 => 'Boating',
			113 => 'Body Building / Fitness',
			114 => 'Bowling',
			115 => 'Boxing',
			116 => 'Canoeing',
			117 => 'Climbing / Mountaineering',
			118 => 'Cycling',
			119 => 'Diving',
			120 => 'Field Hockey',
			121 => 'Skating',
			122 => 'Fishing',
			123 => 'Football',
			124 => 'Frisbee',
			125 => 'Golf',
			126 => 'Gymnastics',
			127 => 'Hockey',
			128 => 'Horses',
			129 => 'Hunting',
			130 => 'In-line Skating',
			131 => 'Kayaking',
			132 => 'Lacrosse',
			133 => 'Martial Arts',
			134 => 'Racquetball',
			135 => 'Running',
			136 => 'Skateboards',
			137 => 'Ski/Snowboard',
			138 => 'Soccer',
			139 => 'Surfing',
			140 => 'Tennis',
			141 => 'Teamware / Logo',
			142 => 'Volleyball',
			143 => 'Wrestling',
			165 => 'Birding',
			174 => 'Prospecting / Treasure Hunting',
			175 => 'Swimming',
			178 => 'Basketball',

		);
		break;
	// Toys / Games Subcategories
	case 14:
		$list = array(
			144 => 'Action',
			145 => 'Animals',
			146 => 'Baby / Infant',
			147 => 'Board Games',
			148 => 'Card / Casino',
			149 => 'Electroni',
			150 => 'Educational',
			151 => 'Magic',
			152 => 'Misc.',
			153 => 'Musical',
			154 => 'Outdoor',
			155 => 'Video',
		);
		break;

	// Travel Subcategories
	case 15:
		$list = array(
			156 => 'Coupons',
			157 => 'Maps',
			158 => 'References / Guides',
			159 => 'Vacation Travel',

		);
		break;
	// Metaphysical Subcategories
	case 16:
		$list = array(
			160 => 'Metaphysical',
		);
		break;
}
$saved_sub_categories = ! empty( get_option( 'cpf_remote_sub_category_' . $feed_identifier ) ) ? get_option( 'cpf_remote_sub_category_' . $feed_identifier ) : false;
$optionList           = '<select name="sas_sub_category_list" id="sas_sub_category_list_' . $feed_type . '" onchange="set_remote_sub_category(this.value, ' . $feed_type . ')" style="width: 250px">';
$optionList          .= '<option value="0">Select Sub-Category</option>';
foreach ( $list as $key => $category ) {
	$selected    = ( $saved_sub_categories && $key == $saved_sub_categories ) ? 'selected' : '';
	$optionList .= '<option value=' . "$key" . ' ' . $selected . '>' . $category . '</option>';
}
$optionList .= '</select>';
$categories  = '';
$categories .= $optionList;
$categories .= '</div>';
echo json_encode(
	array(
		'status' => true,
		'data'   => $categories,
	)
);
die;
