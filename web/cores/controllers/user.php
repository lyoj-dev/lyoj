<?php
class Login_Controller {
    static $db,$user_controller;
    function __construct() {
        self::$db=new Database_Controller;
        self::$user_controller=new User_Controller;
    }

    /**
     * 核验登录态 CheckLogin
     * @return int
     */
    static function CheckLogin():int {
        $config=GetConfig();
        $uid=$_COOKIE["DedeUserID"];
        if (md5($uid)!=$_COOKIE["DedeUserID__ckMd5"]) return false;
        $csrf_token=$_COOKIE["CSRF_TOKEN"]; $sessdata=$_COOKIE["SESSDATA"];
        $arr=self::$db->Query("SELECT * FROM user WHERE id=$uid");
        if (!count($arr)) return false;
        $arr=self::$db->Query("SELECT * FROM logindata WHERE uid=$uid AND csrf='$csrf_token' AND sessdata='$sessdata'");
        if (!count($arr)) return false;
        for ($i=0;$i<count($arr);$i++) if (time()-$arr[$i]["time"]<=$config["web"]["cookie_time"]) return $uid;
        return false;
    }

    /**
     * 获取当前登录账号id GetLoginID
     * @return int
     */
    static function GetLoginID():int {
        if (!self::CheckLogin()) return 0;
        else return $_COOKIE["DedeUserID"];
    }

    /**
     * 获取盐值 UserLoginSalt
     * @param string $email 用户邮箱
     * @return string
     */
    static function UserLoginSalt(string $email):string {
        $uid=self::$user_controller->GetEmailId($email);
        if ($uid==0) return "";
        $salt=bin2hex(openssl_random_pseudo_bytes(5));
        $user_array=self::$db->Query("SELECT * FROM user WHERE id=$uid")[0];
        self::$db->Query("UPDATE user SET salt='$salt' WHERE id=$uid");
        self::$db->Query("UPDATE user SET salttime=".time()." WHERE id=$uid");
        return $salt;
    }

    /**
     * 获取公钥 UserLoginPublicKey
     * @return string
     */
    static function UserLoginPublicKey():string {
        $config=GetConfig();
        $fp=fopen($config["web"]["rsa_public_key"],"r");
        $public=fread($fp,filesize($config["web"]["rsa_public_key"]));
        fclose($fp); return $public;
    }

    /**
     * 获取私钥 UserLoginPrivateKey
     * @return string
     */
    static function UserLoginPrivateKey():string {
        $config=GetConfig();
        $fp=fopen($config["web"]["rsa_private_key"],"r");
        $private=fread($fp,filesize($config["web"]["rsa_private_key"]));
        fclose($fp); return $private;
    }

    /**
     * 用户密码登录并返回用户id UserLoginPasswd
     * @param string $email 用户邮箱
     * @param string $passwd 用户密码
     * @param &$cookie 返回cookie数据
     * @return int
     */
    static function UserLoginPasswd(string $email,string $passwd,&$cookie):int {
        $config=GetConfig();
        $private_key=self::UserLoginPrivateKey();
        if ($private_key==null) return -1;
        $uid=self::$user_controller->GetEmailId($email);
        if (!$uid) return -1;
        $array=self::$db->Query("SELECT * FROM user WHERE id=$uid");
        if ($array==null) return -1; $pass=null;
        $pass=openssl_private_decrypt(base64_decode($passwd),$pass,$private_key)?$pass:null;
        if ($pass==null) return -1;
        $csrf_token=bin2hex(openssl_random_pseudo_bytes(10));
        $sessdata=bin2hex(openssl_random_pseudo_bytes(10));
        self::$db->Query("INSERT INTO logindata (uid,csrf,sessdata,time) VALUES ($uid,'$csrf_token','$sessdata',".time().")");
        if ($array[0]["verify"]!=1) return -3;
        if ($array[0]["passwd"].$array[0]["salt"]==$pass) {
            if (time()-$array[0]["salttime"]>20) return -2;
            $cookie=array("DedeUserID"=>$uid,"DedeUserID__ckMd5"=>md5($uid),
            "CSRF_TOKEN"=>$csrf_token,"SESSDATA"=>$sessdata);
            return $uid;
        } else return 0;
    }

    /**
     * 注册用户 UserRegister
     * @param string $name 用户名
     * @param string $email 用户邮箱
     * @param string $passwd 用户密码
     * @return int
     */
    static function UserRegister(string $name,string $email,string $passwd):int {
        $private_key=self::UserLoginPrivateKey();
        if ($private_key==null) return -1;
        $uuid=self::$user_controller->GetEmailId($email);
        if ($uuid) return -2; $pass=null;
        $user=self::$db->Query("SELECT id FROM user WHERE name='$name'");
        if (count($user)) return -3;
        $user=self::$db->Query("SELECT id FROM user ORDER BY id DESC"); $uid=(count($user)==0?0:$user[0]["id"])+1;
        $pass=openssl_private_decrypt(base64_decode($passwd),$pass,$private_key)?$pass:null;
        $code=bin2hex(openssl_random_pseudo_bytes(50));
        $config=GetConfig(); 
        $fp=fopen($config["email"]["email_register_content"],"r");
        $content=fread($fp,filesize($config["email"]["email_register_content"]));
        $link=GetHTTPUrl("register",array("uid"=>$uid,"code"=>$code));
        $content=str_replace("\$\$name\$\$",$name,$content);
        $content=str_replace("\$\$email\$\$",$email,$content);
        $content=str_replace("\$\$link\$\$",$link,$content);
        Email_Controller::SendEmail(array($email),"Email verify in ".$config["web"]["name"],$content);
        self::$db->Execute("INSERT INTO user (id,name,passwd,email,permission,verify,verify_code) VALUES ($uid,'$name','$pass','$email',1,0,'$code')");
        return $uid;
    }

    /**
     * 用户邮箱验证 UserEmailVerify
     * @param string $id 用户id代码
     * @param string $code 邮箱代码
     * @return void
     */
    static function UserEmailVerify(string $id,string $code):void {
        $array=self::$db->Query("SELECT * FROM user WHERE id=$id");
        if (count($array)==0) Error_Controller::Common("User ID $id is not exist!");
        if ($array[0]["verify_code"]!=$code) Error_Controller::Common("User and code is not matched!");
        self::$db->Execute("UPDATE user SET verify=true WHERE id=$id"); 
        mkdir("./data/user/$id",0777);
        copy("./data/user/default/header.jpg","./data/user/$id/header.jpg");
        copy("./data/user/default/background.jpg","./data/user/$id/background.jpg");
        copy("./data/user/default/intro.md","./data/user/$id/intro.md");
        $config=GetConfig(); 
        $fp=fopen($config["email_content"],"r");
    }

    /**
     * 创建密码修改请求 CreateToken
     * @param string $email 用户邮箱
     * @return void
     */
    static function CreateToken(string $email):void {
        $uid=self::$user_controller->GetEmailId($email);
        if ($uid==0) API_Controller::error_email_not_exist($email);
        $token=bin2hex(openssl_random_pseudo_bytes(50));
        self::$db->Execute("UPDATE user SET passwd_token='$token' WHERE id=$uid");
        $config=GetConfig(); $user=self::$db->Query("SELECT * FROM user WHERE id=$uid")[0];
        $fp=fopen($config["email"]["email_passwd_content"],"r");
        $content=fread($fp,filesize($config["email"]["email_passwd_content"]));
        $link=GetHTTPUrl("passwd",array("email"=>$email,"token"=>$token));
        $content=str_replace("\$\$name\$\$",$user["name"],$content);
        $content=str_replace("\$\$email\$\$",$user["email"],$content);
        $content=str_replace("\$\$link\$\$",$link,$content);
        Email_Controller::SendEmail(array($email),"Password update in ".$config["web"]["name"],$content);
    }

    /**
     * 验证用户密码修改请求 CheckToken
     * @param string $email 用户邮箱
     * @param string $token 用户请求 Token
     * @return bool
     */
    static function CheckToken(string $email,string $token):bool {
        $arr=self::$db->Query("SELECT * FROM user WHERE email='$email' AND passwd_token='$token'");
        if (count($arr)==0) return false;
        return true;
    }

    /**
     * 用户密码修改 ChangePassword
     * @param string $email 用户邮箱
     * @param string $token 用户请求 Token
     * @param string $passwd 新密码
     * @return void
     */
    static function ChangePassword(string $email,string $token,string $passwd):void {
        $arr=self::$db->Query("SELECT * FROM user WHERE email='$email' AND passwd_token='$token'");
        if (count($arr)==0) API_Controller::error_invalid_token();
        $private_key=self::UserLoginPrivateKey();
        $pass=openssl_private_decrypt(base64_decode($passwd),$pass,$private_key)?$pass:null;
        $uid=self::$user_controller->GetEmailId($email);
        self::$db->Execute("UPDATE user SET passwd='$pass',passwd_token='NULL' WHERE id=$uid");
        self::$db->Execute("DELETE FROM logindata WHERE uid=$uid");
    }
}

class User_Controller {
    static $db;
    function __construct() {
        self::$db=new Database_Controller;
    }

    /**
     * 判断用户是否存在 JudgeUserExist
     * @param int $id 用户id
     * @return void
     */
    static function JudgeUserExist(int $id):void {
        $p=self::$db->Query("SELECT id FROM user WHERE id=$id");
        if (!count($p)) {
            $url=$_SERVER['REQUEST_URI'];
            $path=explode("/",$url);
            $api_mode=$path[1]=="api";
            if ($api_mode) API_Controller::error_user_not_found($id);
            else Error_Controller::Common("User id $id not found");
        }
    }

    /**
     * 获取用户邮箱对应id GetEmailId
     * @param string $email
     * @return int
     */
    static function GetEmailId(string $email):int {
        $array=self::$db->Query("SELECT * FROM user WHERE email='$email'");
        if ($array==null) return 0;
        return $array[0]["id"];
    }

    /**
     * 输出api用户信息 OutputAPIInfo
     * @param int $uid 用户id
     * @return array|null
     */
    static function OutputAPIInfo(int $uid):array|null {
        User_Controller::JudgeUserExist($uid);
        $array=self::$db->Query("SELECT * FROM user WHERE id=$uid");
        if ($array==null) return null;
        return array("uid"=>$array[0]["id"],"name"=>$array[0]["name"],
        "title"=>$array[0]["title"]);
    }

    /**
     * 输出完整用户信息 GetWholeUserInfo
     * @param int $uid 用户id
     * @return array|null
     */
    static function GetWholeUserInfo(int $uid):array|null {
        User_Controller::JudgeUserExist($uid);
        $array=self::$db->Query("SELECT * FROM user WHERE id=$uid");
        if ($array==null) return null;
        $array=$array[0];  unset($array["salt"]); unset($array["verify_code"]);
        unset($array["salttime"]); unset($array["passwd"]);
        return $array;
    }

    /**
     * 上传用户头像 UploadHeader
     * @param int $uid 用户id
     * @param string $data 用户头像base64
     * @return void
     */
    static function UploadHeader(int $uid,string $data):void {
        User_Controller::JudgeUserExist($uid);
        $file_content=base64_decode($data);
        $fp=fopen("../../data/user/$uid/header.jpg","w");
        fwrite($fp,$file_content); 
        fclose($fp);
    }

    /**
     * 上传用户空间头图 UploadBackground
     * @param int $uid 用户id
     * @param string $data 空间头图base64
     * @return void
     */
    static function UploadBackground(int $uid,string $data):void {
        User_Controller::JudgeUserExist($uid);
        $file_content=base64_decode($data);
        $fp=fopen("../../data/user/$uid/background.jpg","w");
        fwrite($fp,$file_content); 
        fclose($fp);
    }

    /**
     * 更新用户信息 UpdateInfo
     * @param int $uid 用户id
     * @param string $intro 用户个人介绍
     * @return array
     */
    static function UpdateInfo(int $uid,string $intro):array {
        User_Controller::JudgeUserExist($uid);
        $fp=fopen("../../data/user/$uid/intro.md","w");
        fwrite($fp,$intro);
        fclose($fp);
        return array("uid"=>$uid,"intro"=>$intro);
    }

    /**
     * 搜索用户 SearchUser
     * @param string $name="" 用户名信息
     * @param int $l 左端点
     * @param int $r 右端点
     * @param int& $sum 查询结果总数
     * @return array|null
     */
    static function SearchUser(string $name="",int $l,int $r,int& $sum):array|null {
        $sql="SELECT * FROM user"; if ($name!="") $sql.=" WHERE name LIKE '%$name%'";
        $array=self::$db->Query($sql);
        $sum=count($array); $res=array();
        for ($i=$l-1;$i<count($array);$i++) {
            if ($i>=$r) break;
            $res[]=$array[$i];
        } return $res;
    }
    
    /**
     * 封禁用户 BanUser
     * @param int $uid 用户 id
     * @return void
     */
    static function BanUser(int $uid):void {
        User_Controller::JudgeUserExist($uid);
        $arr=self::$db->Query("SELECT * FROM user WHERE id=$uid")[0];
        $banned=1-$arr["banned"];
        self::$db->Execute("UPDATE user SET banned=$banned WHERE id=$uid");
    }

    /**
     * 删除目录下所有文件 deldir
     * @param string $path 文件夹目录
     * @return mixed
     */
    static function deldir(string $path) {
        if(is_dir($path)){
            $p=scandir($path);
            if(count($p)>2) foreach($p as $val) if($val!="."&&$val!="..")
                if(is_dir($path.$val)) self::deldir($path.$val.'/');
                else unlink($path.$val);
        } return rmdir($path);
    }

    /**
     * 删除用户 DeleteUser
     * @param int $uid 用户 id
     * @return void
     */
    static function DeleteUser(int $uid):void {
        User_Controller::JudgeUserExist($uid);
        unlink("../../data/user/$uid/header.jpg");
        unlink("../../data/user/$uid/background.jpg");
        unlink("../../data/user/$uid/intro.md");
        rmdir("../../data/user/$uid/");
        self::$db->Execute("DELETE FROM user WHERE id=$uid");
    }

    /**
     * 创建用户 CreateUser
     * @param string $name 用户名
     * @param string $email 用户邮箱
     * @param string $passwd 用户密码
     * @return array|null
     */
    static function CreateUser(string $name,string $email,string $passwd):array|null { 
        $uuid=self::GetEmailId($email);
        if ($uuid) API_Controller::error_email_used($email);
        $user=self::$db->Query("SELECT id FROM user WHERE name='$name'");
        if (count($user)) API_Controller::error_username_used($name);
        $id=self::$db->Query("SELECT id FROM user ORDER BY id DESC LIMIT 1")[0]["id"]+1;
        $private_key=Login_Controller::UserLoginPrivateKey();
        if ($private_key==null) return -1;
        $pass=openssl_private_decrypt(base64_decode($passwd),$pass,$private_key)?$pass:null;
        self::$db->Execute("INSERT INTO user (id,name,passwd,title,permission,email,verify,rating,uptime,banned)".
            " VALUES ($id,'$name','$pass','',1,'$email',1,0,0,0)");
        mkdir("../../data/user/$id",0777);
        copy("../../data/user/default/header.jpg","../../data/user/$id/header.jpg");
        copy("../../data/user/default/background.jpg","../../data/user/$id/background.jpg");
        copy("../../data/user/default/intro.md","../../data/user/$id/intro.md");
        return self::$db->Query("SELECT * FROM user WHERE id=$id")[0];
    }
}
?>