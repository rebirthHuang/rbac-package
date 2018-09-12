<?php
/**
 * Created by PhpStorm.
 * User: rebirth.huang
 * Date: 2018/9/10
 * Time: 20:31
 */

namespace Rbac\models;

use Rbac\common\Utils;

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
            throw new \Exception("RBAC db is not object,please check your parameters");
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
        $sql = "SELECT p.`name`,p.`description`,p.`url` 
                FROM permission AS p 
                LEFT JOIN role_permission AS rp ON p.id=rp.permission_id 
                LEFT JOIN user_role AS ur ON rp.role_id=ur.role_id
                WHERE ur.uid={$uid} ORDER BY p.create_time DESC";

        $list = $this->db->getAll($sql);

        return $list ? $list : [];
    }

    /**
     * 获取所有的权限节点
     * @return array
     */
    public function allPermissionList()
    {
        $sql = "SELECT `name`,`description`,`url` FROM permission ORDER BY create_time DESC ";

        $list = $list = $this->db->getAll($sql);

        return $list ? $list : [];
    }

    /**
     * 用户角色信息获取
     * @param $uid
     * @return array
     */
    public function roleListByUid($uid)
    {
        $uid = intval($uid);

        $sql = "SELECT r.`id`,r.`name`,r.`create_time` FROM user_role AS ur LEFT JOIN role AS r ON ur.role_id=r.id WHERE ur.uid={$uid} ORDER BY r.create_time DESC";

        $list = $list = $this->db->getAll($sql);

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
        $insertData = [
            'name' => Utils::html($name),
            'description' => Utils::html($description),
            "url" => Utils::html($router),
            'create_time' => time(),
        ];

        $res = $this->db->insertRow("permission", $insertData);

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
        $insertData = [
            'role_id' => intval($roleId),
            'permission_id' => intval($permissionId),
            'create_time' => time(),
        ];

        $res = $this->db->insertRow("role_permission", $insertData);

        return boolval($res);
    }

    /**
     * 添加角色
     * @param $name
     * @return bool
     */
    public function addRole($name)
    {
        $insertData = [
            'name' => Utils::html($name),
            'create_time' => time(),
        ];

        $res = $this->db->insertRow("role", $insertData);

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
        $insertData = [
            'uid' => intval($uid),
            'role_id' => intval($roleId),
            'create_time' => time(),
        ];

        $res = $this->db->insertRow("user_role", $insertData);

        return boolval($res);
    }

    /** 删除权限节点
     * @param $permissionId
     * @return bool
     */
    public function deletePermission($permissionId)
    {
        $permissionId = intval($permissionId);
        $sql = "DELETE FROM permission WHERE id = {$permissionId};";
        $res = $this->db->exeSql($sql);
        return boolval($res);
    }

    /** 删除角色
     * @param $roleId
     * @return bool
     */
    public function deleteRole($roleId)
    {
        $roleId = intval($roleId);
        $sql = "DELETE FROM role WHERE id = {$roleId};";
        $res = $this->db->exeSql($sql);
        return boolval($res);
    }
}
