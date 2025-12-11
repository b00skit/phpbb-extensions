<?php

namespace booskit\datacollector\controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class collector
{
	protected $config;
	protected $request;
	protected $db;
	protected $user;
	protected $helper;
	protected $auth;

	public function __construct(\phpbb\config\config $config, \phpbb\request\request $request, \phpbb\db\driver\driver_interface $db, \phpbb\user $user, \phpbb\controller\helper $helper, \phpbb\auth\auth $auth)
	{
		$this->config = $config;
		$this->request = $request;
		$this->db = $db;
		$this->user = $user;
		$this->helper = $helper;
		$this->auth = $auth;
	}

	public function send()
	{
		// Check for admin permissions
		// We use m_warn as a proxy for generic moderator access since 'm_' is not a valid permission option
		if (!$this->auth->acl_get('m_warn') && !$this->auth->acl_get('a_'))
		{
			return new Response('Access Denied. You do not have permission to access this page.', 403);
		}

		$type = $this->request->variable('type', '');
		$post_url = $this->config['booskit_datacollector_post_url'];

		if (empty($post_url))
		{
			return new Response('POST API Link is not configured.', 500);
		}

		$data = [];

		if ($type === 'forum')
		{
			$forum_id = (int) $this->config['booskit_datacollector_forum_id'];
			if ($forum_id > 0)
			{
				$data = $this->get_forum_threads($forum_id);
			}
			else
			{
			    return new Response('Forum ID is not configured.', 500);
			}
		}
		elseif ($type === 'groups')
		{
			$data = $this->get_all_groups();
		}
		else
		{
			$group_id = (int) $this->config['booskit_datacollector_group_id'];
			if ($group_id > 0)
			{
				$data = $this->get_group_users($group_id);
			}
             else
			{
			    return new Response('Group ID is not configured.', 500);
			}
		}

		$result = $this->send_data($post_url, $data);

		return new Response($result);
	}

	protected function get_group_users($group_id)
	{
		// users: user_id, username, groups, primary_group, is_group_leader
		// First get all users in the group
		$sql = 'SELECT u.user_id, u.username, u.group_id as primary_group, ug.group_leader
			FROM ' . USERS_TABLE . ' u
			JOIN ' . USER_GROUP_TABLE . ' ug ON (u.user_id = ug.user_id)
			WHERE ug.group_id = ' . (int) $group_id . '
			AND u.user_type IN (' . USER_NORMAL . ', ' . USER_FOUNDER . ')';

		$result = $this->db->sql_query($sql);
		$users = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$users[$row['user_id']] = [
				'user_id' => (int) $row['user_id'],
				'username' => $row['username'],
				'primary_group' => (int) $row['primary_group'],
				'is_group_leader' => (bool) $row['group_leader'],
				'groups' => [], // Will populate next
			];
		}
		$this->db->sql_freeresult($result);

		if (empty($users))
		{
			return [];
		}

		// Get all groups for these users
		$user_ids = array_keys($users);
		$sql = 'SELECT user_id, group_id
			FROM ' . USER_GROUP_TABLE . '
			WHERE ' . $this->db->sql_in_set('user_id', $user_ids);
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			if (isset($users[$row['user_id']]))
			{
				$users[$row['user_id']]['groups'][] = (int) $row['group_id'];
			}
		}
		$this->db->sql_freeresult($result);

		return array_values($users);
	}

	protected function get_forum_threads($forum_id)
	{
		// threads: title, author, date of creation
		$sql = 'SELECT topic_title, topic_poster, topic_first_poster_name, topic_time
			FROM ' . TOPICS_TABLE . '
			WHERE forum_id = ' . (int) $forum_id . '
			AND topic_status <> ' . ITEM_MOVED; // Exclude moved shadow topics

		$result = $this->db->sql_query($sql);
		$threads = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$threads[] = [
				'title' => $row['topic_title'],
				'author' => $row['topic_poster'], // User ID of author
				'author_name' => $row['topic_first_poster_name'],
				'date_of_creation' => (int) $row['topic_time'],
			];
		}
		$this->db->sql_freeresult($result);

		return $threads;
	}

	protected function get_all_groups()
	{
		// Fetch basic group info
		$sql = 'SELECT group_id, group_name
			FROM ' . GROUPS_TABLE . '
			ORDER BY group_id ASC';
		$result = $this->db->sql_query($sql);

		$groups = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$groups[$row['group_id']] = [
				'group_id' => (int) $row['group_id'],
				'group_name' => $row['group_name'],
				'member_count' => 0,
				'leaders' => [],
			];
		}
		$this->db->sql_freeresult($result);

		if (empty($groups))
		{
			return [];
		}

		$group_ids = array_keys($groups);

		// Count members
		// Exclude pending members and potentially inactive users if required,
		// but typically member count includes all approved members.
		$sql = 'SELECT group_id, COUNT(user_id) as total_members
			FROM ' . USER_GROUP_TABLE . '
			WHERE user_pending = 0
			AND ' . $this->db->sql_in_set('group_id', $group_ids) . '
			GROUP BY group_id';

		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			if (isset($groups[$row['group_id']]))
			{
				$groups[$row['group_id']]['member_count'] = (int) $row['total_members'];
			}
		}
		$this->db->sql_freeresult($result);

		// Get Leaders
		$sql = 'SELECT ug.group_id, ug.user_id, u.username
			FROM ' . USER_GROUP_TABLE . ' ug
			JOIN ' . USERS_TABLE . ' u ON (ug.user_id = u.user_id)
			WHERE ug.group_leader = 1
			AND ' . $this->db->sql_in_set('ug.group_id', $group_ids);

		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			if (isset($groups[$row['group_id']]))
			{
				$groups[$row['group_id']]['leaders'][] = [
					'user_id' => (int) $row['user_id'],
					'username' => $row['username'],
				];
			}
		}
		$this->db->sql_freeresult($result);

		return array_values($groups);
	}

	protected function send_data($url, $data)
	{
		$json_data = json_encode($data);

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Content-Type: application/json',
			'Content-Length: ' . strlen($json_data)
		]);

		$response = curl_exec($ch);
		$error = curl_error($ch);
		curl_close($ch);

		if ($error)
		{
			return "Error sending data: " . $error;
		}

		return "Data sent successfully. Response: " . $response;
	}
}
