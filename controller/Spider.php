<?php
namespace C;
/**
 * 蜘蛛爬去控制器
 */
abstract class Spider{
	/**
	 * @param int $level 爬取的层级
	 */
	protected $level;

	/**
	 * @param int $level 爬取的最大层级
	 */
	protected $maxLevel;

	/**
	 * @param const MAX_SPIDER_NUM 最大爬取数量
	 */
	const MAX_SPIDER_NUM = 100;

	/**
	 * @param array 保存爬取的内容和层数
	 *
	 *array(
	 * 	'total' => 10, //当前的链接个数
	 *  0 => array( //第1级的urls
	 *			'http://www.baidu.com',
	 *			...
	 *		),
	 *	1 => array( //第1级的urls
	 *			'http://www.baidu.com',
	 *			...
	 *		),
	 *		...
	 *)
	 */
	protected $urlsinfo = array();

	/**
	 * @param array 保存链接的md5值，标志是否已经保存过一个url到$urlsinfo了
	 */
	protected $pagesurl_md5 = array();

	/**
	 * @param array 临时保存获取到的页面信息
	 */
	protected $pagesinfo = array();
	/**
	 * 构造函数
	 */
	public function __construct($base_url, $level=2){
		$this->maxLevel = $level;
		$this->level = 0;
		$this->urlsinfo[0] = array($base_url);
		$this->urlsinfo['total'] = 1;
		$this->pagesurl_md5[md5($base_url)] = 0; // 标志base_url已经存储过了
		for ($i=1; $i<=$level ; $i++) { 
			$this->urlsinfo[$i] = array();
		}
	}

	/**
	 * 抓取一个页面
	 */
	public function getPageInfo($url){
		$this->pagesinfo[md5($url)] = file_get_contents($url);
	}

	/**
	 * 抓取一个页面的url
	 */
	abstract public function getUrls($level, $url);

	/**
	 * 保存匹配到的url
	 */
	public function pushUrl($level, $url){
		if(!isset($this->pagesurl_md5[md5($url)])){
			array_push($this->urlsinfo[$level], $url);
			$this->pagesurl_md5[md5($url)] = 0;
		}
	}

	/**
	 * 获取所有的urls
	 */
	public function spiderUrls(){
		for($level=$this->level; $level<$this->maxLevel; $level++){
			foreach ($this->urlsinfo[$level] as $key => $value) {
				$this->getUrls($level+1, $value);
			}
		}
		$ret = array();
		for($level=$this->level+1; $level<=$this->maxLevel; $level++){
			$ret = array_merge($ret, $this->urlsinfo[$level]);
		}
		return $ret;
	}
}