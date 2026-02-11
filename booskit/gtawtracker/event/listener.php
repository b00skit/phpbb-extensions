<?php
namespace booskit\gtawtracker\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
    protected $config;
    protected $template;
    protected $user;
    protected $helper;
    protected $request;
    protected $db;
    protected $table_prefix;

    public function __construct($config, $template, $user, $helper, $request, $db, $table_prefix)
    {
        $this->config = $config;
        $this->template = $template;
        $this->user = $user;
        $this->helper = $helper;
        $this->request = $request;
        $this->db = $db;
        $this->table_prefix = $table_prefix;
    }

    static public function getSubscribedEvents()
    {
        return [
            'core.user_setup' => 'load_language_on_setup',
            'core.memberlist_view_profile' => 'view_profile',
        ];
    }

    public function load_language_on_setup($event)
    {
        $lang_set_ext = $event['lang_set_ext'];
        $lang_set_ext[] = [
            'ext_name' => 'booskit/gtawtracker',
            'lang_set' => 'common',
        ];
        $event['lang_set_ext'] = $lang_set_ext;
    }

    public function view_profile($event)
    {
        $faction_id = (int) $this->config['booskit_gtawtracker_faction_id'];
        if ($faction_id === 0) {
            return;
        }

        // Check view permissions
        $view_groups = $this->config['booskit_gtawtracker_view_groups'];
        $allowed_groups = array_map('intval', explode(',', $view_groups));

        // Get user's groups
        // We can use $this->user->data['group_id'] for primary, but usually we need to check all groups.
        // Or using group_memberships function if available, but that requires includes.
        // Assuming user object has group check logic or we query DB.
        // Simpler: Use a helper function or query.

        $user_id = $this->user->data['user_id'];

        // Check if user is in any allowed group
        $sql = 'SELECT group_id FROM ' . $this->table_prefix . 'user_group
                WHERE user_id = ' . (int) $user_id . '
                AND user_pending = 0
                AND ' . $this->db->sql_in_set('group_id', $allowed_groups);
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        $is_allowed = ($row) ? true : false;

        // Admin always allowed? Maybe.
        if ($this->user->data['user_type'] == USER_FOUNDER) {
            $is_allowed = true;
        }

        if (!$is_allowed) {
            return;
        }

        $member = $event['member'];
        $target_user_id = $member['user_id'];

        $this->template->assign_vars([
            'S_GTAW_TRACKER_ENABLED' => true,
            'U_GTAW_TRACKER_AJAX'    => $this->helper->route('booskit_gtawtracker_ajax', ['user_id' => $target_user_id]),
            'GTAW_TRACKER_TARGET_ID' => $target_user_id,
        ]);
    }
}
