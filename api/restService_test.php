<?php
// required headers
header("Access-Control-Allow-Origin: http://localhost/api_task/");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// generate json web token
include_once '../config/core.php';
include_once '../libs/src/BeforeValidException.php';
include_once '../libs/src/ExpiredException.php';
include_once '../libs/src/SignatureInvalidException.php';
include_once '../libs/src/JWT.php';
include_once '../libs/src/Key.php';

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

include_once '../config/database.php';
include '../objects/user.php';

$GLOBALS['db'] = new Database();

class restServiceTest
{
    var $params;

    function __construct()
    {
        $this->params = json_decode(file_get_contents("php://input"));
        $this->createTestRecord($_GET['method']);
        foreach ($this->params as $param => $value) {
            if (!isset($_REQUEST[$param])) {
                $_REQUEST[$param] = $value;
            }
        }
    }

    function router()
    {
        if (!empty($_REQUEST['method'])) {
            if (method_exists($this, strtolower($_REQUEST['method']))) {
                return $this->{$_REQUEST['method']}();
            } else return array('status' => 'error', 'message' => 'Method could not found');
        } else return array('status' => 'error', 'message' => 'Method param must be sent');
    }

    function createToken(array $data)
    {
        global $key, $issued_at, $expiration_time, $issuer;
        $token = array(
            "iat" => $issued_at,
            "exp" => $expiration_time,
            "iss" => $issuer,
            "data" => $data
        );
        return JWT::encode($token, $key, 'HS256');
    }

    function checkTokenIsValid($token)
    {
        global $key;
        try {
            JWT::$leeway=60;
            $jwt = JWT::decode($token, new Key($key, 'HS256'));
            return array('status' => 'ok', "message" => "Access granted", "data" => $jwt->data);
        } catch (Exception $e) {
            return array('status' => 'error', "message" => "Access denied", "error" => $e->getMessage());
        }
    }

    function createUser()
    {
        if (!isset($_REQUEST['user_name'])) return array('status' => 'ok', 'message' => 'Username must be sent');
        if (!isset($_REQUEST['password'])) return array('status' => 'ok', 'message' => 'Password must be sent');
        $user = new User();
        $user->user_name = $_REQUEST['user_name'];
        $user->first_name = $_REQUEST['first_name'];
        $user->last_name = $_REQUEST['last_name'];
        $user->email = $_REQUEST['email'];
        $user->password = $_REQUEST['password'];
        $result = $user->save();
        if ($result) return array('status' => 'ok', 'message' => 'User created successfully');
        else return array('status' => 'error', 'message' => 'Unable to create user');
    }

    function login()
    {
        global $db;
        if (!isset($_REQUEST['user_name'])) return array('status' => 'ok', 'message' => 'Username must be sent');
        if (!isset($_REQUEST['password'])) return array('status' => 'ok', 'message' => 'Password must be sent');
        $sql = "SELECT id FROM users WHERE deleted=0 AND user_name='{$_REQUEST['user_name']}' AND password= SHA2('{$_REQUEST['password']}',256)";
        $user_id = $db->getOne($sql, true);
        if ($user_id) {
            $data['user_id'] = $user_id;
            $data['user_name'] = $_REQUEST['user_name'];
            $data['password'] = $_REQUEST['password'];
            $token = $this->createToken($data);
            return array('status' => 'ok', 'message' => 'Login successful', 'token' => $token);
        } else return array('status' => 'error', 'message' => 'Login Failed');
    }

    function getUserInfo()
    {
        global $db;
        if (!isset($_REQUEST['token'])) return array('status' => 'ok', 'message' => 'Token must be sent');
        $response = $this->checkTokenIsValid($_REQUEST['token']);
        if ($response['status'] == 'ok') {
            $sql = "SELECT id,user_name,first_name,last_name,email,date_created FROM users WHERE deleted=0 AND id='{$response['data']->user_id}'";
            $result = $db->query($sql, true);
            $row = $db->fetchByAssoc($result);
            if (!empty($row['id'])) {
                $user_info['id'] = $row['id'];
                $user_info['user_name'] = $row['user_name'];
                $user_info['first_name'] = $row['first_name'];
                $user_info['last_name'] = $row['last_name'];
                $user_info['email'] = $row['email'];
                $user_info['date_created'] = $row['date_created'];
                return array('status' => 'ok', 'message' => 'User info listing successful', 'data' => $user_info);
            } else return array('status' => 'error', 'message' => 'User info listing failed', 'data' => '');
        } else return $response;
    }

    function updatePassword()
    {
        global $db;
        if (!isset($_REQUEST['token'])) return array('status' => 'ok', 'message' => 'Token must be sent');
        if (!isset($_REQUEST['new_password'])) return array('status' => 'ok', 'message' => 'New Password must be sent');
        $response = $this->checkTokenIsValid($_REQUEST['token']);
        if ($response['status'] == 'ok') {
            $user = new User();
            $user->retrieve($response['data']->user_id);
            if (empty($user->id)) return array('status' => 'error', 'message' => 'User could not found with id: ' . $response['data']->user_id);
            $user->password = $_REQUEST['new_password'];
            $result = $user->save();
            if ($result) {
                $data['user_id'] = $user->id;
                $data['user_name'] = $user->user_name;
                $data['password'] = $_REQUEST['new_password'];
                $token=$this->createToken($data);
                return array('status' => 'ok', 'message' => 'Password changed successfully', 'token' => $token);
            }
            else return array('status' => 'error', 'message' => 'Password changed failed');
        } else return $response;
    }

    function createTestRecord($method = "")
    {
        switch ($method) {
            case "createUser" :
                $this->params = array(
                    'method' => 'createUser',
                    'user_name' => 'mhmmr',
                    'first_name' => 'Muhammer',
                    'last_name' => 'BÜYÜKARSLAN',
                    'email' => 'mhmmr@gmail.com',
                    'password' => 'MB123'
                );
                break;
            case "login" :
                $this->params = array(
                    'method' => 'login',
                    'user_name' => 'mhmmr',
                    'password' => 'MB123'
                );
                break;
            case "getUserInfo" :
                $this->params = array(
                    'method' => 'getUserInfo',
                    'token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE2NjY1MjkxNzQsImV4cCI6MTY2NjUzMjc3NCwiaXNzIjoiaHR0cDovL2dpdGh1Yi5jb20vbml5YXppbXVoYW1tZXRrb3NlIiwiZGF0YSI6eyJ1c2VyX2lkIjoiMiIsInVzZXJfbmFtZSI6Im1obW1yIiwicGFzc3dvcmQiOiJNQjEyMyJ9fQ.RaRJ2yZL9XwdZQ_tzS5VzqVYQB_khir-NPSJALXqLZI'
                );
                break;
            case "updatePassword" :
                $this->params = array(
                    'method' => 'updatePassword',
                    'token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE2NjY1MjkxNzQsImV4cCI6MTY2NjUzMjc3NCwiaXNzIjoiaHR0cDovL2dpdGh1Yi5jb20vbml5YXppbXVoYW1tZXRrb3NlIiwiZGF0YSI6eyJ1c2VyX2lkIjoiMiIsInVzZXJfbmFtZSI6Im1obW1yIiwicGFzc3dvcmQiOiJNQjEyMyJ9fQ.RaRJ2yZL9XwdZQ_tzS5VzqVYQB_khir-NPSJALXqLZI',
                    'new_password' => 'MB12345'
                );
                break;
            default :
                $this->params = array(
                    'method' => 'createUser',
                    'user_name' => 'admin',
                    'first_name' => 'Niyazi Muhammet',
                    'last_name' => 'KÖSE',
                    'email' => 'nmk@gmail.com',
                    'password' => 'NMK123'
                );
        }
    }
}

$rest_service = new restServiceTest();
$return = $rest_service->router();
ob_clean();
die(json_encode($return));
