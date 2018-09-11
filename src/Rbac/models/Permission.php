<?php
/**
 * Created by PhpStorm.
 * User: rebirth.huang
 * Date: 2018/9/10
 * Time: 20:31
 */
require_once("../common/Utils.php");

class Permission
{
    public $db = null;

    /**
     * 初始化
     * Permission constructor.
     * @param $db
     * @throws Exception
     */
    public function __construct($db)
    {
        if(!empty($db) && is_object($db)){
            $this->db = $db;
        }else{
            throw new Exception("RBAC db is not object,please check your parameters");
        }
    }

    /**
     * 获取用户的权限列表
     * @param $uid int 用户UID
     * @return array
     */
    public function permissionListByUid($uid)
    {
        $uid = intval($uid);
        //获取用户的角色列表
        $where = "ur.uid={$uid}";
        $tables=$this->db->JoinTables('permission as p',array('role_permission AS rp','p.id=rp.permission_id'),array('user_role as ur','rp.role_id=ur.role_id'));
        $list = $this->db->GetRow($tables,'p.name,p.description,p.url',$where,'','p.create_time desc');
        return $list ? $list : [];
    }

    /**
     * 获取所有的权限节点
     * @return array
     */
    public function allPermissionList()
    {
        $list = $this->db->GetRow("permission",'name,description,url',"",'','p.create_time desc');
        return $list ? $list : [];
    }

    /**
     *  权限节点添加
     * @param $name
     * @param $description
     * @param $router
     * @return bool
     */
    public function addPermission($name,$description,$router)
    {
        $name = Utils::html($name);
        $description = Utils::html($description);
        $router = Utils::html($router);
        $time = time();
        $res = $this->db->InsertRow('permission','name,description,url,create_time',"'{$name}','{$description}','{$router}','{$time}'");
        return boolval($res);
    }

    /**
     * 添加角色权限节点
     * @param $roleId
     * @param $permissionId
     * @return bool
     */
    public function addRolePermission($roleId,$permissionId)
    {
        $roleId = intval($roleId);
        $permissionId = intval($permissionId);
        $time = time();
        $res = $this->db->InsertRow('role_permission','role_id,permission_id,create_time',"'{$roleId}','{$permissionId}','{$time}','{$time}'");
        return boolval($res);
    }

    /**
     * 添加角色
     * @param $name
     * @return bool
     */
    public function addRole($name)
    {
        $name = Utils::html($name);
        $time = time();
        $res = $this->db->InsertRow('role','name,create_time',"'{$name}','{$time}'");
        return boolval($res);
    }

    /**
     * 添加用户角色
     * @param $uid
     * @param $roleId
     * @return bool
     */
    public function addUserRole($uid,$roleId)
    {
        $uid = intval($uid);
        $roleId = intval($roleId);
        $time = time();
        $res = $this->db->InsertRow('user_role','uid,role_id,create_time',"'{$uid}','{$roleId}','{$time}'");
        return boolval($res);
    }


    /**
     * 用户角色信息获取
     * @param $uid
     * @return array
     */
    public function roleListByUid($uid)
    {
        $uid = intval($uid);
        $swhere = "ur.uid={$uid}";
        $tables=$this->db->JoinTables('user_role as ur',array('role AS r','ur.role_id=r.id'));
        $list = $this->db->GetRow($tables,'r.id,r.name,r.create_time',$swhere,'','r.create_time desc');
        return $list ? $list : [];
    }
}
