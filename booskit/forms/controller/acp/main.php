<?php
/**
 *
 * @package booskit/forms
 * @license MIT
 *
 */

namespace booskit\forms\controller\acp;

class main
{
	protected $config;
	protected $request;
	protected $template;
	protected $user;
	protected $log;
	protected $form_manager;

	public function __construct(\phpbb\config\config $config, \phpbb\request\request_interface $request, \phpbb\template\template $template, \phpbb\user $user, \phpbb\log\log $log, \booskit\forms\service\form_manager $form_manager)
	{
		$this->config = $config;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->log = $log;
		$this->form_manager = $form_manager;
	}

	public function handle($u_action)
	{
		$this->user->add_lang_ext('booskit/forms', 'info_acp_forms');

		$action = $this->request->variable('action', '');
		$form_id = $this->request->variable('form_id', 0);

		if ($action == 'delete')
		{
			if (confirm_box(true))
			{
				$this->form_manager->delete_form($form_id);
				$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_BOOSKIT_FORM_DELETED', false, [$form_id]);
				trigger_error($this->user->lang['FORM_DELETED'] . adm_back_link($u_action));
			}
			else
			{
				confirm_box(false, $this->user->lang['CONFIRM_OPERATION'], build_hidden_fields(['form_id' => $form_id, 'action' => 'delete']));
			}
		}

		if ($this->request->is_set_post('submit'))
		{
			if (!check_form_key('acp_booskit_forms'))
			{
				trigger_error($this->user->lang['FORM_INVALID'] . adm_back_link($u_action), E_USER_WARNING);
			}

			$data = [
				'form_name'			=> $this->request->variable('form_name', '', true),
				'form_slug'			=> $this->request->variable('form_slug', ''),
				'form_groups'		=> $this->request->variable('form_groups', ''),
				'form_public'		=> $this->request->variable('form_public', 0),
				'form_desc'			=> $this->request->variable('form_desc', '', true),
				'form_header'		=> $this->request->variable('form_header', '', true),
				'form_template'		=> $this->request->variable('form_template', '', true),
				'form_subject_tpl'	=> $this->request->variable('form_subject_tpl', '', true),
				'forum_id'			=> $this->request->variable('forum_id', 0),
				'poster_id'			=> $this->request->variable('poster_id', 0),
				'enabled'			=> $this->request->variable('enabled', 1),
			];

			$form_fields_json = $this->request->variable('form_fields', '', true);

			// Parse BBCode for description
			$desc_uid = $desc_bitfield = '';
			$desc_options = 7;
			generate_text_for_storage($data['form_desc'], $desc_uid, $desc_bitfield, $desc_options, true, true, true);
			$data['form_desc_uid'] = $desc_uid;
			$data['form_desc_bitfield'] = $desc_bitfield;
			$data['form_desc_options'] = $desc_options;

			// Parse BBCode for header
			$header_uid = $header_bitfield = '';
			$header_options = 7;
			generate_text_for_storage($data['form_header'], $header_uid, $header_bitfield, $header_options, true, true, true);
			$data['form_header_uid'] = $header_uid;
			$data['form_header_bitfield'] = $header_bitfield;
			$data['form_header_options'] = $header_options;

			if ($action == 'edit' && $form_id)
			{
				$this->form_manager->update_form($form_id, $data);
				$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_BOOSKIT_FORM_UPDATED', false, [$data['form_name']]);
			}
			else
			{
				$form_id = $this->form_manager->add_form($data);
				$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_BOOSKIT_FORM_ADDED', false, [$data['form_name']]);
			}

			// Handle fields
			$this->form_manager->delete_form_fields($form_id);
			$fields = json_decode(htmlspecialchars_decode($form_fields_json), true);
			if (is_array($fields))
			{
				foreach ($fields as $order => $field)
				{
					if (empty($field['name'])) continue;
					
					$field_data = [
						'form_id'			=> (int) $form_id,
						'field_label'		=> (string) $field['label'],
						'field_name'		=> (string) $field['name'],
						'field_desc'		=> (string) $field['description'],
						'field_type'		=> (string) $field['type'],
						'field_options'		=> is_array($field['options']) ? json_encode($field['options']) : (string) $field['options'],
						'field_required'	=> (bool) $field['required'],
						'field_order'		=> (int) $order,
					];
					$this->form_manager->add_field($field_data);
				}
			}

			trigger_error($this->user->lang['CONFIG_UPDATED'] . adm_back_link($u_action));
		}

		if ($action == 'add' || $action == 'edit')
		{
			$form_data = ($action == 'edit') ? $this->form_manager->get_form($form_id) : [];

			if ($action == 'edit')
			{
				$desc_data = generate_text_for_edit($form_data['form_desc'], $form_data['form_desc_uid'], $form_data['form_desc_options']);
				$form_data['form_desc'] = $desc_data['text'];

				$header_data = generate_text_for_edit($form_data['form_header'], $form_data['form_header_uid'], $form_data['form_header_options']);
				$form_data['form_header'] = $header_data['text'];

				// Fetch fields from table
				$fields_rows = $this->form_manager->get_form_fields($form_id);
				$fields_data = [];
				foreach ($fields_rows as $row)
				{
					$options = $row['field_options'];
					$decoded_options = json_decode($options, true);
					if (json_last_error() === JSON_ERROR_NONE)
					{
						$options = $decoded_options;
					}

					$fields_data[] = [
						'label'			=> $row['field_label'],
						'name'			=> $row['field_name'],
						'description'	=> $row['field_desc'],
						'type'			=> $row['field_type'],
						'options'		=> $options,
						'required'		=> (bool) $row['field_required'],
					];
				}
				$form_data['form_fields'] = json_encode($fields_data);
			}

			$this->template->assign_vars([
				'S_EDIT'			=> true,
				'U_ACTION'			=> $u_action . '&amp;action=' . $action . ($form_id ? '&amp;form_id=' . $form_id : ''),
				'FORM_NAME'			=> isset($form_data['form_name']) ? $form_data['form_name'] : '',
				'FORM_SLUG'			=> isset($form_data['form_slug']) ? $form_data['form_slug'] : '',
				'FORM_GROUPS'		=> isset($form_data['form_groups']) ? $form_data['form_groups'] : '',
				'FORM_PUBLIC'		=> isset($form_data['form_public']) ? $form_data['form_public'] : 0,
				'FORM_DESC'			=> isset($form_data['form_desc']) ? $form_data['form_desc'] : '',
				'FORM_HEADER'		=> isset($form_data['form_header']) ? $form_data['form_header'] : '',
				'FORM_TEMPLATE'		=> isset($form_data['form_template']) ? $form_data['form_template'] : '',
				'FORM_SUBJECT_TPL'	=> isset($form_data['form_subject_tpl']) ? $form_data['form_subject_tpl'] : '',
				'FORM_FIELDS'		=> isset($form_data['form_fields']) ? htmlspecialchars($form_data['form_fields']) : '',
				'FORUM_ID'			=> isset($form_data['forum_id']) ? $form_data['forum_id'] : 0,
				'POSTER_ID'			=> isset($form_data['poster_id']) ? $form_data['poster_id'] : 0,
				'ENABLED'			=> isset($form_data['enabled']) ? $form_data['enabled'] : 1,
			]);

			return;
		}

		$forms = $this->form_manager->get_forms();
		foreach ($forms as $form)
		{
			$this->template->assign_block_vars('forms', [
				'ID'		=> $form['form_id'],
				'NAME'		=> $form['form_name'],
				'ENABLED'	=> $form['enabled'],
				'U_EDIT'	=> $u_action . '&amp;action=edit&amp;form_id=' . $form['form_id'],
				'U_DELETE'	=> $u_action . '&amp;action=delete&amp;form_id=' . $form['form_id'],
			]);
		}

		$this->template->assign_vars([
			'U_ACTION'	=> $u_action,
			'U_ADD'		=> $u_action . '&amp;action=add',
		]);
	}
}
