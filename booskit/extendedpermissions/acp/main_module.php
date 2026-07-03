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

class main_module
{
	public $page_title;
	public $tpl_name;
	public $u_action;

	/**
	 * Main ACP module
	 *
	 * @param int    $id   The module ID
	 * @param string $mode The module mode (settings)
	 * @throws \Exception
	 */
	public function main($id, $mode)
	{
		global $phpbb_container;

		/** @var \phpbb\config\config $config */
		$config = $phpbb_container->get('config');
		/** @var \phpbb\request\request $request */
		$request = $phpbb_container->get('request');
		/** @var \phpbb\template\template $template */
		$template = $phpbb_container->get('template');
		/** @var \phpbb\user $user */
		$user = $phpbb_container->get('user');
		/** @var \phpbb\log\log $log */
		$log = $phpbb_container->get('log');
		/** @var \phpbb\language\language $language */
		$language = $phpbb_container->get('language');
		/** @var \phpbb\db\driver\driver_interface $db */
		$db = $phpbb_container->get('dbal.conn');

		// Make our settings language strings available.
		$language->add_lang('acp_extendedpermissions', 'booskit/extendedpermissions');

		$this->tpl_name = 'acp_extendedpermissions_body';
		$this->page_title = $language->lang('ACP_EXTENDEDPERMISSIONS_TITLE');

		// Create a form key for preventing CSRF attacks.
		add_form_key('extendedpermissions_acp');

		// Get current saved group IDs
		$current_mod_logs = !empty($config['extendedpermissions_mod_logs_groups']) ? array_map('intval', explode(',', $config['extendedpermissions_mod_logs_groups'])) : [];
		$current_last_actions = !empty($config['extendedpermissions_last_actions_groups']) ? array_map('intval', explode(',', $config['extendedpermissions_last_actions_groups'])) : [];

		if ($request->is_set_post('submit'))
		{
			if (!check_form_key('extendedpermissions_acp'))
			{
				trigger_error($language->lang('FORM_INVALID') . adm_back_link($this->u_action), E_USER_WARNING);
			}

			$mod_logs_post = $request->variable('extendedpermissions_mod_logs_groups', [0]);
			$last_actions_post = $request->variable('extendedpermissions_last_actions_groups', [0]);

			// Filter out 0/empty values
			$mod_logs_post = array_filter($mod_logs_post);
			$last_actions_post = array_filter($last_actions_post);

			// Save to config
			$config->set('extendedpermissions_mod_logs_groups', implode(',', $mod_logs_post));
			$config->set('extendedpermissions_last_actions_groups', implode(',', $last_actions_post));

			$log->add('admin', $user->data['user_id'], $user->ip, 'LOG_EXTENDEDPERMISSIONS_CONFIG');

			trigger_error($language->lang('ACP_EXTENDEDPERMISSIONS_SAVED') . adm_back_link($this->u_action));
		}

		// Retrieve all user groups
		$sql = 'SELECT group_id, group_name, group_type FROM ' . GROUPS_TABLE . ' ORDER BY group_name ASC';
		$result = $db->sql_query($sql);

		while ($row = $db->sql_fetchrow($result))
		{
			$group_name = ($row['group_type'] == GROUP_SPECIAL) ? $user->lang['G_' . $row['group_name']] : $row['group_name'];

			$template->assign_block_vars('group_options', [
				'GROUP_ID'               => (int) $row['group_id'],
				'GROUP_NAME'             => $group_name,
				'S_MOD_LOGS_SELECTED'    => in_array((int) $row['group_id'], $current_mod_logs, true),
				'S_LAST_ACTIONS_SELECTED'=> in_array((int) $row['group_id'], $current_last_actions, true),
			]);
		}
		$db->sql_freeresult($result);

		$template->assign_vars([
			'U_ACTION' => $this->u_action,
		]);
	}
}
