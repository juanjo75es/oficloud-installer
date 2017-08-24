<?php 

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

header("Cache-Control: post-check=0, pre-check=0", false);

header("Pragma: no-cache");



header("Content-Type: application/json; charset=UTF-8");

error_reporting(E_ERROR | E_WARNING | E_PARSE);

include_once("./db.php");
include_once("./inc-log.php");

$con = mysql_connect($g_db_server, $g_db_user, $g_db_password) or die("Error connecting to database");

mysql_select_db($g_db_name, $con) or die("Error selecting database");



set_include_path($_SERVER["DOCUMENT_ROOT"].'/phpseclib');

include('Crypt/RSA.php');

include('Crypt/AES.php');

include('Crypt/Random.php');





function mi_mysql_real_escape_string($s)

{

	if(isset($s))

		return mysql_real_escape_string($s);

		return $s;

}



$userid = mi_mysql_real_escape_string($_REQUEST['userid']);

$certificadoA = mi_mysql_real_escape_string($_REQUEST['cert']);







$rsa = new Crypt_RSA();



$certificadoA=base64_decode($certificadoA);

$acertificadoA=explode('@@@',$certificadoA);

$certificado_encriptado=$acertificadoA[0];

$firma=$acertificadoA[1];



$sql="SELECT pubkey_signing,account FROM usuarios WHERE id=$userid";

$res=mysql_query($sql);

$row=mysql_fetch_assoc($res);

$pubkey=$row["pubkey_signing"];

$account=$row["account"];



$sql="SELECT privkey,privkey_signing,pubkey,pubkey_signing FROM config";

$res=mysql_query($sql);

$row=mysql_fetch_assoc($res);

$privkey=$row["privkey"];

$privkey_signing=$row["privkey_signing"];

$pubkey_signing=$row["pubkey_signing"];





$rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);

$rsa->loadKey($pubkey);

$rsa->setHash("sha256");

if(!@$rsa->verify($certificado_encriptado, $firma))

{

	echo "{";

	echo "\"e\":\"KSERROR signature verification \",";

	echo "\"cert\":\"\"";

	echo "}";

	die;

}



$acertA=explode("#@@##",$certificado_encriptado);

$encriptado=$acertA[0];

$clave_encriptada=$acertA[1];

$iv=$acertA[2];

$rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_OAEP);

$rsa->setHash("sha1");

$rsa->loadKey($privkey); // private key

$clave=$rsa->decrypt($clave_encriptada);



$cipher = new Crypt_AES(); // could use CRYPT_AES_MODE_CBC

$cipher->setKeyLength(256);

$cipher->setKey($clave);

$cipher->setIV($iv);

$certA=$cipher->decrypt($encriptado);

sqllog($userid,$certA);
$certAutf8=utf8_encode($certA);
$o_certA=json_decode($certAutf8);



$newpassword=$o_certA->newpassword;

$newprivkey=$o_certA->privkey;

$newprivkey_signing=$o_certA->privkey_signing;

$newpubkey=$o_certA->pubkey;

$newpubkey_signing=$o_certA->pubkey_signing;





function arreglar_clave($k)

{

	$res=str_replace('\n',"",$k);

	$res=str_replace("\n","",$res);

	$res=str_replace("-----BEGIN PUBLIC KEY-----","",$res);

	$res=str_replace("-----END PUBLIC KEY-----","",$res);

	

	return $res;

}



$newpubkey=arreglar_clave($newpubkey);

$newprivkey=arreglar_clave($newprivkey);

$newpubkey_signing=arreglar_clave($newpubkey_signing);

$newprivkey_signing=arreglar_clave($newprivkey_signing);







$sql="UPDATE usuarios SET password_hash='$newpassword',pubkey='$newpubkey',encrypted_privkey='$newprivkey',pubkey_signing='$newpubkey_signing',encrypted_privkey_signing='$newprivkey_signing' WHERE id=$userid";

//echo $sql;die;

mysql_query($sql);





/*$rsa = new Crypt_RSA();

$rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_OAEP);

$rsa->loadKey($user_publickey);

$encypted=base64_encode($rsa->encrypt($message));*/





$rsa->loadKey(str_replace("\r","",str_replace("\n","",$privkey_signing))); // private key

$cert=$certA;//"$fileid#$fileName#$dirid#$size#";

$rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);

$rsa->setHash("sha256");

//echo "cert: $cert<br>";

$signature = $rsa->sign($cert);





echo "{";

echo "\"e\":\"OK\",";

echo "\"cert\":\"".base64_encode($signature)."\"";

echo "}";

?>