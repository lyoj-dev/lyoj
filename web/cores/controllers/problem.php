<?php
class Problem_Controller {
    static $db; 
    static $contest_controller;
    static $error_controller;
    static $login_controller;
    static $user_controller;

    function __construct() {
        self::$db=new Database_Controller;
        self::$contest_controller=new Contest_Controller;
        self::$login_controller=new Login_Controller;
        self::$user_controller=new User_Controller;
    }

    /**
     * 判断题目是否存在 JudgeProblemExist
     * @param int $pid 题目id
     * @return void
     */
    static function JudgeProblemExist(int $id):void {
        $p=self::$db->Query("SELECT id FROM problem WHERE id=$id");
        if (!count($p)) {
            $url=$_SERVER['REQUEST_URI'];
            $path=explode("/",$url);
            $api_mode=$path[1]=="api";
            if ($api_mode) API_Controller::error_problem_not_found($id);
            else Error_Controller::Common("Problem id $id not found");
        }
    }

    /**
     * 判断题目的公开程度 JudgeOpen
     * @param int $pid 题目id
     * @param int $cid 比赛id
     * @return bool
     */
    static function JudgeOpen(int $pid,int $cid):bool {
        Problem_Controller::JudgeProblemExist($pid);
        $uid=self::$login_controller->CheckLogin();
        $perm=0; if ($uid) $perm=self::$user_controller->GetWholeUserInfo($uid)["permission"];
        if ($perm>1) return 1;
        $problem=self::$db->Query("SELECT * FROM problem WHERE id=$pid")[0];
        $contest=self::$db->Query("SELECT * FROM contest_problem WHERE pid=$pid");
        for ($i=0;$i<count($contest);$i++) {
            if ($cid==$contest[$i]["id"]) {
                $c=self::$db->Query("SELECT * FROM contest WHERE id=".$contest[$i]["id"])[0];
                if (self::$contest_controller->JudgeSignup($contest[$i]["id"])&&
                    $c["starttime"]+$c["duration"]>=time()&&$c["starttime"]<=time()) return 1;
            }
        } if ($problem["hidden"]||$problem["banned"]) return 0;
        for ($i=0;$i<count($contest);$i++) {
            $c=self::$db->Query("SELECT * FROM contest WHERE id=".$contest[$i]["id"])[0];
            if ($c["starttime"]+$c["duration"]<time()) continue; return 0;
        } return 1;
    }

    /**
     * 判断题目是否在某一场比赛内 JudgeContest
     * @param int $pid 题目id
     * @param int $cid 比赛id
     * @return bool
     */
    static function JudgeContest(int $pid,int $cid):bool {
        Problem_Controller::JudgeProblemExist($pid);
        $array=self::$db->Query("SELECT * FROM contest_problem WHERE pid=$pid AND id=$cid");
        return count($array)>0?1:0;
    }

    /**
     * 以pid列举题目信息 ListProblemByPid
     * @param int $pid 题目id
     * @param bool $strong=false 是否强制执行
     * @param int $contest=0 所属比赛
     * @return array|null
     */
    static function ListProblemByPid(int $pid,bool $strong=false,int $contest=0):array|null {
        Problem_Controller::JudgeProblemExist($pid);
        if ($contest!=0) Contest_Controller::JudgeContestExist($contest);
        $array=self::$db->Query("SELECT * FROM problem WHERE id=$pid"); $array=$array[0]; 
        $uid=self::$login_controller->CheckLogin();
        if ($strong) return $array; $con="";
        if (!Problem_Controller::JudgeOpen($pid,$contest)) Error_Controller::Common("Permission Denied");
        if (Problem_Controller::JudgeContest($pid,$contest)) {
            $c=self::$db->Query("SELECT * FROM contest WHERE id=$contest")[0];
            $con.="AND time>=".$c["starttime"]." AND time<=".($c["starttime"]+$c["duration"]." AND contest=$contest");
        } $tmp=count(self::$db->Query("SELECT id FROM status WHERE uid=$uid AND pid=$pid $con"));
        if ($tmp>0) $array["accepted"]=1;
        else $array["accepted"]=0;
        return $array;
    }

    /**
     * 以现存题目列举题目信息 ListProblemByNumber
     * @param float $l=1 题目数量的左边界
     * @param float $r=1e18 题目数量的右边界
     * @param string $key="" 题目名称关键词
     * @param array|null $tag 题目标签
     * @param int $L=1 题目id左区间
     * @param int $R=1e18 题目id右区间
     * @param float $num 结果总数
     * @return array|null
     */
    static function ListProblemByNumber(
        float $l=1,float $r=1e18,string $key="",array|null $tag,array|null $diff,float $L=1,float $R=1e18,float& $num):array|null {
        $uid=self::$login_controller->CheckLogin(); $permission=$uid;
        if ($uid) {
            $uinfo=self::$user_controller->GetWholeUserInfo($uid);
            $permission=$uinfo["permission"];
        } $diffs=" AND ("; for ($i=0;$i<count($diff);$i++) $diffs.=($i!=0?"OR ":"")."difficult=".$diff[$i]." "; $diffs.=")"; 
        $array=self::$db->Query("SELECT * FROM problem WHERE pid>=$L AND pid<=$R AND ".($key!=""?"name LIKE '%$key%' AND ":"")."id>=0".($permission<=1?" AND banned=0 AND hidden=0":"").(count($diff)?$diffs:"")." ORDER BY pid");
        for ($i=0;$i<count($array);$i++)
            if (!Problem_Controller::JudgeOpen($array[$i]["id"],0))
            {array_splice($array,$i,1);$i--;}
        if ($tag==null) {
            $res2=array();
            for ($i=$l-1;$i<count($array)&&$i<$r;$i++) $res2[]=$array[$i];
            $num=count($array); 
            $login_controller=new Login_Controller;
            $uid=$login_controller->CheckLogin();
            for ($i=0;$i<count($res2);$i++) {
                $tmp=self::$db->Query("SELECT id FROM status WHERE pid=".$res2[$i]["id"]." AND uid=$uid AND status='Accepted'");
                if (count($tmp)>0) $res2[$i]["accepted"]=1;
                else $res2[$i]["accepted"]=0;
            } return $res2;
        } $tags=" WHERE ("; for ($i=0;$i<count($tag);$i++) $tags.=($i!=0?"OR ":"")."tagname='".$tag[$i]."' "; $tags.=") AND type='problem'"; 
        $array2=self::$db->Query("SELECT id FROM tags".(count($tag)?$tags:""));
        $a=array(); for ($i=0;$i<count($array2);$i++) $a[]=$array2[$i]["id"];
        $res=array(); for ($i=0;$i<count($array);$i++) {
            if (array_search($array[$i]["id"],$a)===false) ;
            else $res[]=$array[$i];
        } $res2=array(); $num=count($res);
        for ($i=$l-1;$i<count($res)&&$i<$r;$i++) $res2[]=$res[$i];
        $login_controller=new Login_Controller;
        $uid=$login_controller->CheckLogin();
        for ($i=0;$i<count($res2);$i++) {
            $tmp=self::$db->Query("SELECT id FROM status WHERE pid=".$res2[$i]["id"]." AND uid=$uid AND status='Accepted'");
            if (count($tmp)>0) $res2[$i]["accepted"]=1;
            else $res2[$i]["accepted"]=0;
        } return $res2;
    }

    /**
     * 获取题目总数 GetProblemTotal
     * @return int
     */
    static function GetProblemTotal():int {
        $array=self::$db->Query("SELECT * FROM problem WHERE banned=0 AND contest=0 AND hidden=0");
        return $array==null?0:count($array);
    }

    /**
     * 输出api题目信息 OutputAPIInfo
     * @param int $pid 题目id
     * @return array|null
     */
    static function OutputAPIInfo(int $pid):array|null {
        Problem_Controller::JudgeProblemExist($pid);
        $array=self::$db->Query("SELECT * FROM problem WHERE id=$pid")[0];
        $uid=self::$login_controller->CheckLogin();
        if (!Problem_Controller::JudgeOpen($pid,0)) API_Controller::error_permission_denied();
        $tmp=count(self::$db->Query("SELECT id FROM status WHERE uid=$uid AND pid=$pid"));
        if ($tmp>0) $array["accepted"]=1;
        else $array["accepted"]=0;
        return $array;
    }

    /**
     * 输出题目配置 OutputAPIConfig
     * @param int $pid 题目id
     * @return array|null
     */
    static function OutputAPIConfig(int $pid):array|null {
        Problem_Controller::JudgeProblemExist($pid);
        $array=self::$db->Query("SELECT * FROM problem WHERE id=$pid");
        $array=$array[0]; $fp=fopen("../../../problem/$pid/config.json","r");
        $json=fread($fp,filesize("../../../problem/$pid/config.json"));
        $json=json_decode($json,JSON_UNESCAPED_UNICODE);
        $uid=self::$login_controller->CheckLogin();
        if ($array["contest"]!=0) {
            $contest=self::$contest_controller->GetContest($array["contest"],$array["contest"]);
            $endtime=$contest[0]["starttime"]+$contest[0]["duration"];
            if ($endtime<time()) return $json; 
        } if ($array["contest"]==0&&!$array["banned"]&&!$array["hidden"]) return $json; 
        $uid=self::$login_controller->CheckLogin();
        if (!$uid) API_Controller::error_permission_denied();
        $uinfo=self::$user_controller->GetWholeUserInfo($uid);
        if ($uinfo["permission"]>1) return $json;
        if ($array["banned"]||$array["hidden"]) API_Controller::error_permission_denied();
        if ($array["contest"]!=$_GET["contest"]) API_Controller::error_permission_denied();
        if (self::$contest_controller->JudgeSignup($array["contest"])==false) 
        API_Controller::error_permission_denied();
        $info=self::$contest_controller->GetContest($array["contest"]);
        if ($info["starttime"]>time()) API_Controller::error_permission_denied();
        return $json;
    }

    /**
     * 更新题目显示信息 UpdateProblemHidden
     * @param int $pid 题目id
     * @return bool
     */
    static function UpdateProblemHidden(int $pid):bool {
        Problem_Controller::JudgeProblemExist($pid);
        $hidden=self::$db->Query("SELECT * FROM problem WHERE id=$pid")[0]["hidden"];
        if ($hidden) self::$db->Execute("UPDATE problem SET hidden=0 WHERE id=$pid");
        else self::$db->Execute("UPDATE problem SET hidden=1 WHERE id=$pid");
        return 1-$hidden;
    }

    /**
     * 删除目录下所有文件 deldir
     * @param string $path 文件夹目录
     * @return mixed
     */
    static function deldir(string $path) {
        if(is_dir($path)){
            $p=scandir($path);
            if(count($p)>2) foreach($p as $val) if($val!="."&&$val!="..")
                if(is_dir($path.$val)) Problem_Controller::deldir($path.$val.'/');
                else unlink($path.$val);
        } return rmdir($path);
    }

    /**
     * 新建题目 CreateProblem
     * @param int $ppid 对外显示id
     * @param string $background 题目背景
     * @param string $description 题目描述
     * @param string $input 输入描述
     * @param string $output 输出描述
     * @param string $input_file 输入文件名
     * @param string $output_file 输出文件名
     * @param string $sample 样例数据JSON
     * @param string $hint 提示
     * @param string $title 题目标题
     * @param string $data 数据包base64
     * @param int $time_limit=1000 时间限制
     * @param int $memory_limit=131072 空间限制
     * @param int $full_score=100 题目总分
     * @param int $difficult=0 题目难度
     * @param int $spj_type=1 评测类型
     * @param string $spj_source="" SPJ源文件
     * @param string $spj_compile="" SPJ编译指令
     * @param string $spj_name="" SPJ可执行文件名
     * @param string $spj_param="" SPJ执行附加参数
     * @param array|null $tags=null 题目标签
     * @return int
     */
    static function CreateProblem(int $ppid,
        string $background,string $description,string $input,string $output,
        string $input_file,string $output_file,string $sample,string $hint,
        string $title,string $data,int $time_limit=1000,int $memory_limit=131072,int $full_score=100,int $difficult=0,
        int $spj_type=1,string $spj_source="",string $spj_compile="",string $spj_name="",string $spj_param="",
        array|null $tags=null):int {
        $id=self::$db->Query("SELECT id FROM problem ORDER BY id DESC LIMIT 1")[0]["id"]+1;
        if (count(self::$db->Query("SELECT id FROM problem WHERE pid=$ppid"))) API_Controller::error_problem_id_exist($ppid);
        $data=base64_decode($data); Problem_Controller::deldir("../../../problem/$id/");
        mkdir("../../../problem/$id/",0777); 
        $fp=fopen("../../../problem/$id/data.zip","wb");fwrite($fp,$data);
        fclose($fp); $zip=new ZipArchive();
        $filePath=realpath("../../../problem/$id/data.zip");
        $path=realpath("../../../problem/$id/");
        if ($zip->open($filePath)===true) {
            $zip->extractTo($path);
            $zip->close();
        } else {unlink($filePath);API_Controller::error_invalid_zip();exit;}
        unlink($filePath);
        $files=scandir("../../../problem/$id/");$bracket=array();
        for ($i=0;$i<count($files);$i++) {
            if ($files[$i]=="."||$files[$i]=="..") continue;
            $tmp=explode(".",$files[$i]);
            $extension=$tmp[count($tmp)-1];
            $name=substr($files[$i],0,strlen($files[$i])-strlen($extension)-1);
            if (!array_key_exists($name,$bracket)) $bracket[$name]=array();
            $bracket[$name][]=$extension;
        } $json=array(
            "input"=>$input_file,"output"=>$output_file,
            "spj"=>array(
                "type"=>$spj_type,"source"=>$spj_source,"compile_cmd"=>$spj_compile,
                "exec_path"=>$spj_name,"exec_name"=>$spj_name,"exec_param"=>$spj_param
            ),"data"=>array(),"subtask_depend"=>array()
        ); ksort($bracket,SORT_STRING|SORT_FLAG_CASE|SORT_NATURAL);
        foreach ($bracket as $key => $value) {
            $accepted=true;$exist_in=false;$exist_out=false;
            for ($i=0;$i<count($value);$i++) {
                if ($value[$i]!="in"&&$value[$i]!="out"&&$value[$i]!="ans") $accepted=false;
                else if ($value[$i]=="in") $exist_in=true;
                else $exist_out=true;
            } if (count($value)==2&&$accepted&&$exist_in&&$exist_out) {
                $array=array(
                    "input"=>$key.".".($value[0]=="in"?$value[0]:$value[1]),
                    "output"=>$key.".".($value[0]=="in"?$value[1]:$value[0]),
                    "score"=>0,"time"=>$time_limit,"memory"=>$memory_limit,"subtask"=>0
                ); $json["data"][]=$array;
            }
        } if (count($json["data"])) $min=intval($full_score/count($json["data"]));$max=count($json["data"])-($full_score-count($json["data"])*$min);
        for ($i=0;$i<count($json["data"]);$i++) $json["data"][$i]["score"]=($i<$max?$min:$min+1);
        $fp=fopen("../../../problem/$id/config.json","wb");fwrite($fp,json_encode($json));fclose($fp);
        // $title=str_replace("'","\\'",$title);
        // $background=str_replace("'","\\'",$background);
        // $description=str_replace("'","\\'",$description);
        // $input=str_replace("'","\\'",$input);
        // $output=str_replace("'","\\'",$output);
        // $sample=str_replace("'","\\'",$sample);
        // $hint=str_replace("'","\\'",$hint);
        foreach($tags as $key=>$value) self::$db->Execute("INSERT INTO tags (tagname,id,type) VALUES ('$value',$id,'problem')");
        $sql="INSERT INTO problem (pid,id,name,bg,descrip,input,output,cases,hint,difficult,hidden,banned) VALUES ($ppid,$id,'$title','$background'".
        ",'$description','$input','$output','$sample','$hint',$difficult,0,0)";
        self::$db->Execute($sql);
        return $id;
    }

    /**
     * 修改题目 UpdateProblem
     * @param int $pid 题目id
     * @param int $ppid 对外显示id
     * @param string $background 题目背景
     * @param string $description 题目描述
     * @param string $input 输入描述
     * @param string $output 输出描述
     * @param string $input_file 输入文件名
     * @param string $output_file 输出文件名
     * @param string $sample 样例数据JSON
     * @param string $hint 提示
     * @param string $title 题目标题
     * @param array|null $time_limit 时间限制
     * @param array|null $memory_limit 空间限制
     * @param array|null $score 测试点分数
     * @param array|null $subtask=null 子任务信息
     * @param string $subtask_dependence="" 子任务依赖信息
     * @param int $difficult=0 题目难度
     * @param int $spj_type=1 评测类型
     * @param string $spj_source="" SPJ源文件
     * @param string $spj_compile="" SPJ编译指令
     * @param string $spj_name="" SPJ可执行文件名
     * @param string $spj_param="" SPJ执行附加参数
     * @param array|null $tags=null 题目标签
     * @return void
     */
    static function UpdateProblem( int $pid,int $ppid,
        string $background,string $description,string $input,string $output,
        string $input_file,string $output_file,string $sample,string $hint,
        string $title,array|null $time_limit,array|null $memory_limit,array|null $score,
        array|null $subtask=null,string $subtask_dependence="",int $difficult=0,
        int $spj_type=1,string $spj_source="",string $spj_compile="",string $spj_name="",
        string $spj_param="",array|null $tags=null):void {
        Problem_Controller::JudgeProblemExist($pid);
        $array=self::$db->Query("SELECT * FROM problem WHERE id=$pid");
        if (count($array)==0) return; $ppid_num=self::$db->Query("SELECT id FROM problem WHERE pid=$ppid");
        if (!(count($ppid_num)==0||count($ppid_num)==1&&$ppid_num[0]["id"]==$pid)) API_Controller::error_problem_id_exist($ppid);
        self::$db->Execute("UPDATE problem SET pid=$ppid WHERE id=$pid");
        if ($background!="") self::$db->Execute("UPDATE problem SET bg='$background' WHERE id=$pid");
        if ($description!="") self::$db->Execute("UPDATE problem SET descrip='$description' WHERE id=$pid");
        if ($input!="") self::$db->Execute("UPDATE problem SET input='$input' WHERE id=$pid");
        if ($output!="") self::$db->Execute("UPDATE problem SET output='$output' WHERE id=$pid");
        if ($sample!="") self::$db->Execute("UPDATE problem SET cases='$sample' WHERE id=$pid");
        if ($hint!="") self::$db->Execute("UPDATE problem SET hint='$hint' WHERE id=$pid");
        if ($difficult!="") self::$db->Execute("UPDATE problem SET difficult='$difficult' WHERE id=$pid");
        if ($title!="") self::$db->Execute("UPDATE problem SET name='$title' WHERE id=$pid");
        $fp=fopen("../../../problem/$pid/config.json","r");
        $json=fread($fp,filesize("../../../problem/$pid/config.json"));
        $json=json_decode($json,true); fclose($fp);
        if ($input_file!="") $json["input"]=$input_file;
        if ($output_file!="") $json["output"]=$output_file;
        for ($i=0;$i<count($json["data"]);$i++) {
            if (!array_key_exists($i,$time_limit)) API_Controller::error_param_not_found("\$_POST['time'][$i]");
            if (!array_key_exists($i,$memory_limit)) API_Controller::error_param_not_found("\$_POST['memory'][$i]");
            if (!array_key_exists($i,$score)) API_Controller::error_param_not_found("\$_POST['score'][$i]");
            if (!array_key_exists($i,$subtask)) API_Controller::error_param_not_found("\$_POST['subtask'][$i]");
            $json["data"][$i]["time"]=$time_limit[$i];
            $json["data"][$i]["memory"]=$memory_limit[$i];
            $json["data"][$i]["score"]=$score[$i];
            $json["data"][$i]["subtask"]=$subtask[$i];
        } $json["spj"]=array(
            "type"=>$spj_type,"source"=>$spj_source,"compile_cmd"=>$spj_compile,
            "exec_path"=>$spj_name,"exec_name"=>$spj_name,"exec_param"=>$spj_param
        ); $json["subtask_depend"]=array(); $sd=explode("\n",$subtask_dependence);
        for ($i=0;$i<count($sd);$i++) {
            $info=$sd[$i]; $info=explode(",",$info);
            for ($j=count($info)-1;$j>=0;$j--) if ($info[$j]=="") unset($info[$j]);
            array_values($info);
            for ($j=0;$j<count($info);$j++) $info[$j]=intval($info[$j]);
            $json["subtask_depend"][]=$info;
        } $fp=fopen("../../../problem/$pid/config.json","w");
        fwrite($fp,json_encode($json,JSON_UNESCAPED_UNICODE));
        fclose($fp); self::$db->Execute("DELETE FROM tags WHERE id=$pid AND type='problem'");
        foreach($tags as $key=>$value) self::$db->Execute("INSERT INTO tags (tagname,id,type) VALUES ('$value',$pid,'problem')");
    }

    /**
     * 重新上传题目数据 UploadData
     * @param int $pid 题目id
     * @param string $data 题目数据包
     * @param int $time_limit=1000 时间限制
     * @param int $memory_limit=131072 空间限制
     * @param int $full_score=100 题目总分
     * @return void
     */
    static function UploadData(int $pid,string $data,int $time_limit=1000,int $memory_limit=131072,int $full_score=100):void {
        Problem_Controller::JudgeProblemExist($pid);
        $fp=fopen("../../../problem/$pid/config.json","r");
        $json=fread($fp,filesize("../../../problem/$pid/config.json"));
        $json=json_decode($json,true); fclose($fp);
        $data=base64_decode($data); Problem_Controller::deldir("../../../problem/$pid/");
        mkdir("../../../problem/$pid/",0777);
        $fp=fopen("../../../problem/$pid/data.zip","wb");
        fwrite($fp,$data); fclose($fp);
        $zip=new ZipArchive();
        $filePath=realpath("../../../problem/$pid/data.zip");
        $path=realpath("../../../problem/$pid/");
        if ($zip->open($filePath)===true) {
            $zip->extractTo($path);
            $zip->close();
        } else {unlink($filePath); API_Controller::error_invalid_zip();exit;}
        unlink($filePath);
        $files=scandir("../../../problem/$pid/");$bracket=array();
        for ($i=0;$i<count($files);$i++) {
            if ($files[$i]=="."||$files[$i]=="..") continue;
            $tmp=explode(".",$files[$i]);
            $extension=$tmp[count($tmp)-1];
            $name=substr($files[$i],0,strlen($files[$i])-strlen($extension)-1);
            if (!array_key_exists($name,$bracket)) $bracket[$name]=array();
            $bracket[$name][]=$extension;
        } ksort($bracket,SORT_STRING|SORT_FLAG_CASE|SORT_NATURAL);
        $json["data"]=array();
        foreach ($bracket as $key => $value) {
            $accepted=true;$exist_in=false;$exist_out=false;
            for ($i=0;$i<count($value);$i++) {
                if ($value[$i]!="in"&&$value[$i]!="out"&&$value[$i]!="ans") $accepted=false;
                else if ($value[$i]=="in") $exist_in=true;
                else $exist_out=true;
            } if (count($value)==2&&$accepted&&$exist_in&&$exist_out) {
                $array=array(
                    "input"=>$key.".".($value[0]=="in"?$value[0]:$value[1]),
                    "output"=>$key.".".($value[0]=="in"?$value[1]:$value[0]),
                    "score"=>0,"time"=>$time_limit,"memory"=>$memory_limit
                ); $json["data"][]=$array;
            }
        } if (count($json["data"])) $min=intval($full_score/count($json["data"]));$max=count($json["data"])-($full_score-count($json["data"])*$min);
        for ($i=0;$i<count($json["data"]);$i++) $json["data"][$i]["score"]=($i<$max?$min:$min+1);
        $fp=fopen("../../../problem/$pid/config.json","wb");fwrite($fp,json_encode($json));fclose($fp);
    }

    /**
     * 上传附加文件 UploadFile
     * @param int $pid 题目id
     * @param string $data 数据base64
     * @param string $name 文件名
     * @return void
     */
    static function UploadFile(int $pid,string $data,string $name):void {
        Problem_Controller::JudgeProblemExist($pid);
        if (!is_dir("../../files/$pid")) mkdir("../../files/$pid",0777);
        $data=base64_decode($data);
        $path="../../files/$pid/$name";
        touch($path); $fp=fopen("../../files/$pid/$name","wb");
        fwrite($fp,$data); fclose($fp);
    }

    /**
     * 删除附加文件 DeleteFile
     * @param int $pid 题目id
     * @param string $name 文件名
     * @return void
     */
    static function DeleteFile(int $pid,string $name):void {
        Problem_Controller::JudgeProblemExist($pid);
        unlink("../../files/$pid/$name");
    }

    /**
     * 获取 Subtask 信息 GetSubtaskInfo
     * @param int $pid 题目id
     * @return array|null
     */
    static function GetSubtaskInfo(int $pid):array|null {
        Problem_Controller::JudgeProblemExist($pid);
        $fp=fopen("../problem/$pid/config.json","r");
        $json=fread($fp,filesize("../problem/$pid/config.json"));
        $json=json_decode($json,true);
        $bracket=array();
        for ($i=0;$i<count($json["data"]);$i++)
            $bracket[$json["data"][$i]["subtask"]]++;
        return $bracket;
    }

    /**
     * 整题重测 RejudgeProblem
     * @param int $pid 题目id
     * @return void
     */
    static function RejudgeProblem(int $pid):void {
        Problem_Controller::JudgeProblemExist($pid);
        self::$db->Execute("UPDATE status SET judged=0,status='Waiting...',result='NULL' WHERE pid=$pid");
    }

    /**
     * 删除题目 DeleteProblem
     * @param int $pid 题目id
     * @return void
     */
    static function DeleteProblem(int $pid):void {
        Problem_Controller::JudgeProblemExist($pid);
        self::$db->Execute("DELETE FROM problem WHERE id=$pid");
        self::$db->Execute("DELETE FROM status WHERE pid=$pid");
        self::$db->Execute("DELETE FROM tags WHERE id=$pid AND type='problem'");
        self::deldir("../../../problem/$pid/");
    }

    /**
     * 获取pid对应id GetIdByPid
     * @param int $pid 对外显示id
     * @return int|false
     */
    static function GetIdByPid(int $pid):int|false {
        $arr=self::$db->Query("SELECT id FROM problem WHERE pid=$pid");
        if (count($arr)) return $arr[0]["id"];
        return false;
    }

    /**
     * 获取id对应pid GetPidById
     * @param int $id 题目id
     * @return int|false
     */
    static function GetPidById(int $id):int|false {
        $arr=self::$db->Query("SELECT pid FROM problem WHERE id=$id");
        if (count($arr)) return $arr[0]["pid"];
        return false;
    }
}
?>