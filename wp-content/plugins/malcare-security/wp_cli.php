<?php
if (!defined('ABSPATH')) exit;
if (!class_exists('MCWPCli')) :

require_once dirname( __FILE__ ) . '/recover.php';

class MCWPCli {
	public $settings;
	public $siteinfo;
	public $bvinfo;
	public $bvapi;

	public function __construct($settings, $bvinfo, $bvsiteinfo, $bvapi ) {
		$this->settings = $settings;
		$this->siteinfo = $bvsiteinfo;
		$this->bvinfo = $bvinfo;
		$this->bvapi = $bvapi;
	}

	public function request($url, $request_params = array(), $headers = array()) {
		$resp = $this->bvapi->http_request($url, $request_params, $headers);
		$this->handle_response($resp);
	}

	public function handle_response($resp) {
		if (empty($resp)) {
			WP_CLI::error("Error in connecting to MalCare Server. Please retry after some time.");
		} else if (is_wp_error($resp)) {
			$error_message = "";	
			if (isset($resp->errors["http_request_failed"][0])) {
				$error_message = $resp->errors["http_request_failed"][0];
			} else {
				$error_message = "WPError request params empty";
			}
			WP_CLI::error("{$error_message} . Please retry after sometime or contact us.");
		} else {
			if (isset($resp["response"])) {
				if (isset($resp["response"]["code"])) {
					$resp_code = $resp["response"]["code"];
					if ($resp_code == 200) {
						if (isset($resp["body"])) {
							$body = json_decode($resp["body"], true);
							if (isset($body["error"])) {
								WP_CLI::error("code: {$resp_code} -- message: {$body["error"]} . Please retry or contact us");
							} else if (isset($body["message"])) {
								WP_CLI::success("code: {$resp_code} -- message: {$body["message"]}");
							} else {
								WP_CLI::error("Invalid Response. Please retry or contact us.");
							}
						} else {
							WP_CLI::error("Invalid Response. Please retry or contact us.");
						}
					} else if (preg_match("/^4[0-9][0-9]$/", strval($resp_code))) {
						if (isset($resp["body"])) {
							WP_CLI::error("code: {$resp_code} -- message: {$resp["body"]}");
						} else {
							WP_CLI::error("Invalid Response. Please retry or contact us.");
						}
					} else {
						if (isset($resp["response"]["message"])) {
							WP_CLI::error("code: {$resp_code} -- message: {$resp["response"]["message"]} . Please retry or contact us");
						} else {
							WP_CLI::error("Invalid Response. Please retry or contact us.");
						}
					}
				} else {
					WP_CLI::error("Invalid Response. Please retry or contact us.");
				}
			} else {
				WP_CLI::error("Invalid Response. Please retry or contact us.");
			}
		}
	}

	public function execute($args, $params) {
		switch ($params['action']) {
		case "setkeys":
			if (!isset($params['public']) || !isset($params['secret'])) {
				WP_CLI::error('Please enter valid public and secret keys.');
			}
			$secret = $params['secret'];
			$pubkey = $params['public'];
			if (strlen($pubkey) < 32 || strlen($secret) < 32) {
				WP_CLI::error('Public key and secret key should be 32 characters long.');
			}
			MCAccount::addAccount($this->settings, $pubkey, $secret);
			MCAccount::updateApiPublicKey($this->settings, $pubkey);
			if (MCAccount::exists($this->settings, $pubkey)) {
				WP_CLI::success('Keys Setup Successfully.');
			} else {
				WP_CLI::error('Keys Setup Failed.');
			}
			break;
		case "register":
			$request_params = array_merge($this->siteinfo->info(), $this->bvinfo->info());
			$request_params['bvpublic'] = MCAccount::getApiPublicKey($this->settings);
			$request_params['bvsecret'] = MCRecover::defaultSecret($this->settings);
			$url = $this->bvinfo->appUrl()."/api/v2/sites/register";
			foreach (preg_grep('#time|customer_id|host_id|action|sig|site_id|email|password#i', array_keys($params)) as $key ) {
				$request_params[$key] = $params[$key];
			}
			$headers = array(
				'Authorization' => "BVAPI-{HMAC-SHA512} {$request_params['host_id']}:{$request_params['sig']}:{$request_params['time']}"
			);
			$this->request($url, $request_params, $headers);
			break;
		case "disable_fw":
			$account = MCAccount::apiPublicAccount($this->settings);
			if (!$account) {
				WP_CLI::error('Account not found');
			}
			$this->request($account->authenticatedUrl('/bvapi/disable_fw'));
			break;
		case "enable_fw":
			$account = MCAccount::apiPublicAccount($this->settings);
			if (!$account) {
				WP_CLI::error('Account not found.');
			}
			$this->request($account->authenticatedUrl('/bvapi/enable_fw'));
			break;

		default:
			WP_CLI::error('Please Enter a valid action');
		}
	}
}
endif;