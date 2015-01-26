<?php
namespace M;
/**
 * 博客内容保存模型类文件
 */
use L;
class Blog{
	/**
	 * 保存连接的句柄
	 */
	private $instance = null;
	/**
	 * 
	 */
	public function __construct($aServer){
		$this->instance == null 
		&& $this->instance = new L\Mysql($aServer);
		return $this;
	}

	/**
	 * 保存抓取的标题和文章
	 */
	public function save($title, $body, $time, $author, $authorpage){
		$authorpage = addslashes(trim($authorpage));
		$sql = "select * from cnblogs where authorpage='{$authorpage}'";
		$info = $this->instance->getOne($sql);
		if(!empty($info)){
			echo "重复url：".$authorpage."\r";
			return false;
		}
		$title = addslashes(trim($title));
		$body  = addslashes(trim($body));
		$time  = intval($time);
		$author 	= addslashes(trim($author));
		return $this->instance->query("insert into cnblogs (title,body,time,author,authorpage) values ('{$title}','{$body}',{$time},'{$author}','{$authorpage}')");
	}
}