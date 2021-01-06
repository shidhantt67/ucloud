<?php

/**
 * APIV2 class. See the API docs within the admin area for usage.
 */
class apiv2
{
    /**
     * Property: method
     * The HTTP method this request was made in, either GET, POST, PUT or DELETE
     */
    protected $method = '';

    /**
     * Property: endpoint
     * The Model requested in the URI. eg: /file
     */
    protected $endpoint = '';
    
    /**
     * Property: action
     * The Action requested in the URI. eg: /file/upload
     */
    protected $action = '';

    /**
     * Property: args
     * Any additional URI components after the endpoint and verb have been removed
     */
    protected $args = Array();

    /**
     * Property: file
     * Stores the input of the PUT request
     */
    protected $file = Null;
    
    /**
     * Property: apiUserId
     * Stored the current user id
     */
    private $apiUserId = null;

    /**
     * Constructor: __construct
     * Allow for CORS, assemble and pre-process the data
     */
    public function __construct($request)
    {
        header("Access-Control-Allow-Orgin: *");
        header("Access-Control-Allow-Methods: *");
        header("Content-Type: application/json");
        
        // setup log handling
        log::setContext('apiv2');
        
        // clear old tokens
        $this->_clearExpiredTokens();

        $this->args = explode('/', rtrim($request, '/'));
        
        // if we don't have 2nd argument, set it to 'index'
        if(COUNT($this->args) == 1)
        {
            $this->args[] = 'index';
        }

        $this->endpoint = array_shift($this->args);
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->action = array_shift($this->args);
        if($this->method == 'POST' && array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER))
        {
            if($_SERVER['HTTP_X_HTTP_METHOD'] == 'DELETE')
            {
                $this->method = 'DELETE';
            }
            else if($_SERVER['HTTP_X_HTTP_METHOD'] == 'PUT')
            {
                $this->method = 'PUT';
            }
            else
            {
                throw new Exception("Unexpected Header");
            }
        }

        switch($this->method)
        {
            case 'DELETE':
            case 'POST':
                $this->request = $this->_cleanInputs($_POST);
                break;
            case 'GET':
                $this->request = $this->_cleanInputs($_GET);
                break;
            case 'PUT':
                $this->request = $this->_cleanInputs($_GET);
                $this->file = file_get_contents("php://input");
                break;
            default:
                $this->_response('Invalid Method', 405);
                break;
        }
    }

    public function processAPI()
    {
        if(method_exists($this, $this->action))
        {
            $rs = $this->_response($this->{$this->action}($this->args));
            
            // log
            $logParams = $this->request;
            if(isset($logParams['password']))
            {
                unset($logParams['password']);
            }
            log::info('Request: ['.$this->endpoint.'/'.$this->action.'] ['.stats::getIP().'] '.json_encode($logParams, true));
            log::info('Response: ['.$this->endpoint.'/'.$this->action.'] '.$rs);
            
            return $rs;
        }

        return $this->_response("No method found within endpoint (".$this->endpoint."): ".$this->action, 404);
    }

    private function _response($data, $status = 200)
    {
        header("HTTP/1.1 " . $status . " " . $this->_requestStatus($status));

        // append date time
        if(!is_array($data))
        {
            $data = array($data);
        }
        $data['_status'] = $status==200?'success':'error';
        $data['_datetime'] = coreFunctions::sqlDateTime();
        return json_encode($data);
    }

    private function _cleanInputs($data)
    {
        $clean_input = Array();
        if(is_array($data))
        {
            foreach($data as $k => $v)
            {
                $clean_input[$k] = $this->_cleanInputs($v);
            }
        }
        else
        {
            $clean_input = trim(strip_tags($data));
        }

        return $clean_input;
    }

    private function _requestStatus($code)
    {
        $status = array(
            200 => 'OK',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            500 => 'Internal Server Error',
        );
        return ($status[$code]) ? $status[$code] : $status[500];
    }
    
    public function _generateAccessToken($length = 128)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        
        return $randomString;
    }
    
    public function _clearAllAccessTokensByUserId($userId, $accessToken = null)
    {
        $db = Database::getDatabase();
        $sQL = 'DELETE FROM apiv2_access_token WHERE user_id = :user_id';
        if($accessToken != null)
        {
            $sQL .= ' AND access_token = :access_token';
        }
        
        $params = array();
        $params['user_id'] = $userId;
        if($accessToken != null)
        {
            $params['access_token'] = $accessToken;
        }
        $rs = $db->query($sQL, $params);
    }
    
    public function _validateAccessToken($accessToken, $accountId = null)
    {
        $db = Database::getDatabase();
        $sQL = 'SELECT user_id FROM apiv2_access_token WHERE access_token = :access_token ';
        $replacements = array();
        $replacements['access_token'] = $accessToken;
        if($accountId !== null) {
            $sQL .= 'AND user_id = :user_id ';
            $replacements['user_id'] = $accountId;
        }
        $sQL .= 'LIMIT 1';
        $foundUserId = (int)$db->getValue($sQL, $replacements);
        if($foundUserId >= 1)
        {
            $this->apiUserId = $foundUserId;
            $db->query('UPDATE apiv2_access_token SET date_last_used = NOW() WHERE access_token = :access_token LIMIT 1', array('access_token' => $accessToken));
            return true;
        }
        
        return false;
    }
    
    public function _validateAdminOnly($accessToken)
    {
        $db = Database::getDatabase();
        $sQL = 'SELECT COUNT(id) AS total FROM users WHERE id = :id AND level_id = 20 LIMIT 1';
        $replacements = array('id' => $this->apiUserId);
        $found = (int)$db->getValue($sQL, $replacements);
        if($found >= 1)
        {
            return true;
        }
        
        return false;
    }
    
    public function _clearExpiredTokens()
    {
        $db = Database::getDatabase();
        $db->query('DELETE FROM apiv2_access_token WHERE date_last_used < (NOW() - INTERVAL 1 HOUR)');
        
        return true;
    }

    public static function init($requestUrl, $origin)
    {
        // convert $requestUrl to path
        $requestUrl = trim(strip_tags($requestUrl));
        $requestUrl = strtolower($requestUrl);

        $args = explode('/', rtrim($requestUrl, '/'));
        $endpoint = array_shift($args);

        // make sure endpoint exists, if so instantiate it
        $endPointClassPath = CORE_ROOT . '/includes/api/v2/endpoint/' . $endpoint . '.api.php';
        if(!file_exists($endPointClassPath))
        {
            header("HTTP/1.1 404 Not Found'");

            return json_encode("No endpoint found for: " . $endpoint);
        }

        // include the endpoint
        include_once($endPointClassPath);

        // instantiae the object
        $className = 'api' . str_replace(' ', '', ucwords(str_replace('_', ' ', $endpoint)));
        $endPointInit = new $className($requestUrl, $origin);

        return $endPointInit;
    }

    public static function getApiUrl()
    {
        // load it from the config if we have it
        if(strlen(SITE_CONFIG_API_ACCESS_HOST))
        {
            return SITE_CONFIG_API_ACCESS_HOST;
        }

        // fallback on the default
        return WEB_ROOT . '/api/v2/';
    }

}
