<?php
# -----------------------------------------
# INCLUDES
# -----------------------------------------
include_once dirname(__FILE__).'/include/mySqlConnection.php';
include_once dirname(__FILE__).'/master_of_puppets.php';
# -----------------------------------------
# CONSTANTS
# -----------------------------------------
define("MSG_DATABASE","Attenzione! <em>Dati database obbligatori!!</em>");
define("MSG_PROJECT" ,"Warning! <em>Specify project name first!</em>");
define("MSG_SCHEMA"  ,"Warning! <em>You must specify schema name before!</em>");
define("MASTER_SCHEMA"  ,"information_schema");
# -----------------------------------------
# CONTROLS
# -----------------------------------------
if(!isset($_REQUEST['schema_name']))
    die(MSG_SCHEMA);
if(!isset($_REQUEST['username']) || !isset($_REQUEST['host']) || !isset($_REQUEST['password']))
    die(MSG_DATABASE);
if(isset($_REQUEST['table_name']))
    $table_name 		= $_REQUEST['table_name'];
# -----------------------------------------
$project_name = isset($_REQUEST['project_name']) ?  $_REQUEST['project_name']:"project".rand(1,10000);
define("PROJECT_FOLDER",$project_name);
$username = $_REQUEST['username']; 
$server = $_REQUEST['host']; 
$password = $_REQUEST['password']; 
$table_schema = $_REQUEST['schema_name'];
$filename = dirname(__FILE__)."/class_generated.txt";
$filename_db = dirname(__FILE__)."/".PROJECT_FOLDER . "/Class.Business.php";
$a_capo = "\r\n";
# -----------------------------------------
# MYSQL CONNECT
# -----------------------------------------
$connection = new Connection();
$connection->host = $server;
$connection->username = $username;
$connection->password = $password;
$connection->dbname = MASTER_SCHEMA;
$connection->connect();
# -----------------------------------------
# THIS IS THE MASTER QUERY. IT TAKES ALL DATAS FROM INFORMATION SCHEMA. 
# YOU MUST HAVE ROOT PRIVILEGES!!!
# -----------------------------------------
$query =  "select table_name,column_name from(
           select '&lt?php'as table_name,'' as column_name,'0a' as ordinamento
           from dual
		   union
           select \"include_once \$_SERVER['DOCUMENT_ROOT'] . '/common/config.common.php';\" as table_name,'' as column_name,'0b' as ordinamento
           from dual
           union
           select concat('class ',table_name,'{')as table_name,'' as column_name,concat(table_name,'1','0')as ordinamento
           from columns
           where table_schema = '".$table_schema."'
           and table_name like '%".$table_name."%'
           union
           select table_name,column_name,ordinamento
           from
           (
               select '     'as table_name ,concat('private $',column_name,';')as column_name,concat(table_name,'2',ordinal_position)as ordinamento
               from columns
               where table_schema = '".$table_schema."'
               and table_name like '%".$table_name."%'
               order by ordinamento,table_schema,table_name
           )fields
           union
           select '}' as table_name,'' as column_name,concat(table_name,'3','0')as ordinamento
           from columns
           where table_schema = '".$table_schema."'
           and table_name like '%".$table_name."%'
           order by ordinamento
           )base_table";
# -----------------------------------------
$result = mysql_query($query);
while($row = mysql_fetch_assoc($result)){
    $array_result[] = $row['table_name'] . $row['column_name'].$a_capo;
}
#--------------------------------------------------------
# In this step i'm creating the classes (getters and setters) in a file
#--------------------------------------------------------
$fw = fopen($filename, "w");
foreach ($array_result as $string_result){
    $string_result = str_replace('&lt?php', '<?php', $string_result);
    fwrite($fw, $string_result);
}
fclose($fw);
#--------------------------------------------------------
# CREATE THE PROJECT FOLDER - WATCH OUT THE PERMISSIONS
#--------------------------------------------------------
if(file_exists(PROJECT_FOLDER)){
    die("Attenzione! <em><font color='red' weight='bold'>".PROJECT_FOLDER."</font></em> Directory gia esistente!");
}
if(php_uname('s')!= "Linux"){
    $old = umask(0);
    if(!@mkdir(PROJECT_FOLDER, 0777,TRUE)){
        die("Attenzione! <em><font color='red' weight='bold'>".PROJECT_FOLDER."</font></em> Impossibile creare la directory");
    }
    umask($old);
    chmod(PROJECT_FOLDER,0777);
}else{
    if(!@mkdir(PROJECT_FOLDER, 0777,TRUE)){
        die("Attenzione! <em><font color='red' weight='bold'>".PROJECT_FOLDER."</font></em> Impossibile creare la directory");
    }
}
#this is awesome!--------------------------------------
$masterOfPuppets = new MasterOfPuppets();
$masterOfPuppets->project_folder = PROJECT_FOLDER;
$masterOfPuppets->filename = $filename;
$masterOfPuppets->table_schema = $table_schema;
# -------------------------------------------------------
$masterOfPuppets->getter_and_setter();
$masterOfPuppets->db_object_generator();
$masterOfPuppets->action_generator();
$masterOfPuppets->dao_utils_generator($username,$password,$server,$table_schema);
$masterOfPuppets->email_generator();
$masterOfPuppets->validation_generator();
$masterOfPuppets->trigger_generator();
$masterOfPuppets->constants_generator();
$masterOfPuppets->html_generator();
$masterOfPuppets->generate_views();
$masterOfPuppets->generate_assets();
#--------------------------------------------------------
unlink($filename);
#--------------------------------------------------------
echo "Yes! You did it! Have a nice day<br/>";
?>