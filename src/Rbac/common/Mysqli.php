<?php
/**
 * 通用数据库操作类
 * 继承mysqli 在mysqli基础上简化了insert update delete select 等常用语句的使用
 * 同时仍然可以使用mysqli中所有方法
 * 如果需要运行左联结或者其他一些不常用的sql语句 请直接使用 ExeSql
 * 修改时间：2008-4-25
 * 修改人  ：Moon
 */

require_once ( ROOT . '/gb_php/MysqliMonitor.class.php' );
register_shutdown_function ( function () {
    MysqliMonitor::batchWrite ();            //脚本退出后,将收集到的mysqli监控记录一次性写入文件中
} );

//old
class DB2
{
    private $isPrintSql=false;

    private $sTablePre='';

    private $db;

    private $iLastCount=0;

    function __construct($host,$root,$pw,$dbname)
    {

		 $this->db=new mysqli($host,$root,$pw,$dbname);

        if(!$this->db)
        {
            $this->Halt("Can't connect to the DataBase Please your <i>Host、Root、Password and DataBaseName</i> are right<br/>:");
        }
    }

    function getLastCount()
    {
        return($this->iLastCount);
    }

    function SaveHackInfo()
    {
        global $to8to_uid;
        $dir=ROOT.'/syslog/hack_ip/';
        $file=date('Y-m').'.txt';
        $ip=getip();
        if(!is_dir($dir))
            create_dir($dir);
        touch($dir.$file);
        $t=date("Y-m-d H:i:s");
        $msg="uid:$to8to_uid \t IP:$ip \t 时间:$t \t 文件：".$_SERVER['REQUEST_URI']."\n\r";
        $f=fopen($dir.$file,'a+');
        fwrite($f,$msg);
        fclose($f);
        exit("");
    }

  //   function SqlFilter($sSql)
  //   {
  //       $sSql_tmp=strtolower($sSql);
  //       if(strpos($sSql_tmp,'sleep')!==false) $this->SaveHackInfo();
  //       if(strpos($sSql_tmp,'truncate')!==false)  $this->SaveHackInfo();
		// if(strpos($sSql_tmp,'benchmark')!==false) $this->SaveHackInfo();
  //   }
    function SqlFilter($sSql)
    {
        $logPregArr = array("@@version", "\/\*[\W\w]*?\*\/", "@@datadir", "user\W*?\(", "version\W*?\(", "updatexml\W*?\(", "extractvalue\W*?\(", "from.*?(\W+?)information_schema", "sleep\W*?\(", "benchmark\W*?\(", "base64_decode\W*?\(", "database\W*?\(", "into.*?(\W+?)outfile","&&", "\|\|");
        foreach ($logPregArr as $key => $val)
        {
            if (preg_match("/" . $val . "/", strtolower($sSql)))
            {
                $this->SaveHackInfo($sSql);
            }
        }
    }

    function WhereFilter($sWhere)
    {
        $sWhere_tmp=strtolower($sWhere);
        if(strpos($sWhere_tmp,'delete')!==false) $this->SaveHackInfo();
        if(strpos($sWhere_tmp,'select')!==false) $this->SaveHackInfo();
        if(strpos($sWhere_tmp,'insert')!==false) $this->SaveHackInfo();
        if(strpos($sWhere_tmp,'table')!==false) $this->SaveHackInfo();
        if(strpos($sWhere_tmp,'drop')!==false) $this->SaveHackInfo();
        if(strpos($sWhere_tmp,'modify')!==false) $this->SaveHackInfo();
    }

    //设置需要打印ＳＱＬ
    function SetPrintSql()
    {
        $this->isPrintSql=true;
    }
    /**
     * 设置表前缀
     *
     * @param string $sTablePre
     */
    function SetTablePre($sTablePre)
    {
        $this->sTablePre=$sTablePre;
    }

    function SmtSql($sSql)
    {
        if($this->isPrintSql)
            $this->PrintSql($sSql);
        $oSmt=$this->db->prepare($sSql);
        if(!$oSmt)
            $this->Halt('Querry Error:<br/>'.$sSql);
        else
            return $oSmt;
    }
    /**
     * 打印sql语句
     *
     * @param unknown_type $sql
     */
    function PrintSql($sql)
    {
        if($this->isPrintSql)
        {
            printf("<p><font color=\"#0000ff\">%s</font></p>\n",htmlspecialchars($sql));
        }
    }

    /**
     * 设置是否打印sql语句
     *
     * @param bool $isOpen
     */
    function SetIsPrintSql($isOpen=true)
    {
        $this->isPrintSql=$isOpen;
    }

    /**
     * 执行一条sql语句,对于select 返回获取的记录集指针
     * 而对于insert delete update等返回布尔值
     * @param string $sSql
     * @return mix
     */
    function ExeSql($sSql)
    {
        $this->SqlFilter($sSql);

        $Res=$this->db->query($sSql);

        if(!$Res) $this->Halt('Querry Error:<br/>'.$sSql);

        $this->PrintSql($sSql);


        return $Res;
    }




    /**
     * 向表中插入记录集 如果插入一条 $mValues的形式可以是"$v1,$v2"或"($v1,$v2)"
     * 如果插入多条 $mValues的形式必须为 "($v1,$v2),($v3,$v4)";
     * 字段形式可以为 "c1,c2"或者"(c1,c2)"
     * 返回刚插入记录的id
     *
     * @param string $sTable
     * @param string $mColumns
     * @param string $mValues
     * @return int
     */

    function InsertRow($sTable,$sColumns,$mValues)
    {
        $sTable=$this->AddTablePre($sTable);

        if(substr($sColumns,0,1)=="("&&substr($sColumns,-1,1)==")")
            $sSql="insert into $sTable $sColumns";
        else
            $sSql="insert into $sTable ($sColumns)";

        if(substr($mValues,0,1)=="("&&substr($mValues,-1,1)==")")
            $sSql.=" values $mValues";
        else
            $sSql.=" values ($mValues)";

        $this->ExeSql($sSql);

        return $this->GetInsertId();
    }

    function GetCol($sTables,$mColumns='',$sWhere='',$sGroupby='',$sOrderby='',$mLimit='')
    {
        $arr=GetRow($sTables,$mColumns,$sWhere,$sGroupby,$sOrderby,$mLimit);
        foreach ($arr as $key => $value) {
            $res[]=$value[$mColumns];
        }
        return $res;
    }
    /**
     * 获取记录集(select 语句) 如果是多个表 那么$sTables="$t1,$t2,$t3"的形式
     * 字段$mColumns 以"c1,c2,c3"出现 如果缺省 则默认为所有字段
     * //如果获取的列名为空则，$sTables为完整的Sql语句,换句话也就是只传入一个参数的情况
     * 返回二维数组形式数据
     * @param string $sTables
     * @param string $mColumns
     * @param string $sWhere
     * @param string $sGroupby
     * @param string $sOrderby
     * @param string $mLimit
     * @return array
     */
    function GetRow($sTables,$mColumns='',$sWhere='',$sGroupby='',$sOrderby='',$mLimit='',$needcache=0)
    {

        if ($mColumns=="")  return ($this->FetchRow($sTables));  //如果获取的列名为空则，$sTables为完整的Sql语句


        $aTableList=array();

        $aTableList=explode(",",$sTables);

        $sTables='';

        foreach($aTableList as $sTable)
        {
            $sTables.=empty($sTables)?$this->AddTablePre($sTable):",".$this->AddTablePre($sTable);
        }
        if(empty($mColumns))
        {
            $aTableList=array();

            $aTableList=explode(",",$sTables);

            foreach($aTableList as $sTable)
            {
                $mColumns.=empty($mColumns)?$sTable.".*":",".$sTable.".*";
            }
        }
        $sSql="select $mColumns from $sTables";

        $sWhere=str_ireplace("WHERE","where",$sWhere);

        $sGroupby=str_ireplace("GROUP BY","group by",$sGroupby);

        $sOrderby=str_ireplace("ORDER BY","order by",$sOrderby);

        $mLimit=str_ireplace("LIMIT","limit",$mLimit);


        $this->WhereFilter($sWhere);

        if($sWhere)

            $sSql.=substr($sWhere,0,5)=="where"?" $sWhere":" where $sWhere";

        if($sGroupby)

            $sSql.=substr($sGroupby,0,8)=="group by"?$sGroupby:" group by $sGroupby";

        if($sOrderby)

            $sSql.=substr($sOrderby,0,8)=="order by"?$sOrderby:" order by $sOrderby";

        $countsql=$sSql;

        if($mLimit)

            $sSql.=substr($mLimit,0,5)=="limit"?$mLimit:" limit $mLimit";

        if($needcache)
        {
            global $memcache;
            $key="sql".md5($sSql);
            $countkey="count".md5($countsql);
            $Res=$memcache->get("$key");
            if(!$Res)
            {
                $Res=$this->FetchRow($sSql);
                $memcache->set("$key", $Res , 0, 3600*24*$needcache);
                if(strpos($countsql,"SQL_CALC_FOUND_ROWS")!==false)
                {

                    $countRes=$memcache->get("$countkey");
                    if(!$countRes)
                    {
                        $allNum=$this->FetchRow("SELECT FOUND_ROWS() as allnum");
                        $countRes=$allNum[0]['allnum']!=''?$allNum[0]['allnum']:0;
                        $memcache->set("$countkey", $countRes , 0, 3600*24*$needcache);
                    }

                }
            }
            $this->iLastCount=$memcache->get("$countkey");

        }
        else
        {
            $Res=$this->FetchRow($sSql);
        }


        return $Res;
    }

    /**
     * 构造联结表 返回表名的联结形式 提供给GetRow函数使用 可以使用左联结或者内联结 默认为左联结
     *
     * 参数形式：
     *     如果仅有二个表连接JoinTables($sTableLeft,$sTableRight,$sOn,$sJoin='left')可以如下使用
     *         JoinTables('members as u','log as l','u.uid=l.uid','left');
     *     如果连接三个以上表JoinTables($sTableLeft[$aTableLeft],$aTables1,[$aTables2].....)
     *  $aTable1数组结构如 array('log as l','u.uid=l.uid','inner');
     * 	       JoinTables('members as u',array('log as l','u.uid=l.uid','inner'),array('comment as c','l.lid=c.lid'));
     *
     *     JoinTables 的返回值给GetRow函数使用(联结一个表取数据)
     * 		   $sTables=$db->JoinTables('members as u','log as l','u.uid=l.uid','left');
     * 	       $aRow   =$db->GetRow("$sTables","u.uid as id,u.username as name,l.lid");
     * @return string
     */

    function JoinTables()
    {
        $iArgNum   = func_num_args();
        $aArgsList = func_get_args();
        $sTables='';
        if(4==$iArgNum&&!is_array($aArgsList[3]))
        {
            $sTableLeft = $this->AddTablePre($aArgsList[0]);
            $sTableRight= $this->AddTablePre($aArgsList[1]);
            if(!$aArgsList[2])
                $sTables = "$sTableLeft,$sTableRight";
            else
            {
                if(substr($aArgsList[2],0,2)=='on')
                    $sOn=substr($aArgsList[2],2);
                else
                    $sOn=$aArgsList[2];
                if(!$aArgsList[3])
                    $aArgsList[3] = 'left';
                $sTables = "$sTableLeft $aArgsList[3] join $sTableRight on $sOn";
            }
        }
        else
        {
            for($i=0,$j=count($aArgsList);$i<$j;$i++)
            {
                if(0==$i)
                {
                    if(is_array($aArgsList[$i]))
                        $sTables.=$this->AddTablePre($aArgsList[$i][0]);
                    else
                        $sTables.=$this->AddTablePre($aArgsList[$i]);
                }
                else
                {
                    if(!$aArgsList[$i][2])
                        $sJoin='left';
                    else
                        $sJoin=$aArgsList[$i][2];
                    if(substr($aArgsList[$i][1],0,2)=='on')
                        $sOn=substr($aArgsList[$i][1],2);
                    else
                        $sOn=$aArgsList[$i][1];
                    $sTableRight=$this->AddTablePre($aArgsList[$i][0]);
                    $sTables.=" $sJoin join $sTableRight on $sOn";
                }
            }
        }
        return $sTables;
    }

    /**
     * 更新表的记录 该方法可以带多个参数 但是不能少于三个 第一个参数为需要更新的表名
     * 最后一个参数为条件语句 中间可以有2n个参数 例如a,aa,b,bb,c,cc则代表要更新的
     * 字段和值为 a=aa,b=bb,c=cc
     * 成功执行返回true 否则返回bool
     * @return bool
     */

    function UpdateRow()
    {
        $iArgNum = func_num_args();
        if($iArgNum < 3)
        {
            return false;
        }
        $aArgList = func_get_args();

        $sTableName = $aArgList[0];

        $sTableName = $this->AddTablePre($sTableName);

        if($iArgNum % 2 == 0)
        {
            $sWhere = array_pop($aArgList);
        }

        $sSql = 'UPDATE ' . $sTableName . ' SET ';

        $aSetSqlList = array();

        for($I=1; $I < $iArgNum -1; $I=$I+2)
        {
            if(isset($aArgList[$I]) && isset($aArgList[$I+1]))
            {
                $aSetSqlList[] = "{$aArgList[$I]} = {$aArgList[$I+1]}";
            }
        }

        $sSetSql = join(',', $aSetSqlList);

        $sSql .= $sSetSql;

        $sWhere=str_ireplace('where','WHERE',$sWhere);

        $this->WhereFilter($sWhere);

        if(!empty($sWhere))
        {
            $sSql .= substr($sWhere,0,5)=='WHERE'?" $sWhere":' WHERE ' . $sWhere;
        }

        return $this->ExeSql($sSql);
    }

    /**
     * 删除表中的记录
     * 成功删除返回true 否则返回false
     * @param string $sTable
     * @param string $sWhere
     * @return bool
     */
    function DeleteRow($sTable,$sWhere)
    {
        $sTable=$this->AddTablePre($sTable);

        $sSql="DELETE FROM $sTable";

        $sWhere=str_ireplace('where','WHERE',$sWhere);

        $this->WhereFilter($sWhere);

        if($sWhere)

            $sSql.=substr($sWhere,0,5)=="WHERE"?" $sWhere":" WHERE $sWhere";

        return $this->ExeSql($sSql);
    }


    /**
     * 运行多条sql语句 sql语句之间用分号间隔 一般只在使用了存储过程后返回多条记录的情况下使用
     * 使用几率比较小  其他同 ExeSql 类似
     *
     * @param string $sSql
     * @return mix
     */
    function ExeSqls($sSql)
    {
        $this->SqlFilter($sSql);
        $isOk=$this->db->multi_query($sSql);
        if($isOk)
        {
            $Result=$this->db->store_result();
            return $Result;
        }
        else
        {
            $this->Halt("'Querry Error<br/>'.$sSql");
        }
    }


    /**
     * 接受sql语句 (select 语句) 然后返回查询语句的查询结果 二维数组形式
     * 只能接收查询语句
     * @param string $sSql
     * @return array
     */
    function FetchRow($sSql)
    {
        $Res=$this->ExeSql($sSql);

        $aRow=array();

        while($aRrs=$Res->fetch_assoc())
        {
            $aRow[]=$aRrs;
        }
        $this->db->next_result();
        return $aRow;
    }


    /**
     * 获取查询的一条记录
     * 只接收select 的查询sql语句 返回一维数组数据
     * @param string $sSql
     * @return array
     */
    function GetOne($sTables,$mColumns='',$sWhere='',$sGroupby='',$sOrderby='',$mLimit='')
    {
        $aRow=$this->GetRow($sTables,$mColumns,$sWhere,$sGroupby,$sOrderby,$mLimit);

        return $aRow[0];
    }


    /**
     * 设置数据库连接 结果 和客户端显示数据格式
     * 一般这三者的数据格式需要相同才能正确显示 所以只需要传递一个参数
     * 无返回值
     * @param string $sCharset
     */
    function SetCharset($sCharset)
    {
        $sCharset=str_ireplace('-','',$sCharset);
        $this->ExeSql("SET character_set_connection='$sCharset', character_set_results='$sCharset',character_set_client='$sCharset'");
    }

    /**
     * 释放查询结果的资源变量
     * 无返回值
     * @param resoure $Res
     */


    function FreeRes($Res)
    {
        if('object'!=gettype($Res))
            return ;
        @$Res->free_result();
    }


    /**
     * 获取查询结果记录集的记录数 接收资源变量参数
     *
     * @param resoure $Res
     * @return int
     */
    function GetCountRow($Res)
    {
        return @$Res->num_rows;
    }


    /**
     * 返回最后一条sql语句后主键(自动整加)的值  对于无自动整加的属性以及非插入语句
     * 以及非成功插入的语句返回0值
     *
     * @return int
     */
    function GetInsertId()
    {
        return $this->db->insert_id;
    }


    /**
     * 返回收update delete insert语句影响的记录集条数
     * 如果数据表虽然成功执行操作但是数据表数据本身无变化 也就是说
     * 更新(update 的情况多见 但也包括delete insert)前后数据表
     * 本身没变化 那么返回0
     * @return int
     */
    function GetAffectedRow()
    {
        return $this->db->affected_rows;
    }


    /**
     * 连接数据库 无返回值
     *
     * @param string $sHost
     * @param string $sRoot
     * @param string $sPassword
     * @param string $sDbName
     */
    function ConnectDB($sHost,$sRoot,$sPassword,$sDbName)/*connect to a new database*/
    {
        $isOk=@$this->db->connect($sHost,$sRoot,$sPassword,$sDbName);
        if(!$isOk)
            $this->Halt('CONNECT DATABASE ERROR:<br/>');
    }

    /**
     * 关闭数据库连接 无返回值
     *
     */
    function Shut()
    {
        @$this->db->close();
    }


    /**
     * 自定义的数据库操作错误显示
     *
     * @param string $sMsg
     */
    function Halt($sMsg)
    {
        if(DEBUG)
        {
            require_once(GB_PHP_ROOT.'DBerror.class.php');
            new DB_ERROR($sMsg,$this->db->error,$this->db->erron);
        }
    }

    /**
     * 给表添加指定的前缀  如果表本身已经有前缀则不添加
     * $_CFG 为配置信息数组 在配置文件中定义
     * @param string $sTable
     * @return string
     */
    function AddTablePre($sTable)
    {

        $iLength=strlen($this->sTablePre);

        if(substr($sTable,0,$iLength)==$this->sTablePre)

            return $sTable;

        else

            return $this->sTablePre.$sTable;
    }
}

//new
class DB
{
    private $isPrintSql=false;

    private $sTablePre='';

    private $db;

    private $db_host;

    private $db_slaver;

    private $iLastCount=0;

    private $db_name;

    private $db_port;

    private $db_slaver_host;

    private $db_config = array();

    private $transaction_status = false;  //事务状态

    function __construct($host,$root,$pw,$dbname,$port = 3306)
    {

        $this->_config();

        $connect_time_start = $this->tGetmicrotime ();           //当前时间戳的微秒数
        $this->db=new mysqli($host,$root,$pw,$dbname,$port);
        $connect_time_end = $this->tGetmicrotime ();           //当前时间戳的微秒数
        MysqliMonitor::log ( 'master_conn_time', 1000 * ( $connect_time_end - $connect_time_start ) );       //记录主库连接耗时,单位为毫秒

        if($this->db->connect_errno > 0)
        {
            //主库连接不上，再尝试一次连接从库
            $this->_log('master',"1Can't connect to $dbname ".$_SERVER['REQUEST_URI'].$host);
			$this->Halt("Can't connect to the DataBase Please your <i>Host、Root、Password and DataBaseName</i> are right<br/>:");
            MysqliMonitor::log ( 'master_conn_fail', 1 );         //将主库连接失败的信息存到监控系统中

            /*if(count($this->db_config) == 0)
            {
                $this->Halt("Can't connect to the DataBase Please your <i>Host、Root、Password and DataBaseName</i> are right<br/>:");

            }

            $offset = $this->_max_power($this->db_config);
            $config = $this->db_config[$offset];
            $this->db=new mysqli($config['host'],$config['root'],$config['password'],$dbname);
            $host = $config['host'];

            if($this->db->connect_errno > 0)
            {
                //从也连接不上，放弃连接输出错误
                $this->_log('2master',"Can't connect to ".$config['host']);
                $this->Halt("Can't connect to the DataBase Please your <i>Host、Root、Password and DataBaseName</i> are right<br/>:");
            }*/
        }

        $this->_log('master',"3connect to $dbname".$_SERVER['REQUEST_URI'].$host);
        MysqliMonitor::log ( 'master_conn_succ', 1 );        //将主库连接成功的信息存到监控系统中

        $this->db_host = $host;
        $this->db_name = $dbname;
        $this->db_port = $port;
        $this->slaver_connect();
    }

    function _config()
    {
        $path = dirname(dirname(__FILE__)).'/include/db_slaver.inc.php';
        if(!file_exists($path))return;

        $this->db_config = include($path);

    }

    //获取当前时间戳微秒级
    function tGetmicrotime ()
    {
        $arr = explode ( ' ', microtime () );

        return $arr[0] + $arr[1];
    }

    function slaver_connect()
    {
        //根据权重选择
        $db_slaver_config = $this->db_config;
        if(count($this->db_config) == 0)return;
        $this->_rand_slaver($db_slaver_config);
        if($this->db_slaver && $this->db_slaver->connect_errno > 0)
        {
            $this->db_slaver = null;
//            $this->_log('slaver',"Can't connect to ".$this->db_slaver_host);
        }
//        else {
//            $this->_log('slaver','connect to '.$this->db_slaver_host);
//        }

    }

    function _log($flag,$content)
    {
        //写日志
        $file_path = ROOT.'/syslog/dberror/'.date('Y-m-d').'.log';
        $content = sprintf("[%s] %s %s\n",$flag,date('Y-m-d H:i:s'),$content);
        //@file_put_contents($file_path,$content,FILE_APPEND);
    }

    function _max_power($config)
    {
        $max = $offset = 0;
        foreach($config as $k=>$slaver)
        {
            if($slaver['host'] == $this->db_host)continue;
            $v = $slaver['power'] * mt_rand(1,10);
            if($v > $max)
            {
                $max = $v;
                $offset = $k;
            }
        }

        return $offset;
    }


    function _rand_slaver($db_slaver_config)
    {
        //选择从库

        $offset = $this->_max_power($db_slaver_config);
        $config = $db_slaver_config[$offset];
        $port = $config['port'] ? $config['port'] : 3306;

        $connect_time_start = $this->tGetmicrotime ();
        $this->db_slaver = new mysqli($config['host'],$config['root'],$config['password'],$this->db_name,$port);
        $connect_time_end = $this->tGetmicrotime ();
        MysqliMonitor::log ( 'slaver_conn_time', 1000 * ( $connect_time_end - $connect_time_start ) );       //第一次连接从库耗时


        if($this->db_slaver->connect_errno > 0)
        {
            $this->_log('slaver',"4Can't connect to offset:$offset $this->db_name".$_SERVER['REQUEST_URI'].$config['host']);
            MysqliMonitor::log ( 'slaver_conn_fail', 1 );

            unset($db_slaver_config[$offset]);
//            print_r($db_slaver_config);
//            die;
            if(count($db_slaver_config) == 0)return;
            else {
                $offset1 = $this->_max_power($db_slaver_config);
                $config1 = $db_slaver_config[$offset1];
                $port1 = $config1['port'] ? $config1['port'] : 3306;

                $connect_time_start = $this->tGetmicrotime ();
                $this->db_slaver = new mysqli($config1['host'],$config1['root'],$config1['password'],$this->db_name,$port1);
                $connect_time_end = $this->tGetmicrotime ();
                MysqliMonitor::log ( 'slaver_reconn_time', 1000 * ( $connect_time_end - $connect_time_start ) );       //第二次连接从库耗时

                if($this->db_slaver->connect_errno > 0)
                {
                    $this->_log('slaver',"5Can't connect to offset1:$offset1 $this->db_name ".$_SERVER['REQUEST_URI'].$config1['host']);
                    MysqliMonitor::log ( 'slaver_reconn_fail', 1 );
                }
                else {
//                    $this->db_slaver_host = $config1['host'];
                    $this->_log('slaver',"6connect to offset1:$offset1 $this->db_name ".$_SERVER['REQUEST_URI'].$config1['host']);
                    MysqliMonitor::log ( 'slaver_reconn_succ', 1 );
                }
            }
        }
        else {
//            $this->db_slaver_host = $config['host'];
            $this->_log('slaver',"7connect to offset:$offset $this->db_name ".$_SERVER['REQUEST_URI'].$config['host']);
            MysqliMonitor::log ( 'slaver_conn_succ', 1 );
        }

    }

    function close_slaver()
    {
        //断开从库连接
        if($this->db_slaver)
        {
            @$this->db_slaver->close();
            $this->db_slaver = null;
        }
    }

    function getLastCount()
    {
        return($this->iLastCount);
    }

    function SaveHackInfo()
    {
        global $to8to_uid;
        $dir=ROOT.'/syslog/hack_ip/';
        $file=date('Y-m').'.txt';
        $ip=getip();
        if(!is_dir($dir))
            create_dir($dir);
        touch($dir.$file);
        $t=date("Y-m-d H:i:s");
        $msg="uid:$to8to_uid \t IP:$ip \t 时间:$t \t 文件：".$_SERVER['REQUEST_URI']."\n\r";
        $f=fopen($dir.$file,'a+');
        fwrite($f,$msg);
        fclose($f);

        exit("");
    }

    function SqlFilter($sSql)
    {
        $sSql_tmp=strtolower($sSql);
        if(strpos($sSql_tmp,'sleep')!==false) $this->SaveHackInfo();
        if(strpos($sSql_tmp,'truncate')!==false)  $this->SaveHackInfo();
        if(strpos($sSql_tmp,'benchmark')!==false)  $this->SaveHackInfo();
        if(strpos($sSql_tmp,'information_schema')!==false) $this->SaveHackInfo();
        if(strpos($sSql_tmp,'updatexml(')!==false) $this->SaveHackInfo();
        if(strpos($sSql_tmp,'extractvalue(')!==false) $this->SaveHackInfo();
        if(strpos($sSql_tmp,'version()')!==false) $this->SaveHackInfo();
        if(strpos($sSql_tmp,'@@version')!==false) $this->SaveHackInfo();
        if(strpos($sSql_tmp,'@@datadir')!==false) $this->SaveHackInfo();
        if(strpos($sSql_tmp,'user()')!==false) $this->SaveHackInfo();
        if(strpos($sSql_tmp,'system_user()')!==false) $this->SaveHackInfo();
        if(strpos($sSql_tmp,'database()')!==false) $this->SaveHackInfo();

    }

    function WhereFilter($sWhere)
    {
        $sWhere_tmp=strtolower($sWhere);
        if(strpos($sWhere_tmp,'delete')!==false) $this->SaveHackInfo();
        if(strpos($sWhere_tmp,'select')!==false) $this->SaveHackInfo();
        if(strpos($sWhere_tmp,'insert')!==false) $this->SaveHackInfo();
        if(strpos($sWhere_tmp,'table')!==false) $this->SaveHackInfo();
        if(strpos($sWhere_tmp,'drop')!==false) $this->SaveHackInfo();
        if(strpos($sWhere_tmp,'modify')!==false) $this->SaveHackInfo();

    }

    //设置需要打印ＳＱＬ
    function SetPrintSql()
    {
        $this->isPrintSql=true;
    }
    /**
     * 设置表前缀
     *
     * @param string $sTablePre
     */
    function SetTablePre($sTablePre)
    {
        $this->sTablePre=$sTablePre;
    }

    /**
     * User: tony.tang
     * Date: 2016年12月14日 22:03:54
     * Description: 获取表的前缀
     * @return string
     */
    function GetTablePre()
    {
        return $this->sTablePre;
    }

    function SmtSql($sSql)
    {
        if($this->isPrintSql)
            $this->PrintSql($sSql);
        $oSmt=$this->db->prepare($sSql);
        if(!$oSmt)
            $this->Halt('Querry Error:<br/>'.$sSql);
        else
            return $oSmt;
    }
    /**
     * 打印sql语句
     *
     * @param unknown_type $sql
     */
    function PrintSql($sql)
    {
        if($this->isPrintSql)
        {
            printf("<p><font color=\"#0000ff\">%s</font></p>\n",htmlspecialchars($sql));
        }
    }

    /**
     * 设置是否打印sql语句
     *
     * @param bool $isOpen
     */
    function SetIsPrintSql($isOpen=true)
    {
        $this->isPrintSql=$isOpen;
    }

    /**
     *后台记录sql操作
     * @param type $dir
     * @param type $fileName
     * @param type $sql
     */
    function  gIsSql($dir,$fileName,$sql){

        if(!empty($dir) and !empty($fileName) and !empty($sql)){
            try {
                require_once(GB_PHP_ROOT.'log.class.php');
                $logger =  new Logs($dir,$fileName);
                $com = "[".$sql."]\r";
                $logger->LogNotice($com);
            } catch (Exception $exc) {

            }


        }

    }


    /**
     * 执行一条sql语句,对于select 返回获取的记录集指针
     * 而对于insert delete update等返回布尔值
     * @param string $sSql
     * @return mix
     */
    function ExeSql($sSql)
    {

        $logdir = ROOT."/syslog/sqllog/".date('Ymd');

        // 对非查询语句记录流水日志
        $isSelect = strpos(strtolower($sSql),'select') !== false;

        if (!$isSelect) {
            @error_log($sSql."\n", 3, $logdir);
        }

        $this->SqlFilter($sSql);
        if($this->db_slaver)
        {
            if(strpos(strtolower($sSql),'select') !== false)
            {
                $Res = $this->db_slaver->query($sSql);
            }
            else $Res=$this->db->query($sSql);
        }
        else {
            $Res=$this->db->query($sSql);
        }

        if(!$Res)
        {
            $this->Halt('Querry Error:<br/>'.$sSql);
        }


        //@file_put_contents($logdir.'/log.txt', date("Ymd H:i:s")." ".$sSql."\n",FILE_APPEND);

//        //后台记录sql操作
//
//        if(defined("GETSQL") and defined("LOGDIR") and defined("LOGNAME")){
//            $this->gIsSql(LOGDIR, LOGNAME, $sSql);
//        }

        $this->PrintSql($sSql);
        return $Res;
    }









    /**
     * 向表中插入记录集 如果插入一条 $mValues的形式可以是"$v1,$v2"或"($v1,$v2)"
     * 如果插入多条 $mValues的形式必须为 "($v1,$v2),($v3,$v4)";
     * 字段形式可以为 "c1,c2"或者"(c1,c2)"
     * 返回刚插入记录的id
     *
     * @param string $sTable
     * @param string $mColumns
     * @param string $mValues
     * @return int
     */


    function InsertRow($sTable,$sColumns,$mValues)
    {
        $sTable=$this->AddTablePre($sTable);

        if(substr($sColumns,0,1)=="("&&substr($sColumns,-1,1)==")")
            $sSql="insert into $sTable $sColumns";
        else
            $sSql="insert into $sTable ($sColumns)";

        if(substr($mValues,0,1)=="("&&substr($mValues,-1,1)==")")
            $sSql.=" values $mValues";
        else
            $sSql.=" values ($mValues)";

        $this->ExeSql($sSql);

        return $this->GetInsertId();
    }

    function GetCol($sTables,$mColumns='',$sWhere='',$sGroupby='',$sOrderby='',$mLimit='')
    {
        $arr= $this->GetRow($sTables,$mColumns,$sWhere,$sGroupby,$sOrderby,$mLimit);
        foreach ($arr as $key => $value) {
            $res[]=$value[$mColumns];
        }
        return $res;
    }
    /**
     * 获取记录集(select 语句) 如果是多个表 那么$sTables="$t1,$t2,$t3"的形式
     * 字段$mColumns 以"c1,c2,c3"出现 如果缺省 则默认为所有字段
     * //如果获取的列名为空则，$sTables为完整的Sql语句,换句话也就是只传入一个参数的情况
     * 返回二维数组形式数据
     * @param string $sTables
     * @param string $mColumns
     * @param string $sWhere
     * @param string $sGroupby
     * @param string $sOrderby
     * @param string $mLimit
     * @return array
     */
    function GetRow($sTables,$mColumns='',$sWhere='',$sGroupby='',$sOrderby='',$mLimit='',$needcache=0)
    {

        if ($mColumns=="")  return ($this->FetchRow($sTables));  //如果获取的列名为空则，$sTables为完整的Sql语句


        $aTableList=array();

        $aTableList=explode(",",$sTables);

        $sTables='';

        foreach($aTableList as $sTable)
        {
            $sTables.=empty($sTables)?$this->AddTablePre($sTable):",".$this->AddTablePre($sTable);
        }
        if(empty($mColumns))
        {
            $aTableList=array();

            $aTableList=explode(",",$sTables);

            foreach($aTableList as $sTable)
            {
                $mColumns.=empty($mColumns)?$sTable.".*":",".$sTable.".*";
            }
        }
        $sSql="select $mColumns from $sTables";

        $sWhere=str_ireplace("WHERE","where",$sWhere);

        $sGroupby=str_ireplace("GROUP BY","group by",$sGroupby);

        $sOrderby=str_ireplace("ORDER BY","order by",$sOrderby);

        $mLimit=str_ireplace("LIMIT","limit",$mLimit);


        $this->WhereFilter($sWhere);

        if($sWhere)

            $sSql.=substr($sWhere,0,5)=="where"?" $sWhere":" where $sWhere";

        if($sGroupby)

            $sSql.=substr($sGroupby,0,8)=="group by"?$sGroupby:" group by $sGroupby";

        if($sOrderby)

            $sSql.=substr($sOrderby,0,8)=="order by"?$sOrderby:" order by $sOrderby";

        $countsql=$sSql;

        if($mLimit)

            $sSql.=substr($mLimit,0,5)=="limit"?$mLimit:" limit $mLimit";

        if($needcache)
        {
            global $memcache;
            $key="sql".md5($sSql);
            $countkey="count".md5($countsql);
            $Res=$memcache->get("$key");
            if(!$Res)
            {
                $Res=$this->FetchRow($sSql);
                $memcache->set("$key", $Res , 0, 3600*24*$needcache);
                if(strpos($countsql,"SQL_CALC_FOUND_ROWS")!==false)
                {

                    $countRes=$memcache->get("$countkey");
                    if(!$countRes)
                    {
                        $allNum=$this->FetchRow("SELECT FOUND_ROWS() as allnum");
                        $countRes=$allNum[0]['allnum']!=''?$allNum[0]['allnum']:0;
                        $memcache->set("$countkey", $countRes , 0, 3600*24*$needcache);
                    }

                }
            }
            $this->iLastCount=$memcache->get("$countkey");

        }
        else
        {
            $Res=$this->FetchRow($sSql);
        }


        return $Res;
    }

    /**
     * 构造联结表 返回表名的联结形式 提供给GetRow函数使用 可以使用左联结或者内联结 默认为左联结
     *
     * 参数形式：
     *     如果仅有二个表连接JoinTables($sTableLeft,$sTableRight,$sOn,$sJoin='left')可以如下使用
     *         JoinTables('members as u','log as l','u.uid=l.uid','left');
     *     如果连接三个以上表JoinTables($sTableLeft[$aTableLeft],$aTables1,[$aTables2].....)
     *  $aTable1数组结构如 array('log as l','u.uid=l.uid','inner');
     * 	       JoinTables('members as u',array('log as l','u.uid=l.uid','inner'),array('comment as c','l.lid=c.lid'));
     *
     *     JoinTables 的返回值给GetRow函数使用(联结一个表取数据)
     * 		   $sTables=$db->JoinTables('members as u','log as l','u.uid=l.uid','left');
     * 	       $aRow   =$db->GetRow("$sTables","u.uid as id,u.username as name,l.lid");
     * @return string
     */

    function JoinTables()
    {
        $iArgNum   = func_num_args();
        $aArgsList = func_get_args();
        $sTables='';
        if(4==$iArgNum&&!is_array($aArgsList[3]))
        {
            $sTableLeft = $this->AddTablePre($aArgsList[0]);
            $sTableRight= $this->AddTablePre($aArgsList[1]);
            if(!$aArgsList[2])
                $sTables = "$sTableLeft,$sTableRight";
            else
            {
                if(substr($aArgsList[2],0,2)=='on')
                    $sOn=substr($aArgsList[2],2);
                else
                    $sOn=$aArgsList[2];
                if(!$aArgsList[3])
                    $aArgsList[3] = 'left';
                $sTables = "$sTableLeft $aArgsList[3] join $sTableRight on $sOn";
            }
        }
        else
        {
            for($i=0,$j=count($aArgsList);$i<$j;$i++)
            {
                if(0==$i)
                {
                    if(is_array($aArgsList[$i]))
                        $sTables.=$this->AddTablePre($aArgsList[$i][0]);
                    else
                        $sTables.=$this->AddTablePre($aArgsList[$i]);
                }
                else
                {
                    if(!$aArgsList[$i][2])
                        $sJoin='left';
                    else
                        $sJoin=$aArgsList[$i][2];
                    if(substr($aArgsList[$i][1],0,2)=='on')
                        $sOn=substr($aArgsList[$i][1],2);
                    else
                        $sOn=$aArgsList[$i][1];
                    $sTableRight=$this->AddTablePre($aArgsList[$i][0]);
                    $sTables.=" $sJoin join $sTableRight on $sOn";
                }
            }
        }
        return $sTables;
    }

    /**
     * 更新表的记录 该方法可以带多个参数 但是不能少于三个 第一个参数为需要更新的表名
     * 最后一个参数为条件语句 中间可以有2n个参数 例如a,aa,b,bb,c,cc则代表要更新的
     * 字段和值为 a=aa,b=bb,c=cc
     * 成功执行返回true 否则返回bool
     * @return bool
     */

    function UpdateRow()
    {
        $iArgNum = func_num_args();
        if($iArgNum < 3)
        {
            return false;
        }
        $aArgList = func_get_args();

        $sTableName = $aArgList[0];

        $sTableName = $this->AddTablePre($sTableName);

        if($iArgNum % 2 == 0)
        {
            $sWhere = array_pop($aArgList);
        }

        $sSql = 'UPDATE ' . $sTableName . ' SET ';

        $aSetSqlList = array();

        for($I=1; $I < $iArgNum -1; $I=$I+2)
        {
            if(isset($aArgList[$I]) && isset($aArgList[$I+1]))
            {
                $aSetSqlList[] = "{$aArgList[$I]} = {$aArgList[$I+1]}";
            }
        }

        $sSetSql = join(',', $aSetSqlList);

        $sSql .= $sSetSql;

        $sWhere=str_ireplace('where','WHERE',$sWhere);

        $this->WhereFilter($sWhere);

        if(!empty($sWhere))
        {
            $sSql .= substr($sWhere,0,5)=='WHERE'?" $sWhere":' WHERE ' . $sWhere;
        }
        return $this->ExeSql($sSql);
    }

    /**
     * 删除表中的记录
     * 成功删除返回true 否则返回false
     * @param string $sTable
     * @param string $sWhere
     * @return bool
     */
    function DeleteRow($sTable,$sWhere)
    {
        $sTable=$this->AddTablePre($sTable);

        $sSql="DELETE FROM $sTable";

        $sWhere=str_ireplace('where','WHERE',$sWhere);

        $this->WhereFilter($sWhere);

        if($sWhere)

            $sSql.=substr($sWhere,0,5)=="WHERE"?" $sWhere":" WHERE $sWhere";

        return $this->ExeSql($sSql);
    }


    /**
     * 运行多条sql语句 sql语句之间用分号间隔 一般只在使用了存储过程后返回多条记录的情况下使用
     * 使用几率比较小  其他同 ExeSql 类似
     *
     * @param string $sSql
     * @return mix
     */
    function ExeSqls($sSql)
    {
        $this->SqlFilter($sSql);
        $isOk=$this->db->multi_query($sSql);
        if($isOk)
        {
            $Result=$this->db->store_result();
            return $Result;
        }
        else
        {
            $this->Halt("'Querry Error<br/>'.$sSql");
        }
    }


    /**
     * 接受sql语句 (select 语句) 然后返回查询语句的查询结果 二维数组形式
     * 只能接收查询语句
     * @param string $sSql
     * @return array
     */
    function FetchRow($sSql)
    {
        $Res=$this->ExeSql($sSql);
        if(!$Res){          //如果查出数据为空则返回空数组不做下面fetch_assoc 处理
           return array();
        }
        $aRow=array();

        while($aRrs=$Res->fetch_assoc())
        {
            $aRow[]=$aRrs;
        }
        @$this->db->next_result();
        return $aRow;
    }


    /**
     * 获取查询的一条记录
     * 只接收select 的查询sql语句 返回一维数组数据
     * @param string $sSql
     * @return array
     */
    function GetOne($sTables,$mColumns='',$sWhere='',$sGroupby='',$sOrderby='',$mLimit = 1)
    {

        if ( preg_match( "/for\s+update/",  $sWhere ) ) {
            $sWhere = preg_replace( "/for\s+update/", "", $sWhere );
            $mLimit .= ' for update ';
        }

        $aRow=$this->GetRow($sTables,$mColumns,$sWhere,$sGroupby,$sOrderby,$mLimit);

        return $aRow[0];
    }


    /**
     * 设置数据库连接 结果 和客户端显示数据格式
     * 一般这三者的数据格式需要相同才能正确显示 所以只需要传递一个参数
     * 无返回值
     * @param string $sCharset
     */
    function SetCharset($sCharset)
    {
        $sCharset=str_ireplace('-','',$sCharset);
        $this->db->query("SET character_set_connection='$sCharset', character_set_results='$sCharset',character_set_client='$sCharset'");
        if($this->db_slaver)
        {
            $this->db_slaver->query("SET character_set_connection='$sCharset', character_set_results='$sCharset',character_set_client='$sCharset'");

        }
    }

    /**
     * 释放查询结果的资源变量
     * 无返回值
     * @param resoure $Res
     */


    function FreeRes($Res)
    {
        if('object'!=gettype($Res))
            return ;
        @$Res->free_result();
    }


    /**
     * 获取查询结果记录集的记录数 接收资源变量参数
     *
     * @param resoure $Res
     * @return int
     */
    function GetCountRow($Res)
    {
        return @$Res->num_rows;
    }


    /**
     * 返回最后一条sql语句后主键(自动整加)的值  对于无自动整加的属性以及非插入语句
     * 以及非成功插入的语句返回0值
     *
     * @return int
     */
    function GetInsertId()
    {
        return $this->db->insert_id;
    }


    /**
     * 返回收update delete insert语句影响的记录集条数
     * 如果数据表虽然成功执行操作但是数据表数据本身无变化 也就是说
     * 更新(update 的情况多见 但也包括delete insert)前后数据表
     * 本身没变化 那么返回0
     * @return int
     */
    function GetAffectedRow()
    {
        return $this->db->affected_rows;
    }


    /**
     * 连接数据库 无返回值
     *
     * @param string $sHost
     * @param string $sRoot
     * @param string $sPassword
     * @param string $sDbName
     */
    function ConnectDB($sHost,$sRoot,$sPassword,$sDbName)/*connect to a new database*/
    {
        $isOk=@$this->db->connect($sHost,$sRoot,$sPassword,$sDbName);
        if(!$isOk)
            $this->Halt('CONNECT DATABASE ERROR:<br/>');
    }

    /**
     * 关闭数据库连接 无返回值
     *
     */
    function Shut()
    {
        @$this->db->close();
    }


    /**
     * 自定义的数据库操作错误显示
     *
     * @param string $sMsg
     */
    function Halt($sMsg)
    {
        if(DEBUG)
        {
            require_once(GB_PHP_ROOT.'DBerror.class.php');
            new DB_ERROR($sMsg,$this->db->error,$this->db->erron);
        }
    }

    /**
     * 给表添加指定的前缀  如果表本身已经有前缀则不添加
     * $_CFG 为配置信息数组 在配置文件中定义
     * @param string $sTable
     * @return string
     */
    function AddTablePre($sTable)
    {

        $iLength=strlen($this->sTablePre);

        if(substr($sTable,0,$iLength)==$this->sTablePre)

            return $sTable;

        else

            return $this->sTablePre.$sTable;
    }

	/**
	* 开始事务
 	*/
	function startTransaction(){

        if( $this->transaction_status === true ){
            return true;
        }
		$this->ExeSql('SET AUTOCOMMIT=0');
		$this->ExeSql('START TRANSACTION');
        $this->transaction_status = true;//事务状态标示为开始
	}

	/**
	* 提交事务
	*/
	function  commit(){
		$this->ExeSql('COMMIT');
		$this->ExeSql('SET AUTOCOMMIT=1');
        $this->transaction_status = false;//事务状态标示为结束
	}

	/**
	* 回滚事务
	*/
	function  rollBack(){
		$this->ExeSql('ROLLBACK');
		$this->ExeSql('SET AUTOCOMMIT=1');
        $this->transaction_status = false;//事务状态标示为结束
	}

	/**
	* 关闭事务 (注：commit 和 rollback会自动关闭事务,因此本函数不予使用)
	*/
	function endTransaction(){
         $this->transaction_status = false;//事务状态标示为结束
		//$this->ExeSql('END');
	}


}

/*/////////////////////////////////TEST CASE//////////////////////////

$db=new DB('192.168.0.1','robin',1982425,'newto8to');
$db->SetTablePre('to8to_');
$db->SetCharset('gb2312');
$smt=$db->SmtSql('insert into to8to_group (gid,uid,gname) values(?,?,?)');
$smt->bind_param('iis',$gid,$uid,$gname);
$gid=25;
$uid=24;
$gname='aaaa';
$smt->execute(); //测试成功
$db->Shut();// or $db->close();  //测试成功

//插入数据
$db->InsertRow('mgoods','uid,gid',"25,17");  //测试成功

//更新数据
$db->UpdateRow('mgoods','uid',30,"uid=25 and gid=17"); //测试成功

//删除数据
$db->DeleteRow('mgoods','uid=25 and gid=16');   //测试成功

echo $db->GetAffectedRow();  //测试成功

//查看sql语句
$db->SetIsPrintSql(true);  //测试成功

//获得数据,返回的二维数组
$aRow=$db->GetRow('mgoods','uid,gid','uid=25','','gid asc','');  //测试成功

print_r($aRow);

$db->ExeSql("set names utf8");  //测试成功

$aRow=$db->FetchRow("select uid,gid from to8to_mgoods where uid=30 order by gid asc");  //测试成功 一般在不能使用GetRow方法的情况下使用该方法
print_r($aRow);

////////////////////////////////////////////////////////////////////////////*/

?>
