<?php namespace App\Services;

use \Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthService {
    protected $dbConn;
    protected $secretKey;

    public function __construct(\PDO $dbConn, string $secretKey) {
        $this->dbConn = $dbConn;
        $this->secretKey = $secretKey;
    }

    public function validateCredentials(string $username, string $pass) {
        $stmt = $this->dbConn->prepare("SELECT * FROM users WHERE username = :username AND password = :pass");
        $stmt->bindParam(":username", $username);
        $stmt->bindParam(":pass", $pass);
        $stmt->execute();
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($user) {
            if ($user['active'] != 1)
                throw new \Exception('Account is inactive', 403);
            return $user;
        } else 
            throw new \Exception('Credentials are invalid', 403);
    }

    public function validateToken() {
        try {
            $token = $this->getBearerToken();
            $payload = JWT::decode($token, new Key($this->secretKey, 'HS256'));

            $stmt = $this->dbConn->prepare("SELECT * FROM users WHERE id = :userId");
            $stmt->bindParam(":userId", $payload->userId);
            $stmt->execute();
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            if($user) {
                if($user['active'] != 1) {
                    throw new \Exception('Account is inactive', 403);
                }
                return $payload;
            }

            throw new \Exception('Credentials are invalid', 403);
        } catch(ExpiredException $e) {
            throw new \Exception('Token has expired', 403);
        } catch (\Exception $e) {
            throw new \Exception('Authentication failed', 401);
        }
    }

    //TODO: Function that adds to a table of blacklisted tokens
    public function invalidateToken() {
        
    }

    public function generateToken($userId, $userRole) {    
        $payload = [
            'iat' => time(),
            'iss' => 'localhost',
            'exp' => time() + (60*60),
            'userId' => $userId,
            'userRole' => $userRole
        ];

        $token = JWT::encode($payload, $this->secretKey, 'HS256');
        
        return $token;
    }

    /**
    * Get header Authorization
    * */
    public function getAuthorizationHeader(){
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        }
        else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        return $headers;
    }
    
    /**
     * get access token from header
     * */
    public function getBearerToken() {
        $headers = $this->getAuthorizationHeader();
        // HEADER: Get the access token from the header
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        throw new \Exception('Bearer token not found', 400);
    }
}