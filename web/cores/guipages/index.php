<?php
function run(array $param,string &$html,string &$body):void {
    $config=GetConfig(); $lang=GetLanguage()["main"];
    $fp=fopen($config["announcement_content"],"r");
    $content=fread($fp,filesize("/etc/judge/announcement.md"));
    $body.=InsertTags("div",array("class"=>"default_main","style"=>InsertInlineCssStyle(array(
        "padding-top"=>"20px",
        "margin-bottom"=>"20px"
    ))),InsertTags("hp",array("style"=>InsertInlineCssStyle(array(
        "padding-bottom"=>"60px",
        "padding-left"=>"20px",
        "padding-right"=>"20px"
    ))),$lang["announcement"]).InsertTags("div",array("id"=>"index-announcement","style"=>InsertInlineCssStyle(array(
        "width"=>"calc(100% - 40px)"
    ))),""));
    $script="";
    $script.=md2html($content,"index-announcement");
    $body.=InsertTags("script",null,$script);
}
?>