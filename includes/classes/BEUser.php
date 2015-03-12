<?php
/**
 * @package BlogEngine
 * @author Egor Dmitriev <egordmitriev2@gmail.com>
 * @link https://github.com/EgorDm/BlogEngine
 * @copyright 2015 Egor Dmitriev
 * @license Licensed under MIT https://github.com/EgorDm/BlogEngine/blob/master/LICENSE.md
 */

class User
{

    /**
     * A BEDatabase instance
     *
     * @var BEDatabase $db
     */
    private $db;

    /**
     * A construct method of BEUser for initialisation.
     *
     * @param BEDatabase $db database instance
     */
    public function __construct(BEDatabase $db)
    {
        $this->db = $db;
        $this->user_session_start();
    }

    /**
     * Starts a session and setup its settings.
     * Sessions are stored in cookies.
     */
    public function user_session_start()
    {
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
     * Executes a login sequence with username and password.
     *
     * @param string $username username of the user you want to login
     * @param string $password password input of the user
     * @return bool true on success and false on failure.
     */
    public function login($username, $password)
    {

        $hashed = $this->get_user_hash($username);

        if ($this->verify_hash($password, $hashed) == 1) {
            $user_browser = $_SERVER['HTTP_USER_AGENT'];
            $user_id = preg_replace('/[^0-9]+/', '', $this->get_userid($username));
            $_SESSION['user_id'] = $user_id;
            $username = preg_replace('/[^a-zA-Z0-9_\-]+/', '', $username);
            $_SESSION['username'] = $username;
            $_SESSION['login_string'] = hash('sha512', $hashed . $user_browser);

            return true;
        } else {
            return false;
        }
    }

    /**
     * Returns user's hashed password.
     *
     * @param string $username username of the user you want to get hashed password of
     * @return string|bool hashed password on success and false of failure
     */
    private function get_user_hash($username)
    {
        try {
            $this->db->query('SELECT user_pw FROM ' . BEUSERSTABLE . ' WHERE username = :username');
            $this->db->bind(':username', $username);
            $row = $this->db->single();

            return $row['user_pw'];
        } catch (PDOException $e) {
            return false;
        }
    }


    /**
     * Checks if password is equal to the hashed password.
     *
     * @param string $password not hashed password
     * @param string $hash hashed password
     * @return bool true if passwords are identical, false if not
     */
    private function verify_hash($password, $hash)
    {
        return $hash == crypt($password, $hash);
    }

    /**
     * Logout user currently the session is of.
     *
     * @return void
     */
    public function logout()
    {
        $_SESSION = array();

        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
        session_destroy();
    }

    /**
     * Registers a new user.
     *
     * @param string $fullname full name of the user
     * @param string $email email adress of the user
     * @param string $username username of the user
     * @param string $password user's password
     * @param int $group_id = 1 user's group. If not specified he will be put in the default group (registered).
     * @return bool|int -1 on already existing email || -2 already existing username || -3 smtp fault || true on success
     */
    public function register($fullname, $email, $username, $password, $group_id = 1)
    {

        $this->db->query('SELECT user_id FROM ' . BEUSERSTABLE . ' WHERE user_email = :email');
        $this->db->bind(':email', $email);
        $this->db->execute();

        if ($this->db->rowCount() == 1) {
            return -1;
        }

        $this->db->query('SELECT user_id FROM ' . BEUSERSTABLE . ' WHERE username = :username');
        $this->db->bind(':username', $username);
        $this->db->execute();

        if ($this->db->rowCount() == 1) {
            return -2;
        }

        $hashedpassword = $this->create_hash($password);

        $this->db->query('INSERT INTO ' . BEUSERSTABLE
            . ' (fullname, username, user_pw, user_email, user_group) VALUES (:fullname, :username, :password, :email, :user_group)');
        $this->db->bind(':fullname', $fullname);
        $this->db->bind(':username', $username);
        $this->db->bind(':email', $email);
        $this->db->bind(':password', $hashedpassword);
        $this->db->bind(':user_group', $group_id);
        if ($this->db->execute()) {
            return true;
        } else {
            return -3;
        }

    }

    /**
     * Hashes a string. Usually used to hash a password.
     *
     * @param string $value input string to hash
     * @return string hashed string
     */
    public function create_hash($value)
    {
        return $hash = crypt($value, '$2a$12$' . substr(str_replace('+', '.', base64_encode(sha1(microtime(true), true))), 0, 22));
    }

    /**
     * Get user's ID by its username.
     *
     * @param string $username username of the user you want to get ID of
     * @return string|bool user ID on success and false on failure
     */
    public function get_userid($username)
    {
        $this->db->query('SELECT user_id FROM ' . BEUSERSTABLE . ' WHERE username = :username');
        $this->db->bind(':username', $username);
        $row = $this->db->single();

        return $row['user_id'];
    }

    /**
     * Get user's username by its ID.
     *
     * @param int $user_id user's ID you want to get username of
     * @return string|bool string on success and false on failure
     */
    public function get_username($user_id)
    {
        $this->db->query('SELECT username FROM ' . BEUSERSTABLE . ' WHERE user_id = :user_id');
        $this->db->bind(':user_id', $user_id);
        $row = $this->db->single();

        return $row['username'];
    }

    /**
     * Get user's email by its ID.
     *
     * @param int $user_id user's ID you want to get email of
     * @return string|bool string on success and false on failure
     */
    public function get_email($user_id)
    {
        $this->db->query('SELECT user_email FROM ' . BEUSERSTABLE . ' WHERE user_id = :user_id');
        $this->db->bind(':user_id', $user_id);
        $row = $this->db->single();

        return $row['user_email'];
    }

    /**
     * Get user's group ID by its ID.
     *
     * @param $user_id user's ID you want to get group ID of
     * @return int|bool group ID on success and false on failure
     */
    public function get_group($user_id)
    {
        $this->db->query('SELECT user_group FROM ' . BEUSERSTABLE . ' WHERE user_id = :user_id');
        $this->db->bind(':user_id', $user_id);
        $this->db->execute();
        $row = $this->db->single();

        if ($this->db->rowCount() > 0) {

            return $row['user_group'];
        }
        return false;
    }

    /**
     * Checks if user the session is of is logged in.
     *
     * @return bool true if is logged in and false if not
     */
    public function is_logged_in()
    {
        if (isset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['login_string'])) {
            $login_string = $_SESSION['login_string'];
            $user_browser = $_SERVER['HTTP_USER_AGENT'];

            $this->db->query('SELECT user_pw FROM ' . BEUSERSTABLE . ' WHERE user_id = :user_id LIMIT 1');
            $this->db->bind(':user_id', $_SESSION['user_id']);

            $result = $this->db->single();
            if ($result) {
                if ($this->db->rowCount() == 1) {
                    $login_string_check = hash('sha512', $result['user_pw'] . $user_browser);

                    if ($login_string_check == $login_string) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Changes user's group.
     *
     * @param int $user_id user's ID you want to change group of
     * @param int $group_id new group ID you want to assign user to
     * @return bool true on success and false of failure
     */
    public function set_group($user_id, $group_id)
    {
        $this->db->query('UPDATE ' . BEUSERSTABLE . ' SET user_group = :group_id WHERE user_id = ' . $user_id);
        $this->db->bind(':group_id', $group_id);

        return $this->db->execute();
    }

    /**
     * Changes user's password.
     *
     * @param int $user_id user's ID you want to change password of
     * @param string $new_password new password you want user to have
     * @return bool true on success and false of failure
     */
    public function set_password($user_id, $new_password)
    {
        $this->db->query('UPDATE ' . BEUSERSTABLE . ' SET user_pw = :user_pw WHERE user_id = ' . $user_id);
        $this->db->bind(':user_pw', $this->create_hash($new_password));

        return $this->db->execute();
    }

    /**
     * Deletes user by ID
     *
     * @param int $user_id user's ID you want to delete
     * @return bool true on success and false of failure
     */
    public function delete_user($user_id)
    {
        $this->db->query('DELETE FROM ' . BEUSERSTABLE . ' WHERE user_id = :user_id');
        $this->db->bind(':post_id', $user_id);

        return $this->db->execute();
    }

    /**
     * Checks if user's group has the permission to do ...
     * Available permissions are: 'perm_readpost', 'perm_createpost', 'perm_deletepost',
     * 'perm_editpost', 'perm_comment', 'perm_useredit'
     *
     * @param int $user_id user's ID you want to check permission of
     * @param string $permission name of the permission you want to check
     * @return bool true if has permission and false if not
     */
    public function has_permission($user_id, $permission)
    {
        $group_id = $this->get_group($user_id);

        $this->db->query('SELECT ' . $permission . ' FROM ' . BEGROUPTABLE . ' WHERE group_id = :group_id');
        $this->db->bind(':group_id', $group_id);
        $row = $this->db->single();

        if ($this->db->rowCount() > 0) {
            return (bool)$row[$permission];
        }
        return false;
    }
} 