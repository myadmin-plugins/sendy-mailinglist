<?php

namespace Detain\MyAdminSendy;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Class Plugin
 *
 * @package Detain\MyAdminSendy
 */
class Plugin {

	public static $name = 'Sendy Plugin';
	public static $description = 'Allows handling of Sendy emails and honeypots';
	public static $help = '';
	public static $type = 'plugin';

	/**
	 * Plugin constructor.
	 */
	public function __construct() {
	}

	/**
	 * @return array
	 */
	public static function getHooks() {
		return [
			'system.settings' => [__CLASS__, 'getSettings'],
			'account.activated' => [__CLASS__, 'doAccountActivated'],
			'mailinglist.subscribe' => [__CLASS__, 'doMailinglistSubscribe'],
			//'ui.menu' => [__CLASS__, 'getMenu'],
		];
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function doAccountActivated(GenericEvent $event) {
		$account = $event->getSubject();
		if (defined('SENDY_ENABLE') && SENDY_ENABLE == 1) {
			self::doSetup($account->getAccountId());
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function doMailinglistSubscribe(GenericEvent $event) {
		$account = $event->getSubject();
		if (defined('SENDY_ENABLE') && SENDY_ENABLE == 1) {
			self::doEmailSetup($account->getAccountId());
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getSettings(GenericEvent $event) {
		$settings = $event->getSubject();
		$settings->add_dropdown_setting('Accounts', 'Sendy', 'sendy_enable', 'Enable Sendy', 'Enable/Disable Sendy Mailing on Account Signup', (defined('SENDY_ENABLE') ? SENDY_ENABLE : '0'), ['0', '1'], ['No', 'Yes']);
		$settings->add_text_setting('Accounts', 'Sendy', 'sendy_api_key', 'API Key', 'API Key', (defined('SENDY_API_KEY') ? SENDY_API_KEY : ''));
		$settings->add_text_setting('Accounts', 'Sendy', 'sendy_list_id', 'List ID', 'List ID', (defined('SENDY_LIST_ID') ? SENDY_LIST_ID : ''));
		$settings->add_text_setting('Accounts', 'Sendy', 'sendy_apiurl', 'API URL', 'API URL', (defined('SENDY_APIURL') ? SENDY_APIURL : ''));
	}

	/**
	 * @param int $custid
	 */
	public static function doSetup($custid) {
		$data = $GLOBALS['tf']->accounts->read($custid);
		self::doEmailSetup($lid, isset($data['name']) ? $data['name'] : false);
	}

	/**
	 * @param string $lid
	 */
	public static function doEmailSetup($lid, $name = false) {
		myadmin_log('accounts', 'info', "sendy_setup($lid) Called", __LINE__, __FILE__);
		$postarray = [
			'email' => $lid,
			'list' => SENDY_LIST_ID,
			'boolean' => 'true'
		];
		if ($name !== false)
			$postarray['name'] = $name;
		$postdata = http_build_query($postarray);
		$opts = [
			'http' => [
				'method' => 'POST',
				'header' => 'Content-type: application/x-www-form-urlencoded',
				'content' => $postdata
			]
		];
		$context = stream_context_create($opts);
		$result = trim(file_get_contents(SENDY_APIURL.'/subscribe', FALSE, $context));
		if ($result != '1')
			myadmin_log('accounts', 'info', "Sendy Response: {$result}", __LINE__, __FILE__);
	}
}
