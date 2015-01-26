<?php
namespace C;
/**
 * 抓取博客内容的基类
 */
use I,M;
abstract class Blog implements I\Blog{
	/**
	 * 临时保存抓取来的界面
	 */
	protected $page = null;

	/**
	 * 保存url
	 */
	protected $url = null;

	/**
	 * 检测url是否合法
	 */
	public function check($url){

	}

	/**
	 * 抓取页面
	 */
	public function grap($url){
		$this->url = $url;
		$this->page = file_get_contents($url);
	}
}