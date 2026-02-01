<?php
/**
 *
 * @package booskit/icdisciplinary
 * @license MIT
 *
 */

namespace booskit\icdisciplinary\acp;

class icdisciplinary_module
{
	public $u_action;

	public function main($id, $mode)
	{
		global $user, $template, $request, $config, $phpbb_container;

		$user->add_lang_ext('booskit/icdisciplinary', 'info_acp_icdisciplinary');

		$this->tpl_name = 'acp_icdisciplinary_settings';
		$this->page_title = 'ACP_BOOSKIT_ICDISCIPLINARY_TITLE';

		$form_key = 'acp_icdisciplinary_settings';
		add_form_key($form_key);

		$action = $request->variable('action', '');
		$ic_manager = $phpbb_container->get('booskit.icdisciplinary.service.ic_manager');

		global $db, $table_prefix;
		$config_text = new \phpbb\config\db_text($db, $table_prefix . 'config_text');

		if ($action == 'delete')
		{
			$def_id = $request->variable('def_id', 0);
			if (confirm_box(true))
			{
				if ($def_id)
				{
					$ic_manager->delete_local_definition($def_id);
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
				$color = $request->variable('new_color', '#000000');
				$access_level = $request->variable('new_access_level', 0);

				if (!empty($id) && !empty($name))
				{
					$ic_manager->add_local_definition($id, $name, $desc, $color, $access_level);
				}
				trigger_error($user->lang['CONFIG_UPDATED'] . adm_back_link($this->u_action));
			}

			if ($action == 'update_one')
			{
				$def_id = $request->variable('def_id', 0);

				$ids = $request->variable('id', array(0 => ''));
				$names = $request->variable('name', array(0 => ''));
				$descs = $request->variable('desc', array(0 => ''));
				$colors = $request->variable('color', array(0 => ''));
				$access_levels = $request->variable('access_level', array(0 => 0));

				if ($def_id && isset($ids[$def_id]) && isset($names[$def_id]))
				{
					$ic_manager->update_local_definition(
						$def_id,
						$ids[$def_id],
						$names[$def_id],
						$descs[$def_id],
						$colors[$def_id],
						$access_levels[$def_id]
					);
				}
				trigger_error($user->lang['CONFIG_UPDATED'] . adm_back_link($this->u_action));
			}

			if ($action == '')
			{
				$config->set('booskit_icdisciplinary_source', $request->variable('booskit_icdisciplinary_source', 'url'));
				$config->set('booskit_icdisciplinary_json_url', $request->variable('booskit_icdisciplinary_json_url', ''));
				$config->set('booskit_icdisciplinary_access_l1', $request->variable('booskit_icdisciplinary_access_l1', ''));
				$config->set('booskit_icdisciplinary_access_l2', $request->variable('booskit_icdisciplinary_access_l2', ''));
				$config->set('booskit_icdisciplinary_access_full', $request->variable('booskit_icdisciplinary_access_full', ''));

				// Ruleset
				$ruleset_text = $request->variable('booskit_icdisciplinary_ruleset', '', true);
				$ruleset_uid = $request->variable('booskit_icdisciplinary_ruleset_uid', '');
				$ruleset_bitfield = $request->variable('booskit_icdisciplinary_ruleset_bitfield', '');
				$ruleset_options = $request->variable('booskit_icdisciplinary_ruleset_options', 7);

				generate_text_for_storage($ruleset_text, $ruleset_uid, $ruleset_bitfield, $ruleset_options, true, true, true);

				$config_text->set('booskit_icdisciplinary_ruleset', $ruleset_text);
				$config->set('booskit_icdisciplinary_ruleset_uid', $ruleset_uid);
				$config->set('booskit_icdisciplinary_ruleset_bitfield', $ruleset_bitfield);
				$config->set('booskit_icdisciplinary_ruleset_options', $ruleset_options);

				trigger_error($user->lang['CONFIG_UPDATED'] . adm_back_link($this->u_action));
			}
		}

		// Fetch local definitions
		$local_definitions = $ic_manager->get_local_definitions();

		// Prepare Ruleset
		$ruleset_text = $config_text->get('booskit_icdisciplinary_ruleset');
		$ruleset_uid = isset($config['booskit_icdisciplinary_ruleset_uid']) ? $config['booskit_icdisciplinary_ruleset_uid'] : '';
		$ruleset_bitfield = isset($config['booskit_icdisciplinary_ruleset_bitfield']) ? $config['booskit_icdisciplinary_ruleset_bitfield'] : '';
		$ruleset_options = isset($config['booskit_icdisciplinary_ruleset_options']) ? (int) $config['booskit_icdisciplinary_ruleset_options'] : 7;

		$text_data = generate_text_for_edit($ruleset_text, $ruleset_uid, $ruleset_options);
		$ruleset_text = $text_data['text'];

		$template->assign_vars(array(
			'BOOSKIT_ICDISCIPLINARY_RULESET' => $ruleset_text,
			'BOOSKIT_ICDISCIPLINARY_RULESET_UID' => $ruleset_uid,
			'BOOSKIT_ICDISCIPLINARY_RULESET_BITFIELD' => $ruleset_bitfield,
			'BOOSKIT_ICDISCIPLINARY_RULESET_OPTIONS' => $ruleset_options,
			'BOOSKIT_ICDISCIPLINARY_SOURCE'	=> isset($config['booskit_icdisciplinary_source']) ? $config['booskit_icdisciplinary_source'] : 'url',
			'BOOSKIT_ICDISCIPLINARY_JSON_URL'	=> isset($config['booskit_icdisciplinary_json_url']) ? $config['booskit_icdisciplinary_json_url'] : '',
			'BOOSKIT_ICDISCIPLINARY_ACCESS_L1'	=> isset($config['booskit_icdisciplinary_access_l1']) ? $config['booskit_icdisciplinary_access_l1'] : '',
			'BOOSKIT_ICDISCIPLINARY_ACCESS_L2'	=> isset($config['booskit_icdisciplinary_access_l2']) ? $config['booskit_icdisciplinary_access_l2'] : '',
			'BOOSKIT_ICDISCIPLINARY_ACCESS_FULL'	=> isset($config['booskit_icdisciplinary_access_full']) ? $config['booskit_icdisciplinary_access_full'] : '',
			'LOCAL_DEFINITIONS'				=> $local_definitions,
			'U_ACTION'						=> $this->u_action,
		));
	}
}
