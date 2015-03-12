<?php
/**
 * @package BlogEngine
 * @author Egor Dmitriev <egordmitriev2@gmail.com>
 * @link https://github.com/EgorDm/BlogEngine
 * @copyright 2015 Egor Dmitriev
 * @license Licensed under MIT https://github.com/EgorDm/BlogEngine/blob/master/LICENSE.md
 */


/**
 * Loads a BEEngine config with needed data
 */
$config = parse_ini_file(dirname(__FILE__) . '/../../config.ini');

include_once dirname(__FILE__) . '/BEDatabase.php';
include_once dirname(__FILE__) . '/BEUser.php';

class BEBlogAPI
{

    /**
     * A BEUser instance
     *
     * @var BEUser $user
     */
    public $user;

    /**
     * @var array $config
     */
    public $config;

    /**
     * A BEDatabase instance
     *
     * @var BEDatabase $db
     */
    public $db;

    /**
     * A construct method of BEBlogAPI for initialisation.
     *
     * @param BEDatabase $db database instance
     */
    public function __construct(BEDatabase $db)
    {
        $this->db = $db;

        global $config;
        if ($config['DONE_SETUP'] != 1) {
            include_once dirname(__FILE__) . '/BEInstallHelper.php';
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

    /**
     * Creates a new group with specified name and permissions.
     *
     * @param string $group_name
     * @param bool $perm_readpost permission for reading any post
     * @param bool $perm_createpost permission to create a post
     * @param bool $perm_deletepost permission to delete a post
     * @param bool $perm_editpost permission to edit a post
     * @param bool $perm_comment permission to comment on a post
     * @param bool $perm_useredit permission to edit users
     * @return string returns id of the created group. Will return false on failure.
     */
    public function add_group($group_name, $perm_readpost, $perm_createpost,
                              $perm_deletepost, $perm_editpost, $perm_comment, $perm_useredit)
    {

        $this->db->query('SELECT group_id FROM ' . BEGROUPTABLE . ' WHERE group_name = :group_name');
        $this->db->bind(':group_name', $group_name);
        $this->db->execute();

        if ($this->db->rowCount() == 1) {
            return false;
        }


        $this->db->query('INSERT INTO ' . BEGROUPTABLE
            . ' (group_name, perm_readpost, perm_createpost, perm_deletepost, perm_editpost, perm_comment, perm_useredit)'
            . ' VALUES (:group_name, :perm_readpost, :perm_createpost, :perm_deletepost, :perm_editpost, :perm_comment, :perm_useredit)');

        $this->db->bind(':group_name', $group_name);
        $this->db->bind(':perm_readpost', intval($perm_readpost));
        $this->db->bind(':perm_createpost', intval($perm_createpost));
        $this->db->bind(':perm_deletepost', intval($perm_deletepost));
        $this->db->bind(':perm_editpost', intval($perm_editpost));
        $this->db->bind(':perm_comment', intval($perm_comment));
        $this->db->bind(':perm_useredit', intval($perm_useredit));

        if (!$this->db->execute()) {
            return false;
        }

        return $this->db->lastInsertId();
    }

    /**
     * Creates a post with given content.
     *
     * @param string $post_title a titke of the post
     * @param string $post_desc a description of the post
     * @param string $post_cont content/body of a post
     * @param int $post_owner id of the creator
     * @return bool returns true on success and false on failure
     */
    public function add_post($post_title, $post_desc, $post_cont, $post_owner)
    {
        $this->db->query('INSERT INTO ' . BEPOSTSTABLE . ' (post_title, post_desc, post_cont, post_date, post_owner)'
            . ' VALUES (:post_title, :post_desc, :post_cont, :post_date, :post_owner)');

        $this->db->bind(':post_title', $post_title);
        $this->db->bind(':post_desc', $post_desc);
        $this->db->bind(':post_cont', $post_cont);
        $this->db->bind(':post_date', date('Y-m-d H:i:s'));
        $this->db->bind(':post_owner', $post_owner);

        return $this->db->execute();
    }

    /**
     * Returns the data of the post by its ID.
     *
     * @param int $post_id id of the post it needs to find
     * @return mixed return array on success and false on failure or if couldn't find the post
     */
    public function get_post($post_id)
    {
        $this->db->query('SELECT * FROM ' . BEPOSTSTABLE . ' WHERE post_id = :post_id');
        $this->db->bind(':post_id', $post_id);
        if ($this->db->rowCount() < 1) {
            return false;
        }

        return $this->db->single();
    }

    /**
     * Returns a list with newest posts.
     *
     * @param int $amount max amoutn of post to return
     * @return mixed array of posts on success and false on failure
     */
    public function get_post_row($amount)
    {
        $this->db->query('SELECT * FROM ' . BEPOSTSTABLE . ' ORDER BY post_id DESC LIMIT ' . $amount);

        return $this->db->resultset();
    }

    /**
     * Deletes a post with given ID.
     *
     * @param int $post_id post ID of post to delete
     * @return bool true on success and false on failure
     */
    public function delete_post($post_id)
    {
        $this->db->query('DELETE FROM ' . BEPOSTSTABLE . ' WHERE post_id = :post_id');
        $this->db->bind(':post_id', $post_id);

        return $this->db->execute();
    }

    /**
     * Edits post by replacing its contents with new ones.
     *
     * @param int $post_id post ID of the post you want to edit
     * @param string $post_title new title for the post
     * @param string $post_desc new description for the post
     * @param string $post_cont new content for the post
     * @param int $post_owner new owner for the post
     * @return bool true on success and false on failure
     */
    public function edit_post($post_id, $post_title, $post_desc, $post_cont, $post_owner)
    {
        $this->db->query('UPDATE ' . BEPOSTSTABLE . ' SET ' .
            'post_title = :post_title, post_desc = :post_desc, post_cont = :post_cont, post_owner = :post_owner'
            . ' WHERE post_id = :post_id');

        $this->db->bind(':post_title', $post_title);
        $this->db->bind(':post_desc', $post_desc);
        $this->db->bind(':post_cont', $post_cont);
        $this->db->bind(':post_owner', $post_owner);
        $this->db->bind(':post_id', $post_id);

        return $this->db->execute();
    }

} 