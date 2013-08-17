<?
class Connection{
    protected static $host;
    protected $username;
    protected $password;
    protected $dbname;
    protected static $connection;

    public function __get($name) {
        return $this->$name;
    }
    public function __set($name, $value) {
        $this->$name = $value;
    }
    function connect(){
        if(!self::$connection){
            self::$connection = mysql_connect($this->host,  $this->username,  $this->password);
            if(!self::$connection){
                die(mysql_error());
            }
            mysql_select_db($this->dbname);
        }
    }
    
}
?>