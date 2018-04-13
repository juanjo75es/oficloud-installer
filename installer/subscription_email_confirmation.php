<?
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


$puserid=$con->real_escape_string($_REQUEST['userid']);
$enc_shares=$_REQUEST["enc_shares"];
$email=$con->real_escape_string($_REQUEST['email']);

$sql="SELECT * FROM usuarios WHERE nick='$email'";
$res=$con->query($sql);
$row=$res->fetch_assoc();
$userid=$row["id"];

if($userid!=$puserid)
{
    echo "Wrong user id";
    die;
}

$sql="SELECT pubkey FROM usuarios WHERE id=$userid";
$res=$con->query($sql);
$row=$res->fetch_assoc();
$user_pubkey=$row["pubkey"];


$sql="SELECT privkey,privkey_signing,pubkey,pubkey_signing FROM config";
$res=$con->query($sql);
$row=$res->fetch_assoc();
$privkey=$row["privkey"];
$privkey_signing=$row["privkey_signing"];
$pubkey_signing=$row["pubkey_signing"];

$a_shares=array();

//print_r($enc_shares);die;
if($enc_shares)
{
    foreach($enc_shares as $enc)
    {
        openssl_private_decrypt(base64_decode($enc),$share,$privkey,OPENSSL_PKCS1_OAEP_PADDING);
        //echo $enc." - ".$share;die;
        openssl_public_encrypt($share,$enc2,$user_pubkey,OPENSSL_PKCS1_OAEP_PADDING);
        $a_shares[]=["enc"=>$enc,"new_enc"=>base64_encode($enc2)];
        //$a_shares[]=["enc"=>$enc,"new_enc"=>base64_encode("hola mundo")];
    }
}

//print_r($a_shares);die;

$sql="SELECT * FROM permisos_nuevos WHERE user='$email'";
$res=$con->query($sql);
while($row=$res->fetch_assoc())
{
    $id=$row["id"];
    $is_directory=$row[""];
    $read=$row["read"];
    $write=$row["write"];
    $exec=$row["exec"];
    $admin=$row["admin"];
    $sql="INSERT INTO permisos(id,is_directory,`read`,`write`,`exec`,`admin`,user) VALUES($id,0,$read,$write,$exec,$admin,$userid)";
    $con->query($sql);
}
$sql="SELECT * FROM permisos_nuevos WHERE user='$email'";
$con->query($sql);


$outp="{";
$outp.="\"e\":\"OK\",";
$outp.="\"enc_shares\":".json_encode($a_shares);
$outp.="}";
echo($outp);
die;

?>