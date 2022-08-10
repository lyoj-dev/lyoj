<?php
/**
 * URL获取函数 GetUrl
 * @param string $path $_GET["path"]里的值
 * @param array|null $param 新页面中所有GET的参数
 * @return string
 */
function GetUrl(string $path,array|null $param):string {
    $config=GetConfig(false); $res="";
    if (!$config["web"]["absolute_path"]) $res="./";
    else $res=$config["web"]["protocol"]."://".$config["web"]["domain"]."/";
    $re_path=$path; if ($config["web"]["url_rewrite"]==true) {
        $tmp=$config["controllers"][$path]["rewrite_param"]; $ok=true;
        for ($i=0;$tmp!=null&&$i<count($tmp);$i++) if (!array_key_exists($tmp[$i],$param)) $ok=false;
        if ($ok) for ($i=0;$tmp!=null&&$i<count($tmp);$i++){$re_path.="/".$param[$tmp[$i]];unset($param[$tmp[$i]]);}
    } $res.=(!$config["web"]["url_rewrite"]?"index.php?path=$path&":"$re_path?");
    if ($param!=null) foreach ($param as $key=>$value) $res.="$key=$value&";
    return substr($res,0,strlen($res)-1);
}

/**
 * 绝对URL获取函数 GetRealUrl
 * @param string $path 文件相对路径
 * @param array|null $param 新页面中所有GET的参数
 * @return string
 */
function GetRealUrl(string $path,array|null $param):string {
    $config=GetConfig();
    if (!$config["web"]["absolute_path"]) $res="./";
    else $res=$config["web"]["protocol"]."://".$config["web"]["domain"]."/"; $res.=$path."?"; 
    if ($param!=null) foreach ($param as $key=>$value) $res.="$key=$value&";
    return substr($res,0,strlen($res)-1);
}

/**
 * HTTP URL获取函数 GetHTTPUrl
 * @param string $path  $_GET["path"]里的值
 * @param array|null $param 新页面中所有GET的参数
 * @return string
 */
function GetHTTPUrl(string $path,array|null $param):string {
    $config=GetConfig(); $res="";
    $res=$config["web"]["protocol"]."://".$config["web"]["domain"]."/";
    $res.=(!$config["web"]["url_rewrite"]?"index.php?path=$path&":"$path?");
    if ($param!=null) foreach ($param as $key=>$value) $res.="$key=$value&";
    return substr($res,0,strlen($res)-1);
}

/**
 * API URL获取函数 GetAPIUrl
 * @param string $path API路径
 * @param array|null $param=null 新页面中所有GET的参数
 */
function GetAPIUrl(string $path,array|null $param=null):string {
    $config=GetConfig();
    if (!$config["web"]["absolute_path"]) $res="./api";
    else $res=$config["web"]["protocol"]."://".$config["web"]["domain"]."/api";
    $res.=$path.".php"; $fir=true;
    foreach($param as $key=>$value) {
        if ($fir) $res.="?";
        else $res.="&"; $fir=false;
        $res.="$key=$value";
    } return $res;
}

/**
 * 程序配置获取函数 GetConfig
 * @param bool $merge 是否合并所有配置
 * @return array
 */
function GetConfig(bool $merge=true):array {
    global $config;
    $fp=fopen("/etc/judge/config.json","r");
    $json=fread($fp,filesize("/etc/judge/config.json"));
    $config=json_decode($json,true);
    if ($config["web"]["install"] == 0) $config["web"]["absolute_path"] = false;
    if (!$merge) return $config;
    else return array_merge($config,$config["controllers"][$_GET["path"]]["configs"]);
}

/**
 * 程序语言包获取函数 GetLanguage
 * @return array
 */
function GetLanguage():array {
    $lang=""; $config=GetConfig();
    if (array_key_exists("language",$_COOKIE)) $lang=$_COOKIE["language"];
    else $lang=$config["web"]["default-lang"];
    if (!array_key_exists($lang,$config["web"]["lang"])){$lang=$config["web"]["default-lang"];}
    setcookie("language",$lang,time()+1000*365*24*60*60,"/");
    $fp=fopen($config["web"]["lang"][$lang],"r");
    $json=fread($fp,filesize($config["web"]["lang"][$lang]));
    $arr=json_decode($json,true);
    $pages=$arr["pages"]; unset($arr["pages"]);
    $arr["main"]=$pages[$_GET["path"]];
    return $arr;
}

/**
 * 数组元素存在性检查函数 FindExist
 * @param string $name 元素路径，例$config["a"]["b"][3]["d"]即为"/a/b/3/d"
 * @param array $arr 要查询的数组
 * @param string $arr_name 数组名(注意是变量名)
 * @param bool $allow_empty 是否允许最后的值为空(默认为true)
 * @return void
 */
function FindExist(string $name,array $arr,string $arr_name,bool $allow_empty=true):void {
    $path=explode("//",$name); $arrnow=$arr; $fullpath="";
    for ($i=1;$i<count($path);$i++) 
        $fullpath.="[".(is_numeric($path[$i])?$path[$i]:"\"".$path[$i]."\"")."]";
    for ($i=1;$i<count($path);$i++) {
        if (!array_key_exists($path[$i],$arrnow)) {
            echo Error_Controller::Common("Cannot found '$$arr_name$fullpath'!");
            exit;
        }
        $arrnow=$arrnow[$path[$i]];
    } if (!$allow_empty&&($arrnow==""||$arrnow==null)) {
        echo Error_Controller::Common
        ("Expect '$$arr_name$fullpath', but it was empty!");
        exit;
    }
}

/**
 * 数组元素存在性检查函数2 FindExistBool
 * @param string $name 元素路径，例$config["a"]["b"][3]["d"]即为"//a//b//3//d"
 * @param array $arr 要查询的数组
 * @param bool $allow_empty 是否允许最后的值为空(默认为true)
 * @return bool
 */
function FindExist2(string $name,array $arr,bool $allow_empty=true):bool {
    $path=explode("//",$name); $arrnow=$arr; $fullpath="";
    for ($i=1;$i<count($path);$i++) 
        $fullpath.="[".(is_numeric($path[$i])?$path[$i]:"\"".$path[$i]."\"")."]";
    for ($i=1;$i<count($path);$i++) {
        if (!array_key_exists($path[$i],$arrnow)) return false;
        $arrnow=$arrnow[$path[$i]];
    } if (!$allow_empty&&$arrnow=="") return false;
    return true;
}

/**
 * 文件存在性检查函数 FindFileExist
 * @param string $path 文件路径
 * @return void
 */
function FindFileExist(string $path):void {
    if (!file_exists($path)) {
        echo Error_Controller::Common
        ("File '$path' expected, but it was not found!");
        exit;
    }
}

/**
 * 文件存在性检查函数 FindFileExist2
 * @param string $path 文件路径
 * @return bool
 */
function FindFileExist2(string $path):bool {
    return file_exists($path);
}

/** 
 * 配置检查函数 CheckConfig
 * @return void
 */
function ConfigCheck():void {
    $config=GetConfig(false);

    // Common Config Check 
    FindExist("//skip_config_check",$config,"config");
    FindExist("//version",$config,"config");
    FindExist("//extensions//js",$config,"config");
    FindExist("//extensions//css",$config,"config");

    // Web Information Check
    FindExist("//web//name",$config,"config");
    FindExist("//web//title",$config,"config");
    FindExist("//web//absolute_path",$config,"config");
    FindExist("//web//protocol",$config,"config");
    FindExist("//web//domain",$config,"config");
    FindExist("//web//icon",$config,"config");
    FindExist("//web//logo",$config,"config");
    FindExist("//web//url_rewrite",$config,"config");
    FindExist("//web//rsa_private_key",$config,"config");
    FindFileExist($config["web"]["rsa_private_key"]);
    FindExist("//web//rsa_public_key",$config,"config");
    FindFileExist($config["web"]["rsa_public_key"]);
    FindExist("//web//menu//left",$config,"config");
    FindExist("//web//timezone",$config,"config");
    if ($config["web"]["menu"]["left"]!=null) {
        for ($i=0;$i<count($config["web"]["menu"]["left"]);$i++) {
            FindExist("//web//menu//left//$i//path",$config,"config");
            FindExist("//web//menu//left//$i//param",$config,"config");
        }
    }
    FindExist("//web//menu//right",$config,"config");
    if ($config["web"]["menu"]["right"]!=null) {
        for ($i=0;$i<count($config["web"]["menu"]["right"]);$i++) {
            FindExist("//web//menu//right//$i//path",$config,"config");
            FindExist("//web//menu//right//$i//param",$config,"config");
        }
    }
    FindExist("//web//footer",$config,"config");
    if ($config["web"]["footer"]!=null) {
        for ($i=0;$i<count($config["web"]["footer"]);$i++) {
            FindExist("//web//footer//$i//title",$config,"config");
            FindExist("//web//footer//$i//url",$config,"config");
        }
    }
    FindExist("//web//lang",$config,"config");
    FindExist("//web//default-lang",$config,"config");
    FindExist("//web//lang//".$config["web"]["default-lang"],$config,"config");

    // Controller Format Check
    foreach($config["controllers"] as $key=>$controller) {
        FindExist("//controllers//$key//entrance_function",$config,"config");
        FindExist("//controllers//$key//require",$config,"config");
        FindExist("//controllers//$key//require_param",$config,"config");
        FindExist("//controllers//$key//require_config",$config,"config");
        FindExist("//controllers//$key//configs",$config,"config");
        for($i=0;$i<count($config["controllers"][$key]["require_config"]);$i++)
        FindExist("//controllers//$key//configs//".$config["controllers"][$key]["require_config"][$i],$config,"config");
        if ($config["web"]["url_rewrite"]==true) FindExist("//controllers//$key//rewrite_param",$config,"config");
    }

    // Difficulties Information Check
    FindExist("//difficulties",$config,"config",false);
    for ($i=0;$i<count($config["difficulties"]);$i++) {
        FindExist("//difficulties//$i//name",$config,"config");
        FindExist("//difficulties//$i//color",$config,"config");
    }

    // MySQL/MariaDB Information Check 
    FindExist("//mysql//server",$config,"config");
    FindExist("//mysql//port",$config,"config");
    FindExist("//mysql//user",$config,"config");
    FindExist("//mysql//passwd",$config,"config");
    FindExist("//mysql//database",$config,"config");
}

/**
 * $_GET参数检查函数 ParamCheck
 * @return void
 */
function ParamCheck():void {
    $config=GetConfig(false);
    FindExist("//web//default_path",$config,"config");
    if (!array_key_exists("path",$_GET)) $_GET["path"]=$config["web"]["default_path"];
    $tmp=$_GET["path"]; $tmp=explode("/",$tmp); $path=$_GET["path"]=$tmp[0];
    if (!in_array($path,$config["skip_config_check"])) configCheck();
    $exist=false; foreach($config["controllers"] as $key=>$value) 
    if ($key==$path) $exist=true;
    if (!$exist) {
        echo Error_Controller::Common("Unknown path '$path' in \$_GET['path']");
        exit;
    } if ($config["web"]["url_rewrite"]==true&&count($tmp)==count($config["controllers"][$path]["rewrite_param"])+1) 
        for ($i=0;$i<count($config["controllers"][$path]["rewrite_param"]);$i++) 
            $_GET[$config["controllers"][$path]["rewrite_param"][$i]]=$tmp[$i+1];
    // Necessary Parameter for Runner Function Check
    foreach ($config["controllers"][$path]["require_param"] as $key=>$value) 
        if (!FindExist2("//$key",$_GET)) $_GET[$key]=$value;
}

/**
 * HTML双Tag制造函数 InsertTags
 * @param string $tag_name tag名
 * @param array|null $property tag属性
 * @param string|null $content tag内的内容
 * @return string
 */
function InsertTags(string $tag_name,array|null $property,string|null $content):string {
    $res="<$tag_name"; if ($property!=null)
    foreach($property as $key=>$value) 
        $res.=" $key=\"".str_replace("\"","&quot",$value)."\"";
    $res.=">$content</$tag_name>"; return $res;
}

/**
 * HTML单Tag制造函数 InsertTags
 * @param string $tag_name tag名
 * @param array|null $property tag属性
 * @return string
 */
function InsertSingleTag(string $tag_name,array|null $property):string {
    $res="<$tag_name"; if ($property!=null)
    foreach($property as $key=>$value) 
        $res.=" $key=\"".str_replace("\"","&quot",$value)."\"";
    $res.="/>"; return $res; 
}

/**
 * CSS样式制造函数 InsertCssStyle
 * @param array $name CSS样式名，例:body,.main,#main,.main:hover 
 * @param array|null $property CSS样式属性
 * @return string
 */
function InsertCssStyle(array $name,array|null $property):string {
    $res=""; for ($i=0;$i<count($name)-1;$i++) 
        $res.=$name[$i].",";
    $res.=$name[count($name)-1];
    $res.="{"; if ($property!=null) 
    foreach($property as $key=>$value) 
        $res.="$key:$value;";
    $res.="}"; return $res;
}

/**
 * 行内CSS样式制造函数 InsertInlineCssStyle
 * @param array|null $property CSS样式属性
 * @return string
 */
function InsertInlineCssStyle(array|null $property):string {
    $res=""; if ($property!=null)
    foreach($property as $key=>$value) {
        $res.="$key:$value;";
    } return $res;
}

/**
 * md转html函数 md2html
 * @param string $md markdown代码
 * @param string $id html元素id
 * @return string
 */
function md2html(string $md,string $id):string {
    $config=GetConfig(); $md=str_replace("\\","\\\\",$md); 
    $md=str_replace("\n","\\n",$md); $md=str_replace("\r","",$md); $md=str_replace("'","\\'",$md);
    // echo $md; exit;
    return "editormd.markdownToHTML('$id',{markdown:'$md'});";
}

/**
 * md可视化编辑器创建函数 CreateEditor
 * @param string $md markdown代码
 * @param string $id html元素id
 * @return string
 */
function CreateEditor(string $md,string $id):string {
    $config=GetConfig(); $md=str_replace("\\","\\\\",$md); 
    $md=str_replace("\n","\\n",$md); $md=str_replace("\r","",$md); $md=str_replace("'","\\'",$md);
    return "editormd('$id',{markdown:'$md'});";
}

class Application {
    static $html,$body,$style,$others,$st;

    /**
     * html标签插入函数 InsertIntoHtml
     * @param string $code 要插入的HTML代码
     * @return void
     */
    static function InsertIntoHtml(string $code):void {
        self::$html.=$code;
    }
    
    /**
     * body标签插入函数 InsertIntoBody
     * @param string $code 要插入的HTML代码
     * @return void
     */
    static function InsertIntoBody(string $code):void {
        self::$body.=$code;
    }
    
    /**
     * CSS样式插入函数1 InsertIntoStyleCode
     * @param string $code 要插入的CSS代码
     * @return void
     */
    static function InsertIntoStyleCode(string $code):void {
        self::$style.=$code;
    }
    
    /**
     * CSS样式插入函数2 InsertIntoStyle
     * @param array $name CSS样式名
     * @param array|null $property CSS样式属性
     * @return void
     */
    static function InsertIntoStyle(array $name,array|null $property):void {
        self::$style.=InsertCssStyle($name,$property);
    }

    /**
     * 程序主函数 run
     * @param array $param 等同于$_GET
     * @return void
     */
    static function run(array $param):void {
        self::$st=microtime(true);
        $config=GetConfig(false); ParamCheck(); $path=$_GET["path"];
        $param=$_GET; unset($param["path"]);
        if ($config["web"]["install"] == 0) $path = $_GET["path"] = "config";
        self::$body=""; self::$html="";
        self::SetDefaultHtml($path,$param);
        self::SetDefaultHeader($path,$param);
        date_default_timezone_set($config["web"]["timezone"]);
        self::$others["window_onload"]="";
        if ($config["controllers"][$path]["require"]!=null) 
        for ($i=0;$i<count($config["controllers"][$path]["require"]);$i++)
            require_once $config["controllers"][$path]["require"][$i];
        $ret_body="";$ret_html="";
        $config["controllers"][$path]["entrance_function"]($param,$ret_html,$ret_body,self::$others);
        self::InsertIntoBody(InsertTags("main",array("id"=>"main"),$ret_body));
        self::InsertIntoHtml($ret_html);
        self::SetDefaultFooter($path,$param);
        if ($path!="login"&&$path!="register"&&$path!="error"&&$path!="passwd") setcookie("history",GetUrl($path,$param),time()+30*24*60*60,"/");
        self::Output();
    }

    /**
     * 默认html标签生成函数 SetDefaultHtml
     * @param string $path 等同于$_GET["path"]
     * @param array $param 等同于$_GET
     * @return void
     */
    static function SetDefaultHtml(string $path,array $param):void {
        $config=getConfig(); $lang=GetLanguage();
        $title=$lang["title"][$path]." - ".$config["web"]["title"];
        self::InsertIntoHtml(InsertTags("title",null,$title));
        self::InsertIntoHtml(InsertSingleTag("link",array("rel"=>"shortcut icon","href"=>GetRealUrl($config["web"]["icon"],null))));
        self::InsertIntoHtml(InsertTags("script",null,"var require={paths:{vs:'".GetRealUrl("./extensions/monaco-editor/min/vs",null)."'}};".
        "var ketax_config={delimiters:[{left:\"$$\",right:\"$$\",display:true},{left:\"$\",right:\"$\",display:false},]}"));
        foreach($config["extensions"]["js"] as $value) self::InsertScript(GetRealUrl($value,null));
        foreach($config["extensions"]["css"] as $value) self::InsertCssScript(GetRealUrl($value,null));
        self::InsertIntoStyleCode(
            "@media screen and (min-width: 1000px) {".
            InsertCssStyle(array("main"),array(
                "max-width"=>"940px",
                "margin"=>"auto"
            ))."}"
        ); self::InsertIntoStyleCode(
            "@media screen and (min-width: 1250px) {".
            InsertCssStyle(array("main"),array(
                "max-width"=>"1160px",
                "margin"=>"auto"
            ))."}"
        ); self::InsertIntoStyle(
            array("a"),array(
                "color"=>"green",
                "cursor"=>"pointer",
                "transition"=>"0.2s color"
            )
        ); self::InsertIntoStyle(
            array("a:link","a:visited"),array(
                "color"=>"green",
                "text-decoration"=>"none"
            )
        ); self::InsertIntoStyle(
            array("a:active"),array(
                "color"=>"red",
                "text-decoration"=>"none"
            )
        ); self::InsertIntoStyle(
            array("a:hover"),array(
                "color"=>"red!important",
                "text-decoration"=>"none"
            )
        ); self::InsertIntoStyle(
            array("body"),array(
                "background-color"=>"rgb(237,240,242)",
                "margin"=>"0px",
                "font-family"=>"Segoe UI",
                "overflow-y"=>"scroll",
                "overflow-x"=>"hidden"
            )
        ); self::InsertIntoStyle(
            array("p"),array(
                "margin"=>"0px"
            )
        ); self::InsertIntoStyle(
            array(".flex"),array(
                "display"=>"flex",
                "display"=>"-webkit-flex",
                "align-items"=>"center",
            )
        ); self::InsertIntoStyle(
            $_GET["path"]=="user"?array(".button",".button2"):array("button"),array(
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
                "border-radius"=>"3px",
                "font-weight"=>"500",
                "font-size"=>"13px",
                "background-color"=>"white",
                "cursor"=>"pointer",
                "transition"=>"background-color 0.5s,color 0.5s,border-color 0.5s",    
                "outline"=>"none"
            )
        ); self::InsertIntoStyle(
            array($_GET["path"]=="user"?".button:hover":"button:hover"),array(
                "background-color"=>"rgb(27,116,221)",
                "color"=>"white",
                "border-color"=>"rgb(27,116,221)"
            )
        ); 
        self::InsertIntoStyle(
            array("input"),array(
                "height"=>"30px",
                "line-height"=>"30px",
                "border"=>"1px solid",
                "border-color"=>"rgb(213,216,218)",
                "color"=>"rgb(27,116,221)",
                "padding-left"=>"15px",
                "padding-right"=>"15px",
                "border-radius"=>"3px",
                "font-weight"=>"500",
                "font-size"=>"13px",
                "background-color"=>"white",
                "transition"=>"border-color 0.5s",    
                "outline"=>"none",
                "flex-grow"=>"2000"
            )
        ); self::InsertIntoStyle(
            array("input:hover"),array(
                // "background-color"=>"rgb(27,116,221)",
                // "color"=>"white",
                "border-color"=>"rgb(27,116,221)"
            )
        ); self::InsertIntoStyle(
            array(".ellipsis"),array(
                "overflow"=>"hidden",
                "word-break"=>"keep-all",
                "white-space"=>"nowrap",
                "text-overflow"=>"ellipsis"
            )
        ); foreach ($config["web"]["font"] as $key=>$value) {
            self::InsertIntoStyle(array("@font-face"),array(
                "font-family"=>"'$key'",
                "src"=>"url('".GetRealUrl($value,null)."')"
            ));
        } self::InsertIntoHtml(InsertTags("script",null,"function SendAjax(url,method,data,callback='',async=false) {".
            "var res;$.ajax({url:url,type:method,data:data,async:async,success:function(message) {".
            "console.log(message);res=message;if(typeof callback=='function')callback(message);},error:function(jqXHR,textStatus,errorThrown){".
            "console.log(jqXHR.responseText);console.log(jqXHR.status);console.log(jqXHR.readyState);".
            "console.log(jqXHR.statusText);console.log(textStatus);console.log(errorThrown);res=null;}});return res;}".
            "var \$_GET=(function(){var url=window.document.location.href.toString();var u=url.split(\"?\");".
            "if(typeof(u[1])==\"string\"){u=u[1].split(\"&\");var get={};for(var i in u){var j = u[i].split(\"=\");".
            "get[j[0]]=j[1];}return get;} else {return {};}})();".
            "function strip_tags(str,allow){allow=(((allow||'')+'').toLowerCase().match(/<[a-z][a-z0-9]*>/g)||[]).join('');".
            "var tags=/<\/?([a-z][a-z0-9]*)\b[^>]*>/gi;var commentsAndPhpTags = /<!--[\s\S]*?-->|<\?(?:php)?[\s\S]*?\?>/gi;".
            "return str.replace(commentsAndPhpTags,'').replace(tags,function ($0,$1){".
            "return allow.indexOf('<'+$1.toLowerCase()+'>')>-1?$0:'';});};function addLoadEvent(func){".
            "var oldOnload=window.onload;if(typeof window.onload!='function'){window.onload=func;}else{".
            "window.onload=function(){oldOnload();func();}}}"));
    }

    /**
     * 默认页首生成函数 SetDefaultHeader
     * @param string $path 等同于$_GET["path"]
     * @param array $param 等同于$_GET
     * @return void
     */
    static function SetDefaultHeader(string $path,array $param):void {
        if ($path != "config" && $path != "error") {
            require_once "./cores/controllers/database.php";
            require_once "./cores/controllers/user.php";
            $login_controller=new Login_Controller;
            $user_controller=new User_Controller;
        } $config=GetConfig(); $lang=GetLanguage();
        // self::InsertIntoStyle(array("body"),array("filter"=>"grayscale(100%)"));
        self::InsertIntoStyle(
            array(".menu"),array(
                "width"=>"100%",
                "background-color"=>"white",
                "height"=>"53px",
                "position"=>"fixed",
                "top"=>"0px",
                "display"=>"flex",
                "justify-content"=>"space-around",
                "box-shadow"=>"0 0.375rem 1.375rem rgb(175 194 201 / 50%)",
                "z-index"=>1000
            )
        ); self::InsertIntoStyle(
            array(".menu-item"),array(
                "height"=>"50px",
                "padding-left"=>"10px",
                "padding-right"=>"10px",
                "border"=>"0px solid",
                "border-top-width"=>"3px",
                "border-color"=>"white",
                "cursor"=>"pointer",
                "text-align"=>"center",
                "transition"=>"border-color 0.5s",
                "z-index"=>2000,
                "margin-left"=>"5px"
            )
        ); self::InsertIntoStyle(
            array(".menu-item:hover"),array(
                "border-color"=>"orange"
            )
        ); self::InsertIntoStyle(
            array(".selected"),array(
                "border-color"=>"orange"
            )
        ); $content="";
        $content.=InsertTags("div",array("class"=>"flex",
        "onclick"=>"location.href='".GetUrl("index",null)."'"),
            InsertSingleTag("img",array(
                "src"=>GetRealUrl($config["web"]["logo"],null),
                "style"=>InsertInlineCssStyle(array(
                    "height"=>"32px",
                    "cursor"=>"pointer",
                    "margin-top"=>"3px"
                ))
            )).InsertTags("hp",array("style"=>InsertInlineCssStyle(array(
                "font-family"=>"'Exo 2'",
                "line-height"=>"32px",
                "cursor"=>"pointer"
            ))),"&nbsp;".$config["web"]["name"])
        );
        if ($config["web"]["menu"]["left"]!=null) 
        for($i=0;$i<count($config["web"]["menu"]["left"]);$i++)
            $content.=InsertTags("div",array(
                "class"=>"menu-item flex".($path==$config["web"]["menu"]["left"][$i]["path"]?" selected":""),
                "onclick"=>"location.href='".
                    GetUrl($config["web"]["menu"]["left"][$i]["path"],
                        $config["web"]["menu"]["left"][$i]["param"]
                    )."'",
            ),$lang["path"][$config["web"]["menu"]["left"][$i]["path"]]);
        if ($path != "error" && $path != "config") {
            $uid=$login_controller->CheckLogin(); $p=0; 
            if ($uid&&$user_controller->GetWholeUserInfo($uid)["permission"]>1) $p=2;
            if ($p==2) {
                $content.=InsertTags("div",array(
                    "class"=>"menu-item flex".($path=="admin"?" selected":""),
                    "onclick"=>"location.href='".GetUrl("admin",array())."'",
                ),$lang["path"]["admin"]);
            }
        }
        $content_left=InsertTags("div",array("style"=>InsertInlineCssStyle(array(
            "display"=>"flex"
        ))),$content); $content="";
        if ($config["web"]["menu"]["right"]!=null) {
            if ($path == "error" || $path == "config") $uid = 0;
            else $uid=$login_controller->CheckLogin();
            if (!$uid) for($i=0;$i<count($config["web"]["menu"]["right"]);$i++)
                $content.=InsertTags("div",array(
                    "class"=>"menu-item flex".($path==$config["web"]["menu"]["right"][$i]["path"]?" selected":""),
                    "onclick"=>"location.href='".
                        GetUrl($config["web"]["menu"]["right"][$i]["path"],
                            $config["web"]["menu"]["right"][$i]["param"]
                        )."'",
                ),$lang["path"][$config["web"]["menu"]["right"][$i]["path"]]);
            else {
                $user_controller=new User_Controller;
                $user=$user_controller->GetWholeUserInfo($uid);
                $content.=InsertTags("div",array("class"=>"menu-item".($path=="user"?" selected":""),
                "style"=>InsertInlineCssStyle(array("width"=>"auto"))),
                    InsertTags("p",array(
                        "onclick"=>"location.href='".
                        GetUrl("user",array("id"=>$uid))."'",
                        "style"=>InsertInlineCssStyle(array(
                            "line-height"=>"50px",
                            "padding-left"=>"5px",
                            "padding-right"=>"5px"
                        ))
                    ),$user["name"])
                );
                $content.=InsertTags("div",array(
                    "class"=>"menu-item flex",
                    "onclick"=>"location.href='".GetUrl("login",array("logout"=>1))."'",
                ),$lang["path"]["logout"]);
            } $options=""; if (!array_key_exists("language",$_COOKIE)) {
                setcookie("language",$config["web"]["default-lang"],time()+1000*365*24*60*60,"/");
                $_COOKIE["language"]=$config["web"]["default-lang"];
            } foreach($config["web"]["lang"] as $key => $value) 
                if ($key!=$_COOKIE["language"]) $options.=InsertTags("option",array("value"=>$key),$key);
                else $options.=InsertTags("option",array("value"=>$key,"selected"=>"selected"),$key);
            $content.=InsertTags("div",array("class"=>"menu-item",
                "style"=>InsertInlineCssStyle(array("width"=>"auto"))),
                InsertTags("p",array(
                    "style"=>InsertInlineCssStyle(array(
                        "line-height"=>"50px",
                        "padding-left"=>"5px",
                        "padding-right"=>"5px"
                    ))
                ),InsertTags("i",array("class"=>"globe icon"),"").
                InsertTags("select",array("id"=>"lang","style"=>InsertInlineCssStyle(array("position"=>"relative","top"=>"-1.6px"))),$options))
            );
        }
        $content=$content_left.InsertTags("div",array("style"=>InsertInlineCssStyle(array(
            "display"=>"flex"
        ))),$content);
        self::InsertIntoBody(InsertTags("div",array("class"=>"menu"),$content));
        self::InsertIntoStyle(
            array("main"),array(
                "min-height"=>"800px",
                "margin-top"=>"80px",
                "margin-bottom"=>"30px"
            )
        );
        self::InsertIntoStyle(array(".default_main"),array(
            "background-color"=>"white",
            "box-shadow"=>"0 0.375rem 1.375rem rgb(175 194 201 / 50%)",
            "opacity"=>0,
            "position"=>"relative",
            "top"=>"50px"
        ));
        self::InsertIntoStyle(array(".default_main2"),array(
            "background-color"=>"white",
            "box-shadow"=>"0 0.375rem 1.375rem rgb(175 194 201 / 50%)",
        ));
        self::InsertIntoStyle(array(".a"),array(
            "color"=>"black!important"
        ));
        $script="function setCookie(name,value,expire) {";
        $script.="var exp=new Date(); exp.setTime(exp.getTime()+expire*1000);";
        $script.="document.cookie=name+\"=\"+escape(value)+\";expires=\"+exp.toGMTString()+\";path=/;\";}";
        $script.="var lang=document.getElementById('lang');";
        $script.="lang.onchange=function(){setCookie('language',lang.value,1000*365*24*60*60);location.href=location.href;}";
        self::InsertIntoBody(InsertTags("script",null,$script));
    }

    /**
     * 默认页尾生成函数 SetDefaultFooter
     * @param string $path 等同于$_GET["path"]
     * @param array $param 等同于$_GET
     * @return void
     */
    static function SetDefaultFooter(string $path,array $param):void {
        $config=GetConfig();
        self::InsertIntoStyle(
            array(".footer"),array(
                "background-color"=>"rgba(255,255,255)",
                "display"=>"flex",
                "justify-content"=>"center",
                "flex-wrap"=>"wrap",
                "width"=>"100%",
                "padding-top"=>"30px",
                "padding-bottom"=>"20px",
            )
        ); self::InsertIntoStyle(
            array(".copyright"),array(
                "background-color"=>"rgba(255,255,255)",
                "width"=>"100%",
                "display"=>"flex",
                "display"=>"-webkit-flex",
                "justify-content"=>"center",
                "padding-top"=>"5px",
                "padding-bottom"=>"15px",
            )
        ); self::InsertIntoStyle(
            array("hp"),array(
                "font-size"=>"20px",
                "padding-top"=>"10px",
                "padding-bottom"=>"10px"
            )
        ); $content=""; if ($config["web"]["footer"]!=null)
        for ($i=0;$i<count($config["web"]["footer"]);$i++) {
            $now_content=InsertTags("hp",null,$config["web"]["footer"][$i]["title"]);
            $now_content.=InsertSingleTag("br",null);
            foreach($config["web"]["footer"][$i]["url"] as $key=>$value) {
                $now_content.=InsertTags("a",array("href"=>$value,"target"=>"view_window"),$key).InsertSingleTag("br",null);
            } $content.=InsertTags("div",array("style"=>InsertInlineCssStyle(array(
                "margin-right"=>(count($config["web"]["footer"])!=$i+1?"100px":"0px"),
                "display"=>"inline-block"
            ))),$now_content);
        }
        self::InsertIntoBody(InsertTags("div",array("class"=>"footer"),$content));
        $content="© 2022 - ".date("Y",time())." ".InsertTags("a",array("href"=>"https://github.com/LittleYang0531","target"=>"view_window"),"LittleYang0531").", ";
        $content.="Powered by ".InsertTags("a",array("href"=>"https://github.com/LittleYang0531/lyoj","target"=>"view_window"),"lyoj v".$config["version"]).". ";
        $content.="Calculate this page use ".InsertTags("a",null,round((microtime(true)-self::$st)*1000,0))."ms. ";
        self::InsertIntoBody(InsertTags("div",array("class"=>"copyright"),InsertTags("p",null,$content)));
        self::InsertIntoBody(InsertTags("div",array("style"=>InsertInlineCssStyle(array("display"=>"none")),"id"=>"md2html"),""));
        self::InsertIntoBody(InsertTags("script",null,"var default_main=document.getElementsByClassName('default_main');".
        ($_GET["path"]=="user"?"":"addLoadEvent(function(){")."setTimeout(function(){window.scrollTo(0,window.scrollY-50);},5);".
        "for (var i=0;i<default_main.length;i++) for (var j=1;j<=100;j++)".
        "setTimeout(function(div,j){div.style.opacity=j/100.0;div.style.top=(50+50*Math.cos(Math.PI/200*j+Math.PI/2))+'px';},5*j+i*50,default_main[i],j);".
        ($_GET["path"]=="user"?"":"});")));
    }

    /**
     * 外置Script脚本插入函数 InsertScript
     * @param string $script_url Script脚本地址
     * @return void
     */
    static function InsertScript(string $script_url):void {
        self::InsertIntoHtml(InsertTags("script",array("src"=>$script_url),""));
    }

    /**
     * 外置CSS脚本插入函数 InsertCssScript
     * @param string $script_url CSS脚本地址
     * @return void
     */
    static function InsertCssScript(string $script_url):void {
        self::InsertIntoHtml(InsertSingleTag("link",array("rel"=>"stylesheet","type"=>"text/css","href"=>$script_url)));
    }

    /**
     * 页面输出函数 Output
     * @return void
     */
    static function Output():void {
        self::InsertIntoHtml(InsertTags("style",null,self::$style));
        echo "<!DOCTYPE html>";
        echo InsertTags("html",null,self::$html);
        echo InsertTags("body",null,self::$body);
    }
}
?>