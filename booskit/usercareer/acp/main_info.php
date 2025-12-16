<?php
/**
 *
 * @package booskit/usercareer
 * @license MIT
 *
 */

namespace booskit\usercareer\acp;

class main_info
{
	function module()
	{
		return array(
			'filename'	=> '\booskit\usercareer\acp\main_module',
			'title'		=> 'ACP_BOOSKIT_CAREER_TITLE',
			'modes'		=> array(
				'settings'	=> array('title' => 'ACP_BOOSKIT_CAREER_SETTINGS', 'auth' => 'ext_booskit/usercareer && acl_a_board', 'cat' => array('ACP_BOOSKIT_CAREER_TITLE')),
			),
		);
	}
}
