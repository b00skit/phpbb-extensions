<?php
/**
 *
 * @package booskit/usercommandcenter
 * @license MIT
 *
 */

namespace booskit\usercommandcenter\acp;

class main_module_info
{
	public function module()
	{
		return array(
			'title'		=> 'ACP_BOOSKIT_UCC_TITLE',
			'modes'		=> array(
				'settings'	=> array(
					'title'			=> 'ACP_BOOSKIT_UCC_TITLE',
					'auth'			=> 'ext_booskit/usercommandcenter && acl_a_board',
					'cat'			=> array('ACP_BOOSKIT_UCC_TITLE'),
				),
			),
		);
	}
}
