<?php
  /********************************************************************
	Version 2.0
		Edit a feed's basic information
		Copyright 2014 Purple Turtle Productions. All rights reserved.
		license GNU General Public License version 3 or later; see GPLv3.txt
   ********************************************************************/
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
class EXCPF_PEditFeedDialog {

	public static function pageBody( $feed_id, $feed_type ) {

		require_once dirname( __FILE__ ) . '/../data/savedfeed.php';
		require_once 'dialogbasefeed.php';

		if ( $feed_id == 0 ) {
			return;
		}

		$feed = new EXCPF_PSavedFeed( $feed_id );

			// Figure out the dialog for the provider
			$dialog_file = dirname( __FILE__ ) . '/../feeds/' . strtolower( $feed->provider ) . '/dialognew.php';
		if ( file_exists( $dialog_file ) ) {
			require_once $dialog_file;
		}

			// Instantiate the dialog
			$provider        = 'EXCPF_' . $feed->provider . 'Dlg';
			$provider_dialog = new $provider();

			echo esc_html( $provider_dialog->mainDialog( $feed, $feed_type ) );
	}

}
