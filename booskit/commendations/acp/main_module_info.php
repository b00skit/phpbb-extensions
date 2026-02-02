<?php
/**
 *
 * @package booskit/commendations
 * @license MIT
 *
 */

namespace booskit\commendations\acp;

class main_module_info
{
	function module()
	{
		return array(
			'filename'	=> '\booskit\commendations\acp\main_module',
			'title'		=> 'ACP_BOOSKIT_COMMENDATIONS_TITLE',
			'modes'		=> array(
				'settings'	=> array('title' => 'ACP_BOOSKIT_COMMENDATIONS_SETTINGS', 'auth' => 'ext_booskit/commendations && acl_a_board', 'cat' => array('ACP_BOOSKIT_COMMENDATIONS_TITLE')),
			),
		);
	}
}
