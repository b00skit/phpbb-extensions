<?php
/**
 *
 * Post As. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace booskit\postas\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Post As event listener
 */
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

	/**
	 * Constructor
	 */
	public function __construct(\phpbb\db\driver\driver_interface $db, \phpbb\user $user, \phpbb\template\template $template, \phpbb\request\request $request, $table_prefix, \phpbb\config\config $config)
	{
		$this->db = $db;
		$this->user = $user;
		$this->template = $template;
		$this->request = $request;
		$this->table_prefix = $table_prefix;
		$this->config = $config;
	}

	/**
	 * Get subscribed events
	 */
	public static function getSubscribedEvents()
	{
		return [
			'core.posting_modify_template_vars' => 'inject_postas_dropdown',
			'core.submit_post_end' => 'save_postas_selection',
			'core.viewtopic_modify_post_row' => 'modify_post_display',
		];
	}

	/**
	 * Inject Post As dropdown into posting form
	 */
	public function inject_postas_dropdown($event)
	{
		// Load language file
		$this->user->add_lang_ext('booskit/postas', 'postas');

		// Get mode and post_id from request
		$mode = $this->request->variable('mode', '');
		$post_id = $this->request->variable('p', 0);

		// Debug logging
		error_log('POSTAS DEBUG INJECT: mode=' . $mode . ', post_id=' . $post_id . ', user_id=' . $this->user->data['user_id']);

		// Check if we're editing a post
		if ($mode === 'edit' && $post_id > 0)
		{
			// Get the post's original poster to check ownership
			$post_author_id = $this->get_post_author_id($post_id);
			
			error_log('POSTAS DEBUG INJECT: post_author_id=' . $post_author_id);
			
			// Check if this post was made as an altchar
			$postas_data = $this->get_postas_data($post_id);
			
			error_log('POSTAS DEBUG INJECT: postas_data=' . print_r($postas_data, true));
			
			// Only process if post was made as altchar (has postas data)
			if (!empty($postas_data))
			{
				// Check if the current user is NOT the original poster
				if ($postas_data['user_id'] != $this->user->data['user_id'])
				{
					// Determine if checkbox should be checked (post is currently reverted)
					$is_reverted = isset($postas_data['reverted']) && $postas_data['reverted'] == 1;
					
					error_log('POSTAS DEBUG INJECT: Showing revert checkbox (different user editing altchar post), reverted=' . ($is_reverted ? 'true' : 'false'));
					// Show revert checkbox instead of dropdown
					$this->template->assign_vars([
						'S_POSTAS_REVERT_AVAILABLE' => true,
						'S_POSTAS_REVERT_CHECKED' => $is_reverted,
						'S_POSTAS_AVAILABLE' => false,
					]);
					return;
				}
				else
				{
					error_log('POSTAS DEBUG INJECT: Same user editing their own altchar post');
				}
			}
			else
			{
				// Post was NOT made as altchar
				// Check if current user is editing someone else's post
				if ($post_author_id > 0 && $post_author_id != $this->user->data['user_id'])
				{
					error_log('POSTAS DEBUG INJECT: User editing someone else\'s normal post - no dropdown');
					// Don't show anything (no dropdown, no checkbox)
					return;
				}
				error_log('POSTAS DEBUG INJECT: Post has no altchar data, continuing to normal dropdown');
			}
		}

		// Only show dropdown if user has altchars
		$altchars = $this->get_user_altchars($this->user->data['user_id']);

		if (empty($altchars))
		{
			error_log('POSTAS DEBUG INJECT: No altchars available for user');
			return;
		}

		error_log('POSTAS DEBUG INJECT: Showing dropdown with ' . count($altchars) . ' altchars');

		// Add "Yourself" option
		$this->template->assign_block_vars('postas_options', [
			'VALUE' => 0,
			'NAME' => $this->user->lang('POSTAS_YOURSELF'),
			'COLOR' => '',
			'RANK' => '',
		]);

		foreach ($altchars as $altchar)
		{
			$this->template->assign_block_vars('postas_options', [
				'VALUE' => $altchar['altchar_id'],
				'NAME' => $altchar['name'],
				'COLOR' => $altchar['color'] ?? '',
				'RANK' => $altchar['rank'],
			]);
		}

		$this->template->assign_vars([
			'S_POSTAS_AVAILABLE' => true,
		]);
	}

	public function inject_postas_dropdown_pm($event)
	{
		$this->user->add_lang_ext('booskit/postas', 'postas');
		$altchars = $this->get_user_altchars($this->user->data['user_id']);
		if (empty($altchars))
		{
			return;
		}

		$this->template->assign_block_vars('postas_options', [
			'VALUE' => 0,
			'NAME' => $this->user->lang('POSTAS_YOURSELF'),
			'COLOR' => '',
			'RANK' => '',
		]);

		foreach ($altchars as $altchar)
		{
			$this->template->assign_block_vars('postas_options', [
				'VALUE' => $altchar['altchar_id'],
				'NAME' => $altchar['name'],
				'COLOR' => $altchar['color'] ?? '',
				'RANK' => $altchar['rank'],
			]);
		}

		$template_ary = $event['template_ary'];
		$template_ary['S_POSTAS_AVAILABLE'] = true;
		$event['template_ary'] = $template_ary;
	}

	/**
	 * Save the Post As selection when post is submitted
	 */
	public function save_postas_selection($event)
	{
		$postas_altchar_id = $this->request->variable('postas_altchar_id', 0);
		$postas_revert = $this->request->variable('postas_revert_to_original', false);
		$mode = $this->request->variable('mode', '');

		$data = $event['data'];
		$post_id = $data['post_id'];

		// DEBUG
		error_log('POSTAS DEBUG: postas_altchar_id = ' . $postas_altchar_id);
		error_log('POSTAS DEBUG: postas_revert = ' . ($postas_revert ? 'true' : 'false'));
		error_log('POSTAS DEBUG: mode = ' . $mode);
		error_log('POSTAS DEBUG: post_id = ' . $post_id);

		// Handle revert checkbox (when someone else edits an altchar post)
		if ($mode === 'edit' && $post_id > 0)
		{
			// Check if this post has postas data
			$postas_data = $this->get_postas_data($post_id);
			
			if (!empty($postas_data) && $postas_data['user_id'] != $this->user->data['user_id'])
			{
				// Update the reverted flag based on checkbox state
				$reverted_value = $postas_revert ? 1 : 0;
				
				// Use sql_build_array for safer query construction
				$sql = 'UPDATE ' . $this->table_prefix . 'postas 
					SET ' . $this->db->sql_build_array('UPDATE', ['reverted' => $reverted_value]) . ' 
					WHERE post_id = ' . (int) $post_id;
				$this->db->sql_query($sql);
				error_log('POSTAS DEBUG: updated reverted flag to ' . $reverted_value);
				return;
			}
		}

		// Handle when user selects "Yourself" (value 0) while editing their own post
		if ($mode === 'edit' && $postas_altchar_id === 0 && $post_id > 0)
		{
			// Check if this post has postas data
			$postas_data = $this->get_postas_data($post_id);
			
			if (!empty($postas_data) && $postas_data['user_id'] == $this->user->data['user_id'])
			{
				// Delete the postas entry to revert to yourself
				$sql = 'DELETE FROM ' . $this->table_prefix . 'postas WHERE post_id = ' . (int) $post_id;
				$this->db->sql_query($sql);
				error_log('POSTAS DEBUG: reverted own post to yourself');
			}
			return;
		}

		if ($postas_altchar_id <= 0)
		{
			error_log('POSTAS DEBUG: altchar_id <= 0, returning');
			return;
		}

		// Verify this altchar belongs to the user
		$altchar = $this->get_altchar_by_id($postas_altchar_id, $this->user->data['user_id']);

		if (empty($altchar))
		{
			error_log('POSTAS DEBUG: altchar not found or does not belong to user');
			return;
		}

		error_log('POSTAS DEBUG: altchar found, saving to database');

		// Check if we're editing and need to update existing entry
		if ($mode === 'edit' && $post_id > 0)
		{
			// Check if entry already exists
			$existing = $this->get_postas_data($post_id);
			if (!empty($existing))
			{
				// Update existing entry
				$sql = 'UPDATE ' . $this->table_prefix . 'postas 
					SET ' . $this->db->sql_build_array('UPDATE', [
						'altchar_id' => (int) $postas_altchar_id,
						'reverted' => 0
					]) . ' 
					WHERE post_id = ' . (int) $post_id;
				$this->db->sql_query($sql);
				error_log('POSTAS DEBUG: updated existing postas entry');
				return;
			}
		}

		// Insert new entry - use sql_build_array for safety
		$sql = 'INSERT INTO ' . $this->table_prefix . 'postas ' . 
			$this->db->sql_build_array('INSERT', [
				'post_id' => (int) $post_id,
				'user_id' => (int) $this->user->data['user_id'],
				'altchar_id' => (int) $postas_altchar_id,
				'reverted' => 0
			]);
		$this->db->sql_query($sql);

		error_log('POSTAS DEBUG: saved to database successfully');
	}

	public function save_postas_selection_pm($event)
	{
		$postas_altchar_id = $this->request->variable('postas_altchar_id', 0);
		if ($postas_altchar_id <= 0)
		{
			return;
		}

		$data = $event['data'];
		$pm_id = isset($data['msg_id']) ? (int) $data['msg_id'] : 0;
		if ($pm_id <= 0)
		{
			return;
		}

		$altchar = $this->get_altchar_by_id($postas_altchar_id, $this->user->data['user_id']);
		if (empty($altchar))
		{
			return;
		}

		$sql = 'INSERT INTO ' . $this->table_prefix . 'postas ' .
			$this->db->sql_build_array('INSERT', [
				'post_id' => $pm_id,
				'user_id' => (int) $this->user->data['user_id'],
				'altchar_id' => (int) $postas_altchar_id,
			]);
		$this->db->sql_query($sql);
	}

	/**
	 * Modify post display to show altchar name color and rank
	 */
	public function modify_post_display($event)
	{
		$post_row = $event['post_row'];
		$post_id = $post_row['POST_ID'];
		// Get user_id from row - it may be lowercase or we need to get from event data
		$user_id = isset($post_row['POSTER_ID']) ? $post_row['POSTER_ID'] : (isset($post_row['user_id']) ? $post_row['user_id'] : 0);
		$real_username = isset($post_row['USERNAME']) ? $post_row['USERNAME'] : '';
		$u_post_author = isset($post_row['U_POST_AUTHOR']) ? $post_row['U_POST_AUTHOR'] : '';
		$actual_rank_title = isset($post_row['RANK_TITLE']) ? (string) $post_row['RANK_TITLE'] : '';

		error_log('POSTAS DEBUG DISPLAY: post_id=' . $post_id . ', user_id=' . $user_id);

		// Check if this post was made as an altchar
		$postas_data = $this->get_postas_data($post_id);

		if (empty($postas_data))
		{
			error_log('POSTAS DEBUG DISPLAY: no postas data found');
			return;
		}

		// Check if the post has been reverted - if so, don't show altchar
		if (isset($postas_data['reverted']) && $postas_data['reverted'] == 1)
		{
			error_log('POSTAS DEBUG DISPLAY: post has been reverted, showing original poster');
			return;
		}

		error_log('POSTAS DEBUG DISPLAY: found postas_data, altchar_id=' . $postas_data['altchar_id']);

		$altchar_id = $postas_data['altchar_id'];
		// Verify the user_id matches - use postas_data user_id if available
		$check_user_id = $postas_data['user_id'] ?? $user_id;
		if (empty($real_username) && $check_user_id > 0)
		{
			$real_username = $this->get_username_by_id($check_user_id);
		}
		$altchar = $this->get_altchar_by_id($altchar_id, $check_user_id);

		if (empty($altchar))
		{
			error_log('POSTAS DEBUG DISPLAY: altchar not found');
			return;
		}

		error_log('POSTAS DEBUG DISPLAY: altchar found, name=' . $altchar['name'] . ', rank=' . $altchar['rank']);

		// Get rank data including color - use rank_id like load_altchars.php
		$rank_id = isset($altchar['rank']) ? (int) $altchar['rank'] : 0;
		$rank_data = $this->get_rank_data_by_id($rank_id);

		error_log('POSTAS DEBUG DISPLAY: rank_id=' . $rank_id . ', rank_data=' . print_r($rank_data, true));

		// Modify post display
		$display_name = $altchar['name'];
		$display_color = (!empty($rank_data) && !empty($rank_data['color'])) ? $rank_data['color'] : '';
		$post_row['POST_AUTHOR'] = $display_name;
		if (!empty($display_color))
		{
			$post_row['POST_AUTHOR_COLOUR'] = $display_color;
			error_log('POSTAS DEBUG DISPLAY: applied color ' . $display_color);
		}
		$post_row['POST_AUTHOR_FULL'] = $this->build_postas_author_full($display_name, $display_color, $real_username, $u_post_author);

		if (!empty($rank_data['value']))
		{
			$post_row['RANK_IMG'] = $this->build_rank_image($rank_data);
			$post_row['RANK_IMG_SRC'] = $this->build_rank_image_src($rank_data);
			$post_row['RANK_TITLE'] = $rank_data['name'];
			error_log('POSTAS DEBUG DISPLAY: applied rank image');
		}

		if (!empty($actual_rank_title) && isset($post_row['POSTER_JOINED']) && $post_row['POSTER_JOINED'] !== '')
		{
			$post_row['POSTER_JOINED'] .= '<br /><strong>Actual Rank:</strong> <span style="font-weight: normal">' . $actual_rank_title . '</span>';
		}

		$event['post_row'] = $post_row;
		error_log('POSTAS DEBUG DISPLAY: finished');
	}

	public function modify_pm_display($event)
	{
		if (!isset($event['message_row']) || !isset($event['msg_data']))
		{
			return;
		}

		$message_row = $event['message_row'];
		$msg_data = $event['msg_data'];
		$author_id = isset($message_row['author_id']) ? (int) $message_row['author_id'] : 0;
		$real_username = isset($event['user_info']['username']) ? $event['user_info']['username'] : '';
		$u_message_author = isset($msg_data['U_MESSAGE_AUTHOR']) ? $msg_data['U_MESSAGE_AUTHOR'] : '';

		if ($author_id <= 0)
		{
			return;
		}

		$postas_data = $this->get_postas_data($message_row['msg_id'] ?? 0);
		if (empty($postas_data))
		{
			return;
		}

		$altchar_id = $postas_data['altchar_id'];
		$check_user_id = $postas_data['user_id'] ?? $author_id;
		$altchar = $this->get_altchar_by_id($altchar_id, $check_user_id);
		if (empty($altchar))
		{
			return;
		}

		$rank_id = isset($altchar['rank']) ? (int) $altchar['rank'] : 0;
		$rank_data = $this->get_rank_data_by_id($rank_id);
		$display_color = (!empty($rank_data) && !empty($rank_data['color'])) ? $rank_data['color'] : '';

		$msg_data['MESSAGE_AUTHOR_FULL'] = $this->build_postas_author_full($altchar['name'], $display_color, $real_username, $u_message_author);
		$event['msg_data'] = $msg_data;
	}

	/**
	 * Get user's alternative characters with rank colors
	 */
	private function get_user_altchars($user_id)
	{
		// Query altchars joined with ranks to get color - use rank_id lookup
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

	/**
	 * Get altchar by ID and verify it belongs to user
	 */
	private function get_altchar_by_id($altchar_id, $user_id)
	{
		$sql = 'SELECT * FROM ' . $this->table_prefix . 'princedog_altchars_table WHERE altchar_id = ' . (int) $altchar_id . ' AND user_id = ' . (int) $user_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);
		return $row;
	}

	private function get_username_by_id($user_id)
	{
		$user_id = (int) $user_id;
		if ($user_id <= 0)
		{
			return '';
		}

		$sql = 'SELECT username FROM ' . $this->table_prefix . 'users WHERE user_id = ' . $user_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);
		return (!empty($row) && isset($row['username'])) ? $row['username'] : '';
	}

	private function build_postas_author_full($alt_name, $alt_color, $real_username, $u_profile)
	{
		$alt_name = (string) $alt_name;
		$real_username = (string) $real_username;
		$alt_color = (string) $alt_color;
		$u_profile = (string) $u_profile;

		$alt_span = !empty($alt_color)
			? '<span class="username-coloured" style="color: ' . $alt_color . ';">' . $alt_name . '</span>'
			: '<span class="username">' . $alt_name . '</span>';

		// Check if we should show the original username in brackets
		$show_original = isset($this->config['postas_show_original']) ? (bool) $this->config['postas_show_original'] : true;
		
		$real_span = (!empty($real_username) && $show_original)
			? '<span class="postas-realname" style="color: #000;"> (' . $real_username . ')</span>'
			: '';

		$inner = $alt_span . $real_span;
		if (!empty($u_profile))
		{
			return '<a href="' . $u_profile . '" class="username">' . $inner . '</a>';
		}

		return $inner;
	}

	/**
	 * Get postas data for a post
	 */
	private function get_postas_data($post_id)
	{
		$sql = 'SELECT * FROM ' . $this->table_prefix . 'postas WHERE post_id = ' . (int) $post_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);
		return $row;
	}

	/**
	 * Get post author user_id
	 */
	private function get_post_author_id($post_id)
	{
		$sql = 'SELECT poster_id FROM ' . $this->table_prefix . 'posts WHERE post_id = ' . (int) $post_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);
		return $row ? (int) $row['poster_id'] : 0;
	}

	/**
	 * Get rank data by rank_id
	 */
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

	/**
	 * Build rank image HTML
	 */
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

	/**
	 * Build rank image src only
	 */
	private function build_rank_image_src($rank_data)
	{
		$value = $rank_data['value'];
		$source = $rank_data['source'];

		if (empty($value))
		{
			return '';
		}

		if ($source === 'url')
		{
			return $value;
		}
		else
		{
			$path = ($source === 'ranks') ? 'images/ranks/' : 'images/avatars/gallery/';
			return generate_board_url() . '/' . $path . $value;
		}
	}
}