<?php 

error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");



header("Content-Type: application/json; charset=UTF-8");
error_reporting(E_ERROR | E_WARNING | E_PARSE);


include_once("./db.php");
include_once("./inc-log.php");

$con = new mysqli($g_db_server, $g_db_user, $g_db_password,$g_db_name) or die("Error connecting to database");

include('inc_permisos.php');


set_include_path($_SERVER["DOCUMENT_ROOT"].'/phpseclib');
//include('Crypt/RSA.php');
include('Crypt/AES.php');
include('Crypt/Random.php');




$userid=$con->real_escape_string($_REQUEST['userid']);
$list=$_REQUEST['list'];



$sql="SELECT pubkey_signing,pubkey,account FROM usuarios WHERE id=$userid";
$res=$con->query($sql);
$row=$res->fetch_assoc();
$pubkey=$row["pubkey_signing"];
$user_publickey=$row["pubkey"];
$account=$row["account"];


$sql="SELECT privkey,privkey_signing,pubkey,pubkey_signing FROM config";
$res=$con->query($sql);
$row=$res->fetch_assoc();
$privkey=$row["privkey"];
$privkey_signing=$row["privkey_signing"];



$shares="[";

    $i=0;
foreach($list as $msgid)
{
    $sql="SELECT * FROM other_keyshares WHERE id='$msgid' AND tipo='message' AND (cuenta=$account OR cuenta=-1)";
    $res=$con->query($sql);
    if($row=$res->fetch_assoc())
    {
        $share=$row["share"];
        openssl_public_encrypt($share,$encrypted,$user_publickey,OPENSSL_PKCS1_OAEP_PADDING);
        $encrypted=base64_encode($encrypted);
        
        //$shares[]=[$msgid,$encrypted];
        if($i>0)
        {
            $shares.=",";
        }
        $shares.="{\"msgid\":\"$msgid\",\"share\":\"$encrypted\"}";
        
        $i++;    
    }
}
$shares.="]";



echo "{";
echo "\"e\":\"OK\",";
echo "\"share\":$shares";

echo "}";

?>