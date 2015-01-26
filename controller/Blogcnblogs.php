<?php
namespace C;
/**
 * 抓取cnblogs博客内容的类库
 */
use M;
class Blogcnblogs extends Blog{
	public function grapTitle(){
		$reg = "#<a\s*?id=\"cb_post_title_url\"[^>]*?>(.*?)<\/a>#is";
		if(preg_match($reg, $this->page, $out)){
			return $out[1];
		}
		return null;
	}

	public function grapBody(){
		$reg = "#(<div\s*?id=\"cnblogs_post_body\"[^>]*?>.*)<div\s*id=\"blog_post_info_block\">#is";
		if(preg_match($reg, $this->page, $out)){
			return $out[1];
		}
		return null;
	}

	/**
	 * 获取推荐、反对的次数
	 */
	// public function grapDigg(){
	// 	$reg = "#id=\"digg_count\">(\d+)<\/span>.*?id=\"bury_count\">(\d+)<\/span>#is";
	// 	if(preg_match($reg, $this->page, $out)){
	// 		return array(
	// 			'digg' => $out[1],
	// 			'bury' => $out[2],
	// 		);
	// 	}
	// 	return null;
	// }
	/**
	 * 获取最后编辑时间，浏览次数，评论次数
	 */
	public function grapPostDesc(){
		$reg = "#<span\s*?id=\"post-date\">(.*?)<\/span>\s*?<a\s*href=\'([^>]*)\'>(.*?)<\/a>#is";
		if(preg_match($reg, $this->page, $out)){
			$time = strtotime($out[1]);
			return array(
				'time' => $time,
				'author' => $out[3],
				'authorpage' => $out[2],
			);
		}
		return null;
	}
	public function save(){
		$Mblog = new M\Blog(array("localhost","root","","blog"));
		$title = $this->grapTitle();
		$body  = $this->grapBody();
		$desc  = $this->grapPostDesc();
		$time  = isset($desc['time']) ? $desc['time'] : 0;
		$author= isset($desc['author']) ? $desc['author'] : "anonymous";
		$url   = $this->url ? $this->url : "randUrl:".rand();
		if($title !== null && $body !== null){
			$Mblog->save($title, $body, $time, $author, $url);
		}
	}
}



