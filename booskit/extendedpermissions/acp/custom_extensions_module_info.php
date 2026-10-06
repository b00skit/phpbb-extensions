<?php
/**
 *
 * Extended Permissions. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace booskit\extendedpermissions\acp;

class custom_extensions_module_info
{
	public function module()
	{
		return array(
			'filename'	=> '\booskit\extendedpermissions\acp\custom_extensions_module',
			'title'		=> 'ACP_EXTENSIONS_MANAGER',
			'modes'		=> array(
				'custom_extensions'	=> array(
					'title'	=> 'ACP_CUSTOM_EXTENSIONS',
					'auth'	=> 'ext_booskit/extendedpermissions && acl_a_board',
					'cat'	=> array('ACP_EXTENSIONS_MANAGER'),
				),
			),
		);
	}
}
