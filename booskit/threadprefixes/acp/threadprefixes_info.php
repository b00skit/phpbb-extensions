<?php
/**
 *
 * @package booskit/threadprefixes
 * @license MIT
 *
 */

namespace booskit\threadprefixes\acp;

class threadprefixes_info
{
	public function module()
	{
		return array(
			'filename'	=> '\booskit\threadprefixes\acp\threadprefixes_module',
			'title'		=> 'ACP_BOOSKIT_THREADPREFIXES_TITLE',
			'modes'		=> array(
				'settings'	=> array('title' => 'ACP_BOOSKIT_THREADPREFIXES_SETTINGS', 'auth' => 'ext_booskit/threadprefixes && acl_a_board', 'cat' => array('ACP_BOOSKIT_THREADPREFIXES_TITLE')),
			),
		);
	}
}
