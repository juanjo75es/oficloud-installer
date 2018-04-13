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



set_include_path($_SERVER["DOCUMENT_ROOT"].'/phpseclib');

//include('Crypt/RSA.php');
include('Crypt/AES.php');
include('Crypt/Random.php');




$userid=$con->real_escape_string($_REQUEST['userid']);

$certificadoA = $con->real_escape_string($_REQUEST['cert']);



//$rsa = new Crypt_RSA();



$certificadoA=base64_decode($certificadoA);

$acertificadoA=explode('@#@#@$$',$certificadoA);

$certificado_encriptado=$acertificadoA[0];

$firma=$acertificadoA[1];



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



/*$rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
$rsa->loadKey($pubkey);
$rsa->setHash("sha256");
if(!@$rsa->verify($certificado_encriptado, $firma))
{
	echo "{";
	echo "\"e\":\"KSERROR signature verification $certificadoA\",";
	echo "\"cert\":\"\"";
	echo "}";
	die;
}*/

if(!openssl_verify($certificado_encriptado,$firma,$pubkey,OPENSSL_ALGO_SHA256))
{
	echo "{";
	echo "\"message\":\"KSERROR signature verification\",";
	echo "\"cert\":\"\"";
	echo "}";
	die;
}




$acertA=explode("#@@##",$certificado_encriptado);
$encriptado=$acertA[0];
$clave_encriptada=$acertA[1];
$iv=$acertA[2];

/*$rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_OAEP);
$rsa->setHash("sha1");
$rsa->loadKey($privkey); // private key
$clave=$rsa->decrypt($clave_encriptada);*/

openssl_private_decrypt($clave_encriptada,$clave,$privkey,OPENSSL_PKCS1_OAEP_PADDING);


$cipher = new Crypt_AES(); // could use CRYPT_AES_MODE_CBC

$cipher->setKeyLength(256);

$cipher->setKey($clave);

$cipher->setIV($iv);

$certA=$cipher->decrypt($encriptado);

sqllog($userid,$certA);
$certAutf8=utf8_encode($certA);
$o_certA=json_decode($certAutf8);



//$account=$o_certA->account;

$recipients=$o_certA->recipients;
$op=$o_certA->op;
$files=$o_certA->files;

$img1=$o_certA->img1;
$img2=$o_certA->img2;
$img3=$o_certA->img3;
$img4=$o_certA->img4;
$video=$o_certA->video;

//print_r($o_certA);die;


$img1id=$img1;
$img2id=$img2;
$img3id=$img3;
$img4id=$img4;
$videoid=$video;

$a_recipients=explode(";",$recipients);
foreach($a_recipients as $recipient)
{
	$addr=$recipient;

	$sql="SELECT id FROM usuarios WHERE nick='$addr'";
	$res2=$con->query($sql);
	if($row2=$res2->fetch_assoc())
	{
		$userid2=$row2["id"];
		if($userid!=$userid2)
		{			
			foreach($files as $f)
			{
				$sql="INSERT INTO permisos(user,id,is_directory,`read`,`write`,exec,admin) VALUES($userid2,$f->id,0,1,0,0,0)";
				//echo $sql;die;
				$con->query($sql);
			}
			if($img1id!="")
			{
				$sql="INSERT INTO permisos(user,id,is_directory,`read`,`write`,exec,admin) VALUES($userid2,$img1id,0,1,0,0,0)";
				//echo $sql;die;
				$con->query($sql);
			}
			if($img2id!="")
			{
				$sql="INSERT INTO permisos(user,id,is_directory,`read`,`write`,exec,admin) VALUES($userid2,$img2id,0,1,0,0,0)";
				//echo $sql;die;
				$con->query($sql);
			}
			if($img3id!="")
			{
				$sql="INSERT INTO permisos(user,id,is_directory,`read`,`write`,exec,admin) VALUES($userid2,$img3id,0,1,0,0,0)";
				//echo $sql;die;
				$con->query($sql);
			}
			if($img4id!="")
			{
				$sql="INSERT INTO permisos(user,id,is_directory,`read`,`write`,exec,admin) VALUES($userid2,$img4id,0,1,0,0,0)";
				//echo $sql;die;
				$con->query($sql);
			}
			if($videoid!="")
			{
				$sql="INSERT INTO permisos(user,id,is_directory,`read`,`write`,exec,admin) VALUES($userid2,$videoid,0,1,0,0,0)";
				//echo $sql;die;
				$con->query($sql);
			}
		}	
	}
	else{
		//print_r($files);die;
		foreach($files as $f)
		{
			//guardar en tabla de permisos complementaria y que se pase a permisos al hacer create_account o join_account???
			$sql="INSERT INTO permisos_nuevos(user,id,is_directory,`read`,`write`,exec,admin) VALUES('$addr',$f->id,0,1,0,0,0)";
			//echo $sql;die;
			$con->query($sql);
		}
		if($img1id!="")
		{
			$sql="INSERT INTO permisos_nuevos(user,id,is_directory,`read`,`write`,exec,admin) VALUES('$addr',$img1id,0,1,0,0,0)";
			//echo $sql;die;
			$con->query($sql);
		}
		if($img2id!="")
		{
			$sql="INSERT INTO permisos_nuevos(user,id,is_directory,`read`,`write`,exec,admin) VALUES('$addr',$img2id,0,1,0,0,0)";
			//echo $sql;die;
			$con->query($sql);
		}
		if($img3id!="")
		{
			$sql="INSERT INTO permisos_nuevos(user,id,is_directory,`read`,`write`,exec,admin) VALUES('$addr',$img3id,0,1,0,0,0)";
			//echo $sql;die;
			$con->query($sql);
		}
		if($img4id!="")
		{
			$sql="INSERT INTO permisos_nuevos(user,id,is_directory,`read`,`write`,exec,admin) VALUES('$addr',$img4id,0,1,0,0,0)";
			//echo $sql;die;
			$con->query($sql);
		}
		if($videoid!="")
		{
			$sql="INSERT INTO permisos_nuevos(user,id,is_directory,`read`,`write`,exec,admin) VALUES('$addr',$videoid,0,1,0,0,0)";
			//echo $sql;die;
			$con->query($sql);
		}
	}
}


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