<?php
/**
 *
 * @package booskit/forms
 * @license MIT
 *
 */

namespace booskit\forms\acp;

class main_info
{
	public function module()
	{
		return array(
			'filename'	=> '\booskit\forms\acp\main_module',
			'title'		=> 'ACP_BOOSKIT_FORMS_TITLE',
			'modes'		=> array(
				'manage'	=> array('title' => 'ACP_BOOSKIT_FORMS_MANAGE', 'auth' => 'ext_booskit/forms && acl_a_board', 'cat' => array('ACP_BOOSKIT_FORMS_TITLE')),
			),
		);
	}
}
