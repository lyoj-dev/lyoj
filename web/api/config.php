<?php
require_once "../cores/guipages/config.php";
error_reporting(E_ERROR);
ini_set("display_errors","Off");
require_once "../config.php";
require_once "function.php";
global $config; 
date_default_timezone_set($config["web"]["timezone"]);
for ($i=0;$i<count($config["require_code"]);$i++) 
    require_once("../".$config["require_code"][$i]);
CheckParam(array("key", "value", "passwd"), $_POST);
$type = $_POST["key"];
if ($_POST["passwd"] != $passwd) API_Controller::error_passwd_wrong();
if (file_exists("./config.json") == "") {
    $fp = fopen("/etc/judge/config.json", "r");
    $json = fread($fp, filesize("/etc/judge/config.json"));
    fclose($fp); $config = json_decode($json, true);
    $footer = json_encode($config["web"]["footer"], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    $config["web"]["footer"] = $footer;
    $json = json_encode($config, JSON_UNESCAPED_UNICODE);
    $fp = fopen("./config.json", "w");
    fwrite($fp, $json);
    fclose($fp);
}
$fp = fopen("./config.json", "r");
$json = fread($fp, filesize("./config.json"));
$arr = json_decode($json, true);
fclose($fp);
if ($type == "//lang") $arr["lang"] = json_decode($_POST["value"], true);
else if ($type == "//default_lang") $arr["default_lang"] = $_POST["value"];
else {
    $path=explode("//",$type);
    if (array_key_exists($path[1], $arr) && array_key_exists($path[2], $arr[$path[1]])) {
        $arr[$path[1]][$path[2]] = $_POST["value"];
        if (is_numeric($_POST["value"])) $arr[$path[1]][$path[2]] = intval($_POST["value"]);
    }
}
$fp = fopen("./config.json", "w");
$json = json_encode($arr, true);
fwrite($fp, $json);
fclose($fp);
API_Controller::output(array());
?>
