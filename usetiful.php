<?php
# ----------------------------------------
# Usetiful - Php / Mysql Class generator
# https://github.com/alby981/usetiful
# Copyright 2013, Alberto Belotti 
# 
# Licensed under the MIT license:
# http://www.opensource.org/licenses/MIT
# ----------------------------------------

define("TAB", "        ");
define("A_CAPO", "\r\n");
define("MSG_DATABASE", "Attenzione! <em>Dati database obbligatori!!</em>");
define("MSG_PROJECT", "Warning! <em>Specify project name first!</em>");
define("MSG_SCHEMA", "Warning! <em>You must specify schema name before!</em>");
define("MASTER_SCHEMA", "information_schema");
define("DIR_CREATE_MSG", " I can't create the directory!<br/> Check the folder permission.");
define("DIR_EXIST_MSG", " Directory exists!");

# ---------------------------------
# MYSQL CONNECTION
# ---------------------------------
class Connection {

    protected $host;
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

    function connect() {
        if (!self::$connection) {
            self::$connection = mysql_connect($this->host, $this->username, $this->password);
            if (!self::$connection) {
                throw new Exception(die(mysql_error()));
            }
            if(!mysql_select_db($this->dbname)){
                throw new Exception(die(mysql_error()));
            }
        }
    }

}
# ---------------------------------
# Usetiful Class
# ---------------------------------

class Usetiful extends Connection {

    private $open_php = "&lt?php";
    private $close_php = "?&gt";
    private $project_folder;
    private $filename;
    private $table_schema;
    private $table_name;
    private $result_string = array();

    function __construct($server, $username, $password, $schema, $project_name, $table = false) {
        $username = $username;
        $server = $server;
        $password = $password;
        $table_name = $table;
        $this->filename = dirname(__FILE__) . "/class_generated.txt";
        $this->project_folder = dirname(__FILE__) . "/" . $project_name;
        $this->host = $server;
        $this->username = $username;
        $this->password = $password;
        $this->table_name = $table_name;
        $this->dbname = MASTER_SCHEMA;
        $this->table_schema = $schema;
        
        try {
            $this->connect();
            $this->executeMasterQuery();
            $filename_db = dirname(__FILE__) . "/" . $this->project_folder . "/Class.Business.php";
            $this->getter_and_setter();
            $this->db_object_generator();
            $this->action_generator();
            $this->dao_utils_generator($username, $password, $server, $schema);
            $this->email_generator();
            $this->validation_generator();
            $this->trigger_generator();
            $this->constants_generator();
            $this->html_generator();
            $this->generate_views();
            $this->generate_assets();
            echo "You did it!!!";
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }

    function __set($name, $value) {
        $this->$name = $value;
    }

    function __get($name) {
        return $this->$name;
    }

    function executeMasterQuery() {
        # -----------------------------------------
        # THIS IS THE MASTER QUERY. IT TAKES ALL DATAS FROM INFORMATION SCHEMA. 
        # YOU MUST HAVE ROOT PRIVILEGES!!!
        # -----------------------------------------
        $query = "select table_name,column_name from(
           select '&lt?php'as table_name,'' as column_name,'0a' as ordinamento
           from dual
		   union
           select \"include_once \$_SERVER['DOCUMENT_ROOT'] . '/common/config.common.php';\" as table_name,'' as column_name,'0b' as ordinamento
           from dual
           union
           select concat('class ',table_name,'{')as table_name,'' as column_name,concat(table_name,'1','0')as ordinamento
           from columns
           where table_schema = '" . $this->table_schema . "'
           and table_name like '%" . $this->table_name . "%'
           union
           select table_name,column_name,ordinamento
           from
           (
               select '     'as table_name ,concat('private $',column_name,';')as column_name,concat(table_name,'2',ordinal_position)as ordinamento
               from columns
               where table_schema = '" . $this->table_schema . "'
               and table_name like '%" . $this->table_name . "%'
               order by ordinamento,table_schema,table_name
           )fields
           union
           select '}' as table_name,'' as column_name,concat(table_name,'3','0')as ordinamento
           from columns
           where table_schema = '" . $this->table_schema . "'
           and table_name like '%" . $this->table_name . "%'
           order by ordinamento
           )base_table";
        # -----------------------------------------
        try {
            $result = mysql_query($query);
            if(!$result){
               throw  new Exception(mysql_error());
            }
            
            while ($row = mysql_fetch_assoc($result)) {
                $array_result[] = $row['table_name'] . $row['column_name'] . A_CAPO;
            }
            $this->createMasterFileClassByArray($array_result);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    function createMasterFileClassByArray($array_result) {
        #--------------Exception------------------------------------------
        # In this step i'm creating the classes (getters and setters) in a file
        #--------------------------------------------------------
        if (!$array_result || !is_array($array_result)){
            throw new Exception("Error. Array not valid.");
        }
        
        $fw = fopen($this->filename, "w");
        if(!$fw){
            throw new Exception("Can't open filename for writing...Check folder permission");
            
        }
        foreach ($array_result as $string_result) {
            $string_result = str_replace('&lt?php', '<?php', $string_result);
            fwrite($fw, $string_result);
        }
        fclose($fw);
        #--------------------------------------------------------
        # CREATE THE PROJECT FOLDER - WATCH OUT THE PERMISSIONS
        #--------------------------------------------------------
        if (file_exists($this->project_folder)) {
            die("Warning! <em><font color='red' weight='bold'>" . $this->project_folder . "</font></em>" . DIR_EXIST_MSG);
        }
        if (php_uname('s') != "Linux") {
            $old = umask(0);
            if (!@mkdir($this->project_folder, 0777, TRUE)) {
                die("Warning! <em><font color='red' weight='bold'>" . $this->project_folder . "</font></em>" . DIR_CREATE_MSG);
            }
            umask($old);
            chmod($this->project_folder, 0777);
        } else {
            if (!@mkdir($this->project_folder, 0777, TRUE)) {
                die("Warning! <em><font color='red' weight='bold'>" . $this->project_folder . "</font></em>" . DIR_CREATE_MSG);
            }
        }
    }

    /**
     * Generate the getter and setter for the classes
     * 
     */
    function getter_and_setter() {
        $file = file_get_contents($this->filename);
        $file = str_replace('<?', '&lt?', $file);
        $pattern_class = '/[C|c](lass)[\ ]+[a-zA-Z0-9\_\ ]*{/';
        $pattern = "/(protected|private) [\$a-zA-Z0-9\_]*;/";
        if (preg_match_all($pattern_class, $file, $matches_class, PREG_OFFSET_CAPTURE)) {
            $array_class = $matches_class;
        }
        for ($s = 0; $s < count($array_class[0]); $s++) {
            $names[] = $array_class[0][$s][0];
        }
        for ($i = 0; $i < count($matches_class[0]); $i++) {
            $offsets[] = strlen($matches_class[0][$i][0]) + ($matches_class[0][$i][1]);
        }

        $result_string[] = "<?php";

        for ($z = 0; $z < count($offsets); $z++) {

            $result_string[] = "//--------------------------------------------------";
            $result_string[] = $names[$z] . A_CAPO;
            $subject_class = (isset($offsets[$z + 1])) ? @substr($file, $offsets[$z], $offsets[$z + 1] - $offsets[$z]) : $subject_class = @substr($file, $offsets[$z], strlen($file));
            if (preg_match_all($pattern, $subject_class, $matches)) {
                $array = $matches;
            }
            foreach ($matches[0] as $method) {
                $result_string[] = TAB . $method;
            }
            $result_string[] = A_CAPO;
            foreach ($matches[0] as $method) {

                $method = str_replace('$', '', $method);
                $method = str_replace('private', '', $method);
                $method = str_replace('protected', '', $method);
                $method = str_replace('public', '', $method);
                $method = preg_replace('/\s/', '', $method);
                $method = preg_replace('/;/', '', $method);
                $result_string[] = TAB . 'public function get' . ucfirst($method) . "(){";
                $result_string[] = TAB . TAB . "return \$this->" . $method . ";";
                $result_string[] = TAB . "}";
                $result_string[] = TAB . 'public function set' . ucfirst($method) . "(\$" . $method . "){";
                $result_string[] = TAB . TAB . "\$this->" . $method . " = \$" . $method . ";";
                $result_string[] = TAB . "}";
            }
            $result_string[] = "}";
        }
        $result_string[] = $this->close_php;
        $fw = fopen($this->project_folder . "/Class.Business.php", "w");
        foreach ($result_string as $result_str) {
            $result_str = str_replace($open_php, "<?php", $result_str . A_CAPO);
            fwrite($fw, str_replace($this->close_php, "?>", $result_str));
        }
        fclose($fw);
    }

    /**
     * Persistence Generator - all query methods
     */
    function db_object_generator() {

        $filename = $this->project_folder . "/Class.Business.php";
        $table_schema = $this->table_schema;
        $open_php = $this->open_php;
        $close_php = $this->close_php;
        $subject = file_get_contents($filename); //input

        $totCaratteri = strlen($subject);
        $pattern_class = '/[C|c](lass)[\ ]+[a-zA-Z0-9\_\ ]*{/';
        if (preg_match_all($pattern_class, $subject, $matches_class, PREG_OFFSET_CAPTURE)) {
            $array_class = $matches_class;
        }
        for ($s = 0; $s < count($array_class[0]); $s++) {
            $names[] = $array_class[0][$s][0];
            $class_name_puro_string = preg_replace("/[c|C]lass/", "", $array_class[0][$s][0]);
            $class_name_puro_string = preg_replace("/\s/", "", $class_name_puro_string);
            if (stripos($class_name_puro_string, 'extend') === false) {
                $class_name_puro[] = str_replace("{", "", $class_name_puro_string) . "();";
            } else {
                $class_name_puro[] = substr($class_name_puro_string, 0, stripos($class_name_puro_string, 'extend')) . "();";
            }

            $class_name_string = preg_replace('/^get/', '', $array_class[0][$s][0]);
            $class_name_string = preg_replace("/[c|C]lass/", "", $class_name_string);
            $class_name_string = preg_replace("/\s/", "", $class_name_string);
            if (stripos($class_name_string, 'extend') === false) {
                $class_name[] = str_replace("{", "", $class_name_string);
            } else {
                $class_name[] = substr($class_name_string, 0, stripos($class_name_string, 'extend'));
            }
        }

        $tot = count($matches_class[0]);
        for ($i = 0; $i < $tot; $i++) {
            $offsets[] = strlen($matches_class[0][$i][0]) + ($matches_class[0][$i][1]);
        }
        $offsets[] = $totCaratteri;
        $conta = 0;
        $pattern = '/set[A-Za-z\_]*/';
        $array = array();
        $this->result_string[] = "&lt?php";
        $this->result_string[] = "include_once 'Class.DaoUtils.php';";
        $this->result_string[] = "include_once 'Class.Business.php';";
        $this->result_string[] = "class Dao extends DaoUtils{";
        for ($i = 0; $i < count($class_name); $i++) {
            $this->result_string[] = TAB . "private \$" . $class_name[$i] . TAB . "=" . TAB . "\"" . $class_name[$i] . "\";";
        }
        # -------------------------------------------------------------
        # This method creates the "Read" of CRUD operations.
        # -------------------------------------------------------------
        $this->readCreation($offsets, $subject, $class_name, $pattern, $class_name_puro);
        # -------------------------------------------------------------
        # This method creates the "Create" of CRUD operations.
        # -------------------------------------------------------------
        $this->createCreation($offsets, $subject, $class_name, $pattern);
        # -------------------------------------------------------------
        # This method creates the "Update" of CRUD operations.
        # -------------------------------------------------------------
        $this->updateCreation($offsets, $subject, $class_name, $pattern);
        # -------------------------------------------------------------
        $this->result_string[] = TAB . "public function execQuery(\$query){";
        $this->result_string[] = TAB . TAB . "return mysql_query(\$query);";
        $this->result_string[] = TAB . "}";
        $this->result_string[] = "}";
        # -------------------------------------------------------------
        $this->result_string[] = "?&gt";

        $fw = fopen($this->project_folder . "/Class.Persistence.php", "w");
        foreach ($this->result_string as $result_str) {
            $result_str = str_replace($open_php, "<?php", $result_str . A_CAPO);
            fwrite($fw, str_replace($close_php, "?>", $result_str));
        }
        fclose($fw);
        $this->result_string = array();
    }

    /**
     * This methods generates the "actions" methods.
     */
    function action_generator() {
        $open_php = $this->open_php;
        $close_php = $this->close_php;
        $result_string = "";
        $subject = file_get_contents($this->filename); //input
        $pattern_class = '/[C|c](lass)[\ ]+[a-zA-Z0-9\_\ ]*{/';
        if (preg_match_all($pattern_class, $subject, $matches_class, PREG_OFFSET_CAPTURE)) {
            $array_class = $matches_class;
        }
        for ($s = 0; $s < count($array_class[0]); $s++) {
            $names[] = $array_class[0][$s][0];
            $class_name_puro_string = preg_replace("/[c|C]lass/", "", $array_class[0][$s][0]);

            $class_name_puro_string = (preg_replace("/\s/", "", $class_name_puro_string));
            if (stripos($class_name_puro_string, 'extend') === false) {
                $class_name_puro[] = str_replace("{", "", $class_name_puro_string) . "();";
            } else {
                $class_name_puro[] = substr($class_name_puro_string, 0, stripos($class_name_puro_string, 'extend')) . "();";
            }

            $class_name_string = strtolower(preg_replace('/^set/', '', $array_class[0][$s][0]));
            $class_name_string = preg_replace("/[c|C]lass/", "", $class_name_string);
            $class_name_string = (preg_replace("/\s/", "", $class_name_string));
            if (stripos($class_name_string, 'extend') === false) {
                $class_name[] = str_replace("{", "", $class_name_string);
            } else {
                $class_name[] = substr($class_name_string, 0, stripos($class_name_string, 'extend'));
            }
        }

        for ($i = 0; $i < count($matches_class[0]); $i++) {
            $offsets[] = strlen($matches_class[0][$i][0]) + ($matches_class[0][$i][1]);
        }
        $conta = 0;
        $pattern = '/set[A-Za-z\_]*/';
        $array = array();
        $result_string[] = "&lt?php";
        $result_string[] = "include_once 'Class.Trigger.php';";
        $result_string[] = "include_once 'Class.Persistence.php';";
        $result_string[] = "class Action extends TriggerAction{";


        //-------------------------------------------------------------GET
        $conta = 0;
        for ($za = 0; $za < count($offsets); $za++) {

            $subject_class = @substr($subject, $offsets[$za], $offsets[$za + 1] - $offsets[$za]);
            if (preg_match_all($pattern, $subject_class, $matches)) {
                $array = $matches;
            }
            $result_string[] = "//-------------------------------------------------------------";
            $result_string[] = TAB . "public function get" . ucwords($class_name[$conta]) . "() {";
            $result_string[] = TAB . TAB . "\$dao = new Dao();";
            $result_string[] = TAB . TAB . "return \$dao->get" . ucwords($class_name[$conta]) . "();";
            $result_string[] = TAB . "}";
            $conta++;
        }

        //-------------------------------------------------------------NEW
        $conta = 0;
        for ($za = 0; $za < count($offsets); $za++) {

            $subject_class = @substr($subject, $offsets[$za], $offsets[$za + 1] - $offsets[$za]);
            if (preg_match_all($pattern, $subject_class, $matches)) {
                $array = $matches;
            }
            $result_string[] = "//-------------------------------------------------------------";
            $result_string[] = TAB . "public function new" . ucwords($class_name[$conta]) . "(\$" . $class_name[$conta] . ") {";
            $result_string[] = TAB . TAB . "\$dao = new Dao();";
            $result_string[] = TAB . TAB . "try {";
            $result_string[] = TAB . TAB . TAB . "mysql_query('START TRANSACTION');";
            $result_string[] = TAB . TAB . TAB . "\$insert_id = \$dao->new" . ucwords($class_name[$conta]) . "(\$" . $class_name[$conta] . ");";
            $result_string[] = TAB . TAB . TAB . "mysql_query('COMMIT');";
            $result_string[] = TAB . TAB . TAB . "return true;";
            $result_string[] = TAB . TAB . "} catch (Exception \$e) {";
            $result_string[] = TAB . TAB . TAB . "mysql_query('ROLLBACK');";
            $result_string[] = TAB . TAB . TAB . "\$this->setTrigger(\$e);";
            $result_string[] = TAB . TAB . TAB . "\$this->launchTrigger();";
            $result_string[] = TAB . TAB . "}";
            $result_string[] = TAB . "}";
            $conta++;
        }
        //-------------------------------------------------------------EDIT
        $conta = 0;
        for ($za = 0; $za < count($offsets); $za++) {

            $subject_class = @substr($subject, $offsets[$za], $offsets[$za + 1] - $offsets[$za]);
            if (preg_match_all($pattern, $subject_class, $matches)) {
                $array = $matches;
            }
            $result_string[] = "//-------------------------------------------------------------";
            $result_string[] = TAB . "public function edit" . ucwords($class_name[$conta]) . "(\$" . $class_name[$conta] . ") {";
            $result_string[] = TAB . TAB . "\$dao = new Dao();";
            $result_string[] = TAB . TAB . "try {";
            $result_string[] = TAB . TAB . TAB . "mysql_query('START TRANSACTION');";
            $result_string[] = TAB . TAB . TAB . "\$insert_id = \$dao->edit" . ucwords($class_name[$conta]) . "(\$" . $class_name[$conta] . ",\$id);";
            $result_string[] = TAB . TAB . TAB . "mysql_query('COMMIT');";
            $result_string[] = TAB . TAB . TAB . "return true;";
            $result_string[] = TAB . TAB . "} catch (Exception \$e) {";
            $result_string[] = TAB . TAB . TAB . "mysql_query('ROLLBACK');";
            $result_string[] = TAB . TAB . TAB . "\$this->setTrigger(\$e);";
            $result_string[] = TAB . TAB . TAB . "\$this->launchTrigger();";
            $result_string[] = TAB . TAB . "}";
            $result_string[] = TAB . "}";
            $conta++;
        }


        $result_string[] = "}";
        $result_string[] = "?&gt";
        $fw = fopen($this->project_folder . "/Class.Actions.php", "w");
        foreach ($result_string as $result_str) {
            $result_str = str_replace($open_php, "<?php", $result_str . A_CAPO);
            fwrite($fw, str_replace($close_php, "?>", $result_str));
        }
        fclose($fw);
    }

    /**
     * This method generates a simple email class. 
     */
    function email_generator() {
        $filename = $this->project_folder . "/Class.Email.php";
        $fw = fopen($filename, "w");
        $html = "<?php
        Class Email {

        private \$recipients;
        private \$from;
        private \$message;
        private \$subject;
        private \$headers;
        private \$reply_to;

        public function getReply_to() {
            return \$this->reply_to;
        }

        public function setReply_to(\$reply) {
            \$this->reply_to = \$reply;
        }


        public function getRecipients() {
            return \$this->recipients;
        }

        public function setRecipients(\$recipients) {
            \$this->recipients = \$recipients;
        }

        public function getFrom() {
            return \$this->from;
        }

        public function setFrom(\$from) {
            \$this->from = \$from;
        }

        public function getMessage() {
            return \$this->message;
        }

        public function setMessage(\$message) {
            \$this->message = \$message;
        }

        public function getSubject() {
            return \$this->subject;
        }

        public function setSubject(\$subject) {
            \$this->subject = \$subject;
        }

        public function getHeaders() {
            return \$this->headers;
        }

        public function setHeaders(\$headers) {
            \$this->headers = \$headers;
        }

        function __construct() {
            \$this->headers = 'From: \$from' . \"\r\n\" . 'Reply-To: \$reply_to' . \"\r\n\" . 'X-Mailer: PHP/' . phpversion();
        }

        public function sendMail() {

            if (is_array(\$this->recipients)) {
                foreach (\$this->recipients as \$recipient) {
                    mail(\$recipient, \$this->from, \$this->message, \$this->headers);
                }
            } else {
                mail(\$this->recipients, \$this->from, \$this->message, \$this->headers);
            }
        }

    }
?>";
        fwrite($fw, $html);
        fclose($fw);
    }

    /**
     * This method generates a simple method for fields validations.
     */
    function validation_generator() {
        $open_php = $this->open_php;
        $close_php = $this->close_php;
        $filename = $this->project_folder . "/Class.Validation.php";
        $fw = fopen($filename, "w");
        $result_string[] = "<?php";
        $result_string[] = "class Validation{";

        $result_string[] = TAB . "public function validate(array \$array_validation){";
        $result_string[] = TAB . TAB . "if(is_array(\$array_validation)){";
        $result_string[] = TAB . TAB . TAB . "foreach (\$array_validation as \$field_to_validate){";
        $result_string[] = TAB . TAB . TAB . TAB . "if(empty(\$field_to_validate)){";
        $result_string[] = TAB . TAB . TAB . TAB . TAB . "return false;";
        $result_string[] = TAB . TAB . TAB . TAB . "}";
        $result_string[] = TAB . TAB . TAB . "}";
        $result_string[] = TAB . TAB . TAB . "return true;";
        $result_string[] = TAB . TAB . "}";
        $result_string[] = TAB . "}";
        $result_string[] = "}";
        $result_string[] = "?>";
        foreach ($result_string as $result_str) {
            $result_str = str_replace($open_php, "<?php", $result_str . A_CAPO);
            fwrite($fw, str_replace($close_php, "?>", $result_str));
        }
        fclose($fw);
    }

    /**
     * Generate the db connection class.
     * @param type $username
     * @param type $password
     * @param type $server
     * @param type $dbname
     */
    function dao_utils_generator($username, $password, $server, $dbname) {
        $open_php = $this->open_php;
        $close_php = $this->close_php;
        $result_string = array();
        $result_string[] = "<?php";
        $result_string[] = "class DaoUtils {";
        $result_string[] = TAB . "function __construct() {";
        $result_string[] = TAB . TAB . "\$server = '$server';";

        $result_string[] = TAB . TAB . "\$username = \"" . $username . "\";";
        $result_string[] = TAB . TAB . "\$password = \"" . $password . "\";";
        $result_string[] = TAB . TAB . "\$dbname = \"" . $dbname . "\";";
        $result_string[] = TAB . TAB . "\$link = mysql_connect(\$server, \$username, \$password);";
        $result_string[] = TAB . TAB . "if (!\$link) {";
        $result_string[] = TAB . TAB . TAB . "die('Could not connect: ' . mysql_error());";
        $result_string[] = TAB . TAB . "}";
        $result_string[] = TAB . TAB . "mysql_select_db(\$dbname);";
        $result_string[] = TAB . "}";



        $result_string[] = "}";
        $result_string[] = "?>";
        $fw = fopen($this->project_folder . "/Class.DaoUtils.php", "w");
        foreach ($result_string as $result_str) {
            $result_str = str_replace($open_php, "<?php", $result_str . A_CAPO);
            fwrite($fw, str_replace($close_php, "?>", $result_str));
        }
        fclose($fw);
    }

    /**
     * Generates a class to do a lot of things...not implemented yet.
     */
    function trigger_generator() {
        $open_php = $this->open_php;
        $close_php = $this->close_php;
        $result_string = array();
        $result_string[] = "<?php";
        $result_string[] = "Class TriggerAction{";
        $result_string[] = TAB . "public function setTrigger(){";
        $result_string[] = TAB . "}";
        $result_string[] = TAB . "public function launchTrigger(){";
        $result_string[] = TAB . "}";
        $result_string[] = "}";
        $result_string[] = "?>";
        $fw = fopen($this->project_folder . "/Class.Trigger.php", "w");
        foreach ($result_string as $result_str) {
            $result_str = str_replace($open_php, "<?php", $result_str . A_CAPO);
            fwrite($fw, str_replace($close_php, "?>", $result_str));
        }
        fclose($fw);
    }

    /**
     * Generates some default constants. 
     */
    function constants_generator() {
        $open_php = $this->open_php;
        $close_php = $this->close_php;
        $result_string = array();
        $result_string[] = "<?php";
        $result_string[] = 'define("ZERO_RESULTS","Non sono presenti dati.");';
        $result_string[] = 'define("MANDATORY_FIELDS","Compila i campi obbligatori.");';
        $result_string[] = 'define("RECORD_INSERTED","Record inserito correttamente!");';
        $result_string[] = 'define("RECORD_UPDATED","Record aggiornato correttamente!");';
        $result_string[] = 'define("RECORD_DELETED","Record cancellato correttamente!");';
        $result_string[] = 'define("STATE_ERROR","Errore!");';
        $result_string[] = 'define("NEWLINE_WEB","<br/>");';
        $result_string[] = 'define("NEWLINE_FILE","\r\n");';
        $result_string[] = "?>";
        $fw = fopen($this->project_folder . "/Constants.php", "w");
        foreach ($result_string as $result_str) {
            $result_str = str_replace($open_php, "<?php", $result_str . A_CAPO);
            fwrite($fw, str_replace($close_php, "?>", $result_str));
        }
        fclose($fw);
    }

    /**
     * Generate a class for Html handling.
     */
    function html_generator() {
        $open_php = $this->open_php;
        $close_php = $this->close_php;
        $result_string = array();
        $result_string[] = "<?php";
        $result_string[] = "class Html {";
        $result_string[] = "private \$description;";
        $result_string[] = "private \$keywords;";
        $result_string[] = "function startHtml(){";
        $result_string[] = "?><!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//IT\"";
        $result_string[] = "\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">";
        $result_string[] = "<html xmlns=\"http://www.w3.org/1999/xhtml\"><?";
        $result_string[] = "}";
        $result_string[] = "function head() {";
        $result_string[] = "?><head>";
        $result_string[] = "<title>Cld Professional - professionisti della selezione</title>";
        $result_string[] = "<?= \$this->headerScripts() ?>";
        $result_string[] = "<meta name=\"author\" content=\"Belotti Alberto\" />";
        $result_string[] = "<meta http-equiv=\"reply-to\" content=\"albertopuntobelotti@gmail.com\" />";
        $result_string[] = "<meta http-equiv=\"pragma\" content=\"no-cache\" />";
        $result_string[] = "<meta name=\"description\" content='<?= \$this->description ?>' />";
        $result_string[] = "<meta name=\"keywords\" content='<?= \$this->keywords ?>' />";
        $result_string[] = "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />";
        $result_string[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"../assets/css/style.css\"/>    ";
        $result_string[] = "</head>";
        $result_string[] = "<?";
        $result_string[] = "}";
        $result_string[] = "function headerScripts() {";
        $result_string[] = "\$scripts = array(\"https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js\");";
        $result_string[] = "if (is_array(\$scripts)) {";
        $result_string[] = "foreach (\$scripts as \$script) {";
        $result_string[] = "?><script type=\"text/javascript\" src=\"<?= \$script ?>\"></script><?";
        $result_string[] = "}";
        $result_string[] = "}";
        $result_string[] = "}";
        $result_string[] = "function startBody(){";
        $result_string[] = "?><body><?";
        $result_string[] = "}";
        $result_string[] = "function endBody(){";
        $result_string[] = "?></body><?";
        $result_string[] = "}";
        $result_string[] = "function startHeader() {";
        $result_string[] = "?><div id=\"header\"></div><?";
        $result_string[] = "}";
        $result_string[] = "function startContainer(\$header = false, \$menu = false) {";
        $result_string[] = "?>";
        $result_string[] = "<div id=\"container\">";
        $result_string[] = "<?";
        $result_string[] = "\$header ? \$this->startHeader() : \"\";";
        $result_string[] = "?>";
        $result_string[] = "<?";
        $result_string[] = "}";
        $result_string[] = "function endContainer() {";
        $result_string[] = "?></div><?";
        $result_string[] = "}";
        $result_string[] = "function endHtml(){";
        $result_string[] = "?></html><?";
        $result_string[] = "}";
        $result_string[] = "}";
        $result_string[] = "?>";
        $fw = fopen($this->project_folder . "/Class.Html.php", "w");
        foreach ($result_string as $result_str) {
            $result_str = str_replace($open_php, "<?php", $result_str . A_CAPO);
            fwrite($fw, str_replace($close_php, "?>", $result_str));
        }
        fclose($fw);
    }

    /**
     * Generates some examples folders that show how it works.
     */
    function generate_views() {
        $folder = $this->project_folder;
        include_once $this->project_folder . '/Class.Actions.php';
        $class = new ReflectionClass('Action');
        $methods = $class->getMethods();
        $arrayMethod = array();
        $array_search = array("_");
        $array_replace = array("-");
        foreach ($methods as $method) {
            $arrayMethod[] = $method->name;
        }
        $arrayGetMethods = preg_grep('/^get.*/', $arrayMethod);
        foreach ($arrayGetMethods as $arrayGetMethod) {
            $arrayGetMethodPure = $arrayGetMethod;
            $arrayGetMethod = preg_replace('/^get/', '', $arrayGetMethod);
            $arrayGetMethod = str_replace($array_search, $array_replace, $arrayGetMethod);
            $arrayGetMethod = strtolower($arrayGetMethod);
            if (@mkdir($folder . "/" . $arrayGetMethod, 0777)) {
                $open_php = "&lt?php";
                $close_php = "?&gt";
                $result_string = array();
                $result_string[] = "<?php";
                $result_string[] = "include_once '../Class.Actions.php';";
                $result_string[] = "include_once '../Constants.php';";
                $result_string[] = "include_once '../Class.Html.php';";
                $result_string[] = "\$html = new Html();";
                $result_string[] = "\$action = new Action();";
                $result_string[] = "\$objects = \$action->" . $arrayGetMethodPure . "();";
                $result_string[] = "\$html->startHtml();";
                $result_string[] = "\$html->head();";
                $result_string[] = "\$html->startBody();";
                $result_string[] = "\$html->startContainer(true,true);";
                $result_string[] = "if(is_array(\$objects)){";
                include_once $folder . '/Class.Business.php';
                $arrayGetMethodPure = preg_replace('/^get/', '', $arrayGetMethodPure);
                $arrayGetMethodPure = strtolower($arrayGetMethodPure);
                $actionz = new $arrayGetMethodPure();
                $class = new ReflectionClass($actionz);
                $methods = $class->getMethods();
                foreach ($methods as $method) {
                    $arrayMethodman[] = $method->name;
                }
                $arrayGetMethodsman = preg_grep('/^get.*/', $arrayMethodman);
                $z = 0;

                $result_string[] = "foreach (\$objects as \$object){";

                foreach ($arrayGetMethodsman as $arrayGetMethodman) {
                    $result_string[] = "echo \$object->" . $arrayGetMethodman . "();";
                }
                $result_string[] = "echo NEWLINE_WEB;";
                $result_string[] = "}";
                $result_string[] = "}else{";
                $result_string[] = "echo ZERO_RESULTS;";
                $result_string[] = "echo NEWLINE_WEB;";
                $result_string[] = "}";
                $result_string[] = "\$html->endContainer();";
                $result_string[] = "\$html->endBody();";
                $result_string[] = "\$html->endHtml();";
                $result_string[] = "?>";
                $fw = fopen($folder . "/" . $arrayGetMethod . "/index.php", "w");
                foreach ($result_string as $result_str) {
                    $result_str = str_replace($open_php, "<?php", $result_str . A_CAPO);
                    fwrite($fw, str_replace($close_php, "?>", $result_str));
                }
                fclose($fw);
                $arrayGetMethodsman = array();
                $arrayGetMethod = array();
                $arrayGetMethodPure = array();
                $arrayMethodman = array();
                $methods = array();
            } else {
                die("Attenzione! <em><font color='red' weight='bold'>" . $arrayGetMethod . "</font></em> directory gia esistente");
            }
        }
    }

    /**
     * Generates assets folders with some files in. 
     */
    function generate_assets() {
        $folder = $this->project_folder;
        $array_assets = array('css' => 'style.css', 'images' => '', 'scripts' => '');
        if (@mkdir($folder . "/assets", 0777)) {
            foreach ($array_assets as $asset => $value) {
                if (@mkdir($folder . "/assets/$asset", 0777)) {
                    if ($asset == "css") {
                        copy("style.css", $folder . "/assets/$asset/$value");
                    }
                    if ($asset == "scripts") {
                        copy("jquery-1.8.3.js", $folder . "/assets/$asset/jquery-1.8.3.js");
                    }
                }
            }
        }
    }

    function readCreation($offsets, $subject, $class_name, $pattern, $class_name_puro) {
        $conta = 0;
        for ($z = 0; $z < count($offsets) - 1; $z++) {

            $subject_class = substr($subject, $offsets[$z], $offsets[$z + 1] - $offsets[$z]);
            if (preg_match_all($pattern, $subject_class, $matches)) {
                $array = $matches;
            }

            $this->result_string[] = "//-------------------------------------------------------------";
            $this->result_string[] = TAB . "public function get" . ucwords($class_name[$conta]) . "() {";
            $this->result_string[] = TAB . TAB . TAB . "\$query =   \"select * from \$this->" . $class_name[$conta] . "\";";
            $this->result_string[] = TAB . TAB . TAB . "\$result = \$this->execQuery(\$query);";
            $this->result_string[] = TAB . TAB . TAB . "\$array = array();";
            $this->result_string[] = TAB . TAB . TAB . "while (\$r = mysql_fetch_array(\$result)) {";
            $this->result_string[] = TAB . TAB . TAB . TAB . "\$" . $class_name[$conta] . " = new " . $class_name_puro[$conta];

            for ($i = 0; $i < count($array[0]); $i++) {
                $nome_del_campo = strtolower(preg_replace('/^set/', '', $array[0][$i]));
                $this->result_string[] = TAB . TAB . TAB . TAB . "\$" . $class_name[$conta] . "->" . $array[0][$i] . "(\$r['" . $nome_del_campo . "']);";
            }
            $this->result_string[] = TAB . TAB . TAB . TAB . "\$array[] = \$" . $class_name[$conta] . ";";
            $this->result_string[] = TAB . TAB . TAB . "}";
            $this->result_string[] = TAB . TAB . TAB . "if (sizeof(\$array) >= 1) {";
            $this->result_string[] = TAB . TAB . TAB . TAB . "return \$array;";
            $this->result_string[] = TAB . TAB . TAB . "}";
            $this->result_string[] = TAB . TAB . TAB . "return false;";
            $this->result_string[] = TAB . "}";
            $conta++;
        }
    }

    function createCreation($offsets, $subject, $class_name, $pattern) {
        $conta = 0;
        for ($za = 0; $za < count($offsets) - 1; $za++) {

            $subject_class = @substr($subject, $offsets[$za], $offsets[$za + 1] - $offsets[$za]);
            if (preg_match_all($pattern, $subject_class, $matches)) {
                $array = $matches;
            }
            $this->result_string[] = "//-------------------------------------------------------------";
            $this->result_string[] = TAB . "public function new" . ucwords($class_name[$conta]) . "(\$" . $class_name[$conta] . ") {";
            $this->result_string[] = TAB . TAB . TAB . "\$query =   \"insert into \$this->" . $class_name[$conta] . " set";
            for ($i = 0; $i < count($array[0]); $i++) {

                $nome_del_campo = strtolower(preg_replace('/^set/', '', $array[0][$i]));

                if ($i == count($array[0]) - 1)
                    $this->result_string[] = TAB . TAB . TAB . TAB . $nome_del_campo . " = '\".\$" . $class_name[$conta] . "->get" . ucwords($nome_del_campo) . "().\"'\";" . A_CAPO;
                else
                    $this->result_string[] = TAB . TAB . TAB . TAB . $nome_del_campo . " = '\".\$" . $class_name[$conta] . "->get" . ucwords($nome_del_campo) . "().\"',";
            }

            $this->result_string[] = TAB . TAB . TAB . "\$q = str_replace(\"'NULL'\", \"NULL\", \$query);";
            $this->result_string[] = TAB . TAB . TAB . "\$result = \$this->execQuery(\$q);";
            $this->result_string[] = TAB . TAB . TAB . "return \$result;";
            $this->result_string[] = TAB . "}";
            $conta++;
        }
    }

    function updateCreation($offsets, $subject, $class_name, $pattern) {
        $conta = 0;
        for ($za = 0; $za < count($offsets) - 1; $za++) {
            $primary = array();
            $primary_q = "SELECT column_name, table_name
                         FROM information_schema.columns
                         WHERE table_schema =  '" . $table_schema . "'
                         AND table_name =  '" . $class_name[$conta] . "'
                         AND (column_key =  'PRI' or column_key =  'MUL')";
            $result = mysql_query($primary_q);

            while ($row = mysql_fetch_assoc($result)) {
                $primary[] = $row['column_name'];
            }

            $subject_class = @substr($subject, $offsets[$za], $offsets[$za + 1] - $offsets[$za]);
            if (preg_match_all($pattern, $subject_class, $matches)) {
                $array = $matches;
            }
            $this->result_string[] = "//-------------------------------------------------------------";
            $this->result_string[] = TAB . "public function edit" . ucwords($class_name[$conta]) . "(\$" . $class_name[$conta] . ",\$id) {";
            $this->result_string[] = TAB . TAB . TAB . "\$query =   \"update table \$this->" . $class_name[$conta] . " set";
            for ($i = 0; $i < count($array[0]); $i++) {
                $nome_del_campo = strtolower(preg_replace('/^set/', '', $array[0][$i]));

                if ($i == count($array[0]) - 1) {
                    $this->result_string[] = TAB . TAB . TAB . TAB . $nome_del_campo . " = '\".\$" . $class_name[$conta] . "->get" . ucwords($nome_del_campo) . "().\"'\";" . A_CAPO;
                    $this->result_string[] = TAB . TAB . TAB . "\$query.=   \" where id='\".\$id.\"' \";";
                }
                else
                    $this->result_string[] = TAB . TAB . TAB . TAB . $nome_del_campo . " = '\".\$" . $class_name[$conta] . "->get" . ucwords($nome_del_campo) . "().\"',";
            }

            $this->result_string[] = TAB . TAB . TAB . "\$q = str_replace(\"'NULL'\", \"NULL\", \$query);";
            $this->result_string[] = TAB . TAB . TAB . "\$result = \$this->execQuery(\$q);";
            $this->result_string[] = TAB . TAB . TAB . "return \$result;";
            $this->result_string[] = TAB . "}";
            $conta++;
        }
    }

}
