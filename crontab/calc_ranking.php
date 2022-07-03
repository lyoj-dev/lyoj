<?php
chdir("/etc/judge/crontab");
require_once "./application.php";
$db=new Database_Controller;
$contest=$db->Query("SELECT * FROM contest");
for ($i=0;$i<count($contest);$i++) {
    $c=$contest[$i]; $endtime=$c["starttime"]+$c["duration"];

    // 枚举题目
    $problem=$db->Query("SELECT * FROM contest_problem WHERE id=".$c["id"]);
    // 枚举参与者
    $people=$db->Query("SELECT * FROM contest_signup WHERE id=".$c["id"]);
    for ($j=0;$j<count($people);$j++) {
        $u=$people[$j];

        // 初始化
        $sinfo=array();
        for ($k=0;$k<count($problem);$k++) {
            $arr=array("score"=>0,"time"=>0,"id"=>0,"penalty"=>0);
            $sinfo[]=$arr;
        } $exist=count($db->Query("SELECT uid FROM contest_ranking WHERE id=".$c["id"]." AND uid=".$u["uid"]));
        if ($exist) $db->Execute("UPDATE contest_ranking SET info='".JSON_encode($sinfo,JSON_UNESCAPED_UNICODE)."' WHERE id=".$c["id"]." AND uid=".$u["uid"]);
        else $db->Execute("INSERT INTO contest_ranking (id,uid,score,time,info) VALUES (".$c["id"].",".$u["uid"].",0,0,'".JSON_encode($sinfo,JSON_UNESCAPED_UNICODE)."')");

        // 计算下一个人
        if ($c["starttime"]>time()) continue;

        // 计算这个人
        $sumscore=0;$sumtime=0;$penalty=0;
        for ($k=0;$k<count($problem);$k++) {
            $p=$problem[$k]["pid"]; $ac=false;
            $p=$db->Query("SELECT * FROM problem WHERE id=$p")[0];

            // 获取这个人对于这道题的提交记录
            $filter="uid=".$u["uid"]." AND pid=".$p["id"]." AND time>=".$c["starttime"]." AND time<=$endtime AND contest=".$c["id"];
            $status=$db->Query("SELECT * FROM status WHERE $filter ORDER BY time ASC");
            if (count($status)==0) continue;
            $ac=count($db->Query("SELECT * FROM status WHERE status='Accepted' AND $filter ORDER BY time ASC"))?1:0;

            // 对于 OI 赛制比赛而言
            if ($c["type"]==0) {
                // 以最后一次提交为准
                $s=$status[count($status)-1];

                // 获取当前提交分数
                $score=0; $arr=JSON_decode($s["result"],true);
                if (array_key_exists("info",$arr)) for ($l=0;$l<count($arr["info"]);$l++) $score+=$arr["info"][$l]["score"];

                // 归纳当前提交信息
                if (time()>$endtime) {
                    $sinfo[$k]["score"]=$score;
                    $sinfo[$k]["time"]=$arr["time"];
                    $sinfo[$k]["id"]=$s["id"];
                    $sinfo[$k]["penalty"]=0;
                } else {
                    $sinfo[$k]["score"]=0;
                    $sinfo[$k]["time"]=0;
                    $sinfo[$k]["id"]=$s["id"];
                    $sinfo[$k]["penalty"]=0;    
                }
                
            } 
            // 对于 IOI 赛制而言
            else if ($c["type"]==1) {
                // 以最后一次提交为准
                $s=$status[count($status)-1];
                
                // 获取当前提交分数
                $score=0; $arr=JSON_decode($s["result"],true);
                if (array_key_exists("info",$arr)) for ($l=0;$l<count($arr["info"]);$l++) $score+=$arr["info"][$l]["score"];

                // 归纳当前提交信息
                $sinfo[$k]["score"]=$score;
                $sinfo[$k]["time"]=$arr["time"];
                $sinfo[$k]["id"]=$s["id"];
                $sinfo[$k]["penalty"]=0;
            }
            // 对于 ACM 赛制而言
            else if ($c["type"]==2) {
                // 如果当前没有 AC
                if (!$ac) {
                    // 以最后一次提交为准
                    $s=$status[count($status)-1];

                    // 归纳当前提交信息
                    $sinfo[$k]["score"]=0;
                    $sinfo[$k]["time"]=0;
                    $sinfo[$k]["id"]=$s["id"];
                    $sinfo[$k]["penalty"]=count($status);
                }
                // 如果当前已经 AC
                else {
                    // 以首次 AC 的提交为准
                    $id=0; for ($l=0;$l<count($status);$l++) {
                        if ($status[$l]["status"]=="Accepted") break;
                        $id++;
                    }

                    // 归纳当前提交信息
                    $sinfo[$k]["score"]=1;
                    $sinfo[$k]["time"]=$status[$id]["time"]-$c["starttime"];
                    $sinfo[$k]["id"]=$status[$id]["id"];
                    $sinfo[$k]["penalty"]=$id;

                    // 将罚时提交到总罚时上
                    $penalty+=$id;
                    $sumtime+=$id*60*20;
                }
            }
            // 总结当前人的信息
            $sumscore+=$sinfo[$k]["score"];
            $sumtime+=$sinfo[$k]["time"];
        } 
        // 更新数据库
        // print_r($sinfo);
        $data="score=$sumscore,time=$sumtime,info='".JSON_encode($sinfo,JSON_UNESCAPED_UNICODE)."',penalty=$penalty";
        $db->Execute("UPDATE contest_ranking SET $data WHERE id=".$c["id"]." AND uid=".$u["uid"]);
    }
}
?>