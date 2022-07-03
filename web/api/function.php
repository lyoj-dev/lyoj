<?php
/**
 * API参数检查函数 CheckParam
 * @param array $param 参数名
 * @param array $source 数组
 * @return void
 */
function CheckParam(array $param,array $source):void {
    $api_controller=new API_Controller;
    for ($i=0;$i<count($param);$i++) 
        if (!array_key_exists($param[$i],$source)) 
            $api_controller->error_param_not_found($param[$i]);
}

/**
 * URL获取函数 GetUrl
 * @param string $path  $_GET["path"]里的值
 * @param array|null $param 新页面中所有GET的参数
 * @return string
 */
function GetUrl(string $path,array|null $param):string {
    $config=GetConfig(); $res="";
    if (!$config["web"]["absolute_path"]) $res="./";
    else $res=$config["web"]["protocol"]."://".$config["web"]["domain"]."/";
    $res.=(!$config["web"]["url_rewrite"]?"index.php?path=$path&":"$path?");
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
 * 绝对URL获取函数 GetRealUrl
 * @param string $path  文件相对路径
 * @param array|null $param 新页面中所有GET的参数
 * @return string
 */
function GetRealUrl(string $path,array|null $param):string {
    $config=GetConfig();
    if (!$config["web"]["absolute_path"]) $res="./";
    else $res=$config["web"]["protocol"]."://".$config["web"]["domain"]."/";
    $res.=$path."?"; 
    if ($param!=null) 
    foreach ($param as $key=>$value) $res.="$key=$value&";
    return substr($res,0,strlen($res)-1);
}

/**
 * API URL获取函数 GetAPIUrl
 * @param string $path
 */
function GetAPIUrl(string $path):string {
    $config=GetConfig();
    if (!$config["web"]["absolute_path"]) $res="./api";
    else $res=$config["web"]["protocol"]."://".$config["web"]["domain"]."/api";
    $res.=$path.".php"; return $res;
}

/**
 * 程序配置获取函数 GetConfig
 * @param bool $merge=true 是否合并所有配置
 * @return array
 */
function GetConfig(bool $merge=true):array {
    require_once "../../config.php";
    global $config;
    if ($merge) foreach ($config["controllers"] as $key=>$value) {
        $config=array_merge($config,$config["controllers"][$key]["configs"]);
        // print_r($config["controllers"][$key]["configs"]);
    } // exit;
    return $config;
}
?>