<?php
/**
 *
 * @package booskit/commendations
 * @license MIT
 *
 */

namespace booskit\commendations\acp;

class main_module
{
	public $u_action;

	public function main($id, $mode)
	{
		global $user, $template, $request, $config, $phpbb_container;

		$user->add_lang_ext('booskit/commendations', 'info_acp_commendations');

		$this->tpl_name = 'acp_settings';
		$this->page_title = 'ACP_BOOSKIT_COMMENDATIONS_TITLE';

		$form_key = 'acp_commendations_settings';
		add_form_key($form_key);

		$action = $request->variable('action', '');
		$commendations_manager = $phpbb_container->get('booskit.commendations.service.commendations_manager');

		if ($action == 'delete_perm_group')
		{
			$perm_group_id = $request->variable('perm_group_id', 0);
			if (confirm_box(true))
			{
				if ($perm_group_id)
				{
					$commendations_manager->delete_permission_group($perm_group_id);
				}
				trigger_error($user->lang['CONFIG_UPDATED'] . adm_back_link($this->u_action));
			}
			else
			{
				confirm_box(false, $user->lang['CONFIRM_OPERATION'], build_hidden_fields(array(
					'perm_group_id' => $perm_group_id,
					'action' => 'delete_perm_group',
				)));
			}
		}

		if ($request->is_set_post('submit') || $request->is_set_post('submit_config'))
		{
			if (!check_form_key($form_key))
			{
				trigger_error('FORM_INVALID');
			}

			if ($action == 'add_perm_group')
			{
				$group_name = $request->variable('new_perm_group_name', '', true);
				$applies_to = $request->variable('new_applies_to', array(0));
				$power_over_all = $request->variable('new_power_over_all', 0);
				$power_over_self = $request->variable('new_power_over_self', 0);
				$power_over_groups = $request->variable('new_power_over_groups', array(0));
				$exclude_groups = $request->variable('new_exclude_groups', array(0));
				$view = $request->variable('new_view', 0);
				$submit = $request->variable('new_submit', 0);
				$copy = $request->variable('new_copy', 0);
				$edit_own = $request->variable('new_edit_own', 0);
				$delete_own = $request->variable('new_delete_own', 0);
				$edit = $request->variable('new_edit', 0);
				$delete = $request->variable('new_delete', 0);

				$permissions = [
					'view' => $view,
					'submit' => $submit,
					'copy' => $copy,
					'edit_own' => $edit_own,
					'delete_own' => $delete_own,
					'edit' => $edit,
					'delete' => $delete,
				];

				if (!empty($group_name))
				{
					$commendations_manager->add_permission_group($group_name, $applies_to, $power_over_all, $power_over_self, $power_over_groups, $exclude_groups, $permissions);
				}
				trigger_error($user->lang['CONFIG_UPDATED'] . adm_back_link($this->u_action));
			}

			if ($action == 'update_perm_group')
			{
				$perm_group_id = $request->variable('perm_group_id', 0);

				$group_names = $request->variable('perm_group_name', array(0 => ''), true);
				$applies_to_all = $request->variable('applies_to', array(0 => array(0)));
				$power_over_all_all = $request->variable('power_over_all', array(0 => 0));
				$power_over_self_all = $request->variable('power_over_self', array(0 => 0));
				$power_over_groups_all = $request->variable('power_over_groups', array(0 => array(0)));
				$exclude_groups_all = $request->variable('exclude_groups', array(0 => array(0)));
				$view_all = $request->variable('view', array(0 => 0));
				$submit_perm_all = $request->variable('submit_perm', array(0 => 0));
				$copy_perm_all = $request->variable('copy_perm', array(0 => 0));
				$edit_own_all = $request->variable('edit_own', array(0 => 0));
				$delete_own_all = $request->variable('delete_own', array(0 => 0));
				$edit_all = $request->variable('edit', array(0 => 0));
				$delete_all = $request->variable('delete', array(0 => 0));

				if ($perm_group_id && isset($group_names[$perm_group_id]))
				{
					$group_name = $group_names[$perm_group_id];
					$applies_to = isset($applies_to_all[$perm_group_id]) ? $applies_to_all[$perm_group_id] : array();
					$power_over_all = isset($power_over_all_all[$perm_group_id]) ? $power_over_all_all[$perm_group_id] : 0;
					$power_over_self = isset($power_over_self_all[$perm_group_id]) ? $power_over_self_all[$perm_group_id] : 0;
					$power_over_groups = isset($power_over_groups_all[$perm_group_id]) ? $power_over_groups_all[$perm_group_id] : array();
					$exclude_groups = isset($exclude_groups_all[$perm_group_id]) ? $exclude_groups_all[$perm_group_id] : array();
					$view = isset($view_all[$perm_group_id]) ? $view_all[$perm_group_id] : 0;
					$submit = isset($submit_perm_all[$perm_group_id]) ? $submit_perm_all[$perm_group_id] : 0;
					$copy = isset($copy_perm_all[$perm_group_id]) ? $copy_perm_all[$perm_group_id] : 0;
					$edit_own = isset($edit_own_all[$perm_group_id]) ? $edit_own_all[$perm_group_id] : 0;
					$delete_own = isset($delete_own_all[$perm_group_id]) ? $delete_own_all[$perm_group_id] : 0;
					$edit = isset($edit_all[$perm_group_id]) ? $edit_all[$perm_group_id] : 0;
					$delete = isset($delete_all[$perm_group_id]) ? $delete_all[$perm_group_id] : 0;

					$permissions = [
						'view' => $view,
						'submit' => $submit,
						'copy' => $copy,
						'edit_own' => $edit_own,
						'delete_own' => $delete_own,
						'edit' => $edit,
						'delete' => $delete,
					];

					$commendations_manager->update_permission_group($perm_group_id, $group_name, $applies_to, $power_over_all, $power_over_self, $power_over_groups, $exclude_groups, $permissions);
				}
				trigger_error($user->lang['CONFIG_UPDATED'] . adm_back_link($this->u_action));
			}

			if ($action == '')
			{
				$config->set('booskit_commendations_perm_system', $request->variable('booskit_commendations_perm_system', 'legacy'));
				$config->set('booskit_commendations_access_view', $request->variable('booskit_commendations_access_view', ''));
				$config->set('booskit_commendations_access_view_global', $request->variable('booskit_commendations_access_view_global', ''));
				$config->set('booskit_commendations_access_l1', $request->variable('booskit_commendations_access_l1', ''));
				$config->set('booskit_commendations_access_l2', $request->variable('booskit_commendations_access_l2', ''));
				$config->set('booskit_commendations_access_l3', $request->variable('booskit_commendations_access_l3', ''));
				$config->set('booskit_commendations_access_full', $request->variable('booskit_commendations_access_full', ''));

				$config->set('booskit_commendations_clipboard_tpl', $request->variable('booskit_commendations_clipboard_tpl', '', true));

				$config->set('booskit_commendations_enable_public_posting', $request->variable('booskit_commendations_enable_public_posting', 0));
				$config->set('booskit_commendations_public_posting_mode', $request->variable('booskit_commendations_public_posting_mode', 'forum'));
				$config->set('booskit_commendations_public_posting_forum_id', $request->variable('booskit_commendations_public_posting_forum_id', 0));
				$config->set('booskit_commendations_public_posting_post_id', $request->variable('booskit_commendations_public_posting_post_id', 0));
				$config->set('booskit_commendations_public_posting_poster_id', $request->variable('booskit_commendations_public_posting_poster_id', 0));
				$config->set('booskit_commendations_public_posting_subject_tpl', $request->variable('booskit_commendations_public_posting_subject_tpl', '', true));
				$config->set('booskit_commendations_public_posting_body_tpl', $request->variable('booskit_commendations_public_posting_body_tpl', '', true));

				trigger_error($user->lang['CONFIG_UPDATED'] . adm_back_link($this->u_action));
			}
		}

		$phpbb_groups = $commendations_manager->get_phpbb_groups();
		$permission_groups = $commendations_manager->get_permission_groups();

		$template->assign_vars(array(
			'BOOSKIT_COMMENDATIONS_PERM_SYSTEM'		=> isset($config['booskit_commendations_perm_system']) ? $config['booskit_commendations_perm_system'] : 'legacy',
			'BOOSKIT_COMMENDATIONS_ACCESS_VIEW'			=> isset($config['booskit_commendations_access_view']) ? $config['booskit_commendations_access_view'] : '',
			'BOOSKIT_COMMENDATIONS_ACCESS_VIEW_GLOBAL'	=> isset($config['booskit_commendations_access_view_global']) ? $config['booskit_commendations_access_view_global'] : '',
			'BOOSKIT_COMMENDATIONS_ACCESS_L1'			=> isset($config['booskit_commendations_access_l1']) ? $config['booskit_commendations_access_l1'] : '',
			'BOOSKIT_COMMENDATIONS_ACCESS_L2'			=> isset($config['booskit_commendations_access_l2']) ? $config['booskit_commendations_access_l2'] : '',
			'BOOSKIT_COMMENDATIONS_ACCESS_L3'			=> isset($config['booskit_commendations_access_l3']) ? $config['booskit_commendations_access_l3'] : '',
			'BOOSKIT_COMMENDATIONS_ACCESS_FULL'			=> isset($config['booskit_commendations_access_full']) ? $config['booskit_commendations_access_full'] : '',

			'BOOSKIT_COMMENDATIONS_CLIPBOARD_TPL'		=> isset($config['booskit_commendations_clipboard_tpl']) ? $config['booskit_commendations_clipboard_tpl'] : '',

			'BOOSKIT_COMMENDATIONS_ENABLE_PUBLIC_POSTING'	=> isset($config['booskit_commendations_enable_public_posting']) ? $config['booskit_commendations_enable_public_posting'] : 0,
			'BOOSKIT_COMMENDATIONS_PUBLIC_POSTING_MODE'	=> isset($config['booskit_commendations_public_posting_mode']) ? $config['booskit_commendations_public_posting_mode'] : 'forum',
			'BOOSKIT_COMMENDATIONS_PUBLIC_POSTING_FORUM_ID'	=> isset($config['booskit_commendations_public_posting_forum_id']) ? $config['booskit_commendations_public_posting_forum_id'] : 0,
			'BOOSKIT_COMMENDATIONS_PUBLIC_POSTING_POST_ID'	=> isset($config['booskit_commendations_public_posting_post_id']) ? $config['booskit_commendations_public_posting_post_id'] : 0,
			'BOOSKIT_COMMENDATIONS_PUBLIC_POSTING_POSTER_ID'=> isset($config['booskit_commendations_public_posting_poster_id']) ? $config['booskit_commendations_public_posting_poster_id'] : 0,
			'BOOSKIT_COMMENDATIONS_PUBLIC_POSTING_SUBJECT_TPL' => isset($config['booskit_commendations_public_posting_subject_tpl']) ? $config['booskit_commendations_public_posting_subject_tpl'] : '',
			'BOOSKIT_COMMENDATIONS_PUBLIC_POSTING_BODY_TPL'	=> isset($config['booskit_commendations_public_posting_body_tpl']) ? $config['booskit_commendations_public_posting_body_tpl'] : '',

			'PHPBB_GROUPS'								=> $phpbb_groups,
			'PERMISSION_GROUPS'							=> $permission_groups,
			'U_ACTION'									=> $this->u_action,
		));
	}
}
