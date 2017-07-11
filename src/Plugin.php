<?php

namespace Detain\MyAdminSendy;

use Symfony\Component\EventDispatcher\GenericEvent;

class Plugin {

	public static $name = 'Sendy Plugin';
	public static $description = 'Allows handling of Sendy emails and honeypots';
	public static $help = '';
	public static $type = 'plugin';


	public function __construct() {
	}

	public static function getHooks() {
		return [
			'system.settings' => [__CLASS__, 'getSettings'],
			//'ui.menu' => [__CLASS__, 'getMenu'],
		];
	}

	public static function getMenu(GenericEvent $event) {
		$menu = $event->getSubject();
		if ($GLOBALS['tf']->ima == 'admin') {
			function_requirements('has_acl');
					if (has_acl('client_billing'))
							$menu->add_link('admin', 'choice=none.abuse_admin', '//my.interserver.net/bower_components/webhostinghub-glyphs-icons/icons/development-16/Black/icon-spam.png', 'Sendy');
		}
	}

	public static function getRequirements(GenericEvent $event) {
		$loader = $event->getSubject();
		$loader->add_requirement('class.Sendy', '/../vendor/detain/myadmin-sendy-mailinglist/src/Sendy.php');
		$loader->add_requirement('deactivate_kcare', '/../vendor/detain/myadmin-sendy-mailinglist/src/abuse.inc.php');
		$loader->add_requirement('deactivate_abuse', '/../vendor/detain/myadmin-sendy-mailinglist/src/abuse.inc.php');
		$loader->add_requirement('get_abuse_licenses', '/../vendor/detain/myadmin-sendy-mailinglist/src/abuse.inc.php');
	}

	public static function getSettings(GenericEvent $event) {
		$settings = $event->getSubject();
		$settings->add_text_setting('Accounts', 'Sendy', 'sendy_api_key', 'API Key', 'API Key', (defined('SENDY_API_KEY') ? SENDY_API_KEY : ''));
		$settings->add_text_setting('Accounts', 'Sendy', 'sendy_list_id', 'List ID', 'List ID', (defined('SENDY_LIST_ID') ? SENDY_LIST_ID : ''));
		$settings->add_text_setting('Accounts', 'Sendy', 'sendy_apiurl', 'API URL', 'API URL', (defined('SENDY_APIURL') ? SENDY_APIURL : ''));
	}

}
