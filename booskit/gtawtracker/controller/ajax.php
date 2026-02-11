<?php
namespace booskit\gtawtracker\controller;

use Symfony\Component\HttpFoundation\JsonResponse;

class ajax
{
    protected $config;
    protected $request;
    protected $user;
    protected $template;
    protected $provider;
    protected $db;
    protected $table_prefix;
    protected $language;

    public function __construct($config, $request, $user, $template, $provider, $db, $table_prefix, $language)
    {
        $this->config = $config;
        $this->request = $request;
        $this->user = $user;
        $this->template = $template;
        $this->provider = $provider;
        $this->db = $db;
        $this->table_prefix = $table_prefix;
        $this->language = $language;
    }

    public function fetch_data($user_id)
    {
        $this->language->add_lang('common', 'booskit/gtawtracker');

        $faction_id = (int) $this->config['booskit_gtawtracker_faction_id'];
        if ($faction_id === 0) {
            return new JsonResponse(['error' => 'Faction ID not configured'], 400);
        }

        // Check Permissions
        if (!$this->check_permissions()) {
            return new JsonResponse(['error' => $this->language->lang('GTAW_TRACKER_NO_ACCESS')], 403);
        }

        // Get Viewer Token
        $token_data = $this->get_viewer_token();
        if (!$token_data) {
             return new JsonResponse(['error' => $this->language->lang('GTAW_TRACKER_NO_LINK')], 403);
        }

        $access_token = $token_data['access_token'];

        // Get Target User's GTAW ID
        $target_gtaw_id = $this->get_gtaw_id($user_id);
        if (!$target_gtaw_id) {
             return new JsonResponse(['error' => $this->language->lang('GTAW_TRACKER_NO_CHARACTER')], 404);
        }

        // Call Faction API
        $url = 'https://ucp.gta.world/api/faction/' . $faction_id;
        $response = $this->provider->perform_api_request($url, $access_token);

        if (!$response || !isset($response['data']['members'])) {
             return new JsonResponse(['error' => $this->language->lang('GTAW_TRACKER_ERROR')], 500);
        }

        $members = $response['data']['members'];
        $character_id = null;

        // Find match
        foreach ($members as $member) {
            if ((string)$member['user_id'] === (string)$target_gtaw_id) {
                $character_id = $member['character_id'];
                break;
            }
        }

        if (!$character_id) {
             return new JsonResponse(['error' => $this->language->lang('GTAW_TRACKER_NO_CHARACTER')], 404);
        }

        // Get Character Details (with Alts)
        $url = 'https://ucp.gta.world/api/faction/' . $faction_id . '/character/' . $character_id;
        $details = $this->provider->perform_api_request($url, $access_token);

        if (!$details || !isset($details['data'])) {
             return new JsonResponse(['error' => $this->language->lang('GTAW_TRACKER_ERROR')], 500);
        }

        $data = $details['data'];

        // Calculate Total ABAS
        $total_abas = (float) str_replace(',', '', $data['abas']);
        $characters = [];

        $characters[] = [
            'name' => $data['firstname'] . ' ' . $data['lastname'],
            'rank' => $data['rank_name'],
            'abas' => $data['abas'],
        ];

        if (!empty($data['alternative_characters'])) {
            foreach ($data['alternative_characters'] as $alt) {
                $total_abas += (float) str_replace(',', '', $alt['abas']);
                $characters[] = [
                    'name' => $alt['character_name'],
                    'rank' => $alt['rank_name'],
                    'abas' => $alt['abas'],
                ];
            }
        }

        return new JsonResponse([
            'characters' => $characters,
            'total_abas' => number_format($total_abas, 2),
        ]);
    }

    protected function check_permissions()
    {
        $view_groups = $this->config['booskit_gtawtracker_view_groups'];
        $allowed_groups = array_map('intval', explode(',', $view_groups));

        $user_id = $this->user->data['user_id'];

        // Check if user is in any allowed group
        $sql = 'SELECT group_id FROM ' . $this->table_prefix . 'user_group
                WHERE user_id = ' . (int) $user_id . '
                AND user_pending = 0
                AND ' . $this->db->sql_in_set('group_id', $allowed_groups);
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        if ($row) {
            return true;
        }

        if ($this->user->data['user_type'] == USER_FOUNDER) {
            return true;
        }

        return false;
    }

    protected function get_viewer_token()
    {
        $sql = 'SELECT access_token, refresh_token, expires_at
                FROM ' . $this->table_prefix . 'booskit_oauth_tokens
                WHERE user_id = ' . (int) $this->user->data['user_id'] . '
                AND provider = \'gtaw\'';
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        if (!$row) {
            return null;
        }

        // Refresh if expired
        if (time() >= $row['expires_at']) {
            $new_tokens = $this->provider->perform_token_refresh($row['refresh_token']);
            if ($new_tokens && isset($new_tokens['access_token'])) {
                // Update DB
                $access_token = $new_tokens['access_token'];
                $refresh_token = isset($new_tokens['refresh_token']) ? $new_tokens['refresh_token'] : $row['refresh_token'];
                $expires_in = isset($new_tokens['expires_in']) ? (int) $new_tokens['expires_in'] : 3600;
                $expires_at = time() + $expires_in;

                $sql_ary = [
                    'access_token'  => (string) $access_token,
                    'refresh_token' => (string) $refresh_token,
                    'expires_at'    => (int) $expires_at,
                ];

                $sql = 'UPDATE ' . $this->table_prefix . 'booskit_oauth_tokens
                        SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . '
                        WHERE user_id = ' . (int) $this->user->data['user_id'] . '
                        AND provider = \'gtaw\'';
                $this->db->sql_query($sql);

                return ['access_token' => $access_token];
            } else {
                return null; // Refresh failed
            }
        }

        return ['access_token' => $row['access_token']];
    }

    protected function get_gtaw_id($user_id)
    {
        $sql = 'SELECT oauth_provider_id FROM ' . $this->table_prefix . 'oauth_accounts
                WHERE user_id = ' . (int) $user_id . '
                AND provider = \'gtaw\'';
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        return ($row) ? $row['oauth_provider_id'] : null;
    }
}
