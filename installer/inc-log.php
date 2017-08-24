<?php

function sqllog($user,$msg)
{
    $sql="INSERT INTO `log`(user,msg) VALUES($user,'$msg')";
    mysql_query($sql);
}

?>