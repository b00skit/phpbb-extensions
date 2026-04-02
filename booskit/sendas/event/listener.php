<?php
/**
 *
 * Send As. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace booskit\sendas\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var string */
	protected $table_prefix;

	/** @var \phpbb\config\config */
	protected $config;

	public function __construct(\phpbb\db\driver\driver_interface $db, \phpbb\user $user, \phpbb\template\template $template, \phpbb\request\request $request, $table_prefix, \phpbb\config\config $config)
	{
		$this->db = $db;
		$this->user = $user;
		$this->template = $template;
		$this->request = $request;
		$this->table_prefix = $table_prefix;
		$this->config = $config;
	}

	public static function getSubscribedEvents()
	{
		return [
			'core.ucp_pm_compose_template' => 'inject_sendas_dropdown',
			'core.submit_pm_after' => 'save_sendas_selection',
			'core.ucp_pm_view_message' => 'modify_pm_display',
		];
	}

	public function inject_sendas_dropdown($event)
	{
		$this->user->add_lang_ext('booskit/sendas', 'sendas');

		$altchars = $this->get_user_altchars($this->user->data['user_id']);
		if (empty($altchars))
		{
			return;
		}

		$this->template->assign_block_vars('sendas_options', [
			'VALUE' => 0,
			'NAME' => $this->user->lang('SENDAS_YOURSELF'),
			'COLOR' => '',
			'RANK' => '',
		]);

		foreach ($altchars as $altchar)
		{
			$this->template->assign_block_vars('sendas_options', [
				'VALUE' => $altchar['altchar_id'],
				'NAME' => $altchar['name'],
				'COLOR' => $altchar['color'] ?? '',
				'RANK' => $altchar['rank'],
			]);
		}

		$template_ary = $event['template_ary'];
		$template_ary['S_SENDAS_AVAILABLE'] = true;
		$event['template_ary'] = $template_ary;
	}

	public function save_sendas_selection($event)
	{
		$sendas_altchar_id = $this->request->variable('sendas_altchar_id', 0);
		if ($sendas_altchar_id <= 0)
		{
			return;
		}

		$data = $event['data'];
		$pm_id = isset($data['msg_id']) ? (int) $data['msg_id'] : 0;
		if ($pm_id <= 0)
		{
			return;
		}

		$altchar = $this->get_altchar_by_id($sendas_altchar_id, $this->user->data['user_id']);
		if (empty($altchar))
		{
			return;
		}

		$sql = 'INSERT INTO ' . $this->table_prefix . 'booskit_sendas ' .
			$this->db->sql_build_array('INSERT', [
				'msg_id' => $pm_id,
				'user_id' => (int) $this->user->data['user_id'],
				'altchar_id' => (int) $sendas_altchar_id,
			]);
		$this->db->sql_query($sql);
	}

	public function modify_pm_display($event)
	{
		if (!isset($event['message_row']) || !isset($event['msg_data']))
		{
			return;
		}

		$message_row = $event['message_row'];
		$msg_data = $event['msg_data'];

		$msg_id = isset($message_row['msg_id']) ? (int) $message_row['msg_id'] : 0;
		$author_id = isset($message_row['author_id']) ? (int) $message_row['author_id'] : 0;
		$real_username = isset($event['user_info']['username']) ? (string) $event['user_info']['username'] : '';
		$u_message_author = isset($msg_data['U_MESSAGE_AUTHOR']) ? (string) $msg_data['U_MESSAGE_AUTHOR'] : '';
		$actual_rank_title = isset($msg_data['RANK_TITLE']) ? (string) $msg_data['RANK_TITLE'] : '';

		if ($msg_id <= 0 || $author_id <= 0)
		{
			return;
		}

		$sendas_data = $this->get_sendas_data($msg_id);
		if (empty($sendas_data))
		{
			return;
		}

		$altchar_id = (int) $sendas_data['altchar_id'];
		$check_user_id = isset($sendas_data['user_id']) ? (int) $sendas_data['user_id'] : $author_id;

		$altchar = $this->get_altchar_by_id($altchar_id, $check_user_id);
		if (empty($altchar))
		{
			return;
		}

		$rank_id = isset($altchar['rank']) ? (int) $altchar['rank'] : 0;
		$rank_data = $this->get_rank_data_by_id($rank_id);
		$display_color = (!empty($rank_data) && !empty($rank_data['color'])) ? $rank_data['color'] : '';

		$msg_data['MESSAGE_AUTHOR_FULL'] = $this->build_sendas_author_full($altchar['name'], $display_color, $real_username, $u_message_author);
		$msg_data['MESSAGE_AUTHOR'] = $altchar['name'];
		if (!empty($display_color))
		{
			$msg_data['MESSAGE_AUTHOR_COLOUR'] = $display_color;
		}

		if (!empty($rank_data) && !empty($rank_data['value']))
		{
			$msg_data['RANK_IMG'] = $this->build_rank_image($rank_data);
			$msg_data['RANK_TITLE'] = $rank_data['name'];
		}

		if (!empty($actual_rank_title) && isset($msg_data['AUTHOR_JOINED']) && $msg_data['AUTHOR_JOINED'] !== '')
		{
			$msg_data['AUTHOR_JOINED'] .= '<br /><strong>Actual Rank:</strong> <span style="font-weight: normal">' . $actual_rank_title . '</span>';
		}

		$event['msg_data'] = $msg_data;
	}

	private function get_user_altchars($user_id)
	{
		$sql = 'SELECT a.*, r.color, r.name as rank_name, r.value as rank_value, r.source as rank_source
			FROM ' . $this->table_prefix . 'princedog_altchars_table a
			LEFT JOIN ' . $this->table_prefix . 'princedog_altchars_ranks r ON CAST(a.rank AS UNSIGNED) = r.rank_id
			WHERE a.user_id = ' . (int) $user_id . '
			ORDER BY a.slot ASC';

		$result = $this->db->sql_query($sql);
		$altchars = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$altchars[] = $row;
		}
		$this->db->sql_freeresult($result);
		return $altchars;
	}

	private function get_altchar_by_id($altchar_id, $user_id)
	{
		$sql = 'SELECT * FROM ' . $this->table_prefix . 'princedog_altchars_table
			WHERE altchar_id = ' . (int) $altchar_id . '
				AND user_id = ' . (int) $user_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);
		return $row;
	}

	private function get_rank_data_by_id($rank_id)
	{
		$rank_id = (int) $rank_id;
		if ($rank_id <= 0)
		{
			return null;
		}

		$sql = 'SELECT * FROM ' . $this->table_prefix . 'princedog_altchars_ranks WHERE rank_id = ' . $rank_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);
		return $row;
	}

	private function build_rank_image($rank_data)
	{
		$value = $rank_data['value'];
		$source = $rank_data['source'];

		if (empty($value))
		{
			return '';
		}

		if ($source === 'url')
		{
			$src = $value;
		}
		else
		{
			$path = ($source === 'ranks') ? 'images/ranks/' : 'images/avatars/gallery/';
			$src = generate_board_url() . '/' . $path . $value;
		}

		return '<img src="' . $src . '" alt="" title="' . $rank_data['name'] . '" />';
	}

	private function get_sendas_data($msg_id)
	{
		$sql = 'SELECT * FROM ' . $this->table_prefix . 'booskit_sendas WHERE msg_id = ' . (int) $msg_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);
		return $row;
	}

	private function build_sendas_author_full($alt_name, $alt_color, $real_username, $u_profile)
	{
		$alt_name = (string) $alt_name;
		$real_username = (string) $real_username;
		$alt_color = (string) $alt_color;
		$u_profile = (string) $u_profile;

		$alt_span = !empty($alt_color)
			? '<span class="username-coloured" style="color: ' . $alt_color . ';">' . $alt_name . '</span>'
			: '<span class="username">' . $alt_name . '</span>';

		// Check if we should show the original username in brackets
		$show_original = isset($this->config['sendas_show_original']) ? (bool) $this->config['sendas_show_original'] : true;

		$real_span = (!empty($real_username) && $show_original)
			? '<span class="sendas-realname" style="color: #000;"> (' . $real_username . ')</span>'
			: '';

		$inner = $alt_span . $real_span;
		if (!empty($u_profile))
		{
			return '<a href="' . $u_profile . '" class="username">' . $inner . '</a>';
		}

		return $inner;
	}
}
