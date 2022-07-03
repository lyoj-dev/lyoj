<?php
function run(array $param,string& $html,string& $body):void {
    if (array_key_exists("code",$param)&&array_key_exists("uid",$param)) {
        verify_run($param,$html,$body);
        return;
    }
    $config=GetConfig(); $lang=GetLanguage()["main"];
    $login_controller=new Login_Controller;
    if ($login_controller->CheckLogin()) {
        $user_controller=new User_Controller;
        $info=$user_controller->GetWholeUserInfo($login_controller->CheckLogin());
        $script="alert(\"".str_replace("\$\$name\$\$",$info["name"],$lang["signed"])."\");";
        $script.="window.location.href=\"".($param["return"]!=""?$param["return"]:$_COOKIE["history"])."\";";
        $body.=InsertTags("script",null,$script);
        return;
    }
    $data=InsertTags("hp",array("style"=>InsertInlineCssStyle(array(
        "padding-right"=>"20px",
        "margin-bottom"=>"20px"
    ))),$lang["title"]);
    $data.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array(
        "padding-top"=>"10px"
    ))), InsertTags("p",null,$lang["email"].":&nbsp;").
    InsertSingleTag("input",array("id"=>"email","type"=>"text","placeholder"=>$lang["email-placeholder"])));
    $data.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array(
        "padding-top"=>"10px"
    ))), InsertTags("p",null,$lang["name"].":&nbsp;").
    InsertSingleTag("input",array("id"=>"name","type"=>"text","placeholder"=>$lang["name-placeholder"])));
    $data.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array(
        "padding-top"=>"10px"
    ))), InsertTags("p",null,$lang["password"].":&nbsp;").
    InsertSingleTag("input",array("id"=>"passwd","type"=>"password","placeholder"=>$lang["password-placeholder"])));
    $data.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array(
        "padding-top"=>"10px"
    ))), InsertTags("p",null,$lang["repeat-password"].":&nbsp;").
    InsertSingleTag("input",array("id"=>"passwd2","type"=>"password","placeholder"=>$lang["repeat-password-placeholder"])));
    $data.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array(
        "justify-content"=>"center"
    ))),InsertTags("button",array("onclick"=>"submit()"),$lang["submit"]));
    $body.=InsertTags("div",array("class"=>"default_main","style"=>InsertInlineCssStyle(array(
        "padding-top"=>"20px",
        "padding-left"=>"20px",
        "padding-right"=>"20px",
        "padding-bottom"=>"15px",
        "margin-bottom"=>"20px",
        "width"=>"40%",
        "margin"=>"auto"
    ))),$data);
    $script="function strip_tags_pre(msg){msg=msg.replace(/<(\/)?pre[^>]*>/g,'');return msg;}";
    $script.="function setCookie(name,value,expire) {";
    $script.="var exp=new Date(); exp.setTime(exp.getTime()+expire);";
    $script.="document.cookie=name+\"=\"+escape(value)+\";expires=\"+exp.toGMTString()+\";\";}";
    $script.="function submit(){";
    $script.="var name=document.getElementById('name').value;";
    $script.="var email=document.getElementById('email').value;";
    $script.="var passwd=document.getElementById('passwd').value;";
    $script.="var passwd2=document.getElementById('passwd2').value;";
    $script.="if (passwd!=passwd2) {layer.msg('Password is not the same!'); return false;}";
    $script.="if (passwd=='') {layer.msg('Password is empty!'); return false;}";
    $script.="var public_key=strip_tags_pre(SendAjax('".GetAPIUrl("/login/public")."','GET',null));";
    $script.="if (JSON.parse(public_key)['code']) {layer.msg(JSON.parse(public_key)['message']); return false;}";
    $script.="public_key=JSON.parse(public_key)['data']['key'];";
    $script.="var hash=strip_tags_pre(SendAjax('".GetAPIUrl("/tool/crypt")."','POST',{data:passwd,key:public_key}));";
    $script.="if (JSON.parse(hash)['code']) {layer.msg(JSON.parse(hash)['message']); return false;}";
    $script.="hash=JSON.parse(hash)['data']['result'];";
    $script.="var res=strip_tags_pre(SendAjax('".GetAPIUrl("/login/register",null)."','POST',{name:name,email:email,passwd:hash}));";
    $script.="if (JSON.parse(res)['code']) {layer.msg(JSON.parse(res)['message']); return false;}";
    $script.="res=JSON.parse(res)['data'];";
    $script.="alert(\"Register success! Please check your email to verify your account!\");";
    $script.="window.location.href=\"".($param["return"]!=""?$param["return"]:$_COOKIE["history"])."\";";
    $script.="}";
    $script.="$(document).keypress(function(event){";
    $script.="var keynum=(event.keyCode?event.keyCode:event.which);  ";
    $script.="if(keynum=='13') submit();";
    $script.="});";
    $body.=InsertTags("script",null,$script);
}
function verify_run(array $param,string& $html,string& $body):void {
    $code=$param["code"];
    $uid=$param["uid"];
    $login_controller=new Login_Controller;
    $login_controller->UserEmailVerify($uid,$code);
    $script="alert('Verify success! Please login!');";
    $script.="location.href='".GetUrl("login",null)."';";
    $body.=InsertTags("script",null,$script);
}
?>