<?php
/**
 *
 * @package booskit/icdisciplinary
 * @license MIT
 *
 */

namespace booskit\icdisciplinary\acp;

class icdisciplinary_module_info
{
	function module()
	{
		return array(
			'filename'	=> '\booskit\icdisciplinary\acp\icdisciplinary_module',
			'title'		=> 'ACP_BOOSKIT_ICDISCIPLINARY_TITLE',
			'modes'		=> array(
				'settings'	=> array(
					'title'	=> 'ACP_ICDISCIPLINARY_SETTINGS',
					'auth'	=> 'ext_booskit/icdisciplinary && acl_a_board',
					'cat'	=> array('ACP_BOOSKIT_ICDISCIPLINARY_TITLE'),
				),
			),
		);
	}
}
