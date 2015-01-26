<?php
namespace I;
/**
 * 抓取网站博客内容接口
 */
interface Blog{
	public function grap($url);
	public function grapTitle();
	public function grapBody();
}