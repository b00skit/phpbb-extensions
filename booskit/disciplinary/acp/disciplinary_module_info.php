<?php
/**
 *
 * @package booskit/disciplinary
 * @license MIT
 *
 */

namespace booskit\disciplinary\acp;

class disciplinary_module_info
{
	function module()
	{
		return array(
			'filename'	=> '\booskit\disciplinary\acp\disciplinary_module',
			'title'		=> 'ACP_BOOSKIT_DISCIPLINARY_TITLE',
			'modes'		=> array(
				'settings'	=> array(
					'title'	=> 'ACP_BOOSKIT_DISCIPLINARY_SETTINGS',
					'auth'	=> 'ext_booskit/disciplinary && acl_a_board',
					'cat'	=> array('ACP_BOOSKIT_DISCIPLINARY_TITLE'),
				),
			),
		);
	}
}
