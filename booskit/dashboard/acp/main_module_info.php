<?php
/**
 *
 * @package booskit/dashboard
 * @license MIT
 *
 */

namespace booskit\dashboard\acp;

class main_module_info
{
	public function module()
	{
		return array(
			'filename'	=> '\booskit\dashboard\acp\main_module',
			'title'		=> 'ACP_BOOSKIT_DASHBOARD_TITLE',
			'modes'		=> array(
				'settings'	=> array(
					'title'	=> 'ACP_BOOSKIT_DASHBOARD_SETTINGS',
					'auth'	=> 'ext_booskit/dashboard && acl_a_board',
					'cat'	=> array('ACP_BOOSKIT_DASHBOARD_TITLE'),
				),
			),
		);
	}
}
