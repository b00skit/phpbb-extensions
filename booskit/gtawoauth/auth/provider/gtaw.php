<?php
namespace booskit\gtawoauth\auth\provider;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class gtaw extends \phpbb\auth\provider\oauth\service\base
{
    /** @var \phpbb\config\config */
    protected $config;

    /** @var \phpbb\request\request_interface */
    protected $request;

    /** @var \phpbb\controller\helper */
    protected $helper;

    private $custom_redirect_uri;

    /**
     * Constructor.
     *
     * @param \phpbb\config\config                  $config     Config object
     * @param \phpbb\request\request_interface      $request    Request object
     * @param \phpbb\controller\helper              $helper     Controller helper object
     */
    public function __construct(\phpbb\config\config $config, \phpbb\request\request_interface $request, \phpbb\controller\helper $helper)
    {
        $this->config   = $config;
        $this->request  = $request;
        $this->helper   = $helper;
    }

    public function set_redirect_uri($uri)
    {
        $this->custom_redirect_uri = $uri;
        // Also update parent property if it exists or is used
        $this->redirect_uri = $uri;
    }

    public function get_redirect_uri()
    {
        if ($this->custom_redirect_uri) {
            return $this->custom_redirect_uri;
        }

        // Check if user has defined a base URL
        $base_url = isset($this->config['auth_oauth_gtaw_base_url']) ? trim($this->config['auth_oauth_gtaw_base_url'], '/') : '';

        if (!empty($base_url)) {
            $uri = $base_url . '/app.php/gtaw/callback';
        } else {
            // Fallback to auto-detection
            $uri = $this->helper->route('booskit_gtawoauth_callback', [], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        // Ensure parent property is synced
        $this->redirect_uri = $uri;

        return $uri;
    }

    public function perform_token_exchange($code)
    {
        return $this->request_access_token($code);
    }

    public function perform_token_refresh($refresh_token)
    {
        return $this->refresh_access_token($refresh_token);
    }

    public function fetch_user_info($token)
    {
        return $this->request_user_details($token);
    }

    public function perform_api_request($url, $token, $method = 'GET', $data = [])
    {
        $headers = [
            "Authorization: Bearer " . $token,
            "Accept: application/json"
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'phpBB GTA:W OAuth Extension');

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            }
        }

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code >= 400) {
            return null;
        }

        return json_decode($response, true);
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
        return $json;
    }

    protected function refresh_access_token($refresh_token)
    {
        $redirect_uri = $this->get_redirect_uri();
        $post_data = [
            'grant_type'    => 'refresh_token',
            'client_id'     => $this->config['auth_oauth_gtaw_key'],
            'client_secret' => $this->config['auth_oauth_gtaw_secret'],
            'refresh_token' => $refresh_token,
            'redirect_uri'  => $redirect_uri,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->get_token_endpoint());
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'phpBB GTA:W OAuth Extension');

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return null;
        }

        $json = json_decode($response, true);
        return $json;
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

    /**
     * {@inheritdoc}
     */
    public function get_service_credentials()
    {
        return [
            'key'    => $this->config['auth_oauth_gtaw_key'],
            'secret' => $this->config['auth_oauth_gtaw_secret'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function perform_auth_login()
    {
        $code = $this->request->variable('code', '');

        // Note: State validation is currently bypassed because the state manager service is unavailable
        // $state = $this->request->variable('state', '');
        // $this->state_manager->check_state($state);

        $token_data = $this->request_access_token($code);
        if (!$token_data || !isset($token_data['access_token'])) {
            throw new \phpbb\auth\provider\oauth\service\exception('AUTH_PROVIDER_OAUTH_ERROR_REQUEST');
        }

        $access_token = $token_data['access_token'];

        $user_info = $this->request_user_details($access_token);
        if (!$user_info) {
             throw new \phpbb\auth\provider\oauth\service\exception('AUTH_PROVIDER_OAUTH_ERROR_REQUEST');
        }

        $user_details = $this->get_user_details($user_info);
        if (!$user_details || !isset($user_details['user_id'])) {
             throw new \phpbb\auth\provider\oauth\service\exception('AUTH_PROVIDER_OAUTH_ERROR_REQUEST');
        }

        return $user_details['user_id'];
    }

    /**
     * {@inheritdoc}
     */
    public function perform_token_auth()
    {
        // Token auth typically implies we already have the token in the service provider
        // But since we manage state manually in this custom implementation, we might not support this
        // OR we can just return null, or throw exception.
        // For standard phpBB OAuth flow, perform_auth_login is the main entry point for code exchange.
        // perform_token_auth seems used when re-verifying or obtaining info if token is known.

        throw new \phpbb\auth\provider\oauth\service\exception('NOT_IMPLEMENTED');
    }
}
