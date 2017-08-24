<?php 

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

header("Cache-Control: post-check=0, pre-check=0", false);

header("Pragma: no-cache");



header("Content-Type: application/json; charset=UTF-8");



include_once("../db.php");

$mysqli = new mysqli($g_db_server, $g_db_user, $g_db_password, $g_db_name);



set_include_path($_SERVER["DOCUMENT_ROOT"].'/phpseclib');

include('Crypt/RSA.php');

include('Crypt/Random.php');



function mi_mysql_real_escape_string($s)

{

	if(isset($s))

		return mysql_real_escape_string($s);

		return $s;

}





$parametros = base64_decode($_REQUEST['p']);



$sql="SELECT * FROM config";

$res=$mysqli->query($sql);

$row=$res->fetch_assoc();

$privkey=$row["privkey"];

$privkey_signing=$row["privkey_signing"];



//echo $privkey;



$rsa = new Crypt_RSA();



function arreglar_clave($k)

{

	$res=str_replace('\n',"",$k);

	$res=str_replace("\n","",$res);

	$res=str_replace('\r',"",$res);

	$res=str_replace("\r","",$res);

	$res=str_replace("-----BEGIN PUBLIC KEY-----","",$res);

	$res=str_replace("-----END PUBLIC KEY-----","",$res);

	$res=str_replace("-----BEGIN PRIVATE KEY-----","",$res);

	$res=str_replace("-----END PRIVATE KEY-----","",$res);

	

	return $res;

}



$k=arreglar_clave($privkey);



$rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_OAEP);

$rsa->setHash("sha1");

$rsa->loadKey($k); // private key

$parametros2=$rsa->decrypt($parametros);



$k=arreglar_clave($privkey_signing);

$rsa->setHash("sha256");

$rsa->loadKey($k); // private key

$cert=$parametros2;

$rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);

$signature = base64_encode($rsa->sign($cert));

echo "{";

echo "\"e\":\"OK\",";

echo "\"params\":\"$parametros2\",";

echo "\"signature\":\"$signature\"";

echo "}";

?>