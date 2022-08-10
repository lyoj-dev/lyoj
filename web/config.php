<?php
    global $config;
    $fp=fopen("/etc/judge/config.json","r");
    $json=fread($fp,filesize("/etc/judge/config.json"));
    $config=json_decode($json,true);
?>