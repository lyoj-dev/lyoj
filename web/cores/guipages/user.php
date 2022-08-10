<?php
function run(array $param,string &$html,string &$body):void {
    $style=InsertCssStyle(array(".head"),array(
        "width"=>"100%",
        "height"=>"250px",
        "background-image"=>"url('".GetUrl("data/user/".$param["id"]."/background.jpg",array("t"=>time()))."')",
        "background-size"=>"cover",
        "background-position"=>"center"
    )); $style.=InsertCssStyle(array(".button2"),array(
        "background-color"=>"rgba(0,0,0,0)",
        "border-color"=>"rgba(0,0,0,0)",
        "border-radius"=>"0px",
        "height"=>"40px",
        "font-weight"=>"100",
        "font-size"=>"15px",
        "margin-top"=>"0px",
        "position"=>"relative",
        "top"=>"-40px",
        "color"=>"white",
        "margin-right"=>"0px"
    )); $style.=InsertCssStyle(array(".page"),array(
        "background-color"=>"white",
        "color"=>"grey"
    )); $style.=InsertCssStyle(array(".button2:hover"),array(
        "color"=>"white",
        "background-color"=>"rgba(0,0,0,0)",
        "border-color"=>"rgba(0,0,0,0)"
    )); $style.=InsertCssStyle(array(".page:hover"),array(
        "background-color"=>"white",
        "color"=>"grey"
    )); $style.=InsertCssStyle(array(".div3"),array(
        "display"=>"none",
    )); $style.=InsertCssStyle(array("input[type='file']"),array(
        "display"=>"none"
    )); $style.=InsertCssStyle(array(".label"),array(
        "height"=>"30px",
        "line-height"=>"30px",
        "border"=>"1px solid",
        "border-color"=>"rgb(213,216,218)",
        "color"=>"rgb(27,116,221)",
        "margin-top"=>"10px",
        "margin-bottom"=>"10px",
        "margin-right"=>"5px",
        "padding-left"=>"20px",
        "padding-right"=>"20px",
        "padding-top"=>"5px",
        "padding-bottom"=>"8px",
        "border-radius"=>"3px",
        "font-weight"=>"500",
        "font-size"=>"13px",
        "background-color"=>"white",
        "cursor"=>"pointer",
        "transition"=>"background-color 0.5s,color 0.5s,border-color 0.5s",    
        "outline"=>"none"
    )); $style.=InsertCssStyle(array(".label:hover"),array(
            "background-color"=>"rgb(27,116,221)",
            "color"=>"white",
            "border-color"=>"rgb(27,116,221)"
        )
    ); $lang=GetLanguage()["main"]["info"];
    $user_controller=new User_Controller; 
    $status_controller=new Status_Controller;
    $contest_controller=new Contest_Controller;
    $problem_controller=new Problem_Controller;
    $database_controller=new Database_Controller;
    $login_controller=new Login_Controller;
    $userinfo=$user_controller->GetWholeUserInfo($param["id"]);
    if ($userinfo==null) Error_Controller::Common("Unknown user id");
    if ($userinfo["email"]==null) Error_Controller::Common("The user isn't exist");
    $tmp2=InsertSingleTag("img",array("src"=>GetRealUrl("data/user/".$param["id"]."/header.jpg",array("t"=>time())),"style"=>
        InsertInlineCssStyle(array("width"=>"125px","height"=>"125px","border-radius"=>"125px")),"id"=>"header"));
    $md=""; if (filesize("data/user/".$param["id"]."/intro.md")!=0) {
        $fp=fopen("data/user/".$param["id"]."/intro.md","r");
        $md=fread($fp,filesize("data/user/".$param["id"]."/intro.md"));
        fclose($fp);
    } $tmp2.=InsertTags("div",array("style"=>InsertInlineCssStyle(array("color"=>"white","margin-left"=>"18px","padding-top"=>"5px"))),
        InsertTags("hp",array("style"=>InsertInlineCssStyle(array("font-size"=>"40px","font-weight"=>"200"))),$userinfo["name"].
        InsertTags("hp",array("style"=>InsertInlineCssStyle(array("font-size"=>"30px","font-weight"=>"200"))),"&nbsp;(UID:".$param["id"].")")).InsertSingleTag("br",null).
        InsertTags("hp",array("style"=>InsertInlineCssStyle(array("font-size"=>"20px","font-weight"=>"200"))),str_replace("\$\$num\$\$",count($status_controller->ListWholeByUid($param["id"])),$lang["submitted"]).",&nbsp;").
        InsertTags("hp",array("style"=>InsertInlineCssStyle(array("font-size"=>"20px","font-weight"=>"200"))),str_replace("\$\$num\$\$",count($status_controller->ListAcceptedByUid($param["id"])),$lang["solved"])).InsertSingleTag("br",null).
        InsertTags("hp",array("style"=>InsertInlineCssStyle(array("font-size"=>"20px","font-weight"=>"200"))),str_replace("\$\$num\$\$",count($contest_controller->GetUserSignup($param["id"])),$lang["contest"]).",&nbsp;").
        InsertTags("hp",array("style"=>InsertInlineCssStyle(array("font-size"=>"20px","font-weight"=>"200"))),str_replace("\$\$num\$\$",$userinfo["rating"],str_replace("\$\$time\$\$",date("Y-m-d H:i:s",$userinfo["uptime"]),$lang["rating"]))));
    $tmp=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(
        array("margin-left"=>"15px","padding-top"=>"20px","align-items"=>"normal"))),$tmp2); $lang=GetLanguage()["main"];
    $button=InsertTags("button",array("onclick"=>"open_page('intro')","id"=>"intro-button","class"=>"button2"),$lang["button"]["introduction"]).
    InsertTags("button",array("onclick"=>"open_page('solved')","id"=>"solved-button","class"=>"button2"),$lang["button"]["solved-problem"]).
    InsertTags("button",array("onclick"=>"open_page('contest')","id"=>"contest-button","class"=>"button2"),$lang["button"]["contest-history"]);
    $uid=$login_controller->CheckLogin();
    $ok=$uid&&($param["id"]==$uid||$user_controller->GetWholeUserInfo($uid)["permission"]>1); 
    if ($ok) $button.=InsertTags("button",array("onclick"=>"open_page('setting')","id"=>"setting-button","class"=>"button2"),$lang["button"]["setting"]);
    $tmp=InsertTags("div",array("class"=>"head","id"=>"head"),$tmp);
    $main=InsertTags("div",array("style"=>InsertInlineCssStyle(array("padding-left"=>"20px","float"=>"bottom","height"=>"0px"))),$button);
    if (filesize("data/user/".$param["id"]."/intro.md")!=0) {
        $main.=InsertTags("div",array("id"=>"intro","style"=>InsertInlineCssStyle(array("padding"=>"30px","width"=>"calc( 100% - 60px )"))),null);
        $script=md2html($md,"intro");
    } else $main.=InsertTags("div",array("id"=>"intro","style"=>InsertInlineCssStyle(array("padding"=>"20px"))),
            InsertTags("hp",null,$lang["intro"]["none"]));
    
    $pids=$status_controller->ListAcceptedByUid($param["id"]);
    $tmp2=""; for ($i=0;$i<count($pids);$i++) {
        $pname=$database_controller->Query("SELECT name FROM problem WHERE pid=".$pids[$i])[0]["name"];
        $tmp2.=InsertTags("div",array("style"=>InsertInlineCssStyle(array("width"=>"45%","display"=>"inline-block","margin-top"=>"5px","margin-right"=>"5%"))),
        InsertTags("li",array("class"=>"ellipsis"),InsertTags("a",array("href"=>GetUrl("problem",array("id"=>$pids[$i]))),"P".$pids[$i]." - $pname")));
    } if (count($pids)==0) $tmp2=InsertTags("hp",null,$lang["problem"]["none"]);
    $main.=InsertTags("div",array("id"=>"solved","style"=>InsertInlineCssStyle(array("padding"=>"20px"))),$tmp2);

    $contest=$contest_controller->ListContestByUid($param["id"]);
    $style.=InsertCssStyle(array(".table"),array(
        "padding"=>"10px 10px 10px 10px",
        "border"=>"1px solid",
        "border-top-width"=>"0px",
        "border-right-width"=>"0px",
        "border-bottom-width"=>"0px",
        "justify-content"=>"center"
    ));
    $style.=InsertCssStyle(array(".table_line"),array(
        "border"=>"1px solid",
        "border-left-width"=>"0px",
        "border-top-width"=>"0px"
    ));
    $tmp2=""; if (count($contest)) {
        $tmp3=InsertTags("div",array("class"=>"table flex","style"=>InsertInlineCssStyle(array("width"=>"5%"))),"id");
        $tmp3.=InsertTags("div",array("class"=>"table flex","style"=>InsertInlineCssStyle(array("width"=>"20%"))),$lang["contest"]["date"]);
        $tmp3.=InsertTags("div",array("class"=>"table flex","style"=>InsertInlineCssStyle(array("width"=>"55%","padding"=>"10px 20px 10px 20px"))),$lang["contest"]["contest"]);
        $tmp3.=InsertTags("div",array("class"=>"table flex","style"=>InsertInlineCssStyle(array("width"=>"10%"))),$lang["contest"]["rank"]."&nbsp;/&nbsp;".$lang["contest"]["all"]);
        $tmp3.=InsertTags("div",array("class"=>"table flex","style"=>InsertInlineCssStyle(array("width"=>"10%"))),$lang["contest"]["diff"]);
        $tmp2.=InsertTags("div",array("class"=>"flex table_line","style"=>InsertInlineCssStyle(array("border-top-width"=>"1px","font-weight"=>"600"))),$tmp3);
        for ($i=0;$i<count($contest);$i++) {
            $tmp3=InsertTags("div",array("class"=>"table flex","style"=>InsertInlineCssStyle(array("width"=>"5%"))),
                InsertTags("a",array("href"=>GetUrl("contest",array("id"=>$contest[$i]["id"],"page"=>"index"))),"#".$contest[$i]["id"]));
            $tmp3.=InsertTags("div",array("class"=>"table flex","style"=>InsertInlineCssStyle(array("width"=>"20%"))), 
                date("Y-m-d H:i",$contest[$i]["date"]));
            $tmp3.=InsertTags("div",array("class"=>"table flex","style"=>InsertInlineCssStyle(array("width"=>"55%","justify-content"=>"left","padding"=>"10px 20px 10px 20px"))),
                InsertTags("a",array("href"=>GetUrl("contest",array("id"=>$contest[$i]["id"],"page"=>"index"))),$contest[$i]["name"]." (".($contest[$i]["rated"]?"Rated":"Unrated").")"));
            $tmp3.=InsertTags("div",array("class"=>"table flex","style"=>InsertInlineCssStyle(array("width"=>"10%"))),$contest[$i]["rank"]." / ".$contest[$i]["all"]);
            $tmp3.=InsertTags("div",array("class"=>"table flex","style"=>InsertInlineCssStyle(array("width"=>"10%"))),$contest[$i]["diff"]!=0?"+".$contest[$i]["diff"]:"-");
            $tmp2.=InsertTags("div",array("class"=>"flex table_line"),$tmp3);
        }    
    } else $tmp2=InsertTags("hp",null,$lang["contest"]["none"]);
    $main.=InsertTags("div",array("id"=>"contest","style"=>InsertInlineCssStyle(array("padding"=>"20px"))),$tmp2);
    
    if ($ok) {
        $tmp2=""; $tmp2.=InsertTags("hp",null,$lang["setting"][0]["title"]).
        InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("margin-top"=>"20px"))),
        InsertTags("p",null,$lang["setting"][0]["header"].":&nbsp;").
        InsertTags("div",array("class"=>"file"),InsertTags("label",array("for"=>"setting-header","class"=>"label"),$lang["setting"][0]["header-choose"])).
        InsertSingleTag("input",array("name"=>"setting-header","id"=>"setting-header","type"=>"file","accept"=>"image/jpeg")));
        $tmp2.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("margin-top"=>"10px"))),
        InsertTags("p",null,$lang["setting"][0]["background"].":&nbsp;").
        InsertTags("div",array("class"=>"file"),InsertTags("label",array("for"=>"setting-background","class"=>"label"),$lang["setting"][0]["background-choose"])).
        InsertSingleTag("input",array("name"=>"setting-background","id"=>"setting-background","type"=>"file","accept"=>"image/jpeg")));
        $tmp2.=InsertTags("p",array("style"=>InsertInlineCssStyle(array("margin-top"=>"15px"))),$lang["setting"][0]["introduction"].":");
        $tmp2.=InsertTags("div",array("id"=>"intro-editor","style"=>InsertInlineCssStyle(array("margin-top"=>"10px"))),null);
        $tmp2.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("justify-content"=>"center"))),InsertTags("button",array("onclick"=>"submit()","class"=>"button"),$lang["setting"][0]["submit"]));
        $main.=InsertTags("div",array("id"=>"setting","style"=>InsertInlineCssStyle(array("padding"=>"20px"))),$tmp2);
    }

    $tmp.=InsertTags("div",null,$main); $body.=InsertTags("div",array("class"=>"default_main"),$tmp);
    $script.="var page='".(array_key_exists("page",$param)?$param["page"]:"intro")."';";
    $script.="function open_page(id) {";
    $script.="document.getElementById('intro-button').classList.remove('page');";
    $script.="document.getElementById('solved-button').classList.remove('page');";
    $script.="document.getElementById('contest-button').classList.remove('page');";
    if ($ok) $script.="document.getElementById('setting-button').classList.remove('page');";
    $script.="document.getElementById('intro').style.display='none';";
    $script.="document.getElementById('solved').style.display='none';";
    $script.="document.getElementById('contest').style.display='none';";
    if ($ok) $script.="document.getElementById('setting').style.display='none';";
    $script.="document.getElementById(id).style.display='block';";
    $script.="document.getElementById(id+'-button').classList.add('page');";
    $script.="window.scrollTo(0,0);";
    $script.="}";
    if ($ok) {
        $script.="document.getElementById('setting-header').onchange=function(){";
        $script.="var input=document.getElementById('setting-header');";
		$script.="if(typeof(FileReader)==='undefined'){";
        $script.="alert(\"I'm sorry, your browser is not support FileReader!\");";
		$script.="input.setAttribute('disabled','disabled');}";
		$script.="var reader=new FileReader();";
		$script.="reader.readAsDataURL(input.files[0]);";
		$script.="reader.onload=function(){";
        $script.="SendAjax('".GetAPIUrl("/user/header",null)."','POST',{data:(this.result.split(','))[1],id:".$param["id"]."},function(){alert('Success!')});";
        $script.="document.getElementById('header').src='".GetRealUrl("data/user/".$param["id"]."/header.jpg",null)."?t='+Date.now();";
        $script.="document.getElementById('setting-header').value='';";
        $script.="}};";
        $script.="document.getElementById('setting-background').onchange=function(){";
        $script.="var input=document.getElementById('setting-background');";
        $script.="if(typeof(FileReader)==='undefined'){";
        $script.="alert(\"I'm sorry, your browser is not support FileReader!\");";
        $script.="input.setAttribute('disabled','disabled');}";
        $script.="var reader=new FileReader();";
        $script.="reader.readAsDataURL(input.files[0]);";
        $script.="reader.onload=function(){";
        $script.="SendAjax('".GetAPIUrl("/user/background",null)."','POST',{data:(this.result.split(','))[1],id:".$param["id"]."},function(){alert('Success!')});";
        $script.="document.getElementById('head').style['background-image']='url(".GetRealUrl("data/user/".$param["id"]."/background.jpg",null)."?t='+Date.now()+')';";
        $script.="document.getElementById('setting-background').value='';";
        $script.="}};";
        $script.="var intro_editor=".CreateEditor($md,"intro-editor");
        $script.="function submit(){";
        $script.="var md=intro_editor.getMarkdown();";
        $script.="var html=intro_editor.getHTML();";
        $script.="SendAjax('".GetAPIUrl("/user/intro",null)."','POST',{intro:md,id:".$param["id"]."},function(){alert('Success!');";
        $script.="document.getElementById('intro').innerHTML='';editormd.markdownToHTML('intro',{markdown:md});});";
        $script.="}";
    } $script.="open_page(page);";
    $body.=InsertTags("style",null,$style);
    $body.=InsertTags("script",null,$script);
}
