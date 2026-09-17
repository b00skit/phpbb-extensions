<?php
/**
 *
 * @package booskit/mention
 * @license MIT
 *
 */

namespace booskit\mention\controller;

use Symfony\Component\HttpFoundation\JsonResponse;

class mention
{
	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var \phpbb\request\request_interface */
	protected $request;

	/** @var \booskit\mention\service\mention_manager */
	protected $mention_manager;

	public function __construct(
		\phpbb\db\driver\driver_interface $db,
		\phpbb\user $user,
		\phpbb\auth\auth $auth,
		\phpbb\request\request_interface $request,
		\booskit\mention\service\mention_manager $mention_manager = null
	) {
		$this->db = $db;
		$this->user = $user;
		$this->auth = $auth;
		$this->request = $request;
		$this->mention_manager = $mention_manager;
	}

	/**
	 * Find users and groups matching search query for @mention autocomplete
	 *
	 * @return JsonResponse
	 */
	public function find_users()
	{
		// Basic permission check
		if (!$this->user->data['is_registered'] && !$this->auth->acl_get('u_viewprofile'))
		{
			return new JsonResponse([], 403);
		}

		$query = $this->request->variable('q', '', true);
		$query = trim($query);

		if ($query === '')
		{
			return new JsonResponse([]);
		}

		$clean_query = function_exists('utf8_clean_string') ? utf8_clean_string($query) : strtolower($query);
		$normal_types = defined('USER_NORMAL') && defined('USER_FOUNDER') ? [USER_NORMAL, USER_FOUNDER] : [0, 3];
		$current_user_id = (int) $this->user->data['user_id'];

		$groups_list = [];

		// Check if group mentions are enabled and find matching groups
		if ($this->mention_manager && $this->mention_manager->is_group_mention_enabled())
		{
			$all_groups = $this->mention_manager->get_all_mentionable_groups();
			foreach ($all_groups as $grp)
			{
				$gid = $grp['group_id'];
				// Only include group if user has permission to mention it
				if ($this->mention_manager->can_user_mention_group($current_user_id, $gid))
				{
					if (stripos($grp['group_clean'], $clean_query) === 0 || stripos($grp['group_clean'], $clean_query) !== false)
					{
						$groups_list[] = [
							'id'       => $gid,
							'username' => $grp['group_name'],
							'colour'   => $grp['group_colour'],
							'avatar'   => '',
							'is_group' => true,
						];
					}
				}
			}
		}

		// First try prefix match on users
		$like_expr = $this->db->sql_like_expression($clean_query . $this->db->get_any_char());
		$sql = 'SELECT user_id, username, user_colour, user_avatar, user_avatar_type, user_avatar_width, user_avatar_height
			FROM ' . USERS_TABLE . '
			WHERE username_clean ' . $like_expr . '
				AND ' . $this->db->sql_in_set('user_type', $normal_types) . '
			ORDER BY username_clean ASC';

		$result = $this->db->sql_query_limit($sql, 10);
		$users = [];
		$found_ids = [];

		while ($row = $this->db->sql_fetchrow($result))
		{
			$uid = (int) $row['user_id'];
			$found_ids[$uid] = true;
			$avatar_html = (function_exists('phpbb_get_user_avatar')) ? phpbb_get_user_avatar($row) : '';

			$users[] = [
				'id'       => $uid,
				'username' => $row['username'],
				'colour'   => !empty($row['user_colour']) ? '#' . $row['user_colour'] : '',
				'avatar'   => $avatar_html,
				'is_group' => false,
			];
		}
		$this->db->sql_freeresult($result);

		// If fewer than 3 user results and query has 2+ characters, try substring match
		if (count($users) < 3 && strlen($query) >= 2)
		{
			$like_sub = $this->db->sql_like_expression($this->db->get_any_char() . $clean_query . $this->db->get_any_char());
			$sql = 'SELECT user_id, username, user_colour, user_avatar, user_avatar_type, user_avatar_width, user_avatar_height
				FROM ' . USERS_TABLE . '
				WHERE username_clean ' . $like_sub . '
					AND ' . $this->db->sql_in_set('user_type', $normal_types) . '
				ORDER BY username_clean ASC';

			$result = $this->db->sql_query_limit($sql, 10 - count($users));
			while ($row = $this->db->sql_fetchrow($result))
			{
				$uid = (int) $row['user_id'];
				if (!isset($found_ids[$uid]))
				{
					$found_ids[$uid] = true;
					$avatar_html = (function_exists('phpbb_get_user_avatar')) ? phpbb_get_user_avatar($row) : '';
					$users[] = [
						'id'       => $uid,
						'username' => $row['username'],
						'colour'   => !empty($row['user_colour']) ? '#' . $row['user_colour'] : '',
						'avatar'   => $avatar_html,
						'is_group' => false,
					];
				}
			}
			$this->db->sql_freeresult($result);
		}

		// Return matching groups first, followed by users (up to 12 total items)
		$combined = array_merge($groups_list, $users);
		if (count($combined) > 12)
		{
			$combined = array_slice($combined, 0, 12);
		}

		return new JsonResponse($combined);
	}
}
