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
			if ($field['field_type'] == 'section_start' || $field['field_type'] == 'section_end' || $field['field_type'] == 'input_group_start' || $field['field_type'] == 'input_group_end')
			{
				$options_decoded = json_decode($field['field_options'], true);
				$columns = isset($options_decoded['columns']) ? (int) $options_decoded['columns'] : 2;
				$multi = isset($options_decoded['multi']) ? (bool) $options_decoded['multi'] : false;

				$this->template->assign_block_vars('fields', [
					'NAME'			=> $field['field_name'],
					'LABEL'			=> $field['field_label'],
					'TYPE'			=> $field['field_type'],
					'REQUIRED'		=> false,
					'EXPLAIN'		=> $field['field_desc'],
					'DEFAULT_VALUE'	=> '',
					'S_TEXT'		=> false,
					'S_TEXTAREA'	=> false,
					'S_SELECT'		=> false,
					'S_CHECKBOX'	=> false,
					'S_RADIO'		=> false,
					'S_NUMBER'		=> false,
					'S_DATE'		=> false,
					'S_SECTION_START' => $field['field_type'] == 'section_start',
					'S_SECTION_END'   => $field['field_type'] == 'section_end',
					'S_INPUT_GROUP_START' => $field['field_type'] == 'input_group_start',
					'S_INPUT_GROUP_END'   => $field['field_type'] == 'input_group_end',
					'COLUMNS'		=> $columns,
					'S_MULTI'		=> $multi,
				]);
				continue;
			}

			$current_value = $this->request->variable($field['field_name'], '', true);
			if (empty($current_value))
			{
				$current_value = $this->request->variable($field['field_name'], array(''), true);
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
				'S_SECTION_START' => false,
				'S_SECTION_END'   => false,
				'S_INPUT_GROUP_START' => false,
				'S_INPUT_GROUP_END'   => false,
			]);

			$options = $this->get_field_options($field);

			if (is_array($options))
			{
				foreach ($options as $val => $label)
				{
					$this->template->assign_block_vars('fields.options', [
						'VALUE'		=> $val,
						'LABEL'		=> $label,
						'S_SELECTED' => is_array($current_value) ? in_array((string)$val, $current_value) : ((string)$val == (string)$current_value),
					]);
				}
			}
		}

		// Parse BBCode for display
		$form['form_desc'] = generate_text_for_display($form['form_desc'], $form['form_desc_uid'], $form['form_desc_bitfield'], $form['form_desc_options']);
		$form['form_header'] = generate_text_for_display($form['form_header'], $form['form_header_uid'], $form['form_header_bitfield'], $form['form_header_options']);

		// Fetch submitted values for re-populating (in case of validation error)
		$submitted_data = [];
		foreach ($fields as $field)
		{
			if ($field['field_type'] == 'section_start' || $field['field_type'] == 'section_end' || $field['field_type'] == 'input_group_start' || $field['field_type'] == 'input_group_end')
			{
				continue;
			}
			if ($this->request->is_set_post($field['field_name']))
			{
				$val = $this->request->variable($field['field_name'], array(array('')), true);
				if (empty($val) || (count($val) == 1 && isset($val[0]) && $val[0] === array('')))
				{
					$val = $this->request->variable($field['field_name'], array(''), true);
				}
				if (empty($val) || (count($val) == 1 && isset($val[0]) && $val[0] === ''))
				{
					$val = $this->request->variable($field['field_name'], '', true);
				}

				if ($val !== '' && !empty($val))
				{
					$submitted_data[$field['field_name']] = $val;
				}
			}
		}

		$this->template->assign_vars([
			'FORM_NAME'		=> $form['form_name'],
			'FORM_DESC'		=> $form['form_desc'],
			'FORM_HEADER'	=> $form['form_header'],
			'U_ACTION'		=> $this->helper->route('booskit_forms_submit', ['form_id' => $form_id]),
			'SUBMITTED_DATA_JSON' => !empty($submitted_data) ? json_encode($submitted_data) : '',
		]);

		return $this->helper->render('form_display.html', $form['form_name']);
	}

	protected function get_field_options($field)
	{
		$options = $field['field_options'];
		$decoded_options = json_decode($options, true);
		
		if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_options))
		{
			return $decoded_options;
		}
		else if (strpos($options, ':') !== false)
		{
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
			return $options;
		}

		return [$options => $options];
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

		// 1. Initial system replacements
		$replacements = [
			'USERNAME'  => ($this->user->data['user_id'] == ANONYMOUS) ? $this->user->lang['GUEST'] : $this->user->data['username'],
			'USER_ID'   => $this->user->data['user_id'],
			'FORM_NAME' => $form['form_name'],
			'FORM_ID'   => $form['form_id'],
			'DATE'      => $this->user->format_date(time(), 'D M d, Y'),
			'TIME'      => $this->user->format_date(time(), 'H:i'),
		];
		
		// Legacy lowercase aliases (will be overwritten if user has field with same name)
		$replacements['username'] = $replacements['USERNAME'];
		$replacements['date'] = $replacements['DATE'];
		$replacements['time'] = $replacements['TIME'];

		$raw_values = [];
		$errors = [];

		// Group field associations to check if they are part of a multi input group
		$input_groups = [];
		$current_group_name = null;
		
		foreach ($fields as $field)
		{
			if ($field['field_type'] == 'input_group_start')
			{
				$options_decoded = json_decode($field['field_options'], true);
				$multi = isset($options_decoded['multi']) ? (bool) $options_decoded['multi'] : false;
				
				$current_group_name = $field['field_name'];
				$input_groups[$current_group_name] = [
					'name'   => $current_group_name,
					'multi'  => $multi,
					'fields' => [],
				];
			}
			else if ($field['field_type'] == 'input_group_end')
			{
				$current_group_name = null;
			}
			else if ($field['field_type'] != 'section_start' && $field['field_type'] != 'section_end')
			{
				if ($current_group_name !== null)
				{
					$input_groups[$current_group_name]['fields'][] = $field;
				}
			}
		}

		$field_to_group = [];
		foreach ($input_groups as $group_name => $group_info)
		{
			foreach ($group_info['fields'] as $gf)
			{
				$field_to_group[$gf['field_name']] = $group_info;
			}
		}

		// 2. Process User Fields (Overwrites legacy aliases if collision occurs)
		foreach ($fields as $field)
		{
			if ($field['field_type'] == 'section_start' || $field['field_type'] == 'section_end' || $field['field_type'] == 'input_group_start' || $field['field_type'] == 'input_group_end')
			{
				continue;
			}
			
			$name = $field['field_name'];
			$selected_values = [];
			$is_in_multi_group = isset($field_to_group[$name]) && $field_to_group[$name]['multi'];

			if ($is_in_multi_group)
			{
				if ($field['field_type'] == 'checkbox')
				{
					$raw_data = $this->request->variable($name, array(array('')), true);
					foreach ($raw_data as $row_idx => $row_val)
					{
						$raw_data[$row_idx] = array_values(array_filter($row_val, function($v) { return $v !== ''; }));
					}
				}
				else
				{
					$raw_data = $this->request->variable($name, array(''), true);
				}
				
				$raw_values[$name] = $raw_data;
				
				$field_options = $this->get_field_options($field);
				$all_row_labels = [];
				foreach ($raw_data as $row_val)
				{
					$selected_labels = [];
					if (is_array($row_val))
					{
						foreach ($row_val as $v)
						{
							if ($v !== '')
							{
								$selected_labels[] = isset($field_options[$v]) ? $field_options[$v] : (string)$v;
							}
						}
					}
					else
					{
						if ($row_val !== '')
						{
							$selected_labels[] = isset($field_options[$row_val]) ? $field_options[$row_val] : (string)$row_val;
						}
					}
					if (!empty($selected_labels))
					{
						$all_row_labels[] = implode(', ', $selected_labels);
					}
				}
				
				$replacements[$name] = implode('; ', $all_row_labels);

				if ($field['field_required'])
				{
					$has_empty = false;
					if (empty($raw_data))
					{
						$has_empty = true;
					}
					else
					{
						foreach ($raw_data as $row_val)
						{
							if ($field['field_type'] == 'checkbox')
							{
								if (empty($row_val) || (count($row_val) == 1 && isset($row_val[0]) && $row_val[0] === ''))
								{
									$has_empty = true;
									break;
								}
							}
							else
							{
								if ($row_val === '')
								{
									$has_empty = true;
									break;
								}
							}
						}
					}
					if ($has_empty)
					{
						$errors[] = sprintf($this->user->lang['FIELD_REQUIRED'], $field['field_label']);
					}
				}
			}
			else
			{
				if ($field['field_type'] == 'checkbox')
				{
					$selected_values = $this->request->variable($name, array(''), true);
					$selected_values = array_filter($selected_values, function($v) { return $v !== ''; });
					$selected_values = array_values($selected_values);
				}
				else
				{
					$val = $this->request->variable($name, '', true);
					if ($val !== '')
					{
						$selected_values = [$val];
					}
				}

				$raw_values[$name] = $selected_values;
				
				$field_options = $this->get_field_options($field);
				$selected_labels = [];
				foreach ($selected_values as $v)
				{
					$selected_labels[] = isset($field_options[$v]) ? $field_options[$v] : (string)$v;
				}
				
				$replacements[$name] = implode(', ', $selected_labels);

				if ($field['field_required'] && empty($selected_values))
				{
					$errors[] = sprintf($this->user->lang['FIELD_REQUIRED'], $field['field_label']);
				}
			}
		}

		// 3. Generate Summary Tags
		$all_fields_text = '';
		foreach ($fields as $field)
		{
			if ($field['field_type'] == 'section_end' || $field['field_type'] == 'input_group_end')
			{
				continue;
			}
			if ($field['field_type'] == 'section_start')
			{
				$all_fields_text .= "\n[b][size=120]" . $field['field_label'] . "[/size][/b]\n";
				if (!empty($field['field_desc']))
				{
					$all_fields_text .= "[i]" . $field['field_desc'] . "[/i]\n";
				}
				continue;
			}
			if ($field['field_type'] == 'input_group_start')
			{
				$all_fields_text .= "\n[b]" . $field['field_label'] . "[/b]\n";
				if (!empty($field['field_desc']))
				{
					$all_fields_text .= "[i]" . $field['field_desc'] . "[/i]\n";
				}
				continue;
			}
			$val_text = isset($replacements[$field['field_name']]) ? $replacements[$field['field_name']] : '';
			$all_fields_text .= '[b]' . $field['field_label'] . ':[/b] ' . $val_text . "\n";
		}
		$replacements['SUMMARY'] = $all_fields_text;
		$replacements['ALL_FIELDS'] = $all_fields_text;
		
		// Only use 'fields' or 'all_fields' if it doesn't collide with a user field
		if (!isset($raw_values['all_fields'])) $replacements['all_fields'] = $all_fields_text;
		if (!isset($raw_values['fields'])) $replacements['fields'] = $all_fields_text;

		if (!empty($errors))
		{
			$error_msg = implode('<br />', $errors);
			$back_url = $this->helper->route('booskit_forms_view', ['form_id' => $form_id]);
			trigger_error($error_msg . '<br /><br />' . sprintf($this->user->lang['RETURN_PAGE'], '<a href="' . $back_url . '">', '</a>'), E_USER_WARNING);
		}

		$subject = $form['form_subject_tpl'];
		$body = $form['form_template'];

		// 3b. Process Multi Input Group Loops: {{#groupname}} ... {{/groupname}}
		foreach ($input_groups as $group_name => $group_info)
		{
			if (!$group_info['multi'])
			{
				continue;
			}
			
			$num_rows = 0;
			foreach ($group_info['fields'] as $gf)
			{
				$gf_name = $gf['field_name'];
				if (isset($raw_values[$gf_name]) && is_array($raw_values[$gf_name]))
				{
					$num_rows = max($num_rows, count($raw_values[$gf_name]));
				}
			}
			
			$rows_data = [];
			for ($i = 0; $i < $num_rows; $i++)
			{
				$row_replacements = [];
				foreach ($group_info['fields'] as $gf)
				{
					$gf_name = $gf['field_name'];
					$gf_options = $this->get_field_options($gf);
					$val_at_row = isset($raw_values[$gf_name][$i]) ? $raw_values[$gf_name][$i] : '';
					
					$labels = [];
					if (is_array($val_at_row))
					{
						foreach ($val_at_row as $v)
						{
							if ($v !== '')
							{
								$labels[] = isset($gf_options[$v]) ? $gf_options[$v] : (string)$v;
							}
						}
					}
					else
					{
						if ($val_at_row !== '')
						{
							$labels[] = isset($gf_options[$val_at_row]) ? $gf_options[$val_at_row] : (string)$val_at_row;
						}
					}
					
					$row_replacements[$gf_name] = implode(', ', $labels);
				}
				$rows_data[] = $row_replacements;
			}
			
			$pattern = '/\{\{\s*#' . preg_quote($group_name, '/') . '\s*\}\}(.*?)\{\{\s*\/' . preg_quote($group_name, '/') . '\s*\}\}/s';
			
			$replacement_func = function($matches) use ($rows_data, $group_info) {
				$loop_content = $matches[1];
				$result = '';
				foreach ($rows_data as $row_data)
				{
					$temp = $loop_content;
					foreach ($group_info['fields'] as $gf)
					{
						$gf_name = $gf['field_name'];
						$val_text = isset($row_data[$gf_name]) ? $row_data[$gf_name] : '';
						
						$temp = preg_replace_callback('/\{\{\s*' . preg_quote($gf_name, '/') . '\s*\}\}/i', function() use ($val_text) {
							return $val_text;
						}, $temp);
					}
					$result .= $temp;
				}
				return $result;
			};
			
			$body = preg_replace_callback($pattern, $replacement_func, $body);
			$subject = preg_replace_callback($pattern, $replacement_func, $subject);
		}

		// 4. Process Field Loops: {{#fieldname}} ... {{/fieldname}}
		foreach ($fields as $field)
		{
			if ($field['field_type'] == 'section_start' || $field['field_type'] == 'section_end' || $field['field_type'] == 'input_group_start' || $field['field_type'] == 'input_group_end')
			{
				continue;
			}
			$name = $field['field_name'];
			$selected_values = $raw_values[$name];
			
			// If it's a multi-group field, $selected_values is 2D/1D array.
			// The original field loops only apply for checkbox / multi-select values of a single field.
			// Let's flatten to 1D if it is a multi group nested array for legacy support.
			if (isset($field_to_group[$name]) && $field_to_group[$name]['multi'])
			{
				$flattened = [];
				foreach ($selected_values as $row_val)
				{
					if (is_array($row_val))
					{
						$flattened = array_merge($flattened, $row_val);
					}
					else if ($row_val !== '')
					{
						$flattened[] = $row_val;
					}
				}
				$selected_values = $flattened;
			}
			
			$field_options = $this->get_field_options($field);
			
			$selected_data = [];
			foreach ($selected_values as $v)
			{
				$selected_data[] = [
					'value' => (string)$v,
					'label' => isset($field_options[$v]) ? $field_options[$v] : (string)$v,
				];
			}

			$pattern = '/\{\{\s*#' . preg_quote($name, '/') . '\s*\}\}(.*?)\{\{\s*\/' . preg_quote($name, '/') . '\s*\}\}/s';
			
			$replacement_func = function($matches) use ($selected_data) {
				$loop_content = $matches[1];
				$result = '';
				foreach ($selected_data as $data)
				{
					$temp = preg_replace_callback('/\{\{\s*value\s*\}\}/i', function() use ($data) { return $data['value']; }, $loop_content);
					$temp = preg_replace_callback('/\{\{\s*label\s*\}\}/i', function() use ($data) { return $data['label']; }, $temp);
					$result .= $temp;
				}
				return $result;
			};

			$body = preg_replace_callback($pattern, $replacement_func, $body);
			$subject = preg_replace_callback($pattern, $replacement_func, $subject);
		}

		// 5. Global Fields Loop: {{#fields}} ... {{/fields}}
		if (!isset($raw_values['fields']))
		{
			$all_fields_data = [];
			foreach ($fields as $field)
			{
				if ($field['field_type'] == 'section_start' || $field['field_type'] == 'section_end' || $field['field_type'] == 'input_group_start' || $field['field_type'] == 'input_group_end')
				{
					continue;
				}
				$all_fields_data[] = [
					'name'  => $field['field_name'],
					'label' => $field['field_label'],
					'value' => isset($replacements[$field['field_name']]) ? $replacements[$field['field_name']] : '',
				];
			}
			
			$fields_loop_callback = function($matches) use ($all_fields_data) {
				$loop_content = $matches[1];
				$result = '';
				foreach ($all_fields_data as $data)
				{
					$temp = preg_replace_callback('/\{\{\s*name\s*\}\}/i', function() use ($data) { return $data['name']; }, $loop_content);
					$temp = preg_replace_callback('/\{\{\s*label\s*\}\}/i', function() use ($data) { return $data['label']; }, $temp);
					$temp = preg_replace_callback('/\{\{\s*value\s*\}\}/i', function() use ($data) { return $data['value']; }, $temp);
					$result .= $temp;
				}
				return $result;
			};
			
			$body = preg_replace_callback('/\{\{\s*#fields\s*\}\}(.*?)\{\{\s*\/fields\s*\}\}/si', $fields_loop_callback, $body);
			$subject = preg_replace_callback('/\{\{\s*#fields\s*\}\}(.*?)\{\{\s*\/fields\s*\}\}/si', $fields_loop_callback, $subject);
		}

		// 6. Final Simple Tags: {{variable}}
		foreach ($replacements as $key => $val)
		{
			$pattern = '/\{\{\s*' . preg_quote($key, '/') . '\s*\}\}/i';
			$callback = function() use ($val) { return $val; };
			$subject = preg_replace_callback($pattern, $callback, $subject);
			$body = preg_replace_callback($pattern, $callback, $body);
		}

		if (!$this->request->is_set_post('confirm'))
		{
			// Preview
			$preview_body = $body;
			$uid = $bitfield = '';
			$options = 7;
			generate_text_for_storage($preview_body, $uid, $bitfield, $options, true, true, true);
			$preview_body = generate_text_for_display($preview_body, $uid, $bitfield, $options);

			foreach ($fields as $field)
			{
				if ($field['field_type'] == 'section_start' || $field['field_type'] == 'section_end' || $field['field_type'] == 'input_group_start' || $field['field_type'] == 'input_group_end')
				{
					continue;
				}
				$key = $field['field_name'];
				$val = isset($raw_values[$key]) ? $raw_values[$key] : null;
				if ($val === null)
				{
					continue;
				}
				
				$is_in_multi_group = isset($field_to_group[$key]) && $field_to_group[$key]['multi'];
				
				if ($is_in_multi_group)
				{
					foreach ($val as $row_idx => $row_val)
					{
						if ($field['field_type'] == 'checkbox')
						{
							foreach ($row_val as $v)
							{
								$this->template->assign_block_vars('hidden_fields', [
									'NAME'  => $key . '[' . $row_idx . '][]',
									'VALUE' => $v,
								]);
							}
						}
						else
						{
							$this->template->assign_block_vars('hidden_fields', [
								'NAME'  => $key . '[' . $row_idx . ']',
								'VALUE' => $row_val,
							]);
						}
					}
				}
				else
				{
					if ($field['field_type'] == 'checkbox')
					{
						foreach ($val as $v)
						{
							$this->template->assign_block_vars('hidden_fields', [
								'NAME'  => $key . '[]',
								'VALUE' => $v,
							]);
						}
					}
					else
					{
						$this->template->assign_block_vars('hidden_fields', [
							'NAME'  => $key,
							'VALUE' => isset($val[0]) ? $val[0] : '',
						]);
					}
				}
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
