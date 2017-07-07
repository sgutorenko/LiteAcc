<?php 
require 'user.php';
$extended=file_exists('extended.php');
$nonEditable= array(
	'Accounts' => array('Счет','Дебет','Кредит','Количество','Цена_Розн','Код','ИНН'),
	'Entries'  => array('Дата_Время','Номер'),
	'Goods'    => array('Счет','Дебет','Кредит','АП','Количество','ИНН'),
	'Invoice'  => array('Номер'),
	'Stencil'  => array('Номер'),
	'CounterAgents'    => array('Счет','Дебет','Кредит','АП','Количество','Цена_Розн','Код'),
	'LoadBank'	=> array('Номер','Дата','Сумма','НазначениеПлатежа','ИНН','НомерДок'),
);
$nonAddable= array(
	'Accounts' => array('Дебет','Кредит','Количество','Цена_Розн','Код','ИНН'),
	'Entries'  => array('Дата_Время','Номер'),
	'Goods'    => array('Дебет','Кредит','АП','Количество','ИНН'),
	'Invoice'  => array('Номер'),
	'Stencil'  => array('Номер'),
	'CounterAgents'    => array('Дебет','Кредит','АП','Количество','Цена_Розн','Код'),
);
$Aliases= array(
	'Goods'    => 'Accounts',
	'CounterAgents' => 'Accounts'
);
$columnAliases= array(
	'Goods'    => array("`Сумма`" => "(`Дебет`-`Кредит`) AS `Сумма`", 
	                    "`Цена_Уч`" => "IF(`Количество`,ROUND((`Дебет`-`Кредит`)/`Количество`,2),'') AS `Цена_Уч`"
					)
);
$notShownAccounts= array('41','60','62');
$quantAccs= array('41');
$reserved="/(INFORMATION_SCHEMA|select|alter|table|update|CONCAT|from|where|schema|delete|insert|GROUP BY|UNION)/i";
const ACCOUNTS = 0b00000001;
const ENTRIES  = 0b00000010;
const GOODS    = 0b00000100;
const INVOICE  = 0b00001000;
const STENCIL  = 0b00010000;
const CTRAGENTS= 0b00100000;
const INITIAL  = 0b10000000;
const DLMTR    = ' ';			// разделитель групп разрядов в суммах (пробел)
const SALESACC = '90';			// счет учета продаж
const CODETEMPL= "/^\d+$/";		// 	Регулярка для (штрих)кода
const ELINES   = 50;				// Количество строк в одной порции вывода в "Проводках"
function get_field_info($name) { 
	global $mysqli;
	if(!isset($mysqli)) {
		$mysqli = new mysqli(SERVER, USERNAME, DBPASS,DBNAME) or die('Ошибка подключения (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
		$set=true;
	}
	$query="SHOW COLUMNS FROM `$name`";
    $result = $mysqli->query($query) or die("Query failed : " . $mysqli->error);
	$finfo=[];
	while($line = $result->fetch_array(MYSQLI_NUM)) $finfo[$line[0]]=$line[1];
    $result->free();
	if($set) $mysqli->close();
	return $finfo;
}
function toSQLDate($str) {
	if($str=='') return '';
	return substr($str,6,4).'-'.substr($str,3,2).'-'.substr($str,0,2);
}
function toOurDate($str) {
	return substr($str,8,2).'.'.substr($str,5,2).'.'.substr($str,0,4).substr($str,10);
}
function php2json($obj){
	if(count($obj) == 0) return '[]';
	$is_obj = isset($obj[count($obj) - 1]) ? false : true;
	$str = $is_obj ? '{' : '[';
	foreach ($obj AS $key  => $value) {
	   $str .= $is_obj ? '"' . addslashes($key) . '"' . ':' : '';
	   if (is_array($value))   $str .= php2json($value);
	   elseif (is_null($value))    $str .= 'null';
	   elseif (is_bool($value))    $str .= $value ? 'true' : 'false';
	   elseif (is_numeric($value)) $str .= $value;
	   else                        $str .= '"' . addslashes($value) . '"';
	   $str .= ',';
	   }
	return substr_replace($str, $is_obj ? '}' : ']', -1);
}
$headers=getallheaders();
if($headers['X-Type']=='XMLHttpRequest' && count($_GET)>0) {
	$reply=[];
	if(isset($_GET['MoreEntries']) && preg_match("/^\d+$/",$_GET['MoreEntries'])) {
		$mysqli = new mysqli(SERVER, USERNAME, DBPASS,DBNAME) or die('Ошибка подключения (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
		$result = $mysqli->query("SELECT COUNT(`Номер`) FROM `Entries` LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
		$line = $result->fetch_array(MYSQLI_NUM);
		$reply['More']=((int)$line[0]>(int)$_GET['MoreEntries']+ELINES)?'More':'NoMore';
		$query="SELECT * FROM `Entries` ORDER BY `Дата` DESC, `Номер` DESC LIMIT {$_GET['MoreEntries']}, ".ELINES;
		$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
	    $finfo = $result->fetch_fields();
	    while($line = $result->fetch_array(MYSQLI_NUM)) {
			$str='';
			for($i=0;$i<count($line);$i++) if($finfo[$i]->type==10 || $finfo[$i]->type==7) $str.='|'.toOurDate($line[$i]); else $str.='|'.$line[$i];
			$reply['Entries'][]=$str;
		}
		echo php2json($reply);
		exit;
	}
	elseif(isset($_GET['SaveVidgets'])) {									// Сохранение положений виджетов
		$str='';
		foreach($_POST as $key=>$value) {
			if(preg_match('/^[a-zA-Z_]+$/',$key) && preg_match('/^\d+,\d+$/',$value)) $str.="$key=$value\r\n";
		}
		if($str) {
			$handle=fopen('user.dat','w');
			fwrite($handle,$userdata[0]."\r\n".$userdata[1]."\r\n".$userdata[2]."\r\n");
			fwrite($handle,$str);
			fclose($handle);
		}
		exit;
	}
	elseif(isset($_GET['CheckAccount'])) {										// Проверка существования счета. Возвращает 'OK' в reply и количество если счет есть
		$mysqli = new mysqli(SERVER, USERNAME, DBPASS,DBNAME) or die('Ошибка подключения (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
		if(isset($_GET['wide'])) $query = "SELECT `Наименование`,`Количество`,`Цена_Розн`,IF(`Количество`,ROUND((`Дебет`-`Кредит`)/`Количество`,2),'') AS `Цена_Уч` FROM `Accounts` WHERE `Счет`='{$_GET['CheckAccount']}' LIMIT 1";
		else $query = "SELECT `Количество` FROM `Accounts` WHERE `Счет`='{$_GET['CheckAccount']}' LIMIT 1";
		$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
	    $line = $result->fetch_array(MYSQLI_ASSOC);
		$reply['reply']= $result->num_rows>0 ? 'OK' : '';
		if($line) foreach($line as $key=>$value) $reply[$key]=$value;
		echo php2json($reply);
		exit;
	}
	elseif(isset($_GET['findCode'])) {										// поиск товара по коду. Возвращает 'OK' в reply и параметры товара, если код найден
		$mysqli = new mysqli(SERVER, USERNAME, DBPASS,DBNAME) or die('Ошибка подключения (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
//		$query = "SELECT `Наименование`,`Количество`,`Цена_Розн`,IF(`Количество`,ROUND((`Дебет`-`Кредит`)/`Количество`,2),'') AS `Цена_Уч` FROM `Accounts` WHERE `Код` LIKE '%{$_GET['findCode']}%' LIMIT 1";
		$query = "SELECT `Наименование`,`Количество`,`Цена_Розн`,IF(`Количество`,ROUND((`Дебет`-`Кредит`)/`Количество`,2),'') AS `Цена_Уч` FROM `Accounts` WHERE `Код` REGEXP '[[:<:]]{$_GET['findCode']}[[:>:]]' LIMIT 1";
		$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
	    $line = $result->fetch_array(MYSQLI_ASSOC);
		$reply['reply']= $result->num_rows>0 ? 'OK' : '';
		if($line) foreach($line as $key=>$value) $reply[$key]=$value;
		echo php2json($reply);
		exit;
	}
	elseif(isset($_GET['Search']) || isset($_GET['SearchInvoice'])) {
		$mysqli = new mysqli(SERVER, USERNAME, DBPASS,DBNAME) or die('Ошибка подключения (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
		$value=trim(preg_replace("/<.+>/",'',$mysqli->real_escape_string(iconv('UTF-8', 'windows-1251', $_POST['search']))));
		if(!preg_match($reserved,$value)) {
//			if(preg_match(CODETEMPL,$value)) $query = "SELECT `Счет`,`Наименование`,`Количество`,`Цена_Розн` FROM `Accounts` WHERE `Код` LIKE '%$value%'";
			if(preg_match(CODETEMPL,$value)) $query = "SELECT `Счет`,`Наименование`,`Количество`,`Цена_Розн` FROM `Accounts` WHERE `Код` REGEXP '[[:<:]]{$value}[[:>:]]'";
			else $query = "SELECT `Счет`,`Наименование`,`Количество`,`Цена_Розн` FROM `Accounts` WHERE `Наименование` LIKE '%$value%'";
			if($_GET['template'] && in_array($_GET['template'],$quantAccs)) $query.=" AND `Счет` LIKE '{$_GET['template']}.%'";
			$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
			$str='';
		    while($line = $result->fetch_array(MYSQLI_NUM)) {
				if(in_array(substr($line[0],0,2),$quantAccs)) $str.="{$line[0]} {$line[1]} ={$line[3]} #{$line[2]}|";
				else $str.="{$line[0]} {$line[1]}|";
			}
			if(isset($_GET['SearchInvoice'])) {
				if(preg_match(CODETEMPL,$value)) $query = "SELECT `Товар`,`Наименование`,`Количество`,`Цена_Розн` FROM `Invoice` WHERE `Код` = '$value'";
				else $query = "SELECT `Товар`,`Наименование`,`Количество`,`Цена_Розн` FROM `Invoice` WHERE `Наименование` LIKE '%$value%'";
				if($_GET['template'] && in_array($_GET['template'],$quantAccs)) $query.=" AND `Товар` LIKE '{$_GET['template']}.%'";
				$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
			    while($line = $result->fetch_array(MYSQLI_NUM)) {
					if(in_array(substr($line[0],0,2),$quantAccs)) $str.="{$line[0]} {$line[1]} ={$line[3]} #{$line[2]}|";
					else $str.="{$line[0]} {$line[1]}|";
				}
			}
			if($str) echo substr($str,0,strlen($str)-1);
		}
		exit;
	}
	elseif($_GET['GoodByTemplate']) {
		if(!preg_match("/^41\.\d+\.\d*\*+|41\.\d*\*+$/",$_GET['GoodByTemplate'])) exit;
		if(($i=strpos($_GET['GoodByTemplate'],'*'))===false) exit;
		$tpl=substr($_GET['GoodByTemplate'],0,$i);
		$digits=strlen($_GET['GoodByTemplate'])-$i;
		$templ=preg_replace("/\./","\.",$tpl);
		$templ="^".$templ."[0-9]{".$digits."}$";
		$mysqli = new mysqli(SERVER, USERNAME, DBPASS,DBNAME) or die('Ошибка подключения (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
		$result = $mysqli->query("SELECT `Товар` FROM `Invoice` WHERE `Товар` REGEXP '$templ' ORDER BY `Товар` DESC LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
		$line = $result->fetch_array(MYSQLI_NUM);
		$initial=substr($line[0],$i+1)+1;
		$result = $mysqli->query("SELECT `Счет` FROM `Accounts` WHERE `Счет` REGEXP '$templ'") or die('Ошибка MySQL: ' . $mysqli->error);
		$goods=[];
		while($line = $result->fetch_array(MYSQLI_NUM)) $goods[$line[0]]='';
		$max=10**$digits;
		while(isset($goods[$tpl.sprintf("%0{$digits}d",$initial)]) && $initial<$max) $initial++;
		if($initial>=$max) {echo "OVERFLOW"; exit;}
		echo $tpl.sprintf("%0{$digits}d",$initial);
		exit;
	}
}
require 'uchet_.php';
?>
