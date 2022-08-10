<?php
class Admin_Controller {
    /**
     * 获取CPU信息 GetCPUInfo
     * @return array|null
     */
    function GetCPUInfo():array|null {
        $res=""; exec("top -bn 1 -i -c",$res);
        $res=$res[2]; $res=substr($res,9);
        $res=explode(",",$res); $arr=array();
        for ($i=0;$i<count($res);$i++) {
            $tmp=$res[$i]; $tmp=explode(" ",$tmp);
            $pt=0; while ($tmp[$pt]=="") $pt++;
            $arr[$tmp[$pt+1]]=$tmp[$pt];
        } $cpuinfo=[];
		if (!($str=@file("/proc/cpuinfo"))) return null;
		$str=implode("",$str);
		@preg_match_all("/processor\s{0,}\:+\s{0,}([\w\s\)\(\@.-]+)([\r\n]+)/s",$str,$processor);
		$tmp2=explode("\n",$str); $tmp3=array();
        for ($i=0;$i<count($tmp2);$i++) $tmp3[]=explode(":",$tmp2[$i]);
        $id=-1; for ($i=0;$i<count($tmp3);$i++) 
        if (trim($tmp3[$i][0])=="model name") {$id=$i; break;}
		$cpuinfo['Name']=$id==-1?"null":trim($tmp3[$id][1]);
		$cpuinfo['Cores']=sizeof($processor[1]);
        $cpuinfo['Usage']=$arr;
        return $cpuinfo;
    }

    /**
     * 获取系统内存信息 GetMemoryInfo
     * @return array|null
     */
    function GetMemoryInfo():array|null {
        $meminfo = [];
		if (!($str = @file('/proc/meminfo'))) return null;
		$str=implode('',$str);
		preg_match_all("/MemTotal\s{0,}\:+\s{0,}([\d\.]+).+?MemFree\s{0,}\:+\s{0,}([\d\.]+).+?Cached\s{0,}\:+\s{0,}([\d\.]+).+?SwapTotal\s{0,}\:+\s{0,}([\d\.]+).+?SwapFree\s{0,}\:+\s{0,}([\d\.]+)/s",$str,$buf);
		preg_match_all("/Buffers\s{0,}\:+\s{0,}([\d\.]+)/s",$str,$buffers);
		$meminfo['memTotal']=round($buf[1][0]/1024,1);
		$meminfo['memFree']=round(($buf[2][0]+$buffers[1][0]+$buf[3][0])/1024,1);
		$meminfo['memUsed']=round($meminfo['memTotal']-$meminfo['memFree'],1);
		$meminfo['memPercent']=(floatval($meminfo['memTotal'])!=0)?round($meminfo['memUsed']/$meminfo['memTotal']*100,1):0;
		
		$meminfo['swapTotal']=round($buf[4][0]/1024,1);
		$meminfo['swapFree']=round($buf[5][0]/1024,1);
		$meminfo['swapUsed']=round($meminfo['swapTotal']-$meminfo['swapFree'],1);
		$meminfo['swapPercent']=(floatval($meminfo['swapTotal'])!=0)?round($meminfo['swapUsed']/$meminfo['swapTotal']*100,1):0;
		
		foreach ($meminfo as $key => $value) {
		    if (strpos($key,'Percent')>0) continue;
		    if ($value<1024) $meminfo[$key].=' M';
		    else $meminfo[$key]=round($value/1024,1).' G';
		} return $meminfo;
    }

    /**
     * 获取磁盘信息 GetDiskInfo
     * @return array|null
     */
    function GetDiskInfo():array|null {
        $diskinfo=[];
		$diskinfo['diskTotal']=round(@disk_total_space('.')/(1024*1024),1);
		$diskinfo['diskFree']=round(@disk_free_space('.')/(1024*1024),1);
		$diskinfo['diskUsed']=round($diskinfo['diskTotal']-$diskinfo['diskFree'],1);
		$diskinfo['diskPercent']=0;
		if (floatval($diskinfo['diskTotal'])!=0) $diskinfo['diskPercent']=round($diskinfo['diskUsed']/$diskinfo['diskTotal']*100,1);
		foreach ($diskinfo as $key => $value) {
			if (strpos($key,'Percent')>0||strpos($key,'Capt')>0||strpos($key,'Name')>0) continue;
			if ($value<1024) $diskinfo[$key].=' M';
			else $diskinfo[$key]=round($value/1024,1).' G';
		} return $diskinfo;
    }

    /**
     * 获取数据库信息 GetDatabaseInfo
     * @return array|null
     */
    function GetDatabaseInfo():array|null {
        $dbinfo=array(); $config=GetConfig();
        $dbinfo["status"]=shell_exec("ps -ef | grep mysqld | grep -v grep")==""?"inactive (dead)":"active (running)";
        $dbinfo["version"]=shell_exec("mysql --version");
        $dbinfo["info"]=$config["mysql"]["user"].":".$config["mysql"]["passwd"]."@".$config["mysql"]["server"].":".$config["mysql"]["port"];
        $conn=mysqli_connect($config["mysql"]["server"],$config["mysql"]["user"],$config["mysql"]["passwd"],$config["mysql"]["database"]);
        $dbinfo["connect"]=!$conn?"Disconnected":"Connected";
        if ($conn) {
            $db=new Database_Controller;
            $dbinfo["db_num"]=count($db->Query("show databases"));
            $dbinfo["table_num"]=count($db->Query("show tables"));
            $dbinfo["exist_contest"]="Not Found";
            $dbinfo["exist_contest_ranking"]="Not Found";
            $dbinfo["exist_contest_signup"]="Not Found";
            $dbinfo["exist_crontab"]="Not Found";
            $dbinfo["exist_judger"]="Not Found";
            $dbinfo["exist_logindata"]="Not Found";
            $dbinfo["exist_problem"]="Not Found";
            $dbinfo["exist_status"]="Not Found";
            $dbinfo["exist_tags"]="Not Found";
            $dbinfo["exist_user"]="Not Found";
            $table=$db->Query("show tables");
            for ($i=0;$i<count($table);$i++) $dbinfo["exist_".$table[$i]["Tables_in_".$config["mysql"]["database"]]]="Found";
        }
        return $dbinfo;
    }

    /**
     * 获取评测机信息 GetJudgeInfo
     * @return array|null
     */
    function GetJudgeInfo():array|null {
        $db=new Database_Controller;
        $array=$db->Query("SELECT * FROM judger");
        $online=0; for ($i=0;$i<count($array);$i++) {
            if (time()-$array[$i]["heartbeat"]>10) $array[$i]["online"]=false;
            else{$array[$i]["online"]=true;$online++;}
            unset($array[$i]["config"]);
        }
        $res=array("num"=>count($array),"online"=>$online,"data"=>$array);
        return $res;
    }

    /**
     * 获取PHP信息 GetPHPInfo
     * @return array|null
     */
    function GetPHPInfo():array|null {
        $phpinfo=array();
        $phpinfo["version"]=phpversion();
        $phpinfo["zend_version"]=zend_version();
        $info=shell_exec("top -bn 1 -w 100 | grep php-fpm");
        $info=explode("\n",$info);
        for ($i=0;$i<count($info);$i++) {
            $tmp=explode(" ",trim($info[$i]));
            $tmp1=shell_exec($tmp[count($tmp)-1]." -i | grep configure | grep -v grep");
            $tmp1=explode("\n",$tmp1);
            $tmp2=shell_exec($tmp[count($tmp)-1]." -i | grep 'Build System' | grep -v grep");
            $tmp2=explode("\n",$tmp2);
            $tmp3=shell_exec($tmp[count($tmp)-1]." -i | grep 'Build Date' | grep -v grep");
            $tmp3=explode("\n",$tmp3);
            $phpinfo["configure"]=trim(explode("=>",$tmp1[0])[1]);
            $phpinfo["build"]=trim(explode("=>",$tmp2[0])[1]);
            $phpinfo["build_time"]=trim(explode("=>",$tmp3[0])[1]);
            break;
        } $phpinfo["memory"]=memory_get_usage();
        $phpinfo["peak_memory"]=memory_get_peak_usage();
        return $phpinfo;
    }
    
    /**
     * 获取定时任务信息 GetCrontabInfo
     * @return array|null
     */
    function GetCrontabInfo():array|null {
        $db=new Database_Controller;
        $arr=$db->Query("SELECT * FROM crontab ORDER BY id");
        for ($i=0;$i<count($arr);$i++) $arr[$i]["nexttime"]=date("Y-m-d H:i:s",
        intval($arr[$i]["lasttime"])+intval($arr[$i]["duration"]));
        return $arr;
    }

    /**
     * 运行定时任务 RunCrontab
     * @param int $id 任务id
     * @return void
     */
    function RunCrontab(int $id):void {
        $db=new Database_Controller;
        $db->Execute("UPDATE crontab SET lasttime='0' WHERE id=$id");
    }

    /**
     * 获取系统信息 GetSystemInfo
     * @return array|null
     */
    function GetSystemInfo():array|null {
        $sysinfo["timeGlobal"]=gmdate("Y-m-d H:i:s");
		$sysinfo["timeServer"]=date("Y-m-d H:i:s");
		$sysinfo["timeStamp"]=time();
		$sysinfo["timeZone"]=date_default_timezone_get();
        if (!($str=@file("/etc/issue"))) return null;
		$str_array=explode(" ",$str[0]);
		$sysinfo["sysOperSys"]=$str_array[0]." ".$str_array[2]." ".$str_array[1];
		$sysinfo["sysProcArch"]=strtolower(php_uname("m"));
        return $sysinfo;
    }
}
?>