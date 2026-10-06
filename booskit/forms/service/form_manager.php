<?php
/**
 *
 * @package booskit/forms
 * @license MIT
 *
 */

namespace booskit\forms\service;

class form_manager
{
	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\user */
	protected $user;

	/** @var string */
	protected $table_forms;

	/** @var string */
	protected $table_fields;

	/** @var \phpbb\log\log|null */
	protected $log;

	/** @var int */
	protected $last_post_id = 0;

	/** @var int */
	protected $last_topic_id = 0;

	public function __construct(\phpbb\db\driver\driver_interface $db, \phpbb\user $user, $table_forms, $table_fields, \phpbb\log\log $log = null)
	{
		$this->db = $db;
		$this->user = $user;
		$this->table_forms = $table_forms;
		$this->table_fields = $table_fields;
		$this->log = $log;
	}

	public function get_forms($enabled_only = false)
	{
		$sql = 'SELECT * FROM ' . $this->table_forms . ($enabled_only ? ' WHERE enabled = 1' : '') . ' ORDER BY form_id ASC';
		$result = $this->db->sql_query($sql);
		$forms = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$forms[] = $row;
		}
		$this->db->sql_freeresult($result);
		return $forms;
	}

	public function get_form($form_identifier)
	{
		$column = is_numeric($form_identifier) ? 'form_id' : 'form_slug';
		$sql = 'SELECT * FROM ' . $this->table_forms . ' WHERE ' . $column . ' = \'' . $this->db->sql_escape($form_identifier) . '\'';
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);
		return $row;
	}

	public function check_access($user_id, $group_ids_str)
	{
		if (empty($group_ids_str))
		{
			return true;
		}

		$allowed_groups = array_map('intval', explode(',', $group_ids_str));
		if (empty($allowed_groups))
		{
			return true;
		}

		$sql = 'SELECT group_id FROM ' . USER_GROUP_TABLE . ' 
			WHERE user_id = ' . (int) $user_id . ' 
			AND ' . $this->db->sql_in_set('group_id', $allowed_groups) . '
			AND user_pending = 0';
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return (bool) $row;
	}

	public function add_form($data)
	{
		$sql = 'INSERT INTO ' . $this->table_forms . ' ' . $this->db->sql_build_array('INSERT', $data);
		$this->db->sql_query($sql);
		return $this->db->sql_nextid();
	}

	public function update_form($form_id, $data)
	{
		$sql = 'UPDATE ' . $this->table_forms . ' SET ' . $this->db->sql_build_array('UPDATE', $data) . ' WHERE form_id = ' . (int) $form_id;
		$this->db->sql_query($sql);
	}

	public function delete_form($form_id)
	{
		// Delete fields first
		$sql = 'DELETE FROM ' . $this->table_fields . ' WHERE form_id = ' . (int) $form_id;
		$this->db->sql_query($sql);

		$sql = 'DELETE FROM ' . $this->table_forms . ' WHERE form_id = ' . (int) $form_id;
		$this->db->sql_query($sql);
	}

	public function get_form_fields($form_id)
	{
		$sql = 'SELECT * FROM ' . $this->table_fields . ' WHERE form_id = ' . (int) $form_id . ' ORDER BY field_order ASC, field_id ASC';
		$result = $this->db->sql_query($sql);
		$fields = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$fields[] = $row;
		}
		$this->db->sql_freeresult($result);
		return $fields;
	}

	public function add_field($data)
	{
		$sql = 'INSERT INTO ' . $this->table_fields . ' ' . $this->db->sql_build_array('INSERT', $data);
		$this->db->sql_query($sql);
	}

	public function delete_form_fields($form_id)
	{
		$sql = 'DELETE FROM ' . $this->table_fields . ' WHERE form_id = ' . (int) $form_id;
		$this->db->sql_query($sql);
	}

	public function create_post($forum_id, $poster_id, $subject, $body)
	{
		if (empty($poster_id))
		{
			$poster_id = $this->user->data['user_id'];
		}

		if (!function_exists('submit_post'))
		{
			global $phpbb_root_path, $phpEx;
			include($phpbb_root_path . 'includes/functions_posting.' . $phpEx);
		}

		$subject = utf8_normalize_nfc($subject);
		$text = utf8_normalize_nfc($body);

		$uid = $bitfield = $options = '';
		generate_text_for_storage($text, $uid, $bitfield, $options, true, true, true);

		$poll = $data = [];

		$data = [
			'topic_title'			=> $subject,
			'topic_first_post_id'	=> 0,
			'topic_last_post_id'	=> 0,
			'topic_time_limit'		=> 0,
			'topic_attachment'		=> 0,
			'post_id'				=> 0,
			'topic_id'				=> 0,
			'forum_id'				=> $forum_id,
			'icon_id'				=> 0,
			'poster_id'				=> $poster_id,
			'enable_sig'			=> true,
			'enable_bbcode'			=> true,
			'enable_smilies'		=> true,
			'enable_urls'			=> true,
			'enable_indexing'		=> true,
			'message_md5'			=> md5($text),
			'post_time'				=> time(),
			'post_checksum'			=> '',
			'post_edit_reason'		=> '',
			'post_edit_user'		=> 0,
			'forum_parents'			=> '',
			'forum_name'			=> '',
			'post_subject'			=> $subject,
			'message'				=> $text,
			'post_text'				=> $text,
			'bbcode_uid'			=> $uid,
			'bbcode_bitfield'		=> $bitfield,
			'bbcode_options'		=> $options,
			'poster_ip'				=> $this->user->ip,
			'post_approve'          => 1,
			'post_edit_locked'		=> 0,
			'notify_set'			=> false,
			'notify'				=> false,
		];

		$user_data_backup = $this->user->data;

		if ($poster_id != $this->user->data['user_id'])
		{
			$sql = 'SELECT * FROM ' . USERS_TABLE . ' WHERE user_id = ' . (int) $poster_id;
			$result = $this->db->sql_query($sql);
			$poster_row = $this->db->sql_fetchrow($result);
			$this->db->sql_freeresult($result);

			if ($poster_row)
			{
				$this->user->data = array_merge($this->user->data, $poster_row);
			}
		}

		$username = $this->user->data['username'];

		submit_post('post', $subject, $username, POST_NORMAL, $poll, $data);

		$this->user->data = $user_data_backup;

		$this->last_post_id = isset($data['post_id']) ? (int) $data['post_id'] : 0;
		$this->last_topic_id = isset($data['topic_id']) ? (int) $data['topic_id'] : 0;

		return $this->last_post_id;
	}

	public function get_last_post_id()
	{
		return $this->last_post_id;
	}

	public function get_last_topic_id()
	{
		return $this->last_topic_id;
	}

	public function send_form_webhook(array $form, array $submission_data)
	{
		if (empty($form['webhook_enabled']) || empty($form['webhook_url']))
		{
			return false;
		}

		$urls = preg_split('/[\r\n,]+/', $form['webhook_url']);
		$urls = array_filter(array_map('trim', $urls));
		if (empty($urls))
		{
			return false;
		}

		$replacements = isset($submission_data['replacements']) ? $submission_data['replacements'] : [];
		$raw_values = isset($submission_data['raw_values']) ? $submission_data['raw_values'] : [];
		$fields = isset($submission_data['fields']) ? $submission_data['fields'] : [];
		$post_id = isset($submission_data['post_id']) ? (int) $submission_data['post_id'] : 0;
		$topic_id = isset($submission_data['topic_id']) ? (int) $submission_data['topic_id'] : 0;

		$board_url = function_exists('generate_board_url') ? generate_board_url(true) : '';
		$post_url = ($post_id && $board_url) ? $board_url . '/viewtopic.php?p=' . $post_id . '#p' . $post_id : '';
		$topic_url = ($topic_id && $board_url) ? $board_url . '/viewtopic.php?t=' . $topic_id : '';
		$form_url = $board_url ? $board_url . '/forms/' . (!empty($form['form_slug']) ? $form['form_slug'] : $form['form_id']) : '';

		$current_time = time();
		$variables = array_merge($replacements, [
			'FORM_ID'    => (int) $form['form_id'],
			'FORM_NAME'  => $form['form_name'],
			'FORM_SLUG'  => !empty($form['form_slug']) ? $form['form_slug'] : '',
			'FORUM_ID'   => (int) $form['forum_id'],
			'POST_ID'    => $post_id,
			'TOPIC_ID'   => $topic_id,
			'POST_URL'   => $post_url,
			'TOPIC_URL'  => $topic_url,
			'FORM_URL'   => $form_url,
			'USERNAME'   => isset($replacements['USERNAME']) ? $replacements['USERNAME'] : (isset($this->user->data['username']) ? $this->user->data['username'] : 'Guest'),
			'USER_ID'    => isset($this->user->data['user_id']) ? (int) $this->user->data['user_id'] : 0,
			'USER_IP'    => isset($this->user->ip) ? $this->user->ip : '',
			'DATE'       => isset($replacements['DATE']) ? $replacements['DATE'] : date('M d, Y', $current_time),
			'TIME'       => isset($replacements['TIME']) ? $replacements['TIME'] : date('H:i', $current_time),
			'TIMESTAMP'  => $current_time,
			'ISO_DATE'   => gmdate('Y-m-d\TH:i:s\Z', $current_time),
			'SUMMARY'    => isset($replacements['SUMMARY']) ? $replacements['SUMMARY'] : '',
			'ALL_FIELDS' => isset($replacements['SUMMARY']) ? $replacements['SUMMARY'] : '',
		]);

		$fields_map = [];
		$discord_fields = [];
		foreach ($fields as $f)
		{
			if (in_array($f['field_type'], ['section_start', 'section_end', 'input_group_start', 'input_group_end']))
			{
				continue;
			}
			$fname = $f['field_name'];
			$val = isset($replacements[$fname]) ? (string)$replacements[$fname] : '';
			$label = !empty($f['field_label']) ? $f['field_label'] : $fname;
			$fields_map[$fname] = $val;

			$disp_val = ($val === '') ? '—' : $val;
			if (mb_strlen($disp_val) > 1024)
			{
				$disp_val = mb_substr($disp_val, 0, 1021) . '...';
			}
			if (mb_strlen($label) > 256)
			{
				$label = mb_substr($label, 0, 253) . '...';
			}

			$discord_fields[] = [
				'name'   => $label,
				'value'  => $disp_val,
				'inline' => false,
			];
		}

		$variables['RAW_DATA_JSON'] = json_encode($raw_values, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		$variables['FIELDS_JSON'] = json_encode($fields_map, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		$variables['DISCORD_FIELDS_JSON'] = json_encode($discord_fields, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

		$all_success = true;
		foreach ($urls as $url)
		{
			if (empty($url))
			{
				continue;
			}

			if (!empty(trim($form['webhook_template'])))
			{
				$payload = $this->render_webhook_template($form['webhook_template'], $variables, $fields, $raw_values);
			}
			else
			{
				$payload = $this->build_default_webhook_payload($url, $form, $variables, $fields_map, $discord_fields);
			}

			$result = $this->dispatch_webhook_request($url, $payload);
			if (!$result['success'])
			{
				$all_success = false;
				if ($this->log)
				{
					$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_BOOSKIT_FORM_WEBHOOK_FAILED', false, [$form['form_name'], $url, $result['error']]);
				}
			}
		}

		return $all_success;
	}

	public function build_default_webhook_payload($url, array $form, array $variables, array $fields_map, array $discord_fields)
	{
		$is_discord = (strpos($url, 'discord.com/api/webhooks') !== false || strpos($url, 'discordapp.com/api/webhooks') !== false);
		$username = isset($variables['USERNAME']) ? $variables['USERNAME'] : (isset($this->user->data['username']) ? $this->user->data['username'] : 'Guest');
		$user_id = isset($variables['USER_ID']) ? (int) $variables['USER_ID'] : (isset($this->user->data['user_id']) ? (int) $this->user->data['user_id'] : 0);
		$date = isset($variables['DATE']) ? $variables['DATE'] : date('M d, Y');
		$time = isset($variables['TIME']) ? $variables['TIME'] : date('H:i');
		$timestamp = isset($variables['TIMESTAMP']) ? $variables['TIMESTAMP'] : time();
		$iso_date = isset($variables['ISO_DATE']) ? $variables['ISO_DATE'] : gmdate('Y-m-d\TH:i:s\Z');
		$post_id = isset($variables['POST_ID']) ? (int) $variables['POST_ID'] : 0;
		$topic_id = isset($variables['TOPIC_ID']) ? (int) $variables['TOPIC_ID'] : 0;
		$post_url = isset($variables['POST_URL']) ? $variables['POST_URL'] : '';

		if ($is_discord)
		{
			$embed = [
				'title'       => 'New Submission: ' . (isset($form['form_name']) ? $form['form_name'] : ''),
				'description' => 'Submitted by **' . $username . '**',
				'color'       => 5793266,
				'fields'      => $discord_fields,
				'footer'      => [
					'text' => 'phpBB Custom Forms • ' . $date . ' ' . $time,
				],
				'timestamp'   => $iso_date,
			];
			if (!empty($post_url))
			{
				$embed['url'] = $post_url;
			}
			return json_encode([
				'username' => 'Custom Forms',
				'embeds'   => [$embed],
			], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		}

		return json_encode([
			'event'       => 'form_submission',
			'form_id'     => isset($form['form_id']) ? (int) $form['form_id'] : 0,
			'form_name'   => isset($form['form_name']) ? $form['form_name'] : '',
			'form_slug'   => !empty($form['form_slug']) ? $form['form_slug'] : '',
			'user_id'     => $user_id,
			'username'    => $username,
			'post_id'     => $post_id,
			'topic_id'    => $topic_id,
			'post_url'    => $post_url,
			'date'        => $date,
			'time'        => $time,
			'timestamp'   => $timestamp,
			'fields'      => $fields_map,
		], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	}

	public function render_webhook_template($template, array $variables, array $fields = [], array $raw_values = [])
	{
		$tpl = $template;

		// 1. Process Multi Input Group Loops if any: {{#groupname}} ... {{/groupname}}
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

		foreach ($input_groups as $group_name => $group_info)
		{
			$pattern = '/\{\{\s*#' . preg_quote($group_name, '/') . '\s*\}\}(.*?)\{\{\s*\/' . preg_quote($group_name, '/') . '\s*\}\}/s';
			if (!preg_match($pattern, $tpl))
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

			$tpl = preg_replace_callback($pattern, function($matches) use ($group_info, $num_rows, $raw_values) {
				$loop_content = $matches[1];
				$items = [];
				for ($i = 0; $i < $num_rows; $i++)
				{
					$temp = $loop_content;
					foreach ($group_info['fields'] as $gf)
					{
						$gf_name = $gf['field_name'];
						$val_at_row = isset($raw_values[$gf_name][$i]) ? $raw_values[$gf_name][$i] : '';
						if (is_array($val_at_row))
						{
							$val_text = implode(', ', array_filter($val_at_row));
						}
						else
						{
							$val_text = (string)$val_at_row;
						}
						$escaped_val = substr(json_encode($val_text, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 1, -1);
						$temp = preg_replace_callback('/\{\{\s*' . preg_quote($gf_name, '/') . '\s*\}\}/i', function() use ($escaped_val) {
							return $escaped_val;
						}, $temp);
					}
					$items[] = trim($temp);
				}
				return implode(",\n", $items);
			}, $tpl);
		}

		// 2. Process Field Checkbox/Array Loops: {{#fieldname}} ... {{/fieldname}}
		foreach ($fields as $field)
		{
			if (in_array($field['field_type'], ['section_start', 'section_end', 'input_group_start', 'input_group_end']))
			{
				continue;
			}
			$name = $field['field_name'];
			$pattern = '/\{\{\s*#' . preg_quote($name, '/') . '\s*\}\}(.*?)\{\{\s*\/' . preg_quote($name, '/') . '\s*\}\}/s';
			if (!preg_match($pattern, $tpl))
			{
				continue;
			}

			$selected_values = isset($raw_values[$name]) ? $raw_values[$name] : [];
			if (!is_array($selected_values))
			{
				$selected_values = [$selected_values];
			}

			$tpl = preg_replace_callback($pattern, function($matches) use ($selected_values) {
				$loop_content = $matches[1];
				$items = [];
				foreach ($selected_values as $v)
				{
					if ($v === '') continue;
					$v_str = (string) $v;
					$temp = $loop_content;
					$escaped_val = substr(json_encode($v_str, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 1, -1);
					$temp = preg_replace_callback('/\{\{\s*value\s*\}\}/i', function() use ($escaped_val) { return $escaped_val; }, $temp);
					$temp = preg_replace_callback('/\{\{\s*label\s*\}\}/i', function() use ($escaped_val) { return $escaped_val; }, $temp);
					$items[] = trim($temp);
				}
				return implode(",\n", $items);
			}, $tpl);
		}

		// 3. Process Global Fields Loop: {{#fields}} ... {{/fields}}
		$global_fields_pattern = '/\{\{\s*#fields\s*\}\}(.*?)\{\{\s*\/fields\s*\}\}/si';
		if (preg_match($global_fields_pattern, $tpl))
		{
			$all_fields_data = [];
			foreach ($fields as $field)
			{
				if (in_array($field['field_type'], ['section_start', 'section_end', 'input_group_start', 'input_group_end']))
				{
					continue;
				}
				$all_fields_data[] = [
					'name'  => $field['field_name'],
					'label' => !empty($field['field_label']) ? $field['field_label'] : $field['field_name'],
					'value' => isset($variables[$field['field_name']]) ? (string)$variables[$field['field_name']] : '',
				];
			}

			$tpl = preg_replace_callback($global_fields_pattern, function($matches) use ($all_fields_data) {
				$loop_content = $matches[1];
				$items = [];
				foreach ($all_fields_data as $data)
				{
					$temp = $loop_content;
					$name_escaped = substr(json_encode($data['name'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 1, -1);
					$label_escaped = substr(json_encode($data['label'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 1, -1);
					$val_escaped = substr(json_encode($data['value'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 1, -1);

					$temp = preg_replace_callback('/\{\{\s*name\s*\}\}/i', function() use ($name_escaped) { return $name_escaped; }, $temp);
					$temp = preg_replace_callback('/\{\{\s*label\s*\}\}/i', function() use ($label_escaped) { return $label_escaped; }, $temp);
					$temp = preg_replace_callback('/\{\{\s*value\s*\}\}/i', function() use ($val_escaped) { return $val_escaped; }, $temp);
					$items[] = trim($temp);
				}
				return implode(",\n", $items);
			}, $tpl);
		}

		// 4. Token-based replacement for all variables and raw JSON tags
		$raw_json_keys = ['DISCORD_FIELDS_JSON', 'FIELDS_JSON', 'DATA_JSON', 'RAW_DATA_JSON'];

		$result = '';
		$len = strlen($tpl);
		$in_string = false;
		$is_escaped = false;
		$i = 0;

		while ($i < $len)
		{
			$char = $tpl[$i];

			if ($in_string)
			{
				if ($char === '\\')
				{
					$is_escaped = !$is_escaped;
					$result .= $char;
					$i++;
					continue;
				}

				if ($char === '"' && !$is_escaped)
				{
					$in_string = false;
					$result .= $char;
					$i++;
					continue;
				}

				$is_escaped = false;

				if ($char === '{' && $i + 1 < $len && $tpl[$i + 1] === '{')
				{
					$close_pos = strpos($tpl, '}}', $i + 2);
					if ($close_pos !== false)
					{
						$tag = trim(substr($tpl, $i + 2, $close_pos - ($i + 2)));
						$i = $close_pos + 2;

						$actual_key = null;
						foreach ($variables as $vk => $vv)
						{
							if (strcasecmp($vk, $tag) === 0)
							{
								$actual_key = $vk;
								break;
							}
						}

						if ($actual_key !== null)
						{
							$val = $variables[$actual_key];
							if (in_array(strtoupper($tag), $raw_json_keys))
							{
								$result .= substr(json_encode((string)$val, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 1, -1);
							}
							else
							{
								if (is_array($val))
								{
									$val = json_encode($val, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
								}
								$encoded = json_encode((string)$val, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
								$result .= substr($encoded, 1, -1);
							}
						}
						else
						{
							$result .= '{{' . $tag . '}}';
						}
						continue;
					}
				}

				$result .= $char;
				$i++;
			}
			else
			{
				if ($char === '"')
				{
					foreach ($raw_json_keys as $rk)
					{
						$pattern = '/^"\s*\{\{\s*' . preg_quote($rk, '/') . '\s*\}\}\s*"/i';
						$sub = substr($tpl, $i);
						if (preg_match($pattern, $sub, $m))
						{
							$actual_key = null;
							foreach ($variables as $vk => $vv)
							{
								if (strcasecmp($vk, $rk) === 0)
								{
									$actual_key = $vk;
									break;
								}
							}
							if ($actual_key !== null)
							{
								$result .= is_string($variables[$actual_key]) ? $variables[$actual_key] : json_encode($variables[$actual_key], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
								$i += strlen($m[0]);
								continue 2;
							}
						}
					}

					$in_string = true;
					$is_escaped = false;
					$result .= $char;
					$i++;
					continue;
				}

				if ($char === '{' && $i + 1 < $len && $tpl[$i + 1] === '{')
				{
					$close_pos = strpos($tpl, '}}', $i + 2);
					if ($close_pos !== false)
					{
						$tag = trim(substr($tpl, $i + 2, $close_pos - ($i + 2)));
						$i = $close_pos + 2;

						$actual_key = null;
						foreach ($variables as $vk => $vv)
						{
							if (strcasecmp($vk, $tag) === 0)
							{
								$actual_key = $vk;
								break;
							}
						}

						if ($actual_key !== null)
						{
							$val = $variables[$actual_key];
							if (in_array(strtoupper($tag), $raw_json_keys))
							{
								$result .= is_string($val) ? $val : json_encode($val, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
							}
							else if (is_numeric($val) || is_bool($val) || is_null($val))
							{
								$result .= json_encode($val);
							}
							else if (is_array($val))
							{
								$result .= json_encode($val, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
							}
							else
							{
								$result .= json_encode((string)$val, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
							}
						}
						else
						{
							$result .= '{{' . $tag . '}}';
						}
						continue;
					}
				}

				$result .= $char;
				$i++;
			}
		}

		return $result;
	}

	public function dispatch_webhook_request($url, $payload)
	{
		if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL))
		{
			return ['success' => false, 'error' => 'Invalid webhook URL'];
		}

		if (function_exists('curl_init'))
		{
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
			curl_setopt($ch, CURLOPT_HTTPHEADER, [
				'Content-Type: application/json',
				'Content-Length: ' . strlen($payload),
				'User-Agent: phpBB-CustomForms-Webhook/1.0',
			]);
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

			$response = curl_exec($ch);
			$error = curl_error($ch);
			$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);

			if ($error)
			{
				return ['success' => false, 'error' => 'cURL Error: ' . $error, 'code' => $http_code];
			}

			if ($http_code >= 400)
			{
				return ['success' => false, 'error' => 'HTTP ' . $http_code . ': ' . $response, 'code' => $http_code];
			}

			return ['success' => true, 'response' => $response, 'code' => $http_code];
		}
		else
		{
			$context = stream_context_create([
				'http' => [
					'method'        => 'POST',
					'header'        => "Content-Type: application/json\r\nUser-Agent: phpBB-CustomForms-Webhook/1.0\r\n",
					'content'       => $payload,
					'timeout'       => 10,
					'ignore_errors' => true,
				],
			]);

			$response = @file_get_contents($url, false, $context);
			$status_line = isset($http_response_header[0]) ? $http_response_header[0] : '';
			preg_match('{HTTP\/\S*\s(\d{3})}', $status_line, $match);
			$http_code = isset($match[1]) ? (int) $match[1] : ($response !== false ? 200 : 0);

			if ($http_code >= 400 || $response === false)
			{
				return ['success' => false, 'error' => 'HTTP ' . $http_code . ': ' . $response, 'code' => $http_code];
			}

			return ['success' => true, 'response' => $response, 'code' => $http_code];
		}
	}
}
