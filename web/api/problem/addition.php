<?php
    require_once "../require.php";
    CheckParam(array("pid","name"),$_GET);
    header("Content-Type: application/octet-stream");
    header("Accept-Ranges: bytes");
    $path="../../files/".$_GET["pid"]."/".$_GET["name"];
    $begin=0; $size=filesize($path);
    if (isset($_SERVER["HTTP_RANGE"])) {
        header("HTTP/1.1 206 Partial Content");
        list($name,$range)=explode("=",$_SERVER["HTTP_RANGE"]);
        list($begin,$end)=explode("-",$range);
        if ($end==0) $end=$size-1;
    } else {$begin=0;$end=$size-1;}
    header("Content-Length: ".($end-$begin+1));
    header("Content-Disposition: attachment; filename=".$_GET["name"]);
    header("Content-Range: bytes $begin-$end/$size");
    $fp=fopen($path,"rb"); fseek($fp,$begin);
    while (!feof($fp)) {
        $p=min(1024,$end-$begin+1);
        $begin+=$p; if ($p==0) break;
        echo fread($fp,$p);
    } fclose($fp);
?>