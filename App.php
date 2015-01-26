<?php
/**
 * 项目入口文件
 * @author 燕睿涛(luluyrt@163.com)
 * @time 2015年1月3日 13:11:31
 */
class App{
	protected static $apppath = null;
	private static function init(){
		self::$apppath = realpath(dirname("./"));

		include_once("./core/Autoload.php");
		$Autoload = new core\Autoload();
		$Autoload->addNamespace("L", self::$apppath.DIRECTORY_SEPARATOR."lib".DIRECTORY_SEPARATOR);
		$Autoload->addNamespace("M", self::$apppath.DIRECTORY_SEPARATOR."model".DIRECTORY_SEPARATOR);
		$Autoload->addNamespace("C", self::$apppath.DIRECTORY_SEPARATOR."controller".DIRECTORY_SEPARATOR);
		$Autoload->addNamespace("I", self::$apppath.DIRECTORY_SEPARATOR."interface".DIRECTORY_SEPARATOR);
		$Autoload->register();
	}

	public static function run(){
		self::init();
		$cnblogs = new C\Blogcnblogs();
		for($i=1;$i<=100;$i++){
			echo "PAGE{$i}*************************[begin]***************************\r";
			$spidercnblogs = new C\Spidercnblogs("http://zzk.cnblogs.com/s?t=b&w=php&p={$i}");
			$urls = $spidercnblogs->spiderUrls();
			foreach ($urls as $key => $value) {
				$cnblogs->grap($value);
				$cnblogs->save();
			}
		}
	}
}

App::run();