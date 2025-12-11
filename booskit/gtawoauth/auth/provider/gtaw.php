<?php
namespace booskit\gtawoauth\auth\provider;

class gtaw extends \phpbb\auth\provider\oauth\service\base
{
    private $custom_redirect_uri;

    public function set_redirect_uri($uri)
    {
        $this->custom_redirect_uri = $uri;
    }

    public function get_redirect_uri()
    {
        if ($this->custom_redirect_uri) {
            return $this->custom_redirect_uri;
        }

        return parent::get_redirect_uri();
    }

    public function perform_token_exchange($code)
    {
        return $this->request_access_token($code);
    }

    public function fetch_user_info($token)
    {
        return $this->request_user_details($token);
    }

    /**
     * {@inheritdoc}
     */
    public function get_service_name()
    {
        return 'gtaw';
    }

    /**
     * {@inheritdoc}
     */
    public function get_auth_endpoint()
    {
        return 'https://ucp.gta.world/oauth/authorize';
    }

    /**
     * {@inheritdoc}
     */
    public function get_token_endpoint()
    {
        return 'https://ucp.gta.world/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    public function get_user_info_endpoint()
    {
        return 'https://ucp.gta.world/api/user';
    }

    /**
     * {@inheritdoc}
     */
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

        // Add User-Agent as some APIs require it
        curl_setopt($ch, CURLOPT_USERAGENT, 'phpBB GTA:W OAuth Extension');

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return null;
        }

        $json = json_decode($response, true);
        return isset($json['access_token']) ? $json['access_token'] : null;
    }

    /**
     * {@inheritdoc}
     */
    protected function request_user_details($access_token)
    {
        $headers = [
            "Authorization: Bearer " . $access_token,
            "Accept: application/json"
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->get_user_info_endpoint());
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'phpBB GTA:W OAuth Extension');

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    /**
     * {@inheritdoc}
     */
    public function get_user_details($data)
    {
        // Handle cases where user data might be wrapped in 'user' key or at root
        $user = isset($data['user']) ? $data['user'] : $data;

        if (!isset($user['id'])) {
            return null;
        }

        return array(
            'user_id'     => (string) $user['id'],
            'username'    => isset($user['username']) ? $user['username'] : '',
            'email'       => isset($user['email']) ? $user['email'] : '',
            'new_account' => true,
        );
    }
    
    /**
     * {@inheritdoc}
     */
    public function get_avatar($data)
    {
        return '';
    }
}
