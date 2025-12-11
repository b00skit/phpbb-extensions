<?php
namespace booskit\gtawoauth\auth\provider;

class gtaw extends \phpbb\auth\provider\oauth\service\base
{
    public function get_service_name()
    {
        return 'gtaw';
    }

    public function get_auth_endpoint()
    {
        return 'https://ucp.gta.world/oauth/authorize';
    }

    public function get_token_endpoint()
    {
        return 'https://ucp.gta.world/oauth/token';
    }

    public function get_user_info_endpoint()
    {
        return 'https://ucp.gta.world/api/user';
    }

    // Overriding this to ensure POST params are sent in body, not header
    protected function request_access_token($code)
    {
        $redirect_uri = $this->get_redirect_uri();
        $post_data = [
            'grant_type'    => 'authorization_code',
            'client_id'     => $this->config['auth_oauth_gtaw_key'],
            'client_secret' => $this->config['auth_oauth_gtaw_secret'],
            'redirect_uri'  => $redirect_uri,
            'code'          => $code,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->get_token_endpoint());
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); 
        $response = curl_exec($ch);
        curl_close($ch);

        $json = json_decode($response, true);
        return isset($json['access_token']) ? $json['access_token'] : null;
    }

    protected function request_user_details($access_token)
    {
        $headers = ["Authorization: Bearer " . $access_token];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->get_user_info_endpoint());
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }

    public function get_user_details($data)
    {
        if (!isset($data['user']) || !isset($data['user']['id'])) {
            return null;
        }

        $user = $data['user'];

        return array(
            'user_id'     => (string) $user['id'],
            'username'    => $user['username'],
            'email'       => '', 
            'new_account' => true,
        );
    }
    
    // Required by interface in some versions
    public function get_avatar($data)
    {
        return '';
    }
}