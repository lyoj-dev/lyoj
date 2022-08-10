<?php
class Contest_Controller {
    static $db;
    static $login_controller;
    static $user_controller;
    function __construct() {
        self::$db=new Database_Controller;
        self::$login_controller=new Login_Controller;
        self::$user_controller=new User_Controller;
    }

    /**
     * 判断比赛是否存在 JudgeContestExist
     * @param int $id 比赛id
     * @return void
     */
    static function JudgeContestExist(int $id):void {
        $c=self::$db->Query("SELECT id FROM contest WHERE id=$id");
        if (!count($c)) {
            $url=$_SERVER['REQUEST_URI'];
            $path=explode("/",$url);
            $api_mode=$path[1]=="api";
            if ($api_mode) API_Controller::error_contest_not_found($id);
            else Error_Controller::Common("Contest id $id not found");
        }
    }

    /**
     * 获取所有比赛信息 GetContest
     * @param float $l=1 左区间
     * @param float $r=1e9 右区间
     * @param bool $api_mode=false 是否开启api模式
     * @param string $sort="id" 排序方式
     * @return array|null
     */
    function GetContest(float $l=1,float $r=1e9,bool $api_mode=false,string $sort="id"):array|null {
        $array=self::$db->Query("SELECT * FROM contest ORDER BY $sort");
        $uid=self::$login_controller->CheckLogin(); $admin=0; $res=array();
        if ($uid) $admin=self::$user_controller->GetWholeUserInfo($uid)["permission"]>1;
        for ($i=$l-1;$i<min(count($array),$r);$i++) {
            $fp=fopen(($api_mode?"../../":"")."../contest/".$array[$i]["id"].".md","r");
            $md=fread($fp,filesize(($api_mode?"../../":"")."../contest/".$array[$i]["id"].".md"));
            fclose($fp); $tmp=self::$db->Query("SELECT pid FROM contest_problem WHERE id=".$array[$i]["id"]);
            $arr=array(); for ($j=0;$j<count($tmp);$j++) $arr[]=$tmp[$j]["pid"];
            $dat=array("problem"=>$arr);  $array[$i]["intro"]=$md;
            $tmp=self::$db->Query("SELECT * FROM tags WHERE type='contest' AND id=".$array[$i]["id"]);
            $arr=array(); for ($j=0;$j<count($tmp);$j++) {
                $name=$tmp[$j]["tagname"]; 
                if ($name=="OI") continue;
                if ($name=="IOI") continue;
                if ($name=="ACM") continue;
                if ($name=="Rated") continue;
                if ($name=="Unrated") continue;
                $arr[]=$name;
            } $dat=array_merge(array("tags"=>$arr),$dat);
            $ok=$array[$i]["starttime"]+$array[$i]["duration"]<time()||$admin;
            if (!$api_mode||$ok) $array[$i]=array_merge($array[$i],$dat);
            $res[]=$array[$i]; 
        } return $res;
    }

    /**
     * 获取比赛总数 GetContestTotal
     * @return int
     */
    function GetContestTotal():int {
        $array=self::$db->Query("SELECT * FROM contest");
        return count($array);
    }

    /**
     * 判断用户是否报名参加比赛 JudgeSignup
     * @param int $id 比赛id
     * @return bool
     */
    function JudgeSignup(int $id):bool {
        Contest_Controller::JudgeContestExist($id);
        $uid=self::$login_controller->CheckLogin();
        if (!$uid) return false;
        $array=self::$db->Query("SELECT * FROM contest_signup WHERE uid=$uid AND id=$id");
        return count($array)?true:false;
    }

    /**
     * 获取用户报名比赛 GetUserSignup
     * @param int $uid 用户id
     * @return array|null
     */
    static function GetUserSignup(int $uid):array|null {
        return self::$db->Query("SELECT * FROM contest_signup WHERE uid=$uid");
    }

    /**
     * 获取报名人数 GetContestSignupNumber
     * @param int $id 比赛id
     * @return int
     */
    function GetContestSignupNumber(int $id):int {
        Contest_Controller::JudgeContestExist($id);
        $num=self::$db->Query("SELECT * FROM contest_signup WHERE id=$id");
        return count($num);
    }

    /**
     * 获取报名信息 GetContestSignup
     * @param int $id 比赛id
     * @return array|null
     */
    function GetContestSignup(int $id):array|null {
        Contest_Controller::JudgeContestExist($id);
        $array=self::$db->Query("SELECT uid FROM contest_signup WHERE id=$id");
        return $array;
    }

    /**
     * 比赛报名 SignupContest
     * @param int $id 比赛id
     * @return array|null
     */
    function SignupContest(int $id):void {
        Contest_Controller::JudgeContestExist($id);
        $uid=self::$login_controller->CheckLogin(); 
        $array=self::$db->Query("SELECT * FROM contest_signup WHERE id=$id AND uid=$uid");
        if (count($array)) return;
        self::$db->Execute("INSERT INTO contest_signup (id,uid) VALUES ($id,$uid)");
    }

    /**
     * 获取排行榜 GetRanking
     * @param int $id 比赛id
     * @return array|null
     */
    function GetRanking(int $id):array|null {
        Contest_Controller::JudgeContestExist($id);
        $result=self::$db->Query("SELECT * FROM contest_ranking WHERE id=$id ORDER BY score DESC,time");
        for ($i=0;$i<count($result);$i++) {
            $user=self::$user_controller->GetWholeUserInfo($result[$i]["uid"]);
            $result[$i]["name"]=$user["name"];
            $result[$i]["uid"]=$user["id"];
            $result[$i]["info"]=json_decode($result[$i]["info"],true);
        } return $result;
    }

    /**
     * 获取赛时提交 GetContestSubmit
     * @param int $id 比赛id
     * @param float $l=1 左边界
     * @param float $r=1e18 右边界
     * @param int &$sum 题目总数
     * @return array|null
     */
    function GetContestSubmit(int $id,float $l=1,float $r=1e18,int& $sum):array|null {
        Contest_Controller::JudgeContestExist($id);
        $array=self::$db->Query("SELECT * FROM contest WHERE id=$id");
        if ($array==null||count($array)==0) return null;
        $sql="SELECT * FROM status WHERE contest=$id AND time>=".$array[0]["starttime"]
        ." AND time<=".($array[0]["starttime"]+$array[0]["duration"])." ORDER BY id DESC"; 
        $res=self::$db->Query($sql); $endtime=$array[0]["starttime"]+$array[0]["duration"];
        if ($array[0]["type"]==0&&$endtime>=time()) {
            for ($i=0;$i<count($res);$i++) $res[$i]["status"]="Submitted";
        } $sum=count($res); return array_slice($res,$l-1,$r-$l+1);
    }

    /**
     * 删除比赛 DeleteContest
     * @param int $id 比赛id
     * @return void
     */
    static function DeleteContest(int $id):void {
        Contest_Controller::JudgeContestExist($id);
        self::$db->Execute("DELETE FROM contest WHERE id=$id");
        self::$db->Execute("DELETE FROM contest_problem WHERE id=$id");
        self::$db->execute("DELETE FROM contest_signup WHERE id=$id");
        self::$db->Execute("DELETE FROM contest_ranking WHERE id=$id");
        self::$db->Execute("DELETE FROM tags WHERE id=$id AND type='contest'");
        unlink("../../../contest/$id.md");
    }

    /**
     * 新建比赛 CreateContest
     * @param string $title 题目名称
     * @param int $starttime 开始时间
     * @param int $duration 持续时间
     * @param string $intro 比赛介绍
     * @param string $problem 比赛试题
     * @param int $type 比赛类型
     * @param bool $rated 是否rated
     * @param string $tag 比赛tag
     * @param string $lang 允许使用的语言
     * @return int
     */
    static function CreateContest(string $title,int $starttime,int $duration,   
        string $intro,string $problem,int $type,int $rated,string $tag,string $lang):int {
        $id=self::$db->Query("SELECT id FROM contest ORDER BY id DESC LIMIT 1")[0]["id"]+1;
        self::$db->Execute("INSERT INTO contest (id,title,starttime,duration,type,rated,lang)".
            "VALUES ($id,'$title',$starttime,$duration,$type,$rated,'$lang');");
        $fp=fopen("../../../contest/$id.md","w");
        fwrite($fp,$intro); fclose($fp);
        $problem=explode(",",$problem);
        $problem_controller=new Problem_Controller;
        for ($i=0;$i<count($problem);$i++) {
            $pid=trim($problem[$i]);
            if ($pid=="") continue;
            if (!is_numeric($pid)) continue;
            $pid=$problem_controller->GetIdByPid(intval($pid));
            if ($pid===false) continue;
            self::$db->Execute("INSERT INTO contest_problem (id,pid) VALUES ($id,$pid)");
        } $tag=explode(",",$tag);
        if ($type==0) self::$db->Execute("INSERT INTO tags (tagname,id,type) VALUES ('OI',$id,'contest')");
        if ($type==1) self::$db->Execute("INSERT INTO tags (tagname,id,type) VALUES ('IOI',$id,'contest')");
        if ($type==2) self::$db->Execute("INSERT INTO tags (tagname,id,type) VALUES ('ACM',$id,'contest')");
        if ($rated==0) self::$db->Execute("INSERT INTO tags (tagname,id,type) VALUES ('Unrated',$id,'contest')");
        if ($rated==1) self::$db->Execute("INSERT INTO tags (tagname,id,type) VALUES ('Rated',$id,'contest')");
        for ($i=0;$i<count($problem);$i++) {
            $t=trim($tag[$i]);
            if ($t=="") continue;
            self::$db->Execute("INSERT INTO tags (tagname,id,type) VALUES ('$t',$id,'contest')");
        } return $id;
    }

    /**
     * 修改比赛 UpdateContest
     * @param int $id 比赛id
     * @param string $title 题目名称
     * @param int $starttime 开始时间
     * @param int $duration 持续时间
     * @param string $intro 比赛介绍
     * @param string $problem 比赛试题
     * @param int $type 比赛类型
     * @param bool $rated 是否rated
     * @param string $tag 比赛tag
     * @param string $lang 允许使用的语言
     * @return void
     */
    static function UpdateContest(int $id,string $title,int $starttime,int $duration,   
        string $intro,string $problem,int $type,int $rated,string $tag,string $lang):void {
        Contest_Controller::JudgeContestExist($id);
        self::$db->Execute("UPDATE contest SET title='$title' WHERE id=$id");
        self::$db->Execute("UPDATE contest SET starttime=$starttime WHERE id=$id");
        self::$db->Execute("UPDATE contest SET duration=$duration WHERE id=$id");
        self::$db->Execute("UPDATE contest SET type=$type WHERE id=$id");
        self::$db->Execute("UPDATE contest SET rated=$rated WHERE id=$id");
        self::$db->Execute("UPDATE contest SET lang='$lang' WHERE id=$id");
        self::$db->Execute("DELETE FROM contest_problem WHERE id=$id");
        self::$db->Execute("DELETE FROM tags WHERE id=$id AND type='contest'");
        $problem=explode(",",$problem);
        $problem_controller=new Problem_Controller;
        for ($i=0;$i<count($problem);$i++) {
            $pid=trim($problem[$i]);
            if ($pid=="") continue;
            if (!is_numeric($pid)) continue;
            $pid=$problem_controller->GetIdByPid(intval($pid));
            if ($pid===false) continue;
            self::$db->Execute("INSERT INTO contest_problem (id,pid) VALUES ($id,$pid)");
        } $tag=explode(",",$tag);
        if ($type==0) self::$db->Execute("INSERT INTO tags (tagname,id,type) VALUES ('OI',$id,'contest')");
        if ($type==1) self::$db->Execute("INSERT INTO tags (tagname,id,type) VALUES ('IOI',$id,'contest')");
        if ($type==2) self::$db->Execute("INSERT INTO tags (tagname,id,type) VALUES ('ACM',$id,'contest')");
        if ($rated==0) self::$db->Execute("INSERT INTO tags (tagname,id,type) VALUES ('Unrated',$id,'contest')");
        if ($rated==1) self::$db->Execute("INSERT INTO tags (tagname,id,type) VALUES ('Rated',$id,'contest')");
        for ($i=0;$i<count($tag);$i++) {
            $t=trim($tag[$i]);
            if ($t=="") continue;
            self::$db->Execute("INSERT INTO tags (tagname,id,type) VALUES ('$t',$id,'contest')");
        } $fp=fopen("../../../contest/$id.md","w");
        fwrite($fp,$intro); fclose($fp);
    }

    /**
     * 获取比赛历史记录 ListContestByUid
     * @param int $uid 用户id
     * @return array|null
     */
    function ListContestByUid(int $uid):array|null {
        $arr=self::$db->Query("SELECT * FROM contest_signup WHERE uid=$uid ORDER BY id DESC");
        $res=array(); for ($i=0;$i<count($arr);$i++) {
            $c=self::$db->Query("SELECT * FROM contest WHERE id=".$arr[$i]["id"])[0];
            $t=array(); $t["id"]=$arr[$i]["id"]; $t["date"]=$c["starttime"]; $t["name"]=$c["title"]; $t["rated"]=$c["rated"];
            $tmp=self::$db->Query("SELECT uid,score FROM contest_ranking WHERE id=".$c["id"]." ORDER BY score DESC,time ASC");
            $n=count($tmp); $kk=1/pow(50,2/$n);
            for ($k=0;$k<count($tmp);$k++) {
                if ($tmp[$k]["uid"]==$uid) {
                    if ($c["rated"]!=0) $t["diff"]=floor(50*pow($kk,$k));
                    else $t["diff"]=0;
                    $t["rank"]=$k+1; $t["all"]=$n;
                    break;
                }
            } $res[]=$t;
        }
        return $res;
    }

    /**
     * 获取比赛允许的语言 GetLangById
     * @param int $id 比赛id
     * @return array|null
     */
    static function GetLangById(int $id):array|null {
        Contest_Controller::JudgeContestExist($id);
        $lang=self::$db->Query("SELECT * FROM contest WHERE id=$id")[0]["lang"];
        $x=explode(",",$lang); $res=array();
        $config=GetConfig();
        for($i=0;$i<count($x);$i++) {
            if (!is_numeric($x[$i])) continue;
            if ($x[$i]<0) continue;
            if ($x[$i]>=count($config["lang"])) continue;
            $res[]=intval($x[$i]);
        } if (count($res)==0) for ($i=0;$i<count($config["lang"]);$i++) $res[]=$i;
        return $res;
    }
}
?>