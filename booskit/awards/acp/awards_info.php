<?php
/**
 *
 * @package booskit/awards
 * @license MIT
 *
 */

namespace booskit\awards\acp;

class awards_info
{
	public function module()
	{
		return array(
			'filename'	=> '\booskit\awards\acp\awards_module',
			'title'		=> 'ACP_BOOSKIT_AWARDS_TITLE',
			'modes'		=> array(
				'settings'	=> array('title' => 'ACP_BOOSKIT_AWARDS_SETTINGS', 'auth' => 'ext_booskit/awards && acl_a_board', 'cat' => array('ACP_BOOSKIT_AWARDS_TITLE')),
			),
		);
	}
}
