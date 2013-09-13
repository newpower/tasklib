<?php
/*
 * Библиотека обработки задач
 * @input array data connection
 */
class TaskManager_Main {
	private $arr_agent;
	private $arr_task=array();
	private $arr_ident;

	function get_agent()
	{
			//Блокируем таблицы
			$this->arr_agent = $this->arr_ident["db1"]->selectRow('SELECT * FROM ?_task_manager_executor WHERE id=?', $this->arr_ident["executor_id"]);
			$this->arr_agent["role"] = $this->arr_ident["db1"]->selectCol('SELECT ?# FROM ?_task_manager_executor_role WHERE `executor_id` =?', 'name',$this->arr_agent["id"]);
	
	}

	//Конструктор класа, задаем переменные для подключения и идентификации
	function __construct($arr_param)
	{
		$this->arr_ident=$arr_param;
		if (!isset($this->arr_ident["max_time"])){
			$this->arr_ident["max_time"]=60;
		}
	}


function get_agent_as_array()
{
		if (!isset($this->arr_agent))  {
			$this->get_agent();
		}
		return $this->arr_agent;
}
function get_task()
{

	if (($this->arr_ident["db1"]) and ($this->arr_ident["executor_id"])){
		$this->arr_ident["db1"]->query('LOCK TABLES ?_task_manager_base WRITE, ?_task_manager_executor WRITE,?_task_manager_executor_role WRITE;');
		
		if (!isset($this->arr_agent))  {
			$this->get_agent();
		}

		$a=0;

		$arr_task_id=array('0'=>'0');
		while (($a < $this->arr_ident["max_time"]) and ($this->arr_agent["active"])) {
						
			$sql='SELECT *
				FROM ?_task_manager_base
				WHERE (
				DATE_ADD( `date_task_start` , INTERVAL `task_run_minute_planned`
				MINUTE ) <
				CURRENT_TIMESTAMP OR `date_task_start` = \'0000-00-00 00:00\'
				)
				AND `start_date` <
				CURRENT_TIMESTAMP AND `task_code` =0 
				and id not in (?a)
				and task_type in (?a)
				';

			$arr_task_one= $this->arr_ident["db1"]->selectRow($sql, $arr_task_id, $this->arr_agent["role"]);
			 if (count($arr_task_one)>0){
			 	$arr_task_id[]=$arr_task_one["id"];
				//echo $arr_task_one["id"]."string";
				$arr_update=array(
					
					'date_edit'=>date("Y-m-d H:i:s"),
					'start_executor_id'=>$this->arr_ident["executor_id"],
					'task_attempt'=>$arr_task_one["task_attempt"]+1,
				);
				if ($arr_task_one["task_attempt"] > 5){
					$arr_update["task_code"]=500;
				}
				$this->update_task_executor(array('executor_count_task_set'=> $this->arr_agent["executor_count_task_set"]+1));
				$this->update_task($arr_update, $arr_task_one["id"]);
				
				$this->arr_task[]=$arr_task_one;
				$a=$a+$arr_task_one["task_run_minute_planned"];
			 }
			 else {
				 $a = $this->arr_ident["max_time"];
			 }
		}
	}
	$this->arr_ident["db1"]->query('UNLOCK TABLES;');
	return $this->arr_task;
}

	function update_task($arr_update,$id_task)
	{
		$this->arr_ident["db1"]->query('UPDATE ?_task_manager_base SET ?a , `date_task_start`=CURRENT_TIMESTAMP where id=?', $arr_update, $id_task);
		
	}
	function update_task_executor($arr_update)
	{
		$this->arr_ident["db1"]->query('UPDATE ?_task_manager_executor SET ?a where id=?', $arr_update, $this->arr_agent["id"]);
	}
	
	function set_new_task($arr_update)
	{
		$arr_update["date_add"]=date("Y-m-d H:i:s");
		$arr_update["date_edit"]=date("Y-m-d H:i:s");
				
		$this->insert_new_task($arr_update);
	}	

	function set_end_task($arr_update)
	{
		$arr_update["date_task_end"]=date("Y-m-d H:i:s");
		$arr_update["date_edit"]=date("Y-m-d H:i:s");
				
		$this->update_task($arr_update, $id_task);
	}	
		
	function insert_new_task($arr_update)
	{
		$this->arr_ident["db1"]->query('INSERT INTO ?_task_manager_base (?#) VALUES(?a)', array_keys($arr_update), array_values($arr_update));
	}





}

/*
 * Функции
 * 
 * 
 */

function set_new_task_download_news_rss($arraydata,$arr_ident)
{
	$arr_task=array(
		'task_comment'=>'download rss and parse',
		'task_type'=> 'get_page',
		'start_function'=>'reader_rss_one',
	);
	$arr_task_ret=array(
	'task_code'=>0,
	);
	
		$query = "SELECT *,DATE_ADD(`date_rss_read`, INTERVAL `ttl_time` minute) as `date_new_read` from `agro2b_rss_reader_sources` where DATE_ADD(`date_rss_read`, INTERVAL `ttl_time` minute) < CURRENT_TIMESTAMP and `parse_active`='1' limit 0,1000;";
		get_connect_db(1);
	$result_all = mysql_query($query) or   $arr_task_ret['task_history']=date("Y-m-d H:i:s")."Query failed : " . mysql_error().$query."<br>";
	
	
	
  	while ($line = mysql_fetch_array($result_all, MYSQL_ASSOC)) 
	{
		$count=0;
		$arr_task['start_param']='';
  		if ($line["link_rss"]) 
  		{
  			$arr_task['start_param']=json_encode(array('id'=>$line["id"],'link_rss'=>$line["link_rss"]));
			$handler_new_task=new TaskManager_Main($arr_ident);
			$handler_new_task->set_new_task($arr_task);
			$count++;

		}
		else 
		{
			
		}
    }

	$arr_task_ret["task_code"]=1;
	$arr_task=array(
		'task_comment'=>'create task for start new',
		'task_type'=> 'db_work',
		'start_function'=>'set_new_task_download_news_rss',
		'start_date'=> date('Y-m-j H:i:s',mktime(0, 0, 0, date("m"),   date("d"),   date("Y"), date("H"), date("i")+10)),
	);
	$handler_new_task=new TaskManager_Main($arr_ident);
	$handler_new_task->set_new_task($arr_task);	
	

	$arr_task_ret['task_history']=date("Y-m-d H:i:s")."Добавлено ".$count." задач <br>".$arr_task_ret['task_history'];
	return $arr_task_ret;
}

 
function reader_rss_one($arraydata,$arr_ident)
{
	$arraymodelnews=array();
		//id, name, descrition, link_main, link_rss, link_image, lang, managing_editor_name, managing_editor_mail, date_add, date_edit, date_rss_read, ttl_time

		 	$page=new GetPage_Main();
	$arr_page =array();
	$arr_return=array();

	if (isset($arr_ident["proxy_download"]))
	{
		if (isset($arraydata["http_options"]))		{	$arraydata["http_options"]=array_merge($arr_ident["proxy_download"],$arraydata["http_options"]);		}
		else {	$arraydata["http_options"]=$arr_ident["proxy_download"];	}
	}	
	
	if (isset($arraydata["http_options"])){$page->set_http_options($arraydata["http_options"]);}

	$array_page=$page->get_page_run($arraydata["link_rss"]);

	$count_new=0;
	//echo $array_page["html"]."string".$array_page["http_code"];
	//Запускаем скачивание если астановлен флаг активности
	if ($arraydata["parse_active"])
	{
		$bodytag = str_replace("yandex:full-text", "yandex_full_text", $array_page["html"]);
				libxml_use_internal_errors(true);
		$xml= simplexml_load_string($bodytag);

		
		if (!$xml) {
		    echo "Ошибка загрузки XML\n <br>";
		    foreach(libxml_get_errors() as $error) {
		        echo "<hr color=red>\t", $error->message."<br><br>";
		    }
		}
		//foreach ($xml->channel as $news)
		//{
		//	echo '<B><u>'.$news->title.'</u></b> ';
			//echo '('.$sort->lastBuildDate.')<BR><BR>';
		//}
		foreach ($xml->channel->item as $news)
		{
			//link, title, description, pubDate, guid, category, author, yandex_full_text, text_news, language, date_add, date_edit, enclosure, id_sources, comments
			
			$arraymodelnews["pubDate"]=Rfc2822ToTimestamp($news->pubDate);
			$arraymodelnews["yandex_full_text"]=$news->yandex_full_text;
			$arraymodelnews["description"]=$news->description;
			$arraymodelnews["title"]=$news->title;
			$arraymodelnews["link"]=$news->link;
			$arraymodelnews["guid"]=$news->guid;
			$arraymodelnews["category"]=$news->category;
			$arraymodelnews["author"]=$news->author;
			$arraymodelnews["language"]=$arraydata["lang"];
			$arraymodelnews["id_sources"]=$arraydata["id"];
			$arraymodelnews["date_add"]=date("Y-m-d H:i:s");
			$arraymodelnews["date_edit"]=date("Y-m-d H:i:s");
			
	
	
			//echo $news->title.' - '.Rfc2822ToTimestamp($news->pubDate).$arraydata["link_rss"].'<BR><hr>';
	
			//foreach ($news as $key=>$value)
			//foreach ($arraymodelnews as $key=>$value)
			//{ 
				//	echo $key.' - '.$value.'<BR><BR>';
			//}
			$count_new=$count_new+set_news($arraymodelnews);
			
		echo "Ресурс:".$line["link_rss"]." NEWS NEW:".$count."<br>";
		$query = "UPDATE `agro2b_rss_reader_sources` SET `date_rss_read`=CURRENT_TIMESTAMP where `id` = '".$arraydata["id"]."' limit 1;";
	
		$result = mysql_query($query) or die("Query failed : " . mysql_error().$query);
		
		$query = "INSERT INTO `agro2b_rss_new_statistics` (`id_rss_cources`, `count_new`, `date_add`, `date_edit`) values ('".$arraydata["id"]."', '".$count_new."',CURRENT_TIMESTAMP,CURRENT_TIMESTAMP);";
		$result = mysql_query($query) or die("Query failed : " . mysql_error().$query);
  
 
			
		}
	}
	return $count_new;
	//exit;
	
}

function set_news($news=array(),$iddata=array())
{
	$my_str1="";
	$my_str2="";
	$my_str3="";
	$query ="";
	$count=0;
	$srparat="";
	
	
	foreach ($news as $key => $value)
	{
			$count=$count+1;
			if ($count >1){	$srparat=",";}
			if (strlen($value) > 0)
			{
				$my_str1=$my_str1.$srparat."`".addslashes($key)."`";
				$my_str2=$my_str2.$srparat."'".addslashes($value)."'";
				$my_str3=$my_str3.$srparat."`".addslashes($key)."`='".addslashes($value)."'";
			}	
		if (isset($iddata["id"]) and  isset($iddata["value"]))
		{
			
			$query = "UPDATE `agro2b_rss_reader_all` SET $my_str3 where `".$iddata["id"]."` = '".$iddata["value"]."';";
		}
		else {
			$query = "INSERT INTO `agro2b_rss_reader_all` ($my_str1) values ($my_str2);";
		}
	}
	$count_new=1;
	//echo $query;
	$result = mysql_query($query) or $count_new=0;
	return $count_new;
}



function get_connect_db($id=1)
{
	if ($id==1)
	{
		$data_host="93.171.202.18"; 
		$database="user2324_main"; 

		$data_user="user2324_nawww"; 
		$data_user_pass="1234567aA"; 	
	}
	
			
			
    $link = mysql_connect($data_host, $data_user, $data_user_pass)
        or die("Could not connect : " . mysql_error());
    print "Connected successfully";
    mysql_select_db("user2324_main") or die("Could not select database");
	
    $query = "SET NAMES utf8";
    $result = mysql_query($query) or die("Query failed : " . mysql_error());
}

?>