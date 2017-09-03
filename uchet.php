<?php 
require 'user.php';
$extended=file_exists('extended.php');
$nonEditable= array(
	'Accounts' => array('����','�����','������','����������','����_����','���','���'),
	'Entries'  => array('����_�����','�����'),
	'Goods'    => array('����','�����','������','��','����������','���'),
	'Invoice'  => array('�����'),
	'Stencil'  => array('�����'),
	'CounterAgents'    => array('����','�����','������','��','����������','����_����','���'),
	'LoadBank'	=> array('�����','����','�����','�����������������','���','��������'),
);
$nonAddable= array(
	'Accounts' => array('�����','������','����������','����_����','���','���'),
	'Entries'  => array('����_�����','�����'),
	'Goods'    => array('�����','������','��','����������','���'),
	'Invoice'  => array('�����'),
	'Stencil'  => array('�����'),
	'CounterAgents'    => array('�����','������','��','����������','����_����','���'),
);
$Aliases= array(
	'Goods'    => 'Accounts',
	'CounterAgents' => 'Accounts'
);
$columnAliases= array(
	'Goods'    => array("`�����`" => "(`�����`-`������`) AS `�����`", 
	                    "`����_��`" => "IF(`����������`,ROUND((`�����`-`������`)/`����������`,2),'') AS `����_��`"
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
const DLMTR    = ' ';			// ����������� ����� �������� � ������ (������)
const SALESACC = '90';			// ���� ����� ������
const CODETEMPL= "/^\d+$/";		// 	��������� ��� (�����)����
const ELINES   = 50;				// ���������� ����� � ����� ������ ������ � "���������"
function get_field_info($name) { 
	global $mysqli;
	if(!isset($mysqli)) {
		$mysqli = new mysqli(SERVER, USERNAME, DBPASS,DBNAME) or die('������ ����������� (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
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
		$mysqli = new mysqli(SERVER, USERNAME, DBPASS,DBNAME) or die('������ ����������� (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
		$result = $mysqli->query("SELECT COUNT(`�����`) FROM `Entries` LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
		$line = $result->fetch_array(MYSQLI_NUM);
		$reply['More']=((int)$line[0]>(int)$_GET['MoreEntries']+ELINES)?'More':'NoMore';
		$query="SELECT * FROM `Entries` ORDER BY `����` DESC, `�����` DESC LIMIT {$_GET['MoreEntries']}, ".ELINES;
		$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
	    $finfo = $result->fetch_fields();
	    while($line = $result->fetch_array(MYSQLI_NUM)) {
			$str='';
			for($i=0;$i<count($line);$i++) if($finfo[$i]->type==10 || $finfo[$i]->type==7) $str.='|'.toOurDate($line[$i]); else $str.='|'.$line[$i];
			$reply['Entries'][]=$str;
		}
		echo php2json($reply);
		exit;
	}
	elseif(isset($_GET['SaveVidgets'])) {									// ���������� ��������� ��������
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
	elseif(isset($_GET['CheckAccount'])) {										// �������� ������������� �����. ���������� 'OK' � reply � ���������� ���� ���� ����
		$mysqli = new mysqli(SERVER, USERNAME, DBPASS,DBNAME) or die('������ ����������� (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
		if(isset($_GET['wide'])) $query = "SELECT `������������`,`����������`,`����_����`,IF(`����������`,ROUND((`�����`-`������`)/`����������`,2),'') AS `����_��` FROM `Accounts` WHERE `����`='{$_GET['CheckAccount']}' LIMIT 1";
		else $query = "SELECT `����������` FROM `Accounts` WHERE `����`='{$_GET['CheckAccount']}' LIMIT 1";
		$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
	    $line = $result->fetch_array(MYSQLI_ASSOC);
		$reply['reply']= $result->num_rows>0 ? 'OK' : '';
		if($line) foreach($line as $key=>$value) $reply[$key]=$value;
		echo php2json($reply);
		exit;
	}
	elseif(isset($_GET['findCode'])) {										// ����� ������ �� ����. ���������� 'OK' � reply � ��������� ������, ���� ��� ������
		$mysqli = new mysqli(SERVER, USERNAME, DBPASS,DBNAME) or die('������ ����������� (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
//		$query = "SELECT `������������`,`����������`,`����_����`,IF(`����������`,ROUND((`�����`-`������`)/`����������`,2),'') AS `����_��` FROM `Accounts` WHERE `���` LIKE '%{$_GET['findCode']}%' LIMIT 1";
		$query = "SELECT `������������`,`����������`,`����_����`,IF(`����������`,ROUND((`�����`-`������`)/`����������`,2),'') AS `����_��` FROM `Accounts` WHERE `���` REGEXP '[[:<:]]{$_GET['findCode']}[[:>:]]' LIMIT 1";
		$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
	    $line = $result->fetch_array(MYSQLI_ASSOC);
		$reply['reply']= $result->num_rows>0 ? 'OK' : '';
		if($line) foreach($line as $key=>$value) $reply[$key]=$value;
		echo php2json($reply);
		exit;
	}
	elseif(isset($_GET['Search']) || isset($_GET['SearchInvoice'])) {
		$mysqli = new mysqli(SERVER, USERNAME, DBPASS,DBNAME) or die('������ ����������� (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
		$value=trim(preg_replace("/<.+>/",'',$mysqli->real_escape_string(iconv('UTF-8', 'windows-1251', $_POST['search']))));
		if(!preg_match($reserved,$value)) {
//			if(preg_match(CODETEMPL,$value)) $query = "SELECT `����`,`������������`,`����������`,`����_����` FROM `Accounts` WHERE `���` LIKE '%$value%'";
			if(preg_match(CODETEMPL,$value)) $query = "SELECT `����`,`������������`,`����������`,`����_����` FROM `Accounts` WHERE `���` REGEXP '[[:<:]]{$value}[[:>:]]'";
			else $query = "SELECT `����`,`������������`,`����������`,`����_����` FROM `Accounts` WHERE `������������` LIKE '%$value%'";
			if($_GET['template'] && in_array($_GET['template'],$quantAccs)) $query.=" AND `����` LIKE '{$_GET['template']}.%'";
			$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
			$str='';
		    while($line = $result->fetch_array(MYSQLI_NUM)) {
				if(in_array(substr($line[0],0,2),$quantAccs)) $str.="{$line[0]} {$line[1]} ={$line[3]} #{$line[2]}|";
				else $str.="{$line[0]} {$line[1]}|";
			}
			if(isset($_GET['SearchInvoice'])) {
				if(preg_match(CODETEMPL,$value)) $query = "SELECT `�����`,`������������`,`����������`,`����_����` FROM `Invoice` WHERE `���` = '$value'";
				else $query = "SELECT `�����`,`������������`,`����������`,`����_����` FROM `Invoice` WHERE `������������` LIKE '%$value%'";
				if($_GET['template'] && in_array($_GET['template'],$quantAccs)) $query.=" AND `�����` LIKE '{$_GET['template']}.%'";
				$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
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
		$mysqli = new mysqli(SERVER, USERNAME, DBPASS,DBNAME) or die('������ ����������� (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
		$result = $mysqli->query("SELECT `�����` FROM `Invoice` WHERE `�����` REGEXP '$templ' ORDER BY `�����` DESC LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
		$line = $result->fetch_array(MYSQLI_NUM);
		$initial=substr($line[0],$i+1)+1;
		$result = $mysqli->query("SELECT `����` FROM `Accounts` WHERE `����` REGEXP '$templ'") or die('������ MySQL: ' . $mysqli->error);
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
