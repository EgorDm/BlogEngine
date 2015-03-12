<?php

/**
 * @package BlogEngine
 * @author Egor Dmitriev <egordmitriev2@gmail.com>
 * @link https://github.com/EgorDm/BlogEngine
 * @copyright 2015 Egor Dmitriev
 * @license Licensed under MIT https://github.com/EgorDm/BlogEngine/blob/master/LICENSE.md
 */
class BEDatabase
{

    /**
     * A PDO instance to access the database.
     *
     * @var PDO $pdo
     */
    protected static $pdo;

    /**
     * Instance of BEDatabase to create a easy access to database across the files.
     *
     * @var BEDatabase $pdo
     */
    private static $instance;


    /**
     * A variable to hold a statement.
     *
     * @var PDOStatement $stmt
     */
    private $stmt;


    /**
     * A construct method of BEDatabase for initialisation.
     */
    public function __construct()
    {
        $this->connect();
    }


    /**
     * A method to connect to the database.
     */
    private function connect()
    {
        global $config;

        $dsn = 'mysql:dbname=' . $config['DBNAME'] . ';host=' . $config['DBHOST'] . '';
        try {
            self::$pdo = new PDO($dsn, $config['DBUSER'], $config['DBPASSWORD'],
                array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . $config['DB_CHARSET']));

            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch (PDOException $e) {
            echo $e->getMessage();
            die();
        }
    }

    /**
     * Returns a instance of BEDatabase.
     *
     * @return BEDatabase
     */
    public static function get_instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new BEDatabase();
        }

        return self::$instance;
    }

    /**
     * Closes connection with sql server.
     */
    public function close_connection()
    {
        self::$pdo = null;
    }


    /**
     * Prepares a statement for execution and returns a statement object.
     *
     * @param string $query a valid sql statement
     */
    public function query($query)
    {
        $this->stmt = self::$pdo->prepare($query);
    }

    /**
     * Binds value to a param in the query.
     *
     * @param mixed $param parameter identifier
     * @param mixed $value the value to bind to the parameter.
     * @param null $type explicit data type
     */
    public function bind($param, $value, $type = null)
    {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
    }

    /**
     * Executes prepared statement and returns an array containing all of the result set rows.
     *
     * @return array containing all of the remaining rows in the result set
     */
    public function resultset()
    {
        $this->execute();
        return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Executes a prepared statement.
     *
     * @return bool result, on success true on failure false
     */
    public function execute()
    {
        return $this->stmt->execute();
    }

    /**
     * Executes prepared statement and fetches the next row from a result set.
     *
     * @return mixed returns result on success and false on failure
     */
    public function single()
    {
        $this->execute();
        return $this->stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Returns the number of rows affected by the last SQL statement.
     *
     * @return int amount of rows effected
     */
    public function rowCount()
    {
        return $this->stmt->rowCount();
    }

    /**
     * Returns the ID of the last inserted row or sequence value.
     *
     * @return string representing the row ID of the last row that was inserted into the database.
     */
    public function lastInsertId()
    {
        return self::$pdo->lastInsertId();
    }

}