<?php
class Error_Controller {
    /**
     * 错误抛出函数 Common
     * @param string $err 错误描述
     * @param int $code 错误码
     * @param bool $api_mode 是否为api模式
     * @return string
     */
    static function Common(string $err,int $code=0,bool $api_mode=false):string {
        if (!$api_mode) {
            echo InsertTags("script",null,"window.location.href='".
            str_replace("'","\\'",GetUrl("error",array("err"=>$err)))."'");  
            exit;
        } 
        $data=array("code"=>$code,"message"=>$err,"ttl"=>1);
		echo "<pre style='word-wrap: break-word;white-space: pre-wrap;'>".json_encode($data, JSON_UNESCAPED_UNICODE)."</pre>";
        exit;
    } 
}
?>