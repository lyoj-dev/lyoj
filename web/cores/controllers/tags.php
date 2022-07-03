<?php
class Tags_Controller {
    static $db;
    function __construct() {
        self::$db=new Database_Controller;
    }

    /**
     * 以pid列举Tag信息 ListProblemTagsByPid
     * @param int $pid 题目id
     * @return array|null
     */
    static function ListProblemTagsByPid(int $pid):array|null {
        Problem_Controller::JudgeProblemExist($pid);
        $array=self::$db->Query("SELECT * FROM tags WHERE id=$pid AND type='problem'");
        $res=array(); for ($i=0;$i<count($array);$i++) $res[]=$array[$i]["tagname"];
        $res=array_unique($res); $res=array_values($res); sort($res);
        return $res;
    }

    /**
     * 获取题目Tag信息 ListProblemTag
     * @return array|null
     */
    static function ListProblemTag():array|null {
        $array=self::$db->Query("SELECT tagname FROM tags WHERE type='problem'");
        $res=array(); for ($i=0;$i<count($array);$i++) $res[]=$array[$i]["tagname"];
        $res=array_unique($res); $res=array_values($res);
        sort($res);
        return $res; 
    }

    /**
     * 以id列举比赛Tag信息 GetContestTagsById
     * @param int $id 比赛id
     * @return array|null
     */
    function ListContestTagsById(int $id):array|null {
        Contest_Controller::JudgeContestExist($id);
        $array=self::$db->Query("SELECT tagname FROM tags WHERE type='contest' AND id=$id");
        return $array;
    }
}
?>