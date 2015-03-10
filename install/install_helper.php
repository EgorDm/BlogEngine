<?php
/**
 * @author Egor Dmitriev
 * @package BlogEngine
 */


/**
 * @return bool
 */
function create_main_tables() {

    $create_postsdb = "( ".
        "`post_id` INT(11) unsigned NOT NULL AUTO_INCREMENT,".
        "`post_title` VARCHAR(255) DEFAULT NULL,".
        "`post_desc` TEXT,".
        "`post_cont` TEXT,".
        "`post_date` DATETIME DEFAULT NULL,".
        "`post_owner` INT(11) unsigned NOT NULL DEFAULT 0,".
        "PRIMARY KEY (`post_id`)); ";

    $create_userdb = "( ".
        "`user_id` INT(11) unsigned NOT NULL AUTO_INCREMENT,".
        "`fullname` VARCHAR(255) DEFAULT NULL,".
        "`username` VARCHAR(255) DEFAULT NULL,".
        "`user_pw` VARCHAR(255) DEFAULT NULL,".
        "`user_email` VARCHAR(255) DEFAULT NULL,".
        "`user_group` INT(11) unsigned NOT NULL DEFAULT 0,".
        "PRIMARY KEY (`user_id`)); ";

    $create_groupdb = "( ".
        "`group_id` int(11) unsigned NOT NULL AUTO_INCREMENT,".
        "`group_name` varchar(255) DEFAULT NULL,".
        "`perm_readpost` int(2) unsigned NOT NULL DEFAULT '0',".
        "`perm_createpost` int(2) unsigned NOT NULL DEFAULT '0',".
        "`perm_deletepost` int(2) unsigned NOT NULL DEFAULT '0',".
        "`perm_editpost` int(2) unsigned NOT NULL DEFAULT '0',".
        "`perm_comment` int(2) unsigned NOT NULL DEFAULT '0',".
        "`perm_useredit` int(2) unsigned NOT NULL DEFAULT '0',".
        "PRIMARY KEY (`group_id`)); ";

    if(create_table($create_postsdb, 'posts') &&
        create_table($create_userdb, 'users') &&
        create_table($create_groupdb, 'groups')) {
        global $config;

        $config['DONE_SETUP'] = "1";
        update_setup_config();

        return true;
    }
}

/**
 * @param $query
 * @param $name
 * @return bool
 */
function create_table($query, $name) {
    global $db;
    global $config;
    $new_table_name = $config['TABLE_PREFIX'] . $name;

    // Didn't find it, so try to create it.
    BEDatabase::get_instance()->query("CREATE TABLE IF NOT EXISTS " . $new_table_name . $query);
    BEDatabase::get_instance()->execute();

    return true;
}

function update_setup_config(){
    $f = fopen( dirname(__FILE__) . '/../config.ini', "w" );
    global $config;
    foreach ( $config as $name => $value )
    {
        $value = str_replace( '"', '\"', $value );
        fwrite( $f, "$name = \"$value\"\n" );
    }

    fclose( $f );
}
