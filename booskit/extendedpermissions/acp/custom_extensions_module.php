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

class custom_extensions_module
{
	/** @var string */
	public $u_action;

	/** @var string */
	public $tpl_name;

	/** @var string */
	public $page_title;

	/**
	 * Main module controller.
	 *
	 * @param int $id
	 * @param string $mode
	 * @return void
	 */
	public function main($id, $mode)
	{
		global $user, $template, $request, $config, $phpbb_container;

		$user->add_lang('posting');
		$user->add_lang_ext('booskit/extendedpermissions', 'info_acp_custom_extensions');
		$user->add_lang_ext('booskit/extendedpermissions', 'permissions_extendedpermissions');

		$this->tpl_name = 'acp_custom_extensions';
		$this->page_title = 'ACP_CUSTOM_EXTENSIONS_TITLE';

		$form_key = 'acp_custom_extensions';
		add_form_key($form_key);

		$action = $request->variable('action', '');

		/** @var \booskit\extendedpermissions\service\permission_manager $permission_manager */
		$permission_manager = $phpbb_container->get('booskit.extendedpermissions.manager');

		// Verify that current user is authorized to manage custom extensions
		if (!$permission_manager->can_user_access_module($user->data['user_id']))
		{
			send_status_line(403, 'Forbidden');
			trigger_error('NOT_AUTHORISED');
		}

		// Handle deleting a permission group
		if ($action === 'delete_perm_group')
		{
			$perm_group_id = $request->variable('perm_group_id', 0);
			if (confirm_box(true))
			{
				if ($perm_group_id)
				{
					$permission_manager->delete_permission_group($perm_group_id);
				}
				trigger_error($user->lang['CONFIG_UPDATED'] . adm_back_link($this->u_action));
			}
			else
			{
				confirm_box(false, $user->lang['CONFIRM_OPERATION'], build_hidden_fields([
					'perm_group_id' => $perm_group_id,
					'action'        => 'delete_perm_group',
				]));
			}
		}

		// Handle form submissions
		if ($request->is_set_post('submit'))
		{
			if (!check_form_key($form_key))
			{
				trigger_error('FORM_INVALID');
			}

			// Add a new permission group
			if ($action === 'add_perm_group')
			{
				$group_name        = $request->variable('new_perm_group_name', '', true);
				$applies_to        = $request->variable('new_applies_to', [0]);
				$can_manage_module = $request->variable('new_can_manage_module', 0);
				$perms_raw         = $request->variable('new_perms', ['' => 0]);
				$allowed_exts      = array_keys(array_filter($perms_raw));

				if (!empty($group_name))
				{
					$permission_manager->add_permission_group($group_name, $applies_to, $can_manage_module, $allowed_exts);
				}
				trigger_error($user->lang['CONFIG_UPDATED'] . adm_back_link($this->u_action));
			}

			// Update an existing permission group
			if ($action === 'update_perm_group')
			{
				$perm_group_id         = $request->variable('perm_group_id', 0);
				$group_names           = $request->variable('perm_group_name', [0 => ''], true);
				$applies_to_all        = $request->variable('applies_to', [0 => [0]]);
				$can_manage_module_all = $request->variable('can_manage_module', [0 => 0]);
				$perms_all             = $request->variable('perms', [0 => ['' => 0]]);

				if ($perm_group_id && isset($group_names[$perm_group_id]))
				{
					$group_name        = $group_names[$perm_group_id];
					$applies_to        = isset($applies_to_all[$perm_group_id]) ? $applies_to_all[$perm_group_id] : [];
					$can_manage_module = isset($can_manage_module_all[$perm_group_id]) ? $can_manage_module_all[$perm_group_id] : 0;
					$perms             = isset($perms_all[$perm_group_id]) ? $perms_all[$perm_group_id] : [];
					$allowed_exts      = array_keys(array_filter($perms));

					$permission_manager->update_permission_group($perm_group_id, $group_name, $applies_to, $can_manage_module, $allowed_exts);
				}
				trigger_error($user->lang['CONFIG_UPDATED'] . adm_back_link($this->u_action));
			}

			// Global settings update
			if ($action === '')
			{
				$perm_system          = $request->variable('booskit_extperm_perm_system', 'groups');
				$module_access_groups = $request->variable('module_access_groups', [0]);
				$module_access_csv    = implode(',', array_map('intval', array_filter($module_access_groups)));

				$config->set('booskit_extperm_perm_system', $perm_system);
				$config->set('booskit_extperm_module_access', $module_access_csv);

				trigger_error($user->lang['CONFIG_UPDATED'] . adm_back_link($this->u_action));
			}
		}

		// Retrieve data for template
		$permission_groups = $permission_manager->get_permission_groups();
		$phpbb_groups      = $permission_manager->get_phpbb_groups();
		$extensions        = $permission_manager->get_available_extensions();
		$module_access     = $permission_manager->get_config_groups('booskit_extperm_module_access');

		$template->assign_vars([
			'BOOSKIT_EXTPERM_PERM_SYSTEM' => isset($config['booskit_extperm_perm_system']) ? $config['booskit_extperm_perm_system'] : 'groups',
			'MODULE_ACCESS_GROUPS'        => $module_access,
			'PERMISSION_GROUPS'           => $permission_groups,
			'PHPBB_GROUPS'                => $phpbb_groups,
			'EXTENSIONS'                  => $extensions,
			'U_ACTION'                    => $this->u_action,
		]);
	}
}
