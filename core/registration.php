<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/********************************************************************
 * Check the license key
 * Version 2.1
 * By: Keneto 2014-05-12
 * 2014-08: Added GTS plugin class PLicenseGTS
 * License    GNU General Public License version 3 or later; see GPLv3.txt
 ********************************************************************/
class EXCPF_PLicense {


	public $debugmode               = false;
	public $error_message           = '';
	public $results                 = array();
	public $valid                   = false;
	protected $strErrorMsgNolicense = '  No license Key (limited to 100 items per feed). Get the pro version or a free trial key at: <a target=\'_blank\' href = \'http://exportfeed.com/\'>exportfeed.com</a> ';
	protected $strErrorMsgMain      = '  You are using the 7 day trial version. Get the pro version or a free trial key at: <a target=\'_blank\' href = \'http://exportfeed.com/\'>exportfeed.com</a> ';
	protected $strErrorMsgInactive  = 'Your License has been expired. Get the pro version or a free trial key at: <a target=\'_blank\' href = \'http://exportfeed.com/\'>exportfeed.com</a> ';
	protected $strErrorMsgExpired   = 'Your License has been expired. Get the pro version or a free trial key at: <a target=\'_blank\' href = \'http://exportfeed.com/\'>exportfeed.com</a> ';

	protected $strLicenseKey     = 'cp_licensekey';
	protected $strLocalKey       = 'cp_localkey';
	protected $strLicenseKeyOld  = 'purplexml_licensekey';
	protected $strLocalKeyOld    = 'purplexml_localkey';
	protected $strRapidcartToken = 'cp_rapidcarttoken';

	function __construct( $debug = false ) {

		global $pfcore;

		$rapidToken = $pfcore->settingGet( $this->strRapidcartToken );
		// When loading license key, we must be careful to check for valid licenses from prior versions
		$licensekey = $pfcore->settingGet( $this->strLicenseKey );
		if ( strlen( $licensekey ) == 0 ) {
			// Look for old version of key
			$licensekey = $pfcore->settingGet( $this->strLicenseKeyOld );
			if ( strlen( $licensekey ) > 0 ) {
				$pfcore->settingSet( $this->strLicenseKey, $licensekey );
			}
		}

		$localkey = $pfcore->settingGet( $this->strLocalKey );
		if ( strlen( $localkey ) == 0 ) {
			// Look for old version of key
			$localkey = $pfcore->settingGet( $this->strLocalKeyOld );
			if ( strlen( $localkey ) > 0 ) {
				$pfcore->settingSet( $this->strLocalKey, $localkey );
			}
		}

		$this->debugmode = $debug;
		if ( $this->debugmode ) {
			echo 'License Key: ' . esc_html( $licensekey ) . " \r\n";
			echo 'Local Key: ' . esc_html( $localkey ) . " \r\n";
			echo 'RapidCart Token: ' . esc_html( $rapidToken ) . " \r\n";
		}

		$this->results['status'] = 'Invalid';
		$this->error_message     = '';

		// If there are keys set, remember this fact
		$hasKeys = false;
		if ( strlen( $localkey ) > 0 || strlen( $licensekey ) > 0 ) {
			$hasKeys = true;
		}

		$this->checkLicense( $licensekey, $localkey );
		$this->results['checkmd5x'] = new EXCPF_md5x( $licensekey . $localkey, $this->results );
		if ( $this->results['status'] == 'Active' ) {
			$this->valid = true;

		} elseif ( $hasKeys ) {
			$this->error_message = 'License Key Invalid: ' . $this->error_message;
		}

		return true;
	}


	function checkLicense( $licensekey, $localkey = '' ) {
		global $wpdb;
		// echo '<pre>';print_r($_SERVER);die;
		// initial values
		// $whmcsurl = 'https://shop.shoppingcartproductfeed.com/'; Old url
		$protocol             = is_ssl() ? 'https://' : 'http://';
		$whmcsurl             = $protocol . 'shop.exportfeed.com/';
		$licensing_secret_key = '437682532'; // Unique value, should match what is set in the product configuration for MD5 Hash Verification
		$check_token          = time() . md5( mt_rand( 1000000000, mt_getrandmax() ) . $licensekey );
		$checkdate            = date( 'Ymd' ); // Current date
		if ( ! isset( $_SERVER['SERVER_ADDR'] ) && ! isset( $_SERVER['LOCAL_ADDR'] ) ) {
			$sql     = 'SELECT option_value FROM `' . $wpdb->options . "` where option_name like '%_transient_external_ip_address%'";
			$usersip = $wpdb->get_var( $sql );
		} else {
			$usersip = isset( $_SERVER['SERVER_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_ADDR'] ) ) : sanitize_text_field( wp_unslash( $_SERVER['LOCAL_ADDR'] ) );
		}

		$localkeydays       = 1; // How long the local key is valid for in between remote checks
		$allowcheckfaildays = 0; // How many days to allow after local key expiry before blocking access if connection cannot be made
		$localkeyvalid      = false;
		$originalcheckdate  = '';
		$localexpiry        = 0;
		$status             = '';

		$Results = $this->validateLocalKey( $localkey, $localkeydays, $licensing_secret_key, $localexpiry, $usersip );
		if ( $Results ) {
			if ( $this->debugmode ) {
				echo "Return After validation. \r\n";
			}
			foreach ( $Results as $k => $result ) {
				$this->results[ $k ] = $result;
			}
			return;
		}

		$domain = get_option( 'siteurl' ); // or home
		$domain = str_replace( $protocol, '', $domain );
		$domain = str_replace( 'www', '', $domain ); // add the . after the www if you don't want it
		$domain = strstr( $domain, '/', true ); // PHP5 only, this is in case WP is not root
		if ( isset( $_SERVER['SERVER_NAME'] ) ) {
			$domain = sanitize_text_field( wp_unslash( $_SERVER['SERVER_NAME'] ) );
		}
		$postfields['licensekey'] = $licensekey;
		// $postfields['domain'] = $_SERVER['SERVER_NAME'];
		$postfields['domain'] = $domain;
		$postfields['ip']     = $usersip;
		$postfields['dir']    = dirname( __FILE__ );

		// if ($postfields) {
		// if ($serverValue = get_option('CPF_LICENING_SERVER_DETAILS')) {
		// $postfields = maybe_unserialize($serverValue);
		// if ($postfields['licensekey'] !== $licensekey) {
		// $postfields['licensekey'] = $licensekey;
		// update_option('CPF_LICENING_SERVER_DETAILS', maybe_serialize($postfields));
		// }
		// } else {
		// update_option('CPF_LICENING_SERVER_DETAILS', maybe_serialize($postfields));
		// }
		// } TODO need to work in near future

		if ( $check_token ) {
			$postfields['check_token'] = $check_token;
		}
		$response = wp_remote_post(
			$whmcsurl . 'modules/servers/licensing/verify.php',
			array(
				'timeout'     => 60,
				'redirection' => 5,
				'blocking'    => true,
				'headers'     => array(
					'Expect' => '',
				),
				'body'        => $postfields,
			)
		);
		$code     = wp_remote_retrieve_response_code( $response );
		if ( $code !== 200 ) {
			$this->error_message = 'Curl error: ' . $code . wp_remote_retrieve_response_message( $response );
		}
		$data = wp_remote_retrieve_body( $response );
		if ( ! $data ) {
			if ( $this->debugmode ) {
				echo "Remote check failed. \r\n";
			}
			$this->error_message = 'Remote Check Failed. Please enable cURl to activate your license key.For more details please contact your hosting service.<br/>Check our FAQ for technical requirements: <a target=\'_blank\' href = \'http://www.exportfeed.com/faq/technical-requirement-use-plugin-wordpress-site/\'> Click Here</a>';
			return;
		}

		preg_match_all( '/<(.*?)>([^<]+)<\/\\1>/i', $data, $matches );
		$inputdata = array();
		foreach ( $matches[1] as $k => $v ) {
			$inputdata[ $v ]     = $matches[2][ $k ];
			$this->results[ $v ] = $matches[2][ $k ];
		}

		if ( isset( $inputdata['md5hash'] ) && $inputdata['md5hash'] ) {
			if ( $inputdata['md5hash'] != md5( $licensing_secret_key . $check_token ) ) {
				$this->error_message     = 'It seems that the licensekey you have entered does not exists in our records. Please contact our <a target="_blank" href="https://www.exportfeed.com/contact">Support</a> for further assistance.';
				$this->results['status'] = 'Inactive';
				if ( $this->debugmode ) {
					echo "MD5 Checksum Verification Failed. \r\n";
				}
				return;
			}
		}
		// $inputdata["status"]="Inactive";
		if ( isset( $inputdata['status'] ) && $inputdata['status'] == 'Active' ) {
			if ( $inputdata['productid'] == 88 ) {
				$this->error_message .= $this->strErrorMsgMain;
				if ( $this->debugmode ) {
					echo "FreeLicenseKey. \r\n";
				}
			}
			if ( $this->debugmode ) {
				echo "Status Active. \r\n";
			}
			$inputdata['checkdate'] = $checkdate;
			$data_encoded           = serialize( $inputdata );
			$data_encoded           = base64_encode( $data_encoded );
			$data_encoded           = md5( $checkdate . $licensing_secret_key ) . $data_encoded;
			$data_encoded           = strrev( $data_encoded );
			$data_encoded           = $data_encoded . md5( $data_encoded . $licensing_secret_key );
			$data_encoded           = wordwrap( $data_encoded, 80, "\n", true );
			$inputdata['localkey']  = $data_encoded;
			update_option( 'cp_localkey', $data_encoded );
		} elseif ( isset( $inputdata['status'] ) && $inputdata['status'] == 'Expired' ) {
			$this->error_message .= $this->strErrorMsgExpired;
			if ( $this->debugmode ) {
				echo "Expired. \r\n";
			}
		} else {
			$this->error_message .= $this->strErrorMsgNolicense;
			if ( $this->debugmode ) {
				echo "Inactive. \r\n";
			}
		}

		$this->inputdata['remotecheck'] = true;
		unset( $postfields, $data, $matches, $whmcsurl, $licensing_secret_key, $checkdate, $usersip, $localkeydays, $allowcheckfaildays, $md5hash );
	}

	function setLicenseKey( $licenseKey, $localKey ) {
		global $pfcore;
		$pfcore->settingSet( $this->strLicenseKey, $licenseKey );
		$pfcore->settingSet( $this->strLocalKey, $localKey );
	}

	function unregister() {
		global $pfcore;
		// This will remove the license key (which is likely an undesirable course of action)
		$pfcore->settingSet( $this->strLicenseKey, '' );
		$pfcore->settingSet( $this->strLocalKey, '' );
	}

	function unregisterAll() {
		global $pfcore;
		// Remove all stored license keys for all known products
		$pfcore->settingDelete( 'cp_licensekey' );
		$pfcore->settingDelete( 'cp_localkey' );
		$pfcore->settingDelete( 'cp_rapidcarttoken' );
		$pfcore->settingDelete( 'purplexml_licensekey' );
		$pfcore->settingDelete( 'purplexml_localkey' );
		$pfcore->settingDelete( 'gts_licensekey' );
		$pfcore->settingDelete( 'gts_localkey' );
		$pfcore->settingDelete( 'fv_licensekey' );
		$pfcore->settingDelete( 'fv_localkey' );
	}

	function validateLocalKey( $localkey, $localkeydays, $licensing_secret_key, $localexpiry, $usersip ) {
		if ( ! $localkey ) {
			return false;
		}

		$localkey  = str_replace( "\n", '', $localkey ); // Remove the line breaks
		$localdata = substr( $localkey, 0, strlen( $localkey ) - 32 ); // Extract License Data
		$md5hash   = substr( $localkey, strlen( $localkey ) - 32 ); // Extract MD5 Hash

		if ( $md5hash != md5( $localdata . $licensing_secret_key ) ) {
			return false;
		}

		$localdata         = strrev( $localdata ); // Reverse the string
		$md5hash           = substr( $localdata, 0, 32 ); // Extract MD5 Hash
		$localdata         = substr( $localdata, 32 ); // Extract License Data
		$localdata         = base64_decode( $localdata );
		$localkeyresults   = unserialize( $localdata );
		$originalcheckdate = $localkeyresults['checkdate'];

		if ( $md5hash != md5( $originalcheckdate . $licensing_secret_key ) ) {
			return false;
		}

		$locheck_licensecalexpiry = date( 'Ymd', mktime( 0, 0, 0, date( 'm' ), date( 'd' ) - $localkeydays, date( 'Y' ) ) );

		if ( $originalcheckdate < $locheck_licensecalexpiry ) {
			return false;
		}

		$validdomains = explode( ',', $localkeyresults['validdomain'] );
		if ( ! in_array( $_SERVER['SERVER_NAME'], $validdomains ) ) {
			$this->error_message = 'Valid domain incorrect. ';
			return false;
		}

		$validips = explode( ',', $localkeyresults['validip'] );
		if ( ! in_array( $usersip, $validips ) ) {
			$this->error_message = 'IP incorrect. ';
			return false;
		}

		if ( $localkeyresults['validdirectory'] != dirname( __FILE__ ) ) {
			$this->error_message = 'Valid directory mismatch. ';
			return false;
		}
		return $localkeyresults;
	}
}

class EXCPF_PLicenseGTS extends EXCPF_PLicense {


	function __construct() {
		$this->strErrorMsgMain = '- Register for the pro version to get full functionality: <a target=\'_blank\' href = \'http://exportfeed.com/\'>exportfeed.com</a> ';
		$this->strLicenseKey   = 'gts_licensekey';
		$this->strLocalKey     = 'gts_localkey';
		parent::__construct();
	}
}

class EXCPF_PLicenseFV extends EXCPF_PLicense {


	function __construct() {
		$this->strErrorMsgMain = '- Register for the pro version to get full functionality: <a target=\'_blank\' href = \'http://exportfeed.com/\'>exportfeed.com</a> ';
		$this->strLicenseKey   = 'fv_licensekey';
		$this->strLocalKey     = 'fv_localkey';
		$this->strLocalKeyOld  = '';
		$this->strLocalKeyOld  = '';
		parent::__construct();
	}
}
