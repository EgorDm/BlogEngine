<?php
/**
 * @package BlogEngine
 * @author Egor Dmitriev <egordmitriev2@gmail.com>
 * @link https://github.com/EgorDm/BlogEngine
 * @copyright 2015 Egor Dmitriev
 * @license Licensed under MIT https://github.com/EgorDm/BlogEngine/blob/master/LICENSE.md
 */

/**
 * Imports BlogEngine Api, creates an instance of BEBlogAPI and stores it into $bea variable
 */
include_once dirname(__FILE__) . '/includes/classes/BEBlogAPI.php';
$bea = new BEBlogAPI(BEDatabase::get_instance());
