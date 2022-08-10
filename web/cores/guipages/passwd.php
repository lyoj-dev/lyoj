<?php
function run(array $param,string& $html,string& $body):void {
    if (array_key_exists("email",$param)&&array_key_exists("token",$param)) {
        $login_controller=new Login_Controller;
        if ($login_controller->CheckToken($param["email"],$param["token"])) {
            update_run($param,$html,$body);
            return;
        }
    } 
    $config=GetConfig(); $lang=GetLanguage()["main"];
    $login=InsertTags("hp",array("style"=>InsertInlineCssStyle(array(
        "padding-right"=>"20px",
        "margin-bottom"=>"20px"
    ))),$lang["title"]);
    $login.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array(
        "padding-top"=>"10px"
    ))), InsertTags("p",null,$lang["email"].":&nbsp;").
    InsertSingleTag("input",array("id"=>"email","type"=>"text","placeholder"=>$lang["email-placeholder"])));
    $login.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array(
        "padding-top"=>"10px",
        "justify-content"=>"center"
    ))),InsertTags("button",array("onclick"=>"passwd()"),$lang["submit"]));
    $body.=InsertTags("div",array("class"=>"default_main","style"=>InsertInlineCssStyle(array(
        "padding-top"=>"20px",
        "padding-left"=>"20px",
        "padding-right"=>"20px",
        "padding-bottom"=>"15px",
        "margin-bottom"=>"20px",
        "width"=>"40%",
        "margin"=>"auto"
    ))),$login);
    $script="function strip_tags_pre(msg){msg=msg.replace(/<(\/)?pre[^>]*>/g,'');return msg;}";
    $script.="function setCookie(name,value,expire) {";
    $script.="var exp=new Date(); exp.setTime(exp.getTime()+expire*1000);";
    // $script.="console.log(exp.getTime());";
    // $script.="console.log(exp.getTime()+expire);";
    $script.="document.cookie=name+\"=\"+escape(value)+\";expires=\"+exp.toGMTString()+\";\";}";
    $script.="function passwd(){";
    $script.="var name=document.getElementById('email').value;";
    $script.="var res=strip_tags_pre(SendAjax('".GetAPIUrl("/login/passwdToken",null)."','POST',{email:name}));";
    $script.="if (JSON.parse(res)['code']) {layer.msg(JSON.parse(res)['message']); return false;}";
    $script.="res=JSON.parse(res)['data']; alert(\"Email send successfully!\");";
    $script.="window.location.href=\"".($param["return"]!=""?$param["return"]:$_COOKIE["history"])."\";";
    $script.="}";
    $script.="$(document).keypress(function(event){";
    $script.="var keynum=(event.keyCode?event.keyCode:event.which);  ";
    $script.="if(keynum=='13') passwd();";
    $script.="});";
    $body.=InsertTags("script",null,$script);
}

function update_run(array $param,string &$html,string &$body):void {
$config=GetConfig(); $lang=GetLanguage()["main"];
    $tmp=InsertTags("hp",array("style"=>InsertInlineCssStyle(array(
        "padding-right"=>"20px",
        "margin-bottom"=>"20px"
    ))),$lang["title"]);
    $tmp.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array(
        "padding-top"=>"10px"
    ))),InsertTags("p",null,$lang["password"].":&nbsp;").
    InsertSingleTag("input",array("id"=>"passwd","type"=>"password","placeholder"=>$lang["password-placeholder"])));
    $tmp.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array(
        "padding-top"=>"10px"
    ))),InsertTags("p",null,$lang["repeat-password"].":&nbsp;").
    InsertSingleTag("input",array("id"=>"repeat","type"=>"password","placeholder"=>$lang["repeat-password-placeholder"])));
    $tmp.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array(
        "padding-top"=>"10px",
        "justify-content"=>"center"
    ))),InsertTags("button",array("onclick"=>"passwd()"),$lang["submit"]));
    $body.=InsertTags("div",array("class"=>"default_main","style"=>InsertInlineCssStyle(array(
        "padding-top"=>"20px",
        "padding-left"=>"20px",
        "padding-right"=>"20px",
        "padding-bottom"=>"15px",
        "margin-bottom"=>"20px",
        "width"=>"40%",
        "margin"=>"auto"
    ))),$tmp);

    $script="function strip_tags_pre(msg){msg=msg.replace(/<(\/)?pre[^>]*>/g,'');return msg;}";
    $script.="function passwd(){";
    $script.="var passwd=document.getElementById('passwd').value;";
    $script.="var repeat=document.getElementById('repeat').value;";
    $script.="if (passwd!=repeat) {alert(\"The two password you input is the same!\"); return false;}";
    $script.="var public_key=strip_tags_pre(SendAjax('".GetAPIUrl("/login/public")."','GET',null));";
    $script.="if (JSON.parse(public_key)['code']) {layer.msg(JSON.parse(public_key)['message']); return false;}";
    $script.="public_key=JSON.parse(public_key)['data']['key'];";
    $script.="var hash=strip_tags_pre(SendAjax('".GetAPIUrl("/tool/crypt")."','POST',{data:passwd,key:public_key}));";
    $script.="if (JSON.parse(hash)['code']) {layer.msg(JSON.parse(hash)['message']); return false;}";
    $script.="hash=JSON.parse(hash)['data']['result'];";
    $script.="var res=strip_tags_pre(SendAjax('".GetAPIUrl("/login/passwdCheck",null)."','POST',{email:\"".$param["email"]."\",passwd:hash,token:\"".$param["token"]."\"}));";
    $script.="if (JSON.parse(res)['code']) {layer.msg(JSON.parse(res)['message']); return false;}";
    $script.="res=JSON.parse(res)['data']; alert(\"Change success! Please login!\");";
    $script.="location.href='".GetUrl("login",null)."';";
    $script.="}";
    $script.="$(document).keypress(function(event){";
    $script.="var keynum=(event.keyCode?event.keyCode:event.which);";
    $script.="if(keynum=='13') passwd();";
    $script.="});";
    $body.=InsertTags("script",null,$script);
}
?>