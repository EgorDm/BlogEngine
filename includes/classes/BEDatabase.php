<?php
/**
 * @author Egor Dmitriev
 * @package BlogEngine
 */


class BEDatabase {

    /** @var BEDatabase $pdo */
    private static $instance;

    /** @var PDO $pdo */
    protected static $pdo;

    /** @var PDOStatement $stmt */
    private $stmt;


    public function __construct()
    {
        $this->connect();
    }

    public static function get_instance() {
        if(!isset(self::$instance)) {
            self::$instance = new BEDatabase();
        }

        return self::$instance;
    }

    private function connect()
    {
        global $config;

        $dsn = 'mysql:dbname='. $config['DBNAME'] .';host='. $config['DBHOST'] .'';
        try
        {
            self::$pdo = new PDO($dsn, $config['DBUSER'], $config['DBPASSWORD'],
                array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES ".$config['DB_CHARSET']));

            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        }
        catch (PDOException $e)
        {
            echo $e->getMessage();
            die();
        }
    }

    public function CloseConnection()
    {
        self::$pdo = null;
    }

    public function query($query){
        $this->stmt = self::$pdo->prepare($query);
    }

    public function bind($param, $value, $type = null){
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

    public function execute(){
        return $this->stmt->execute();
    }

    public function resultset(){
        $this->execute();
        return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function single(){
        $this->execute();
        return $this->stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function rowCount(){
        return $this->stmt->rowCount();
    }

    public function lastInsertId(){
        return self::$pdo->lastInsertId();
    }

}