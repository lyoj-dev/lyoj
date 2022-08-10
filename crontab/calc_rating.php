<?php
chdir("/etc/judge/crontab");
require_once "./application.php";
$db=new Database_Controller;
$user=$db->Query("SELECT * FROM user");
for ($i=0;$i<count($user);$i++) {
    $rating=0; if ($user[$i]["email"]==null) continue; $new=array();
    $status=$db->Query("SELECT pid FROM status WHERE uid=".$user[$i]["id"]." AND status='Accepted'");
    for ($j=0;$j<count($status);$j++) $new[]=$status[$j]["pid"];
    $new=array_unique($new); $rating=round(sin(count($new)/2000*M_PI_2)*1000,0);
    $contest=$db->Query("SELECT id FROM contest_signup WHERE uid=".$user[$i]["id"]);
    for ($j=0;$j<count($contest);$j++) {
        $c=$db->Query("SELECT starttime,duration,rated FROM contest WHERE id=".$contest[$j]["id"])[0];
        if ($c["starttime"]+$c["duration"]>=time()) continue;
        if ($c["rated"]==0) continue;
        $tmp=$db->Query("SELECT uid,score FROM contest_ranking WHERE id=".$contest[$j]["id"]." ORDER BY score DESC");
        $n=count($tmp); $kk=1/pow(50,2/$n);
        for ($k=0;$k<count($tmp);$k++) {
            if ($tmp[$k]["uid"]==$user[$i]["id"]) {
                $id=$k;
                while ($k-1>0&&$tmp[$k-1]["score"]==$tmp[$id]["score"]) $k--;
                $rating+=floor(50*pow($kk,$k));
                break;
            }
        }
    } $db->Execute("UPDATE user SET rating=$rating,uptime=".time()." WHERE id=".$user[$i]["id"]);
    // echo $user[$i]["name"]." ".$rp."\n";
}
?>