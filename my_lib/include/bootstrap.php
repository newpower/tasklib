<?php

function __autoload($objName)
{
	$fileName = DIR_ROOT."/my_lib/".str_replace('_', '/', $objName).".php";
    	// ищем в папке с внутренними классами системы
    	
    if (file_exists($fileName)) {
		require_once $fileName;
		return true;
	}
	     return false;
}

?>