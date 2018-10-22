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
include('inc_certificados.php');

set_include_path($_SERVER["DOCUMENT_ROOT"].'/phpseclib');
//include('Crypt/RSA.php');
include('Crypt/AES.php');
include('Crypt/Random.php');




$userid=$con->real_escape_string($_REQUEST['userid']);
$certificadoA = $con->real_escape_string($_REQUEST['cert']);


//$rsa = new Crypt_RSA();

$certificadoA=base64_decode($certificadoA);



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

$o_res=extraer_certificado($certificadoA,$pubkey,$privkey,$certA);

if(isset($o_res->e))
{
	echo json_encode($o_res);
	die;
}

$o_certA=$o_res;

$chid=$o_certA->channel;
$user_email=$o_certA->user;



function arreglar_clave($k)
{
	$res=str_replace('\n',"",$k);
	$res=str_replace("\n","",$res);
	$res=str_replace("-----BEGIN PUBLIC KEY-----","",$res);
	$res=str_replace("-----END PUBLIC KEY-----","",$res);
	
	return $res;
}


//$user_publickey=arreglar_clave($user_publickey);


//echo "$user_publickey<br>";

$sql="SELECT * FROM other_keyshares WHERE id='$chid' AND tipo='canal'";
$res=$con->query($sql);
if(!$row=$res->fetch_assoc())
{
	echo "{";
    echo "\"e\":\"KSERROR channel doesn't exist\",";
    echo "\"cert\":\"\"";
    echo "}";
    die;
}

$sql="SELECT * FROM usuarios WHERE nick='$user_email' AND estado=1 AND account=$account";
$res=$con->query($sql);
if(!$row=$res->fetch_assoc())
{
	echo "{";
    echo "\"e\":\"KSERROR email doesn't exist\",";
    echo "\"cert\":\"\"";
    echo "}";
    die;
}
$newuser_id=$row["id"];

$sql="INSERT INTO other_keyshares_subscriptions(keysh_id,usuario,cuenta) VALUES('$chid',$newuser_id,$account)";
//echo $sql;die;
$con->query($sql);



$cert="$certA";
/*$rsa->loadKey($privkey_signing); // private key
$rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
$signature = base64_encode($rsa->sign($cert));*/
openssl_sign($cert,$signature,$privkey_signing,OPENSSL_ALGO_SHA256);
$signature = base64_encode($signature);


echo "{";
echo "\"e\":\"OK\",";
echo "\"cert\":\"$signature\"";
echo "}";

?>