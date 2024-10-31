<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
/********************************************************************
 * Make a database entry about the feed that just occurred
 * Copyright 2014 Purple Turtle Productions. All rights reserved.
 * license    GNU General Public License version 3 or later; see GPLv3.txt
 * By: Keneto 2014-06-16
 ********************************************************************/
class EXCPF_PFeedActivityLog {


	function __construct( $feedIdentifier = '' ) {
		// When instantiated (as opposed to static calls) it means we need to log the phases
		// therefore, save the feedIdentifier
		$this->feedIdentifier = $feedIdentifier;
	}

	function __destruct() {
		global $pfcore;
		if ( ! empty( $pfcore ) && ( strlen( $pfcore->callSuffix ) > 0 ) ) {
			$deleteLogData = 'deleteLogData' . $pfcore->callSuffix;
			$this->$deleteLogData();
		}
	}

	/********************************************************************
	 * Add a record to the activity log for "Manage Feeds"
	 ********************************************************************/

	private static function addNewFeedData( $category, $remote_category, $file_name, $file_path, $providerName, $productCount, $miinto_country_code = null, $feedIdentifier ) {
		global $pfcore;
		$addNewFeedData = 'addNewFeedData' . $pfcore->callSuffix;
		self::$addNewFeedData( $category, $remote_category, $file_name, $file_path, $providerName, $productCount, $miinto_country_code, $feedIdentifier );
	}

	private static function addNewFeedDataJ( $category, $remote_category, $file_name, $file_path, $providerName, $productCount ) {
		$date = JFactory::getDate();
		$user = JFactory::getUser();
		$db   = JFactory::getDBO();

		$sql = 'SELECT COUNT(*) FROM #__cartproductfeed_feeds';
		$db->setQuery( $sql );
		$db->query();
		$ordering = $db->loadResult() + 1;

		$newData                  = new stdClass();
		$newData->title           = $file_name;
		$newData->category        = $category;
		$newData->remote_category = $remote_category;
		$newData->filename        = $file_name;
		$newData->url             = $file_path;
		$newData->type            = $providerName;
		$newData->product_count   = $productCount;
		$newData->ordering        = $ordering;
		$newData->created         = $date->toSql();
		$newData->created_by      = $user->get( 'id' );
		// $newData->catid int,
		$newData->modified    = $date->toSql();
		$newData->modified_by = $user->get( 'id' );
		// $productCount
		$db->insertObject( '#__cartproductfeed_feeds', $newData, 'id' );
	}

	private static function addNewFeedDataJH( $category, $remote_category, $file_name, $file_path, $providerName, $productCount ) {
		self::addNewFeedDataJ( $category, $remote_category, $file_name, $file_path, $providerName, $productCount );
	}

	private static function addNewFeedDataJS( $category, $remote_category, $file_name, $file_path, $providerName, $productCount ) {

		global $pfcore;
		$shopID = $pfcore->shopID;

		$date = JFactory::getDate();
		$user = JFactory::getUser();
		$db   = JFactory::getDBO();

		$sql = 'SELECT COUNT(*) FROM #__cartproductfeed_feeds';
		$db->setQuery( $sql );
		$db->query();
		$ordering = $db->loadResult() + 1;

		$newData                  = new stdClass();
		$newData->title           = substr( $file_name, 3 );
		$newData->category        = $category;
		$newData->remote_category = $remote_category;
		$newData->filename        = $file_name;
		$newData->url             = $file_path;
		$newData->type            = $providerName;
		$newData->product_count   = $productCount;
		$newData->ordering        = $ordering;
		$newData->created         = $date->toSql();
		$newData->created_by      = $user->get( 'id' );
		// $newData->catid int,
		$newData->modified    = $date->toSql();
		$newData->modified_by = $user->get( 'id' );
		$newData->shop_id     = $shopID;
		// $productCount
		$db->insertObject( '#__cartproductfeed_feeds', $newData, 'id' );
	}

	private static function addNewFeedDataW( $category, $remote_category, $file_name, $file_path, $providerName, $productCount, $miinto_country_code = null, $feedIdentifier ) {
		global $wpdb;
		global $pfcore;
		$feed_type = $pfcore->feedType;

		$product_details = '';

		if ( intval( $pfcore->feedType ) == 1 ) {
			$feed_type       = 1;
			$product_details = null;
		}
		if ( intval( $pfcore->feedType ) == 0 ) {
			$feed_type       = 0;
			$product_details = null;
		}
		$feed_table = $wpdb->prefix . 'cp_feeds';
		if ( $wpdb->query( $wpdb->prepare( "INSERT INTO $feed_table(`category`, `remote_category`, `filename`, `url`, `type`, `product_count`,`feed_type`, `feed_identifier`, `product_details` , `miinto_country_code`) VALUES ('$category','$remote_category','$file_name','$file_path','$providerName', '$productCount','$feed_type','$feedIdentifier','$product_details' , '$miinto_country_code')" ) ) ) {
			$insertID = $wpdb->insert_id;
			if ( $feed_type == 1 ) {
				$csfeedtbl = $wpdb->prefix . 'cpf_customfeeds';
				if ( $wpdb->update(
					$csfeedtbl,
					array(
						'status'  => '1',
						'feed_id' => $insertID,
					),
					array( 'feed_identifier' => $feedIdentifier )
				) ) {
					if ( $wpdb->delete( $csfeedtbl, array( 'status' => '0' ) ) ) {
						return true;
					}
				}
				return false;
			} else {
				return true;
			}
		}
		return false;
	}

	private static function addNewFeedDataWe( $category, $remote_category, $file_name, $file_path, $providerName, $productCount ) {
		self::addNewFeedDataW( $category, $remote_category, $file_name, $file_path, $providerName, $productCount );
	}

	/********************************************************************
	 * Search the DB for a feed matching filename / providerName
	 ********************************************************************/

	public static function feedDataToID( $file_name, $providerName ) {
		global $pfcore;
		$feedDataToID = 'feedDataToID' . $pfcore->callSuffix;
		return self::$feedDataToID( $file_name, $providerName );
	}

	private static function feedDataToIDJ( $file_name, $providerName ) {
		$db    = JFactory::getDBO();
		$query = "
			SELECT id
			FROM #__cartproductfeed_feeds
			WHERE filename='$file_name' AND type='$providerName'";
		$db->setQuery( $query );
		$db->query();
		$result = $db->loadObject();
		if ( ! $result ) {
			return -1;
		}

		return $result->id;

	}

	private static function feedDataToIDJH( $file_name, $providerName ) {

		return self::feedDataToIDJ( $file_name, $providerName );

	}

	private static function feedDataToIDJS( $file_name, $providerName ) {

		global $pfcore;
		$shopID = $pfcore->shopID;

		$db = JFactory::getDBO();
		$db->setQuery(
			'
			SELECT id
			FROM #__cartproductfeed_feeds
			WHERE (filename=' . $db->quote( $file_name ) . ') AND (type=' . $db->quote( $providerName ) . ') AND (shop_id = ' . (int) $shopID . ')'
		);
		$result = $db->loadObject();
		if ( ! $result ) {
			return -1;
		}

		return $result->id;

	}

	private static function feedDataToIDW( $file_name, $providerName ) {
		global $wpdb;
		$feed_table    = $wpdb->prefix . 'cp_feeds';
		$list_of_feeds = $wpdb->get_results( $wpdb->prepare( "SELECT * from $feed_table WHERE `filename`='$file_name' AND `type`='$providerName'" ), ARRAY_A );
		if ( $list_of_feeds ) {
			return $list_of_feeds[0]['id'];
		} else {
			return -1;
		}
	}

	private static function feedDataToIDWe( $file_name, $providerName ) {
		return self::feedDataToIDW( $file_name, $providerName );
	}

	/********************************************************************
	 * Called from outside... this class has to make sure the feed shows under "Manage Feeds"
	 ********************************************************************/

	public static function updateFeedList( $category, $remote_category, $file_name, $file_path, $providerName, $productCount, $miinto_country_code = null, $feedIdentifier = null ) {
		$id = self::feedDataToID( $file_name, $providerName );
		if ( $id == -1 ) {
			self::addNewFeedData( $category, $remote_category, $file_name, $file_path, $providerName, $productCount, $miinto_country_code, $feedIdentifier );
		} else {
			self::updateFeedData( $id, $category, $remote_category, $file_name, $file_path, $providerName, $productCount, $miinto_country_code, $feedIdentifier );
		}
	}

	public static function updateCustomFeedList( $category, $remote_category, $file_name, $file_path, $providerName, $productCount, $miinto_country_code = null, $feedIdentifier ) {
		$category = implode( ',', $category );
		$id       = self::feedDataToID( $file_name, $providerName );
		if ( $id == -1 ) {
			self::addNewFeedData( $category, $remote_category, $file_name, $file_path, $providerName, $productCount, $miinto_country_code, $feedIdentifier );
		} else {
			self::updateFeedData( $id, $category, $remote_category, $file_name, $file_path, $providerName, $productCount, $miinto_country_code, $feedIdentifier );
		}
	}

	/********************************************************************
	 * Update a record in the activity log
	 ********************************************************************/

	private static function updateFeedData( $id, $category, $remote_category, $file_name, $file_path, $providerName, $productCount, $miinto_country_code = null, $feedIdentifier ) {
		global $pfcore;
		$updateFeedData = 'updateFeedData' . $pfcore->callSuffix;
		self::$updateFeedData( $id, $category, $remote_category, $file_name, $file_path, $providerName, $productCount, $miinto_country_code, $feedIdentifier );
	}

	private static function updateFeedDataJ( $id, $category, $remote_category, $file_name, $file_path, $providerName, $productCount ) {

		$date = JFactory::getDate();
		$user = JFactory::getUser();
		$db   = JFactory::getDBO();

		$newData                  = new stdClass();
		$newData->id              = $id;
		$newData->category        = $category;
		$newData->remote_category = $remote_category;
		$newData->filename        = $file_name;
		$newData->url             = $file_path;
		$newData->type            = $providerName;
		$newData->product_count   = $productCount;
		$newData->modified        = $date->toSql();
		$newData->modified_by     = $user->get( 'id' );
		// $productCount
		$db->updateObject( '#__cartproductfeed_feeds', $newData, 'id' );
	}

	private static function updateFeedDataJH( $id, $category, $remote_category, $file_name, $file_path, $providerName, $productCount ) {

		self::updateFeedDataJ( $id, $category, $remote_category, $file_name, $file_path, $providerName, $productCount );

	}

	private static function updateFeedDataJS( $id, $category, $remote_category, $file_name, $file_path, $providerName, $productCount ) {

		$date = JFactory::getDate();
		$user = JFactory::getUser();
		$db   = JFactory::getDBO();

		// global $pfcore;
		// $shopID = $pfcore->shopID;

		$newData                  = new stdClass();
		$newData->id              = $id;
		$newData->category        = $category;
		$newData->remote_category = $remote_category;
		$newData->filename        = $file_name;
		$newData->url             = $file_path;
		$newData->type            = $providerName;
		$newData->product_count   = $productCount;
		$newData->modified        = $date->toSql();
		$newData->modified_by     = $user->get( 'id' );
		// $newData->shop_id = $shopID;
		// $productCount

		$db->updateObject( '#__cartproductfeed_feeds', $newData, 'id' );

	}

	private static function updateFeedDataW( $id, $category, $remote_category, $file_name, $file_path, $providerName, $productCount, $miinto_country_code = null, $feedIdentifier ) {
		global $wpdb;
		global $pfcore;
		$product_details = '';
		if ( $pfcore->feedType == 1 ) {
			$feed_type       = 1;
			$product_details = null;
		} else {
			$feed_type       = 0;
			$product_details = null;
		}
		$feed_table = $wpdb->prefix . 'cp_feeds';

		if ( $wpdb->query(
			$wpdb->prepare(
				"
		UPDATE $feed_table 
		SET 
							`category`='$category',
							`remote_category`='$remote_category',
							`filename`='$file_name',
							`url`='$file_path',
							`type`='$providerName',
							`product_count`='$productCount',
							`feed_type` = '$feed_type',
							`product_details` = '$product_details',
							`miinto_country_code` = '$miinto_country_code',
							`feed_identifier` = '$feedIdentifier',
							`updated_at` = date('Y-m-d H:i:s')
		WHERE `id`=$id"
			)
		) ) {
			if ( $feed_type == 1 ) {
				$insertID  = $id;
				$csfeedtbl = $wpdb->prefix . 'cpf_customfeeds';
				if ( $wpdb->update(
					$csfeedtbl,
					array(
						'status'  => '1',
						'feed_id' => $insertID,
					),
					array( 'feed_identifier' => $feedIdentifier )
				) ) {
					if ( $wpdb->delete( $csfeedtbl, array( 'status' => '0' ) ) ) {
						return true;
					}
				}
				return false;
			} else {
				return true;
			}
		}
		return false;
	}

	private static function updateFeedDataWe( $id, $category, $remote_category, $file_name, $file_path, $providerName, $productCount ) {
		self::updateFeedDataW( $id, $category, $remote_category, $file_name, $file_path, $providerName, $productCount );
	}

	/********************************************************************
	 * Save a Feed Phase
	 ********************************************************************/

	function logPhase( $activity ) {
		global $pfcore;
		$pfcore->settingSet( 'cp_feedActivity_' . $this->feedIdentifier, $activity );
	}

	/********************************************************************
	 * Remove Log info
	 ********************************************************************/

	function deleteLogDataJ() {

	}

	function deleteLogDataJH() {

	}

	function deleteLogDataJS() {

	}

	function deleteLogDataW() {
		delete_option( 'cp_feedActivity_' . $this->feedIdentifier );
	}

	function deleteLogDataWe() {
		delete_option( 'cp_feedActivity_' . $this->feedIdentifier );
	}

}
