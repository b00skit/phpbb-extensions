<?php
/**
 *
 * @package booskit/awards
 * @license MIT
 *
 */

namespace booskit\awards\controller\acp;

class settings
{
	protected $config;
	protected $config_text;
	protected $request;
	protected $template;
	protected $user;
	protected $log;
	protected $award_manager;

	public function __construct(\phpbb\config\config $config, \phpbb\db\driver\driver_interface $db, \phpbb\request\request_interface $request, \phpbb\template\template $template, \phpbb\user $user, \phpbb\log\log $log, \booskit\awards\service\award_manager $award_manager, $table_prefix)
	{
		$this->config = $config;
		$this->config_text = new \phpbb\config\db_text($db, $table_prefix . 'config_text');
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->log = $log;
		$this->award_manager = $award_manager;
	}

	public function handle($u_action)
	{
		$form_key = 'acp_booskit_awards';
		add_form_key($form_key);
		$this->user->add_lang_ext('booskit/awards', 'info_acp_awards');

		$action = $this->request->variable('action', '');

		// Handle Delete
		if ($action == 'delete')
		{
			$def_id = $this->request->variable('def_id', 0);

			if (confirm_box(true))
			{
				if ($def_id)
				{
					$this->award_manager->delete_local_definition($def_id);
					$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_BOOSKIT_AWARDS_DEF_DELETED', false, [$def_id]);
				}
				trigger_error($this->user->lang['CONFIG_UPDATED'] . adm_back_link($u_action));
			}
			else
			{
				confirm_box(false, $this->user->lang['CONFIRM_OPERATION'], build_hidden_fields(array(
					'def_id' => $def_id,
					'action' => 'delete',
				)));
			}
		}

		// Handle Add/Update (POST)
		if ($this->request->is_set_post('submit') || $this->request->is_set_post('submit_config'))
		{
			if (!check_form_key($form_key))
			{
				trigger_error($this->user->lang['FORM_INVALID'] . adm_back_link($u_action), E_USER_WARNING);
			}

			if ($action == 'add_perm_group')
			{
				$group_name = $this->request->variable('new_perm_group_name', '', true);
				$applies_to = $this->request->variable('new_applies_to', [0]);
				$power_over_all = $this->request->variable('new_power_over_all', 0);
				$power_over_self = $this->request->variable('new_power_over_self', 0);
				$power_over_groups = $this->request->variable('new_power_over_groups', [0]);
				$exclude_groups = $this->request->variable('new_exclude_groups', [0]);
				$view = $this->request->variable('new_view', 0);
				$submit_perm = $this->request->variable('new_submit', 0);

				$permissions = [
					'view' => (int) $view,
					'submit' => (int) $submit_perm,
				];

				if (!empty($group_name))
				{
					$this->award_manager->add_permission_group($group_name, $applies_to, $power_over_all, $power_over_self, $power_over_groups, $exclude_groups, $permissions);
					$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_BOOSKIT_AWARDS_SETTINGS_UPDATED');
				}
				trigger_error($this->user->lang['CONFIG_UPDATED'] . adm_back_link($u_action));
			}

			if ($action == 'update_perm_group')
			{
				$perm_group_id = $this->request->variable('perm_group_id', 0);

				$names = $this->request->variable('perm_group_name', [0 => ''], true);
				$applies_to_raw = $this->request->variable('applies_to', [0 => [0]]);
				$power_over_all_raw = $this->request->variable('power_over_all', [0 => 0]);
				$power_over_self_raw = $this->request->variable('power_over_self', [0 => 0]);
				$power_over_groups_raw = $this->request->variable('power_over_groups', [0 => [0]]);
				$exclude_groups_raw = $this->request->variable('exclude_groups', [0 => [0]]);
				$views_raw = $this->request->variable('view', [0 => 0]);
				$submits_raw = $this->request->variable('submit_perm', [0 => 0]);

				if ($perm_group_id && isset($names[$perm_group_id]))
				{
					$group_name = $names[$perm_group_id];
					$applies_to = isset($applies_to_raw[$perm_group_id]) ? $applies_to_raw[$perm_group_id] : [];
					$power_over_all = isset($power_over_all_raw[$perm_group_id]) ? (int) $power_over_all_raw[$perm_group_id] : 0;
					$power_over_self = isset($power_over_self_raw[$perm_group_id]) ? (int) $power_over_self_raw[$perm_group_id] : 0;
					$power_over_groups = isset($power_over_groups_raw[$perm_group_id]) ? $power_over_groups_raw[$perm_group_id] : [];
					$exclude_groups = isset($exclude_groups_raw[$perm_group_id]) ? $exclude_groups_raw[$perm_group_id] : [];
					$view = isset($views_raw[$perm_group_id]) ? 1 : 0;
					$submit_perm = isset($submits_raw[$perm_group_id]) ? 1 : 0;

					$permissions = [
						'view' => $view,
						'submit' => $submit_perm,
					];

					$this->award_manager->update_permission_group($perm_group_id, $group_name, $applies_to, $power_over_all, $power_over_self, $power_over_groups, $exclude_groups, $permissions);
					$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_BOOSKIT_AWARDS_SETTINGS_UPDATED');
				}
				trigger_error($this->user->lang['CONFIG_UPDATED'] . adm_back_link($u_action));
			}

			if ($action == 'delete_perm_group')
			{
				$perm_group_id = $this->request->variable('perm_group_id', 0);
				if ($perm_group_id)
				{
					$this->award_manager->delete_permission_group($perm_group_id);
					$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_BOOSKIT_AWARDS_SETTINGS_UPDATED');
				}
				trigger_error($this->user->lang['CONFIG_UPDATED'] . adm_back_link($u_action));
			}

			if ($action == 'add')
			{
				$id = $this->request->variable('new_id', '');
				$name = $this->request->variable('new_name', '');
				$desc = $this->request->variable('new_desc', '');
				$img = $this->request->variable('new_img', '');
				$w = $this->request->variable('new_w', '150px');
				$h = $this->request->variable('new_h', '150px');

				if (!empty($id) && !empty($name))
				{
					$this->award_manager->add_local_definition($id, $name, $desc, $img, $w, $h);
					$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_BOOSKIT_AWARDS_DEF_ADDED', false, [$name]);
				}
				trigger_error($this->user->lang['CONFIG_UPDATED'] . adm_back_link($u_action));
			}

			if ($action == 'update_one')
			{
				$def_id = $this->request->variable('def_id', 0);

				// Fetch arrays
				$ids = $this->request->variable('id', array(0 => ''));
				$names = $this->request->variable('name', array(0 => ''));
				$descs = $this->request->variable('desc', array(0 => ''));
				$imgs = $this->request->variable('img', array(0 => ''));
				$ws = $this->request->variable('w', array(0 => ''));
				$hs = $this->request->variable('h', array(0 => ''));

				$enable_public_postings = $this->request->variable('enable_public_posting', array(0 => 0));
				$poster_ids = $this->request->variable('public_posting_poster_id', array(0 => 0));
				$forum_ids = $this->request->variable('public_posting_forum_id', array(0 => 0));
				$subject_tpls = $this->request->variable('public_posting_subject_tpl', array(0 => ''), true);
				$body_tpls = $this->request->variable('public_posting_body_tpl', array(0 => ''), true);
				$fields_jsons = $this->request->variable('public_posting_fields_json', array(0 => ''), true);

				if ($def_id && isset($ids[$def_id]) && isset($names[$def_id]))
				{
					$this->award_manager->update_local_definition(
						$def_id,
						$ids[$def_id],
						$names[$def_id],
						$descs[$def_id],
						$imgs[$def_id],
						$ws[$def_id],
						$hs[$def_id],
						isset($enable_public_postings[$def_id]) ? $enable_public_postings[$def_id] : 0,
						isset($poster_ids[$def_id]) ? $poster_ids[$def_id] : 0,
						isset($forum_ids[$def_id]) ? $forum_ids[$def_id] : 0,
						isset($subject_tpls[$def_id]) ? $subject_tpls[$def_id] : '',
						isset($body_tpls[$def_id]) ? $body_tpls[$def_id] : '',
						isset($fields_jsons[$def_id]) ? htmlspecialchars_decode($fields_jsons[$def_id]) : ''
					);
					$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_BOOSKIT_AWARDS_DEF_UPDATED', false, [$names[$def_id]]);
				}
				trigger_error($this->user->lang['CONFIG_UPDATED'] . adm_back_link($u_action));
			}

			// Main Config Submit
			if ($action == '')
			{
				$perm_system = $this->request->variable('booskit_awards_perm_system', 'legacy');
				$source = $this->request->variable('booskit_awards_source', 'url');
				$json_url = $this->request->variable('booskit_awards_json_url', '');
				$access_l1 = $this->request->variable('booskit_awards_access_l1', '');
				$access_l2 = $this->request->variable('booskit_awards_access_l2', '');
				$access_full = $this->request->variable('booskit_awards_access_full', '');

				// Ruleset
				$ruleset_text = $this->request->variable('booskit_awards_ruleset', '', true);
				$ruleset_uid = $this->request->variable('booskit_awards_ruleset_uid', '');
				$ruleset_bitfield = $this->request->variable('booskit_awards_ruleset_bitfield', '');
				$ruleset_options = $this->request->variable('booskit_awards_ruleset_options', 7);

				generate_text_for_storage($ruleset_text, $ruleset_uid, $ruleset_bitfield, $ruleset_options, true, true, true);

				$this->config->set('booskit_awards_perm_system', $perm_system);
				$this->config->set('booskit_awards_source', $source);
				$this->config->set('booskit_awards_json_url', $json_url);
				$this->config->set('booskit_awards_access_l1', $access_l1);
				$this->config->set('booskit_awards_access_l2', $access_l2);
				$this->config->set('booskit_awards_access_full', $access_full);

				$this->config_text->set('booskit_awards_ruleset', $ruleset_text);
				$this->config->set('booskit_awards_ruleset_uid', $ruleset_uid);
				$this->config->set('booskit_awards_ruleset_bitfield', $ruleset_bitfield);
				$this->config->set('booskit_awards_ruleset_options', $ruleset_options);

				$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_BOOSKIT_AWARDS_SETTINGS_UPDATED');
				trigger_error($this->user->lang['CONFIG_UPDATED'] . adm_back_link($u_action));
			}
		}

		// Fetch local definitions
		$local_definitions = $this->award_manager->get_local_definitions();

		// Prepare Ruleset
		$ruleset_text = $this->config_text->get('booskit_awards_ruleset');
		$ruleset_uid = isset($this->config['booskit_awards_ruleset_uid']) ? $this->config['booskit_awards_ruleset_uid'] : '';
		$ruleset_bitfield = isset($this->config['booskit_awards_ruleset_bitfield']) ? $this->config['booskit_awards_ruleset_bitfield'] : '';
		$ruleset_options = isset($this->config['booskit_awards_ruleset_options']) ? (int) $this->config['booskit_awards_ruleset_options'] : 7;

		// FIX: Use correct arguments and capture return
		$text_data = generate_text_for_edit($ruleset_text, $ruleset_uid, $ruleset_options);
		$ruleset_text = $text_data['text'];

		$this->template->assign_vars(array(
			'U_ACTION' => $u_action,
			'BOOSKIT_AWARDS_PERM_SYSTEM' => $this->award_manager->get_perm_system(),
			'PERMISSION_GROUPS' => $this->award_manager->get_permission_groups(),
			'PHPBB_GROUPS' => $this->award_manager->get_phpbb_groups(),
			'BOOSKIT_AWARDS_RULESET' => $ruleset_text,
			'BOOSKIT_AWARDS_RULESET_UID' => $ruleset_uid,
			'BOOSKIT_AWARDS_RULESET_BITFIELD' => $ruleset_bitfield,
			'BOOSKIT_AWARDS_RULESET_OPTIONS' => $ruleset_options,
			'BOOSKIT_AWARDS_SOURCE' => isset($this->config['booskit_awards_source']) ? $this->config['booskit_awards_source'] : 'url',
			'BOOSKIT_AWARDS_JSON_URL' => $this->config['booskit_awards_json_url'],
			'BOOSKIT_AWARDS_ACCESS_L1' => isset($this->config['booskit_awards_access_l1']) ? $this->config['booskit_awards_access_l1'] : '',
			'BOOSKIT_AWARDS_ACCESS_L2' => isset($this->config['booskit_awards_access_l2']) ? $this->config['booskit_awards_access_l2'] : '',
			'BOOSKIT_AWARDS_ACCESS_FULL' => isset($this->config['booskit_awards_access_full']) ? $this->config['booskit_awards_access_full'] : '',
			'LOCAL_DEFINITIONS' => $local_definitions,
		));
	}
}