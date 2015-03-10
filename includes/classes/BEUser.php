<?php
/**
 * @author Egor Dmitriev
 * @package BlogEngine
 */

class User {

    /** @var BEDatabase $db */
    private $db;

    public function __construct($db){
        $this->db = $db;
        $this->user_session_start();
    }

    /**
     *
     */
    public function user_session_start() {
        $session_name = 'BESESSION';
        $secure = false;
        $httponly = true;

        if (ini_set('session.use_only_cookies', 1) === FALSE) {
            exit();
        }

        $cookieParams = session_get_cookie_params();

        session_set_cookie_params($cookieParams["lifetime"],
            $cookieParams["path"],
            $cookieParams["domain"],
            $secure,
            $httponly);

        session_name($session_name);
        session_start();
        session_regenerate_id();
        ob_start();
    }

    /**
     * @return bool
     */
    public function is_logged_in(){
        if (isset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['login_string'])) {
            $user_id = $_SESSION['user_id'];
            $login_string = $_SESSION['login_string'];
            $user_browser = $_SERVER['HTTP_USER_AGENT'];

            $this->db->query('SELECT user_pw FROM '. BEUSERSTABLE .' WHERE user_id = :user_id LIMIT 1');
            $this->db->bind(':user_id', $user_id);

            if($this->db->execute()) {
                if($this->db->rowCount() == 1) {
                    $row = $this->db->single();
                    $login_string_check = hash('sha512', $row['user_pw'] . $user_browser);

                    if($login_string_check == $login_string) {
                        return true;
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }
        return false;
    }

    public function create_hash($value)
    {
        return $hash = crypt($value, '$2a$12$'.substr(str_replace('+', '.', base64_encode(sha1(microtime(true), true))), 0, 22));
    }

    private function verify_hash($password,$hash)
    {
        return $hash == crypt($password, $hash);
    }

    private function get_user_hash($username){

        try {

            $this->db->query('SELECT user_pw FROM '. BEUSERSTABLE .' WHERE username = :username');
            $this->db->bind(':username', $username);
            $this->db->execute();
            $row = $this->db->single();

            return $row['user_pw'];

        } catch(PDOException $e) {
            echo '<p class="error">'.$e->getMessage().'</p>';
            return '';
        }
    }

    /**
     * @param $username
     * @param $password
     * @return bool
     */
    public function login($username,$password){

        $hashed = $this->get_user_hash($username);

        if($this->verify_hash($password,$hashed) == 1) {
            $user_browser = $_SERVER['HTTP_USER_AGENT'];
            $user_id = preg_replace('/[^0-9]+/', '', $this->get_userid($username));
            $_SESSION['user_id'] = $user_id;
            $username = preg_replace('/[^a-zA-Z0-9_\-]+/','',$username);
            $_SESSION['username'] = $username;
            $_SESSION['login_string'] = hash('sha512', $hashed . $user_browser);

            return 2;
        } else {
            return -1;
        }
    }

    /**
     * @return bool
     */
    public function logout() {
        $_SESSION = array();

        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
        session_destroy();

        return true;
    }

    /**
     * @param $fullname
     * @param $email
     * @param $username
     * @param $password
     * @param $group_id = 0
     * @return bool|int -1 on already existing email || -3 already existing username || -2 smtp fault || true is success
     */
    public function register($fullname, $email, $username, $password, $group_id = 0) {

        $this->db->query('SELECT user_id FROM '. BEUSERSTABLE .' WHERE user_email = :email');
        $this->db->bind(':email', $email);
        $this->db->execute();

        if($this->db->rowCount() == 1) {
                return -1;
        }

        $this->db->query('SELECT user_id FROM '. BEUSERSTABLE .' WHERE username = :username');
        $this->db->bind(':username', $username);
        $this->db->execute();

        if($this->db->rowCount() == 1) {
            return -3;
        }

        $hashedpassword = $this->create_hash($password);

        $this->db->query('INSERT INTO '.BEUSERSTABLE
            .' (fullname, username, user_pw, user_email, user_group) VALUES (:fullname, :username, :password, :email, :user_group)');
        $this->db->bind(':fullname', $fullname);
        $this->db->bind(':username', $username);
        $this->db->bind(':email', $email);
        $this->db->bind(':password', $hashedpassword);
        $this->db->bind(':user_group', $group_id);
        if($this->db->execute()) {
            return 2;
        } else {
            return -4;
        }

    }

    /**
     * @param $username
     * @return mixed
     */
    public function get_userid($username) {
        $this->db->query('SELECT user_id FROM '. BEUSERSTABLE .' WHERE username = :username');
        $this->db->bind(':username', $username);
        $this->db->execute();
        $row = $this->db->single();

        return $row['user_id'];
    }

    public function get_username($user_id) {

        $this->db->query('SELECT username FROM '. BEUSERSTABLE .' WHERE user_id = :user_id');
        $this->db->bind(':user_id', $user_id);
        $this->db->execute();
        $row = $this->db->single();

        return $row['username'];
    }

    /**
     * @param bool $loginCheck
     * @return mixed
     */
    public function get_email($loginCheck = false) {
        if($loginCheck == true) {
            if($this->is_logged_in() == false) {
                return false;
            }
        }
        $this->db->query('SELECT user_email FROM '. BEUSERSTABLE .' WHERE username = :username');
        $this->db->bind(':username', $_SESSION['username']);
        $this->db->execute();
        $row = $this->db->single();

        return $row['user_email'];
    }

    public function set_group($user_id, $group_id) {
        $this->db->query('UPDATE '. BEUSERSTABLE .' SET user_group = :group_id WHERE user_id = '. $user_id);
        $this->db->bind(':group_id', $group_id);
        if($this->db->execute()) {
            return true;
        } else {
            return false;
        }
    }

    public function get_group($user_id) {
        $this->db->query('SELECT user_group FROM '. BEUSERSTABLE .' WHERE user_id = :user_id');
        $this->db->bind(':user_id', $user_id);
        $this->db->execute();
        $row = $this->db->single();

        if($this->db->rowCount() > 0) {

            return $row['user_group'];
        } else {
            return 1;
        }

    }

    public function set_password($user_id, $new_password) {
        $this->db->query('UPDATE '. BEUSERSTABLE .' SET user_pw = :user_pw WHERE user_id = '. $user_id);
        $this->db->bind(':user_pw', $this->create_hash($new_password));
        if($this->db->execute()) {
            return true;
        } else {
            return false;
        }
    }

    public function delete_user($user_id) {
        $this->db->query('DELETE FROM '. BEUSERSTABLE .' WHERE user_id = :user_id');
        $this->db->bind(':post_id', $user_id);
        $this->db->execute();
    }

    public function has_permission($user_id, $permission) {
        $group_id = $this->get_group($user_id);

        $this->db->query('SELECT '. $permission .' FROM '. BEGROUPTABLE .' WHERE group_id = :group_id');
        $this->db->bind(':group_id', $group_id);
        $this->db->execute();
        $row = $this->db->single();

        if($this->db->rowCount() > 0) {
            return (boolean)$row[$permission];
        } else {
            return false;
        }
    }
} 