<?php
/**
 *
 * @package booskit/usercareer
 * @license MIT
 *
 */

namespace booskit\usercareer\acp;

class main_module
{
	public $u_action;

	public function main($id, $mode)
	{
		global $user, $template, $request, $config, $phpbb_container;

		$user->add_lang_ext('booskit/usercareer', 'info_acp_career');

		$this->tpl_name = 'acp_career_settings';
		$this->page_title = 'ACP_BOOSKIT_CAREER_TITLE';

		$form_key = 'acp_career_settings';
		add_form_key($form_key);

		$action = $request->variable('action', '');
		$career_manager = $phpbb_container->get('booskit.usercareer.service.career_manager');

		global $db, $table_prefix;
		$config_text = new \phpbb\config\db_text($db, $table_prefix . 'config_text');

		if ($action == 'delete')
		{
			$def_id = $request->variable('def_id', 0);
			if (confirm_box(true))
			{
				if ($def_id)
				{
					$career_manager->delete_local_definition($def_id);
				}
				trigger_error($user->lang['CONFIG_UPDATED'] . adm_back_link($this->u_action));
			}
			else
			{
				confirm_box(false, $user->lang['CONFIRM_OPERATION'], build_hidden_fields(array(
					'def_id' => $def_id,
					'action' => 'delete',
				)));
			}
		}

		if ($action == 'delete_perm_group')
		{
			$perm_group_id = $request->variable('perm_group_id', 0);
			if (confirm_box(true))
			{
				if ($perm_group_id)
				{
					$career_manager->delete_permission_group($perm_group_id);
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

		if ($request->is_set_post('submit'))
		{
			if (!check_form_key($form_key))
			{
				trigger_error('FORM_INVALID');
			}

			if ($action == 'add')
			{
				$id = $request->variable('new_id', '');
				$name = $request->variable('new_name', '');
				$desc = $request->variable('new_desc', '');
				$icon = $request->variable('new_icon', '');

				if (!empty($id) && !empty($name))
				{
					$career_manager->add_local_definition($id, $name, $desc, $icon);
				}
				trigger_error($user->lang['CONFIG_UPDATED'] . adm_back_link($this->u_action));
			}

			if ($action == 'update_one')
			{
				$def_id = $request->variable('def_id', 0);

				$ids = $request->variable('id', array(0 => ''));
				$names = $request->variable('name', array(0 => ''));
				$descs = $request->variable('desc', array(0 => ''));
				$icons = $request->variable('icon', array(0 => ''));

				$enable_public_postings = $request->variable('enable_public_posting', array(0 => 0));
				$poster_ids = $request->variable('public_posting_poster_id', array(0 => 0));
				$forum_ids = $request->variable('public_posting_forum_id', array(0 => 0));
				$subject_tpls = $request->variable('public_posting_subject_tpl', array(0 => ''), true);
				$body_tpls = $request->variable('public_posting_body_tpl', array(0 => ''), true);
				$fields_jsons = $request->variable('public_posting_fields_json', array(0 => ''), true);

				$enable_group_actions = $request->variable('enable_group_action', array(0 => 0));
				$group_action_adds = $request->variable('group_action_add', array(0 => ''));
				$group_action_removes = $request->variable('group_action_remove', array(0 => ''));
				$automation_settings_jsons = $request->variable('automation_settings_json', array(0 => ''), true);

				$enable_note_templates = $request->variable('enable_note_template', array(0 => 0));
				$inherit_note_fields_all = $request->variable('inherit_note_fields', array(0 => 0));
				$note_template_body_tpls = $request->variable('note_template_body_tpl', array(0 => ''), true);
				$note_template_fields_jsons = $request->variable('note_template_fields_json', array(0 => ''), true);

				if ($def_id && isset($ids[$def_id]) && isset($names[$def_id]))
				{
					$career_manager->update_local_definition(
						$def_id,
						$ids[$def_id],
						$names[$def_id],
						$descs[$def_id],
						$icons[$def_id],
						isset($enable_public_postings[$def_id]) ? $enable_public_postings[$def_id] : 0,
						isset($poster_ids[$def_id]) ? $poster_ids[$def_id] : 0,
						isset($forum_ids[$def_id]) ? $forum_ids[$def_id] : 0,
						isset($subject_tpls[$def_id]) ? $subject_tpls[$def_id] : '',
						isset($body_tpls[$def_id]) ? $body_tpls[$def_id] : '',
						isset($fields_jsons[$def_id]) ? htmlspecialchars_decode($fields_jsons[$def_id]) : '',
						isset($enable_group_actions[$def_id]) ? $enable_group_actions[$def_id] : 0,
						isset($group_action_adds[$def_id]) ? $group_action_adds[$def_id] : '',
						isset($group_action_removes[$def_id]) ? $group_action_removes[$def_id] : '',
						isset($automation_settings_jsons[$def_id]) ? htmlspecialchars_decode($automation_settings_jsons[$def_id]) : '',
						isset($enable_note_templates[$def_id]) ? $enable_note_templates[$def_id] : 0,
						isset($inherit_note_fields_all[$def_id]) ? $inherit_note_fields_all[$def_id] : 0,
						isset($note_template_body_tpls[$def_id]) ? $note_template_body_tpls[$def_id] : '',
						isset($note_template_fields_jsons[$def_id]) ? htmlspecialchars_decode($note_template_fields_jsons[$def_id]) : ''
					);
				}
				trigger_error($user->lang['CONFIG_UPDATED'] . adm_back_link($this->u_action));
			}

			if ($action == 'add_perm_group')
			{
				$group_name = $request->variable('new_perm_group_name', '', true);
				$applies_to = $request->variable('new_applies_to', array(0));
				$power_over_all = $request->variable('new_power_over_all', 0);
				$power_over_self = $request->variable('new_power_over_self', 0);
				$power_over_groups = $request->variable('new_power_over_groups', array(0));
				$exclude_groups = $request->variable('new_exclude_groups', array(0));
				$edit_own = $request->variable('new_edit_own', 0);
				$delete_own = $request->variable('new_delete_own', 0);
				$view = $request->variable('new_view', 0);
				$submit = $request->variable('new_submit', 0);
				$edit = $request->variable('new_edit', 0);
				$delete = $request->variable('new_delete', 0);

				$perms_combined = [
					'view' => $view,
					'submit' => $submit,
					'edit_own' => $edit_own,
					'delete_own' => $delete_own,
					'edit' => $edit,
					'delete' => $delete,
				];

				if (!empty($group_name))
				{
					$career_manager->add_permission_group($group_name, $applies_to, $power_over_all, $power_over_self, $power_over_groups, $exclude_groups, $perms_combined);
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
				$edit_own_all = $request->variable('edit_own', array(0 => 0));
				$delete_own_all = $request->variable('delete_own', array(0 => 0));
				$view_all = $request->variable('view', array(0 => 0));
				$submit_perm_all = $request->variable('submit_perm', array(0 => 0));
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
					$edit_own = isset($edit_own_all[$perm_group_id]) ? $edit_own_all[$perm_group_id] : 0;
					$delete_own = isset($delete_own_all[$perm_group_id]) ? $delete_own_all[$perm_group_id] : 0;
					$view = isset($view_all[$perm_group_id]) ? $view_all[$perm_group_id] : 0;
					$submit = isset($submit_perm_all[$perm_group_id]) ? $submit_perm_all[$perm_group_id] : 0;
					$edit = isset($edit_all[$perm_group_id]) ? $edit_all[$perm_group_id] : 0;
					$delete = isset($delete_all[$perm_group_id]) ? $delete_all[$perm_group_id] : 0;

					$perms_combined = [
						'view' => $view,
						'submit' => $submit,
						'edit_own' => $edit_own,
						'delete_own' => $delete_own,
						'edit' => $edit,
						'delete' => $delete,
					];

					$career_manager->update_permission_group($perm_group_id, $group_name, $applies_to, $power_over_all, $power_over_self, $power_over_groups, $exclude_groups, $perms_combined);
				}
				trigger_error($user->lang['CONFIG_UPDATED'] . adm_back_link($this->u_action));
			}

			if ($action == '')
			{
				$config->set('booskit_career_perm_system', $request->variable('booskit_career_perm_system', 'legacy'));
				$config->set('booskit_career_source', $request->variable('booskit_career_source', 'url'));
				$config->set('booskit_career_json_url', $request->variable('booskit_career_json_url', ''));
				$config->set('booskit_career_access_view', $request->variable('booskit_career_access_view', ''));
				$config->set('booskit_career_access_view_global', $request->variable('booskit_career_access_view_global', ''));
				$config->set('booskit_career_access_l1', $request->variable('booskit_career_access_l1', ''));
				$config->set('booskit_career_access_l2', $request->variable('booskit_career_access_l2', ''));
				$config->set('booskit_career_access_l3', $request->variable('booskit_career_access_l3', ''));
				$config->set('booskit_career_access_full', $request->variable('booskit_career_access_full', ''));

				// Ruleset
				$ruleset_text = $request->variable('booskit_career_ruleset', '', true);
				$ruleset_uid = $request->variable('booskit_career_ruleset_uid', '');
				$ruleset_bitfield = $request->variable('booskit_career_ruleset_bitfield', '');
				$ruleset_options = $request->variable('booskit_career_ruleset_options', 7);

				generate_text_for_storage($ruleset_text, $ruleset_uid, $ruleset_bitfield, $ruleset_options, true, true, true);

				$config_text->set('booskit_career_ruleset', $ruleset_text);
				$config->set('booskit_career_ruleset_uid', $ruleset_uid);
				$config->set('booskit_career_ruleset_bitfield', $ruleset_bitfield);
				$config->set('booskit_career_ruleset_options', $ruleset_options);

				trigger_error($user->lang['CONFIG_UPDATED'] . adm_back_link($this->u_action));
			}
		}

		// Fetch local definitions
		$local_definitions = $career_manager->get_local_definitions();
		$phpbb_groups = $career_manager->get_phpbb_groups();
		$permission_groups = $career_manager->get_permission_groups();

		// Prepare Ruleset
		$ruleset_text = $config_text->get('booskit_career_ruleset');
		$ruleset_uid = isset($config['booskit_career_ruleset_uid']) ? $config['booskit_career_ruleset_uid'] : '';
		$ruleset_bitfield = isset($config['booskit_career_ruleset_bitfield']) ? $config['booskit_career_ruleset_bitfield'] : '';
		$ruleset_options = isset($config['booskit_career_ruleset_options']) ? (int) $config['booskit_career_ruleset_options'] : 7;

		$text_data = generate_text_for_edit($ruleset_text, $ruleset_uid, $ruleset_options);
		$ruleset_text = $text_data['text'];

		$template->assign_vars(array(
			'BOOSKIT_CAREER_PERM_SYSTEM' => $career_manager->get_perm_system(),
			'BOOSKIT_CAREER_RULESET' => $ruleset_text,
			'BOOSKIT_CAREER_RULESET_UID' => $ruleset_uid,
			'BOOSKIT_CAREER_RULESET_BITFIELD' => $ruleset_bitfield,
			'BOOSKIT_CAREER_RULESET_OPTIONS' => $ruleset_options,
			'BOOSKIT_CAREER_SOURCE'	=> isset($config['booskit_career_source']) ? $config['booskit_career_source'] : 'url',
			'BOOSKIT_CAREER_JSON_URL'	=> $config['booskit_career_json_url'],
			'BOOSKIT_CAREER_ACCESS_VIEW'	=> isset($config['booskit_career_access_view']) ? $config['booskit_career_access_view'] : '',
			'BOOSKIT_CAREER_ACCESS_VIEW_GLOBAL'	=> isset($config['booskit_career_access_view_global']) ? $config['booskit_career_access_view_global'] : '',
			'BOOSKIT_CAREER_ACCESS_L1'	=> $config['booskit_career_access_l1'],
			'BOOSKIT_CAREER_ACCESS_L2'	=> $config['booskit_career_access_l2'],
			'BOOSKIT_CAREER_ACCESS_L3'	=> $config['booskit_career_access_l3'],
			'BOOSKIT_CAREER_ACCESS_FULL'	=> $config['booskit_career_access_full'],
			'LOCAL_DEFINITIONS'			=> $local_definitions,
			'PERMISSION_GROUPS'			=> $permission_groups,
			'PHPBB_GROUPS'				=> $phpbb_groups,
			'U_ACTION'						=> $this->u_action,
		));
	}
}