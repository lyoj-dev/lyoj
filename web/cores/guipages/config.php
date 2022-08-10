<?php
global $passwd;
$passwd = "123456";
?>
<?php
function compareVersion($version1,$version2):int {
    if ($version1==$version2) return 2;
    $version1_arr=explode('.',$version1);
    $version2_arr=explode('.',$version2);
    for ($i=0;$i<count($version1_arr);$i++) {
        if (!isset($version2_arr[$i])) continue;
        if($version1_arr[$i]==$version2_arr[$i]) continue;
        if($version1_arr[$i]<$version2_arr[$i]) return 3;
        if($version1_arr[$i]>$version2_arr[$i]) return 1;
    } if(count($version1_arr)!=count($version2_arr)){
        if(count($version1_arr)>count($version2_arr)) return 1;
        return 3;
    }
}
$error_info="";
function read_file($path):string {
    if (!file_exists($path)) return "";
    if (filesize($path) == 0) return "";
    $fp = fopen($path, "r");
    $cont = fread($fp, filesize($path));
    return $cont;
}
function getConfigTemp() {
    $json = read_file("./api/config.json");
    return json_decode($json, true);
}
function configCheck2($step):bool {
    global $error_info; $config = getConfigTemp();
    switch($step) {
        case 1: {
            $ok=compareVersion(phpversion(),"8.0.0")<=2&&class_exists("mysqli")&&class_exists("ZipArchive")
                &&function_exists("json_encode")&&exec("ps -ef | grep mysqld | grep -v grep")!="";
            return $ok;
        } break;
        case 2: {
            mysqli_report(MYSQLI_REPORT_OFF);
            $conn=mysqli_connect($config["mysql"]["server"],$config["mysql"]["user"],
                $config["mysql"]["passwd"],$config["mysql"]["database"],intval($config["mysql"]["port"]));
            if (is_bool($conn))
                $error_info="Couldn't connect to database according to the configuration you provide!".
                "<br/>Error info: ".mysqli_connect_error();
            return !is_bool($conn);
        } break;
        case 5: {
            if ($config["web"]["name"]==""){$error_info="Website name couldn't be empty";return false;}
            if ($config["web"]["title"]==""){$error_info="Website title suffix couldn't be empty";return false;}
            if ($config["web"]["protocol"]!="http"&&$config["web"]["protocol"]!="https") 
                {$error_info="Invalid website protocol";return false;}
            if ($config["web"]["domain"]==""){$error_info="Website domain couldn't be empty";return false;}
            if (!file_exists($config["web"]["logo"])){$error_info="Couldn't find file '".$config["web"]["logo"]."'";return false;}
            if (!file_exists($config["web"]["rsa_private_key"])){$error_info="Couldn't find file '".$config["web"]["rsa_private_key"]."'";return false;}
            if (!file_exists($config["web"]["rsa_public_key"])){$error_info="Couldn't find file '".$config["web"]["rsa_public_key"]."'";return false;}
            if (!is_numeric($config["web"]["cookie_time"])){$error_info="Cookie time must be a valid integer";return false;}
            if (!date_default_timezone_set($config["web"]["timezone"])){$error_info="Invalid timezone '".$config["web"]["timezone"].
                "', reference <a href='https://www.php.net/manual/en/timezones.php'>https://www.php.net/manual/en/timezones.php</a>";return false;}
            $fp=fopen($config["web"]["rsa_private_key"],"r");
            if (!$fp){$error_info="Couldn't open file '".$config["web"]["rsa_private_key"]."'";return false;}
            $private=filesize($config["web"]["rsa_private_key"])==0?"":fread($fp,filesize($config["web"]["rsa_private_key"]));
            if (!$private){$error_info="Invalid RSA private key";return false;} fclose($fp);
            $fp=fopen($config["web"]["rsa_public_key"],"r");
            if (!$fp){$error_info="Couldn't open file '".$config["web"]["rsa_public_key"]."'";return false;}
            $public=filesize($config["web"]["rsa_public_key"])==0?"":fread($fp,filesize($config["web"]["rsa_public_key"]));
            if (!$public){$error_info="Invalid RSA public key";return false;} fclose($fp);
            $key=bin2hex(random_bytes(5));
            $ret=openssl_public_encrypt($key,$crypted,$public);
            if (!$ret){$error_info="Invalid RSA public key";return false;}
            $ret=openssl_private_decrypt($crypted,$decrypted,$private);
            if (!$ret){$error_info="Invalid RSA private key";return false;}
            if ($decrypted!=$key){$error_info="Unmatched public key and private key";return false;}
            return true;
        } break;
        case 3: {
            $json = $config["web"]["footer"];
            $arr=JSON_decode($json,true);
            if ($arr==null) {$error_info="Invalid JSON data, ".json_last_error_msg();return false;}
            return true;
        } break;
        case 4: {
            $arr = $config["lang"];
            if (count($arr) == 0) {$error_info = "Empty language config. Please add at lease one language!"; return false;} 
            for ($i = 0; $i < count($arr); $i++) {
                $obj = $arr[$i];
                if ($obj["name"] == "") {$error_info = "Language #".($i + 1)." name is empty!"; return false;}
                if ($obj["source_path"] == "") {$error_info = "Language #".($i + 1)." source is empty!"; return false;}
                if ($obj["type"] != 1 && $obj["command"] == "") {$error_info = "Language #".($i + 1)." compile command is empty!"; return false;}
                if ($obj["exec_command"] == "") {$error_info = "Language #".($i + 1)." execute command is empty!"; return false;}
            } return true;
        } break;
        case 6: {
            return true;
        }
        default: return false;
    } 
}
if ($_GET["path"] == "config") {
    function run(array $param,string &$html,string &$body):void {
        if (file_exists("./api/config.json") == "") {
            $fp = fopen("/etc/judge/config.json", "r");
            $json = fread($fp, filesize("/etc/judge/config.json"));
            fclose($fp); $config = json_decode($json, true);
            $footer = json_encode($config["web"]["footer"], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $config["web"]["footer"] = $footer;
            $json = json_encode($config, JSON_UNESCAPED_UNICODE);
            $fp = fopen("./api/config.json", "w");
            fwrite($fp, $json);
            fclose($fp);
        } global $passwd; $config = getConfigTemp();
        if (!array_key_exists("passwd",$param) || $param["passwd"] != $passwd) {
            echo InsertTags("script", null, "alert('Invalid Password!'); window.history.go(-1);");
            exit;
        } $step=1; if (array_key_exists("step",$param)) $step=$param["step"];
        if (!is_numeric($step)) $step=1;
        // $script="var menu=document.getElementsByClassName('menu-item');";
        // $script.="for (i=0;i<menu.length;i++)menu[i].style.display='none';";
        $script="setTimeout(function(){";
        $script.="if (window.innerWidth>=800) document.getElementsByTagName('main')[0].style.width='800px';";
        $script.="else document.getElementsByTagName('main')[0].style.width='100%';";
        $script.="document.getElementsByClassName('footer')[0].style.display='none';";
        $script.="document.getElementsByClassName('copyright')[0].style.paddingTop='15px';";
        $script.="document.getElementsByClassName('copyright')[0].style.position='fixed';";
        $script.="document.getElementsByClassName('copyright')[0].style.bottom='0px';";
        $script.="document.getElementsByTagName('main')[0].style.minHeight='0px';";
        $script.="document.getElementsByTagName('main')[0].style.marginBottom='80px';";
        $script.="},100);";
        $script.="function strip_tags_pre(msg){msg=msg.replace(/<(\/)?pre[^>]*>/g,'');return msg;}";
        $script.="function saveKey(key, value) {";
        $script.="var json = SendAjax('".GetAPIUrl("/config")."', 'POST', {passwd:'$passwd', key:key, value:value});";
        $script.="var arr = JSON.parse(strip_tags_pre(json)); if (arr['code'] != 0) {layer.msg(arr['message']); return false;}}";
        $style=InsertCssStyle(array("input"),array(
            "border-left"=>"0px","border-right"=>"0px","border-top"=>"0px",
            "border-radius"=>"0px","background-color"=>"rgba(0,0,0,0)",
            "font-family"=>"'Exo 2'"
        )); $style.=InsertCssStyle(array("select"),array(
            "border-left"=>"0px","border-right"=>"0px","border-top"=>"0px",
            "border-radius"=>"0px","background-color"=>"rgba(0,0,0,0)",
            "font-family"=>"'Exo 2'","outline"=>"none","flex-grow"=>"2000",
            "padding-left"=>"15px","padding-right"=>"15px","height"=>"30px",
            "color"=>"rgb(27,116,221)","border-color"=>"rgb(213,216,218)",
            "transition"=>"border-color 0.5s",
        )); $style.=InsertCssStyle(array("select:hover","select:focus"),array(
            "border-color"=>"rgb(27,116,221)"
        )); $style.=InsertCssStyle(array("textarea"),array(
            "border-radius"=>"1px","background-color"=>"rgba(0,0,0,0)",
            "font-family"=>"Consolas","font-size"=>"20px","resize"=>"none",
            "outline"=>"none","width"=>"100%","height"=>"600px","tab-size"=>4
        )); $style.=InsertCssStyle(array(".check"),array("color"=>"rgb(36,140,36)"));
        $style.=InsertCssStyle(array(".x"),array("color"=>"rgb(255,0,0)"));
        for ($i=1;$i<$step;$i++) if (!configCheck2($i)) {$step=$i;break;}
        global $error_info;
        if ($error_info!="") {
            $style.=InsertCssStyle(array(".error"),array(
                "background-color"=>"rgb(255, 246, 246)",
                "border"=>"1px solid",
                "border-radius"=>"5px",
                "width"=>"calc(100% - 62px)",
                "border-color"=>"rgb(159, 58, 56)",
                "padding-left"=>"30px",
                "padding-right"=>"30px",
                "padding-top"=>"15px",
                "padding-bottom"=>"15px",
                "margin-bottom"=>"20px"
            ));
            $body.=InsertTags("div",array("class"=>"error"),$error_info);
        } $body.=InsertTags("hp",array("style"=>InsertInlineCssStyle(array("font-size"=>"35px"))),"Program Configure");
        switch($step) {
            case 1: {
                $style.=InsertCssStyle(array("table"),array("width"=>"100%","margin-top"=>"30px"));
                $style.=InsertCssStyle(array("tr td"),array("padding-top"=>"12px","padding-bottom"=>"12px","padding-left"=>"24px"));
                $style.=InsertCssStyle(array("tbody tr td"),array("border"=>"1px solid","border-bottom"=>"0px","border-left"=>"0px","border-right"=>"0px"));
                $style.=InsertCssStyle(array("thead tr td"),array("border"=>"0px solid","font-weight"=>"600"));
                $body.=InsertTags("hp",array("style"=>InsertInlineCssStyle(array("font-weight"=>"200"))),"&nbsp;&nbsp;Environment Check");
                $h="<table border='1'><thead><tr><td>#</td><td>Environment Requirement</td>";
                $h.="<td>Current Environment</td><td>Check Result</td></tr></thead><tbody>";
                $h.="<tr><td>1</td><td>PHP > 8.0.0</td><td>".phpversion()."</td>";
                $h.="<td><i class='".(compareVersion(phpversion(),"8.0.0")<=2?"check":"x")." icon'></i></td></tr>";
                $h.="<tr><td>2</td><td>PHP MySQLi</td><td>".(class_exists("mysqli")?"TRUE":"FALSE")."</td>";
                $h.="<td><i class='".(class_exists("mysqli")?"check":"x")." icon'></i></td></tr>";
                $h.="<tr><td>3</td><td>PHP ZipArchive</td><td>".(class_exists("ZipArchive")?"TRUE":"FALSE")."</td>";
                $h.="<td><i class='".(class_exists("ZipArchive")?"check":"x")." icon'></i></td></tr>";
                $h.="<tr><td>4</td><td>PHP libjson</td><td>".(function_exists("json_encode")?"TRUE":"FALSE")."</td>";
                $h.="<td><i class='".(function_exists("json_encode")?"check":"x")." icon'></i></td></tr>";
                $h.="<tr><td>5</td><td>MySQL Service</td><td>".(exec("ps -ef | grep mysqld | grep -v grep")!=""?"TRUE":"FALSE")."</td>";
                $h.="<td><i class='".(exec("ps -ef | grep mysqld | grep -v grep")!=""?"check":"x")." icon'></i></td></tr>";
                $h.="<tr><td>-</td><td>Total Check</td><td>".(configCheck2(1)?"OK":"Failure")."</td>";
                $h.="<td><i class='".(configCheck2(1)?"check":"x")." icon'></i></td></tr>";
                $h.="</tbody></table>"; $body.=$h;
                if (configCheck2(1)) {
                    $body.=InsertTags("center",array("style"=>"margin-top:10px"),
                        InsertTags("button",array("onclick"=>"location.href='".GetUrl("config",array("step"=>$step+1, "passwd"=>$passwd))."'"),"Next"));
                }
            } break;
            case 2: {
                $body.=InsertTags("hp",array("style"=>InsertInlineCssStyle(array("font-weight"=>"200"))),"&nbsp;&nbsp;Database Configure");
                $body.=InsertTags("div",array("class"=>"flex","style"=>"margin-top:30px"),
                InsertTags("i",array("class"=>"server icon"),null).
                "&nbsp;&nbsp;&nbsp;&nbsp;".InsertSingleTag("input",array(
                    "id"=>"server-input",
                    "placeholder"=>"MySQL / MariaDB Server IP Address (default: 127.0.0.1)",
                    "value"=>$config["mysql"]["server"]
                ))."&nbsp;&nbsp;&nbsp;&nbsp;".
                InsertTags("i",array("class"=>($config["mysql"]["server"]==""?"x":"check")." icon"),"")); 
                $body.=InsertTags("div",array("class"=>"flex","style"=>"margin-top:20px"),
                InsertTags("i",array("class"=>"circle icon"),null).
                "&nbsp;&nbsp;&nbsp;&nbsp;".InsertSingleTag("input",array(
                    "id"=>"port-input",
                    "placeholder"=>"MySQL / MariaDB Server Port (default: 3306)",
                    "value"=>$config["mysql"]["port"]
                ))."&nbsp;&nbsp;&nbsp;&nbsp;".
                InsertTags("i",array("class"=>(!is_numeric($config["mysql"]["port"])?"x":"check")." icon"),"")); 
                $body.=InsertTags("div",array("class"=>"flex","style"=>"margin-top:20px"),
                InsertTags("i",array("class"=>"user icon"),null).
                "&nbsp;&nbsp;&nbsp;&nbsp;".InsertSingleTag("input",array(
                    "id"=>"user-input",
                    "placeholder"=>"MySQL / MariaDB Admin User",
                    "value"=>$config["mysql"]["user"]
                ))."&nbsp;&nbsp;&nbsp;&nbsp;".
                InsertTags("i",array("class"=>($config["mysql"]["user"]==""?"x":"check")." icon"),"")); 
                $body.=InsertTags("div",array("class"=>"flex","style"=>"margin-top:20px"),
                InsertTags("i",array("class"=>"lock icon"),null).
                "&nbsp;&nbsp;&nbsp;&nbsp;".InsertSingleTag("input",array(
                    "id"=>"password-input",
                    "type"=>"password",
                    "placeholder"=>"MySQL / MariaDB Admin Password",
                    "value"=>$config["mysql"]["passwd"]
                ))."&nbsp;&nbsp;&nbsp;&nbsp;".
                InsertTags("i",array("class"=>($config["mysql"]["passwd"]==""?"x":"check")." icon"),"")); 
                $body.=InsertTags("div",array("class"=>"flex","style"=>"margin-top:20px"),
                InsertTags("i",array("class"=>"database icon"),null).
                "&nbsp;&nbsp;&nbsp;&nbsp;".InsertSingleTag("input",array(
                    "id"=>"database-input",
                    "placeholder"=>"MySQL / MariaDB Database",
                    "value"=>$config["mysql"]["database"]
                ))."&nbsp;&nbsp;&nbsp;&nbsp;".
                InsertTags("i",array("class"=>($config["mysql"]["database"]==""?"x":"check")." icon"),""));
                $body.=InsertTags("div",array("style"=>"margin-top:10px"),InsertTags("center",null,
                    InsertTags("button",array("onclick"=>"previous()"),"Previous").
                    InsertTags("button",array("onclick"=>"submit()"),"Next")));
                $script.="function submit() {";
                $script.="saveKey('//mysql//server',document.getElementById('server-input').value);";
                $script.="saveKey('//mysql//port',document.getElementById('port-input').value);";
                $script.="saveKey('//mysql//user',document.getElementById('user-input').value);";
                $script.="saveKey('//mysql//passwd',document.getElementById('password-input').value);";
                $script.="saveKey('//mysql//database',document.getElementById('database-input').value);";
                $script.="location.href='".GetUrl("config",array("step"=>$step+1, "passwd"=>$passwd))."';";
                $script.="}";
                $script.="function previous() {";
                $script.="saveKey('//mysql//server',document.getElementById('server-input').value);";
                $script.="saveKey('//mysql//port',document.getElementById('port-input').value);";
                $script.="saveKey('//mysql//user',document.getElementById('user-input').value);";
                $script.="saveKey('//mysql//passwd',document.getElementById('password-input').value);";
                $script.="saveKey('//mysql//database',document.getElementById('database-input').value);";
                $script.="location.href='".GetUrl("config",array("step"=>$step-1, "passwd"=>$passwd))."';";
                $script.="}";
            } break;
            case 5: {
                $body.=InsertTags("hp",array("style"=>InsertInlineCssStyle(array("font-weight"=>"200"))),"&nbsp;&nbsp;Website Configure");
                $body.=InsertTags("div",array("class"=>"flex","style"=>"margin-top:30px"),
                InsertTags("i",array("class"=>"print icon"),null).
                "&nbsp;&nbsp;&nbsp;&nbsp;".InsertSingleTag("input",array(
                    "id"=>"name-input",
                    "placeholder"=>"Online Judge Name (eg: LYOJ)",
                    "value"=>$config["web"]["name"]
                ))."&nbsp;&nbsp;&nbsp;&nbsp;".
                InsertTags("i",array("class"=>($config["web"]["name"]==""?"x":"check")." icon"),""));
                $body.=InsertTags("div",array("class"=>"flex","style"=>"margin-top:30px"),
                InsertTags("i",array("class"=>"info icon"),null).
                "&nbsp;&nbsp;&nbsp;&nbsp;".InsertSingleTag("input",array(
                    "id"=>"title-input",
                    "placeholder"=>"Website Title Suffix",
                    "value"=>$config["web"]["title"]
                ))."&nbsp;&nbsp;&nbsp;&nbsp;".
                InsertTags("i",array("class"=>($config["web"]["title"]==""?"x":"check")." icon"),"")); 
                $body.=InsertTags("div",array("class"=>"flex","style"=>"margin-top:30px"),
                InsertTags("i",array("class"=>"cog icon"),null).
                "&nbsp;&nbsp;&nbsp;&nbsp;".InsertSingleTag("input",array(
                    "id"=>"protocol-input",
                    "placeholder"=>"Website Protocol (http/https)",
                    "value"=>$config["web"]["protocol"]
                ))."&nbsp;&nbsp;&nbsp;&nbsp;".
                InsertTags("i",array("class"=>($config["web"]["protocol"]!="http"&&$config["web"]["protocol"]!="https"?"x":"check")." icon"),"")); 
                $body.=InsertTags("div",array("class"=>"flex","style"=>"margin-top:30px"),
                InsertTags("i",array("class"=>"globe icon"),null).
                "&nbsp;&nbsp;&nbsp;&nbsp;".InsertSingleTag("input",array(
                    "id"=>"domain-input",
                    "placeholder"=>"Website Domain",
                    "value"=>$config["web"]["domain"]
                ))."&nbsp;&nbsp;&nbsp;&nbsp;".
                InsertTags("i",array("class"=>($config["web"]["domain"]==""?"x":"check")." icon"),"")); 
                $body.=InsertTags("div",array("class"=>"flex","style"=>"margin-top:30px"),
                InsertTags("i",array("class"=>"image icon"),null).
                "&nbsp;&nbsp;&nbsp;&nbsp;".InsertSingleTag("input",array(
                    "id"=>"logo-input",
                    "placeholder"=>"Website Logo Path",
                    "value"=>$config["web"]["logo"]
                ))."&nbsp;&nbsp;&nbsp;&nbsp;".
                InsertTags("i",array("class"=>(!file_exists($config["web"]["logo"])?"x":"check")." icon"),"")); 
                $body.=InsertTags("div",array("class"=>"flex","style"=>"margin-top:30px"),
                InsertTags("i",array("class"=>"lock icon"),null).
                "&nbsp;&nbsp;&nbsp;&nbsp;".InsertSingleTag("input",array(
                    "id"=>"rsa_private_key-input",
                    "placeholder"=>"RSA Private Key Path",
                    "value"=>$config["web"]["rsa_private_key"]
                ))."&nbsp;&nbsp;&nbsp;&nbsp;".
                InsertTags("i",array("class"=>(!file_exists($config["web"]["rsa_private_key"])?"x":"check")." icon"),"")); 
                $body.=InsertTags("div",array("class"=>"flex","style"=>"margin-top:30px"),
                InsertTags("i",array("class"=>"key icon"),null).
                "&nbsp;&nbsp;&nbsp;&nbsp;".InsertSingleTag("input",array(
                    "id"=>"rsa_public_key-input",
                    "placeholder"=>"RSA Public Key Path",
                    "value"=>$config["web"]["rsa_public_key"]
                ))."&nbsp;&nbsp;&nbsp;&nbsp;".
                InsertTags("i",array("class"=>(!file_exists($config["web"]["rsa_public_key"])?"x":"check")." icon"),"")); 
                $body.=InsertTags("div",array("class"=>"flex","style"=>"margin-top:30px"),
                InsertTags("i",array("class"=>"cloud icon"),null).
                "&nbsp;&nbsp;&nbsp;&nbsp;".InsertSingleTag("input",array(
                    "id"=>"cookie_time-input",
                    "placeholder"=>"Cookie Saving Time (In Days)",
                    "value"=>$config["web"]["cookie_time"]/24/60/60
                ))."&nbsp;&nbsp;&nbsp;&nbsp;".
                InsertTags("i",array("class"=>(!is_numeric($config["web"]["cookie_time"])?"x":"check")." icon"),"")); 
                $body.=InsertTags("div",array("class"=>"flex","style"=>"margin-top:30px"),
                InsertTags("i",array("class"=>"time icon"),null).
                "&nbsp;&nbsp;&nbsp;&nbsp;".InsertSingleTag("input",array(
                    "id"=>"timezone-input",
                    "placeholder"=>"Server Time Zone",
                    "value"=>$config["web"]["timezone"]
                ))."&nbsp;&nbsp;&nbsp;&nbsp;".
                InsertTags("i",array("class"=>(!date_default_timezone_set($config["web"]["timezone"])?"x":"check")." icon"),"")); 
                
                $option = ""; for ($i = 0; $i < count($config["lang"]); $i++) 
                    if ($i != $config["default_lang"]) $option.=InsertTags("option", array("value" => $i), "$i - ".$config["lang"][$i]["name"]);
                    else $option.=InsertTags("option", array("value" => $i, "selected" => "selected"), "$i - ".$config["lang"][$i]["name"]);
                $body.=InsertTags("div",array("class"=>"flex","style"=>"margin-top:30px"),
                InsertTags("i",array("class"=>"code icon"),null).
                "&nbsp;&nbsp;&nbsp;&nbsp;".InsertTags("select",array(
                    "id"=>"lang-input", "value" => $config["default_lang"]
                ), $option));

                $body.=InsertTags("div",array("style"=>"margin-top:10px"),InsertTags("center",null,
                    InsertTags("button",array("onclick"=>"previous()"),"Previous").
                    InsertTags("button",array("onclick"=>"submit()"),"Next")));
                $script.="function submit() {";
                $script.="saveKey('//web//name',document.getElementById('name-input').value);";
                $script.="saveKey('//web//title',document.getElementById('title-input').value);";
                $script.="saveKey('//web//protocol',document.getElementById('protocol-input').value);";
                $script.="saveKey('//web//domain',document.getElementById('domain-input').value);";
                $script.="saveKey('//web//logo',document.getElementById('logo-input').value);";
                $script.="saveKey('//web//icon',document.getElementById('logo-input').value);";
                $script.="saveKey('//web//rsa_private_key',document.getElementById('rsa_private_key-input').value);";
                $script.="saveKey('//web//rsa_public_key',document.getElementById('rsa_public_key-input').value);";
                $script.="saveKey('//web//cookie_time',document.getElementById('cookie_time-input').value*24*60*60);";
                $script.="saveKey('//web//timezone',document.getElementById('timezone-input').value);";
                $script.="saveKey('//default_lang',document.getElementById('lang-input').value);";
                $script.="location.href='".GetUrl("config",array("step"=>$step+1, "passwd"=>$passwd))."';}";
                $script.="function previous() {";
                $script.="saveKey('//web//name',document.getElementById('name-input').value);";
                $script.="saveKey('//web//title',document.getElementById('title-input').value);";
                $script.="saveKey('//web//protocol',document.getElementById('protocol-input').value);";
                $script.="saveKey('//web//domain',document.getElementById('domain-input').value);";
                $script.="saveKey('//web//logo',document.getElementById('logo-input').value);";
                $script.="saveKey('//web//icon',document.getElementById('logo-input').value);";
                $script.="saveKey('//web//rsa_private_key',document.getElementById('rsa_private_key-input').value);";
                $script.="saveKey('//web//rsa_public_key',document.getElementById('rsa_public_key-input').value);";
                $script.="saveKey('//web//cookie_time',document.getElementById('cookie_time-input').value*24*60*60);";
                $script.="saveKey('//web//timezone',document.getElementById('timezone-input').value);";
                $script.="saveKey('//default_lang',document.getElementById('lang-input').value);";
                $script.="location.href='".GetUrl("config",array("step"=>$step-1, "passwd"=>$passwd))."';}";
            } break;
            case 3: {
                $body.=InsertTags("hp",array("style"=>InsertInlineCssStyle(array("font-weight"=>"200"))),"&nbsp;&nbsp;Footer Configure");
                $content=$config["web"]["footer"];
                if ($content=="") $content="[\n    {\n        \"title\":\"My Website\",\n        \"url\":{".
                "\n            \"Yucai News Weekly\":\"https://www.littleyang.ml\",\n            \"LittleYang0531's Blog\":\"https://blog.littleyang.ml\",".
                "\n            \"Public Image Repository\":\"https://pic.littleyang.ml\",\n            \"Image Website for My Class\":\"https://photo.littleyang.ml\",".
                "\n            \"Gitea for LittleYang\":\"https://git.littleyang.ml\",\n            \"Tool Website\":\"https://tools.littleyang.ml\"".
                "\n        }\n    },\n    {\n        \"title\":\"My Repository\",\n        \"url\":{\n            \"News Website\":\"https://github.com/LittleYang0531/News-Website\",".
                "\n            \"LittleYang Onlinejudge\":\"https://github.com/LittleYang0531/lyoj\"\n        }\n    },\n    {".
                "\n        \"title\":\"About Me\",\n        \"url\":{\n            \"My Github\":\"https://github.com/LittleYang0531\",".
                "\n            \"My Email\":\"mailto:admin@littleyang.ml\",\n            \"Feedback Email\":\"mailto:feedback@littleyang.ml\"\n        }\n    }\n]";
                $ok=true; JSON_decode($content); if (json_last_error()!=0) $ok=false;
                $body.=InsertTags("div",array("class"=>"flex","style"=>"margin-top:30px;justify-content:center"),
                    InsertTags("textarea",array("id"=>"footer-input","style"=>"overflow:scroll","wrap"=>"off"),$content).
                    "&nbsp;&nbsp;&nbsp;&nbsp;".InsertTags("i",array("class"=>(!$ok?"x":"check")." icon"),""));
                $script.="text=document.getElementById('footer-input');";
                $script.="function getCursortPosition(ctrl){";
                $script.="var CaretPos=0;if(document.selection){ctrl.focus();";
                $script.="var Sel=document.selection.createRange();Sel.moveStart('character',-ctrl.value.length);";
                $script.="CaretPos=Sel.text.length;}else if (ctrl.selectionStart||ctrl.selectionStart=='0'){";
                $script.="CaretPos=ctrl.selectionStart;}return CaretPos;}";
                $script.="function setCaretPosition(ctrl,pos){if(ctrl.setSelectionRange){";
                $script.="ctrl.focus();ctrl.setSelectionRange(pos,pos);}";
                $script.="else if (ctrl.createTextRange){var range=ctrl.createTextRange();";
                $script.="range.collapse(true);range.moveEnd('character',pos);";
                $script.="range.moveStart('character',pos);range.select();}}";
                $script.="text.onkeydown=function(){if (event.key==='Tab'){";
                $script.="event.preventDefault();let start=this.selectionStart;";
                $script.="let end=this.selectionEnd;if(start===end){";
                $script.="document.execCommand('insertText',false,'\\t');";
                $script.="}else{let strBefore=this.value.slice(0,start);";
                $script.="let curLineStart=strBefore.includes('\\n')?strBefore.lastIndexOf('\\n')+1:0;";
                $script.="let strBetween=this.value.slice(curLineStart,end+1);";
                $script.="let newStr='\\t'+strBetween.replace(/\\n/g,'\\n\\t');";
                $script.="let lineBreakCount=strBetween.split('\\n').length;";
                $script.="let newStart=start+1;let newEnd=end+(lineBreakCount+1)*1;";
                $script.="this.setSelectionRange(curLineStart,end);";
                $script.="document.execCommand('insertText',false,newStr);";
                $script.="this.setSelectionRange(newStart,newEnd);}";
                $script.="}if(event.key==='{'){document.execCommand('insertText',false,'{');";
                $script.="document.execCommand('insertText',false,'}');";
                $script.="var pos=getCursortPosition(text);";
                $script.="setCaretPosition(text,pos-1);return false;";
                $script.="}if(event.key==='}'){var pos=getCursortPosition(text);";
                $script.="var nxt=this.value.slice(pos,pos+1);";
                $script.="if (nxt=='}') {setCaretPosition(text,pos+1);";
                $script.="return false;}}if(event.key==='['){";
                $script.="document.execCommand('insertText',false,'[');";
                $script.="document.execCommand('insertText',false,']');";
                $script.="var pos=getCursortPosition(text);setCaretPosition(text,pos-1);";
                $script.="return false;}if(event.key===']'){";
                $script.="var pos=getCursortPosition(text);";
                $script.="var nxt=this.value.slice(pos,pos+1);";
                $script.="if(nxt==']'){setCaretPosition(text,pos+1);";
                $script.="return false;}}if(event.key==='\"'){";
                $script.="var pos=getCursortPosition(text);";
                $script.="var nxt=this.value.slice(pos,pos+1);";
                $script.="if(nxt=='\"'){setCaretPosition(text,pos+1);";
                $script.="return false;}document.execCommand('insertText',false,'\"');";
                $script.="document.execCommand('insertText',false,'\"');";
                $script.="pos=getCursortPosition(text);";
                $script.="setCaretPosition(text,pos-1);";
                $script.="return false;}if(event.key === '\\''){";
                $script.="var pos=getCursortPosition(text);";
                $script.="var nxt=this.value.slice(pos,pos+1);";
                $script.="if(nxt=='\\''){setCaretPosition(text,pos+1);";
                $script.="return false;}document.execCommand('insertText',false,'\\'');";
                $script.="document.execCommand('insertText',false,'\\'');";
                $script.="pos=getCursortPosition(text);";
                $script.="setCaretPosition(text,pos-1);";
                $script.="return false;}if(event.key==='Enter'){";
                $script.="var pos=getCursortPosition(text);";
                $script.="var pre=this.value.slice(pos-1,pos);";
                $script.="var nxt=this.value.slice(pos,pos+1);";
                $script.="if(pre=='{'&&nxt=='}'){document.execCommand('insertText',false,'\\n');";
                $script.="document.execCommand('insertText',false,'\\n');";
                $script.="setCaretPosition(text,pos+1);var str=this.value.slice(0,pos+1);";
                $script.="pre=str.split('{').length-1+str.split('[').length-1;";
                $script.="nxt=str.split('}').length-1+str.split(']').length-1;";
                $script.="for (i=1;i<=pre-nxt;i++) document.execCommand('insertText',false,'\\t');";
                $script.="pos=getCursortPosition(text); setCaretPosition(text,pos+1);";
                $script.="for (i=1;i<=pre-nxt-1;i++) document.execCommand('insertText',false,'\\t');";
                $script.="setCaretPosition(text,pos);return false;";
                $script.="}else if(pre=='['&&nxt==']'){";    
                $script.="document.execCommand('insertText',false,'\\n');";
                $script.="document.execCommand('insertText',false,'\\n');";
                $script.="setCaretPosition(text,pos+1);";
                $script.="var str=this.value.slice(0,pos+1);";
                $script.="pre=str.split('{').length-1+str.split('[').length-1;";
                $script.="nxt=str.split('}').length-1+str.split(']').length-1;";
                $script.="for(i=1;i<=pre-nxt;i++)document.execCommand('insertText',false,'\\t');";
                $script.="pos=getCursortPosition(text);setCaretPosition(text,pos+1);";
                $script.="for(i=1;i<=pre-nxt-1;i++)document.execCommand('insertText',false,'\\t');";
                $script.="setCaretPosition(text,pos);return false;}else{";
                $script.="document.execCommand('insertText',false,'\\n');";
                $script.="var str=this.value.slice(0,pos+1);";
                $script.="pre=str.split('{').length-1+str.split('[').length-1;";
                $script.="nxt=str.split('}').length-1+str.split(']').length-1;";
                $script.="for (i=1;i<=pre-nxt;i++) document.execCommand('insertText',false,'\\t');";
                $script.="return false;}}};";
                $body.=InsertTags("div",array("style"=>"margin-top:10px"),InsertTags("center",null,
                    InsertTags("button",array("onclick"=>"previous()"),"Previous").
                    InsertTags("button",array("onclick"=>"submit()"),"Next")));
                $script.="function strip_tags_pre(msg){msg=msg.replace(/<(\/)?pre[^>]*>/g,'');return msg;}";
                $script.="function setCookie(name,value,expire) {";
                $script.="var exp=new Date(); exp.setTime(exp.getTime()+expire*1000);";
                $script.="document.cookie=name+\"=\"+escape(value)+\";expires=\"+exp.toGMTString()+\";\";}";
                $script.="function strip_tags_pre(msg){msg=msg.replace(/<(\/)?pre[^>]*>/g,'');return msg;}";
                $script.="function submit() {";
                $script.="var json = SendAjax('".GetAPIUrl("/config")."', 'POST', {passwd:'$passwd', key:'//web//footer', value:document.getElementById('footer-input').value});";
                $script.="var arr = JSON.parse(strip_tags_pre(json)); if (arr['code'] != 0) {layer.msg(arr['message']); return false;}";
                $script.="location.href='".GetUrl("config",array("step"=>$step+1, "passwd"=>$passwd))."';}";
                $script.="function previous() {";
                $script.="var json = SendAjax('".GetAPIUrl("/config")."', 'POST', {passwd:'$passwd', key:'//web//footer', value:document.getElementById('footer-input').value});";
                $script.="var arr = JSON.parse(strip_tags_pre(json)); if (arr['code'] != 0) {layer.msg(arr['message']); return false;}";
                $script.="location.href='".GetUrl("config",array("step"=>$step-1, "passwd"=>$passwd))."';}";
            } break;
            case 4: {
                $body.=InsertTags("hp",array("style"=>InsertInlineCssStyle(array("font-weight"=>"200"))),"&nbsp;&nbsp;Language Configure");
                $body.=InsertTags("div",array("id"=>"language","style"=>InsertInlineCssStyle(array("margin-top"=>"30px"))),"");
                $tmp=InsertTags("p",array("style"=>InsertInlineCssStyle(array("width"=>"3%"))),InsertTags("i",array("class"=>"angle right icon green"),null));
                $tmp.=InsertTags("p",array(
                    "style"=>InsertInlineCssStyle(array("width"=>"40%","cursor"=>"pointer")),
                    "onclick"=>"add('',0,'','','','','')",
                    "class"=>"ellipsis"
                ),"Create a new language!");
                $body.=InsertTags("div",array(
                    "style"=>InsertInlineCssStyle(array("min-height"=>"40px","margin-top"=>"20px","padding-left"=>"20px")),
                    "class"=>"default_main3 flex",
                ),$tmp);
                $tmp=InsertTags("p",array("style"=>InsertInlineCssStyle(array("width"=>"3%"))),InsertTags("i",array("class"=>"angle right icon green"),null));
                $tmp.=InsertTags("p",array(
                    "style"=>InsertInlineCssStyle(array("width"=>"40%","cursor"=>"pointer")),
                    "onclick"=>"Import()",
                    "class"=>"ellipsis"
                ),"Import language config from JSON format!");
                $body.=InsertTags("div",array(
                    "style"=>InsertInlineCssStyle(array("min-height"=>"40px","padding-left"=>"20px")),
                    "class"=>"default_main3 flex",
                ),$tmp);
                $tmp=InsertTags("p",array("style"=>InsertInlineCssStyle(array("width"=>"3%"))),InsertTags("i",array("class"=>"angle right icon green"),null));
                $tmp.=InsertTags("p",array(
                    "style"=>InsertInlineCssStyle(array("width"=>"40%","cursor"=>"pointer")),
                    "onclick"=>"Export()",
                    "class"=>"ellipsis"
                ),"Export language config to JSON format!");
                $body.=InsertTags("div",array(
                    "style"=>InsertInlineCssStyle(array("min-height"=>"40px","padding-left"=>"20px")),
                    "class"=>"default_main3 flex",
                ),$tmp);
                $body.=InsertTags("div",array("style"=>"margin-top:10px"),InsertTags("center",null,
                    InsertTags("button",array("onclick"=>"previous()"),"Previous").
                    InsertTags("button",array("onclick"=>"submit()"),"Next")));
                $script.="function submit() {";
                $script.="var json = SendAjax('".GetAPIUrl("/config")."', 'POST', {passwd:'$passwd', key:'//lang', value:JSON.stringify(saveLanguage())});";
                $script.="var arr = JSON.parse(strip_tags_pre(json)); if (arr['code'] != 0) {layer.msg(arr['message']); return false;}";
                $script.="location.href='".GetUrl("config",array("step"=>$step+1, "passwd"=>$passwd))."';}";
                $script.="function previous() {";
                $script.="var json = SendAjax('".GetAPIUrl("/config")."', 'POST', {passwd:'$passwd', key:'//lang', value:JSON.stringify(saveLanguage())});";
                $script.="var arr = JSON.parse(strip_tags_pre(json)); if (arr['code'] != 0) {layer.msg(arr['message']); return false;}";
                $script.="location.href='".GetUrl("config",array("step"=>$step-1, "passwd"=>$passwd))."';}";
                $json = json_encode($config["lang"], JSON_UNESCAPED_UNICODE);
                $script.="var language_number=0, lst = 0;";
                $script.="var data = new Array; var deleted = new Array;";
                $script.="var json = '".str_replace("'", "\\'", $json)."';";
                $script.="load(json);";
                $script.="function load(json) {";
                $script.="var arr = JSON.parse(json);";
                $script.="for (i = 0; i < arr.length; i++){";
                $script.="if (arr[i]['command'] == undefined) arr[i]['command'] = '';";
                $script.="add(arr[i]['name'], arr[i]['type'], arr[i]['mode'], arr[i]['highlight-mode'], arr[i]['source_path'], arr[i]['command'], arr[i]['exec_command']);";
                $script.="} if (lst == 0) add('', 0, '', '', '', '', '', '');";
                $script.="}";
                $script.="function deleteLanguage(id){";
                $script.="deleted[id]=1; lst--;";
                $script.="document.getElementById(\"language-\" + id).style.display = \"none\";";
                $script.="if (lst == 0) add('', 0, '', '', '', '', '');}";
                $script.="function saveLanguage(){";
                $script.="var res = new Array;";
                $script.="for (i = 1; i <= language_number; i++) {";
                $script.="var x = new Object; if (deleted[i] == 1) continue;";
                $script.="x['type'] = Number(document.getElementById(\"type-\" + i).value);";
                $script.="x['name'] = document.getElementById(\"name-\" + i).value;";
                $script.="x['mode'] = document.getElementById(\"mode-\" + i).value;";
                $script.="x['highlight-mode'] = document.getElementById(\"highlight-mode-\" + i).value;";
                $script.="x['source_path'] = document.getElementById(\"source-path-\" + i).value;";
                $script.="x['command'] = document.getElementById(\"compile-command-\" + i).value;";
                $script.="x['exec_command'] = document.getElementById(\"execute-command-\" + i).value;";
                $script.="if (x['type'] == 0 && x['name'] == '' && x['mode'] == '' && x['highlight-mode'] == ''";
                $script.=" && x['source_path'] == '' && x['command'] == '' && x['exec_command'] == '') continue;";
                $script.="res.push(x);} return res;}";
                $script.="function add(name,type,mode,hlmode,source_path,command,exec_command){";
                $script.="language_number++; var fa = document.getElementById('language'); lst++;";
                $script.="var son = document.createElement('div'); son.id = \"language-\" + language_number;";
                $script.="son.innerHTML = \"<div class='default_main3' style='padding:20px;margin-top:20px;'>\"+";
                $script.="\"<hp style='font-size:18px;font-weight:200'>Language #\"+language_number+\"&nbsp;&nbsp;&nbsp;&nbsp;<a href='javascript:void(0);' onclick='deleteLanguage(\"+language_number+\")'>Delete</a></hp>";
                $script.="<div class='flex' style='margin-top:15px;font-size:14px'>Compile Type&nbsp&nbsp<select id='type-\"+language_number+\"'>";
                $script.="<option value=0 id='type-\"+language_number+\"-0' \"+(type == 0 ? \"selected\" : \"\")+\">Source need to be compiled and compiler will output a executable file (eg: C++/C/Pascal)</option>";
                $script.="<option value=2 id='type-\"+language_number+\"-2' \"+(type == 2 ? \"selected\" : \"\")+\">Source need to be compiled and compiler do not output a executable file (eg: Java)</option\>";
                $script.="<option value=1 id='type-\"+language_number+\"-1' \"+(type == 1 ? \"selected\" : \"\")+\">Source need to be interpretived by the compiler (eg: Python/PHP)</option></select></div>";
                $script.="<div class='flex' style='margin-top:10px;font-size:14px'>Language Name&nbsp&nbsp";
                $script.="<input id='name-\"+language_number+\"' placeholder='Input this language name here' value='\"+name+\"'/></div>";
                $script.="<div class='flex' style='margin-top:10px;font-size:14px'>Source Name&nbsp&nbsp";
                $script.="<input id='source-path-\"+language_number+\"' placeholder='Input source name here' value='\"+source_path+\"'/></div>";
                $script.="<div class='flex' style='margin-top:10px;font-size:14px'>Editor Language Type&nbsp&nbsp";
                $script.="<input id='mode-\"+language_number+\"' placeholder='Reference: Monaco Editor on Github' value='\"+mode+\"'/></div>";
                $script.="<div class='flex' style='margin-top:10px;font-size:14px'>Highlight Language Type&nbsp&nbsp";
                $script.="<input id='highlight-mode-\"+language_number+\"' placeholder='Reference: highlight.js on Github' value='\"+hlmode+\"'/></div>";
                $script.="<div class='flex' style='margin-top:10px;font-size:14px'>Compile Command:&nbsp&nbsp";
                $script.="<input id='compile-command-\"+language_number+\"' placeholder='Input compile command here(except Interpretive Language)' value='\"+command+\"'/></div>";
                $script.="<div class='flex' style='margin-top:10px;font-size:14px'>Execute Command:&nbsp&nbsp";
                $script.="<input id='execute-command-\"+language_number+\"' placeholder='Input execute command here' value='\"+exec_command+\"'/></div>";
                $script.="</div>\"; fa.appendChild(son);";
                $script.="}";
                $script.="function Import() {";
                $script.="layer.prompt({title:'Paste JSON code here', formType: 2, maxlength: 100000000000}, function(text, index){";
                $script.="layer.close(index);";
                $script.="load(text); layer.msg('Import success!');";
                $script.="});";
                $script.="}";
                $script.="function Export() {";
                $script.="var json = JSON.stringify(saveLanguage());";
                $script.="var e = document.createElement('input');";
                $script.="document.body.appendChild(e);";
                $script.="e.setAttribute('value', json); e.select();";
                $script.="if (document.execCommand('copy')) layer.msg('Success!');";
                $script.="document.body.removeChild(e);";
                $script.="}";
            } break;
            case 6: {
                $body.=InsertTags("hp",array("style"=>InsertInlineCssStyle(array("font-weight"=>"200"))),"&nbsp;&nbsp;Email Configure");
                $body.=InsertTags("div",array("class"=>"flex","style"=>"margin-top:30px"),
                InsertTags("i",array("class"=>"server icon"),null).
                "&nbsp;&nbsp;&nbsp;&nbsp;".InsertSingleTag("input",array(
                    "id"=>"server-input",
                    "placeholder"=>"Email Server IP Address",
                    "value"=>$config["email"]["email_host"]
                ))."&nbsp;&nbsp;&nbsp;&nbsp;".
                InsertTags("i",array("class"=>($config["email"]["email_host"]==""?"x":"check")." icon"),"")); 
                $body.=InsertTags("div",array("class"=>"flex","style"=>"margin-top:20px"),
                InsertTags("i",array("class"=>"circle icon"),null).
                "&nbsp;&nbsp;&nbsp;&nbsp;".InsertSingleTag("input",array(
                    "id"=>"port-input",
                    "placeholder"=>"Email Server Port",
                    "value"=>$config["email"]["email_port"]
                ))."&nbsp;&nbsp;&nbsp;&nbsp;".
                InsertTags("i",array("class"=>(!is_numeric($config["email"]["email_port"])?"x":"check")." icon"),"")); 
                $body.=InsertTags("div",array("class"=>"flex","style"=>"margin-top:20px"),
                InsertTags("i",array("class"=>"cog icon"),null).
                "&nbsp;&nbsp;&nbsp;&nbsp;".InsertSingleTag("input",array(
                    "id"=>"protocol-input",
                    "placeholder"=>"Email Server Protocol",
                    "value"=>$config["email"]["email_protocol"]
                ))."&nbsp;&nbsp;&nbsp;&nbsp;".
                InsertTags("i",array("class"=>($config["email"]["email_protocol"] == ""?"x":"check")." icon"),"")); 
                $body.=InsertTags("div",array("class"=>"flex","style"=>"margin-top:20px"),
                InsertTags("i",array("class"=>"user icon"),null).
                "&nbsp;&nbsp;&nbsp;&nbsp;".InsertSingleTag("input",array(
                    "id"=>"user-input",
                    "placeholder"=>"Email User",
                    "value"=>$config["email"]["email_name"]
                ))."&nbsp;&nbsp;&nbsp;&nbsp;".
                InsertTags("i",array("class"=>($config["email"]["email_name"]==""?"x":"check")." icon"),"")); 
                $body.=InsertTags("div",array("class"=>"flex","style"=>"margin-top:20px"),
                InsertTags("i",array("class"=>"lock icon"),null).
                "&nbsp;&nbsp;&nbsp;&nbsp;".InsertSingleTag("input",array(
                    "id"=>"password-input",
                    "type"=>"password",
                    "placeholder"=>"Email Password",
                    "value"=>$config["email"]["email_password"]
                ))."&nbsp;&nbsp;&nbsp;&nbsp;".
                InsertTags("i",array("class"=>($config["email"]["email_password"]==""?"x":"check")." icon"),"")); 
                $body.=InsertTags("div",array("class"=>"flex","style"=>"margin-top:20px"),
                InsertTags("i",array("class"=>"child icon"),null).
                "&nbsp;&nbsp;&nbsp;&nbsp;".InsertSingleTag("input",array(
                    "id"=>"sender-input",
                    "placeholder"=>"Sender Name",
                    "value"=>$config["email"]["email_from"]
                ))."&nbsp;&nbsp;&nbsp;&nbsp;".
                InsertTags("i",array("class"=>($config["email"]["email_from"]==""?"x":"check")." icon"),""));
                $body.=InsertTags("div",array("style"=>"margin-top:10px"),InsertTags("center",null,
                    InsertTags("button",array("onclick"=>"previous()"),"Previous").
                    InsertTags("button",array("onclick"=>"submit()"),"Next")));
                $script.="function submit() {";
                $script.="var json = SendAjax('".GetAPIUrl("/config")."', 'POST', {passwd:'$passwd', key:'//email//email_host', value:document.getElementById('server-input').value});";
                $script.="var arr = JSON.parse(strip_tags_pre(json)); if (arr['code'] != 0) {layer.msg(arr['message']); return false;}";
                $script.="var json = SendAjax('".GetAPIUrl("/config")."', 'POST', {passwd:'$passwd', key:'//email//email_name', value:document.getElementById('user-input').value});";
                $script.="var arr = JSON.parse(strip_tags_pre(json)); if (arr['code'] != 0) {layer.msg(arr['message']); return false;}";
                $script.="var json = SendAjax('".GetAPIUrl("/config")."', 'POST', {passwd:'$passwd', key:'//email//email_password', value:document.getElementById('password-input').value});";
                $script.="var arr = JSON.parse(strip_tags_pre(json)); if (arr['code'] != 0) {layer.msg(arr['message']); return false;}";
                $script.="var json = SendAjax('".GetAPIUrl("/config")."', 'POST', {passwd:'$passwd', key:'//email//email_protocol', value:document.getElementById('protocol-input').value});";
                $script.="var arr = JSON.parse(strip_tags_pre(json)); if (arr['code'] != 0) {layer.msg(arr['message']); return false;}";
                $script.="var json = SendAjax('".GetAPIUrl("/config")."', 'POST', {passwd:'$passwd', key:'//email//email_port', value:document.getElementById('port-input').value});";
                $script.="var arr = JSON.parse(strip_tags_pre(json)); if (arr['code'] != 0) {layer.msg(arr['message']); return false;}";
                $script.="var json = SendAjax('".GetAPIUrl("/config")."', 'POST', {passwd:'$passwd', key:'//email//email_from', value:document.getElementById('sender-input').value});";
                $script.="var arr = JSON.parse(strip_tags_pre(json)); if (arr['code'] != 0) {layer.msg(arr['message']); return false;}";
                $script.="location.href='".GetUrl("config",array("step"=>$step+1, "passwd"=>$passwd))."';";
                $script.="}";
                $script.="function previous() {";
                $script.="var json = SendAjax('".GetAPIUrl("/config")."', 'POST', {passwd:'$passwd', key:'//email//email_host', value:document.getElementById('server-input').value});";
                $script.="var arr = JSON.parse(strip_tags_pre(json)); if (arr['code'] != 0) {layer.msg(arr['message']); return false;}";
                $script.="var json = SendAjax('".GetAPIUrl("/config")."', 'POST', {passwd:'$passwd', key:'//email//email_name', value:document.getElementById('user-input').value});";
                $script.="var arr = JSON.parse(strip_tags_pre(json)); if (arr['code'] != 0) {layer.msg(arr['message']); return false;}";
                $script.="var json = SendAjax('".GetAPIUrl("/config")."', 'POST', {passwd:'$passwd', key:'//email//email_password', value:document.getElementById('password-input').value});";
                $script.="var arr = JSON.parse(strip_tags_pre(json)); if (arr['code'] != 0) {layer.msg(arr['message']); return false;}";
                $script.="var json = SendAjax('".GetAPIUrl("/config")."', 'POST', {passwd:'$passwd', key:'//email//email_protocol', value:document.getElementById('protocol-input').value});";
                $script.="var arr = JSON.parse(strip_tags_pre(json)); if (arr['code'] != 0) {layer.msg(arr['message']); return false;}";
                $script.="var json = SendAjax('".GetAPIUrl("/config")."', 'POST', {passwd:'$passwd', key:'//email//email_port', value:document.getElementById('port-input').value});";
                $script.="var arr = JSON.parse(strip_tags_pre(json)); if (arr['code'] != 0) {layer.msg(arr['message']); return false;}";
                $script.="var json = SendAjax('".GetAPIUrl("/config")."', 'POST', {passwd:'$passwd', key:'//email//email_from', value:document.getElementById('sender-input').value});";
                $script.="var arr = JSON.parse(strip_tags_pre(json)); if (arr['code'] != 0) {layer.msg(arr['message']); return false;}";
                $script.="location.href='".GetUrl("config",array("step"=>$step-1, "passwd"=>$passwd))."';";
                $script.="}";
            } break;
            case 7: {
                $body.=InsertTags("hp",array("style"=>InsertInlineCssStyle(array("font-weight"=>"200"))),"&nbsp;&nbsp;Save Configure");
                if ($param["op"] == "cover") {
                    $fp = fopen("/etc/judge/config.json", "r");
                    $json = fread($fp, filesize("/etc/judge/config.json"));
                    fclose($fp); 
                    $fp = fopen("/etc/judge/config.json.bak", "w");
                    fwrite($fp, $json); 
                    fclose($fp);
                    $arr = json_decode($json, true);
                    $arr["web"]["footer"] = json_encode($arr["web"]["footer"], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                    $fp = fopen("./api/config.json", "w");
                    fwrite($fp, json_encode($arr, JSON_UNESCAPED_UNICODE));
                    fclose($fp); 
                    $script.="alert('Success!'); location.href = '".GetUrl("config", array("step" => 1, "passwd" => $passwd))."';";
                } else if ($param["op"] == "write") {
                    $fp = fopen("./api/config.json", "r");
                    $json = fread($fp, filesize("./api/config.json"));
                    $arr = json_decode($json, true);
                    $arr["web"]["footer"] = json_decode($arr["web"]["footer"], true);
                    $arr["web"]["install"] = 1;
                    $arr["web"]["absolute_path"] = true;
                    fclose($fp); $fp = fopen("/etc/judge/config.json", "w");
                    fwrite($fp, json_encode($arr, JSON_UNESCAPED_UNICODE));
                    $script.="alert('Success!'); location.href = '".GetUrl($config["web"]["default_path"], null)."';";
                    fclose($fp);
                } else {
                    $body.=InsertSingleTag("br", null);
                    $body.=InsertSingleTag("br", null);
                    $body.=InsertSingleTag("br", null);
                    $body.=InsertTags("hp", null, "Choose Operation");
                    $tmp=InsertTags("p",array("style"=>InsertInlineCssStyle(array("width"=>"3%"))),InsertTags("i",array("class"=>"angle right icon green"),null));
                    $tmp.=InsertTags("a",array(
                        "style"=>InsertInlineCssStyle(array("cursor"=>"pointer")),
                        "href"=>GetUrl("config", array("step" => $step, "passwd" => $passwd, "op" => "write")),
                        "class"=>"ellipsis"
                    ),"A. Create backup & Write temporary configure to website config!");
                    $body.=InsertSingleTag("br", null);
                    $body.=InsertSingleTag("br", null);
                    $body.=InsertTags("div",array(
                        "style"=>InsertInlineCssStyle(array("min-height"=>"40px","padding-left"=>"20px")),
                        "class"=>"default_main3 flex",
                    ),$tmp);
                    $tmp=InsertTags("p",array("style"=>InsertInlineCssStyle(array("width"=>"3%"))),InsertTags("i",array("class"=>"angle right icon green"),null));
                    $tmp.=InsertTags("a",array(
                        "style"=>InsertInlineCssStyle(array("cursor"=>"pointer")),
                        "href"=>GetUrl("config", array("step" => $step, "passwd" => $passwd, "op" => "cover")),
                        "class"=>"ellipsis"
                    ),"B. Cover temporary configure by website config!");
                    $body.=InsertTags("div",array(
                        "style"=>InsertInlineCssStyle(array("min-height"=>"40px","padding-left"=>"20px")),
                        "class"=>"default_main3 flex",
                    ),$tmp);
                    $tmp=InsertTags("p",array("style"=>InsertInlineCssStyle(array("width"=>"3%"))),InsertTags("i",array("class"=>"angle right icon green"),null));
                    $tmp.=InsertTags("a",array(
                        "style"=>InsertInlineCssStyle(array("cursor"=>"pointer")),
                        "href"=>GetUrl("config", array("step" => $step-1, "passwd" => $passwd,)),
                        "class"=>"ellipsis"
                    ),"C. Return to modify some configure!");
                    $body.=InsertTags("div",array(
                        "style"=>InsertInlineCssStyle(array("min-height"=>"40px","padding-left"=>"20px")),
                        "class"=>"default_main3 flex",
                    ),$tmp);
                }
            }
        }
        $script .= "document.title = 'Configure - LYOJ | LittleYang OnlineJudge';";
        $body.=InsertTags("style",null,$style);
        $body.=InsertTags("script",null,$script);
    }
}
?>