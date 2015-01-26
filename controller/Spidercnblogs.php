<?php
namespace C;
/**
 * cnblogs抓取模型类
 */
class Spidercnblogs extends Spider{
	public function getUrls($level, $url){
		$this->getPageInfo($url);
		preg_match_all("#href=\"(http:\/\/www.cnblogs.com\/[^><]*?\.html)\"#", $this->pagesinfo[md5($url)], $out);
		foreach ($out[1] as $key => $value) {
			$this->pushUrl($level, $value);
		}
	}
}