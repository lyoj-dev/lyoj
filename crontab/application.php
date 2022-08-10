<?php
chdir("/etc/judge/crontab");
require_once "../web/function.php";
$config=GetConfig(false);
for($i=0;$i<count($config["require_code"]);$i++) require_once "../web/".$config["require_code"][$i];
?>