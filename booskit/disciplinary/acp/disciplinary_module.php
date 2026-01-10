<?php
/**
 *
 * @package booskit/disciplinary
 * @license MIT
 *
 */

namespace booskit\disciplinary\acp;

class disciplinary_module
{
	public $u_action;

	public function main($id, $mode)
	{
		global $user, $template, $request, $config, $phpbb_container;

		$user->add_lang_ext('booskit/disciplinary', 'info_acp_disciplinary');

		$this->tpl_name = 'acp_disciplinary_settings';
		$this->page_title = 'ACP_BOOSKIT_DISCIPLINARY_TITLE';

		$form_key = 'acp_disciplinary_settings';
		add_form_key($form_key);

		$action = $request->variable('action', '');
		$disciplinary_manager = $phpbb_container->get('booskit.disciplinary.service.disciplinary_manager');

		if ($action == 'delete')
		{
			$def_id = $request->variable('def_id', 0);
			if (confirm_box(true))
			{
				if ($def_id)
				{
					$disciplinary_manager->delete_local_definition($def_id);
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
					$disciplinary_manager->add_local_definition($id, $name, $desc, $color, $access_level);
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
					$disciplinary_manager->update_local_definition(
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
				$config->set('booskit_disciplinary_source', $request->variable('booskit_disciplinary_source', 'url'));
				$config->set('booskit_disciplinary_json_url', $request->variable('booskit_disciplinary_json_url', ''));
				$config->set('booskit_disciplinary_access_l1', $request->variable('booskit_disciplinary_access_l1', ''));
				$config->set('booskit_disciplinary_access_l2', $request->variable('booskit_disciplinary_access_l2', ''));
				$config->set('booskit_disciplinary_access_l3', $request->variable('booskit_disciplinary_access_l3', ''));
				$config->set('booskit_disciplinary_access_full', $request->variable('booskit_disciplinary_access_full', ''));

				trigger_error($user->lang['CONFIG_UPDATED'] . adm_back_link($this->u_action));
			}
		}

		// Fetch local definitions
		$local_definitions = $disciplinary_manager->get_local_definitions();

		$template->assign_vars(array(
			'BOOSKIT_DISCIPLINARY_SOURCE'	=> isset($config['booskit_disciplinary_source']) ? $config['booskit_disciplinary_source'] : 'url',
			'BOOSKIT_DISCIPLINARY_JSON_URL'	=> $config['booskit_disciplinary_json_url'],
			'BOOSKIT_DISCIPLINARY_ACCESS_L1'	=> $config['booskit_disciplinary_access_l1'],
			'BOOSKIT_DISCIPLINARY_ACCESS_L2'	=> $config['booskit_disciplinary_access_l2'],
			'BOOSKIT_DISCIPLINARY_ACCESS_L3'	=> $config['booskit_disciplinary_access_l3'],
			'BOOSKIT_DISCIPLINARY_ACCESS_FULL'	=> $config['booskit_disciplinary_access_full'],
			'LOCAL_DEFINITIONS'				=> $local_definitions,
			'U_ACTION'						=> $this->u_action,
		));
	}
}
