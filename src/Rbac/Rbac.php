<?php
/**
 * Created by PhpStorm.
 * User: rebirth.huang
 * Date: 2018/9/11
 * Time: 10:34
 */

require_once("models/Permission.php");
require_once("common/Mysqli.php");
class Rbac
{
    private $permissionObj = null;
    const ADMIN_UID = 1;

    /**
     * Rbac constructor.
     * @param $host
     * @param $root
     * @param $password
     * @param $dbName
     * @param $port
     * @throws Exception
     */
    public function  __construct($host,$root,$password,$dbName,$port)
    {
        try{
            $db = new DB($host, $root, $password, $dbName,$port);
        }catch (Exception $e){
            throw new Exception("connect DB error");
        }
        $this->permissionObj = new Permission($db);
    }

    /**
     * 验证用户权限
     * @param $uid int 用户UID
     * @param $router string 访问的路由
     * @return bool
     */
    public function check($uid,$router)
    {
        if($uid == self::ADMIN_UID){  //超级管理员
            return true;
        }
        $router = str_replace('//', '/', $router);
        $permission = $this->permissionObj->permissionListByUid($uid);
        $hasPriv = false;
        if (!empty($permission)) {
            foreach ($permission as $v) {
                if ($router == $v['url']) {
                    $hasPriv = true;
                    break;
                }
            }
        }
        return $hasPriv;
    }

    /**
     * 用户权限列表获取
     * @param $uid
     * @return array
     */
    public function permissionList($uid)
    {
        if($uid == self::ADMIN_UID){
            $list = $this->permissionObj->allPermissionList();
        }else{
            $list = $this->permissionObj->permissionListByUid($uid);
        }
        return $list;
    }

    /**
     * 权限节点添加
     * @param $name
     * @param $description
     * @param $router
     * @return bool
     */
    public function addPermission($name,$description,$router)
    {
        if(empty($name) || empty($description) || empty($router)){
            return false;
        }
        return $this->permissionObj->addPermission($name,$description,$router);
    }

    /** 添加角色
     * @param $name
     * @return bool
     */
    public function addRole($name)
    {
        if(empty($name)){
            return false;
        }
        return $this->permissionObj->addRole($name);
    }

    /**
     * 添加用户角色
     * @param $uid
     * @param $roleId
     * @return bool
     */
    public function addUserRole($uid,$roleId)
    {
        if(empty($uid) || empty($roleId)){
            return false;
        }
        return $this->permissionObj->addUserRole($uid,$roleId);
    }

    /**
     * 添加角色权限
     * @param $roleId
     * @param $permissionId
     * @return bool
     */
    public function addRolePermission($roleId,$permissionId)
    {
        if(empty($roleId) ||  empty($permissionId)){
            return false;
        }
        return $this->addRolePermission($roleId,$permissionId);
    }

    /**
     * 获取用户角色信息
     * @param $uid
     * @return array
     */
    public function roleListByUid($uid)
    {
        if(empty($uid)){
            return [];
        }
        return $this->roleListByUid($uid);
    }
}
