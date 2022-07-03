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
function configCheck2($step):bool {
    global $error_info;
    switch($step) {
        case 1: {
            $ok=compareVersion(phpversion(),"8.0.0")<=2&&class_exists("mysqli")&&class_exists("ZipArchive")
                &&function_exists("json_encode")&&exec("ps -ef | grep mysqld | grep -v grep")!="";
            return $ok;
        } break;
        case 2: {
            mysqli_report(MYSQLI_REPORT_OFF);
            $conn=mysqli_connect($_COOKIE["mysql-server"],$_COOKIE["mysql-user"],
                $_COOKIE["mysql-passwd"],$_COOKIE["mysql-database"],intval($_COOKIE["mysql-port"]));
            if (is_bool($conn))
                $error_info="Couldn't connect to database according to the configuration you provide!".
                "<br/>Error info: ".mysqli_connect_error();
            return !is_bool($conn);
        } break;
        case 3: {
            if ($_COOKIE["web-name"]==""){$error_info="Website name couldn't be empty";return false;}
            if ($_COOKIE["web-title"]==""){$error_info="Website title suffix couldn't be empty";return false;}
            if ($_COOKIE["web-protocol"]!="http"&&$_COOKIE["web-protocol"]!="https") 
                {$error_info="Invalid website protocol";return false;}
            if ($_COOKIE["web-domain"]==""){$error_info="Website domain couldn't be empty";return false;}
            if (!file_exists($_COOKIE["web-logo"])){$error_info="Couldn't find file '".$_COOKIE["web-logo"]."'";return false;}
            if (!file_exists($_COOKIE["web-rsa_private_key"])){$error_info="Couldn't find file '".$_COOKIE["web-rsa_private_key"]."'";return false;}
            if (!file_exists($_COOKIE["web-rsa_public_key"])){$error_info="Couldn't find file '".$_COOKIE["web-rsa_public_key"]."'";return false;}
            if (!is_numeric($_COOKIE["web-cookie_time"])){$error_info="Cookie time must be a valid integer";return false;}
            if (!date_default_timezone_set($_COOKIE["web-timezone"])){$error_info="Invalid timezone '".$_COOKIE["web-timezone"].
                "', reference <a href='https://www.php.net/manual/en/timezones.php'>https://www.php.net/manual/en/timezones.php</a>";return false;}
            $fp=fopen($_COOKIE["web-rsa_private_key"],"r");
            if (!$fp){$error_info="Couldn't open file '".$_COOKIE["web-rsa_private_key"]."'";return false;}
            $private=filesize($_COOKIE["web-rsa_private_key"])==0?"":fread($fp,filesize($_COOKIE["web-rsa_private_key"]));
            if (!$private){$error_info="Invalid RSA private key";return false;} fclose($fp);
            $fp=fopen($_COOKIE["web-rsa_public_key"],"r");
            if (!$fp){$error_info="Couldn't open file '".$_COOKIE["web-rsa_public_key"]."'";return false;}
            $public=filesize($_COOKIE["web-rsa_public_key"])==0?"":fread($fp,filesize($_COOKIE["web-rsa_public_key"]));
            if (!$public){$error_info="Invalid RSA public key";return false;} fclose($fp);
            $key=bin2hex(random_bytes(5));
            $ret=openssl_public_encrypt($key,$crypted,$public);
            if (!$ret){$error_info="Invalid RSA public key";return false;}
            $ret=openssl_private_decrypt($crypted,$decrypted,$private);
            if (!$ret){$error_info="Invalid RSA private key";return false;}
            if ($decrypted!=$key){$error_info="Unmatched public key and private key";return false;}
            return true;
        } break;
        case 4: {
            $arr=JSON_decode($_COOKIE["web-footer"],true);
            if ($arr==null) {$error_info="Invalid JSON data, ".json_last_error_msg();return false;}
            return true;
        } break;
        default: return false;
    } 
}
function run(array $param,string &$html,string &$body):void {
    $step=1; if (array_key_exists("step",$param)) $step=$param["step"];
    if (!is_numeric($step)) $step=1;
    $script="var menu=document.getElementsByClassName('menu-item');";
    $script.="for (i=0;i<menu.length;i++)menu[i].style.display='none';";
    $script.="setTimeout(function(){";
    $script.="document.getElementsByClassName('footer')[0].style.display='none';";
    $script.="document.getElementsByClassName('copyright')[0].style.paddingTop='15px';";
    $script.="document.getElementsByClassName('copyright')[0].style.position='fixed';";
    $script.="document.getElementsByClassName('copyright')[0].style.bottom='0px';";
    $script.="document.getElementsByTagName('main')[0].style.minHeight='0px';";
    $script.="document.getElementsByTagName('main')[0].style.marginBottom='80px';";
    $script.="if (window.innerWidth>=800) document.getElementsByTagName('main')[0].style.width='800px';";
    $script.="else document.getElementsByTagName('main')[0].style.width='100%';";
    $script.="},0);";
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
    } $body.=InsertTags("hp",array("style"=>InsertInlineCssStyle(array("font-size"=>"35px"))),"Program Installer");
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
                    InsertTags("button",array("onclick"=>"location.href='".GetUrl("install",array("step"=>2))."'"),"Next"));
            }
        } break;
        case 2: {
            $body.=InsertTags("hp",array("style"=>InsertInlineCssStyle(array("font-weight"=>"200"))),"&nbsp;&nbsp;Database Information");
            $body.=InsertTags("div",array("class"=>"flex","style"=>"margin-top:30px"),
            InsertTags("i",array("class"=>"server icon"),null).
            "&nbsp;&nbsp;&nbsp;&nbsp;".InsertSingleTag("input",array(
                "id"=>"server-input",
                "placeholder"=>"MySQL / MariaDB Server IP Address (default: 127.0.0.1)",
                "value"=>$_COOKIE["mysql-server"]
            ))."&nbsp;&nbsp;&nbsp;&nbsp;".
            InsertTags("i",array("class"=>($_COOKIE["mysql-server"]==""?"x":"check")." icon"),"")); $body.=InsertTags("div",array("class"=>"flex","style"=>"margin-top:20px"),
            InsertTags("i",array("class"=>"circle icon"),null).
            "&nbsp;&nbsp;&nbsp;&nbsp;".InsertSingleTag("input",array(
                "id"=>"port-input",
                "placeholder"=>"MySQL / MariaDB Server Port (default: 3306)",
                "value"=>$_COOKIE["mysql-port"]
            ))."&nbsp;&nbsp;&nbsp;&nbsp;".
            InsertTags("i",array("class"=>(!is_numeric($_COOKIE["mysql-port"])?"x":"check")." icon"),"")); $body.=InsertTags("div",array("class"=>"flex","style"=>"margin-top:20px"),
            InsertTags("i",array("class"=>"user icon"),null).
            "&nbsp;&nbsp;&nbsp;&nbsp;".InsertSingleTag("input",array(
                "id"=>"user-input",
                "placeholder"=>"MySQL / MariaDB Admin User",
                "value"=>$_COOKIE["mysql-user"]
            ))."&nbsp;&nbsp;&nbsp;&nbsp;".
            InsertTags("i",array("class"=>($_COOKIE["mysql-user"]==""?"x":"check")." icon"),"")); $body.=InsertTags("div",array("class"=>"flex","style"=>"margin-top:20px"),
            InsertTags("i",array("class"=>"lock icon"),null).
            "&nbsp;&nbsp;&nbsp;&nbsp;".InsertSingleTag("input",array(
                "id"=>"password-input",
                "type"=>"password",
                "placeholder"=>"MySQL / MariaDB Admin Password",
                "value"=>$_COOKIE["mysql-passwd"]
            ))."&nbsp;&nbsp;&nbsp;&nbsp;".
            InsertTags("i",array("class"=>($_COOKIE["mysql-passwd"]==""?"x":"check")." icon"),"")); $body.=InsertTags("div",array("class"=>"flex","style"=>"margin-top:20px"),
            InsertTags("i",array("class"=>"database icon"),null).
            "&nbsp;&nbsp;&nbsp;&nbsp;".InsertSingleTag("input",array(
                "id"=>"database-input",
                "placeholder"=>"MySQL / MariaDB Database",
                "value"=>$_COOKIE["mysql-database"]
            ))."&nbsp;&nbsp;&nbsp;&nbsp;".
            InsertTags("i",array("class"=>($_COOKIE["mysql-database"]==""?"x":"check")." icon"),""));
            $body.=InsertTags("div",array("style"=>"margin-top:10px"),InsertTags("center",null,
                InsertTags("button",array("onclick"=>"previous()"),"Previous").
                InsertTags("button",array("onclick"=>"submit()"),"Next")));
            $script.="function setCookie(name,value,expire) {";
            $script.="var exp=new Date(); exp.setTime(exp.getTime()+expire*1000);";
            $script.="document.cookie=name+\"=\"+escape(value)+\";expires=\"+exp.toGMTString()+\";\";}";
            $script.="function submit() {";
            $script.="setCookie('mysql-server',document.getElementById('server-input').value,1e10);";
            $script.="setCookie('mysql-port',document.getElementById('port-input').value,1e10);";
            $script.="setCookie('mysql-user',document.getElementById('user-input').value,1e10);";
            $script.="setCookie('mysql-passwd',document.getElementById('password-input').value,1e10);";
            $script.="setCookie('mysql-database',document.getElementById('database-input').value,1e10);";
            $script.="location.href='".GetUrl("install",array("step"=>3))."';";
            $script.="}";
            $script.="function previous() {";
            $script.="setCookie('mysql-server',document.getElementById('server-input').value,1e10);";
            $script.="setCookie('mysql-port',document.getElementById('port-input').value,1e10);";
            $script.="setCookie('mysql-user',document.getElementById('user-input').value,1e10);";
            $script.="setCookie('mysql-passwd',document.getElementById('password-input').value,1e10);";
            $script.="setCookie('mysql-database',document.getElementById('database-input').value,1e10);";
            $script.="location.href='".GetUrl("install",array("step"=>1))."';";
            $script.="}";
        } break;
        case 3: {
            $body.=InsertTags("hp",array("style"=>InsertInlineCssStyle(array("font-weight"=>"200"))),"&nbsp;&nbsp;Website Configure");
            $body.=InsertTags("div",array("class"=>"flex","style"=>"margin-top:30px"),
            InsertTags("i",array("class"=>"print icon"),null).
            "&nbsp;&nbsp;&nbsp;&nbsp;".InsertSingleTag("input",array(
                "id"=>"name-input",
                "placeholder"=>"Online Judge Name (eg: LYOJ)",
                "value"=>$_COOKIE["web-name"]
            ))."&nbsp;&nbsp;&nbsp;&nbsp;".
            InsertTags("i",array("class"=>($_COOKIE["web-name"]==""?"x":"check")." icon"),""));
            $body.=InsertTags("div",array("class"=>"flex","style"=>"margin-top:30px"),
            InsertTags("i",array("class"=>"info icon"),null).
            "&nbsp;&nbsp;&nbsp;&nbsp;".InsertSingleTag("input",array(
                "id"=>"title-input",
                "placeholder"=>"Website Title Suffix",
                "value"=>$_COOKIE["web-title"]
            ))."&nbsp;&nbsp;&nbsp;&nbsp;".
            InsertTags("i",array("class"=>($_COOKIE["web-title"]==""?"x":"check")." icon"),"")); $body.=InsertTags("div",array("class"=>"flex","style"=>"margin-top:30px"),
            InsertTags("i",array("class"=>"cog icon"),null).
            "&nbsp;&nbsp;&nbsp;&nbsp;".InsertSingleTag("input",array(
                "id"=>"protocol-input",
                "placeholder"=>"Website Protocol (http/https)",
                "value"=>$_COOKIE["web-protocol"]
            ))."&nbsp;&nbsp;&nbsp;&nbsp;".
            InsertTags("i",array("class"=>($_COOKIE["web-protocol"]!="http"&&$_COOKIE["web-protocol"]!="https"?"x":"check")." icon"),"")); $body.=InsertTags("div",array("class"=>"flex","style"=>"margin-top:30px"),
            InsertTags("i",array("class"=>"globe icon"),null).
            "&nbsp;&nbsp;&nbsp;&nbsp;".InsertSingleTag("input",array(
                "id"=>"domain-input",
                "placeholder"=>"Website Domain",
                "value"=>$_COOKIE["web-domain"]
            ))."&nbsp;&nbsp;&nbsp;&nbsp;".
            InsertTags("i",array("class"=>($_COOKIE["web-domain"]==""?"x":"check")." icon"),"")); $body.=InsertTags("div",array("class"=>"flex","style"=>"margin-top:30px"),
            InsertTags("i",array("class"=>"image icon"),null).
            "&nbsp;&nbsp;&nbsp;&nbsp;".InsertSingleTag("input",array(
                "id"=>"logo-input",
                "placeholder"=>"Website Logo Path",
                "value"=>$_COOKIE["web-logo"]
            ))."&nbsp;&nbsp;&nbsp;&nbsp;".
            InsertTags("i",array("class"=>(!file_exists($_COOKIE["web-logo"])?"x":"check")." icon"),"")); $body.=InsertTags("div",array("class"=>"flex","style"=>"margin-top:30px"),
            InsertTags("i",array("class"=>"lock icon"),null).
            "&nbsp;&nbsp;&nbsp;&nbsp;".InsertSingleTag("input",array(
                "id"=>"rsa_private_key-input",
                "placeholder"=>"RSA Private Key Path",
                "value"=>$_COOKIE["web-rsa_private_key"]
            ))."&nbsp;&nbsp;&nbsp;&nbsp;".
            InsertTags("i",array("class"=>(!file_exists($_COOKIE["web-rsa_private_key"])?"x":"check")." icon"),"")); $body.=InsertTags("div",array("class"=>"flex","style"=>"margin-top:30px"),
            InsertTags("i",array("class"=>"key icon"),null).
            "&nbsp;&nbsp;&nbsp;&nbsp;".InsertSingleTag("input",array(
                "id"=>"rsa_public_key-input",
                "placeholder"=>"RSA Public Key Path",
                "value"=>$_COOKIE["web-rsa_public_key"]
            ))."&nbsp;&nbsp;&nbsp;&nbsp;".
            InsertTags("i",array("class"=>(!file_exists($_COOKIE["web-rsa_public_key"])?"x":"check")." icon"),"")); $body.=InsertTags("div",array("class"=>"flex","style"=>"margin-top:30px"),
            InsertTags("i",array("class"=>"cloud icon"),null).
            "&nbsp;&nbsp;&nbsp;&nbsp;".InsertSingleTag("input",array(
                "id"=>"cookie_time-input",
                "placeholder"=>"Cookie Saving Time (In Days)",
                "value"=>$_COOKIE["web-cookie_time"]
            ))."&nbsp;&nbsp;&nbsp;&nbsp;".
            InsertTags("i",array("class"=>(!is_numeric($_COOKIE["web-cookie_time"])?"x":"check")." icon"),"")); $body.=InsertTags("div",array("class"=>"flex","style"=>"margin-top:30px"),
            InsertTags("i",array("class"=>"time icon"),null).
            "&nbsp;&nbsp;&nbsp;&nbsp;".InsertSingleTag("input",array(
                "id"=>"timezone-input",
                "placeholder"=>"Server Time Zone",
                "value"=>$_COOKIE["web-timezone"]
            ))."&nbsp;&nbsp;&nbsp;&nbsp;".
            InsertTags("i",array("class"=>(!date_default_timezone_set($_COOKIE["web-timezone"])?"x":"check")." icon"),""));
            $body.=InsertTags("div",array("style"=>"margin-top:10px"),InsertTags("center",null,
                InsertTags("button",array("onclick"=>"previous()"),"Previous").
                InsertTags("button",array("onclick"=>"submit()"),"Next")));
            $script.="function setCookie(name,value,expire) {";
            $script.="var exp=new Date(); exp.setTime(exp.getTime()+expire*1000);";
            $script.="document.cookie=name+\"=\"+escape(value)+\";expires=\"+exp.toGMTString()+\";\";}";
            $script.="function submit() {";
            $script.="setCookie('web-name',document.getElementById('name-input').value,1e10);";
            $script.="setCookie('web-title',document.getElementById('title-input').value,1e10);";
            $script.="setCookie('web-protocol',document.getElementById('protocol-input').value,1e10);";
            $script.="setCookie('web-domain',document.getElementById('domain-input').value,1e10);";
            $script.="setCookie('web-logo',document.getElementById('logo-input').value,1e10);";
            $script.="setCookie('web-rsa_private_key',document.getElementById('rsa_private_key-input').value,1e10);";
            $script.="setCookie('web-rsa_public_key',document.getElementById('rsa_public_key-input').value,1e10);";
            $script.="setCookie('web-cookie_time',document.getElementById('cookie_time-input').value,1e10);";
            $script.="setCookie('web-timezone',document.getElementById('timezone-input').value,1e10);";
            $script.="location.href='".GetUrl("install",array("step"=>4))."';}";
            $script.="function previous() {";
            $script.="setCookie('web-name',document.getElementById('name-input').value,1e10);";
            $script.="setCookie('web-title',document.getElementById('title-input').value,1e10);";
            $script.="setCookie('web-protocol',document.getElementById('protocol-input').value,1e10);";
            $script.="setCookie('web-domain',document.getElementById('domain-input').value,1e10);";
            $script.="setCookie('web-logo',document.getElementById('logo-input').value,1e10);";
            $script.="setCookie('web-rsa_private_key',document.getElementById('rsa_private_key-input').value,1e10);";
            $script.="setCookie('web-rsa_public_key',document.getElementById('rsa_public_key-input').value,1e10);";
            $script.="setCookie('web-cookie_time',document.getElementById('cookie_time-input').value,1e10);";
            $script.="setCookie('web-timezone',document.getElementById('timezone-input').value,1e10);";
            $script.="location.href='".GetUrl("install",array("step"=>2))."';}";
        } break;
        case 4: {
            $body.=InsertTags("hp",array("style"=>InsertInlineCssStyle(array("font-weight"=>"200"))),"&nbsp;&nbsp;Footer Configure"); $content="";
            if ($_COOKIE["web-footer"]=="") $content="[\n    {\n        \"title\":\"My Website\",\n        \"url\":{".
            "\n            \"Yucai News Weekly\":\"https://www.littleyang.ml\",\n            \"LittleYang0531's Blog\":\"https://blog.littleyang.ml\",".
            "\n            \"Public Image Repository\":\"https://pic.littleyang.ml\",\n            \"Image Website for My Class\":\"https://photo.littleyang.ml\",".
            "\n            \"Gitea for LittleYang\":\"https://git.littleyang.ml\",\n            \"Tool Website\":\"https://tools.littleyang.ml\"".
            "\n        }\n    },\n    {\n        \"title\":\"My Repository\",\n        \"url\":{\n            \"News Website\":\"https://github.com/LittleYang0531/News-Website\",".
            "\n            \"LittleYang Onlinejudge\":\"https://github.com/LittleYang0531/lyoj\"\n        }\n    },\n    {".
            "\n        \"title\":\"About Me\",\n        \"url\":{\n            \"My Github\":\"https://github.com/LittleYang0531\",".
            "\n            \"My Email\":\"mailto:admin@littleyang.ml\",\n            \"Feedback Email\":\"mailto:feedback@littleyang.ml\"\n        }\n    }\n]";
            else $content=$_COOKIE["web-footer"];
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
            $script.="function setCookie(name,value,expire) {";
            $script.="var exp=new Date(); exp.setTime(exp.getTime()+expire*1000);";
            $script.="document.cookie=name+\"=\"+escape(value)+\";expires=\"+exp.toGMTString()+\";\";}";
            $script.="function submit() {";
            $script.="setCookie('web-footer',document.getElementById('footer-input').value,1e10);";
            $script.="location.href='".GetUrl("install",array("step"=>5))."';}";
            $script.="function previous() {";
            $script.="setCookie('web-footer',document.getElementById('footer-input').value,1e10);";
            $script.="location.href='".GetUrl("install",array("step"=>3))."';}";
        } break;
        case 5: {
            $body.=InsertTags("hp",array("style"=>InsertInlineCssStyle(array("font-weight"=>"200"))),"&nbsp;&nbsp;Language Configure");
            $body.=InsertTags("div",array("id"=>"language","style"=>InsertInlineCssStyle(array("margin-top"=>"30px"))),"");
            $tmp=InsertTags("p",array("style"=>InsertInlineCssStyle(array("width"=>"3%"))),InsertTags("i",array("class"=>"angle right icon green"),null));
            $tmp.=InsertTags("p",array(
                "style"=>InsertInlineCssStyle(array("width"=>"40%","cursor"=>"pointer")),
                "onclick"=>"add('',0,0,0,'','','')",
                "class"=>"ellipsis"
            ),"Create a new language!");
            $body.=InsertTags("div",array(
                "style"=>InsertInlineCssStyle(array("min-height"=>"50px","margin-top"=>"20px","padding-left"=>"20px")),
                "class"=>"default_main3 flex",
            ),$tmp);
            $script.="var language_number=0; add('',0,0,0,'','','');";
            $script.="function add(name,type,mode,hlmode,source_path,command,exec_command){";
            $script.="language_number++;";
            $script.="document.getElementById('language').innerHTML+=\"<div class='default_main3' ";
            $script.="style='padding:20px;margin-top:20px;'>\"+";
            $script.="\"<hp style='font-size:18px;font-weight:200'>Language #\"+language_number+\"</hp>";
            $script.="<div class='flex' style='margin-top:10px;font-size:14px'>Language Name&nbsp&nbsp";
            $script.="<input id='name-\"+i+\"' placeholder='Input this language name here' value='\"+name+\"'/></div>";
            $script.="<div class='flex' style='margin-top:5px;font-size:14px'>Language Type&nbsp&nbsp<select id='type-\"+i+\"'>";
            $script.="<option value=0 id='type-\"+i+\"-0'>Source need to be compiled and compiler will output a executable file</option>";
            $script.="<option value=1 id='type-\"+i+\"-1'>Source need to be compiled and compiler do not output a executable file</option>";
            $script.="<option value=2 id='type-\"+i+\"-2'>Source need to be explained by the compiler</option></select></div></div>\";";
            $script.="document.getElementById('type-'+i+'-'+type).setAttribute('selected','selected');";
            $script.="}";
        }
    }
    $body.=InsertTags("style",null,$style);
    $body.=InsertTags("script",null,$script);
}
?>