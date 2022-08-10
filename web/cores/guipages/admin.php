<?php
function run(array $param,string &$html,string &$body):void {
    $config=GetConfig(); $lang=GetLanguage()["main"];
    $login_controller=new Login_Controller;
    $uid=$login_controller->CheckLogin();
    $user_controller=new User_Controller;
    if (!$uid) Error_Controller::Common("Permission denied");
    $permission=$user_controller->GetWholeUserInfo($uid)["permission"];
    if ($permission<2) Error_Controller::Common("Permission denied");
    $title=InsertTags("hp",array("style"=>InsertInlineCssStyle(array(
        "padding-left"=>"20px",
        "padding-right"=>"20px",
        "font-size"=>"25px",
        "font-weight"=>"400"
    ))),str_replace("\$\$name\$\$",$config["web"]["name"],$lang["title"])).
    InsertTags("p",array("style"=>InsertInlineCssStyle(array(
        "padding-left"=>"20px",
        "padding-right"=>"20px",
        "margin-top"=>"5px",
    )),"id"=>"hitokoto"),"");
    $body.=InsertTags("div",array("class"=>"default_main","style"=>InsertInlineCssStyle(array(
        "padding-top"=>"20px",
        "margin-bottom"=>"20px",
        "padding-bottom"=>"20px",
        "font-size"=>"14px"
    ))),$title); global $passwd;
    $control_panel=InsertTags("button",array("onclick"=>"location.href='".GetUrl("admin",array("page"=>"system"))."'"),$lang["button"]["system"]);
    $control_panel.=InsertTags("button",array("onclick"=>"location.href='".GetUrl("admin",array("page"=>"problem"))."'"),$lang["button"]["problem"]);
    $control_panel.=InsertTags("button",array("onclick"=>"location.href='".GetUrl("admin",array("page"=>"contest"))."'"),$lang["button"]["contest"]);
    $control_panel.=InsertTags("button",array("onclick"=>"location.href='".GetUrl("admin",array("page"=>"user"))."'"),$lang["button"]["user"]);
    $control_panel.=InsertTags("button",array("onclick"=>"location.href='".GetUrl("config", array("step" => 1, "passwd" => $passwd))."'"),$lang["button"]["config"]);
    $body.=InsertTags("div",array("class"=>"default_main flex","style"=>
    InsertInlineCssStyle(array("padding-left"=>"10px"))),$control_panel);
    switch($param["page"]) {
        case "system": system_run($param,$html,$body);break;
        case "problem": problem_run($param,$html,$body); break;
        case "contest": contest_run($param,$html,$body); break;
        case "user": user_run($param,$html,$body); break;
        // case "bash": control_run($param,$html,$body); break;
        default: system_run($param,$html,$body); break;
    }
    $script="function hitokoto(obj){";
    $script.="if (obj==null) layer.msg('Failed to load hitokoto!');";
    $script.="else {var content=obj['hitokoto']+'    ——'+(obj['from_who']==null?'':obj['from_who'])+'「'+obj['from']+'」';";
    $script.="document.getElementById('hitokoto').innerHTML=content;}};";
    $script.="SendAjax('".$config["hitokoto_link"]."','GET',null,hitokoto,true);";
    $body.=InsertTags("script",null,$script);
}

// function control_run(array $param,string &$html,string &$body):void {

// }

function user_run(array $param,string &$html,string &$body):void {
    if (array_key_exists("id",$param)) user_create($param,$html,$body);
    else user_list($param,$html,$body);
}

function user_create(array $param,string &$html,string &$body):void {
    $lang=GetLanguage()["main"]["user"]["update"];

    $tmp=InsertTags("hp",null,$lang["title"]);
    $tmp.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("margin-top"=>"15px"))),
        $lang["name"].":&nbsp;".InsertSingleTag("input",array("type"=>"text","id"=>"name","placeholder"=>$lang["name-placeholder"])));
    $tmp.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("margin-top"=>"15px"))),
        $lang["email"].":&nbsp;".InsertSingleTag("input",array("type"=>"text","id"=>"email","placeholder"=>$lang["email-placeholder"])));
    $tmp.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("margin-top"=>"15px"))),
        $lang["password"].":&nbsp;".InsertSingleTag("input",array("type"=>"password","id"=>"password","placeholder"=>$lang["password-placeholder"])));
    $tmp.=InsertTags("center",null,InsertTags("button",array("onclick"=>"submit()"),$lang["submit"]));

    $script="function strip_tags_pre(msg){msg=msg.replace(/<(\/)?pre[^>]*>/g,'');return msg;}";
    $script.="function submit(){";
    $script.="var name=document.getElementById(\"name\").value;";
    $script.="var email=document.getElementById(\"email\").value;";
    $script.="var passwd=document.getElementById(\"password\").value;";
    $script.="var public_key=strip_tags_pre(SendAjax('".GetAPIUrl("/login/public")."','GET',null));";
    $script.="if (JSON.parse(public_key)['code']) {layer.msg(JSON.parse(public_key)['message']); return false;}";
    $script.="public_key=JSON.parse(public_key)['data']['key'];";
    $script.="var hash=strip_tags_pre(SendAjax('".GetAPIUrl("/tool/crypt")."','POST',{data:passwd,key:public_key}));";
    $script.="if (JSON.parse(hash)['code']) {layer.msg(JSON.parse(hash)['message']); return false;}";
    $script.="hash=JSON.parse(hash)['data']['result'];";
    $script.="var res=strip_tags_pre(SendAjax('".GetAPIUrl("/user/create",null)."','POST',{email:email,name:name,password:hash}));";
    $script.="if (JSON.parse(res)['code']) {layer.msg(JSON.parse(res)['message']); return false;}";
    $script.="res=JSON.parse(res)['data']['id'];";
    $script.="alert('Success!');";
    $script.="location.href=\"".GetUrl("user",array("id"=>""))."\"+res;}";

    $body.=InsertTags("div",array("class"=>"default_main","style"=>InsertInlineCssStyle(array(
        "margin-top"=>"20px","padding"=>"20px"))),$tmp);
    $body.=InsertTags("script",null,$script);
}

function user_list(array $param,string &$html,string &$body):void {
    $config=GetConfig(); $lang=GetLanguage()["main"]["user"]["list"];
    $name=""; if (array_key_exists("key",$param)) $name=$param["key"];
    $num=1; if (array_key_exists("num",$param)) $num=$param["num"]; $page=$num;
    $l=($config["user_of_pages"]*($num-1)+1);$r=($config["user_of_pages"]*$num);$sum=0;
    $user_controller=new User_Controller;
    $user=$user_controller->SearchUser($name,$l,$r,$sum); $sum=intval(($sum-1)/10+1);
    $search_box=InsertSingleTag("input",array("id"=>"key","placeholder"=>$lang["search-placeholder"],"value"=>$param["key"])).
    InsertTags("button",array("onclick"=>"search()","style"=>InsertInlineCssStyle(array(
        "margin-left"=>"10px",
        "height"=>"33px"
    ))),$lang["search-button"]);
    $body.=InsertTags("div",array("class"=>"default_main flex","style"=>InsertInlineCssStyle(array(
        "padding-left"=>"20px",
        "margin-bottom"=>"20px",
        "padding-right"=>"20px",
        "margin-top"=>"20px"
    ))),$search_box);
    $tmp=InsertTags("p",array("style"=>InsertInlineCssStyle(array("width"=>"3%"))),InsertTags("i",array("class"=>"angle right icon green"),null));
    $tmp.=InsertTags("div",array("style"=>InsertInlineCssStyle(array("width"=>"40%")),"class"=>"ellipsis"),
        InsertTags("a",array(
            "href"=>GetUrl("admin",array("page"=>"user","id"=>0)),
            "class"=>"a"
        ),$lang["create"]));
    $body.=InsertTags("div",array("class"=>"default_main flex","style"=>InsertInlineCssStyle(array(
        "padding"=>"15px 20px 15px 20px"))),$tmp);
    for ($i=0;$i<count($user);$i++) {
        $tmp=""; if ($user[$i]["banned"]) $tmp.=InsertTags("div",array("style"=>InsertInlineCssStyle(array("width"=>"3%","color"=>"red"))),InsertTags("i",array("class"=>"ban icon"),""));
        else if ($user[$i]["email"]=="") $tmp.=InsertTags("div",array("style"=>InsertInlineCssStyle(array("width"=>"3%","color"=>"red"))),InsertTags("i",array("class"=>"user outline icon"),""));
        else if ($user[$i]["verify"]==0) $tmp.=InsertTags("div",array("style"=>InsertInlineCssStyle(array("width"=>"3%","color"=>"red"))),InsertTags("i",array("class"=>"x icon"),""));
        else if ($user[$i]["permission"]<2) $tmp.=InsertTags("div",array("style"=>InsertInlineCssStyle(array("width"=>"3%","color"=>"lightskyblue"))),InsertTags("i",array("class"=>"user icon"),""));
        else $tmp.=InsertTags("div",array("style"=>InsertInlineCssStyle(array("width"=>"3%","color"=>"purple"))),InsertTags("i",array("class"=>"child icon"),""));
        $tmp.=InsertTags("div",array("style"=>InsertInlineCssStyle(array("width"=>"10%"))),InsertTags("a",array("href"=>GetUrl("user",array("id"=>$user[$i]["id"])),"class"=>"a"),"UID: ".$user[$i]["id"]));
        $tmp.=InsertTags("div",array("style"=>InsertInlineCssStyle(array("width"=>"22%"))),InsertTags("a",array("href"=>GetUrl("user",array("id"=>$user[$i]["id"])),"class"=>"a"),$user[$i]["name"]));
        $tmp.=InsertTags("div",array("style"=>InsertInlineCssStyle(array("width"=>"35%"))),($user[$i]["email"]==""?"-":$user[$i]["email"]));
        $tmp.=InsertTags("div",array("style"=>InsertInlineCssStyle(array("width"=>"15%"))),$lang["rating"].": ".$user[$i]["rating"]);
        $tmp.=InsertTags("div",array("style"=>InsertInlineCssStyle(array("width"=>"15%"))),
            InsertTags("a",array("onclick"=>"ban('".$user[$i]["id"]."')"),($user[$i]["banned"]?$lang["unban"]:$lang["ban"]))."&nbsp;|&nbsp;".
            InsertTags("a",array("onclick"=>"delete2('".$user[$i]["id"]."')"),$lang["delete"])."&nbsp;|&nbsp;". 
            InsertTags("a",array("href"=>GetUrl("user",array("id"=>$user[$i]["id"],"page"=>"setting"))),$lang["update"])
        );
        $body.=InsertTags("div",array("class"=>"default_main flex","style"=>InsertInlineCssStyle(array(
            "padding"=>"15px 20px 15px 20px","margin-top"=>"20px"))),$tmp);
    } $content=""; $style=InsertCssStyle(array(".pages"),array(
        "height"=>"30px",
        "line-height"=>"30px",
        "border"=>"1px solid",
        "border-color"=>"rgb(213,216,218)",
        "color"=>"rgb(27,116,221)",
        "margin-top"=>"10px",
        "margin-bottom"=>"10px",
        "padding-left"=>"20px",
        "padding-right"=>"20px",
        "border-radius"=>"3px",
        "font-weight"=>"500",
        "font-size"=>"13px",
        "background-color"=>"white",
        "cursor"=>"pointer",
        "transition"=>"background-color 0.5s,color 0.5s,border-color 0.5s"
    )).InsertCssStyle(array(".banned"),array(
        "color"=>"rgb(137,182,234)"
    )).InsertCssStyle(array(".pages:not(.banned):hover"),array(
        "background-color"=>"rgb(27,116,221)",
        "color"=>"white",
        "border-color"=>"rgb(27,116,221)"
    )); $lang=GetLanguage()["main"]["pages"];
    $content.=InsertTags("div",array("class"=>"pages","onclick"=>"location.href='".GetUrl("admin",array("page"=>"user","num"=>1,"key"=>$param["key"]))."'",
    "style"=>InsertInlineCssStyle(array("margin-right"=>"10px"))),InsertTags("p",null,$lang["top"]));
    if ($page==1) $content.=InsertTags("div",array("class"=>"pages banned"),InsertTags("p",null,$lang["previous"]));
    else $content.=InsertTags("div",array("class"=>"pages","onclick"=>"location.href='".GetUrl("admin",array("page"=>"user","num"=>$page-1,"key"=>$param["key"]))."'"),InsertTags("p",null,$lang["previous"]));
    if ($page==$sum) $content.=InsertTags("div",array("class"=>"pages banned"),InsertTags("p",null,$lang["next"]));
    else $content.=InsertTags("div",array("class"=>"pages","onclick"=>"location.href='".GetUrl("admin",array("page"=>"user","num"=>$page+1,"key"=>$param["key"]))."'"),InsertTags("p",null,$lang["next"]));
    $content.=InsertTags("div",array("class"=>"pages","onclick"=>"location.href='".GetUrl("admin",array("page"=>"user","num"=>$sum,"key"=>$param["key"]))."'",
    "style"=>InsertInlineCssStyle(array("margin-left"=>"10px"))),InsertTags("p",null,$lang["bottom"]));
    $body.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array(
        "justify-content"=>"center",
        "margin-top"=>"20px"
    ))),$content);
    $body.=InsertTags("style",null,$style);
    $script="function search(){";
    $script.="var key=document.getElementById(\"key\").value;";
    $script.="location.href=\"".GetUrl("admin",array("page"=>"user","key"=>""))."\"+key;";
    $script.="} function delete2(id) {";
    $script.="if (confirm('Click \"OK\" to delete the user!')==false) return false;";
    $script.="SendAjax('".GetAPIUrl("/user/delete",null)."','POST',{uid:id},function(){";
    $script.="alert('Success!'); location.href=location.href;},true);";
    $script.="} function ban(id) {";
    $script.="SendAjax('".GetAPIUrl("/user/ban",null)."','POST',{uid:id},function(){";
    $script.="alert('Success!'); location.href=location.href;},true);";
    $script.="} $(\"#key\").keypress(function(event){";
    $script.="var keynum=(event.keyCode?event.keyCode:event.which);  ";
    $script.="if(keynum=='13') search();";
    $script.="});";
    $body.=InsertTags("script",null,$script);
}

function contest_run(array $param,string &$html,string &$body):void {
    if (array_key_exists("num",$param)){contest_list_run($param,$html,$body);return;}
    if (!array_key_exists("id",$param)){$param["num"]=1;contest_list_run($param,$html,$body);return;}
    if ($param["id"]==0){contest_create($param,$html,$body);return;}
    contest_update($param,$html,$body);
}

function contest_list_run(array $param,string &$html,string &$body):void {
    $style=""; $content=""; $lang=GetLanguage()["main"]["contest"]["list"];
    $contest_controller=new Contest_Controller;
    $tags_controller=new Tags_Controller;
    $page=$param["num"]; $config=GetConfig(); $num=$contest_controller->GetContestTotal();
    $pages_num=($num+$config["contest_of_pages"]-1)/$config["contest_of_pages"];
    $pages_num=intval($pages_num);
    if ($page<=0||$page>$pages_num) $page=1; 
    $contest_list=$contest_controller->GetContest
    (($page-1)*$config["contest_of_pages"]+1,$page*$config["contest_of_pages"],false,"starttime DESC");
    $style=InsertCssStyle(array(".contest-item"),array(
        "width"=>"calc(100% - 20px)",
        "min-height"=>"50px",
        "background-color"=>"white",
        "padding-left"=>"20px",
        "margin-top"=>"20px",
        "box-shadow"=>"0 0.375rem 1.375rem rgb(175 194 201 / 50%)",
        "align-items"=>"center"
    )); $style.=InsertCssStyle(array(".contest-tags"),array(
        "background-color"=>"#e67e22",
        "border-radius"=>"100px",
        "height"=>"25px",
        "color"=>"white",
        "padding-left"=>"10px",
        "padding-right"=>"10px",
        "font-size"=>"13px",
        "line-height"=>"25px",
        "margin-right"=>"4px",
        "margin-bottom"=>"4px",
        "width"=>"fit-content",
        "display"=>"inline-block",
        "cursor"=>"pointer"
    )); $tmp=InsertTags("p",array("style"=>InsertInlineCssStyle(array("width"=>"3%"))),InsertTags("i",array("class"=>"angle right icon green"),null));
    $tmp.=InsertTags("div",array("style"=>InsertInlineCssStyle(array("width"=>"40%")),"class"=>"ellipsis"),
        InsertTags("a",array(
            "href"=>GetUrl("admin",array("page"=>"contest","id"=>0)),
            "class"=>"a"
        ),$lang["create"]));
    $body.=InsertTags("div",array(
        "class"=>"contest-item default_main flex",
    ),$tmp);
    for ($i=0;$i<count($contest_list);$i++) {
        $color=""; $word="";
        if (time()<$contest_list[$i]["starttime"]){$color="green";$word=$lang["not-start"];}
        else if (time()<=$contest_list[$i]["starttime"]+$contest_list[$i]["duration"]){$color="blue";$word=$lang["running"];}
        else {$color="red";$word=$lang["finished"];}
        $tmp=InsertTags("p",array("style"=>InsertInlineCssStyle(array(
            "margin-left"=>"1%",
            "width"=>"9%",
            "font-weight"=>"500",
            "color"=>$color,
            "cursor"=>"pointer"
        ))),$word);
        $tmp.=InsertTags("div",array("style"=>InsertInlineCssStyle(array("width"=>"37%")),"class"=>"ellipsis"),
        InsertTags("a",array(
            "href"=>GetUrl("contest",array("id"=>$contest_list[$i]["id"],"page"=>"index")),
            "class"=>"a"
        ),$contest_list[$i]["title"]));
        $starttime=$contest_list[$i]["starttime"]; $endtime=$starttime+$contest_list[$i]["duration"];
        $tags_content=""; $tags=$tags_controller->ListContestTagsById($contest_list[$i]["id"]);
        if ($tags!=null) for ($j=0;$j<count($tags);$j++) $tags_content.=InsertTags("div",array("class"=>"contest-tags"),$tags[$j]["tagname"]);
        $tmp.=InsertTags("div",array("style"=>InsertInlineCssStyle(array("width"=>"20%","padding-top"=>"12.5px","padding-bottom"=>"8.5px"))),$tags_content);
        $tmp.=InsertTags("p",array("style"=>InsertInlineCssStyle(array("width"=>"20%"))),date("m-d H:i",$starttime)." ~ ".date("m-d H:i",$endtime));
        $tmp.=InsertTags("div",array("style"=>InsertInlineCssStyle(array("width"=>"10%"))),
            InsertTags("a",array("onclick"=>"delete2(".$contest_list[$i]["id"].")"),$lang["delete"])."&nbsp;&nbsp;&nbsp;".
            InsertTags("a",array("href"=>GetUrl("admin",array("page"=>"contest","id"=>$contest_list[$i]["id"]))),$lang["update"]));
        $body.=InsertTags("div",array(
            "class"=>"contest-item default_main flex",
        ),$tmp);
    } $style.=InsertCssStyle(array(".pages"),array(
        "height"=>"30px",
        "line-height"=>"30px",
        "border"=>"1px solid",
        "border-color"=>"rgb(213,216,218)",
        "color"=>"rgb(27,116,221)",
        "margin-top"=>"10px",
        "margin-bottom"=>"10px",
        "padding-left"=>"20px",
        "padding-right"=>"20px",
        "border-radius"=>"3px",
        "font-weight"=>"500",
        "font-size"=>"13px",
        "background-color"=>"white",
        "cursor"=>"pointer",
        "transition"=>"background-color 0.5s,color 0.5s,border-color 0.5s"
    )).InsertCssStyle(array(".banned"),array(
        "color"=>"rgb(137,182,234)"
    )).InsertCssStyle(array(".pages:not(.banned):hover"),array(
        "background-color"=>"rgb(27,116,221)",
        "color"=>"white",
        "border-color"=>"rgb(27,116,221)"
    )); $lang=GetLanguage()["main"]["pages"];
    $content.=InsertTags("div",array("class"=>"pages","onclick"=>"location.href='".GetUrl("admin",array("page"=>"contest","num"=>1))."'",
    "style"=>InsertInlineCssStyle(array("margin-right"=>"10px"))),InsertTags("p",null,$lang["top"]));
    if ($page==1) $content.=InsertTags("div",array("class"=>"pages banned"),InsertTags("p",null,$lang["previous"]));
    else $content.=InsertTags("div",array("class"=>"pages","onclick"=>"location.href='".GetUrl("admin",array("page"=>"contest","num"=>$page-1))."'"),InsertTags("p",null,$lang["previous"]));
    if ($page==$pages_num) $content.=InsertTags("div",array("class"=>"pages banned"),InsertTags("p",null,$lang["next"]));
    else $content.=InsertTags("div",array("class"=>"pages","onclick"=>"location.href='".GetUrl("admin",array("page"=>"contest","num"=>$page+1))."'"),InsertTags("p",null,$lang["next"]));
    $content.=InsertTags("div",array("class"=>"pages","onclick"=>"location.href='".GetUrl("admin",array("page"=>"contest","num"=>$pages_num))."'",
    "style"=>InsertInlineCssStyle(array("margin-left"=>"10px"))),InsertTags("p",null,$lang["bottom"]));
    $body.=InsertTags("style",null,$style);
    $body.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array(
        "justify-content"=>"center","margin-top"=>"20px"
    ))),$content);
    $script="function delete2(id){";
    $script.="if (confirm('Click \"OK\" to delete the contest!')==false) return false;";
    $script.="SendAjax('".GetAPIUrl("/contest/delete",null)."','POST',{id:id}";
    $script.=",function(){alert('Success!');location.href=location.href;},true)}";
    $body.=InsertTags("script",null,$script);
}

function contest_create(array $param,string &$html,string &$body):void {
    $lang=GetLanguage()["main"]["contest"]["update"];

    $tmp=InsertTags("hp",null,$lang["title"]);
    $tmp.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("margin-top"=>"15px"))),
        $lang["name"].":&nbsp;".InsertSingleTag("input",array("type"=>"text","id"=>"name","placeholder"=>$lang["name-placeholder"])));
    $tmp.=InsertTags("div",array("id"=>"intro","style"=>InsertInlineCssStyle(array("margin-top"=>"15px"))),null);
    $tmp.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("margin-top"=>"15px"))),
        $lang["starttime"].":&nbsp;".InsertSingleTag("input",array("type"=>"text","id"=>"starttime","value"=>date("Y-m-d H:i:s",time()),"placeholder"=>str_replace("\$\$time\$\$",date("Y-m-d H:i:s",time()),$lang["starttime-placeholder"]))));
    $tmp.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("margin-top"=>"15px"))),
        $lang["endtime"].":&nbsp;".InsertSingleTag("input",array("type"=>"text","id"=>"endtime","value"=>date("Y-m-d H:i:s",time()+24*3600),"placeholder"=>str_replace("\$\$time\$\$",date("Y-m-d H:i:s",time()+3600*24),$lang["endtime-placeholder"]))));
    $tmp.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("margin-top"=>"15px"))),
        $lang["problem"].":&nbsp;".InsertSingleTag("input",array("type"=>"text","id"=>"problem","placeholder"=>$lang["problem-placeholder"])));
    $tmp.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("margin-top"=>"15px"))),
        $lang["tags"].":&nbsp;".InsertSingleTag("input",array("type"=>"text","id"=>"tags","placeholder"=>$lang["tags-placeholder"])));
    $tmp.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("margin-top"=>"15px"))),
        $lang["lang"].":&nbsp;".InsertSingleTag("input",array("type"=>"text","id"=>"lang2","placeholder"=>$lang["lang-placeholder"])));
    $options=InsertTags("option",array("value"=>0),$lang["type-oi"]);
    $options.=InsertTags("option",array("value"=>1),$lang["type-ioi"]);
    $options.=InsertTags("option",array("value"=>2),$lang["type-acm"]);
    $tmp.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("margin-top"=>"15px"))),
    $lang["type"].":&nbsp;".InsertTags("select",array("id"=>"type","style"=>InsertInlineCssStyle(array(
        "width"=>"10px",
        "flex-grow"=>"1000",
        "height"=>"30px",
        "padding-left"=>"10px",
        "outline"=>"none",
        "border"=>"rgb(221,221,221) 1px solid",
        "background-color"=>"rgb(249,255,204)",
        "border-radius"=>"3px",
        "padding-right"=>"10px"
    ))),$options));
    $options=InsertTags("option",array("value"=>0),$lang["rated-unrated"]);
    $options.=InsertTags("option",array("value"=>1),$lang["rated-rated"]);
    $tmp.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("margin-top"=>"15px"))),
    $lang["rated"].":&nbsp;".InsertTags("select",array("id"=>"rated","style"=>InsertInlineCssStyle(array(
        "width"=>"10px",
        "flex-grow"=>"1000",
        "height"=>"30px",
        "padding-left"=>"10px",
        "outline"=>"none",
        "border"=>"rgb(221,221,221) 1px solid",
        "background-color"=>"rgb(249,255,204)",
        "border-radius"=>"3px",
        "padding-right"=>"10px"
    ))),$options));
    $tmp.=InsertTags("center",null,InsertTags("button",array("onclick"=>"submit()"),$lang["submit"]));
    $body.=InsertTags("div",array("class"=>"default_main","style"=>InsertInlineCssStyle(array(
        "margin-top"=>"20px","padding"=>"20px"))),$tmp);
    $script="var editor=".CreateEditor("","intro",",height:'400px'");
    $script.="function getTime(x){return (new Date(x).getTime())/1000;}";
    $script.="function strip_tags_pre(msg){msg=msg.replace(/<(\/)?pre[^>]*>/g,'');return msg;}";
    $script.="function submit(){";
    $script.="var title=document.getElementById('name').value;";
    $script.="var intro=editor.getMarkdown();";
    $script.="var starttime=getTime(document.getElementById('starttime').value);";
    $script.="var endtime=getTime(document.getElementById('endtime').value);";
    $script.="var duration=endtime-starttime;";
    $script.="var problem=document.getElementById('problem').value;";
    $script.="var tags=document.getElementById('tags').value;";
    $script.="var lang=document.getElementById('lang2').value;";
    $script.="var type=document.getElementById('type').value;";
    $script.="var rated=document.getElementById('rated').value;";
    $script.="SendAjax('".GetAPIUrl("/contest/create")."','POST',{tags:tags,lang:lang,";
    $script.="title:title,intro:intro,starttime:starttime,duration:duration,problem:problem,type:type,rated:rated},";
    $script.="function(res){alert(\"Create Successfully! Your contest id: \"+JSON.parse(strip_tags_pre(res))['data']['id']);";
    $script.="location.href='".GetUrl("admin",array("page"=>"contest"))."&id='+JSON.parse(strip_tags_pre(res))['data']['id'];},true)}";
    $body.=InsertTags("script",null,$script);
}

function contest_update(array $param,string &$html,string &$body):void {
    $contest_controller=new Contest_Controller;
    $problem_controller=new Problem_Controller;
    Contest_Controller::JudgeContestExist($param["id"]);
    $info=$contest_controller->GetContest($param["id"],$param["id"])[0];
    $lang=GetLanguage()["main"]["contest"]["update"];

    // print_r($info); exit;
    $tmp=InsertTags("hp",null,$lang["title"]);
    $tmp.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("margin-top"=>"15px"))),
        $lang["name"].":&nbsp;".InsertSingleTag("input",array("type"=>"text","id"=>"name","placeholder"=>$lang["name-placeholder"],"value"=>$info["title"])));
    $tmp.=InsertTags("div",array("id"=>"intro","style"=>InsertInlineCssStyle(array("margin-top"=>"15px"))),null);
    $tmp.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("margin-top"=>"15px"))),
        $lang["starttime"].":&nbsp;".InsertSingleTag("input",array("type"=>"text","id"=>"starttime","value"=>date("Y-m-d H:i:s",$info["starttime"]),"placeholder"=>str_replace("\$\$time\$\$",date("Y-m-d H:i:s",time()),$lang["starttime-placeholder"]))));
    $tmp.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("margin-top"=>"15px"))),
        $lang["endtime"].":&nbsp;".InsertSingleTag("input",array("type"=>"text","id"=>"endtime","value"=>date("Y-m-d H:i:s",$info["starttime"]+$info["duration"]),"placeholder"=>str_replace("\$\$time\$\$",date("Y-m-d H:i:s",time()+3600*24),$lang["endtime-placeholder"]))));
    $problem=""; for ($i=0;$i<count($info["problem"]);$i++) {
        $id=$problem_controller->GetPidById($info["problem"][$i]);
        if ($id===false) continue;
        $problem.=$id.",";
    } $tmp.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("margin-top"=>"15px"))),
        $lang["problem"].":&nbsp;".InsertSingleTag("input",array("type"=>"text","id"=>"problem","placeholder"=>$lang["problem-placeholder"],"value"=>$problem)));
    
    $tags=""; for ($i=0;$i<count($info["tags"]);$i++) $tags.=$info["tags"][$i].",";
    $tmp.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("margin-top"=>"15px"))),
        $lang["tags"].":&nbsp;".InsertSingleTag("input",array("type"=>"text","id"=>"tags","placeholder"=>$lang["tags-placeholder"],"value"=>$tags)));
    $tmp.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("margin-top"=>"15px"))),
        $lang["lang"].":&nbsp;".InsertSingleTag("input",array("type"=>"text","id"=>"lang2","placeholder"=>$lang["lang-placeholder"],"value"=>$info["lang"])));
    $options=InsertTags("option",array("value"=>0,"id"=>"type0"),$lang["type-oi"]);
    $options.=InsertTags("option",array("value"=>1,"id"=>"type1"),$lang["type-ioi"]);
    $options.=InsertTags("option",array("value"=>2,"id"=>"type2"),$lang["type-acm"]);
    $md=$info["intro"];
    $tmp.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("margin-top"=>"15px"))),
    $lang["type"].":&nbsp;".InsertTags("select",array("id"=>"type","style"=>InsertInlineCssStyle(array(
        "width"=>"10px",
        "flex-grow"=>"1000",
        "height"=>"30px",
        "padding-left"=>"10px",
        "outline"=>"none",
        "border"=>"rgb(221,221,221) 1px solid",
        "background-color"=>"rgb(249,255,204)",
        "border-radius"=>"3px",
        "padding-right"=>"10px"
    ))),$options));
    $options=InsertTags("option",array("value"=>0,"id"=>"rated0"),$lang["rated-unrated"]);
    $options.=InsertTags("option",array("value"=>1,"id"=>"rated1"),$lang["rated-rated"]);
    $tmp.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("margin-top"=>"15px"))),
    $lang["rated"].":&nbsp;".InsertTags("select",array("id"=>"rated","style"=>InsertInlineCssStyle(array(
        "width"=>"10px",
        "flex-grow"=>"1000",
        "height"=>"30px",
        "padding-left"=>"10px",
        "outline"=>"none",
        "border"=>"rgb(221,221,221) 1px solid",
        "background-color"=>"rgb(249,255,204)",
        "border-radius"=>"3px",
        "padding-right"=>"10px"
    ))),$options));
    $tmp.=InsertTags("center",null,InsertTags("button",array("onclick"=>"submit()"),$lang["submit"]));
    $body.=InsertTags("div",array("class"=>"default_main","style"=>InsertInlineCssStyle(array(
        "margin-top"=>"20px","padding"=>"20px"))),$tmp);
    $script="var editor=".CreateEditor($md,"intro",",height:'400px'");
    $script.="function getTime(x){return (new Date(x).getTime())/1000;}";
    $script.="function strip_tags_pre(msg){msg=msg.replace(/<(\/)?pre[^>]*>/g,'');return msg;}";
    $script.="function submit(){";
    $script.="var title=document.getElementById('name').value;";
    $script.="var intro=editor.getMarkdown();";
    $script.="var starttime=getTime(document.getElementById('starttime').value);";
    $script.="var endtime=getTime(document.getElementById('endtime').value);";
    $script.="var duration=endtime-starttime;";
    $script.="var problem=document.getElementById('problem').value;";
    $script.="var tags=document.getElementById('tags').value;";
    $script.="var lang=document.getElementById('lang2').value;";
    $script.="var type=document.getElementById('type').value;";
    $script.="var rated=document.getElementById('rated').value;";
    $script.="SendAjax('".GetAPIUrl("/contest/update")."','POST',{id:".$param["id"].",tags:tags,lang:lang,";
    $script.="title:title,intro:intro,starttime:starttime,duration:duration,problem:problem,type:type,rated:rated},";
    $script.="function(res){alert(\"Update Successfully!\");},true)}";
    $script.="document.getElementById('type'+".$info["type"].").setAttribute('selected','selected');";
    $script.="document.getElementById('rated'+".$info["rated"].").setAttribute('selected','selected');";
    $body.=InsertTags("script",null,$script);
}

function problem_list_run(array $param,string &$html,string &$body):void {
    $L=1;$R=intval(1e18);
    $lang=GetLanguage()["main"]["problem"]["list"];
    if (!array_key_exists("key",$param)) $_GET["key"]="";
    if (!array_key_exists("tag",$param)) $_GET["tag"]="";
    if (!array_key_exists("diff",$param)) $_GET["diff"]="";
    if (array_key_exists("l",$param)) $L=intval($param["l"]);
    if (array_key_exists("r",$param)) $R=intval($param["r"]);
    $t=explode(",",$_GET["tag"]);array_splice($t,count($t)-1,1);
    $d=explode(",",$_GET["diff"]);array_splice($d,count($d)-1,1);
    $problem_controller=new Problem_Controller;
    $tags_controller=new Tags_Controller;
    $page=$param["num"]; $config=GetConfig(); $num=0;
    $problem_list=$problem_controller->ListProblemByNumber(
        ($page-1)*$config["problem_of_pages"]+1,
        $page*$config["problem_of_pages"],
        $_GET["key"],$t,$d,$L,$R,$num
    ); $pages_num=($num+$config["problem_of_pages"]-1)/$config["problem_of_pages"];
    $pages_num=intval($pages_num);
    if ($page<=0||$page>$pages_num) $page=1; 
    $problem_list=$problem_controller->ListProblemByNumber(
        ($page-1)*$config["problem_of_pages"]+1,
        $page*$config["problem_of_pages"],
        $_GET["key"],$t,$d,$L,$R,$num
    ); $search_box=InsertSingleTag("input",array("id"=>"key","placeholder"=>$lang["search-placeholder"],"value"=>$param["key"])).
    InsertTags("button",array("onclick"=>"search()","style"=>InsertInlineCssStyle(array(
        "margin-left"=>"10px",
        "height"=>"33px"
    ))),$lang["search-button"]);
    $search_box=InsertTags("div",array("class"=>"flex"),$search_box);
    $tags=$tags_controller->ListProblemTag();
    $tag_box=""; for ($i=0;$i<count($tags);$i++)
        $tag_box.=InsertTags("div",array("class"=>"problem-tags".(array_search($tags[$i],$t)===false?" unsubmitted":"")
        ,"id"=>"tag-".$tags[$i],"onclick"=>"addTag('".$tags[$i]."')"),$tags[$i]);
    $search_box.=InsertTags("div",null,InsertTags("p",array("style"=>InsertInlineCssStyle(array(
        "display"=>"inline-block"
    ))),$lang["tags-filter"].":&nbsp;&nbsp;").$tag_box); $tag_box="";
    for ($i=0;$i<count($config["difficulties"]);$i++)
        $tag_box.=InsertTags("div",array("class"=>"problem-difficulties-$i".(array_search($i,$d)===false?" unsubmitted":""),"id"=>"difficulties-$i",
        "onclick"=>"addDiff($i)"),$config["difficulties"][$i]["name"]);
    $search_box.=InsertTags("div",null,InsertTags("p",array("style"=>InsertInlineCssStyle(array(
        "display"=>"inline-block"
    ))),$lang["difficult-filter"].":&nbsp;&nbsp;").$tag_box);
    $body.=InsertTags("div",array("class"=>"default_main","style"=>InsertInlineCssStyle(array(
        "padding-left"=>"20px",
        "margin-bottom"=>"20px",
        "padding-bottom"=>"15px",
        "padding-right"=>"20px",
        "margin-top"=>"20px"
    ))),$search_box); $style=InsertCssStyle(array(".problem-item"),array(
        "min-height"=>"50px",
        "background-color"=>"white",
        "padding-left"=>"20px",
        "margin-bottom"=>"20px",
        "align-items"=>"center"
    )); for ($i=0;$i<count($config["difficulties"]);$i++) {
        $style.=InsertCssStyle(array(".problem-difficulties-$i"),array(
            "background-color"=>$config["difficulties"][$i]["color"],
            "border-radius"=>"100px",
            "height"=>"25px",
            "color"=>"white",
            "padding-left"=>"10px",
            "padding-right"=>"10px",
            "font-size"=>"13px",
            "line-height"=>"25px",
            "margin-right"=>"5px",
            "width"=>"fit-content",
            "display"=>"inline-block",
            "cursor"=>"pointer"
        ));
    } $style.=InsertCssStyle(array(".problem-tags"),array(
        "background-color"=>"rgb(41,73,180)",
        "border-radius"=>"100px",
        "height"=>"25px",
        "color"=>"white",
        "padding-left"=>"10px",
        "padding-right"=>"10px",
        "font-size"=>"13px",
        "line-height"=>"25px",
        "margin-right"=>"4px",
        "margin-bottom"=>"4px",
        "width"=>"fit-content",
        "display"=>"inline-block",
        "cursor"=>"pointer"
    )); $style.=InsertCssStyle(array(".gray"),array(
        "color"=>"orange"
    )); $style.=InsertCssStyle(array(".red"),array(
        "color"=>"red"
    )); $style.=InsertCssStyle(array(".unsubmitted"),array(
        "background-color"=>"rgb(210,210,210)"
    )); $tmp=InsertTags("p",array("style"=>InsertInlineCssStyle(array("width"=>"3%"))),InsertTags("i",array("class"=>"angle right icon green"),null));
    $tmp.=InsertTags("div",array("style"=>InsertInlineCssStyle(array("width"=>"40%")),"class"=>"ellipsis"),
        InsertTags("a",array(
            "href"=>GetUrl("admin",array("page"=>"problem","id"=>0)),
            "class"=>"a"
        ),$lang["create"]));
    $body.=InsertTags("div",array(
        "class"=>"problem-item default_main flex",
    ),$tmp);
    for ($i=0;$i<count($problem_list);$i++) {
        if ($problem_list[$i]["hidden"]==0) $tmp=InsertTags("p",array("style"=>InsertInlineCssStyle(array("width"=>"3%","cursor"=>"pointer"))),InsertTags("i",array("class"=>"eye icon gray"),null));
        else $tmp=InsertTags("p",array("style"=>InsertInlineCssStyle(array("width"=>"3%"))),InsertTags("i",array("class"=>"ban icon red"),null));
        $tmp.=InsertTags("div",array("style"=>InsertInlineCssStyle(array("width"=>"7%"))),
        InsertTags("a",array("href"=>GetUrl("problem",array("id"=>$problem_list[$i]["id"])),"class"=>"a"),"P".$problem_list[$i]["pid"]));
        $tmp.=InsertTags("div",array("style"=>InsertInlineCssStyle(array("width"=>"35%")),"class"=>"ellipsis"),
            InsertTags("a",array(
                "href"=>GetUrl("problem",array("id"=>$problem_list[$i]["id"])),
                "class"=>"a"
            ),$problem_list[$i]["name"])); $tags_content="";
        $tags=$tags_controller->ListProblemTagsByPid($problem_list[$i]["id"]);
        if ($tags!=null) for ($j=0;$j<count($tags);$j++) $tags_content.=InsertTags("div",array("class"=>"problem-tags","onclick"=>"searchTag('".$tags[$j]."')"),$tags[$j]);
        $tmp.=InsertTags("div",array("style"=>InsertInlineCssStyle(array("width"=>"20%","padding-top"=>"12.5px","padding-bottom"=>"8.5px"))),$tags_content);
        $tmp.=InsertTags("div",array("style"=>InsertInlineCssStyle(array("width"=>"15%","text-align"=>"center"))),
        InsertTags("div",array("class"=>"problem-difficulties-".$problem_list[$i]["difficult"],"style"=>InsertInlineCssStyle(array(
        "margin"=>"auto")),"onclick"=>"searchDiff('".$problem_list[$i]["difficult"]."')"),$config["difficulties"][$problem_list[$i]["difficult"]]["name"]));
        $tmp.=InsertTags("div",array("style"=>InsertInlineCssStyle(array("width"=>"20%"))),
            InsertTags("a",array("onclick"=>"hidden2(".$problem_list[$i]["id"].")"),($problem_list[$i]["hidden"]==0?$lang["hidden"]:$lang["show"]))."&nbsp;|&nbsp;".
            InsertTags("a",array("onclick"=>"rejudge(".$problem_list[$i]["id"].")"),$lang["rejudge"])."&nbsp;|&nbsp;".
            InsertTags("a",array("onclick"=>"delete2(".$problem_list[$i]["id"].")"),$lang["delete"])."&nbsp;|&nbsp;".
            InsertTags("a",array("href"=>GetUrl("admin",array("page"=>"problem","id"=>$problem_list[$i]["id"]))),$lang["update"]));
        $body.=InsertTags("div",array(
            "class"=>"problem-item default_main flex",
        ),$tmp);
    } $content=""; $style.=InsertCssStyle(array(".pages"),array(
        "height"=>"30px",
        "line-height"=>"30px",
        "border"=>"1px solid",
        "border-color"=>"rgb(213,216,218)",
        "color"=>"rgb(27,116,221)",
        "margin-top"=>"10px",
        "margin-bottom"=>"10px",
        "padding-left"=>"20px",
        "padding-right"=>"20px",
        "border-radius"=>"3px",
        "font-weight"=>"500",
        "font-size"=>"13px",
        "background-color"=>"white",
        "cursor"=>"pointer",
        "transition"=>"background-color 0.5s,color 0.5s,border-color 0.5s"
    )).InsertCssStyle(array(".banned"),array(
        "color"=>"rgb(137,182,234)"
    )).InsertCssStyle(array(".pages:not(.banned):hover"),array(
        "background-color"=>"rgb(27,116,221)",
        "color"=>"white",
        "border-color"=>"rgb(27,116,221)"
    )); $lang=GetLanguage()["main"]["pages"];
    $content.=InsertTags("div",array("class"=>"pages","onclick"=>"location.href='".GetUrl("admin",array("page"=>"problem","num"=>1,"l"=>$L,"r"=>$R,"key"=>$param["key"],"tag"=>$param["tag"],"diff"=>$param["diff"]))."'",
    "style"=>InsertInlineCssStyle(array("margin-right"=>"10px"))),InsertTags("p",null,$lang["top"]));
    if ($page==1) $content.=InsertTags("div",array("class"=>"pages banned"),InsertTags("p",null,$lang["previous"]));
    else $content.=InsertTags("div",array("class"=>"pages","onclick"=>"location.href='".GetUrl("admin",array("page"=>"problem","num"=>$page-1,"l"=>$L,"r"=>$R,"key"=>$param["key"],"tag"=>$param["tag"],"diff"=>$param["diff"]))."'"),InsertTags("p",null,$lang["previous"]));
    if ($page==$pages_num) $content.=InsertTags("div",array("class"=>"pages banned"),InsertTags("p",null,$lang["next"]));
    else $content.=InsertTags("div",array("class"=>"pages","onclick"=>"location.href='".GetUrl("admin",array("page"=>"problem","num"=>$page+1,"l"=>$L,"r"=>$R,"key"=>$param["key"],"tag"=>$param["tag"],"diff"=>$param["diff"]))."'"),InsertTags("p",null,$lang["next"]));
    $content.=InsertTags("div",array("class"=>"pages","onclick"=>"location.href='".GetUrl("admin",array("page"=>"problem","num"=>$pages_num,"l"=>$L,"r"=>$R,"key"=>$param["key"],"tag"=>$param["tag"],"diff"=>$param["diff"]))."'",
    "style"=>InsertInlineCssStyle(array("margin-left"=>"10px"))),InsertTags("p",null,$lang["bottom"]));
    $body.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array(
        "justify-content"=>"center"
    ))),$content);
    $body.=InsertTags("style",null,$style);
    $script="var tag=new Array,diff=new Array;";
    for ($i=0;$i<count($t);$i++) $script.="tag.push('".$t[$i]."');";
    for ($i=0;$i<count($d);$i++) $script.="diff.push(".$d[$i].");";
    $script.="function searchTag(name) {";
    $script.="tag=new Array; diff=new Array;";
    $script.="addTag(name); search();}";
    $script.="function searchDiff(name) {";
    $script.="tag=new Array; diff=new Array;";
    $script.="addDiff(name); search();}";
    $script.="function addTag(name) {";
    $script.="if (!tag.includes(name)) {document.getElementById('tag-'+name).classList.remove('unsubmitted');tag.push(name);}";
    $script.="else {document.getElementById('tag-'+name).classList.add('unsubmitted');tag=tag.filter(function(item){return item!=name});}";
    $script.="} function addDiff(id) {";
    $script.="if (!diff.includes(id)) {document.getElementById('difficulties-'+id).classList.remove('unsubmitted');diff.push(id);}";
    $script.="else {document.getElementById('difficulties-'+id).classList.add('unsubmitted');diff=diff.filter(function(item){return item!=id});}";
    $script.="} function search() {";
    $script.="var url='".GetUrl("admin",array("page"=>"problem","num"=>1,"l"=>$L,"r"=>$R))."&key='+encodeURIComponent(document.getElementById('key').value);";
    $script.="url+='&tag='; for (i=0;i<tag.length;i++) url+=encodeURIComponent(tag[i]+',');";
    $script.="url+='&diff='; for (i=0;i<diff.length;i++) url+=encodeURIComponent(diff[i]+',');";
    $script.="window.location.href=url;";
    $script.="} function hidden2(id) {"; 
    $script.="SendAjax('".GetAPIUrl("/problem/hidden",null)."','POST',{pid:id},function(){";
    $script.="alert('Success!'); location.href=location.href;},true);";
    $script.="} function rejudge(id) {";
    $script.="SendAjax('".GetAPIUrl("/problem/rejudge",null)."','POST',{pid:id},function(){";
    $script.="alert('Success!'); location.href=location.href;},true);";
    $script.="} function delete2(id) {";
    $script.="if (confirm('Click \"OK\" to delete the problem!')==false) return false;";
    $script.="SendAjax('".GetAPIUrl("/problem/delete",null)."','POST',{pid:id},function(){";
    $script.="alert('Success!'); location.href=location.href;},true);";
    $script.="} $(\"#key\").keypress(function(event){";
    $script.="var keynum=(event.keyCode?event.keyCode:event.which);  ";
    $script.="if(keynum=='13') search();";
    $script.="});";
    $body.=InsertTags("script",null,$script);
}

function problem_create(array $param,string &$html,string &$body):void {
    $contest_controller=new Contest_Controller;
    $style=InsertCssStyle(array("input[type='file']"),array(
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
    ); $style.=InsertCssStyle(array("textarea"),array(
        "width"=>"calc( 100% - 16.8px )",
        "height"=>"100px",
        "resize"=>"vertical",
        "padding"=>"8.4px 10px",
        "outline"=>"none",
        "border"=>"rgb(221,221,221) solid 1px",
        "background-color"=>"rgb(249,255,204)",
    )); $lang=GetLanguage()["main"]["problem"]["update"];

    $tmp=InsertTags("hp",null,$lang["info"]);
    $tmp.=InsertTags("div",array("style"=>InsertInlineCssStyle(array("margin-top"=>"15px","display"=>"flex","align-items"=>"center"))),InsertTags("p",null,$lang["upload-data"].":&nbsp;").
        InsertTags("div",array("class"=>"file"),InsertTags("label",array("for"=>"data","class"=>"label"),$lang["choose-data"])).
        InsertSingleTag("input",array("accept"=>".zip","type"=>"file","id"=>"data","name"=>"data")).
        InsertTags("div",array("id"=>"dataname"),""));
    $tmp.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("margin-top"=>"15px"))),
        $lang["name"].":&nbsp;".InsertSingleTag("input",array("type"=>"text","id"=>"name","placeholder"=>$lang["name-placeholder"])));
    $tmp.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("margin-top"=>"15px"))),
        $lang["pid"].":&nbsp;".InsertSingleTag("input",array("type"=>"text","id"=>"pid","placeholder"=>$lang["pid-placeholder"])));
    $body.=InsertTags("div",array("class"=>"default_main","style"=>InsertInlineCssStyle(array(
        "margin-top"=>"20px","padding"=>"20px"))),$tmp);

    $tmp=InsertTags("hp",null,$lang["background"]);
    $tmp.=InsertTags("div",array("id"=>"bg","style"=>InsertInlineCssStyle(array("margin-top"=>"15px"))),null);
    $body.=InsertTags("div",array("class"=>"default_main","style"=>InsertInlineCssStyle(array(
        "margin-top"=>"20px","padding"=>"20px"))),$tmp);

    $tmp=InsertTags("hp",null,$lang["description"]);
    $tmp.=InsertTags("div",array("id"=>"descrip","style"=>InsertInlineCssStyle(array("margin-top"=>"15px"))),null);
    $body.=InsertTags("div",array("class"=>"default_main","style"=>InsertInlineCssStyle(array(
        "margin-top"=>"20px","padding"=>"20px"))),$tmp);

    $tmp=InsertTags("hp",null,$lang["io-information"]);
    $tmp.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("margin-top"=>"15px"))),
        $lang["input-file"].":&nbsp;".InsertSingleTag("input",array("type"=>"text","id"=>"input-file","placeholder"=>$lang["input-file-placeholder"])));
    $tmp.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("margin-top"=>"15px"))),
        $lang["output-file"].":&nbsp;".InsertSingleTag("input",array("type"=>"text","id"=>"output-file","placeholder"=>$lang["output-file-placeholder"])));
    $body.=InsertTags("div",array("class"=>"default_main","style"=>InsertInlineCssStyle(array(
        "margin-top"=>"20px","padding"=>"20px"))),$tmp);

    $tmp=InsertTags("hp",null,$lang["input"]);
    $tmp.=InsertTags("div",array("id"=>"input","style"=>InsertInlineCssStyle(array("margin-top"=>"15px"))),null);
    $body.=InsertTags("div",array("class"=>"default_main","style"=>InsertInlineCssStyle(array(
        "margin-top"=>"20px","padding"=>"20px"))),$tmp);
    $tmp=InsertTags("hp",null,$lang["output"]);
    $tmp.=InsertTags("div",array("id"=>"output","style"=>InsertInlineCssStyle(array("margin-top"=>"15px"))),null);
    $body.=InsertTags("div",array("class"=>"default_main","style"=>InsertInlineCssStyle(array(
        "margin-top"=>"20px","padding"=>"20px"))),$tmp);

    $tmp=InsertTags("hp",null,$lang["sample"]);
    $tmp.=InsertTags("div",array("id"=>"cases"),"");
    $tmp.=InsertTags("center",null,InsertTags("button",array("onclick"=>"appendCase()"),$lang["append"]));
    $body.=InsertTags("div",array("class"=>"default_main","style"=>InsertInlineCssStyle(array(
        "margin-top"=>"20px","padding"=>"20px"))),$tmp);

    $tmp=InsertTags("hp",null,$lang["hint"]);
    $tmp.=InsertTags("div",array("id"=>"hint","style"=>InsertInlineCssStyle(array("margin-top"=>"15px"))),null);
    $body.=InsertTags("div",array("class"=>"default_main","style"=>InsertInlineCssStyle(array(
        "margin-top"=>"20px","padding"=>"20px"))),$tmp);

    $tmp=InsertTags("hp",null,$lang["others"]);
    $tmp.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("margin-top"=>"15px"))),
        $lang["tags"].":&nbsp;".InsertSingleTag("input",array("type"=>"text","id"=>"tags","placeholder"=>$lang["tags-placeholder"])));
    $config=GetConfig();
    $options=InsertTags("option",array("value"=>"0"),$config["difficulties"][0]["name"]); for ($i=1;$i<count($config["difficulties"]);$i++) 
        $options.=InsertTags("option",array("value"=>$i),$config["difficulties"][$i]["name"]);
    $tmp.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("margin-top"=>"15px"))),
        $lang["difficulty"].":&nbsp;".InsertTags("select",array("id"=>"difficulty","style"=>InsertInlineCssStyle(array(
        "width"=>"10px",
        "flex-grow"=>"1000",
        "height"=>"30px",
        "padding-left"=>"10px",
        "outline"=>"none",
        "border"=>"rgb(221,221,221) 1px solid",
        "background-color"=>"rgb(249,255,204)",
        "border-radius"=>"3px",
        "padding-right"=>"10px"
    ))),$options));
    $tmp.=InsertTags("center",null,InsertTags("button",array("onclick"=>"submit()"),$lang["submit"]));
    $body.=InsertTags("div",array("class"=>"default_main","style"=>InsertInlineCssStyle(array(
        "margin-top"=>"20px","padding"=>"20px"))),$tmp);
    
    $script="var bg=".CreateEditor("","bg",",height:'400px'");
    $script.="var descrip=".CreateEditor("","descrip",",height:'400px'");
    $script.="var input=".CreateEditor("","input",",height:'400px'");
    $script.="var output=".CreateEditor("","output",",height:'400px'");
    $script.="var hint=".CreateEditor("","hint",",height:'400px'");
    $script.="var cs=0;appendCase();function appendCase() {cs++;var content=\"<div id='case\"+cs+\"' style='display:flex;display:-webkit-flex;margin-top:15px'>\"+";
    $script.="\"<div style='flex-grow:1000'><p>".$lang["sample-input"]." #\"+cs+\": </p><textarea id='case\"+cs+\"-input'></textarea></div>\"+";
    $script.="\"<div style='flex-grow:1000;margin-left:20px'><p>".$lang["sample-output"]." #\"+cs+\": </p><textarea id='case\"+cs+\"-output'></textarea></div>\"+";
    $script.="\"</div>\";$(\"#cases\").append(content);} function getCases(){";
    $script.="var obj=new Array;for (var i=1;i<=cs;i++) {var objnow=new Object;";
    $script.="objnow={input:document.getElementById(\"case\"+i+\"-input\").value,";
    $script.="output:document.getElementById(\"case\"+i+\"-output\").value};";
    $script.="if (objnow[\"input\"]==\"\"&&objnow[\"output\"]==\"\") continue;";
    $script.="obj.push(objnow);}return JSON.stringify(obj);}";
    $script.="function readFile(id){var input=document.getElementById(id);if (input.value==''){alert(\"Data Package is not uploaded!\");return false;}";
    $script.="if (typeof(FileReader)==='undefined'){alert(\"Your browser is too old, sorry!\");";
    $script.="input.setAttribute('disabled','disabled');} var reader=new FileReader();";
    $script.="reader.readAsDataURL(input.files[0]);reader.onload=function(){getFormData(this.result);}}";
    $script.="function submit(){readFile(\"data\");}function strip_tags_pre(msg){msg=msg.replace(/<(\/)?pre[^>]*>/g,'');return msg;}";
    $script.="function getFormData(content) {var Form={\"file\":(content.split(\",\"))[1],\"ppid\":document.getElementById(\"pid\").value,\"title\":document.getElementById(\"name\").value,";
    $script.="\"background\":bg.getMarkdown(),\"description\":descrip.getMarkdown(),\"input-file\":document.getElementById(\"input-file\").value,";
    $script.="\"output-file\":document.getElementById(\"output-file\").value,\"input\":input.getMarkdown(),\"output\":output.getMarkdown(),";
    $script.="\"cases\":getCases(),\"hint\":hint.getMarkdown(),\"tags\":document.getElementById('tags').value,";
    $script.="\"difficulty\":document.getElementById('difficulty').value};console.log(Form);";
    $script.="SendAjax(\"".GetAPIUrl("/problem/create",null)."\",\"POST\",Form,function(res){alert(\"Create successfully! Your problem id: \"+JSON.parse(strip_tags_pre(res))['data']['id']);";
    $script.="location.href=\"".GetUrl("admin",array("page"=>"problem"))."?id=\"+JSON.parse(strip_tags_pre(res))['data']['id'];},true);}";
    $script.="document.getElementById('data').onchange=function(){document.getElementById('dataname').innerHTML=document.getElementById('data').files[0]['name'];};";
    $script.="setTimeout(function(){window.scrollTo(0,0)},100);";

    $body.=InsertTags("script",null,$script);
    $body.=InsertTags("style",null,$style);
}

function solve($md) {
    $md=str_replace("\\","\\\\",$md); $md=str_replace("\n","\\n",$md); 
    $md=str_replace("\r","",$md); $md=str_replace("'","\\'",$md);
    return $md;
}

function format_size(int $size):string {
    if ($size<=2048) return $size."B";
    $size=$size/1024; if ($size<100) return round($size,2)."KB";
    if ($size<1000) return round($size,1)."KB";
    if ($size<=2048) return round($size,0)."KB";
    $size=$size/1024; if ($size<100) return round($size,2)."MB";
    if ($size<1000) return round($size,1)."MB";
    if ($size<=2048) return round($size,0)."MB";
    $size=$size/1024; return round($size,0)."GB";
}

function problem_update(array $param,string &$html,string &$body):void {
    $id=$param["id"];
    $problem_controller=new Problem_Controller;
    $tags_controller=new Tags_Controller;
    $problem=$problem_controller->ListProblemByPid($id);
    $tags=$tags_controller->ListProblemTagsByPid($id);
    $fp=fopen("../problem/$id/config.json","r");
    $json=fread($fp,filesize("../problem/$id/config.json"));
    $json=json_decode($json,true);
    fclose($fp); $lang=GetLanguage()["main"]["problem"]["update"];

    $contest_controller=new Contest_Controller;
    $style=InsertCssStyle(array("input[type='file']"),array(
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
    ); $style.=InsertCssStyle(array("textarea"),array(
        "width"=>"calc( 100% - 16.8px )",
        "height"=>"100px",
        "resize"=>"vertical",
        "padding"=>"8.4px 10px",
        "outline"=>"none",
        "border"=>"rgb(221,221,221) solid 1px",
        "background-color"=>"rgb(249,255,204)",
    )); $style.=InsertCssStyle(array(".data-main"),array(
        "display"=>"flex",
        "display"=>"-webkit-flex",
        "margin-top"=>"19.92px"
    )); $style.=InsertCssStyle(array(".name"),array(
        "width"=>"1px",
        "flex-grow"=>"1500",
    )); $style.=InsertCssStyle(array(".input",".input2"),array(
        "width"=>"1px",
        "flex-grow"=>"2000",
    )); $style.=InsertCssStyle(array(".input > center > input"),array(
        "flex-grow"=>"0!important",
        "width"=>"150px",
    ));  $style.=InsertCssStyle(array(".input > center"),array(
        "position"=>"relative",
        "left"=>"10px"
    ));

    $tmp=InsertTags("hp",null,$lang["info"]);
    $tmp.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("margin-top"=>"15px"))),
        $lang["name"].":&nbsp;".InsertSingleTag("input",array("type"=>"text","id"=>"name","placeholder"=>$lang["name-placeholder"],"value"=>$problem["name"])));
    $tmp.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("margin-top"=>"15px"))),
        $lang["pid"].":&nbsp;".InsertSingleTag("input",array("type"=>"text","id"=>"pid","placeholder"=>$lang["pid-placeholder"],"value"=>$problem["pid"])));
    $body.=InsertTags("div",array("class"=>"default_main","style"=>InsertInlineCssStyle(array(
        "margin-top"=>"20px","padding"=>"20px"))),$tmp);

    $tmp=InsertTags("hp",null,$lang["background"]);
    $tmp.=InsertTags("div",array("id"=>"bg","style"=>InsertInlineCssStyle(array("margin-top"=>"15px"))),null);
    $body.=InsertTags("div",array("class"=>"default_main","style"=>InsertInlineCssStyle(array(
        "margin-top"=>"20px","padding"=>"20px"))),$tmp);

    $tmp=InsertTags("hp",null,$lang["description"]);
    $tmp.=InsertTags("div",array("id"=>"descrip","style"=>InsertInlineCssStyle(array("margin-top"=>"15px"))),null);
    $body.=InsertTags("div",array("class"=>"default_main","style"=>InsertInlineCssStyle(array(
        "margin-top"=>"20px","padding"=>"20px"))),$tmp);

    $tmp=InsertTags("hp",null,$lang["io-information"]);
    $tmp.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("margin-top"=>"15px"))),
        $lang["input-file"].":&nbsp;".InsertSingleTag("input",array("type"=>"text","id"=>"input-file","placeholder"=>$lang["input-file-placeholder"],"value"=>$json["input"])));
    $tmp.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("margin-top"=>"15px"))),
        $lang["output-file"].":&nbsp;".InsertSingleTag("input",array("type"=>"text","id"=>"output-file","placeholder"=>$lang["output-file-placeholder"],"value"=>$json["output"])));
    $body.=InsertTags("div",array("class"=>"default_main","style"=>InsertInlineCssStyle(array(
        "margin-top"=>"20px","padding"=>"20px"))),$tmp);

    $tmp=InsertTags("hp",null,$lang["input"]);
    $tmp.=InsertTags("div",array("id"=>"input","style"=>InsertInlineCssStyle(array("margin-top"=>"15px"))),null);
    $body.=InsertTags("div",array("class"=>"default_main","style"=>InsertInlineCssStyle(array(
        "margin-top"=>"20px","padding"=>"20px"))),$tmp);
    $tmp=InsertTags("hp",null,$lang["output"]);
    $tmp.=InsertTags("div",array("id"=>"output","style"=>InsertInlineCssStyle(array("margin-top"=>"15px"))),null);
    $body.=InsertTags("div",array("class"=>"default_main","style"=>InsertInlineCssStyle(array(
        "margin-top"=>"20px","padding"=>"20px"))),$tmp);

    $tmp=InsertTags("hp",null,$lang["sample"]);
    $tmp.=InsertTags("div",array("id"=>"cases"),"");
    $tmp.=InsertTags("center",null,InsertTags("button",array("onclick"=>"appendCase()"),$lang["append"]));
    $body.=InsertTags("div",array("class"=>"default_main","style"=>InsertInlineCssStyle(array(
        "margin-top"=>"20px","padding"=>"20px"))),$tmp);

    $tmp=InsertTags("hp",null,$lang["hint"]);
    $tmp.=InsertTags("div",array("id"=>"hint","style"=>InsertInlineCssStyle(array("margin-top"=>"15px"))),null);
    $body.=InsertTags("div",array("class"=>"default_main","style"=>InsertInlineCssStyle(array(
        "margin-top"=>"20px","padding"=>"20px"))),$tmp);

    $tmp=InsertTags("hp",null,$lang["additional"]);
    if (is_dir("./files/".$param["id"])) $files=scandir("./files/".$param["id"]);
    else $files=array(".",".."); $tmp2="";
    unset($files[0]); unset($files[1]);
    foreach ($files as $key=>$value) {
        $tmp2.=InsertTags("i",array("class"=>"linkify icon","style"=>InsertInlineCssStyle(array(
            "width"=>"auto",
            "margin"=>"10px 0px"
        ))),"&nbsp;&nbsp;&nbsp;&nbsp;".
        InsertTags("a",array("href"=>GetAPIUrl("/problem/addition",array("pid"=>$param["id"],"name"=>$value))),$value).
        "&nbsp;&nbsp;&nbsp;&nbsp;".InsertTags("pp",null,format_size(filesize("./files/".$param["id"]."/$value"))).
        "&nbsp;&nbsp;|&nbsp;&nbsp;".InsertTags("a",array("onclick"=>"delete_file('$value')"),$lang["delete-additional"])
        ).InsertSingleTag("br",null);
    } $tmp.=InsertTags("div",array("id"=>"addition","style"=>InsertInlineCssStyle(array("margin-top"=>"15px"))),$tmp2);
    $tmp.=InsertTags("div",array("style"=>InsertInlineCssStyle(array("margin-top"=>"15px","display"=>"flex","align-items"=>"center"))),InsertTags("p",null,$lang["additional-upload"].":&nbsp;").
        InsertTags("div",array("class"=>"file"),InsertTags("label",array("for"=>"data2","class"=>"label"),$lang["choose-file"])).
        InsertSingleTag("input",array("type"=>"file","id"=>"data2","name"=>"data2")).
        InsertTags("div",array("id"=>"data2name"),""));
    $body.=InsertTags("div",array("class"=>"default_main","style"=>InsertInlineCssStyle(array(
        "margin-top"=>"20px","padding"=>"20px"))),$tmp);

    $tagstring=""; for ($i=0;$i<count($tags);$i++) $tagstring.=$tags[$i].",";
    $tmp=InsertTags("hp",null,$lang["others"]);
    $tmp.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("margin-top"=>"15px"))),
        $lang["tags"].":&nbsp;".InsertSingleTag("input",array("type"=>"text","id"=>"tags","placeholder"=>$lang["tags-placeholder"],"value"=>$tagstring)));
    $config=GetConfig();
    $options=InsertTags("option",array("value"=>"0"),$config["difficulties"][0]["name"]);  
    for ($i=1;$i<count($config["difficulties"]);$i++) 
        if ($i!=$problem["difficult"]) $options.=InsertTags("option",array("value"=>$i),$config["difficulties"][$i]["name"]);
        else $options.=InsertTags("option",array("value"=>$i,"selected"=>"selected"),$config["difficulties"][$i]["name"]);
    $tmp.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("margin-top"=>"15px"))),
    $lang["difficulty"].":&nbsp;".InsertTags("select",array("id"=>"difficulty","style"=>InsertInlineCssStyle(array(
        "width"=>"10px",
        "flex-grow"=>"1000",
        "height"=>"30px",
        "padding-left"=>"10px",
        "outline"=>"none",
        "border"=>"rgb(221,221,221) 1px solid",
        "background-color"=>"rgb(249,255,204)",
        "border-radius"=>"3px",
        "padding-right"=>"10px"
    ))),$options));
    $body.=InsertTags("div",array("class"=>"default_main","style"=>InsertInlineCssStyle(array(
        "margin-top"=>"20px","padding"=>"20px"))),$tmp);

    $tmp=InsertTags("hp",null,$lang["data-config"]);
    $tmp.=InsertTags("div",array("style"=>InsertInlineCssStyle(array("margin-top"=>"15px","display"=>"flex","align-items"=>"center"))),InsertTags("p",null,$lang["upload-data"].":&nbsp;").
        InsertTags("div",array("class"=>"file"),InsertTags("label",array("for"=>"data","class"=>"label"),$lang["choose-data"])).
        InsertSingleTag("input",array("accept"=>".zip","type"=>"file","id"=>"data","name"=>"data")).
        InsertTags("div",array("id"=>"dataname"),""));
    $tmp.=InsertTags("div",array("style"=>InsertInlineCssStyle(array("display"=>"flex","align-items"=>"center"))),
        InsertTags("p",null,$lang["download-data"].":&nbsp;").InsertTags("button",array("onclick"=>"download()"),$lang["click-here"]));
    $tmp.=InsertTags("div",array("class"=>"data-main","id"=>"data-header"),
        InsertTags("div",array("class"=>"name"),InsertTags("center",null,$lang["test-case"])).
        InsertTags("div",array("class"=>"input2"),InsertTags("center",null,$lang["time-limit"])).
        InsertTags("div",array("class"=>"input2"),InsertTags("center",null,$lang["memory-limit"])).
        InsertTags("div",array("class"=>"input2"),InsertTags("center",null,$lang["score"])).
        InsertTags("div",array("class"=>"input2"),InsertTags("center",null,$lang["subtask"]))
    ); $tmp.=InsertTags("div",array("class"=>"data-main","id"=>"data-all"),
        InsertTags("div",array("class"=>"name"),$lang["one-click"]).
        InsertTags("div",array("class"=>"input"),InsertTags("center",null,InsertSingleTag("input",array("type"=>"text","id"=>"data-time0"))."&nbsp;ms")).
        InsertTags("div",array("class"=>"input"),InsertTags("center",null,InsertSingleTag("input",array("type"=>"text","id"=>"data-memory0"))."&nbsp;MiB")).
        InsertTags("div",array("class"=>"input"),InsertTags("center",null,InsertSingleTag("input",array("type"=>"text","id"=>"data-score0"))."&nbsp;pts")).
        InsertTags("div",array("class"=>"input"),InsertTags("center",null,InsertSingleTag("input",array("type"=>"text","id"=>"data-subtask0"))))
    ); for ($i=0;$i<count($json["data"]);$i++) {
        $tmp.=InsertTags("div",array("class"=>"data-main","id"=>"data-".($i+1)),
            InsertTags("div",array("class"=>"name ellipsis"),"#".($i+1).":&nbsp;".$json["data"][$i]["input"]."&nbsp;/&nbsp;".$json["data"][$i]["output"]).
            InsertTags("div",array("class"=>"input"),InsertTags("center",null,InsertSingleTag("input",array("type"=>"text","id"=>"data-time".($i+1),"value"=>$json["data"][$i]["time"]))."&nbsp;ms")).
            InsertTags("div",array("class"=>"input"),InsertTags("center",null,InsertSingleTag("input",array("type"=>"text","id"=>"data-memory".($i+1),"value"=>round($json["data"][$i]["memory"]/1024)))."&nbsp;MiB")).
            InsertTags("div",array("class"=>"input"),InsertTags("center",null,InsertSingleTag("input",array("type"=>"text","id"=>"data-score".($i+1),"value"=>$json["data"][$i]["score"]))."&nbsp;pts")).
            InsertTags("div",array("class"=>"input"),InsertTags("center",null,InsertSingleTag("input",array("type"=>"text","id"=>"data-subtask".($i+1),"value"=>$json["data"][$i]["subtask"]))))
        );
    } $tmp.=InsertTags("p",null,$lang["subtask-dependence"].": "); $sd="\n";
    for ($i=0;$i<count($json["subtask_depend"]);$i++) {
        for ($j=0;$j<count($json["subtask_depend"][$i]);$j++) {
            $sd.=$json["subtask_depend"][$i][$j].",";
        } $sd.="\n";
    } // echo $sd; exit;
    $tmp.=InsertTags("textarea",array("id"=>"subtask-dependence"),$sd);
    $body.=InsertTags("div",array("class"=>"default_main","style"=>InsertInlineCssStyle(array(
        "margin-top"=>"20px","padding"=>"20px"))),$tmp);

    $tmp=InsertTags("hp",null,$lang["spj-config"]);
    $options=InsertTags("option",array("id"=>"spj-type-0","value"=>0),$lang["spj-custom"]);
    for ($i=0;$i<count($config["spj"]);$i++) $options.=InsertTags("option",array("id"=>"spj-type-".($i+1),"value"=>($i+1)),$config["spj"][$i]["name"]);
    $tmp.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("margin-top"=>"15px"))),
    $lang["spj-template"].":&nbsp;".InsertTags("select",array("id"=>"spj-type","style"=>InsertInlineCssStyle(array(
        "width"=>"10px",
        "flex-grow"=>"1000",
        "height"=>"30px",
        "padding-left"=>"10px",
        "outline"=>"none",
        "border"=>"rgb(221,221,221) 1px solid",
        "background-color"=>"rgb(249,255,204)",
        "border-radius"=>"3px",
        "padding-right"=>"10px"
    ))),$options));
    $tmp.=InsertTags("div",array("id"=>"spj-1","class"=>"flex","style"=>InsertInlineCssStyle(array("margin-top"=>"15px"))),
        $lang["spj-source"].":&nbsp;".InsertSingleTag("input",array("type"=>"text","id"=>"spj-source","placeholder"=>$lang["spj-source-placeholder"],"value"=>$json["spj"]["source"])));
    $tmp.=InsertTags("div",array("id"=>"spj-2","class"=>"flex","style"=>InsertInlineCssStyle(array("margin-top"=>"15px"))),
        $lang["spj-command"].":&nbsp;".InsertSingleTag("input",array("type"=>"text","id"=>"spj-compile-cmd","placeholder"=>$lang["spj-command-placeholder"],"value"=>$json["spj"]["compile_cmd"])));
    $tmp.=InsertTags("div",array("id"=>"spj-3","class"=>"flex","style"=>InsertInlineCssStyle(array("margin-top"=>"15px"))),
        $lang["spj-path"].":&nbsp;".InsertSingleTag("input",array("type"=>"text","id"=>"spj-exec-name","placeholder"=>$lang["spj-path-placeholder"],"value"=>$json["spj"]["exec_name"])));
    $tmp.=InsertTags("div",array("id"=>"spj-4","class"=>"flex","style"=>InsertInlineCssStyle(array("margin-top"=>"15px"))),
        $lang["spj-param"].":&nbsp;".InsertSingleTag("input",array("type"=>"text","id"=>"spj-exec-param","placeholder"=>$lang["spj-param-placeholder"],"value"=>$json["spj"]["exec_param"])));
    $tmp.=InsertTags("center",null,InsertTags("button",array("onclick"=>"submit()"),$lang["submit"]));
    $body.=InsertTags("div",array("class"=>"default_main","style"=>InsertInlineCssStyle(array(
        "margin-top"=>"20px","padding"=>"20px"))),$tmp);
    
    $script="var bg=".CreateEditor($problem["bg"],"bg",",height:'400px'");
    $script.="var descrip=".CreateEditor($problem["descrip"],"descrip",",height:'400px'");
    $script.="var input=".CreateEditor($problem["input"],"input",",height:'400px'");
    $script.="var output=".CreateEditor($problem["output"],"output",",height:'400px'");
    $script.="var hint=".CreateEditor($problem["hint"],"hint",",height:'400px'");
    $sample=$problem["cases"]; $sample=preg_replace('/[\x00-\x1F\x7F-\x9F]/u','',$sample);
    $sample=json_decode($sample,true); 
    $script.="var cs=0;function appendCase() {cs++;var content=\"<div id='case\"+cs+\"' style='display:flex;display:-webkit-flex;margin-top:15px'>\"+";
    $script.="\"<div style='flex-grow:1000'><p>".$lang["sample-input"]."&nbsp;#\"+cs+\": </p><textarea id='case\"+cs+\"-input'></textarea></div>\"+";
    $script.="\"<div style='flex-grow:1000;margin-left:20px'><p>".$lang["sample-output"]."&nbsp;#\"+cs+\": </p><textarea id='case\"+cs+\"-output'></textarea></div>\"+";
    $script.="\"</div>\";$(\"#cases\").append(content);} function getCases(){";
    $script.="var obj=new Array;for (var i=1;i<=cs;i++) {var objnow=new Object;";
    $script.="objnow={input:document.getElementById(\"case\"+i+\"-input\").value,";
    $script.="output:document.getElementById(\"case\"+i+\"-output\").value};";
    $script.="if (objnow[\"input\"]==\"\"&&objnow[\"output\"]==\"\") continue;";
    $script.="obj.push(objnow);}return JSON.stringify(obj);}";
    $script.="function readFile(id,callback){var input=document.getElementById(id);if (input.value==''){alert(\"Data Package is not uploaded!\");return false;}";
    $script.="if (typeof(FileReader)==='undefined'){alert(\"Your browser is too old, sorry!\");";
    $script.="input.setAttribute('disabled','disabled');} var reader=new FileReader();";
    $script.="reader.readAsDataURL(input.files[0]);reader.onload=function(){callback(this.result);}}";
    $script.="function strip_tags_pre(msg){msg=msg.replace(/<(\/)?pre[^>]*>/g,'');return msg;}";
    $script.="function submit() {var Form={\"pid\":$id,\"ppid\":document.getElementById(\"pid\").value,\"title\":document.getElementById(\"name\").value,";
    $script.="\"background\":bg.getMarkdown(),\"description\":descrip.getMarkdown(),\"input-file\":document.getElementById(\"input-file\").value,";
    $script.="\"output-file\":document.getElementById(\"output-file\").value,\"input\":input.getMarkdown(),\"output\":output.getMarkdown(),";
    $script.="\"cases\":getCases(),\"hint\":hint.getMarkdown(),\"tags\":document.getElementById('tags').value,";
    $script.="\"difficulty\":document.getElementById('difficulty').value,";
    $script.="\"data\":getData(),\"spj_type\":document.getElementById('spj-type').value,";
    $script.="\"spj_source\":document.getElementById('spj-source').value,\"spj_compile\":document.getElementById('spj-compile-cmd').value,";
    $script.="\"spj_name\":document.getElementById('spj-exec-name').value,\"spj_param\":document.getElementById('spj-exec-param').value,";
    $script.="\"subtask_dependence\":document.getElementById('subtask-dependence').value};console.log(Form);";
    $script.="SendAjax(\"".GetAPIUrl("/problem/update",null)."\",\"POST\",Form,function(res){";
    $script.="if (JSON.parse(strip_tags_pre(res))['code']!=0) layer.msg(JSON.parse(strip_tags_pre(res))['message']);";
    $script.="else alert(\"Update successfully! Your problem id: \"+JSON.parse(strip_tags_pre(res))['data']['id']);},true);}";
    for ($i=0;$i<count($sample);$i++) $script.="appendCase();document.getElementById('case'+cs+'-input').innerHTML='".solve($sample[$i]["input"])."';document.getElementById('case'+cs+'-output').innerHTML='".solve($sample[$i]["output"])."';";
    $script.="document.getElementById(\"data-time0\").oninput=function() {for (var i=1;i<=".count($json["data"]).";i++) {";
    $script.="document.getElementById(\"data-time\"+i).value=document.getElementById(\"data-time0\").value;}};";
    $script.="document.getElementById(\"data-memory0\").oninput=function() {for (var i=1;i<=".count($json["data"]).";i++) {";
    $script.="document.getElementById(\"data-memory\"+i).value=document.getElementById(\"data-memory0\").value;}};";
    $script.="document.getElementById(\"data-score0\").oninput=function() {for (var i=1;i<=".count($json["data"]).";i++) {";
    $script.="document.getElementById(\"data-score\"+i).value=document.getElementById(\"data-score0\").value;}};";
    $script.="document.getElementById(\"data-subtask0\").oninput=function() {for (var i=1;i<=".count($json["data"]).";i++) {";
    $script.="document.getElementById(\"data-subtask\"+i).value=document.getElementById(\"data-subtask0\").value;}};";
    $script.="document.getElementById(\"spj-type-".$json["spj"]["type"]."\").setAttribute(\"selected\",\"selected\");";
    if ($json["spj"]["type"]==0) {
        $script.="document.getElementById(\"spj-1\").style=\"display:flex;margin-top:15px\";";
        $script.="document.getElementById(\"spj-2\").style=\"display:flex;margin-top:15px\";";
        $script.="document.getElementById(\"spj-3\").style=\"display:flex;margin-top:15px\";";
    } else {
        $script.="document.getElementById(\"spj-1\").style=\"display:none\";";
        $script.="document.getElementById(\"spj-2\").style=\"display:none\";";
        $script.="document.getElementById(\"spj-3\").style=\"display:none\";";
    } $script.="document.getElementById(\"spj-type\").onchange=function() {";
    $script.="if (document.getElementById(\"spj-type\").value==0) {";
    $script.="document.getElementById(\"spj-1\").style=\"display:flex;margin-top:15px\";";
    $script.="document.getElementById(\"spj-2\").style=\"display:flex;margin-top:15px\";";
    $script.="document.getElementById(\"spj-3\").style=\"display:flex;margin-top:15px\";";
    $script.="} else {";
    $script.="document.getElementById(\"spj-1\").style=\"display:none\";";
    $script.="document.getElementById(\"spj-2\").style=\"display:none\";";
    $script.="document.getElementById(\"spj-3\").style=\"display:none\";";
    $script.="}}; function getData() {";
    $script.="var res={\"time\":new Array,\"memory\":new Array,\"score\":new Array,\"subtask\":new Array};";
    $script.="for (var i=1;i<=".count($json["data"]).";i++) {";
    $script.="res[\"time\"].push(Number(document.getElementById(\"data-time\"+i).value));";
    $script.="res[\"memory\"].push(Number(document.getElementById(\"data-memory\"+i).value*1024));";
    $script.="res[\"score\"].push(Number(document.getElementById(\"data-score\"+i).value));";
    $script.="res[\"subtask\"].push(Number(document.getElementById(\"data-subtask\"+i).value));";
    $script.="} return JSON.stringify(res);";
    $script.="} document.getElementById('data').onchange=function(){readFile('data',UploadFile);};";
    $script.="document.getElementById('data2').onchange=function(){readFile('data2',UploadFile2);};";
    $script.="function UploadFile(content) {";
    $script.="SendAjax('".GetAPIUrl("/problem/upload",null)."','POST',{pid:$id,file:(content.split(\",\"))[1]}";
    $script.=",function(){alert('Upload Success!');location.href=location.href;},true);}";
    $script.="function download(){location.href='".GetAPIUrl("/problem/download",array("id"=>$id))."';}";
    $script.="function UploadFile2(content) { console.log(document.getElementById('data2').files[0]);";
    $script.="SendAjax('".GetAPIUrl("/problem/upload2",null)."','POST',{pid:$id,file:(content.split(\",\"))[1],name:document.getElementById('data2').files[0]['name']}";
    $script.=",function(){alert('Upload Success!');location.href=location.href;},true);}";
    $script.="function delete_file(name){SendAjax('".GetAPIUrl("/problem/delete2",null)."','POST',{pid:$id,name:name}";
    $script.=",function(){alert('Delete Success!');location.href=location.href;},true)}";
    $body.=InsertTags("script",null,$script);
    $body.=InsertTags("style",null,$style);
}

function problem_run(array $param,string &$html,string &$body):void {
    if (array_key_exists("num",$param)){problem_list_run($param,$html,$body);return;}
    if (!array_key_exists("id",$param)){$param["num"]=1;problem_list_run($param,$html,$body);return;}
    if ($param["id"]==0){problem_create($param,$html,$body);return;}
    problem_update($param,$html,$body);
}

function system_run(array $param,string &$html,string &$body):void {
    $admin_controller=new Admin_Controller;

    $cpuinfo=$admin_controller->GetCPUInfo(); $lang=GetLanguage()["main"]["system"];
    $cpu=InsertTags("div",array("style"=>InsertInlineCssStyle(array(
        "padding-bottom"=>"20px",
        "padding-right"=>"20px"
    ))),InsertTags("hp",null,$lang["cpu"]["title"]));
    $style=InsertCssStyle(array(".cpu-usage-all",".cpu-usage-system",".cpu-usage-user"),array(
        "flex-grow"=>"2000",
        "background-color"=>"lightgrey",
        "height"=>"10px",
        "border-radius"=>"5px"
    )); $style.=InsertCssStyle(array(".cpu-usage-system"),array(
        "background-color"=>"lightskyblue",
        "transition"=>"width 0.5s"
    )); $style.=InsertCssStyle(array(".cpu-usage-user"),array(
        "background-color"=>"orange",
        "transition"=>"width 0.5s"
    )); $cpu.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("width"=>"100%"))),$lang["cpu"]["name"].":&nbsp;".
    InsertSingleTag("input",array("disabled"=>"disabled","value"=>$cpuinfo["Name"])));
    $cpu.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("width"=>"100%","padding-top"=>"10px"))),$lang["cpu"]["cores"].":&nbsp;".
    InsertSingleTag("input",array("disabled"=>"disabled","value"=>$cpuinfo["Cores"]." Cores")));
    $cpu.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("width"=>"100%","padding-top"=>"10px"))),$lang["cpu"]["usage"].":&nbsp;".
    InsertTags("div",array("class"=>"cpu-usage-all"),InsertTags("div",array("class"=>"cpu-usage-system","id"=>"cpu-usage-system",
    "style"=>InsertInlineCssStyle(array("width"=>($cpuinfo["Usage"]["us"]+$cpuinfo["Usage"]["sy"])."%"))),
    InsertTags("div",array("class"=>"cpu-usage-user","id"=>"cpu-usage-user","style"=>InsertInlineCssStyle(array(
    "width"=>($cpuinfo["Usage"]["us"]/($cpuinfo["Usage"]["us"]+$cpuinfo["Usage"]["sy"]==0?1:($cpuinfo["Usage"]["us"]+$cpuinfo["Usage"]["sy"]))*100)."%"))),""))).
    InsertTags("div",array("id"=>"cpu-usage"),"&nbsp;".$cpuinfo["Usage"]["us"]."% user | ".$cpuinfo["Usage"]["sy"]."% system | ".(100-$cpuinfo["Usage"]["us"]-$cpuinfo["Usage"]["sy"])."% free"));
    // echo ($cpuinfo["Usage"]["us"]/($cpuinfo["Usage"]["us"]+$cpuinfo["Usage"]["sy"]==0?1:($cpuinfo["Usage"]["us"]+$cpuinfo["Usage"]["sy"]))); exit;
    $body.=InsertTags("div",array("class"=>"default_main","style"=>
    InsertInlineCssStyle(array(
        "padding"=>"20px",
        "margin-bottom"=>"20px",
        "margin-top"=>"20px",
    ))),$cpu);

    $meminfo=$admin_controller->GetMemoryInfo();
    $mem=InsertTags("div",array("style"=>InsertInlineCssStyle(array(
        "padding-bottom"=>"20px",
        "padding-right"=>"20px"
    ))),InsertTags("hp",null,$lang["memory"]["title"]));
    $style.=InsertCssStyle(array(".usage-all",".usage-use"),array(
        "flex-grow"=>"2000",
        "background-color"=>"lightgrey",
        "height"=>"10px",
        "border-radius"=>"5px"
    )); $style.=InsertCssStyle(array(".usage-use"),array(
        "background-color"=>"orange",
        "transition"=>"width 0.5s"
    )); $mem.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("width"=>"100%"))),$lang["memory"]["memory"].":&nbsp;".
    InsertTags("div",array("class"=>"usage-all"),InsertTags("div",array("class"=>"usage-use","id"=>"usage-memory","style"=>
    InsertInlineCssStyle(array("width"=>$meminfo["memPercent"]."%"))),""))
    .InsertTags("div",array("id"=>"memory-usage"),"&nbsp;".$meminfo["memUsed"]."/".$meminfo["memTotal"])
    ); $mem.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("width"=>"100%","padding-top"=>"10px"))),$lang["memory"]["swap"].":&nbsp;".
    InsertTags("div",array("class"=>"usage-all"),InsertTags("div",array("class"=>"usage-use","id"=>"usage-swap","style"=>
    InsertInlineCssStyle(array("width"=>$meminfo["swapPercent"]."%"))),""))
    .InsertTags("div",array("id"=>"swap-usage"),"&nbsp;".$meminfo["swapUsed"]."/".$meminfo["swapTotal"])
    ); $body.=InsertTags("div",array("class"=>"default_main","style"=>
    InsertInlineCssStyle(array(
        "padding"=>"20px",
        "margin-bottom"=>"20px",
        "margin-top"=>"20px",
    ))),$mem);

    $diskinfo=$admin_controller->GetDiskInfo();
    $disk=InsertTags("div",array("style"=>InsertInlineCssStyle(array(
        "padding-bottom"=>"20px",
        "padding-right"=>"20px"
    ))),InsertTags("hp",null,$lang["disk"]["title"]));
    $disk.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("width"=>"100%"))),$lang["disk"]["disk"].":&nbsp;".
    InsertTags("div",array("class"=>"usage-all"),InsertTags("div",array("class"=>"usage-use","id"=>"usage-disk","style"=>
    InsertInlineCssStyle(array("width"=>$diskinfo["diskPercent"]."%"))),""))
    .InsertTags("div",array("id"=>"disk-usage"),"&nbsp;".$diskinfo["diskUsed"]."/".$diskinfo["diskTotal"])
    ); $body.=InsertTags("div",array("class"=>"default_main","style"=>
    InsertInlineCssStyle(array(
        "padding"=>"20px",
        "margin-bottom"=>"20px",
        "margin-top"=>"20px",
    ))),$disk);

    $dbinfo=$admin_controller->GetDatabaseInfo();
    $db=InsertTags("div",array("style"=>InsertInlineCssStyle(array(
        "padding-bottom"=>"20px",
        "padding-right"=>"20px"
    ))),InsertTags("hp",null,$lang["database"]["title"]));
    $db.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("width"=>"100%"))),$lang["database"]["version"].":&nbsp;".
    InsertSingleTag("input",array("disabled"=>"disabled","value"=>$dbinfo["version"])));
    $db.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("width"=>"100%","padding-top"=>"10px"))),$lang["database"]["status"].":&nbsp;".
    InsertSingleTag("input",array("disabled"=>"disabled","id"=>"db-status","value"=>$dbinfo["status"])));
    $db.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("width"=>"100%","padding-top"=>"10px"))),$lang["database"]["login"].":&nbsp;".
    InsertSingleTag("input",array("disabled"=>"disabled","value"=>$dbinfo["info"])));
    $db.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("width"=>"100%","padding-top"=>"10px"))),$lang["database"]["databases"].":&nbsp;".
    InsertSingleTag("input",array("disabled"=>"disabled","value"=>$dbinfo["db_num"]." databases")));
    $db.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("width"=>"100%","padding-top"=>"10px"))),$lang["database"]["tables"].":&nbsp;".
    InsertSingleTag("input",array("disabled"=>"disabled","value"=>$dbinfo["table_num"]." tables")));
    foreach($dbinfo as $key=>$value) {
        if (strpos($key,"exist")===false) continue;
        $db.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("width"=>"100%","padding-top"=>"10px"))),$lang["database"]["table"]."&nbsp;'".substr($key,6)."':&nbsp;".
        InsertSingleTag("input",array("disabled"=>"disabled","value"=>$value)));
    }
    $body.=InsertTags("div",array("class"=>"default_main","style"=>
    InsertInlineCssStyle(array(
        "padding"=>"20px",
        "margin-bottom"=>"20px",
        "margin-top"=>"20px",
    ))),$db);

    $judgerinfo=$admin_controller->GetJudgeInfo();
    $judger=InsertTags("div",array("style"=>InsertInlineCssStyle(array(
        "padding-bottom"=>"20px",
        "padding-right"=>"20px"
    ))),InsertTags("hp",null,$lang["judger"]["title"]));
    $judger.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("width"=>"100%"))),$lang["judger"]["number"].":&nbsp;".
    InsertSingleTag("input",array("disabled"=>"disabled","id"=>"judger-number","value"=>$judgerinfo["num"]." judgers")));
    $judger.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("width"=>"100%","padding-top"=>"10px"))),$lang["judger"]["online"].":&nbsp;".
    InsertSingleTag("input",array("disabled"=>"disabled","id"=>"judger-online","value"=>$judgerinfo["online"]." judgers")));
    for ($i=0;$i<count($judgerinfo["data"]);$i++) {
        $judger.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("width"=>"100%","padding-top"=>"10px"))),str_replace("\$\$name\$\$",$judgerinfo["data"][$i]["name"],$lang["judger"]["key"]).
        InsertSingleTag("input",array("disabled"=>"disabled","value"=>$judgerinfo["data"][$i]["id"])));
        $judger.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("width"=>"100%","padding-top"=>"10px"))),str_replace("\$\$name\$\$",$judgerinfo["data"][$i]["name"],$lang["judger"]["status"]).
        InsertSingleTag("input",array("disabled"=>"disabled","id"=>"judger-$i-status","value"=>$judgerinfo["data"][$i]["online"]?"active (running)":"inactive (dead)")));
    } $body.=InsertTags("div",array("class"=>"default_main","style"=>
    InsertInlineCssStyle(array(
        "padding"=>"20px",
        "margin-bottom"=>"20px",
        "margin-top"=>"20px",
    ))),$judger);

    $phpinfo=$admin_controller->GetPHPInfo();
    $php=InsertTags("div",array("style"=>InsertInlineCssStyle(array(
        "padding-bottom"=>"20px",
        "padding-right"=>"20px"
    ))),InsertTags("hp",null,$lang["php"]["title"]));
    $php.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("width"=>"100%"))),$lang["php"]["version"].":&nbsp;".
    InsertSingleTag("input",array("disabled"=>"disabled","value"=>$phpinfo["version"])));
    $php.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("width"=>"100%","padding-top"=>"10px"))),$lang["php"]["zend-version"].":&nbsp;".
    InsertSingleTag("input",array("disabled"=>"disabled","value"=>$phpinfo["zend_version"])));
    $php.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("width"=>"100%","padding-top"=>"10px"))),$lang["php"]["param"].":&nbsp;".
    InsertSingleTag("input",array("disabled"=>"disabled","value"=>$phpinfo["configure"])));
    $php.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("width"=>"100%","padding-top"=>"10px"))),$lang["php"]["system"].":&nbsp;".
    InsertSingleTag("input",array("disabled"=>"disabled","value"=>$phpinfo["build"])));
    $php.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("width"=>"100%","padding-top"=>"10px"))),$lang["php"]["time"].":&nbsp;".
    InsertSingleTag("input",array("disabled"=>"disabled","value"=>$phpinfo["build_time"])));
    $php.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("width"=>"100%","padding-top"=>"10px"))),$lang["php"]["memory"].":&nbsp;".
    InsertSingleTag("input",array("disabled"=>"disabled","value"=>round($phpinfo["memory"]/1024,1)." MB")));
    $php.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("width"=>"100%","padding-top"=>"10px"))),$lang["php"]["peak"].":&nbsp;".
    InsertSingleTag("input",array("disabled"=>"disabled","value"=>round($phpinfo["peak_memory"]/1024,1)." MB")));
    $body.=InsertTags("div",array("class"=>"default_main","style"=>
    InsertInlineCssStyle(array(
        "padding"=>"20px",
        "margin-bottom"=>"20px",
        "margin-top"=>"20px",
    ))),$php);

    $crontabinfo=$admin_controller->GetCrontabInfo();
    $crontab=InsertTags("div",array("style"=>InsertInlineCssStyle(array(
        "padding-bottom"=>"20px",
        "padding-right"=>"20px"
    ))),InsertTags("hp",null,$lang["crontab"]["title"]));
    $crontab.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("width"=>"100%"))),$lang["crontab"]["number"].":&nbsp;".
    InsertSingleTag("input",array("disabled"=>"disabled","id"=>"crontab-number","value"=>count($crontabinfo)." tasks")));
    $tmp=InsertTags("p",array("style"=>InsertInlineCssStyle(array("width"=>"10%"))),$lang["crontab"]["task-id"]);
    $tmp.=InsertTags("p",array("style"=>InsertInlineCssStyle(array("width"=>"20%"))),$lang["crontab"]["next-execute-time"]);
    $tmp.=InsertTags("p",array("style"=>InsertInlineCssStyle(array("width"=>"20%"))),$lang["crontab"]["task-name"]);
    $tmp.=InsertTags("p",array("style"=>InsertInlineCssStyle(array("width"=>"30%"))),$lang["crontab"]["execute-command"]);
    $table=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("margin-top"=>"20px","width"=>"100%","margin-bottom"=>"10px"))),$tmp);
    for ($i=0;$i<count($crontabinfo);$i++) {
        $tmp=InsertTags("p",array("style"=>InsertInlineCssStyle(array("width"=>"10%"))),"#".$crontabinfo[$i]["id"]);
        $tmp.=InsertTags("p",array("id"=>"crontab-".$crontabinfo[$i]["id"],"style"=>InsertInlineCssStyle(array("width"=>"20%"))),$crontabinfo[$i]["nexttime"]);
        $tmp.=InsertTags("p",array("style"=>InsertInlineCssStyle(array("width"=>"20%"))),$crontabinfo[$i]["name"]);
        $tmp.=InsertTags("p",array("style"=>InsertInlineCssStyle(array("width"=>"30%","flex-grow"=>"1000"))),$crontabinfo[$i]["command"]);
        $tmp.=InsertTags("p",null,InsertTags("button",array("onclick"=>"cronrun('".$crontabinfo[$i]["id"]."')"),$lang["crontab"]["run"]));
        $table.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("width"=>"100%","height"=>"35px"))),$tmp);
    } $body.=InsertTags("div",array("class"=>"default_main","style"=>
    InsertInlineCssStyle(array(
        "padding"=>"20px",
        "margin-bottom"=>"20px",
        "margin-top"=>"20px",
    ))),$crontab.$table);

    $sysinfo=$admin_controller->GetSystemInfo();
    $system=InsertTags("div",array("style"=>InsertInlineCssStyle(array(
        "padding-bottom"=>"20px",
        "padding-right"=>"20px"
    ))),InsertTags("hp",null,$lang["system"]["title"]));
    $system.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("width"=>"100%"))),$lang["system"]["global"].":&nbsp;".
    InsertSingleTag("input",array("disabled"=>"disabled","id"=>"global-time","value"=>$sysinfo["timeGlobal"])));
    $system.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("width"=>"100%","padding-top"=>"10px"))),$lang["system"]["server"].":&nbsp;".
    InsertSingleTag("input",array("disabled"=>"disabled","id"=>"server-time","value"=>$sysinfo["timeServer"])));
    $system.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("width"=>"100%","padding-top"=>"10px"))),$lang["system"]["timestamp"].":&nbsp;".
    InsertSingleTag("input",array("disabled"=>"disabled","id"=>"time-stamp","value"=>$sysinfo["timeStamp"])));
    $system.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("width"=>"100%","padding-top"=>"10px"))),$lang["system"]["timezone"].":&nbsp;".
    InsertSingleTag("input",array("disabled"=>"disabled","value"=>$sysinfo["timeZone"])));
    $system.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("width"=>"100%","padding-top"=>"10px"))),$lang["system"]["name"].":&nbsp;".
    InsertSingleTag("input",array("disabled"=>"disabled","value"=>$sysinfo["sysOperSys"])));
    $system.=InsertTags("div",array("class"=>"flex","style"=>InsertInlineCssStyle(array("width"=>"100%","padding-top"=>"10px"))),$lang["system"]["architecture"].":&nbsp;".
    InsertSingleTag("input",array("disabled"=>"disabled","value"=>$sysinfo["sysProcArch"])));
    $body.=InsertTags("div",array("class"=>"default_main","style"=>
    InsertInlineCssStyle(array(
        "padding"=>"20px",
        "margin-bottom"=>"20px",
        "margin-top"=>"20px",
    ))),$system);

    $script="function strip_tags_pre(msg){msg=msg.replace(/<(\/)?pre[^>]*>/g,'');return msg;}";
    $script.="setInterval(function(){";
    $script.="var cpuinfo=SendAjax('".GetAPIUrl("/admin/cpuinfo")."','GET',null);";
    $script.="cpuinfo=JSON.parse(strip_tags_pre(cpuinfo));";
    $script.="cpu_usage_system=Number(cpuinfo['data']['Usage']['sy'])+Number(cpuinfo['data']['Usage']['us']);";
    $script.="cpu_usage_user=Number(cpuinfo['data']['Usage']['us'])/(cpu_usage_system==0?1:cpu_usage_system)*100;";
    $script.="document.getElementById('cpu-usage-system').style.width=cpu_usage_system+'%';";
    $script.="document.getElementById('cpu-usage-user').style.width=cpu_usage_user+'%';";
    $script.="document.getElementById('cpu-usage').innerHTML='&nbsp;'+cpuinfo['data']['Usage']['us']+'% user | '+cpuinfo['data']['Usage']['sy']+'% system | '+(100-cpu_usage_system).toFixed(1)+'% free'";
    $script.="},2000);";
    $script.="setInterval(function(){";
    $script.="var meminfo=SendAjax('".GetAPIUrl("/admin/meminfo")."','GET',null);";
    $script.="meminfo=JSON.parse(strip_tags_pre(meminfo));";
    $script.="document.getElementById('usage-memory').style.width=meminfo['data']['memPercent']+'%';";
    $script.="document.getElementById('usage-swap').style.width=meminfo['data']['swapPercent']+'%';";
    $script.="document.getElementById('memory-usage').innerHTML='&nbsp;'+meminfo['data']['memUsed']+'/'+meminfo['data']['memTotal'];";
    $script.="document.getElementById('swap-usage').innerHTML='&nbsp;'+meminfo['data']['swapUsed']+'/'+meminfo['data']['swapTotal'];";
    $script.="},2000);";
    $script.="setInterval(function(){";
    $script.="var diskinfo=SendAjax('".GetAPIUrl("/admin/diskinfo")."','GET',null);";
    $script.="diskinfo=JSON.parse(strip_tags_pre(diskinfo));";
    $script.="document.getElementById('usage-disk').style.width=diskinfo['data']['diskPercent']+'%';";
    $script.="document.getElementById('disk-usage').innerHTML='&nbsp;'+diskinfo['data']['diskUsed']+'/'+diskinfo['data']['diskTotal'];";
    $script.="},2000);";
    $script.="setInterval(function(){";
    $script.="var dbinfo=SendAjax('".GetAPIUrl("/admin/dbstatus")."','GET',null);";
    $script.="dbinfo=JSON.parse(strip_tags_pre(dbinfo));";
    $script.="document.getElementById('db-status').value=dbinfo['data']['status'];";
    $script.="},2000);";
    $script.="setInterval(function(){";
    $script.="var judgerinfo=SendAjax('".GetAPIUrl("/admin/judgerinfo")."','GET',null);";
    $script.="judgerinfo=JSON.parse(strip_tags_pre(judgerinfo));";
    $script.="document.getElementById('judger-number').value=judgerinfo['data']['num']+' judgers';";
    $script.="document.getElementById('judger-online').value=judgerinfo['data']['online']+' judgers';";
    $script.="for (i=0;i<judgerinfo['data']['data'].length;i++) ";
    $script.="document.getElementById('judger-'+i+'-status').value=judgerinfo['data']['data'][i]['online']?'active (running)':'inactive (dead)';";
    $script.="},2000);";
    $script.="setInterval(function(){";
    $script.="var crontabinfo=SendAjax('".GetAPIUrl("/admin/crontabinfo")."','GET',null);";
    $script.="crontabinfo=JSON.parse(strip_tags_pre(crontabinfo));";
    $script.="document.getElementById('crontab-number').value=crontabinfo['data'].length+' tasks';";
    $script.="for (i=0;i<crontabinfo['data'].length;i++) ";
    $script.="document.getElementById('crontab-'+crontabinfo['data'][i]['id']).innerHTML=crontabinfo['data'][i]['nexttime'];";
    $script.="},2000);";
    $script.="function cronrun(id){";
    $script.="var res=SendAjax('".GetAPIUrl("/admin/cronrun")."','POST',{id:id});";
    $script.="res=strip_tags_pre(res);";
    $script.="res=JSON.parse(res); alert(res['code']==0?'Success!':res['message']);}";
    $script.="setInterval(function(){";
    $script.="var sysinfo=SendAjax('".GetAPIUrl("/admin/sysinfo")."','GET',null);";
    $script.="sysinfo=JSON.parse(strip_tags_pre(sysinfo));";
    $script.="document.getElementById('global-time').value=sysinfo['data']['timeGlobal'];";
    $script.="document.getElementById('server-time').value=sysinfo['data']['timeServer'];";
    $script.="document.getElementById('time-stamp').value=sysinfo['data']['timeStamp'];";
    $script.="},1000);";
    $script.="setTimeout(function(){window.scrollTo(0,0)},100);";
    $body.=InsertTags("style",null,$style);
    $body.=InsertTags("script",null,$script);
}
?>