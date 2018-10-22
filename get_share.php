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
$user_pubkey=base64_decode($_REQUEST['user_pubkey']);
$user_pubkey_signing=base64_decode($_REQUEST['user_pubkey_signing']);

if($user_pubkey_signing)
	$pubkey=$user_pubkey_signing;
if($user_pubkey)
	$user_publickey=$user_pubkey;

	


//$rsa = new Crypt_RSA();



$certificadoA=base64_decode($certificadoA);



$sql="SELECT pubkey_signing,pubkey,account FROM usuarios WHERE id=$userid";
$res=$con->query($sql);
$row=$res->fetch_assoc();

if($row)
{
	$pubkey=$row["pubkey_signing"];
	$user_publickey=$row["pubkey"];
	$account=$row["account"];
}



$sql="SELECT privkey,privkey_signing,pubkey,pubkey_signing FROM config";
$res=$con->query($sql);
$row=$res->fetch_assoc();
$privkey=$row["privkey"];
$privkey_signing=$row["privkey_signing"];


$o_res=extraer_certificado($certificadoA,$pubkey,$privkey);

if(isset($o_res->e))
{
	echo json_encode($o_res);
	die;
}

$o_certA=$o_res;


$fileid=$o_certA->fileid;
$secret=$o_certA->secret;




if(sizeof($fileid)<1)

{

	echo "{";

	echo "\"message\":\"KSERROR error in decrypt\",";

	echo "\"cert\":\"\"";

	echo "}";

	die;

}





//$user_publickey = $con->real_escape_string($_REQUEST['key']);





function arreglar_clave($k)

{

	$res=str_replace('\n',"",$k);

	$res=str_replace("\n","",$res);

	$res=str_replace("-----BEGIN PUBLIC KEY-----","",$res);

	$res=str_replace("-----END PUBLIC KEY-----","",$res);

	

	return $res;

}

$id=$fileid;

$secret_matched=false;

$sql="SELECT `secret` FROM secrets WHERE aux_id=$id AND tipo='file'";
//echo $sql;die;
$res=$con->query($sql);
if($row=$res->fetch_assoc())
{
	$file_secret=$row["secret"];
	if($file_secret!=$secret)
	{
		$outp="{";
		$outp.="\"e\":\"Permission denied (file needs secret)\"";
		$outp .="}";
		echo($outp);
		die;
	}
	else{
		$secret_matched=true;
	}
}

if(!$secret_matched)
{
	$permiso_usuario=-1;
	$permiso_global=-1;
	$permiso_internet=-1;

	$sql="SELECT * FROM permisos WHERE id=$id AND is_directory=0 AND user=$userid";
	$res=$con->query($sql);
	if($row=$res->fetch_assoc())
	{
			$permiso_usuario=$row["read"];
	}

	$sql="SELECT * FROM permisos WHERE id=$id AND is_directory=0 AND user=-1";
	$res=$con->query($sql);
	if($row=$res->fetch_assoc())
	{
		$permiso_global=$row["read"];
	}

	$sql="SELECT * FROM permisos WHERE id=$id AND is_directory=0 AND user=-2";
	$res=$con->query($sql);
	if($row=$res->fetch_assoc())
	{
		$permiso_internet=$row["read"];
	}

	if($permiso_usuario=="0")
	{
		$outp="{";
		$outp.="\"e\":\"Permission denied(1)\"";
		$outp .="}";
		echo($outp);
		die;
	}
	if($permiso_usuario=="-1")
	{
		if($permiso_global=="0")
		{
			$outp="{";
			$outp.="\"e\":\"Permission denied(2)\"";
			$outp .="}";
			echo($outp);
			die;
		}
		if($permiso_global=="-1")
		{
			if($permiso_internet=="1")
			{

			}
			else
			{
				$permiso=comprobar_permiso_heredado($id,"nodirectory","read",$userid);
				if($permiso=="0")
				{
					$outp="{";
					$outp.="\"e\":\"Permission denied(3)\",";
					$outp.="\"desc\":\"Inherited from parent folders\"";
					$outp .="}";
					echo($outp);
					die;				
				}
			}
		}
	}
}


//$user_publickey=arreglar_clave($user_publickey);





//echo "$user_publickey<br>";



$sql="SELECT * FROM keyshares WHERE fileid=$fileid";

$res=$con->query($sql);



if($row=$res->fetch_assoc())

{

	$share=$row["share"];

}



//$rsa = new Crypt_RSA();
/*$rsa->setHash("sha1");
$rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_OAEP);
$rsa->loadKey($user_publickey);
$encypted=base64_encode($rsa->encrypt($share));*/

openssl_public_encrypt($share,$encrypted,$user_publickey,OPENSSL_PKCS1_OAEP_PADDING);
$encrypted=base64_encode($encrypted);




$cert="$certA###$share";

/*$rsa->loadKey($privkey_signing); // private key
$rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
$signature = base64_encode($rsa->sign($cert));*/

openssl_sign($cert,$signature,$privkey_signing,OPENSSL_ALGO_SHA256);
$signature = base64_encode($signature);

echo "{";
echo "\"e\":\"OK\",";
echo "\"share\":\"$encrypted\",";
echo "\"cert\":\"$signature\"";
echo "}";

?>