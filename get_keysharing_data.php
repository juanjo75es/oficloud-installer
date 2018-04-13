<?php

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

error_reporting(E_ERROR | E_WARNING | E_PARSE);

$host='//'.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']);

include_once("./db.php");
$con = mysql_connect($g_db_server, $g_db_user, $g_db_password) or die("Error connecting to database");
mysql_select_db($g_db_name, $con) or die("Error selecting database");



$sql="SELECT * FROM config";
$res=mysql_query($sql);
if(!$_REQUEST["token"] || (!($row=mysql_fetch_assoc($res))) || $row["tokenkey"]!=$_REQUEST["token"])
{
    $outp="{";
    $outp.="\"e\":\"Invalid token according to previous keysharing server ($host)\"";    
    $outp.="}";
    echo($outp);
    die;
}


require_once('include/fkmysqldump.php');

function dump($table)
{
    //Connects to mysql server
    global $g_db_name;
    

    //Set encoding
    mysql_query("SET CHARSET utf8");
    mysql_query("SET NAMES 'utf8' COLLATE 'utf8_general_ci'");

    //Creates a new instance of FKMySQLDump: it exports without compress and base-16 file
    $dumper = new FKMySQLDump($g_db_name,'fk_dump.sql',false,false);

    $params = array(
        'skip_structure' => TRUE, // if set true = only data is in result
        //'skip_data' => TRUE, // if set true = only structure and FKs are in result
    );

    //Make dump
    return $dumper->doFKDump($table,$params);

}


/********EXPORT DATABASE CONTENTS*************/
$keyserverdb=$g_db_name;

$output=array();
$res=null;

$output=dump("emails_importados");
$semails=implode("\\n",$output);
$output=dump("directorios");
$sdirectorios=implode("\\n",$output);
$output=dump("keyshares");
$skeyshares=implode("\\n",$output);
$output=dump("permisos");
$spermisos=implode("\\n",$output);
$output=dump("usuarios");
$susuarios=implode("\\n",$output);
$output=dump("cuentas");
$scuentas=implode("\\n",$output);

$dump="[\"".urlencode($sdirectorios)."\"";
$dump.=",\"".urlencode($skeyshares)."\"";
$dump.=",\"".urlencode($semails)."\"";
$dump.=",\"".urlencode($spermisos)."\"";
$dump.=",\"".urlencode($susuarios)."\"";
$dump.=",\"".urlencode($scuentas)."\"";
$dump.="]";


//$dump="{\"directorios\":\"".urlencode($sql)."\"}";
$outp="{";
$outp.="\"e\":\"OK\",";
$outp.="\"dump\":$dump";
$outp.="}";
echo($outp);

?>