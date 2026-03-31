<?php
/**
 *
 * Post As. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\postas\acp;

/**
 * Post As ACP module info.
 */
class main_info
{
	public function module()
	{
		return array(
			'filename'	=> '\\phpbb\\postas\\acp\\main_module',
			'title' => 'ACP_POSTAS_TITLE',
			'modes' => array(
				'settings' => array(
					'title' => 'ACP_POSTAS_SETTINGS',
					'auth' => 'acl_a_board',
					'cat' => array('ACP_CAT_DOT_MODS'),
				),
			),
		);
	}
}
