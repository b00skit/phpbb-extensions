<?php
/**
 *
 * @package booskit/darkmode
 * @license MIT
 *
 */

namespace booskit\darkmode\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\request\request_interface */
	protected $request;

	/** @var \phpbb\config\config */
	protected $config;

	public function __construct(
		\phpbb\template\template $template,
		\phpbb\user $user,
		\phpbb\request\request_interface $request,
		\phpbb\config\config $config
	) {
		$this->template = $template;
		$this->user = $user;
		$this->request = $request;
		$this->config = $config;
	}

	public static function getSubscribedEvents()
	{
		return [
			'core.page_header' => 'on_page_header',
		];
	}

	public function on_page_header($event)
	{
		$this->user->add_lang_ext('booskit/darkmode', 'darkmode');

		$cookie_prefix = !empty($this->config['cookie_name']) ? $this->config['cookie_name'] : 'phpbb3';
		$candidates = [
			'booskit_darkmode',
			$cookie_prefix . '_booskit_darkmode',
			'phpbb3_booskit_darkmode',
		];

		$is_dark = false;
		foreach ($candidates as $name) {
			if ($this->request->variable($name, '', false, \phpbb\request\request_interface::COOKIE) === '1') {
				$is_dark = true;
				break;
			}
		}

		$this->template->assign_vars([
			'S_DARK_MODE_ENABLED'   => $is_dark,
			'DARK_MODE_COOKIE_NAME' => $cookie_prefix . '_booskit_darkmode',
			'L_DARK_MODE_ENABLE'    => $this->user->lang('DARK_MODE_ENABLE'),
			'L_DARK_MODE_DISABLE'   => $this->user->lang('DARK_MODE_DISABLE'),
			'L_DARK_MODE_TOGGLE'    => $this->user->lang('DARK_MODE_TOGGLE'),
		]);
	}
}
