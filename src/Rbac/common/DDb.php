<?php

/**
 * Class DDb
 */
namespace Rbac\common;

class DDb
{
	public $db;
	public $dbHost   = '';
	public $dbPort   = '3306';
	public $dbUser   = '';
	public $dbPwd    = '';
	public $dbName   = '';
	public $dbPrefix = '';
	public $charset  = 'utf8';

    /**
     * DDb constructor.
     * @param $host
     * @param $root
     * @param $password
     * @param $dbName
     * @param $port
     */
	public function __construct($host, $root, $password, $dbName,$port)
	{
		$this->dbHost   = $host;
		$this->dbPort   = $port;
		$this->dbUser   = $root;
		$this->dbPwd    = $password;
		$this->dbName   = $dbName;
		//实例化db
		$this->db = new \mysqli($this->dbHost, $this->dbUser, $this->dbPwd, $this->dbName);
		$this->db->set_charset($this->charset);
	}

	/**
	 * 执行sql语句，所有sql操作最终走到这里
	 * @param  [string] $sql 要执行的sql语句
	 * @return resource
	 */
	public function exeSql($sql)
	{
		$res = $this->db->query($sql) or $this->printError();
		return $res;
	}

	/**
	 * 打印sql错误，并加样式
	 */
	public function printError()
	{
		$sqlError = $this->db->error;
		die('<div style="font-size:22px;font-family:Microsoft Yahei;">'.$sqlError.'</div>');
	}

	/**
	 * 取得最近一次insert操作的id
	 * @return int
	 */
	public function getInsertId()
	{
		return $this->db->insert_id;
	}

	/**
	 * 查询某一个值
	 * @param  [string] $sql 要执行的sql语句
	 * @return string
	 */
	public function getOne($sql)
	{
		$result = $this->exeSql($sql);
		if($result)
		{
			$datas = $result->fetch_row();
		}
		$datas = isset($datas[0]) ? $datas[0] : '';
		return $datas;
	}

	/**
	 * 查询一条数据
	 * @param  [string] $sql 要执行的sql语句
	 * @return array
	 */
	public function getRow($sql)
	{
		$result = $this->exeSql($sql);
		$datas = array();
		if($result)
		{
			$datas = $result->fetch_assoc();
		}
		return $datas;
	}

	/**
	 * 查询一组数据
	 * @param  [string] $sql 要执行的sql语句
	 * @return array
	 */
	public function getAll($sql)
	{
		$result = $this->exeSql($sql);
		$datas = array();
		if($result)
		{
			while ($row = $result->fetch_assoc())
			{
				$datas[] = $row;
			}
		}
		return $datas;
	}

    /** insert插入语句
     * @param $tabName
     * @param $arrData
     * @return 结果集
     */
	public function insertRow($tabName, $arrData)
	{
		$iField = $iData = " (";
		foreach($arrData as $key=>$val){
			$iField .= $key.",";
			$iData .= "'".$val."',";
		}
		$iField = rtrim($iField,",").") ";
		$iData = rtrim($iData,",").") ";
		$sql = "insert into ".$tabName.$iField." values ".$iData;
		return $this->exeSql($sql);
	}

    /** update更新语句
     * @param $tabName
     * @param $arrData
     * @param $where
     * @return 结果集
     */
	public function updateRow($tabName, $arrData, $where)
	{
		$sql = "update $tabName set ";
		foreach($arrData as $key=>$val)
		{
			$sql .= " $key = $val,";
		}
		$sql = rtrim($sql,",")." where ".$where;
		return $this->exeSql($sql);
	}

}
