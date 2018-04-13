<?php

function sqllog($user,$msg)
{
    global $con;
    
    $sql="INSERT INTO `log`(user,msg) VALUES($user,'$msg')";
    $con->query($sql);
}

?>