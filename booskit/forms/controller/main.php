<?php
/**
 *
 * @package booskit/forms
 * @license MIT
 *
 */

namespace booskit\forms\controller;

class main
{
	protected $config;
	protected $request;
	protected $template;
	protected $user;
	protected $helper;
	protected $form_manager;

	public function __construct(\phpbb\config\config $config, \phpbb\request\request_interface $request, \phpbb\template\template $template, \phpbb\user $user, \phpbb\controller\helper $helper, \booskit\forms\service\form_manager $form_manager)
	{
		$this->config = $config;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->helper = $helper;
		$this->form_manager = $form_manager;
	}

	public function display($form_id)
	{
		$this->user->add_lang_ext('booskit/forms', 'forms');
		$form = $this->form_manager->get_form($form_id);

		if (!$form || !$form['enabled'])
		{
			return $this->helper->error($this->user->lang['FORM_NOT_FOUND'], 404);
		}

		if (!$form['form_public'])
		{
			if ($this->user->data['user_id'] == ANONYMOUS)
			{
				return $this->helper->error($this->user->lang['LOGIN_REQUIRED_FOR_FORM'], 403);
			}

			if (!$this->form_manager->check_access($this->user->data['user_id'], $form['form_groups']))
			{
				return $this->helper->error($this->user->lang['FORM_NOT_AUTHORIZED'], 403);
			}
		}

		$fields = $this->form_manager->get_form_fields($form['form_id']);

		foreach ($fields as $field)
		{
			$current_value = $this->request->variable($field['field_name'], '', true);
			if (empty($current_value))
			{
				$current_value = $this->request->variable($field['field_name'], []);
			}

			$this->template->assign_block_vars('fields', [
				'NAME'			=> $field['field_name'],
				'LABEL'			=> $field['field_label'],
				'TYPE'			=> $field['field_type'],
				'REQUIRED'		=> (bool) $field['field_required'],
				'EXPLAIN'		=> $field['field_desc'],
				'DEFAULT_VALUE'	=> is_array($current_value) ? '' : $current_value,
				'S_TEXT'		=> $field['field_type'] == 'text',
				'S_TEXTAREA'	=> $field['field_type'] == 'textarea',
				'S_SELECT'		=> $field['field_type'] == 'select',
				'S_CHECKBOX'	=> $field['field_type'] == 'checkbox',
				'S_RADIO'		=> $field['field_type'] == 'radio',
				'S_NUMBER'		=> $field['field_type'] == 'number',
				'S_DATE'		=> $field['field_type'] == 'date',
			]);

			$options = $field['field_options'];
			$decoded_options = json_decode($options, true);
			
			if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_options))
			{
				$options = $decoded_options;
			}
			else if (strpos($options, ':') !== false)
			{
				// Fallback to manual parsing if it's stored as Val:Label,Val2:Label2
				$pairs = explode(',', $options);
				$options = [];
				foreach ($pairs as $p)
				{
					$kv = explode(':', $p);
					if (count($kv) == 2)
					{
						$options[trim($kv[0])] = trim($kv[1]);
					}
				}
			}

			if (is_array($options))
			{
				foreach ($options as $val => $label)
				{
					$this->template->assign_block_vars('fields.options', [
						'VALUE'		=> $val,
						'LABEL'		=> $label,
						'S_SELECTED' => is_array($current_value) ? in_array($val, $current_value) : ($val == $current_value),
					]);
				}
			}
			elseif (!empty($options))
			{
				$this->template->assign_block_vars('fields.options', [
					'VALUE' => $options,
					'LABEL' => $options,
					'S_SELECTED' => ($options == $current_value),
				]);
			}
		}

		// Parse BBCode for display
		$form['form_desc'] = generate_text_for_display($form['form_desc'], $form['form_desc_uid'], $form['form_desc_bitfield'], $form['form_desc_options']);
		$form['form_header'] = generate_text_for_display($form['form_header'], $form['form_header_uid'], $form['form_header_bitfield'], $form['form_header_options']);

		$this->template->assign_vars([
			'FORM_NAME'		=> $form['form_name'],
			'FORM_DESC'		=> $form['form_desc'],
			'FORM_HEADER'	=> $form['form_header'],
			'U_ACTION'		=> $this->helper->route('booskit_forms_submit', ['form_id' => $form_id]),
		]);

		return $this->helper->render('form_display.html', $form['form_name']);
	}

	public function submit($form_id)
	{
		$this->user->add_lang_ext('booskit/forms', 'forms');
		$form = $this->form_manager->get_form($form_id);

		if (!$form || !$form['enabled'])
		{
			return $this->helper->error($this->user->lang['FORM_NOT_FOUND'], 404);
		}

		if (!$form['form_public'])
		{
			if ($this->user->data['user_id'] == ANONYMOUS)
			{
				return $this->helper->error($this->user->lang['LOGIN_REQUIRED_FOR_FORM'], 403);
			}

			if (!$this->form_manager->check_access($this->user->data['user_id'], $form['form_groups']))
			{
				return $this->helper->error($this->user->lang['FORM_NOT_AUTHORIZED'], 403);
			}
		}

		if ($this->request->is_set_post('back'))
		{
			return $this->display($form_id);
		}

		$fields = $this->form_manager->get_form_fields($form['form_id']);

		$replacements = [
			'username' => ($this->user->data['user_id'] == ANONYMOUS) ? $this->user->lang['GUEST'] : $this->user->data['username'],
			'user_id'  => $this->user->data['user_id'],
		];

		$errors = [];
		foreach ($fields as $field)
		{
			$name = $field['field_name'];
			$value = '';

			if ($field['field_type'] == 'checkbox')
			{
				$value = $this->request->variable($name, []);
				$value = implode(', ', $value);
			}
			else
			{
				$value = $this->request->variable($name, '', true);
			}

			if ($field['field_required'] && empty($value))
			{
				$errors[] = sprintf($this->user->lang['FIELD_REQUIRED'], $field['field_label']);
			}

			$replacements[$name] = $value;
		}

		if (!empty($errors))
		{
			$error_msg = implode('<br />', $errors);
			$back_url = $this->helper->route('booskit_forms_view', ['form_id' => $form_id]);
			trigger_error($error_msg . '<br /><br />' . sprintf($this->user->lang['RETURN_PAGE'], '<a href="' . $back_url . '">', '</a>'), E_USER_WARNING);
		}

		$subject = $form['form_subject_tpl'];
		$body = $form['form_template'];

		foreach ($replacements as $key => $val)
		{
			$subject = str_replace('{{' . $key . '}}', $val, $subject);
			$body = str_replace('{{' . $key . '}}', $val, $body);
		}

		if (!$this->request->is_set_post('confirm'))
		{
			// Preview
			$preview_body = $body;
			$uid = $bitfield = '';
			$options = 7;
			generate_text_for_storage($preview_body, $uid, $bitfield, $options, true, true, true);
			$preview_body = generate_text_for_display($preview_body, $uid, $bitfield, $options);

			foreach ($replacements as $key => $val)
			{
				if ($key == 'username' || $key == 'user_id') continue;
				$this->template->assign_block_vars('hidden_fields', [
					'NAME'  => $key,
					'VALUE' => $val,
				]);
			}

			$this->template->assign_vars([
				'FORM_NAME'			=> $form['form_name'],
				'PREVIEW_SUBJECT'	=> $subject,
				'PREVIEW_BODY'		=> $preview_body,
				'U_ACTION'			=> $this->helper->route('booskit_forms_submit', ['form_id' => $form_id]),
			]);

			return $this->helper->render('form_preview.html', $this->user->lang['PREVIEW'] . ': ' . $form['form_name']);
		}

		$post_id = $this->form_manager->create_post($form['forum_id'], $form['poster_id'], $subject, $body);

		return $this->helper->message($this->user->lang['FORM_SUBMITTED_SUCCESS'], array_values([
			$this->user->lang['BACK_TO_FORM'] => $this->helper->route('booskit_forms_view', ['form_id' => $form_id]),
		]), 'INFORMATION');
	}
}
