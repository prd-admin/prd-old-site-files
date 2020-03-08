<?php

if (!defined('ABSPATH')) exit;
if (!class_exists('BVProtectCallback')) :

require_once dirname( __FILE__ ) . '/../../protect/wp/protect.php';
require_once dirname( __FILE__ ) . '/../../protect/fw/config.php';
require_once dirname( __FILE__ ) . '/../../protect/wp/lp/config.php';

class BVProtectCallback extends BVCallbackBase {
	public $db;
	public $settings;

	public function __construct($callback_handler) {
		$this->db = $callback_handler->db;
		$this->settings = $callback_handler->settings;
	}

	public function contentDir() {
		return defined('WP_CONTENT_DIR') ? WP_CONTENT_DIR : "";
	}

	public function mcDataPath() {
		return $this->contentDir() . '/mc_data/';
	}

	public function mcIPsFilePath($confkey) {
		return $this->mcDataPath() . $confkey. '-mc_ips.conf';
	}

	public function mcConfigFilePath($confkey) {
		return $this->mcDataPath() . $confkey. '-mc.conf';
	}

	public function mcLogFilePath($confkey) {
		return $this->mcDataPath() . $confkey. '-mc.log';
	}

	public function mcWAFFilePath() {
		return ABSPATH . 'malcare-waf.php';
	}

	public function htAccessFilePath() {
		return ABSPATH . '.htaccess';
	}

	public function useriniFilePath() {
		return ABSPATH . '.user.ini';
	}

	public function writeToFile($file, $content, $permissions) {
		$tmp_file = tmpfile();
		$result = array();

		if (!$tmp_file) {
			$result = array('status' => 'Error', 'message' => 'UNABLE_TO_CREATE_TMPFILE');
		} else {
			$tmpmeta = stream_get_meta_data($tmp_file);
			$tmpfilename = $tmpmeta['uri'];

			if (file_put_contents($tmpfilename, $content)) {

				$tmp_contents = file_get_contents($tmpfilename);

				if ($tmp_contents !== $content) {
					$result = array('status' => 'Error', 'message' => 'TMPFILE_CONTENT_MISMATCH');
				} else if (!@rename($tmpfilename, $file)) {
					$result = array('status' => 'Error', 'message' => 'UNABLE_TO_RENAME_TMPFILE');
				} else {
					chmod($file, $permissions);
					$fcontent = file_get_contents($file);

					if ($fcontent !== $content) {
						$result = array(
							'status' => 'Error',
							'message' => 'FILE_NOT_WRITTEN_PROPERLY',
							'content' => $fcontent
						);
					} else {
						$result = array('status' => 'Done');
					}

				}
			} else {
				$result = array('status' => 'Error', 'message' => 'UNABLE_TO_WRITE_IN_TMPFILE');
			}
		}
		return $result;
	}

	public function writeToMcDataFile($fname, $content, $confkey, $permissions) {
		$result = array();
		if (in_array($fname, BVFWConfig::$validMcDataFilenames)) {

			$mcDataPath = $this->mcDataPath();

			if (file_exists($mcDataPath)) {
				$filepath = $mcDataPath . $confkey . '-' . $fname;
				$result = $this->writeToFile($filepath, $content, $permissions);
			} else {
				$result['status'] = 'Error';
				$result['message'] = 'MC_DATA_PATH_DOES_NOT_EXISTS';
			}

		} else {
			$result['status'] = 'Error';
			$result['message'] = 'INVALID_FILE_NAME';
		}
		return $result;
	}

	public function getMalcareWAFContent($confkey) {
		return sprintf(
			'<?php
// Please validate auto_prepend_file setting before removing this file

if (file_exists(%1$s)) {
  define("MCDATAPATH", %2$s);
  define("MCCONFKEY", %3$s);
  include_once(%1$s);
}
?>',
				var_export(MCBASEPATH . 'protect/prepend/ignitor.php', true),
				var_export($this->mcDataPath(), true),
				var_export($confkey, true)
			);
	}

	public function configureMalcareWAF($confkey) {
		$result = array();
		if (!$confkey || $confkey === "") {
			$result['status'] = 'Error';
			$result['message'] = 'INVALID_CONFKEY';
		} else {

			$content = $this->getMalcareWAFContent($confkey);
			$result = $this->writeToFile($this->mcWAFFilePath(), $content, 0644);
		}

		return $result;
	}

	public function addPrependToHtaccess($user_ini, $mode) {
		$userIniHtaccessContent = '';
		if ($user_ini) {
			$userIniHtaccessContent = sprintf('<Files "%s">
<IfModule mod_authz_core.c>
  Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
  Order deny,allow
  Deny from all
</IfModule>
</Files>
', $user_ini);
	}
		
		switch ($mode) {
		case BVFWConfig::MODE_APACHEMODPHP:
			$htaccessPrependContent = sprintf("# MalCare WAF
<IfModule mod_php5.c>
  php_value auto_prepend_file '%s'
</IfModule>
<IfModule mod_php7.c>
  php_value auto_prepend_file '%s'
</IfModule>
$userIniHtaccessContent
# END MalCare WAF
", $this->mcWAFFilePath(), $this->mcWAFFilePath());
			break;

		case BVFWConfig::MODE_LITESPEED:
			$htaccessPrependContent = sprintf("# MalCare WAF
<IfModule LiteSpeed>
  php_value auto_prepend_file '%s'
</IfModule>
<IfModule lsapi_module>
  php_value auto_prepend_file '%s'
</IfModule>
$userIniHtaccessContent
# END MalCare WAF
", $this->mcWAFFilePath(), $this->mcWAFFilePath());
			break;

		case BVFWConfig::MODE_APACHESUPHP:
			$htaccessPrependContent = sprintf("# MalCare WAF
$userIniHtaccessContent
# END MalCare WAF
", ABSPATH);
			break;

		case BVFWConfig::MODE_CGI_FASTCGI:
			if ($userIniHtaccessContent) {
				$htaccessPrependContent = sprintf("# MalCare WAF
$userIniHtaccessContent
# END MalCare WAF
", ABSPATH);
			}
			break;

		}

		$result = array();
		$htaccessPath = $this->htAccessFilePath();

		if (!empty($htaccessPrependContent)) {
			$htaccessContent = '';
			if (file_exists($htaccessPath)) {
				$htaccessContent = file_get_contents($htaccessPath);
			}

			if (!empty($htaccessContent)) {
				$regex = '/# MalCare WAF.*?# END MalCare WAF/is';
				if (preg_match($regex, $htaccessContent, $matches)) {
					$htaccessContent = preg_replace($regex, $htaccessPrependContent, $htaccessContent);
				} else {
					$htaccessContent .= "\n\n" . $htaccessPrependContent;
				}
			} else {
				$htaccessContent = $htaccessPrependContent;
			}

			$result = $this->writeToFile($htaccessPath, $htaccessContent, 0755);

			if ($mode == BVFWConfig::MODE_LITESPEED) {
				touch($htaccessPath);
			}

		}

		if (file_exists($htaccessPath)) {
			$result['content'] = file_get_contents($htaccessPath);
		}

		return $result;
	}

	public function removePrependFromHtaccess() {
		$result = array();
		$htaccessPath = $this->htAccessFilePath();

		if (file_exists($htaccessPath)) {
			$htaccessContent = file_get_contents($htaccessPath);
			$regex = '/# MalCare WAF.*?# END MalCare WAF/is';

			if (preg_match($regex, $htaccessContent, $matches)) {
				$htaccessContent = preg_replace($regex, '', $htaccessContent);

				$result = $this->writeToFile($htaccessPath, $htaccessContent, 0755);
			}
		}

		if (file_exists($htaccessPath)) {
			$result['content'] = file_get_contents($htaccessPath);
		}

		return $result;
	}

	public function addPrependToUserini($mode) {
		switch ($mode) {
		case BVFWConfig::MODE_CGI_FASTCGI:
		case BVFWConfig::MODE_NGINX:
		case BVFWConfig::MODE_IIS:
		case BVFWConfig::MODE_LITESPEED:
		case BVFWConfig::MODE_APACHESUPHP:
			$useriniPrependContent = sprintf("; MalCare WAF
auto_prepend_file = '%s'
; END MalCare WAF
", $this->mcWAFFilePath());
			break;
		}

		$result = array();
		$useriniPath = $this->useriniFilePath();

		if (!empty($useriniPrependContent)) {
			$useriniContent = '';
			if (file_exists($useriniPath)) {
				$useriniContent = file_get_contents($useriniPath);
			}
			if (!empty($useriniContent)) {
				$useriniContent = str_replace('auto_prepend_file', ';auto_prepend_file', $useriniContent);
				$regex = '/; MalCare WAF.*?; END MalCare WAF/is';
				if (preg_match($regex, $useriniContent, $matches)) {
					$useriniContent = preg_replace($regex, $useriniPrependContent, $useriniContent);
				} else {
					$useriniContent .= "\n\n" . $useriniPrependContent;
				}
			} else {
				$useriniContent = $useriniPrependContent;
			}

			$result = $this->writeToFile($useriniPath, $useriniContent, 0755);
		}

		if (file_exists($useriniPath)) {
			$result['content'] = file_get_contents($useriniPath);
		}

		return $result;
	}

	public function removePrependFromUserini() {
		$result = array();
		$useriniPath = $this->UseriniFilePath();

		if (file_exists($useriniPath)) {
			$useriniContent = file_get_contents($useriniPath);
			$regex = '/; MalCare WAF.*?; END MalCare WAF/is';

			if (preg_match($regex, $useriniContent, $matches)) {
				$useriniContent = preg_replace($regex, '', $useriniContent);

				$result = $this->writeToFile($useriniPath, $useriniContent, 0755);
			}
		}

		if (file_exists($useriniPath)) {
			$result['content'] = file_get_contents($useriniPath);
		}

		return $result;
	}

	public function serverConfig() {
		return array(
			'software' => $_SERVER['SERVER_SOFTWARE'],
			'sapi' => (function_exists('php_sapi_name')) ? php_sapi_name() : false,
			'has_apache_get_modules' => function_exists('apache_get_modules'),
			'posix_getuid' => (function_exists('posix_getuid')) ? posix_getuid() : null,
			'uid' => (function_exists('getmyuid')) ? getmyuid() : null,
			'user_ini' => ini_get('user_ini.filename'),
			'php_major_version' => PHP_MAJOR_VERSION
		);
	}

	public function removeDir($dir) {
		$result = array();
		if (file_exists($dir)) {
			if (rmdir($dir)) {
				$result = array('status' => 'Done');
			} else {
				$result = array('status' => 'Error', 'message' => 'UNABLE_TO_REMOVE_DIR');
			}
		} else {
			$result = array('status' => 'Done', 'message' => 'DIR_DOESNOT_EXISTS');
		}

		return $result;
	}

	public function removeFile($filename) {
		$result = array();
		if (file_exists($filename)) {
			if (unlink($filename)) {
				$result = array('status' => 'Done');
			} else {
				$result = array('status' => 'Error', 'message' => 'UNABLE_TO_REMOVE_FILE');
			}
		} else {
			$result = array('status' => 'Done', 'message' => 'FILE_DOESNOT_EXISTS');
		}
		return $result;
	}

	public function unBlockLogins() {
		$this->settings->deleteTransient('bvlp_block_logins');
		$this->settings->setTransient('bvlp_allow_logins', 'true', 1800);
		return $this->settings->getTransient('bvlp_allow_logins');
	}

	public function blockLogins($time) {
		$this->settings->deleteTransient('bvlp_allow_logins');
		$this->settings->setTransient('bvlp_block_logins', 'true', $time);
		return $this->settings->getTransient('bvlp_block_logins');
	}

	public function unBlockIP($ip, $attempts, $time) {
		$transient_name = BVWPLP::$unblock_ip_transient.$ip;
		$this->settings->setTransient($transient_name, $attempts, $time);
		return $this->settings->getTransient($transient_name);
	}

	public function process($request) {
		$bvinfo = new MCInfo($this->settings);
		$params = $request->params;

		switch ($request->method) {
		case "gtipprobeinfo":
			$resp = array();
			$headers = $params['hdrs'];
			$hdrsinfo = array();
			if ($headers && is_array($headers)) {
				foreach($headers as $hdr) {
					if (array_key_exists($hdr, $_SERVER)) {
						$hdrsinfo[$hdr] = $_SERVER[$hdr];
					}
				}
			}
			$resp["hdrsinfo"] = $hdrsinfo;
			break;
		case "gtptcnf":
			$resp = array('conf' => $this->settings->getOption('bvptconf'));
			break;
		case "clrcnf":
			$this->settings->deleteOption('bvptconf');
			$this->settings->deleteOption('bvptplug');
			$resp = array("clearconfig" => true);
			break;
		case "docnf":
			$this->settings->updateOption('bvptconf', $params['conf']);
			$resp = array('conf' => $this->settings->getOption('bvptconf'));
			break;
		case "gtraddr":
			$raddr = array_key_exists('REMOTE_ADDR', $_SERVER) ? $_SERVER['REMOTE_ADDR'] : false;
			$resp = array("raddr" => $raddr);
			break;
		case "svrcnf":
			$resp = array("serverconfig" => $this->serverConfig());
			break;
		case "gtmcwafcntent":
			$resp = array('content' => $this->getMalcareWAFContent($params['confkey']));
			break;
		case "wrtmcdtafle":
			$permissions = array_key_exists('permissions', $params) ? $params['permissions'] : 0664;
			$confkey = $params['confkey'];
			$fname = $params['fname'];
			$content = $params['content'];
			$resp = array('writetomcdatafile' => $this->writeToMcDataFile($fname, $content, $confkey, $permissions));
			break;
		case "cnfgrewaf":
			$confkey = $params['confkey'];
			$resp = array('configurewaf' => $this->configureMalcareWAF($confkey));
			break;
		case "rmmcdta":
			$name = $params['name'];
			if (in_array($name, BVFWConfig::$validDeletableFiles)) {
				switch($name) {
				case "mc.conf":
					$name = $this->mcConfigFilePath($params['confkey']);
					$resp = $this->removeFile($name);
					break;
				case "mc_ips.conf":
					$name = $this->mcIPsFilePath($params['confkey']);
					$resp = $this->removeFile($name);
					break;
				case "malcare-waf.php":
					$name = $this->mcWAFFilePath();
					$resp = $this->removeFile($name);
					break;
				case "mc.log":
					$name = $this->mcLogFilePath($params['confkey']);
					$resp = $this->removeFile($name);
					break;
				case "mc_data":
					$dir = $this->mcDataPath();
					$resp = $this->removeDir($dir);
					break;
				default:
					$resp = array('status' => 'Error', 'message' => 'INCORRECT_FILENAME');
				}
			} else {
				$resp = array('status' => 'Error', 'message' => 'INCORRECT_FILENAME');
			}
			break;
		case "addprepndtohtacess":
			$user_ini = $params['user_ini'];
			$mode = intval($params['mode']);
			$resp = array('prependtohtaccess' => $this->addPrependToHtaccess($user_ini, $mode));
			break;
		case "rmprepndfrmhtacess":
			$resp = array('removefromhtaccess' => $this->removePrependFromHtaccess());
			break;
		case "addprepndtousrini":
			$mode = intval($params['mode']);
			$resp = array('prependtouserini' => $this->addPrependToUserini($mode));
			break;
		case "rmprepndfrmusrini":
			$resp = array('removefromuserini' => $this->removePrependFromUserini());
			break;
		case "setptplug":
			$this->settings->updateOption('bvptplug', $params['ptplug']);
			$resp = array("setptplug" => $this->settings->getOption('bvptplug'));
			break;
		case "unsetptplug":
			$this->settings->deleteOption('bvptlug');
			$resp = array("unsetptplug" => $this->settings->getOption('bvptlug'));
			break;
		case "unblklogins":
			$resp = array("unblocklogins" => $this->unBlockLogins());
			break;
		case "blklogins":
			$time = array_key_exists('time', $params) ? $params['time'] : 1800;
			$resp = array("blocklogins" => $this->blockLogins($time));
			break;
		case "unblkip":
			$resp = array("unblockip" => $this->unBlockIP($params['ip'], $params['attempts'], $params['time']));
			break;
		case "rmwatchtime":
			$this->settings->deleteOption('bvwatchtime');
			$resp = array("rmwatchtime" => !$bvinfo->getWatchTime());
			break;
		default:
			$resp = false;
		}

		return $resp;
	}
}
endif;
