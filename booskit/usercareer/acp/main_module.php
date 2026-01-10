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

				if ($def_id && isset($ids[$def_id]) && isset($names[$def_id]))
				{
					$career_manager->update_local_definition(
						$def_id,
						$ids[$def_id],
						$names[$def_id],
						$descs[$def_id],
						$icons[$def_id]
					);
				}
				trigger_error($user->lang['CONFIG_UPDATED'] . adm_back_link($this->u_action));
			}

			if ($action == '')
			{
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

		// Prepare Ruleset
		$ruleset_text = $config_text->get('booskit_career_ruleset');
		$ruleset_uid = isset($config['booskit_career_ruleset_uid']) ? $config['booskit_career_ruleset_uid'] : '';
		$ruleset_bitfield = isset($config['booskit_career_ruleset_bitfield']) ? $config['booskit_career_ruleset_bitfield'] : '';
		$ruleset_options = isset($config['booskit_career_ruleset_options']) ? (int) $config['booskit_career_ruleset_options'] : 7;

		generate_text_for_edit($ruleset_text, $ruleset_uid, $ruleset_bitfield, $ruleset_options, false);

		$template->assign_vars(array(
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
			'U_ACTION'						=> $this->u_action,
		));
	}
}
