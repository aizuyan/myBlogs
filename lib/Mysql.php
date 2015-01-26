<?php
namespace L;
/**
 * $config['db] = array('localhost:/tmp/mysql.sock', 'username', 'password', 'database_name');
 * $oMudb = new mudb( $config['db'], false);
 */
class Mysql{
	public $count = 0; //查询次数
	public $aServer = array(); //地址配置
	
	private $persist = false; //是否长连接
	private $die = true; //有SQL错误时是否退出脚本
	private $connect = false; //是否连接上
	private $connected = false; //是否已经连接过
	private $connectlast = 0; //记录最后连接的时间.每隔一段时间强制连一次.受限于wait_timeout配置
	
	private $new_link = false; //如果用同样的参数第二次调用 mysql_connect() 是否建立新连接 (强制为否)
	private $client_flags = 0; //连接参数.可以是以下常量的组合：MYSQL_CLIENT_SSL，MYSQL_CLIENT_COMPRESS，MYSQL_CLIENT_IGNORE_SPACE 或 MYSQL_CLIENT_INTERACTIVE
	private $link_identifier = null; //连接句柄
	
	/**
	 * 初始化
	 * @param Array $aServer array('localhost:/tmp/mysql.sock', 'username', 'password', 'database_name')
	 * @param Boolean $persist 是否长连接
	 * @param Boolean $die 有sql错误时是否退出脚本
	 */
	public function __construct( $aServer, $persist=false, $die=true){
		$this->aServer = $aServer;
		$this->persist = $persist;
		$this->die = $die;
	}
	
	/**
	 * 打开一个到 MySQL 服务器的连接
	 * @return Boolean
	 */
	public function connect(){
		
		$this->count++; //统计查询次数
		
		if(! $this->connected){
			$this->connected = true; //标志已经连接过一次
			$this->connectlast = time(); //记录此次连接的时间

			$this->link_identifier = $this->persist ? 
										@mysql_pconnect($this->aServer[0], $this->aServer[1], $this->aServer[2], $this->client_flags) : 
										@mysql_connect($this->aServer[0], $this->aServer[1], $this->aServer[2], $this->new_link, $this->client_flags);

			is_resource( $this->link_identifier) ? ($this->connect = true) : $this->errorlog('Connect', true, null); //标志连接上/错误.连不上强制退出
	
			empty( $this->aServer[3]) || $this->select_db( $this->aServer[3]); //需要选择数据库
			mysql_set_charset('utf8',$this->link_identifier);
		}
		return $this->connect;
	}
	/**
	 * 取得最近一次 INSERT,UPDATE 或 DELETE 所影响的记录行数.如果最近一次查询失败的话,函数返回 -1.
	 * UPDATE: 只有真正被修改的记录数才会被返回
	 * REPLACE: 返回的是被删除的记录数加上被插入的记录数.
	 * Transactions: 需要在 INSERT,UPDATE 或 DELETE 查询后调用此函数,而不是在 COMMIT 命令之后. 
	 * @return int 错误或连不上返回-1
	 */
	public function affected_rows(){
		return $this->connect ? mysql_affected_rows( $this->link_identifier) : -1;
	}
	/**
	 * 返回字符集的名称
	 * @return String
	 */
	public function client_encoding(){
		return $this->connect() ? mysql_client_encoding( $this->link_identifier) : '';
	}
	/**
	 * 关闭 MySQL 非持久连接
	 * @return Boolean
	 */
	public function close(){
		return $this->connect && (($this->connected = $this->connect = false) || mysql_close( $this->link_identifier));
	}
	/**
	 * 移动内部结果的指针.只能和 mysql_query() 结合起来使用,而不能用于 mysql_unbuffered_query()
	 * @return Boolean
	 */
	public function data_seek($result, $row_number){
		return is_resource( $result) && ($row_number >= 0) && @mysql_data_seek($result, $row_number);
	}
	/**
	 * 取得 mysql_list_dbs() 调用所返回的数据库名
	 */
	public function db_name($result, $row, $field=null){
		return is_resource( $result) && ($row >=0 ) ? mysql_db_name($result, $row, $field) : '';
	}
	/**
	 * 返回上一个 MySQL 操作中的错误信息的数字编码.如果没有出错则返回 0
	 * @return int
	 */
	public function errno(){
		return is_resource( $this->link_identifier) ? mysql_errno( $this->link_identifier) : mysql_errno();
	}
	/**
	 * 返回上一个 MySQL 操作产生的文本错误信息.如果没有错误则返回 ''
	 * @return String
	 */
	public function error(){
		return is_resource( $this->link_identifier) ? mysql_error( $this->link_identifier) : mysql_error();
	}
	/**
	 * 转义一个字符串用于 query
	 * @return String
	 */
	public function escape_string( $unescaped_string){		
		return (PHP_VERSION > '5.3.0' ) ? addslashes( trim( $unescaped_string)) : mysql_escape_string( trim( $unescaped_string));
	}
	/**
	 * 从结果集中取得一行作为关联数组，或数字数组，或二者兼有(带指针移动)
	 * @return Array
	 */
	public function fetch_array($result, $result_type=MYSQL_BOTH){
		return $this->connect && ($result = mysql_fetch_array($result, $result_type)) ? $result : array();
	}
	/**
	 * 从结果集中取得列信息并作为对象返回
	 * @param $field_offset int
	 * @return Object/false
	 */
	public function fetch_field($result, $field_offset=null){
		return is_resource($result) ? (is_null( $field_offset) ? @mysql_fetch_field($result) : @mysql_fetch_field($result, $field_offset)) : false;
	}
	/**
	 * 返回上一次用 fetch_*() 取得的行中每个字段的长度.必须要在 fetch_* 之后再执行此方法
	 * @return Array
	 */
	public function fetch_lengths( $result){
		return is_resource( $result) && ($result = mysql_fetch_lengths( $result)) ? $result : array();
	}
	/**
	 * 从结果集中取得一行作为对象(带指针移动)
	 * @return Object/false
	 */
	public function fetch_object( $result){
		return $this->connect && ($result = mysql_fetch_object( $result)) ? $result : false;
	}
	/**
	 * 从结果集中取得一行作为枚举数组(带指针移动)
	 * @return Array
	 */
	public function fetch_row( $result){
		return $this->connect && ($result = mysql_fetch_row( $result)) ? $result : array();
	}
	/**
	 * 从结果集中取得一行作为关联数组(带指针移动)
	 * @return Array
	 */
	public function fetch_assoc( $result){
		return $this->connect && ($result = mysql_fetch_assoc( $result)) ? $result : array();
	}
	/**
	 * 从结果中取得和指定字段关联的标志
	 * @return String
	 */
	public function field_flags($result, $field_offset){
		return is_resource( $result) && ($result = @mysql_field_flags($result, $field_offset)) ? $result : '';
	}
	/**
	 * 返回结果中指定字段的长度.指字节,如UTF-8则为宽度*3
	 * @param $field_offset int 第几列字段
	 * @return int
	 */
	public function field_len($result, $field_offset){
		return is_resource( $result) && ($result = @mysql_field_len($result, $field_offset)) ? $result : 0;
	}
	/**
	 * 取得结果中指定字段的字段名
	 * @param $field_index int 第几列字段
	 * @return String
	 */
	public function field_name($result, $field_index){
		return is_resource( $result) && ($result = @mysql_field_name($result, $field_index)) ? $result : '';
	}
	/**
	 * 将结果集中的指针设定为制定的字段偏移量
	 * @return int
	 */
	public function field_seek($result, $field_offset){
		return is_resource( $result) && ($result = @mysql_field_seek($result, $field_offset)) ? $result : 0;
	}
	/**
	 * 取得结果集中指定字段所在的表名
	 * @param $field_offset int 第几列字段
	 * @return String
	 */
	public function field_table($result, $field_offset){
		return is_resource( $result) && ($result = @mysql_field_table($result, $field_offset)) ? $result : '';
	}
	/**
	 * 取得结果集中指定字段的类型
	 * @param $field_offset int 第几列字段
	 * @return String
	 */
	public function field_type($result, $field_offset){
		return is_resource( $result) && ($result = @mysql_field_type($result, $field_offset)) ? $result : '';
	}
	/**
	 * 释放结果内存
	 * @return Boolean
	 */
	public function free_result( $result){
		return is_resource( $result) && mysql_free_result( $result);
	}
	/**
	 * 取得 MySQL 客户端信息
	 * @return String
	 */
	public function get_client_info(){
		return mysql_get_client_info();
	}
	/**
	 * 取得 MySQL 主机信息
	 * @return String
	 */
	public function get_host_info(){
		return $this->connect() ? mysql_get_host_info( $this->link_identifier) : ''; 
	}
	/**
	 * 取得 MySQL 协议信息
	 * @return int
	 */
	public function get_proto_info(){
		return $this->connect() ? mysql_get_proto_info( $this->link_identifier) : 0;
	}
	/**
	 * 取得 MySQL 服务器版本
	 * @return String
	 */
	public function get_server_info(){
		return $this->connect() ? mysql_get_server_info( $this->link_identifier) : '';
	}
	/**
	 * 取得最近一条查询的信息.仅针对INSERT,UPDATE,ALTER,LOAD DATA INFILE
	 * @return String
	 */
	public function info(){
		return $this->connect && ($result = mysql_info( $this->link_identifier)) ? $result : '';
	}
	/**
	 * 取得上一步 INSERT 操作产生的 ID
	 * @return int 如果自增字段是bigint,则返回值会有问题
	 */
	public function insert_id(){
		if($this->connect && ($insert_id = mysql_insert_id( $this->link_identifier))){
			return $insert_id;
		}
		$result = $this->query('SELECT LAST_INSERT_ID()');
		return (int)$this->result($result, 0);
	}
	/**
	 * 列出 MySQL 服务器中所有的数据库.返回资源集,然后用fetch_array获取实际数据
	 * @return Resource/null
	 */
	public function list_dbs(){
		return $this->connect() ? mysql_list_dbs( $this->link_identifier) : null;
	}
	/**
	 * 列出 MySQL 进程
	 * @return Resource
	 */
	public function list_processes(){
		return $this->connect() ? mysql_list_processes( $this->link_identifier) : array();
	}
	/**
	 * 取得结果集中字段的数目
	 * @return int
	 */
	public function num_fields( $result){
		return is_resource( $result) ? mysql_num_fields( $result) : 0;
	}
	/**
	 * 取得结果集中行的数目
	 * @return int
	 */
	public function num_rows( $result){
		return is_resource( $result) ? mysql_num_rows( $result) : 0;
	}
	/**
	 * Ping 一个服务器连接，如果没有连接则重新连接 wait timeout
	 */
	public function ping(){
		return $this->connect && mysql_ping( $this->link_identifier);
	}
	/**
	 * 发送一条 MySQL 查询
	 * @return Boolean/Resource 针对 SELECT，SHOW，DESCRIBE 或 EXPLAIN 语句返回资源集,其他为Boolean
	 */
	public function query( $query){
		$result = $this->connect() && ($result = @mysql_query($query, $this->link_identifier)) ? $result : $this->errorlog('Query', false, $query);
		if( defined('CMS_ROOT') && class_exists('sys') && (stripos($query, 'SELECT') !== 0) && (stripos($query, 'SHOW') !== 0) ) sys::log($query, 'sql');//cmsapi操作日志
		return $result;
	}
	/**
	 * 转义 SQL 语句中使用的字符串中的特殊字符，并考虑到连接的当前字符集
	 * @return String
	 */
	public function real_escape_string($unescaped_string){
		return $this->connect() ? mysql_real_escape_string($unescaped_string, $this->link_identifier) : $this->escape_string( $unescaped_string);
	}
	/**
	 * 返回结果集中一个单元的内容.$row 0,$field 0表示结果集第0条第0个单元格的数据.不能是unbuffered_query
	 * @return Mixed
	 */
	public function result($result, $row, $field=null){
		return $this->connect ? mysql_result($result, $row, $field) : null;
	}
	/**
	 * 选择 MySQL 数据库.
	 * @return Boolean 没有连接上或者不存在的db
	 */
	public function select_db( $database_name){
		return $this->connect() && mysql_select_db($database_name, $this->link_identifier);
	}
	/**
	 * 设置客户端字符集
	 * @return Boolean
	 */
	public function set_charset( $charset){
		return $this->connect() && mysql_set_charset( $charset, $this->link_identifier);
	}
	/**
	 * 取得当前系统状态
	 * @return String
	 */
	public function stat(){
		return $this->connect() ? mysql_stat( $this->link_identifier) : '';
	}
	/**
	 * 返回当前线程的 ID
	 * @return int
	 */
	public function thread_id(){
		return $this->connect() ? mysql_thread_id( $this->link_identifier) : 0;
	}
	/**
	 * 向 MySQL 发送一条 SQL 查询,并不获取和缓存结果的行.
	 * 返回的结果集之上不能使用 mysql_num_rows() 和 mysql_data_seek()。此外在向 MySQL 发送一条新的 SQL 查询之前，必须提取掉所有未缓存的 SQL 查询所产生的结果行。
	 * @return Boolean/Resource 不是一个SELECT查询或者连接失败.不管是不是SELECT,对应的语句都会被执行
	 */
	public function unbuffered_query( $query){
		$result = $this->connect() && ($result = mysql_unbuffered_query($query, $this->link_identifier)) ? $result : $this->errorlog( 'Unbuffered_query', false, $query);
		return $result;
	}
	
	/**
	 * 获取所有结果
	 * @return Array
	 */
	public function getAll($query, $result_type=MYSQL_BOTH){
		$result = $this->unbuffered_query( $query);
		while ($array = $this->fetch_array($result, $result_type)) {
			$aList[] = $array;        		
		}
		return (array)$aList;
	}
	/**
	 * 获取一行结果
	 * @return Array
	 */
	public function getOne($query, $result_type=MYSQL_BOTH){
		$result = $this->unbuffered_query( $query);
		return $this->fetch_array($result, $result_type);
	}
	/**
	 * 事务处理章节
	 */
	public function Start(){
		return $this->unbuffered_query("START TRANSACTION");
	}
	public function Commit(){
		return $this->unbuffered_query("COMMIT");
	}
	public function Rollback(){
		return $this->unbuffered_query("ROLLBACK");
	}
	public function __destruct(){
		
	}
	/**
	 * 记录错误,加上断线自动重连
	 * 连接不上会强制退出脚本.
	 * 其他则依照$this->die判断..
	 */
	private function errorlog( $msg, $die=false, $query=null){
		if(($this->errno() == 2006) && ($this->connectlast < time()-60) && $query) { //连接超时并且上次连接比这次超过60秒
			$this->close(); //强制关闭清理各状态
			return $this->query( $query); //返回结果资源
		}
		($this->die || $die) && trigger_error("Query: {$query} // #".$this->errno().' '.$this->error(), E_USER_ERROR);
		return false;
	}
}