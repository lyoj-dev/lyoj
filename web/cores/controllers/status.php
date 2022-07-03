<?php
class Status_Controller{
    static $db;
    function __construct() {
        self::$db=new Database_Controller;
    }

    /**
     * 判断提交是否存在 JudgeStatusExist
     * @param int $id 提交id
     * @return void
     */
    static function JudgeStatusExist(int $id):void {
        $p=self::$db->Query("SELECT id FROM status WHERE id=$id");
        if (!count($p)) {
            $url=$_SERVER['REQUEST_URI'];
            $path=explode("/",$url);
            $api_mode=$path[1]=="api";
            if ($api_mode) API_Controller::error_status_not_found($id);
            else Error_Controller::Common("Status id $id not found");
        }
    }

    /**
     * 根据用户uid列举所有状态 ListWholeByUid
     * @param int $uid 用户uid
     * @return array|null
     */
    static function ListWholeByUid(int $uid):array|null {
        User_Controller::JudgeUserExist($uid);
        $array=self::$db->Query("SELECT id FROM status WHERE uid=$uid");
        return $array;
    }

    /**
     * 根据用户uid列举正确状态 ListAcceptedByUid
     * @param int $uid 用户uid
     * @return array|null
     */
    static function ListAcceptedByUid(int $uid):array|null {
        User_Controller::JudgeUserExist($uid);
        $array=self::$db->Query("SELECT pid FROM status WHERE uid=$uid AND status='Accepted'");
        $res=array(); for ($i=0;$i<count($array);$i++) $res[]=$array[$i]["pid"];
        $array=array_unique($res); $array=array_values($array); sort($array);
        return $array;
    }

    /**
     * 根据题目pid列举所有状态 ListWholeByPid
     * @param int $pid 题目pid
     * @return array|null  
     */
    static function ListWholeByPid(int $pid):array|null {
        Problem_Controller::JudgeProblemExist($pid);
        $array=self::$db->Query("SELECT id FROM status WHERE pid=$pid");
        return $array;
    }

    /**
     * 根据题目pid列举正确状态 ListAcceptedByPid
     * @param int $pid 题目pid
     * @return array|null
     */
    static function ListAcceptedByPid(int $pid):array|null {
        Problem_Controller::JudgeProblemExist($pid);
        $array=self::$db->Query("SELECT id FROM status WHERE pid=$pid AND status='Accepted'");
        return $array;
    }

    /**
     * 列举所有已测评的状态 ListJudgedStatus
     * @return array|null
     */
    static function ListJudgedStatus():array|null {
        return self::$db->Query("SELECT id FROM status WHERE judged=1");
    }
    
    /**
     * 列举所有未测评的状态 ListJudgingStatus
     * @return array|null
     */
    static function ListJudgingStatus():array|null {
        return self::$db->Query("SELECT id FROM status WHERE judged=0");
    }

    /**
     * 题目提交函数 Submit
     * @param string $lang 语言id
     * @param string $code 代码
     * @param int $uid 用户id
     * @param int $pid 题目id
     * @param int $cid=0 比赛id
     * @return int
     */
    static function Submit(string $lang,string $code,int $uid,int $pid,int $cid=0):int {
        User_Controller::JudgeUserExist($uid);
        Problem_Controller::JudgeProblemExist($pid);
        $sid=self::$db->Query("SELECT id FROM status ORDER BY id DESC")[0]["id"]+1;
        $c=self::$db->Query("SELECT * FROM contest WHERE id=$cid")[0];
        if ($c["starttime"]+$c["duration"]<time()) $cid=0;
        // $keyword=["reboot"];
        // if (strpos($code,"reboot")!==FALSE) {
        //     $json=array("result"=>"Compile Error","output"=>"Compile Error",
        //     "compile_info"=>"Find key word \\\"reboot\\\" in code!");
        //     self::$db->Execute("INSERT INTO status (id,uid,pid,lang,code,result,time,status,ideinfo,judged,contest) VALUES 
        //     ($sid,$uid,$pid,$lang,'$code','".json_encode($json,JSON_UNESCAPED_UNICODE)."',".time().",'Compile Error','NULL',1,$cid)"); return $sid;
        // } else 
        self::$db->Execute("INSERT INTO status (id,uid,pid,lang,code,result,time,status,ideinfo,judged,contest) VALUES 
        ($sid,$uid,$pid,$lang,'$code','',".time().",'Waiting...','NULL',0,$cid)"); return $sid;
    }

    /**
     * 根据测评id获取测评状态 GetJudgeStatusById
     * @param int $id 测评id
     * @return array|null
     */
    static function GetJudgeStatusById(int $id):string|null {
        Status_Controller::JudgeStatusExist($id);
        $array=self::$db->Query("SELECT result,judged FROM status WHERE id=$id");
        if ($array==null) return null;
        if ($array[0]["judged"]==false) return $array[0]["status"];
        $json=json_decode($array[0]["result"],true);
        return $json["result"];
    }

    /**
     * 根据测评id获取测评结果 GetJudgeResultById
     * @param int $id 测评id
     * @return array|null
     */
    static function GetJudgeResultById(int $id):array|null {
        Status_Controller::JudgeStatusExist($id);
        $array=self::$db->Query("SELECT result,judged FROM status WHERE id=$id");
        if ($array==null) return null;
        if ($array[0]["judged"]==false) return null;
        return json_decode($array[0]["result"],true);
    }

    /**
     * 根据测评id获取测评信息 GetJudgeInfoById
     * @param int $id 测评id
     * @return array|null
     */
    static function GetJudgeInfoById(int $id):array|null {
        Status_Controller::JudgeStatusExist($id);
        $array=self::$db->Query("SELECT * FROM status WHERE id=$id");
        if ($array==null) return null;
        if ($array[0]["contest"]==0) return $array[0];
        $c=self::$db->Query("SELECT * FROM contest WHERE id=".$array[0]["contest"]);
        $endtime=$c[0]["starttime"]+$c[0]["duration"];
        if ($c[0]["type"]!=0||$endtime<time()) return $array[0];
        $status=$array[0]["status"];
        switch($status) {
            case "Accepted":$status="Submitted";break;
            case "Submitted":$status="Submitted";break;
            case "Wrong Answer":$status="Submitted";break;
            case "Compile Error":$status="Submitted";break;
            case "Time Limited Exceeded":$status="Submitted";break;
            case "Memory Limited Exceeded":$status="Submitted";break;
            case "Runtime Error":$status="Submitted";break;
            default: $status="Waiting...";break;
        }; $array[0]["status"]=$status;
        $array[0]["result"]="{}";
        return $array[0];
    }

    /**
     * 搜索测评信息 GetJudgeInfo
     * @param float $l=1 sid左边界
     * @param float $r=1e18 sid右边界
     * @param string $user 用户名
     * @param int $pid 题目id
     * @param int &$sum 符合条件的状态总数
     * @return array|null
     */
    function GetJudgeInfo(float $l=1,float $r=1e18,string $user,int $pid,int &$sum):array|null {
        $sql="SELECT id,uid,pid,lang,code,time,status,ideinfo,judged,contest FROM status WHERE"; $head=0; $uid=0;
        if (is_numeric($user)) $uid=$user;
        else {$info=self::$db->Query("SELECT * FROM user WHERE name='$user'");
        $uid=count($info)?$info[0]["id"]:0;}
        if ($uid==0&&$pid==0){$sql.=(!$head?"":" AND")." contest=0"; $head=1;}
        if ($uid!=0){$sql.=(!$head?"":" AND")." uid=$uid"; $head=1;}
        if ($pid!=0){$sql.=(!$head?"":" AND")." pid=$pid"; $head=1;}
        $sql.=" ORDER BY id DESC";
        $array=self::$db->Query($sql);
        $sum=count($array); $result=array();
        // print_r($sum); exit;
        for ($i=$l-1;$i<$r&&$i<count($array);$i++) {
            $c=self::$db->Query("SELECT * FROM contest WHERE id=".$array[$i]["contest"]);
            $endtime=$c[0]["starttime"]+$c[0]["duration"];
            if ($c[0]["type"]!=0||$endtime<time()) {$result[]=$array[$i]; continue;}
            $status=$array[$i]["status"];
            switch($status) {
                case "Accepted":$status="Submitted";break;
                case "Submitted":$status="Submitted";break;
                case "Wrong Answer":$status="Submitted";break;
                case "Compile Error":$status="Submitted";break;
                case "Time Limited Exceeded":$status="Submitted";break;
                case "Memory Limited Exceeded":$status="Submitted";break;
                case "Runtime Error":$status="Submitted";break;
                default: $status="Waiting...";break;
            }; $array[$i]["status"]=$status;
            $result[]=$array[$i];
        } return $result;
    }

    /**
     * 重新测评提交函数 SubmitRejudge
     * @param int $id 评测id
     * @return void
     */
    function SubmitRejudge(int $id):void {
        Status_Controller::JudgeStatusExist($id);
        self::$db->Execute("UPDATE status SET judged=0,status='Waiting...',result='NULL' WHERE id=$id");
    }
}
?>