<?php
namespace booskit\phpbbapi\controller;

use phpbb\config\config;
use phpbb\db\driver\driver_interface;
use phpbb\request\request_interface;
use phpbb\user;

class api
{
    /** @var config */
    protected $config;

    /** @var driver_interface */
    protected $db;

    /** @var request_interface */
    protected $request;

    /** @var user */
    protected $user;

    /** @var string */
    protected $table_prefix;

    public function __construct(config $config, driver_interface $db, request_interface $request, user $user, $table_prefix)
    {
        $this->config = $config;
        $this->db = $db;
        $this->request = $request;
        $this->user = $user;
        $this->table_prefix = $table_prefix;
    }

    /* -------------------- Utility -------------------- */

    protected function get_api_key()
    {
        $key = (string) $this->request->header('X-API-Key');
        if (!$key) {
            $key = (string) $this->request->variable('key', '');
        }
        return $key;
    }

    protected function require_key()
    {
        $configured = trim((string) $this->config['booskit_phpbbapi_key']);
        $provided = trim((string) $this->get_api_key());

        if ($configured === '' || $provided === '' || !hash_equals($configured, $provided)) {
            return $this->json([ 'error' => 'Forbidden: invalid or missing API key' ], 403);
        }
        return null; // ok
    }

    protected function json($data, $status = 200)
    {
        // Minimal JSON response for phpBB controller methods
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
        exit;
    }

    protected function table($name)
    {
        return $this->table_prefix . $name;
    }

    /* -------------------- Endpoints -------------------- */

    // GET /groups : list groups (no members)
    public function groups()
    {
        if ($resp = $this->require_key()) { return $resp; }

        $sql = 'SELECT group_id, group_name, group_type, group_desc, group_desc_uid, group_desc_options, group_desc_bitfield
                FROM ' . $this->table('groups') . '
                ORDER BY group_name ASC';
        $result = $this->db->sql_query($sql);

        $groups = [];
        while ($row = $this->db->sql_fetchrow($result)) {
            // Convert description using generate_text_for_display? Keep simple (raw) to avoid bbcode dependencies.
            $groups[] = [
                'id'    => (int) $row['group_id'],
                'name'  => (string) $row['group_name'],
                'type'  => (int) $row['group_type'],
                'desc'  => (string) $row['group_desc'],
            ];
        }
        $this->db->sql_freeresult($result);

        return $this->json([ 'groups' => $groups ]);
    }

    // GET /group/{id} : group with members & leaders
    public function group($id)
    {
        if ($resp = $this->require_key()) { return $resp; }
        $group_id = (int) $id;

        // Basic group info
        $sql = 'SELECT group_id, group_name, group_type, group_desc
                FROM ' . $this->table('groups') . '
                WHERE group_id = ' . $group_id;
        $result = $this->db->sql_query($sql);
        $group = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        if (!$group) {
            return $this->json([ 'error' => 'Group not found' ], 404);
        }

        // Members
        $sql = 'SELECT ug.user_id, ug.group_leader, u.username, u.user_email
                FROM ' . $this->table('user_group') . ' ug
                JOIN ' . $this->table('users') . ' u ON u.user_id = ug.user_id
                WHERE ug.group_id = ' . $group_id . ' AND ug.user_pending = 0
                ORDER BY ug.group_leader DESC, u.username_clean ASC';
        $result = $this->db->sql_query($sql);

        $leaders = [];
        $members = [];
        while ($row = $this->db->sql_fetchrow($result)) {
            $user = [
                'id'       => (int) $row['user_id'],
                'username' => (string) $row['username'],
                'email'    => (string) $row['user_email'],
            ];
            if ((int) $row['group_leader'] === 1) {
                $leaders[] = $user;
            } else {
                $members[] = $user;
            }
        }
        $this->db->sql_freeresult($result);

        return $this->json([
            'group' => [
                'id'      => (int) $group['group_id'],
                'name'    => (string) $group['group_name'],
                'type'    => (int) $group['group_type'],
                'desc'    => (string) $group['group_desc'],
                'leaders' => $leaders,
                'members' => $members,
                'counts'  => [
                    'leaders' => count($leaders),
                    'members' => count($members),
                    'total'   => count($leaders) + count($members),
                ],
            ],
        ]);
    }

    // GET /user/{id} : user with groups
    public function user($id)
    {
        if ($resp = $this->require_key()) { return $resp; }
        $user_id = (int) $id;

        // User basic info
        $sql = 'SELECT user_id, username, user_email
                FROM ' . $this->table('users') . '
                WHERE user_id = ' . $user_id . ' AND user_type <> 2'; // exclude anonymous
        $result = $this->db->sql_query($sql);
        $user = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        if (!$user) {
            return $this->json([ 'error' => 'User not found' ], 404);
        }

        // Groups for user
        $sql = 'SELECT g.group_id, g.group_name, ug.group_leader
                FROM ' . $this->table('user_group') . ' ug
                JOIN ' . $this->table('groups') . ' g ON g.group_id = ug.group_id
                WHERE ug.user_id = ' . $user_id . ' AND ug.user_pending = 0
                ORDER BY ug.group_leader DESC, g.group_name ASC';
        $result = $this->db->sql_query($sql);
        $groups = [];
        while ($row = $this->db->sql_fetchrow($result)) {
            $groups[] = [
                'id'     => (int) $row['group_id'],
                'name'   => (string) $row['group_name'],
                'leader' => (int) $row['group_leader'] === 1,
            ];
        }
        $this->db->sql_freeresult($result);

        return $this->json([
            'user' => [
                'id'       => (int) $user['user_id'],
                'username' => (string) $user['username'],
                'email'    => (string) $user['user_email'],
                'groups'   => $groups,
                'counts'   => [ 'groups' => count($groups) ],
            ],
        ]);
    }

    // GET /user/username/{username} : user with groups (lookup by username)
    public function user_by_username($username)
    {
        if ($resp = $this->require_key()) { return $resp; }

        $username_clean = $this->db->sql_escape(utf8_clean_string($username));

        $sql = 'SELECT user_id
                FROM ' . $this->table('users') . "
                WHERE username_clean = '" . $username_clean . "' AND user_type <> 2"; // exclude anonymous
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        if (!$row) {
            return $this->json([ 'error' => 'User not found' ], 404);
        }

        return $this->user((int) $row['user_id']);
    }

    // GET /forum/{id} : forum with topics & authors
    public function forum($id)
    {
        if ($resp = $this->require_key()) { return $resp; }
        $forum_id = (int) $id;

        $allowed = array_filter(array_map('intval', explode(',', (string) $this->config['booskit_phpbbapi_allowed_forum_ids'])));
        if (!empty($allowed) && !in_array($forum_id, $allowed, true)) {
            return $this->json([ 'error' => 'Forbidden: forum not allowed' ], 403);
        }

        // Forum basic info
        $sql = 'SELECT forum_id, forum_name, forum_desc
                FROM ' . $this->table('forums') . '
                WHERE forum_id = ' . $forum_id;
        $result = $this->db->sql_query($sql);
        $forum = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        if (!$forum) {
            return $this->json([ 'error' => 'Forum not found' ], 404);
        }

        // Topics in forum (limit optional via ?limit=)
        $limit = max(0, (int) $this->request->variable('limit', 50));
        if ($limit === 0) { $limit = 50; }

        $sql = 'SELECT t.topic_id, t.topic_title, t.topic_poster, u.username AS author
                FROM ' . $this->table('topics') . ' t
                LEFT JOIN ' . $this->table('users') . ' u ON u.user_id = t.topic_poster
                WHERE t.forum_id = ' . $forum_id . ' AND t.topic_status <> 2
                ORDER BY t.topic_time DESC
                LIMIT ' . (int) $limit;
        $result = $this->db->sql_query($sql);

        $topics = [];
        while ($row = $this->db->sql_fetchrow($result)) {
            $topics[] = [
                'id'        => (int) $row['topic_id'],
                'title'     => (string) $row['topic_title'],
                'author_id' => (int) $row['topic_poster'],
                'author'    => (string) $row['author'],
            ];
        }
        $this->db->sql_freeresult($result);

        return $this->json([
            'forum' => [
                'id'     => (int) $forum['forum_id'],
                'name'   => (string) $forum['forum_name'],
                'desc'   => (string) $forum['forum_desc'],
                'topics' => $topics,
                'counts' => [ 'topics' => count($topics) ],
            ],
        ]);
    }
}