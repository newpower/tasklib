<?php ## Подключение к БД.


define('DIR_ROOT',    dirname(__FILE__));
define('DIR_HOME',    dirname(__FILE__));
define('DIR_PRIVATE', DIR_HOME);
define('DIR_PUBLIC',  DIR_ROOT);
//$base_dir=dirname(__FILE__);
require_once "./my_lib/include/bootstrap.php";
require_once "./my_lib/config.php";


new Debug_HackerConsole_Main(true);
// Output to default group.
Debug_HackerConsole_Main::out("Usual message");
// Dump random structure.
//Debug_HackerConsole_Main::out($_SERVER, "Input");

$arr_ident=array(
			'executor_id'=>115544,
			'max_time'=>60,
			);

//Для загрузчиков прокси, если нужно загружать страницы
$arr_ident["proxy_download"]=array('PROXY'=> 'ipp-proxy.yugrusiagro.ru:3128','CURLOPT_PROXYUSERPWD'=>'feofanov_ei:Iethae7z');


// Подключаемся к БД.
$arr_ident["db1"] = DbSimple_Generic::connect('mysql://user2324_nawww:1234567aA@93.171.202.18/user2324_main');
//$DATABASE =  DbSimple_Generic::connect('mysql://user2324_nawww:1234567aA@93.171.202.18/user2324_main');
$arr_ident["db1"]->setIdentPrefix(TABLE_PREFIX); 

// Устанавливаем обработчик ошибок.
//$DATABASE->setErrorHandler('databaseErrorHandler');

//подключаем логер базы 
$arr_ident["db1"]->setLogger('myLogger');
function myLogger($db, $sql)
{
  // Находим контекст вызова этого запроса.
  $caller = $db->findLibraryCaller();
  $tip = "at ".@$caller['file'].' line '.@$caller['line'];
  // Печатаем запрос (конечно, Debug_HackerConsole лучше).
   call_user_func(array('Debug_HackerConsole_Main', 'out'), "<xmp title=\"$tip\">\n\n".$sql."\n\n</xmp>");
  //echo "<xmp title=\"$tip\">"; 
 // print_r($sql); 
  //echo "</xmp>";
}
$task_man=new TaskManager_Main($arr_ident);
$arr_task=$task_man->get_task();

//$arr_task=get_task($arr_ident);
while (count($arr_task) > 0) {
	echo "Запущена в обработку задача";
	$arr_task_ret=array();
	$task_one=array_shift($arr_task);
	echo $task_one['start_function']." rr <br>";
	
	if (function_exists($task_one['start_function'])) {
		$arr_task_ret=$task_one['start_function'](json_decode($task_one['start_param'],true));
	    echo "IMAP functions are available.<br />\n";
	} else {
		//Обработка ошибки если нет такой функции
		$arr_agent=$task_man->get_agent_as_array($arr_ident);
	    $arr_task_ret['task_history']=date("Y-m-d H:i:s").'Ошибка при запуске обработчика задания не найдена функция\n <br>'.$task_one['task_history'];
	}
	$task_man->update_task($arr_task_ret, $arr_task["id"]);
}
	
	






echo "vse ok";
?>
