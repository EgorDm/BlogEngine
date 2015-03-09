<?php
/**
 * @author Egor Dmitriev
 * @package BlogEngine
 */

$config = parse_ini_file("config.ini");

include_once 'includes/classes/BEDatabase.php';
include_once 'includes/classes/BEUser.php';

class BEBlogAPI {

    public $user;
    public $config;

    public function __construct(){
        global $config;
        if($config['DONE_SETUP'] != 1) {
            include_once '././install/install_helper.php';
            create_main_tables();
            $this->add_group('unregistered', true, false, false, false, false, false);
            $this->add_group('registered', true, false, false, false, true, false);
            $this->add_group('admin', true, true, true, true, true, true);
        }

        date_default_timezone_set('Europe/Amsterdam');
        $this->user = new User(BEDatabase::get_instance());

        define('BEGROUPTABLE', $config['TABLE_PREFIX'] . 'groups');
        define('BEPOSTSTABLE', $config['TABLE_PREFIX'] . 'posts');
        define('BEUSERSTABLE', $config['TABLE_PREFIX'] . 'users');

    }

    public function add_group($group_name, $perm_readpost, $perm_createpost,
                              $perm_deletepost, $perm_editpost, $perm_comment, $perm_useredit) {
        $db = BEDatabase::get_instance();

        $db->query('SELECT group_id FROM '. BEGROUPTABLE .' WHERE group_name = :group_name');
        $db->bind(':group_name', $group_name);
        $db->execute();

        if($db->rowCount() == 1) {
            return;
        }


        $db->query('INSERT INTO '.BEGROUPTABLE
            .' (group_name, perm_readpost, perm_createpost, perm_deletepost, perm_editpost, perm_comment, perm_useredit)'
            .' VALUES (:group_name, :perm_readpost, :perm_createpost, :perm_deletepost, :perm_editpost, :perm_comment, :perm_useredit)');

        $db->bind(':group_name', $group_name);
        $db->bind(':perm_readpost', intval($perm_readpost));
        $db->bind(':perm_createpost', intval($perm_createpost));
        $db->bind(':perm_deletepost',intval($perm_deletepost));
        $db->bind(':perm_editpost', intval($perm_editpost));
        $db->bind(':perm_comment', intval($perm_comment));
        $db->bind(':perm_useredit', intval($perm_useredit));
        $db->execute();
    }

    public function add_post($post_title, $post_desc, $post_cont, $post_owner) {
        $db = BEDatabase::get_instance();


        $db->query('INSERT INTO '. BEPOSTSTABLE .' (post_title, post_desc, post_cont, post_date, post_owner)'
                   .' VALUES (:post_title, :post_desc, :post_cont, :post_date, :post_owner)') ;

        $db->bind(':post_title', $post_title);
        $db->bind(':post_desc', $post_desc);
        $db->bind(':post_cont', $post_cont);
        $db->bind(':post_date', date('Y-m-d H:i:s'));
        $db->bind(':post_owner', $post_owner);
        $db->execute();
    }

    public function get_post($post_id) {
        $db = BEDatabase::get_instance();

        $db->query('SELECT * FROM '. BEPOSTSTABLE .' WHERE post_id = :post_id');
        $db->bind(':post_id', $post_id);
        $db->execute();

        return $db->single();
    }

    public function get_post_row($amount) {
        $db = BEDatabase::get_instance();

        $db->query('SELECT * FROM '. BEPOSTSTABLE .' ORDER BY post_id DESC LIMIT ' . $amount);
        $db->execute();

        return $db->resultset();
    }

    public function delete_post($post_id) {
        $db = BEDatabase::get_instance();

        $db->query('DELETE FROM '. BEPOSTSTABLE .' WHERE post_id = :post_id');
        $db->bind(':post_id', $post_id);
        $db->execute();
    }

} 