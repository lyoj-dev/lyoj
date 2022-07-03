<?php
require_once "../require.php";
CheckParam(array("id"),$_GET);
$contest_controller=new Contest_Controller;
$info=$contest_controller->GetContest($_GET["id"],$_GET["id"],true)[0];
if ($info["type"]==0&&$info["starttime"]+$info["duration"]>=time()) API_Controller::error_permission_denied();
$rank=$contest_controller->GetRanking($_GET["id"]);
header("Content-Type: application/octet-stream");
header("Content-Disposition: attachment; filename=result.html");
$pname=array(); $pid=array();
?><!DOCTYPE html><html><style>th,td{padding-left:1em;padding-right:1em;white-space:nowrap;text-align:center;verticle-align:middle;}</style><style>.head{border-style:none solid solid none;border-width:3px 3px;border-color:#000;}</style><style>.cont{border-style:none solid solid none;border-width:1px 3px;border-color:#ccc;font-weight:normal}</style><style>.score{border-radius:5px;font-weight:normal;}</style><style>.all{border-radius:5px;}table{border-color:#000;}</style><style>.a{color:black;text-decoration:none;}</style><style>code{background-color:white;tab-size:4;font-family:consolas;font-size:13px;}</style><title><?php echo $info["title"]?>: Contest Result</title></html><body><p style="font-size:x-large;font-weight:bold;margin:0px"><span><a name="top"></a><?php echo $info["title"]?>: Ranking</span></p><p style="font-size:small">Click on the name or single question score to jump to the details. Use LYOJ contest system to judge.</p><p><table cellpadding="1" style="border-style:solid;"><tbody><tr><th class="head" scope="col">Rank</th><th class="head" scope="col">Name</th><th class="head" scope="col">Time</th><th class="head" scope="col">Score</th><?php
    $database_controller=new Database_Controller; $cnt=0;
    $p=$database_controller->Query("SELECT pid FROM contest_problem WHERE id=".$_GET["id"]);
    for ($i=0;$i<count($p);$i++) {
        $fp=fopen("../../../problem/".$p[$i]["pid"]."/config.json","r");
        $json=fread($fp,filesize("../../../problem/".$p[$i]["pid"]."/config.json"));
        $pid[]=$p[$i]["pid"];
        fclose($fp); $arr=json_decode($json,true);
        $name=explode(".",$arr["input"]); $tmp="";
        for ($j=0;$j<count($name)-1;$j++) $tmp.=($j==0?"":".").$name[$j];
        if (count($name)==1) $tmp="main".(++$cnt);
        $pname[]=$tmp; echo "<th class=\"head\" scope=\"col\">$tmp</th>";
    }
?></tr><?php
    $r=0; 
    for ($i=0;$i<count($rank);$i++) {
        if ($rank[$i]["score"]!=$rank[$i-1]["score"]) $r=$i+1;
        $t=round(intval($rank[$i]["time"])/1000,2);
        echo "<tr style=\"height:27px\">";
        echo "<th class=\"cont\" scope=\"col\">$r</th>";
        echo "<th class=\"cont\" scope=\"col\"><a href='#rank$i' class='a'>".$rank[$i]["name"]."</a></th>";
        echo "<th class=\"cont\" scope=\"col\">".$t."s</th>";
        $percent=$rank[$i]["score"]/count($pname)/100*80+20;
        echo "<th class=\"all\" scope=\"col\" style=\"background-color:rgba(92,201,92,".($rank[$i]["score"]/count($pname))."%);".
        "border: 2px solid rgba(30,90,30,$percent%)\">".$rank[$i]["score"]."</th>";
        for ($j=0;$j<count($pname);$j++) {
            $s=$database_controller->Query("SELECT id,status FROM status WHERE pid=".$pid[$j]." AND contest=".$_GET["id"]." AND uid=".$rank[$i]["uid"]." LIMIT 1");
            if (count($s)==0){echo "<th class=\"score\" scope=\"col\" style=\"background-color:#eaeaea\"><a href='#rank$i-$j' class='a'>0</a></th>"; continue;}
            if ($s[0]["status"]=="Compile Error"){echo "<th class=\"score\" scope=\"col\" style=\"background-color:#ffaaff\"><a href='#rank$i-$j' class='a'>0</a></th>";continue;}
            echo "<th class=\"score\" scope=\"col\" style=\"background-color:rgba(92,201,92,"
            .$rank[$i]["info"][$j]["score"]."%)\"><a href='#rank$i-$j' class='a'>".$rank[$i]["info"][$j]["score"]."</a></th>";
        } 
        echo "</tr>";
    }
?></tbody></table></p><?php
    for ($i=0;$i<count($rank);$i++) {
        echo "<hr>";
        echo "<p style=\"font-size:x-large;font-weight:bold;margin:0px\"><span><a name=\"rank$i\">".
        "</a>Contestant: ".$rank[$i]["name"]."</span></p>";
        for ($j=0;$j<count($pname);$j++) {
            echo "<p></p><p style=\"font-size:large;font-weight:bold;margin:0px\"><span><a name=\"rank$i-$j\"></a>Problem ".$pname[$j]."</span></p>";
            $s=$database_controller->Query("SELECT id,status FROM status WHERE pid=".$pid[$j]." AND contest=".$_GET["id"]." AND uid=".$rank[$i]["uid"]." LIMIT 1");
            if (count($s)==0) {echo "<p style=\"margin:0px\">&nbsp;&nbsp;Cannot found contestant's source.</p>";continue;}
            echo "<p style=\"margin:0px\">&nbsp;&nbsp;source: ".$pname[$j].".cpp</p>";
            if ($s[0]["status"]=="Compile Error"){
                $arr=$database_controller->Query("SELECT result FROM status WHERE id=".$s[0]["id"])[0]["result"];
                $arr=json_decode($arr,true);
                echo "<p style=\"margin:0px\">&nbsp;&nbsp;Compile Error</p>";
                echo "<table cellpadding=\"1\" border=\"1\"><tbody><tr><td style=\"padding:0.5em;text-align:left;font-family:monospace\"><code>".
                str_replace(" ","&nbsp;",str_replace("\n","<br/>",htmlentities($arr["compile_info"])))."</code></td></tr></tbody></table>";
                continue;
            }
            $arr=$database_controller->Query("SELECT result FROM status WHERE id=".$s[0]["id"])[0]["result"];
            $arr=json_decode($arr,true)["info"]; 
            echo "<div><table cellpadding=\"1\" style=\"border-style:solid;\"><tbody>";
            echo "<tr><th class=\"head\" scope=\"col\">Case</th><th class=\"head\" scope=\"col\">Input</th>";
            echo "<th class=\"head\" scope=\"col\">Result</th><th class=\"head\" scope=\"col\">Time</th>";
            echo "<th class=\"head\" scope=\"col\">Memory</th><th class=\"head\" scope=\"col\">Score</th></tr>";
            $fp=fopen("../../../problem/".$pid[$j]."/config.json","r");
            $config=fread($fp,filesize("../../../problem/".$pid[$j]."/config.json"));
            fclose($fp); $config=json_decode($config,true);
            for ($k=0;$k<count($arr);$k++) {
                echo "<tr>";
                echo "<th class=\"cont\" scope=\"col\">#".($k+1)."</th>";
                echo "<th class=\"cont\" scope=\"col\">".$config["data"][$k]["input"]."</th>";
                $col=""; if ($arr[$k]["state"]=="Wrong Answer") $col="#ffc0c0";
                else if ($arr[$k]["state"]=="Accepted") $col="#c0ffc0";
                else if ($arr[$k]["state"]=="Partially Correct") $col="#c0ffff";
                else if ($arr[$k]["state"]=="Time Limited Exceeded") $col="#ffffc0";
                else if ($arr[$k]["state"]=="Memory Limited Exceeded") $col="#ffffc0";
                else if ($arr[$k]["state"]=="Runtime Error") $col="#ffc0ff";
                echo "<th class=\"cont\" scope=\"col\" style=\"background-color:$col\">"
                .$arr[$k]["state"]."&nbsp;<a href=\"javascript:alert('".
                htmlentities(str_replace("'","\\'",str_replace("\n","",$arr[$k]["info"])))."')\">(...)</a></th>";
                echo "<th class=\"cont\" scope=\"col\">".sprintf("%.3f",$arr[$k]["time"]/1000)." s</th>";
                echo "<th class=\"cont\" scope=\"col\">".sprintf("%.3f",$arr[$k]["memory"]/1024)." MB</th>";
                $col=""; if ($arr[$k]["score"]==0) $col="#ffc0c0";
                else if ($arr[$k]["score"]==$config["data"][$k]["score"]) $col="#c0ffc0";
                else $col="#c0ffff";
                echo "<th class=\"cont\" scope=\"col\" style=\"background-color:$col\">".
                "<a style=\"font-weight:bold;font-size:large;\">".$arr[$k]["score"].
                "</a>&nbsp;/&nbsp;".$config["data"][$k]["score"]."</th>";
                echo "</tr>";
            }
            echo "</tbody></table></div><p></p>";
        } echo "<a href='#top'>Return Top</a>";
    }
?></body>