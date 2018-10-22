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


$sql="SELECT pubkey_signing,account FROM usuarios WHERE id=$userid";
$res=$con->query($sql);
$row=$res->fetch_assoc();
$pubkey=$row["pubkey_signing"];
$account=$row["account"];



$sql="SELECT privkey,privkey_signing,pubkey,pubkey_signing FROM config";
$res=$con->query($sql);
$row=$res->fetch_assoc();
$privkey=$row["privkey"];
$privkey_signing=$row["privkey_signing"];
$pubkey_signing=$row["pubkey_signing"];



$o_res=extraer_certificado($certificadoA,$pubkey,$privkey);

if(isset($o_res->e))
{
	echo json_encode($o_res);
	die;
}

$o_certA=$o_res;


//$account=$o_certA->account;

$share2=$o_certA->share;


$sql="SELECT account FROM usuarios WHERE id=$userid";
$res=$con->query($sql);
if(!$row=$res->fetch_assoc())
{
    $outp="{";
        $outp.="\"e\":\"User not authorized\",";
        $outp.="\"message\":\"User not authorized\"";
    $outp.="}";
    echo($outp);
    die;
}

$cuenta=$row["account"];

$sql="INSERT INTO blobs(tipo,owner_id,share) VALUES('account_thumbnails_id',$cuenta,'$share2')";
//echo $sql; die;
$con->query($sql);



$cert="$certA";
/*$rsa->loadKey(str_replace("\r","",str_replace("\n","",$privkey_signing))); // private key
$rsa->setHash("sha256");
$rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
//echo $cert;
//$signature = base64_encode($rsa->sign($certB));
$signature = $rsa->sign($cert);*/

openssl_sign($cert,$signature,$privkey_signing,OPENSSL_ALGO_SHA256);
$signature = base64_encode($signature);

$outp="{";
$outp.="\"e\":\"OK\",";
$outp.="\"cert\":\"".$signature."\"";
$outp.="}";
echo($outp);

?>