<?php
function error_page(array $param,string &$html,string &$body):void {
    $tmp=InsertTags("h3",null,"Error Occurred!");
    $tmp.=InsertTags("p",null,"We are sorry, this page has some problem.<br/>".
    "The error info: ".$param["err"]."<br/><br/>".
    "If you are visitor or common user, you can call the website master to fix it.<br/>".
    "If you are website master and you cannot fix the problem, you can ask ".InsertTags("a",array("href"=>"https://github.com/LittleYang0531/lyoj/wiki/Common-Error"),"Common Error · LittleYang0531/lyoj Wiki")." for help.<br/><br/>".
    "If you think this is a bug, please report it on ".InsertTags("a",array("href"=>"https://github.com/LittleYang0531/lyoj/issues"),"Issues · LittleYang0531/lyoj").".<br/>".
    "Here are the issue format(in markdown): </br>".
    "### What version are you using?<br/><br/>".
    "### What is your machine configure?<br/><br/>".
    "### What problems do you find?<br/><br/>".
    "### How do you find it?<br/><br/>".
    "### Paste your log file here:<br/>".
    "(the bug is on the web platform, ignore!)<br/><br/>".
    "Thank you for your reporting!");
    $body=InsertTags("div",array("class"=>"default_main","style"=>InsertInlineCssStyle(array(
        "padding-bottom"=>"60px",
        "padding-left"=>"20px",
        "padding-right"=>"20px",
        "padding-top"=>"20px",
        "min-height"=>"720px"
    ))),$tmp);
}
?>