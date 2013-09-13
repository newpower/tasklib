<?php ## Главный конфигурационный файл сайта.
// Подключается ко всем сценариям (автоматически или вручную)

header('Content-type: text/html; charset=utf-8');
setlocale(LC_ALL, "ru_RU.UTF-8");

if (!defined("PATH_SEPARATOR"))
  define("PATH_SEPARATOR", getenv("COMSPEC")? ";" : ":");
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__));

//Define autoloader
//function __autoload($className) {
//	$path=dirname(__FILE__).str_replace("_", "/", $className)."php";
//      if (file_exists($path)) {
//          require_once $path;
//          return true;
//      }
//      return false;
//} 



//Устанавливаем префикс для таблиц
define(TABLE_PREFIX, 'agro2b_'); // с подчерком!

//require_once dirname(__FILE__)."/DbSimple/Generic.php";
//require_once dirname(__FILE__)."/TaskManager.php";
//require_once dirname(__FILE__)."/Debug/HackerConsole/Main.php";

//require_once dirname(__FILE__)."/getpage.php";


?>
