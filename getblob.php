<?php 

error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");



header("Content-Type: application/json; charset=UTF-8");
error_reporting(E_ERROR | E_PARSE);


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



$sql="SELECT pubkey_signing,pubkey FROM usuarios WHERE id=$userid";
$res=$con->query($sql);
$row=$res->fetch_assoc();
if($row)
{
	$pubkey=$row["pubkey_signing"];
	$user_publickey=$row["pubkey"];	
}
else{
	$pubkey=$_REQUEST["user_pubkey_signing"];
	$user_publickey=$_REQUEST["user_pubkey"];	
}


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

$cert_no_encriptado=false;
if(!isset($certA) || $certA=="")
	{		
		$cert_no_encriptado=true;
		$acertificadoA=explode('@#@#@$$',$certificadoA);
		$certificado_encriptado=$acertificadoA[0];
		$certA=$certificado_encriptado;
		$o_certA=json_decode($certA);
	}

$id=$o_certA->auxid;
$tipo=$o_certA->type;

if(!$id)
{
	echo "{";
	echo "\"message\":\"KSERROR Unknown error $certA\",";
	echo "\"cert\":\"\"";
	echo "}";
	die;
}


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

$sql="SELECT * FROM blobs WHERE owner_id=$id AND tipo='$tipo'";
$res=$con->query($sql);
if(!$res)
{
	//echo "cert: $certificado_encriptado firma: $firma certa: $certA";die;
	//echo $;
	echo "{";
	echo "\"message\":\"KSERROR Unexpected error\",";
	echo "\"cert\":\"\"";
	echo "}";
	die;	
}

if($row=$res->fetch_assoc())
{
	$share=$row["share"];
}


openssl_public_encrypt($share,$encrypted,$user_publickey,OPENSSL_PKCS1_OAEP_PADDING);
$encrypted=base64_encode($encrypted);

$cert="$certA###$share";

openssl_sign($cert,$signature,$privkey_signing,OPENSSL_ALGO_SHA256);
$signature = base64_encode($signature);

echo "{";
echo "\"e\":\"OK\",";
echo "\"share\":\"$encrypted\",";
echo "\"cert\":\"$signature\"";
echo "}";

?>