<?php
if($headers['X-Type']=='XMLHttpRequest' && count($_GET)>0 || isset($_GET['LoadBank']) && isset($_FILES['userfile'])) {
	if(isset($_GET['Accounts'])) {
		if(count($notShownAccounts)==0) { $where1='1'; $where2='0'; }
		else {
			$where1="`Счет` NOT LIKE '".implode("%' AND `Счет` NOT LIKE '",$notShownAccounts)."%'";
			$where2="`Счет` LIKE '".implode("%' OR `Счет` LIKE '",$notShownAccounts)."%'";
		}
		echo show_all("SELECT `Счет`,`Наименование`,`Дебет`,`Кредит`,`АП` FROM `Accounts` WHERE $where1 UNION SELECT `Счет`,`Наименование`,SUM(`Дебет`),SUM(`Кредит`),`АП` FROM `Accounts` WHERE $where2 GROUP BY LEFT(`Счет`,2) ORDER BY `Счет`");
	}
	elseif(isset($_GET['Entries'])) {
		if($_GET['Date']) $query="SELECT * FROM `Entries` WHERE `Дата`='".toSQLDate($_GET['Date'])."' ORDER BY `Номер` DESC";
		else $query="SELECT * FROM `Entries` ORDER BY `Дата` DESC, `Номер` DESC LIMIT ".ELINES;
		echo show_all($query);
	}
	elseif(isset($_GET['Stencil'])) {
		if(isset($_GET['Debet'])) echo show_all("SELECT * FROM `Stencil` WHERE `Дебет`='{$_GET['Debet']}' ORDER BY `Дебет`,`Кредит`,`Содержание`");
		elseif(isset($_GET['Credit'])) echo show_all("SELECT * FROM `Stencil` WHERE `Кредит`='{$_GET['Credit']}' ORDER BY `Дебет`,`Кредит`,`Содержание`");
		else echo show_all("SELECT * FROM `Stencil` ORDER BY `Дебет`,`Кредит`,`Содержание`");
	}
	elseif(isset($_GET['ShowTables'])) {
		echo "Таблицы БД:<br>".show_all("SHOW TABLES",true)."<br>";
		echo "Accounts:<br>".show_all("SHOW COLUMNS FROM Accounts",true)."<br>";
		echo "Balances:<br>".show_all("SHOW COLUMNS FROM Balances",true)."<br>";
		echo "Entries:<br>".show_all("SHOW COLUMNS FROM Entries",true)."<br>";
		echo "Invoice:<br>".show_all("SHOW COLUMNS FROM Invoice",true)."<br>";
		if($extended) echo "LoadBank:<br>".show_all("SHOW COLUMNS FROM LoadBank",true)."<br>";
		echo "Stencil:<br>".show_all("SHOW COLUMNS FROM Stencil",true);
	}
	elseif(isset($_GET['MakeBalances'])) {
		makeBalances();
	}
	elseif(isset($_GET['Diagnostics'])) {
		Diagnostics();
//		echo show_all("SELECT * FROM `Balances` ORDER BY `Дата`, `Счет`");
	}
	elseif(isset($_GET['Start'])) {											// Инициирование содержимого виджетов
		$mysqli = new mysqli(SERVER, USERNAME, DBPASS,DBNAME) or die('Ошибка подключения (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
		$result=$mysqli->query("SELECT MAX(`Дата`) FROM `Balances` LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
	    $line = $result->fetch_array(MYSQLI_NUM);
		makeBalances($line[0]);
		liveVidgets(255);
		echo php2json($reply);
	}
	elseif(isset($_GET['Statement'])) {
		if(preg_match("/\d+\.?\d*\.?\d*/",$_GET['Acc'])) $Acc=$_GET['Acc']; else $Acc='';
		if(preg_match("/\d{2}\.\d{2}\.\d{4}/",$_GET['First'])) $First=$_GET['First']; else $First='';
		if(preg_match("/\d{2}\.\d{2}\.\d{4}/",$_GET['End'])) $End=$_GET['End']; else $End='';
		$checked=$_GET['Sub']=='Yes'?'checked':'';
		echo "<table class='npr' cellpadding=4 border=0>";
		echo "<tr><td style='font-weight:bold'>Счет</td><td><input type='text' name='СчетВ' format='varchar(10)' value='$Acc'></td></tr>";
		echo "<tr><td style='font-weight:bold'>Включить субсчета</td><td><input type='checkbox' name='ВклСбсч' format='checkbox' $checked></td></tr>";
		echo "<tr><td style='font-weight:bold'>Начальная дата</td><td><input type='text' name='ДатаОт' format='date' value='$First'></td></tr>";
		echo "<tr><td style='font-weight:bold'>Конечная дата</td><td><input type='text' name='ДатаДо' format='date' value='$End'></td></tr>";
		echo "</table>";
		echo "<button onclick='statement()'>Выписка</button>";
		if($Acc && $First && $End) {
			$quantum=in_array(substr($Acc,0,2),$quantAccs);
			$mysqli = new mysqli(SERVER, USERNAME, DBPASS,DBNAME) or die('Ошибка подключения (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
			$result = $mysqli->query("SELECT `Наименование`, `АП` FROM `Accounts` WHERE `Счет`='$Acc' LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
		    $line = $result->fetch_array(MYSQLI_NUM);
			$ap=$line[1];
			echo "<table cellpadding=4 border=0 style='text-align:left'>";
			echo "<caption>Выписка по счету $Acc \"{$line[0]}\"".($checked?", включая субсчета":"")." за период с $First по $End</caption>";
			$First=toSQLDate($First); $End=toSQLDate($End);
			$fd=substr($First,0,8).'01';
			if($checked) $query="SELECT SUM(`Остаток`),SUM(`Количество`) FROM `Balances` WHERE `Дата`='$fd' AND (`Счет`='$Acc' OR `Счет` LIKE '{$Acc}.%') LIMIT 1";
			else $query="SELECT `Остаток`,`Количество` FROM `Balances` WHERE `Дата`='$fd' AND `Счет`='$Acc' LIMIT 1";
			$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
		    $line = $result->fetch_array(MYSQLI_NUM);
			$balance=$line[0];
			$quant=$line[1];
			if($checked) $query="SELECT SUM(`Сумма`),SUM(`Количество`) FROM `Entries` WHERE `Дата`>='$fd' AND `Дата`<'$First' AND (`Дебет`='$Acc' OR `Дебет` LIKE '{$Acc}.%') LIMIT 1";
			else $query="SELECT SUM(`Сумма`),SUM(`Количество`) FROM `Entries` WHERE `Дата`>='$fd' AND `Дата`<'$First' AND `Дебет`='$Acc' LIMIT 1";
			$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
		    $line = $result->fetch_array(MYSQLI_NUM);
			$balance-=$line[0];
			$quant+=$line[1];
			if($checked) $query="SELECT SUM(`Сумма`),SUM(`Количество`) FROM `Entries` WHERE `Дата`>='$fd' AND `Дата`<'$First' AND (`Кредит`='$Acc' OR `Кредит` LIKE '{$Acc}.%') LIMIT 1";
			else $query="SELECT SUM(`Сумма`),SUM(`Количество`) FROM `Entries` WHERE `Дата`>='$fd' AND `Дата`<'$First' AND `Кредит`='$Acc' LIMIT 1";
			$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
		    $line = $result->fetch_array(MYSQLI_NUM);
			$balance+=$line[0];
			$quant-=$line[1];
			$debet=0;
			$credit=0;
			echo "<tr class='noHL'><td>Входящий</td>";
			if($balance<0 || $balance==0 && $ap=='А') echo "<td class='decimal'".($ap=='П'?" style='color:red'":"").">".number_format(-$balance,2,'.',DLMTR)."</td><td>&nbsp;";
			else echo "<td>&nbsp;</td><td class='decimal'".($ap=='А'?" style='color:red'":"").">".number_format($balance,2,'.',DLMTR);
			if($quantum) echo "</td><td>Кол:</td><td>$quant</td></tr>";
			else echo "</td><td colspan=2>&nbsp;</td></tr>";
			echo "<tr class='bord'><th>Дата</th><th>Дебет</th><th>Кредит</th><th>Счет</th>".($quantum?"<th>Кол</th>":"").($checked?"<th>СчетВ</th>":"")."<th>Содержание</th></tr>";
			if($checked) $query="SELECT `Дата`,`Дебет`,`Кредит`,`Сумма`,`Содержание`,`Количество` FROM `Entries` WHERE `Дата`>='$First' AND `Дата`<='$End' AND (`Дебет`='$Acc' OR `Дебет` LIKE '{$Acc}.%' OR `Кредит`='$Acc' OR `Кредит` LIKE '{$Acc}.%') ORDER BY `Дата`";
			else $query="SELECT `Дата`,`Дебет`,`Кредит`,`Сумма`,`Содержание`,`Количество` FROM `Entries` WHERE `Дата`>='$First' AND `Дата`<='$End' AND (`Дебет`='$Acc' OR `Кредит`='$Acc') ORDER BY `Дата`";
			$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
		    while($line = $result->fetch_array(MYSQLI_NUM)) {
				echo "<tr class='bord'><td>".toOurDate($line[0])."</td><td class='decimal'>";
				if($line[1]===$Acc || ($checked && substr($line[1],0,strlen($Acc)+1)==$Acc.'.')) 
					{ echo number_format($line[3],2,'.',DLMTR)."</td><td>&nbsp;</td><td>{$line[2]}"; $debet+=$line[3]; $quant+=$line[5]; }
				else { echo "&nbsp;</td><td class='decimal'>".number_format($line[3],2,'.',DLMTR)."</td><td>{$line[1]}"; $credit+=$line[3]; $quant-=$line[5]; }
				echo "</td>".($quantum?"<td>{$line[5]}</td>":"");
				if($checked) { if($line[1]===$Acc || ($checked && substr($line[1],0,strlen($Acc)+1)==$Acc.'.')) echo "<td>{$line[1]}</td>"; 
				else echo "<td>{$line[2]}</td>";	}
				echo "<td>{$line[4]}</td></tr>";
			}
			echo "<tr class='noHL'><td>Обороты</td><td class='decimal'>".number_format($debet,2,'.',DLMTR)."</td><td class='decimal'>".number_format($credit,2,'.',DLMTR)."</td><td colspan=2>&nbsp;</td></tr>";
			echo "<tr class='noHL'><td>Исходящий</td>";
			$balance+=-$debet+$credit;
			if($balance<0 || $balance==0 && $ap=='А') echo "<td class='decimal'".($ap=='П'?" style='color:red'":"").">".number_format(-$balance,2,'.',DLMTR)."</td><td>&nbsp;";
			else echo "<td>&nbsp;</td><td class='decimal'".($ap=='А'?" style='color:red'":"").">".number_format($balance,2,'.',DLMTR);
			if($quantum) echo "</td><td>Кол:</td><td>$quant</td></tr>";
			else echo "</td><td colspan=2>&nbsp;</td></tr>";
			echo "</table>";
		}
	}
	elseif(isset($_GET['Goods'])) {
		$query="SELECT `Счет`,`Наименование`,`Сумма`,`Количество`,`Цена_Уч`,`Цена_Розн`,`Код`  FROM `Accounts` AS `Goods` WHERE `Счет` LIKE '41.%'";
		foreach($columnAliases['Goods'] as $key=>$value) $query=str_replace($key,$value,$query);
		echo show_all($query);
	}
	elseif(isset($_GET['Edit'])) {										// Редактировать строку
		if(isset($Aliases[$_GET['name']])) $name=$Aliases[$_GET['name']]; else $name=$_GET['name'];
		if(!isset($nonEditable[$name])) die("Таблицы $name не найдено");
		$mysqli = new mysqli(SERVER, USERNAME, DBPASS,DBNAME) or die('Ошибка подключения (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
		$finfo_=get_field_info($name);
		if($name=='Accounts') {
			$query = "SELECT `Номер` FROM `Entries` WHERE `Дебет`='{$_GET['value']}' OR `Кредит`='{$_GET['value']}'  LIMIT 1";
			$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
			if($result->num_rows>0) $no_delete='По этому счету есть операции, удалять нельзя';
		}
		$query = "SELECT * FROM `$name` WHERE `{$_GET['field']}`='{$_GET['value']}' LIMIT 1";
		$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
		if($result->num_rows==1) {
		    $line = $result->fetch_array(MYSQLI_BOTH);
		    $finfo = $result->fetch_fields();
			if($name=='Entries' && in_array(substr($line['Кредит'],0,2),$quantAccs)) {
				$res = $mysqli->query("SELECT `Количество` FROM `Accounts` WHERE `Счет`='{$line['Кредит']}' LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
				$qline=$res->fetch_array(MYSQLI_NUM);
				$quantity=$qline[0]+$line['Количество'];
			}
			else $quantity=-1;
			echo "<table class='npr' cellpadding=4 border=0>";
			for($i=0;$i<$result->field_count;$i++) {
				if(in_array($finfo[$i]->name,$nonEditable[$_GET['name']])) {
					if(!in_array($finfo[$i]->name,$nonAddable[$_GET['name']])) {
						$line[$i]=formatStr($line[$i],$finfo[$i]->type==246,"&nbsp;");
						echo "<tr><td style='font-weight:bold'>{$finfo[$i]->name}</td><td>{$line[$i]}</td></tr>";
					}
				}
				else {
					$line[$i]=formatStr($line[$i],$finfo[$i]->type==246);
					if($_GET['name']=='LoadBank' && ($finfo[$i]->name=='Дебет' || $finfo[$i]->name=='Кредит') && substr($line[$i],0,2)=='51') {
						echo "<tr><td style='font-weight:bold'>{$finfo[$i]->name}</td><td>{$line[$i]}</td></tr>";
						$no_delete="Нельзя удялить строку из банковской выписки";
					}
					else {
						echo "<tr><td style='font-weight:bold'>{$finfo[$i]->name}:</td><td><input type='text' name='{$finfo[$i]->name}' value='{$line[$i]}' format='{$finfo_[$finfo[$i]->name]}'";
						if($quantity>=0 && $finfo[$i]->name=='Кредит') echo " quant=$quantity><span class=\"remains\">Остаток $quantity единиц</span></td></tr>";
						else echo "></td></tr>";
					}
				}
			}
			echo "</table>";
			echo "<button onclick='saveFromEdit(\"{$_GET['name']}\",\"{$_GET['field']}\",\"{$_GET['value']}\")'>Сохранить</button>";
			echo "<button ".($no_delete?"disabled=disabled title='$no_delete'":"")." onclick='Confirm(\"Удалить строку. Вы уверены?\",\"deleteString\",\"{$_GET['name']}\",\"{$_GET['field']}\",\"{$_GET['value']}\")'>Удалить</button>";
		}
		else echo "ОШИБКА: Запись {$_GET['field']}={$_GET['value']} в таблице {$_GET['name']} не найдена";
	}
	elseif(isset($_GET['Save'])) {										// Сохранить строку после редактирования
		if(isset($Aliases[$_GET['name']])) $name=$Aliases[$_GET['name']]; else $name=$_GET['name'];
		if(!isset($nonEditable[$name])) die("Таблицы $name не найдено");
		$mysqli = new mysqli(SERVER, USERNAME, DBPASS,DBNAME) or die('Ошибка подключения (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
		$finfo=get_field_info($name);
		if($name=='Entries') {
			$result = $mysqli->query("LOCK TABLES `Entries` WRITE, `Accounts` WRITE, `Balances` WRITE") or die('Ошибка MySQL: ' . $mysqli->error);
			$result = $mysqli->query("SELECT * FROM `Entries` WHERE `{$_GET['field']}`='{$_GET['value']}' LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
		    $paramsOld = $result->fetch_array(MYSQLI_ASSOC);
		}
		else {
			$result = $mysqli->query("LOCK TABLES `$name` WRITE") or die('Ошибка MySQL: ' . $mysqli->error);
		}
		$query="UPDATE `$name` SET";
		$paramsNew=[];
		foreach($_POST as $key=>$value) {
			if(isset($finfo[$key=iconv('UTF-8', 'windows-1251', $key)])) {
				$value=iconv('UTF-8', 'windows-1251', $value);
				$value=trim(preg_replace("/<.+>/",'',$mysqli->real_escape_string($value)));
				if($finfo[$key]=='date') $value=toSQLDate($value);
				elseif(substr($finfo[$key],0,7)=='decimal') $value=preg_replace("/[\s`]/",'',$value);
				if(!preg_match($reserved,$value)) { $query.=" `$key`='$value',"; $paramsNew[$key]=$value; }
			}
		}
		foreach($finfo as $key=>$value) if($value=='timestamp')  $query.=" `$key`=NOW(),";
		$query=substr($query,0,strlen($query)-1);
		$query.=" WHERE `{$_GET['field']}`='{$_GET['value']}' LIMIT 1";
		$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
		if($name=='LoadBank') {
			$result = $mysqli->query("SELECT `Дата`,`Сумма`,`НазначениеПлатежа`,`Содержание`,`НомерДок` FROM `LoadBank` WHERE `{$_GET['field']}`='{$_GET['value']}' LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
		    $paramsTemp = $result->fetch_array(MYSQLI_ASSOC);
			$paramsTemp['Содержание']=apply_stencil($paramsTemp['Содержание'],$paramsTemp);
			$query="UPDATE `LoadBank` SET";
			foreach($paramsTemp as $key=>$value) $query.=" `$key`='$value',";
			$query=substr($query,0,strlen($query)-1)." WHERE `{$_GET['field']}`='{$_GET['value']}' LIMIT 1";
			$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
		}
		$columns=trim(preg_replace("/<.+>/",'',$mysqli->real_escape_string(iconv('UTF-8', 'windows-1251', $_POST['columns']))));
		if($_GET['name']!=$name && isset($columnAliases[$_GET['name']])) {
			foreach($columnAliases[$_GET['name']] as $key=>$value)
			$columns=str_replace($key,$value,$columns);
		}
		$query="SELECT $columns FROM `$name` WHERE `{$_GET['field']}`='{$_GET['value']}'";
		$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
	    $finfo = $result->fetch_fields();
	    $line = $result->fetch_array(MYSQLI_NUM);
        for($i=0; $i<count($line); $i++) {
			$line[$i]=formatStr($line[$i],$finfo[$i]->type==246,"&nbsp;");
		}
		$reply['reply']= implode('|',$line);
		if($name=='Entries') {
			// Сначала удаляем старую проводку
			$result = $mysqli->query("SELECT `Дебет`,`Кредит` FROM `Accounts` WHERE `Счет`='{$paramsOld['Дебет']}' LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
		    $line = $result->fetch_array(MYSQLI_ASSOC);
			$value=-$line['Дебет']+$line['Кредит']+$paramsOld['Сумма'];
			if($value<0) { $dbsumm=-$value; $crsumm=0; }
			else { $dbsumm=0; $crsumm=$value; }
			if(in_array(substr($paramsOld['Дебет'],0,2),$quantAccs) && ($dbquan=$paramsOld['Количество'])>0) {
				$query="UPDATE `Accounts` SET `Дебет`=$dbsumm, `Кредит`=$crsumm, `Количество`=`Количество`-$dbquan WHERE `Счет`='{$paramsOld['Дебет']}' LIMIT 1";
			}
			else $query="UPDATE `Accounts` SET `Дебет`=$dbsumm, `Кредит`=$crsumm WHERE `Счет`='{$paramsOld['Дебет']}' LIMIT 1";
			$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
			$result = $mysqli->query("SELECT `Дебет`,`Кредит` FROM `Accounts` WHERE `Счет`='{$paramsOld['Кредит']}' LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
		    $line = $result->fetch_array(MYSQLI_ASSOC);
			$value=-$line['Дебет']+$line['Кредит']-$paramsOld['Сумма'];
			if($value<0) { $dbsumm=-$value; $crsumm=0; }
			else { $dbsumm=0; $crsumm=$value; }
			if(in_array(substr($paramsOld['Кредит'],0,2),$quantAccs) && ($crquan=$paramsOld['Количество'])>0) {
				$query="UPDATE `Accounts` SET `Дебет`=$dbsumm, `Кредит`=$crsumm, `Количество`=`Количество`+$crquan WHERE `Счет`='{$paramsOld['Кредит']}' LIMIT 1";
			}
			else $query="UPDATE `Accounts` SET `Дебет`=$dbsumm, `Кредит`=$crsumm WHERE `Счет`='{$paramsOld['Кредит']}' LIMIT 1";
			$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
			$today=date("Y-m-d");
			$fd=substr($today,0,8).'01';
			if($paramsOld['Дата']<$fd) {
				$cd=$paramsOld['Дата'];
				while($cd<$fd) {
					list($year,$month,$day)=explode('-',$cd);
					if(++$month>12) { $year++; $month='01'; }
					$month=strlen($month)<2?'0'.$month:$month;
					$cd="$year-$month-01";
					if($dbquan>0) $query="UPDATE `Balances` SET `Остаток`=`Остаток`+{$paramsOld['Сумма']}, `Количество`=`Количество`-$dbquan WHERE `Дата`='$cd' AND `Счет`='{$paramsOld['Дебет']}' LIMIT 1";
					else $query="UPDATE `Balances` SET `Остаток`=`Остаток`+{$paramsOld['Сумма']} WHERE `Дата`='$cd' AND `Счет`='{$paramsOld['Дебет']}' LIMIT 1";
					$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
					if($mysqli->affected_rows==1) {
						$result = $mysqli->query("SELECT `Остаток` FROM `Balances` WHERE `Дата`='$cd' AND `Счет`='{$paramsOld['Дебет']}' LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
					    $line = $result->fetch_array(MYSQLI_NUM);
						if($line[0]==0) $result = $mysqli->query("DELETE FROM `Balances` WHERE `Дата`='$cd' AND `Счет`='{$paramsOld['Дебет']}' LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
					}
					else {
						if($dbquan>0) $query="INSERT INTO `Balances` SET `Дата`='$cd', `Счет`='{$paramsOld['Дебет']}', `Остаток`={$paramsOld['Сумма']}, `Количество`=-$dbquan";
						else $query="INSERT INTO `Balances` SET `Дата`='$cd', `Счет`='{$paramsOld['Дебет']}', `Остаток`={$paramsOld['Сумма']}";
						$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
					}
					if($crquan>0) $query="UPDATE `Balances` SET `Остаток`=`Остаток`-{$paramsOld['Сумма']}, `Количество`=`Количество`+$crquan WHERE `Дата`='$cd' AND `Счет`='{$paramsOld['Кредит']}' LIMIT 1";
					else $query="UPDATE `Balances` SET `Остаток`=`Остаток`-{$paramsOld['Сумма']} WHERE `Дата`='$cd' AND `Счет`='{$paramsOld['Кредит']}' LIMIT 1";
					$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
					if($mysqli->affected_rows==1) {
						$result = $mysqli->query("SELECT `Остаток` FROM `Balances` WHERE `Дата`='$cd' AND `Счет`='{$paramsOld['Кредит']}' LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
					    $line = $result->fetch_array(MYSQLI_NUM);
						if($line[0]==0) $result = $mysqli->query("DELETE FROM `Balances` WHERE `Дата`='$cd' AND `Счет`='{$paramsOld['Кредит']}' LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
					}
					else {
						if($crquan>0) $query="INSERT INTO `Balances` SET `Дата`='$cd', `Счет`='{$paramsOld['Кредит']}', `Остаток`=-{$paramsOld['Сумма']}, `Количество`=$crquan";
						else $query="INSERT INTO `Balances` SET `Дата`='$cd', `Счет`='{$paramsOld['Кредит']}', `Остаток`=-{$paramsOld['Сумма']}";
						$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
					}
				}
			}
			// Потом делаем измененную проводку
			unset($dbquan); unset($crquan);
			$result = $mysqli->query("SELECT `Дебет`,`Кредит` FROM `Accounts` WHERE `Счет`='{$paramsNew['Дебет']}' LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
		    $line = $result->fetch_array(MYSQLI_ASSOC);
			$value=-$line['Дебет']+$line['Кредит']-$paramsNew['Сумма'];
			if($value<0) { $dbsumm=-$value; $crsumm=0; }
			else { $dbsumm=0; $crsumm=$value; }
			if(in_array(substr($paramsNew['Дебет'],0,2),$quantAccs) && ($dbquan=$paramsNew['Количество'])>0) {
				$query="UPDATE `Accounts` SET `Дебет`=$dbsumm, `Кредит`=$crsumm, `Количество`=`Количество`+$dbquan WHERE `Счет`='{$paramsNew['Дебет']}' LIMIT 1";
			}
			else $query="UPDATE `Accounts` SET `Дебет`=$dbsumm, `Кредит`=$crsumm WHERE `Счет`='{$paramsNew['Дебет']}' LIMIT 1";
			$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
			$result = $mysqli->query("SELECT `Дебет`,`Кредит` FROM `Accounts` WHERE `Счет`='{$paramsNew['Кредит']}' LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
		    $line = $result->fetch_array(MYSQLI_ASSOC);
			$value=-$line['Дебет']+$line['Кредит']+$paramsNew['Сумма'];
			if($value<0) { $dbsumm=-$value; $crsumm=0; }
			else { $dbsumm=0; $crsumm=$value; }
			if(in_array(substr($paramsNew['Кредит'],0,2),$quantAccs) && ($crquan=$paramsNew['Количество'])>0) {
				$query="UPDATE `Accounts` SET `Дебет`=$dbsumm, `Кредит`=$crsumm, `Количество`=`Количество`-$crquan WHERE `Счет`='{$paramsNew['Кредит']}' LIMIT 1";
			}
			else $query="UPDATE `Accounts` SET `Дебет`=$dbsumm, `Кредит`=$crsumm WHERE `Счет`='{$paramsNew['Кредит']}' LIMIT 1";
			$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
			if($paramsNew['Дата']<$fd) {
				$cd=$paramsNew['Дата'];
				while($cd<$fd) {
					list($year,$month,$day)=explode('-',$cd);
					if(++$month>12) { $year++; $month='01'; }
					$month=strlen($month)<2?'0'.$month:$month;
					$cd="$year-$month-01";
					if($dbquan>0) $query="UPDATE `Balances` SET `Остаток`=`Остаток`-{$paramsNew['Сумма']}, `Количество`=`Количество`+$dbquan WHERE `Дата`='$cd' AND `Счет`='{$paramsNew['Дебет']}' LIMIT 1";
					else $query="UPDATE `Balances` SET `Остаток`=`Остаток`-{$paramsNew['Сумма']} WHERE `Дата`='$cd' AND `Счет`='{$paramsNew['Дебет']}' LIMIT 1";
					$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
					if($mysqli->affected_rows==1) {
						$result = $mysqli->query("SELECT `Остаток` FROM `Balances` WHERE `Дата`='$cd' AND `Счет`='{$paramsNew['Дебет']}' LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
					    $line = $result->fetch_array(MYSQLI_NUM);
						if($line[0]==0) $result = $mysqli->query("DELETE FROM `Balances` WHERE `Дата`='$cd' AND `Счет`='{$paramsNew['Дебет']}' LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
					}
					else {
						if($dbquan>0) $query="INSERT INTO `Balances` SET `Дата`='$cd', `Счет`='{$paramsNew['Дебет']}', `Остаток`=-{$paramsNew['Сумма']}, `Количество`=$dbquan";
						else $query="INSERT INTO `Balances` SET `Дата`='$cd', `Счет`='{$paramsNew['Дебет']}', `Остаток`=-{$paramsNew['Сумма']}";
						$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
					}
					if($crquan>0) $query="UPDATE `Balances` SET `Остаток`=`Остаток`+{$paramsNew['Сумма']}, `Количество`=`Количество`-$crquan WHERE `Дата`='$cd' AND `Счет`='{$paramsNew['Кредит']}' LIMIT 1";
					else $query="UPDATE `Balances` SET `Остаток`=`Остаток`+{$paramsNew['Сумма']} WHERE `Дата`='$cd' AND `Счет`='{$paramsNew['Кредит']}' LIMIT 1";
					$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
					if($mysqli->affected_rows==1) {
						$result = $mysqli->query("SELECT `Остаток` FROM `Balances` WHERE `Дата`='$cd' AND `Счет`='{$paramsNew['Кредит']}' LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
					    $line = $result->fetch_array(MYSQLI_NUM);
						if($line[0]==0) $result = $mysqli->query("DELETE FROM `Balances` WHERE `Дата`='$cd' AND `Счет`='{$paramsNew['Кредит']}' LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
					}
					else {
						if($crquan>0) $query="INSERT INTO `Balances` SET `Дата`='$cd', `Счет`='{$paramsNew['Кредит']}', `Остаток`={$paramsNew['Сумма']}, `Количество`=-$crquan";
						else $query="INSERT INTO `Balances` SET `Дата`='$cd', `Счет`='{$paramsNew['Кредит']}', `Остаток`={$paramsNew['Сумма']}";
						$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
					}
				}
			}
			if($_POST['columnsA']) {
				// анализируем, что изменилось в проводке
				$accs=[];
				$str='';
				if($paramsOld['Сумма']==$paramsNew['Сумма']) {
					if($paramsOld['Дебет']!=$paramsNew['Дебет']) { $accs[]=$paramsOld['Дебет']; $accs[]=$paramsNew['Дебет']; }
					if($paramsOld['Кредит']!=$paramsNew['Кредит']) { $accs[]=$paramsOld['Кредит']; $accs[]=$paramsNew['Кредит']; }
				}
				else {
					if(!in_array($paramsOld['Дебет'],$accs)) $accs[]=$paramsOld['Дебет'];
					if(!in_array($paramsOld['Кредит'],$accs)) $accs[]=$paramsOld['Кредит'];
					if(!in_array($paramsNew['Дебет'],$accs)) $accs[]=$paramsNew['Дебет'];
					if(!in_array($paramsNew['Кредит'],$accs)) $accs[]=$paramsNew['Кредит'];
				}
				$columns=trim(preg_replace("/<.+>/",'',$mysqli->real_escape_string(iconv('UTF-8', 'windows-1251', $_POST['columnsA']))));
				for($j=0;$j<count($accs);$j++) {
					$query="SELECT $columns FROM `Accounts` WHERE `Счет`='{$accs[$j]}' LIMIT 1";
					$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
				    $finfo = $result->fetch_fields();
				    $line = $result->fetch_array(MYSQLI_NUM);
			        for($i=0; $i<count($line); $i++) {
						$line[$i]=formatStr($line[$i],$finfo[$i]->type==246);
					}
					$str.= ($str?"^":"").implode('|',$line);
				}
				if($str!='') $reply['Accounts']=$str;
			}
			if($_POST['columnsG']) {
				$accs=[];
				if(substr($paramsOld['Дебет'],0,2)=='41') $accs[]=$paramsOld['Дебет'];
				if(substr($paramsOld['Кредит'],0,2)=='41' && !in_array($paramsOld['Кредит'],$accs)) $accs[]=$paramsOld['Кредит'];
				if(substr($paramsNew['Дебет'],0,2)=='41' && !in_array($paramsNew['Дебет'],$accs)) $accs[]=$paramsNew['Дебет'];
				if(substr($paramsNew['Кредит'],0,2)=='41' && !in_array($paramsNew['Кредит'],$accs)) $accs[]=$paramsNew['Кредит'];
				$columns=trim(preg_replace("/<.+>/",'',$mysqli->real_escape_string(iconv('UTF-8', 'windows-1251', $_POST['columnsG']))));
				foreach($columnAliases['Goods'] as $key=>$value)	$columns=str_replace($key,$value,$columns);
				for($j=0;$j<count($accs);$j++) {
					$query="SELECT $columns FROM `Accounts` WHERE `Счет`='{$accs[$j]}' LIMIT 1";
					$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
				    $finfo = $result->fetch_fields();
				    $line = $result->fetch_array(MYSQLI_NUM);
			        for($i=0; $i<count($line); $i++) {
						$line[$i]=formatStr($line[$i],$finfo[$i]->type==246);
					}
					$str.= ($str?"^":"").implode('|',$line);
				}
				if($str!='') $reply['Goods']=$str;
			}
		}
		if($name=='Entries') $flag=ACCOUNTS | ENTRIES | GOODS | CTRAGENTS; 
		elseif($name=='Accounts') $flag=ACCOUNTS | GOODS | CTRAGENTS;
		elseif($name=='Invoice') $flag=INVOICE;
		elseif($name=='Stencil') $flag=STENCIL;
		else $flag=0;
		if($flag) liveVidgets($flag);
		echo php2json($reply);
		$result = $mysqli->query("UNLOCK TABLES") or die('Ошибка MySQL: ' . $mysqli->error);
	}
	elseif(isset($_GET['Delete'])) {									// Удалить строку
		if(isset($Aliases[$_GET['name']])) $name=$Aliases[$_GET['name']]; else $name=$_GET['name'];
		if(!isset($nonEditable[$name])) die("Таблицы $name не найдено");
		$mysqli = new mysqli(SERVER, USERNAME, DBPASS,DBNAME) or die('Ошибка подключения (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
		if($name=='Entries') {
			$query="SELECT * FROM `Entries` WHERE `{$_GET['field']}`='{$_GET['value']}' LIMIT 1";
			$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
		    $paramsOld = $result->fetch_array(MYSQLI_ASSOC);
			$result = $mysqli->query("SELECT `Дебет`,`Кредит` FROM `Accounts` WHERE `Счет`='{$paramsOld['Дебет']}' LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
		    $line = $result->fetch_array(MYSQLI_ASSOC);
			$value=-$line['Дебет']+$line['Кредит']+$paramsOld['Сумма'];
			if($value<0) { $dbsumm=-$value; $crsumm=0; }
			else { $dbsumm=0; $crsumm=$value; }
			if(in_array(substr($paramsOld['Дебет'],0,2),$quantAccs) && ($dbquan=$paramsOld['Количество'])>0) {
				$query="UPDATE `Accounts` SET `Дебет`=$dbsumm, `Кредит`=$crsumm, `Количество`=`Количество`-$dbquan WHERE `Счет`='{$paramsOld['Дебет']}' LIMIT 1";
			}
			else $query="UPDATE `Accounts` SET `Дебет`=$dbsumm, `Кредит`=$crsumm WHERE `Счет`='{$paramsOld['Дебет']}' LIMIT 1";
			$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
			$result = $mysqli->query("SELECT `Дебет`,`Кредит` FROM `Accounts` WHERE `Счет`='{$paramsOld['Кредит']}' LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
		    $line = $result->fetch_array(MYSQLI_ASSOC);
			$value=-$line['Дебет']+$line['Кредит']-$paramsOld['Сумма'];
			if($value<0) { $dbsumm=-$value; $crsumm=0; }
			else { $dbsumm=0; $crsumm=$value; }
			if(in_array(substr($paramsOld['Кредит'],0,2),$quantAccs) && ($crquan=$paramsOld['Количество'])>0) {
				$query="UPDATE `Accounts` SET `Дебет`=$dbsumm, `Кредит`=$crsumm, `Количество`=`Количество`+$crquan WHERE `Счет`='{$paramsOld['Кредит']}' LIMIT 1";
			}
			else $query="UPDATE `Accounts` SET `Дебет`=$dbsumm, `Кредит`=$crsumm WHERE `Счет`='{$paramsOld['Кредит']}' LIMIT 1";
			$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
			if(isset($_POST['columnsA'])) {
				$columns=trim(preg_replace("/<.+>/",'',$mysqli->real_escape_string(iconv('UTF-8', 'windows-1251', $_POST['columnsA']))));
				$query="SELECT $columns FROM `Accounts` WHERE `Счет`='{$paramsOld['Дебет']}' LIMIT 1";
				$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
			    $finfo = $result->fetch_fields();
			    $line = $result->fetch_array(MYSQLI_NUM);
		        for($i=0; $i<count($line); $i++) {
					$line[$i]=formatStr($line[$i],$finfo[$i]->type==246,"&nbsp;");
				}
				$str=implode('|',$line);
				$query="SELECT $columns FROM `Accounts` WHERE `Счет`='{$paramsOld['Кредит']}' LIMIT 1";
				$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
			    $line = $result->fetch_array(MYSQLI_NUM);
		        for($i=0; $i<count($line); $i++) {
					$line[$i]=formatStr($line[$i],$finfo[$i]->type==246,"&nbsp;");
				}
				$reply['Accounts']=$str."^".implode('|',$line);
			}
			if(isset($_POST['columnsG'])) {
				$columns=trim(preg_replace("/<.+>/",'',$mysqli->real_escape_string(iconv('UTF-8', 'windows-1251', $_POST['columnsG']))));
				foreach($columnAliases['Goods'] as $key=>$value)	$columns=str_replace($key,$value,$columns);
				$str='';
				if(substr($paramsOld['Дебет'],0,2)=='41') {
					$query="SELECT $columns FROM `Accounts` WHERE `Счет`='{$paramsOld['Дебет']}' LIMIT 1";
					$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
				    $finfo = $result->fetch_fields();
				    $line = $result->fetch_array(MYSQLI_NUM);
			        for($i=0; $i<count($line); $i++) {
						$line[$i]=formatStr($line[$i],$finfo[$i]->type==246,"&nbsp;");
					}
					$str=implode('|',$line);
				}
				if(substr($paramsOld['Кредит'],0,2)=='41') {
					$query="SELECT $columns FROM `Accounts` WHERE `Счет`='{$paramsOld['Кредит']}' LIMIT 1";
					$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
				    $finfo = $result->fetch_fields();
				    $line = $result->fetch_array(MYSQLI_NUM);
			        for($i=0; $i<count($line); $i++) {
						$line[$i]=formatStr($line[$i],$finfo[$i]->type==246,"&nbsp;");
					}
					$str=($str?$str.'^':'').implode('|',$line);
				}
				$reply['Goods']=$str;
			}
			$today=date("Y-m-d");
			$fd=substr($today,0,8).'01';
			if($paramsOld['Дата']<$fd) {
				$cd=$paramsOld['Дата'];
				while($cd<$fd) {
					list($year,$month,$day)=explode('-',$cd);
					if(++$month>12) { $year++; $month='01'; }
					$month=strlen($month)<2?'0'.$month:$month;
					$cd="$year-$month-01";
					if($dbquan>0) $query="UPDATE `Balances` SET `Остаток`=`Остаток`+{$paramsOld['Сумма']}, `Количество`=`Количество`-$dbquan WHERE `Дата`='$cd' AND `Счет`='{$paramsOld['Дебет']}' LIMIT 1";
					else $query="UPDATE `Balances` SET `Остаток`=`Остаток`+{$paramsOld['Сумма']} WHERE `Дата`='$cd' AND `Счет`='{$paramsOld['Дебет']}' LIMIT 1";
					$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
					if($mysqli->affected_rows==1) {
						$result = $mysqli->query("SELECT `Остаток` FROM `Balances` WHERE `Дата`='$cd' AND `Счет`='{$paramsOld['Дебет']}' LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
					    $line = $result->fetch_array(MYSQLI_NUM);
						if($line[0]==0) $result = $mysqli->query("DELETE FROM `Balances` WHERE `Дата`='$cd' AND `Счет`='{$paramsOld['Дебет']}' LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
					}
					else {
						if(isset($dbquan)) $query="INSERT INTO `Balances` SET `Дата`='$cd', `Счет`='{$paramsOld['Дебет']}', `Остаток`={$paramsOld['Сумма']}, `Количество`=-$dbquan";
						else $query="INSERT INTO `Balances` SET `Дата`='$cd', `Счет`='{$paramsOld['Дебет']}', `Остаток`={$paramsOld['Сумма']}";
						$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
					}
					if($crquan>0) $query="UPDATE `Balances` SET `Остаток`=`Остаток`-{$paramsOld['Сумма']}, `Количество`=`Количество`+$crquan WHERE `Дата`='$cd' AND `Счет`='{$paramsOld['Кредит']}' LIMIT 1";
					else $query="UPDATE `Balances` SET `Остаток`=`Остаток`-{$paramsOld['Сумма']} WHERE `Дата`='$cd' AND `Счет`='{$paramsOld['Кредит']}' LIMIT 1";
					$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
					if($mysqli->affected_rows==1) {
						$result = $mysqli->query("SELECT `Остаток` FROM `Balances` WHERE `Дата`='$cd' AND `Счет`='{$paramsOld['Кредит']}' LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
					    $line = $result->fetch_array(MYSQLI_NUM);
						if($line[0]==0) $result = $mysqli->query("DELETE FROM `Balances` WHERE `Дата`='$cd' AND `Счет`='{$paramsOld['Кредит']}' LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
					}
					else {
						if(isset($crquan)) $query="INSERT INTO `Balances` SET `Дата`='$cd', `Счет`='{$paramsOld['Кредит']}', `Остаток`=-{$paramsOld['Сумма']}, `Количество`=$crquan";
						else $query="INSERT INTO `Balances` SET `Дата`='$cd', `Счет`='{$paramsOld['Кредит']}', `Остаток`=-{$paramsOld['Сумма']}";
						$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
					}
				}
			}
		}
		$query = "DELETE FROM `$name` WHERE `{$_GET['field']}`='{$_GET['value']}' LIMIT 1";
		$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
		$reply['reply']='Deleted';
		if($name=='Entries') $flag=ACCOUNTS | ENTRIES | GOODS | CTRAGENTS; 
		elseif($name=='Accounts') $flag=ACCOUNTS | GOODS | CTRAGENTS;
		elseif($name=='Invoice') $flag=INVOICE;
		elseif($name=='Stencil') $flag=STENCIL;
		else $flag=0;
		if($flag) liveVidgets($flag);
		echo php2json($reply);
	}
	elseif(isset($_GET['Add']) || isset($_GET['newEntry'])) {			// Новая строка или новая проводка
		$name=isset($_GET['Add'])?$_GET['name']:'Entries';
		if(isset($Aliases[$name])) $name=$Aliases[$name];
		if(!isset($nonEditable[$name])) die("Таблицы $name не найдено");
		if(count($_POST)==0) {
			$finfo=get_field_info($name);
			echo "<table class='npr' cellpadding=4 border=0 style='margin-top:1em'>";
			foreach($finfo as $key=>$value) {
				if(!in_array($key,$nonAddable[isset($_GET['name'])?$_GET['name']:$name])) {
					$value=preg_replace("/'/","\"",$value);
					echo "<tr><td style='font-weight:bold'>$key:</td><td><input type='text' name='$key' format='$value'".($key=='Дата'?" value='".date("d.m.Y")."'":"")."></td></tr>";
				}
			}
			echo "</table>";
			echo "<button onclick='saveFromAdd(\"".($_GET['name']?$_GET['name']:$name)."\")' style='margin: 20px 37% 0'>Сохранить</button>";
		}
		else {
			$mysqli = new mysqli(SERVER, USERNAME, DBPASS,DBNAME) or die('Ошибка подключения (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
			$finfo=get_field_info($name);
			$params=[];
			$query="INSERT INTO `$name` SET";
			foreach($_POST as $key=>$value) {
				if(isset($finfo[$key=iconv('UTF-8', 'windows-1251', $key)])) {
					$value=iconv('UTF-8', 'windows-1251', $value);
					$value=trim(preg_replace("/<.+>/",'',$mysqli->real_escape_string($value)));
					if($finfo[$key]=='date') $value=toSQLDate($value);
					elseif(substr($finfo[$key],0,7)=='decimal') $value=preg_replace("/[\s`]/",'',$value);
					if(!preg_match($reserved,$value) && $value!='') { $query.=" `$key`='$value',"; $params[$key]=$value; }
				}
			}
			if($query[strlen($query)-1]==',') $query=substr($query,0,strlen($query)-1);
			else exit;
			if($name=='Entries') makeEntry($params);
			else {
				$result = $mysqli->query("LOCK TABLES `$name` WRITE") or die('Ошибка MySQL: ' . $mysqli->error);
				$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
				$result = $mysqli->query("UNLOCK TABLES") or die('Ошибка MySQL: ' . $mysqli->error);
			}
			if($_POST['columns']) {
				if($_POST['columns']=='unknown') {
					if($name=='Entries') $reply['reply']= show_all("SELECT * FROM `Entries` ORDER BY `Дата` DESC, `Дата_Время` DESC");
					elseif($name=='Invoice') $reply['reply']= show_all("SELECT * FROM `Invoice` ORDER BY `Дата`, `Номер`");
					else $reply['reply']= 'Reload';
				}
				else {
					$columns=trim(preg_replace("/<.+>/",'',$mysqli->real_escape_string(iconv('UTF-8', 'windows-1251', $_POST['columns']))));
					if($_GET['name']!=$name && isset($columnAliases[$_GET['name']])) {
						foreach($columnAliases[$_GET['name']] as $key=>$value)
						$columns=str_replace($key,$value,$columns);
					}
					$query=str_replace(',',' AND ',$query);
					if($name=='Entries') $query="SELECT $columns FROM `Entries` ORDER BY `Номер` DESC LIMIT 1";
					else $query="SELECT $columns FROM `$name` WHERE ".substr($query,strpos($query,'SET')+4)." LIMIT 1";
					$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
				    $finfo = $result->fetch_fields();
				    $line = $result->fetch_array(MYSQLI_NUM);
			        for($i=0; $i<count($line); $i++) {
						$line[$i]=formatStr($line[$i],$finfo[$i]->type==246,"&nbsp;");
					}
					$reply['reply']=implode('|',$line);
				}
			}
			if(isset($_POST['columnsE'])) {
				if($_POST['columnsE']=='unknown' || $_POST['columnsE']=='') {
					$reply['Entries']=show_all("SELECT * FROM `Entries` ORDER BY `Дата` DESC, `Дата_Время` DESC");
				}
				else {
					$columns=trim(preg_replace("/<.+>/",'',$mysqli->real_escape_string(iconv('UTF-8', 'windows-1251', $_POST['columnsE']))));
					$query="SELECT $columns FROM `Entries` ORDER BY `Номер` DESC LIMIT 1";
					$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
				    $finfo = $result->fetch_fields();
				    $line = $result->fetch_array(MYSQLI_NUM);
			        for($i=0; $i<count($line); $i++) {
						$line[$i]=formatStr($line[$i],$finfo[$i]->type==246,"&nbsp;");
					}
					$reply['Entries']=implode('|',$line);
				}
			}
			if($_POST['columnsA']) {
				$columns=trim(preg_replace("/<.+>/",'',$mysqli->real_escape_string(iconv('UTF-8', 'windows-1251', $_POST['columnsA']))));
				$query="SELECT $columns FROM `Accounts` WHERE `Счет`='{$params['Дебет']}' LIMIT 1";
				$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
			    $finfo = $result->fetch_fields();
			    $line = $result->fetch_array(MYSQLI_NUM);
		        for($i=0; $i<count($line); $i++) {
					$line[$i]=formatStr($line[$i],$finfo[$i]->type==246,"&nbsp;");
				}
				$str=implode('|',$line);
				$query="SELECT $columns FROM `Accounts` WHERE `Счет`='{$params['Кредит']}' LIMIT 1";
				$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
			    $line = $result->fetch_array(MYSQLI_NUM);
		        for($i=0; $i<count($line); $i++) {
					$line[$i]=formatStr($line[$i],$finfo[$i]->type==246,"&nbsp;");
				}
				$reply['Accounts']=$str."^".implode('|',$line);
			}
			if(isset($_POST['columnsG'])) {
				$columns=trim(preg_replace("/<.+>/",'',$mysqli->real_escape_string(iconv('UTF-8', 'windows-1251', $_POST['columnsG']))));
				foreach($columnAliases['Goods'] as $key=>$value)	$columns=str_replace($key,$value,$columns);
				$str='';
				if(substr($params['Дебет'],0,2)=='41') {
					$query="SELECT $columns FROM `Accounts` WHERE `Счет`='{$params['Дебет']}' LIMIT 1";
					$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
				    $finfo = $result->fetch_fields();
				    $line = $result->fetch_array(MYSQLI_NUM);
			        for($i=0; $i<count($line); $i++) {
						$line[$i]=formatStr($line[$i],$finfo[$i]->type==246,"&nbsp;");
					}
					$str=implode('|',$line);
				}
				if(substr($params['Кредит'],0,2)=='41') {
					$query="SELECT $columns FROM `Accounts` WHERE `Счет`='{$params['Кредит']}' LIMIT 1";
					$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
				    $finfo = $result->fetch_fields();
				    $line = $result->fetch_array(MYSQLI_NUM);
			        for($i=0; $i<count($line); $i++) {
						$line[$i]=formatStr($line[$i],$finfo[$i]->type==246,"&nbsp;");
					}
					$str=($str?$str.'^':'').implode('|',$line);
				}
				$reply['Goods']=$str;
			}
			if($name=='Entries') $flag=ACCOUNTS | ENTRIES | GOODS | CTRAGENTS; 
			elseif($name=='Accounts') $flag=ACCOUNTS | GOODS | CTRAGENTS;
			elseif($name=='Invoice') $flag=INVOICE;
			elseif($name=='Stencil') $flag=STENCIL;
			else $flag=0;
			if($flag) liveVidgets($flag);
			echo php2json($reply);
		}
	}
	elseif(isset($_GET['Sale'])) {
		if(count($_POST)==0) {
			echo "<table class='npr' cellpadding=4 border=0>";
			echo "<tr><td><b>Дата</b></td><td><input type='text' name='Дата' format='date' value='".date("d.m.Y")."'></td></tr>";
			echo "<tr><td><b>Дебет</b></td><td><input type='text' name='Дебет' format='varchar(10)'></td></tr>";
			echo "<tr><td><b>Товар</b></td><td><input type='text' name='Товар' format='varchar(7)'></td></tr>";
			echo "<tr><td>или <b>Код</b></td><td><input type='text' name='Код' format='varchar(20)'></td></tr>";
			echo "<tr><td><b>Наименование</b></td><td><span name='Наименование'></span></td></tr>";
			echo "<tr><td><b>Остаток, ед.</b></td><td><span name='Количество'></span></td></tr>";
			echo "<tr><td><b>Цена учетная</b></td><td><span name='Цена_Уч'></span></td></tr>";
			echo "<tr><td><b>Цена розничная</b></td><td><span name='Цена_Розн'></span></td></tr>";
			echo "<tr><td><b>Количество</b></td><td><input type='text' name='Количество' format='int'></td></tr>";
			echo "</table>";
			echo "<button onclick='doSale()'>Продажа</button>";
		}
		else {
			$mysqli = new mysqli(SERVER, USERNAME, DBPASS,DBNAME) or die('Ошибка подключения (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
			$finfo=['Дата'=>'date', 'Дебет'=>'varchar', 'Товар'=>'varchar', 'Код'=>'varchar', 'Количество'=>'int'];
			$paramsSale=[];
			foreach($_POST as $key=>$value) {
				if(isset($finfo[$key=iconv('UTF-8', 'windows-1251', $key)])) {
					$value=iconv('UTF-8', 'windows-1251', $value);
					$value=trim(preg_replace("/<.+>/",'',$mysqli->real_escape_string($value)));
					if($finfo[$key]=='date') $value=toSQLDate($value);
					if(!preg_match($reserved,$value) && $value!='') $paramsSale[$key]=$value;
				}
			}
			$params1=[]; $params2=[];
			if($paramsSale['Код']) $result = $mysqli->query("SELECT `Счет`,`Наименование`,`Цена_Розн`,IF(`Количество`,ROUND((`Дебет`-`Кредит`)/`Количество`,2),'') AS `Цена_Уч` FROM `Accounts` WHERE `Код` LIKE '%{$paramsSale['Код']}%' LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
			if($result->num_rows==0) $result = $mysqli->query("SELECT `Счет`,`Наименование`,`Цена_Розн`,IF(`Количество`,ROUND((`Дебет`-`Кредит`)/`Количество`,2),'') AS `Цена_Уч` FROM `Accounts` WHERE `Счет`='{$paramsSale['Товар']}' LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
		    $line = $result->fetch_array(MYSQLI_ASSOC);
			if(!$line) { echo "ОШИБКА - Не найден товар"; exit; }
			$params1['Дата']=$paramsSale['Дата'];
			$params1['Дебет']=SALESACC;
			$params1['Кредит']=$line['Счет'];
			$params1['Сумма']=$line['Цена_Уч']*$paramsSale['Количество'];
			$params1['Содержание']="Продажа {$line['Наименование']}, {$line['Цена_Уч']}*{$paramsSale['Количество']}";
			$params1['Количество']=$paramsSale['Количество'];
			$params2['Дата']=$paramsSale['Дата'];
			$params2['Дебет']=$paramsSale['Дебет'];
			$params2['Кредит']=SALESACC;
			$params2['Сумма']=$line['Цена_Розн']*$paramsSale['Количество'];
			$params2['Содержание']="Продажа {$line['Наименование']}, {$line['Цена_Розн']}*{$paramsSale['Количество']}";
			$params2['Количество']='';
			makeEntry($params1);
			makeEntry($params2);
			if(isset($_POST['columnsE'])) {
				if($_POST['columnsE']=='unknown' || $_POST['columnsE']=='') {
					$reply['Entries']=show_all("SELECT * FROM `Entries` ORDER BY `Дата` DESC, `Дата_Время` DESC");
				}
				else {
					$columns=trim(preg_replace("/<.+>/",'',$mysqli->real_escape_string(iconv('UTF-8', 'windows-1251', $_POST['columnsE']))));
					$query="SELECT $columns FROM `Entries` ORDER BY `Номер` DESC LIMIT 2";
					$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
				    $finfo = $result->fetch_fields();
				    $line = $result->fetch_array(MYSQLI_NUM);
			        for($i=0; $i<count($line); $i++) {
						$line[$i]=formatStr($line[$i],$finfo[$i]->type==246,"&nbsp;");
					}
					$str=implode('|',$line);
				    $line = $result->fetch_array(MYSQLI_NUM);
			        for($i=0; $i<count($line); $i++) {
						$line[$i]=formatStr($line[$i],$finfo[$i]->type==246,"&nbsp;");
					}
					$reply['Entries']=$str.'^'.implode('|',$line);
				}
			}
			if($_POST['columnsA']) {
				$columns=trim(preg_replace("/<.+>/",'',$mysqli->real_escape_string(iconv('UTF-8', 'windows-1251', $_POST['columnsA']))));
				$query="SELECT $columns FROM `Accounts` WHERE `Счет`='".SALESACC."' LIMIT 1";
				$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
			    $finfo = $result->fetch_fields();
			    $line = $result->fetch_array(MYSQLI_NUM);
		        for($i=0; $i<count($line); $i++) {
					$line[$i]=formatStr($line[$i],$finfo[$i]->type==246,"&nbsp;");
				}
				$str=implode('|',$line);
				$query="SELECT $columns FROM `Accounts` WHERE `Счет`='{$paramsSale['Дебет']}' LIMIT 1";
				$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
			    $line = $result->fetch_array(MYSQLI_NUM);
		        for($i=0; $i<count($line); $i++) {
					$line[$i]=formatStr($line[$i],$finfo[$i]->type==246,"&nbsp;");
				}
				$reply['Accounts']=$str."^".implode('|',$line);
			}
			if(isset($_POST['columnsG'])) {
				$columns=trim(preg_replace("/<.+>/",'',$mysqli->real_escape_string(iconv('UTF-8', 'windows-1251', $_POST['columnsG']))));
				foreach($columnAliases['Goods'] as $key=>$value)	$columns=str_replace($key,$value,$columns);
				$str='';
				$query="SELECT $columns FROM `Accounts` WHERE `Счет`='{$params1['Кредит']}' LIMIT 1";
				$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
			    $finfo = $result->fetch_fields();
			    $line = $result->fetch_array(MYSQLI_NUM);
		        for($i=0; $i<count($line); $i++) {
					$line[$i]=formatStr($line[$i],$finfo[$i]->type==246,"&nbsp;");
				}
				$reply['Goods']=implode('|',$line);
			}
			liveVidgets(ACCOUNTS | ENTRIES | GOODS | CTRAGENTS);
			echo php2json($reply);
		}
	}
	elseif(isset($_GET['Invoice'])) {									// Приходная накладная	
		echo show_all("SELECT * FROM `Invoice` ORDER BY `Дата`, `Номер`");
	}
	elseif(isset($_GET['AddInvoice'])) {						// Новая строка в приходной накладной
		echo "<table class='npr' cellpadding=4 border=0 style='margin-top:1em'>";
		echo "<tr><td style='font-weight:bold'>Дата</td><td><input type='text' name='Дата' format='date' value='".date("d.m.Y")."'></td></tr>";
		echo "<tr><td style='font-weight:bold'>Шаблон нового<br>счета товара</td><td><input type='text' name='Шаблон' format='varchar'><div class='tip'>Звездочки заменятся цифрами в номере нового счета</div></td></tr>";
		echo "<tr><td style='font-weight:bold'>Кредит</td><td><input type='text' name='Кредит' format='varchar'></td></tr>";
		echo "<tr class='hr'><td style='font-weight:bold'>Наименование<br>или код</td><td><div id='anchor'></div><input type='text' name='Поиск' format='varchar(30)'></td></tr>";
		echo "<tr><td style='font-weight:bold'>Код</td><td><input type='text' name='Код' format='int'></td></tr>";
		echo "<tr><td style='font-weight:bold'>Товар</td><td><input type='text' name='Товар' format='varchar' disabled></td></tr>";
		echo "<tr><td style='font-weight:bold'>Цена</td><td><input type='text' name='Цена' format='decimal'></td></tr>";
		echo "<tr><td style='font-weight:bold'>Количество</td><td><input type='text' name='Количество' format='int'></td></tr>";
		echo "<tr><td style='font-weight:bold'>Сумма</td><td><input type='text' name='Сумма' format='decimal'></td></tr>";
		echo "<tr><td style='font-weight:bold'>Цена_Розн</td><td><input type='text' name='Цена_Розн' format='decimal'></td></tr>";
		echo "</table>";
		echo "<input type='hidden' name='Содержание' format='varchar'>";
		echo "<button onclick='saveFromAdd(\"Invoice\")' style='margin: 20px 37% 0'>Сохранить</button>";
	}
	elseif(isset($_GET['LoadInvoice'])) {
		$mysqli = new mysqli(SERVER, USERNAME, DBPASS,DBNAME) or die('Ошибка подключения (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
		$resultI = $mysqli->query("SELECT * FROM `Invoice` ORDER BY `Номер`") or die('Ошибка MySQL: ' . $mysqli->error);
		$n=0; // строк обработано
		$m=0; // товарных позиций добавлено
		$k=0; // количество единиц приходованного товара
		$s=0; // стоимость приходованного товара
		while($lineI = $resultI->fetch_array(MYSQLI_ASSOC)) {
			$result = $mysqli->query("SELECT `Счет`,`Код` FROM `Accounts` WHERE `Счет` = '{$lineI['Товар']}' LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
			if($result->num_rows==0) {
				$query="INSERT INTO `Accounts` SET `Счет`='{$lineI['Товар']}', `Наименование`='{$lineI['Наименование']}', `АП`='А', `Цена_Розн`='{$lineI['Цена_Розн']}', `Код`='{$lineI['Код']}'";
				$result = $mysqli->query($query); $m++;
			}
			else {
				$query="UPDATE `Accounts` SET `Наименование`='{$lineI['Наименование']}', `Цена_Розн`='{$lineI['Цена_Розн']}'";
				$line=$result->fetch_array(MYSQLI_ASSOC);
				if($lineI['Код']!='' && strpos($line['Код'],$lineI['Код'])===false) $query.=", `Код`='".($line['Код']?$line['Код'].',':'').$lineI['Код']."'";
				$query.=" WHERE `Счет` = '{$lineI['Товар']}' LIMIT 1";
				$result = $mysqli->query($query);
			}
			$params['Дата']=$lineI['Дата'];
			$params['Дебет']=$lineI['Товар'];
			$params['Кредит']=$lineI['Кредит'];
			$params['Сумма']=$lineI['Сумма'];
			$params['Содержание']="Закуп {$lineI['Наименование']} ".sprintf("%01.2f",$lineI['Сумма']/$lineI['Количество'])."*{$lineI['Количество']}";
			$params['Количество']=$lineI['Количество'];
			makeEntry($params);
			$k+=$lineI['Количество'];
			$s+=$lineI['Сумма'];
			$n++;
		}
		$result = $mysqli->query("TRUNCATE TABLE `Invoice`");
		$reply['reply']="Обработано <b>$n</b> строк приходной накладной<br>Добавлено <b>$m</b> новых товарных позиций<br>Оприходовано <b>$k</b> единиц товара<br>на общую сумму <b>".number_format($s,2,'.',DLMTR)."</b>";
		liveVidgets(255);
		echo php2json($reply);
	}
	elseif(isset($_GET['About'])) {
		include "readme.html";
	}
	elseif(isset($_GET['UsersGuide'])) {
		include "usersguide.html";
	}
	elseif(isset($_GET['ShortCourse'])) {
		include "shortcourse.html";
	}
	elseif(isset($_GET['Reset'])) {									// Инициировать базу данных	
//echo "ОШИБКА - пока не надо уничтожать базу...";
//exit;	
		$mysqli = new mysqli(SERVER, USERNAME, DBPASS,DBNAME) or die('Ошибка подключения (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
		$tpl=file('templates/tables.tpl', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		for($i=0;$i<count($tpl);$i++) {
			if($tpl[$i]=='//EXTENDED' && !$extended) break;
			if(substr($tpl[$i],0,2)!='//') $result = $mysqli->query($tpl[$i]) or die('Ошибка MySQL: ' . $mysqli->error);
		}
		$reply['reply']='База данных инициализирована';
		liveVidgets(ACCOUNTS | ENTRIES | GOODS | INVOICE | CTRAGENTS);
		echo php2json($reply);
	}
	elseif(isset($_GET['KudirRules'])) {
		$param=read_param(false);
		if($_GET['KudirRules']=='titul') {
			echo "<table class='npr' cellpadding=4 border=0 style='margin-top:1em'>";
			echo "<tr><td style='font-weight:bold'>Ф.И.О.</td><td><input type='text' name='ФИО' format='varchar(40)' value='{$param['ФИО']}'></td></tr>";
			echo "<tr><td style='font-weight:bold'>ИНН</td><td><input type='text' name='ИНН' format='int(12)' value='{$param['ИНН']}'></td></tr>";
			echo "<tr><td style='font-weight:bold'>Объект налогообложения</td><td><label><input type='radio' name='Объект' value='Доходы'".($param['Объект']=='Доходы'?' checked':'').">Доходы</label><label><input type='radio' name='Объект' value='Расходы'".($param['Объект']!='Доходы'?' checked':'').">Доходы минус расходы</label></td></tr>";
			echo "<tr><td style='font-weight:bold'>Адрес</td><td><input type='text' name='Адрес' format='varchar(80)' value='{$param['Адрес']}'></td></tr>";
			echo "<tr><td style='font-weight:bold'>Номера счетов в банках</td><td><input type='text' name='Счета' format='varchar(80)' value='{$param['Счета']}'></td></tr>";
			echo "</table>";
			echo "<button onclick='saveFromKudir()' style='margin: 20px 37% 0'>Сохранить</button>";
		}
		elseif($_GET['KudirRules']=='rules') {
			echo "<table class='npr' cellpadding=4 border=0 style='margin-top:1em'>";
			echo "<tr><td style='font-weight:bold'>Правила принятия доходов</td><td><textarea name='Правила_доходов' cols=44 rows=3>{$param['Правила_доходов']}</textarea></td></tr>";
			echo "<tr><td style='font-weight:bold'>Исключения доходов</td><td><textarea name='Исключения_доходов' cols=44 rows=3>{$param['Исключения_доходов']}</textarea></td></tr>";
			echo "<tr><td style='font-weight:bold'>Правила принятия расходов</td><td><textarea name='Правила_расходов' cols=44 rows=3>{$param['Правила_расходов']}</textarea></td></tr>";
			echo "<tr><td style='font-weight:bold'>Исключения расходов</td><td><textarea name='Исключения_расходов' cols=44 rows=3>{$param['Исключения_расходов']}</textarea></td></tr>";
			echo "</table>";
			echo "<button onclick='saveFromKudir()' style='margin: 20px 37% 0'>Сохранить</button>";
		}
		elseif($_GET['KudirRules']=='tunes') {
			echo "<table class='npr' cellpadding=4 border=0 style='margin-top:1em'>";
			echo "<tr><td style='font-weight:bold'>Строк на странице</td><td><input type='text' name='Строк' format='int' value='".($param['Строк']==0?22:$param['Строк'])."'></td></tr>";
			echo "<tr><td style='font-weight:bold'>НДС отдельной строкой</td><td><input type='checkbox' name='НДС' format='checkbox' value='Да'".($param['НДС']=='Да'?' checked':'')."></td></tr>";
			echo "<tr><td style='font-weight:bold'>Уменьшать расходы <br>на увеличение товарных остатков и наоборот</td><td><input type='checkbox' name='Остатки' format='checkbox' value='Да'".($param['Остатки']=='Да'?' checked':'')."></td></tr>";
			echo "<tr><td style='font-weight:bold'>Уменьшать расходы <br>на увеличение дебиторки по счету 60 и наоборот</td><td><input type='checkbox' name='Дебиторка' format='checkbox' value='Да'".($param['Дебиторка']=='Да'?' checked':'')."></td></tr>";
			echo "<tr><td style='font-weight:bold'>Уменьшать расходы <br>на увеличение кредиторки по счету 60 и наоборот</td><td><input type='checkbox' name='Кредиторка' format='checkbox' value='Да'".($param['Кредиторка']=='Да'?' checked':'')."></td></tr>";
			echo "</table>";
			echo "<button onclick='saveFromKudir()' style='margin: 20px 37% 0'>Сохранить</button>";
		}
		elseif($_GET['KudirRules']=='save') {
			$finfo=['ФИО'=>'', 'ИНН'=>'', 'Объект'=>'', 'Адрес'=>'', 'Счета'=>'',
					'Правила_доходов'=>'', 'Правила_расходов'=>'', 'Исключения_доходов'=>'', 'Исключения_расходов'=>'',
					'Строк'=>'', 'НДС'=>'', 'Остатки'=>'', 'Дебиторка'=>'', 'Кредиторка'=>''];
			foreach($_POST as $key=>$value) {
				if(isset($finfo[$key=iconv('UTF-8', 'windows-1251', $key)])) {
					$value=iconv('UTF-8', 'windows-1251', $value);
					$value=trim(preg_replace("/<.+>/",'',$value));
					$value=preg_replace("/\h\h+/",' ',$value);
					if(!preg_match($reserved,$value)) $param[$key]=$value;
				}
			}
			$handle=fopen('kudir.dat','w');
			foreach($param as $key=>$value) fwrite($handle,$key.'='.$value."\r\n");
			fclose($handle);
			if((fileperms('kudir.dat') & 0777)!=0600) chmod('kudir.dat',0600);
		}
	}
	elseif(isset($_GET['Kudir'])) {
		if(!$param=read_param()) exit;
		if($param['Правила_доходов']) $revRules=format_rules($param['Правила_доходов']);
		else { echo "Не указаны правила принятия доходов"; exit; }
		if($param['Исключения_доходов']) $revExc=format_rules($param['Исключения_доходов']);
		else $revExc=[];
		if($param['Правила_расходов']) $expRules=format_rules($param['Правила_расходов']);
		elseif($param['Объект']=='Расходы') { echo "Не указаны правила принятия расходов"; exit; }
		else $expRules=[];
		if($param['Исключения_расходов']) $expExc=format_rules($param['Исключения_расходов']);
		else $expExc=[];
		if(preg_match("/^20\d\d$/",$_GET['Year'])) $Year=$_GET['Year']; else $Year='';
		if(preg_match("/^Quartal$|^Actual$/",$_GET['Mode'])) $Mode=$_GET['Mode']; else $Mode='';
		echo "<table class='npr' cellpadding=4 border=0>";
		echo "<tr><td style='font-weight:bold;width:60%'>Год для формирования КУДиР</td><td><input type='text' name='Year' format='year' value='$Year'></td></tr>";
		echo "<tr><td style='font-weight:bold'>Режим формирования</td><td><label><input type='radio' name='Mode' value='Quartal'".($Mode=='Quartal'?' checked':'')." style='margin-left:0'>Квартальный</label><br><label><input type='radio' name='Mode' value='Actual'".($Mode=='Quartal'?'':' checked')." style='margin-left:0'>Текущий</label></td></tr>";
		echo "</table>";
		echo "<button onclick='kudirForm()'>Сформировать</button>";
		if($Year && $Mode) {
			if($Mode=='Quartal') {	?>
				<div class='page'>
					<div class='kudir-upheader'>Приложение № 1<br>к приказу Министерства финансов<br>Российской Федерации<br>от 22.10.2012 № 135н</div>
					<div class='kudir-header' style='margin-top:5em'>КНИГА<br>учета доходов и расходов организаций и индивидуальных предпринимателей,<br>применяющих упрощенную систему налогообложения</div>
					<table class='kudir-titul' border=0>
						<tr><td style='width:85%'>&nbsp;</td><td colspan=3 class='b'>Коды</td></tr>
						<tr><td style='text-align:right'>Форма по ОКУД</td><td colspan=3 class='b'>&nbsp;</td></tr>
						<tr><td><span style='margin-left:5em'>На <u><b><? echo $Year ?></b></u> год</span><span style='float:right'>Дата (год, месяц, число)</span></td><td class='b'>&nbsp;</td><td class='b'>&nbsp;</td><td class='b' style='width:7%'>&nbsp;</td></tr>
						<tr><td>&nbsp;</td><td colspan=3 class='blr'>&nbsp;</td></tr>
						<tr><td>Налогоплательщик (наименование</td><td colspan=3 class='blr'>&nbsp;</td></tr>
						<tr><td>организации/фамилия, имя, отчество</td><td colspan=3 class='blr'>&nbsp;</td></tr>
						<tr><td>индивидуального предпринимателя) <u><b><? echo $param['ФИО'] ?></b></u></td><td colspan=3 class='blr'>&nbsp;</td></tr>
						<tr><td style='text-align:right'>по ОКПО</td><td colspan=3 class='blr'>&nbsp;</td></tr>
						<tr><td>&nbsp;</td><td colspan=3 class='blr'>&nbsp;</td></tr>
						<tr><td>Идентификационный номер налогоплательщика - организации/код причины постановки на</td><td colspan=3 class='blr'>&nbsp;</td></tr>
						<tr><td>учет в налоговом органе (ИНН/КПП)</td><td colspan=3 class='blr'>&nbsp;</td></tr>
						<tr><td>&nbsp;</td><td colspan=3 class='blr'>&nbsp;</td></tr>
						<tr><td><table style='width:77%'><tr><? for($i=0;$i<12;$i++) echo "<td class='b'>&nbsp;</td>" ?><td class='b'>/</td><? for($i=0;$i<9;$i++) echo "<td class='b'>&nbsp;</td>" ?></tr></table></td><td colspan=3 class='blr'>&nbsp;</td></tr>
						<tr><td>&nbsp;</td><td colspan=3 class='blr'>&nbsp;</td></tr>
						<tr><td>Идентификационный номер налогоплательщика - индивидуального предпринимателя (ИНН)</td><td colspan=3 class='blr'>&nbsp;</td></tr>
						<tr><td>&nbsp;</td><td colspan=3 class='blr'>&nbsp;</td></tr>
						<tr><td><table style='width:42%'><tr><? for($i=0;$i<12;$i++) echo "<td class='b'>{$param['ИНН'][$i]}</td>" ?></tr></table></td><td colspan=3 class='blr'>&nbsp;</td></tr>
						<tr><td>&nbsp;</td><td colspan=3 class='blr'>&nbsp;</td></tr>
						<tr><td>Объект налогообложения <u><b><? echo $param['Объект']=='Доходы'?'Доходы':'Доходы минус расходы' ?></b></u></td><td colspan=3 class='blr'>&nbsp;</td></tr>
						<tr><td style='text-align:center'>(наименование выбранного объекта налогообложения<br>в соответствии со статьей 346.14 Налогового кодекса Российской Федерации)</td><td colspan=3 class='blr'>&nbsp;</td></tr>
						<tr><td>&nbsp;</td><td colspan=3 class='blr'>&nbsp;</td></tr>
						<tr><td>Единица измерения: руб.<span style='float:right'>по ОКЕИ</span></td><td colspan=3 class='b'>383</td></tr>
					</table>
					<div class='kudir-text'>Адрес места нахождения организации<br>(места жительства индивидуального<br>предпринимателя) <u><b><? echo $param['Адрес'] ?></b></u></div>
					<div class='kudir-text'>Номера расчетных и иных счетов, открытых в учреждениях банков <u><b><? echo $param['Счета'] ?></b></u></div>
				</div>	<?PHP
				if(($m=date("m"))<='03') { echo "<b>Первый квартал еще не закончился</b>"; exit; }
				elseif($m<='06') $endDate=$Year."-03-31";
				elseif($m<='09') $endDate=$Year."-06-30";
				else $endDate=$Year."-09-30";
			}
			else $endDate=date("Y-m-d");
			if($Year<date("Y")) { $endDate=$Year."-12-31"; $Mode='Quartal'; }
			$strings=[];
			$strnum=0;
			$mysqli = new mysqli(SERVER, USERNAME, DBPASS,DBNAME) or die('Ошибка подключения (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
			$day=1; $num=1; $prevDate='';
			$osQuart=0; $osTotal=0;
			while(($curDate=date("Y-m-d",mktime(0,0,0,1,$day,(int)$Year)))<=$endDate) {
				if($prevDate && substr($prevDate,5,2)!=($mm=substr($curDate,5,2)) && (int)$mm%3==1) {
					$mm=(int)($mm/3);
					$startQuart=$Year.'-'.sprintf("%02d",$mm*3-2).'-01';
					$endQuart=date("Y-m-d",mktime(0,0,0,1,$day-1,(int)$Year));
					if($param['Объект']=='Расходы') {
						$osTotal+=$osQuart/(5-$mm);
						if($osTotal>0) {
							$strings[$strnum]=[];
							$strings[$strnum][0]=$num++;
							$strings[$strnum][1]=$prevDate;
							$strings[$strnum][2]="Списание расходов на приобретение основных средств в $mm квартале";
							$strings[$strnum][3]='';
							$strings[$strnum][4]=$osTotal;
							if(++$strnum==$param['Строк']) {
								make_page($strings); $strings=[]; $strnum=0;
							}
						}
						if($param['Остатки']=='Да') {
							$result = $mysqli->query("SELECT -SUM(`Остаток`) FROM `Balances` WHERE `Дата`='$startQuart' AND `Счет` LIKE '41%' AND `Остаток`<0 LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
							$line = $result->fetch_array(MYSQLI_NUM);
							$rests1=$line[0];
							$result = $mysqli->query("SELECT -SUM(`Остаток`) FROM `Balances` WHERE `Дата`='$curDate' AND `Счет` LIKE '41%' AND `Остаток`<0 LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
							$line = $result->fetch_array(MYSQLI_NUM);
							$rests2=$line[0];
							if(($rests1>0 || $rests2>0)) {
								$strings[$strnum]=[];
								$strings[$strnum][0]=$num++;
								$strings[$strnum][1]=toOurDate($endQuart);
								$strings[$strnum][2]="Товарные остатки на ".toOurDate($startQuart).": ".number_format($rests1,2,'.',DLMTR)."<br>Товарные остатки на ".toOurDate($curDate).": ".number_format($rests2,2,'.',DLMTR)."<br>".($rests1>$rests2?'Уменьшение':'Увеличение')." товарных остатков в $mm квартале: ".number_format(abs($rests1-$rests2),2,'.',DLMTR);
								$strings[$strnum][3]='';
								$strings[$strnum][4]=$rests1-$rests2;
								if(++$strnum==$param['Строк']) {
									make_page($strings); $strings=[]; $strnum=0;
								}
							}
						}
						if($param['Дебиторка']=='Да') {
							$result = $mysqli->query("SELECT -SUM(`Остаток`) FROM `Balances` WHERE `Дата`='$startQuart' AND `Счет` LIKE '60%' AND `Остаток`<0 LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
							$line = $result->fetch_array(MYSQLI_NUM);
							$debts1=$line[0];
							$result = $mysqli->query("SELECT -SUM(`Остаток`) FROM `Balances` WHERE `Дата`='$curDate' AND `Счет` LIKE '60%' AND `Остаток`<0 LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
							$line = $result->fetch_array(MYSQLI_NUM);
							$debts2=$line[0];
							if($debts1>0 || $debts2>0) {
								$strings[$strnum]=[];
								$strings[$strnum][0]=$num++;
								$strings[$strnum][1]=toOurDate($endQuart);
								$strings[$strnum][2]="Дебетовые остатки по сч.60 на ".toOurDate($startQuart).": ".number_format($debts1,2,'.',DLMTR)."<br>Дебетовые остатки по сч.60 на ".toOurDate($curDate).": ".number_format($debts2,2,'.',DLMTR)."<br>".($debts1>$debts2?'Уменьшение':'Увеличение')." дебетовых остатков в $mm квартале: ".number_format(abs($debts1-$debts2),2,'.',DLMTR);
								$strings[$strnum][3]='';
								$strings[$strnum][4]=$debts1-$debts2;
								if(++$strnum==$param['Строк']) {
									make_page($strings); $strings=[]; $strnum=0;
								}
							}
						}
						if($param['Кредиторка']=='Да') {
							$result = $mysqli->query("SELECT SUM(`Остаток`) FROM `Balances` WHERE `Дата`='$startQuart' AND `Счет` LIKE '60%' AND `Остаток`>0 LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
							$line = $result->fetch_array(MYSQLI_NUM);
							$credts1=$line[0];
							$result = $mysqli->query("SELECT SUM(`Остаток`) FROM `Balances` WHERE `Дата`='$curDate' AND `Счет` LIKE '60%' AND `Остаток`>0 LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
							$line = $result->fetch_array(MYSQLI_NUM);
							$credts2=$line[0];
							if($credts1>0 || $credts2>0) {
								$strings[$strnum]=[];
								$strings[$strnum][0]=$num++;
								$strings[$strnum][1]=toOurDate($endQuart);
								$strings[$strnum][2]="Кредитовые остатки по сч.60 на ".toOurDate($startQuart).": ".number_format($credts1,2,'.',DLMTR)."<br>Кредитовые остатки по сч.60 на ".toOurDate($curDate).": ".number_format($credts2,2,'.',DLMTR)."<br>".($credts1>$credts2?'Уменьшение':'Увеличение')." кредитовых остатков в $mm квартале: ".number_format(abs($credts1-$credts2),2,'.',DLMTR);
								$strings[$strnum][3]='';
								$strings[$strnum][4]=$credts1-$credts2;
								if(++$strnum==$param['Строк']) {
									make_page($strings); $strings=[]; $strnum=0;
								}
							}
						}
					}
					make_page($strings,$mm); $strings=[]; $strnum=0; $osQuart=0;
				}
				$prevDate=$curDate;
				$result = $mysqli->query("SELECT `Дата`, `Дебет`, `Кредит`, `Сумма`, `Содержание` FROM `Entries` WHERE `Дата`='$curDate'") or die('Ошибка MySQL: ' . $mysqli->error);
				while($line = $result->fetch_array(MYSQLI_NUM)) {
					$revPass=false; $expPass=false; $revSumRule=''; $expSumRule='';
					for($i=0;$i<count($revExc);$i++) if(preg_match($revExc[$i]['Д'],$line[1]) && preg_match($revExc[$i]['К'],$line[2])) break;
					if($i==count($revExc)) {
						for($i=0;!$revPass && $i<count($revRules);$i++) {
							$revPass=preg_match($revRules[$i]['Д'],$line[1]) && preg_match($revRules[$i]['К'],$line[2]);
							if($revPass && isset($revRules[$i]['С'])) $revSumRule=$revRules[$i]['С'];
						}
					}
					if($param['Объект']=='Расходы') {
						for($i=0;$i<count($expExc);$i++) if(preg_match($expExc[$i]['Д'],$line[1]) && preg_match($expExc[$i]['К'],$line[2])) break;
						if($i==count($expExc)) {
							for($i=0;!$expPass && $i<count($expRules);$i++) {
								$expPass=preg_match($expRules[$i]['Д'],$line[1]) && preg_match($expRules[$i]['К'],$line[2]);
								if($expPass && isset($expRules[$i]['С'])) $expSumRule=$expRules[$i]['С'];
							}
						}
					}
					if($revPass || $expPass) {
						$strings[$strnum]=[];
						$strings[$strnum][0]=$num++;
						if(preg_match("/от\s+(\d\d[\.,]\d\d[\.,]\d{2,4})/",$line[4],$match)) $docDate=str_replace(',','.',$match[1]); else $docDate=toOurDate($line[0]);
						if(substr($line[1],0,2)=='51') $docNum='вып.банка';
						elseif(substr($line[1],0,2)=='50') $docNum='z-отчет';
						elseif(preg_match("/[№N]\s+([^\s,;]+)|[нН]омер\s+([^\s,;]+)/",$line[4],$match)) $docNum=$match[1];
						else $docNum='б/н';
						$strings[$strnum][1]=$docDate.' '.$docNum;
						if($param['НДС']=='Да' && preg_match("/НДС\s+(\d{1,2}\%\s+)?(-\s+)?(\d+[\.,]?\d{0,2})/",$line[4],$match)) $nds=str_replace(',','.',$match[3]); else $nds=0;
						$strings[$strnum][2]=$nds?substr($line[4],0,strpos($line[4],'НДС')):$line[4];
						if($revSumRule=='') $strings[$strnum][3]=$revPass?$line[3]:'';
						else $strings[$strnum][3]=calc_rule($revSumRule,$line[4],$line[3]);
						if($nds) $strings[$strnum][4]=$line[3]-$nds;
						elseif($expSumRule=='') $strings[$strnum][4]=$expPass?$line[3]:'';
						else $strings[$strnum][4]=calc_rule($expSumRule,$line[4],$line[3]);
						if(++$strnum==$param['Строк']) {
							make_page($strings); $strings=[]; $strnum=0;
						}
						if($nds) {
							$strings[$strnum]=[];
							$strings[$strnum][0]=$num++;
							$strings[$strnum][1]=$docDate.' '.$docNum;
							$strings[$strnum][2]=$line[4];
							$strings[$strnum][3]='';
							if($nds) $strings[$strnum][4]=$nds;
							if(++$strnum==$param['Строк']) {
								make_page($strings); $strings=[]; $strnum=0;
							}
						}
					}
					if(substr($line[1],0,2)=='01' && substr($line[2],0,2)!='01') $osQuart+=$line[3];
				}
				$day++;
			}
			$result = $mysqli->query("SELECT SUM(`Сумма`) FROM `Entries` WHERE `Дебет` LIKE '68%' AND `Содержание` LIKE '%$Year%' LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
			$line = $result->fetch_array(MYSQLI_NUM);
			$taxPayed=$line[0];
			if($param['Объект']=='Доходы') {
				$result = $mysqli->query("SELECT SUM(`Сумма`) FROM `Entries` WHERE `Дата`>='".$Year.'-01-01'."' AND `Дата`<='$endDate' AND `Кредит` REGEXP '51|50' AND (`Дебет` LIKE '69%' OR `Содержание` REGEXP 'ОПС|ОМС|ОСС|ФСС|ДЛС|ПФР|пособи') LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
				$line = $result->fetch_array(MYSQLI_NUM);
				$pfrPayed=$line[0];
				$result = $mysqli->query("SELECT SUM(`Сумма`) FROM `Entries` WHERE `Дата`>='".$Year.'-01-01'."' AND `Дата`<='$endDate' AND `Кредит` REGEXP '51|50' AND `Содержание` REGEXP 'торгов.{2,3} +сбор' LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
				$line = $result->fetch_array(MYSQLI_NUM);
				$duePayed=$line[0];
			}
			if(substr($endDate,5)=='12-31') $mm=4; else $mm=0;
			if($mm>0) $osTotal+=$osQuart/(5-$mm);
			if($param['Объект']=='Расходы')	{
				if($mm>0 && $osTotal>0) {
					$strings[$strnum]=[];
					$strings[$strnum][0]=$num++;
					$strings[$strnum][1]=$endDate;
					$strings[$strnum][2]="Списание расходов на приобретение основных средств в 4 квартале";
					$strings[$strnum][3]='';
					$strings[$strnum][4]=$osTotal;
					if(++$strnum==$param['Строк']) {
						make_page($strings); $strings=[]; $strnum=0;
					}
				}
				$startDate=date("Y-m-d",mktime(0,0,0,(int)(((int)substr($curDate,5,2)-1)/3)*3+1,1,(int)$Year));
				$m=(int)(((int)substr($curDate,5,2)+2)/3);
				if($param['Остатки']=='Да') {
					$result = $mysqli->query("SELECT -SUM(`Остаток`) FROM `Balances` WHERE `Дата`='$startDate' AND `Счет` LIKE '41%' AND `Остаток`<0 LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
					$line = $result->fetch_array(MYSQLI_NUM);
					$rests1=$line[0];
					if($Mode=='Quartal') $query="SELECT -SUM(`Остаток`) FROM `Balances` WHERE `Дата`='$curDate' AND `Счет` LIKE '41%' AND `Остаток`<0 LIMIT 1";
					else $query="SELECT SUM(`Дебет`) FROM `Accounts` WHERE `Счет` LIKE '41%' AND `Дебет`>0 LIMIT 1";
					$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
					$line = $result->fetch_array(MYSQLI_NUM);
					$rests2=$line[0];
					if(($rests1>0 || $rests2>0)) {
						$strings[$strnum]=[];
						$strings[$strnum][0]=$num++;
						$strings[$strnum][1]=toOurDate($endDate);
						$strings[$strnum][2]="Товарные остатки на ".toOurDate($startDate).": ".number_format($rests1,2,'.',DLMTR)."<br>Товарные остатки на ".toOurDate($curDate).": ".number_format($rests2,2,'.',DLMTR)."<br>".($rests1>$rests2?'Уменьшение':'Увеличение')." товарных остатков в $m квартале: ".number_format(abs($rests1-$rests2),2,'.',DLMTR);
						$strings[$strnum][3]='';
						$strings[$strnum][4]=$rests1-$rests2;
						if(++$strnum==$param['Строк']) {
							make_page($strings); $strings=[]; $strnum=0;
						}
					}
				}
				if($param['Дебиторка']=='Да') {
					$result = $mysqli->query("SELECT -SUM(`Остаток`) FROM `Balances` WHERE `Дата`='$startDate' AND `Счет` LIKE '60%' AND `Остаток`<0 LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
					$line = $result->fetch_array(MYSQLI_NUM);
					$debts1=$line[0];
					if($Mode=='Quartal') $query="SELECT -SUM(`Остаток`) FROM `Balances` WHERE `Дата`='$curDate' AND `Счет` LIKE '60%' AND `Остаток`<0 LIMIT 1";
					else $query="SELECT SUM(`Дебет`) FROM `Accounts` WHERE `Счет` LIKE '60%' AND `Дебет`>0 LIMIT 1";
					$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
					$line = $result->fetch_array(MYSQLI_NUM);
					$debts2=$line[0];
					if($debts1>0 || $debts2>0) {
						$strings[$strnum]=[];
						$strings[$strnum][0]=$num++;
						$strings[$strnum][1]=toOurDate($endDate);
						$strings[$strnum][2]="Дебетовые остатки по сч.60 на ".toOurDate($startDate).": ".number_format($debts1,2,'.',DLMTR)."<br>Дебетовые остатки по сч.60 на ".toOurDate($curDate).": ".number_format($debts2,2,'.',DLMTR)."<br>".($debts1>$debts2?'Уменьшение':'Увеличение')." дебетовых остатков в $m квартале: ".number_format(abs($debts1-$debts2),2,'.',DLMTR);
						$strings[$strnum][3]='';
						$strings[$strnum][4]=$debts1-$debts2;
						if(++$strnum==$param['Строк']) {
							make_page($strings); $strings=[]; $strnum=0;
						}
					}
				}
				if($param['Кредиторка']=='Да') {
					$result = $mysqli->query("SELECT SUM(`Остаток`) FROM `Balances` WHERE `Дата`='$startDate' AND `Счет` LIKE '60%' AND `Остаток`>0 LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
					$line = $result->fetch_array(MYSQLI_NUM);
					$credts1=$line[0];
					if($Mode=='Quartal') $query="SELECT SUM(`Остаток`) FROM `Balances` WHERE `Дата`='$curDate' AND `Счет` LIKE '60%' AND `Остаток`>0 LIMIT 1";
					else $query="SELECT SUM(`Кредит`) FROM `Accounts` WHERE `Счет` LIKE '60%' AND `Кредит`>0 LIMIT 1";
					$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
					$line = $result->fetch_array(MYSQLI_NUM);
					$credts2=$line[0];
					if($credts1>0 || $credts2>0) {
						$strings[$strnum]=[];
						$strings[$strnum][0]=$num++;
						$strings[$strnum][1]=toOurDate($endDate);
						$strings[$strnum][2]="Кредитовые остатки по сч.60 на ".toOurDate($startDate).": ".number_format($credts1,2,'.',DLMTR)."<br>Кредитовые остатки по сч.60 на ".toOurDate($curDate).": ".number_format($credts2,2,'.',DLMTR)."<br>".($credts1>$credts2?'Уменьшение':'Увеличение')." кредитовых остатков в $m квартале: ".number_format(abs($credts1-$credts2),2,'.',DLMTR);
						$strings[$strnum][3]='';
						$strings[$strnum][4]=$credts1-$credts2;
						if(++$strnum==$param['Строк']) {
							make_page($strings); $strings=[]; $strnum=0;
						}
					}
				}
			}
			make_page($strings,$mm);
		?>
		<?PHP
		}
	}
	elseif(isset($_GET['Kudir2'])) {
		if(!$param=read_param()) exit;
		if(preg_match("/^20\d\d$/",$_GET['Kudir2'])) $Year=$_GET['Kudir2'];
		echo "<table class='npr' cellpadding=4 border=0 style='margin-bottom:2em'>";
		echo "<tr><td style='font-weight:bold;width:60%'>Год для формирования КУДиР</td><td><input type='text' name='Year' format='year' value='$Year'></td></tr>";
		echo "<tr><td colspan=2 style='font-weight:bold;'><br>Внимание!<br>Эту страницу распечатывать <u>горизонтально</u> (альбом, пейзаж)</td></tr>";
		echo "</table>";
		if(!$Year) echo "<button onclick='kudir2()'>Сформировать</button>";
		else {
			$mysqli = new mysqli(SERVER, USERNAME, DBPASS,DBNAME) or die('Ошибка подключения (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
			$result = $mysqli->query("SELECT `Дата`,`Сумма`,`Содержание` FROM `Entries` WHERE YEAR(`Дата`)='$Year' AND `Дебет` LIKE '01%' ORDER BY `Дата`") or die('Ошибка MySQL: ' . $mysqli->error);
			echo "<div class='kudir-header'>II. Расчет расходов на приобретение (сооружение, изготовление) основных средств<br>и на приобретение (создание самим налогоплательщиком) нематериальных активов,<br>учитываемых при исчислении налоговой базы по налогу<br>за $Year год</div>";
			echo "<table class='kudir2' border=1 cellpadding=1>";
			echo "<tr><th rowspan=2>№ п/п</th>"; // 1
			echo "<th rowspan=2>Наимено-вание объекта основных средств или нематери-альных активов</th>"; // 2
			echo "<th rowspan=2>Дата оплаты объекта основных средств или нематери-альных активов</th>"; //3
			echo "<th rowspan=2>Дата подачи докумен-тов на государ-ственную регистра-цию объектов основных средств</th>"; //4
			echo "<th rowspan=2>Дата ввода в эксплу-атацию (принятия к бухгал-терскому учету) объекта основных средств или немате-риальных активов</th>"; //5
			echo "<th rowspan=2>Первона-чальная стоимость объекта основных средств или немате-риальных активов (руб.)</th>"; //6
			echo "<th rowspan=2>Срок полезного использо-вания объекта основных средств или немате-риальных активов (количество лет)</th>"; //7
			echo "<th rowspan=2>Остаточ-ная стоимость объекта основных средств или немате-риальных активов (руб.)</th>"; //8
			echo "<th rowspan=2>Количество кварталов эксплуата-ции объекта основных средств или немате-риальных активов в налоговом периоде</th>"; //9
			echo "<th rowspan=2>Доля стоимости объекта основных средств или немате-риальных активов, прини-маемая в расходы за налоговый период (%)</th>"; //10
			echo "<th rowspan=2>Доля стоимости объекта основных средств или немате-риальных активов, прини-маемая в расходы за каждый квартал налогового периода (%) (гр. 10 / гр. 9)</th>"; //11
			echo "<th colspan=2>Сумма расходов, учитываемая при исчислении налоговой базы (руб.)</th>"; //12-13
			echo "<th rowspan=2>Включено в расходы за преды-дущие налоговые периоды применения упрощенной системы налого-обложения (руб.) (гр. 13 Расчета за предыдущие налоговые периоды)</th>"; //14
			echo "<th rowspan=2>Оставшаяся часть расходов, подлежащая списанию в после-дующих налоговых периодах (руб.) (гр. 8 - гр. 13 - гр. 14)</th>"; //15
			echo "<th rowspan=2>Дата выбытия (реали-зации) объекта основных средств или немате-риальных активов</th>"; //16
			echo "</tr><tr><th>за каждый квартал налогового периода (гр. 6 или гр. 8 х гр. 11 / 100)</th>"; //12
			echo "<th>за налоговый период (гр. 12 х гр. 9)</th></tr><tr>"; //13
			for($i=1;$i<17;$i++) echo "<td>$i</td>";
			echo "</tr>";
			$n=1;
			while(($line = $result->fetch_array(MYSQLI_NUM)) && $param['Объект']=='Расходы') {
				$nquart=5-(int)((substr($line[0],5,2)+2)/3);
				echo "<tr><td>$n</td><td edit>{$line[2]}</td><td edit format='date'>".toOurDate($line[0])."</td>";	// 1-3
				echo "<td edit format='date'>&nbsp;</td><td>".toOurDate($line[0])."</td><td edit format='decimal'>".number_format($line[1],2,'.',DLMTR)."</td>";	// 4-6
				echo "<td edit format='int'>&nbsp;</td><td edit format='decimal'>&nbsp;</td><td>$nquart</td>";	// 7-9
				echo "<td edit format='percent'>100</td><td format='percent' float formula='#(10)/#(9)'>&nbsp;</td>";	//10-11
				echo "<td format='decimal' float formula='(#(6)+#(8))*#(11)/100'>&nbsp;</td>";	// 12
				echo "<td format='decimal' formula='#(12)*#(9)'>&nbsp;</td>"; // 13
				echo "<td edit format='decimal'>&nbsp;</td><td format='decimal' formula='#(8)?#(8)-#(13)-#(14):\"\"'>&nbsp;</td><td edit format='date'>&nbsp;</td></tr>"; // 14-16
				$n++;
			}
			echo "<tr><td>Всего за отчет-ный (нало-говый) период</td><td>x</td><td>x</td><td>x</td><td>x</td>";	//1-5
			echo "<td format='decimal' formula='TOTAL'>&nbsp;</td>";	//	6
			echo "<td>x</td><td format='decimal' formula='TOTAL'>&nbsp;</td><td>x</td><td>x</td><td>x</td>";	//	7-11
			echo "<td format='decimal' formula='TOTAL'>&nbsp;</td>";	//	12
			echo "<td format='decimal' formula='TOTAL'>&nbsp;</td>";	//	13
			echo "<td format='decimal' formula='TOTAL'>&nbsp;</td><td format='decimal' formula='TOTAL'>&nbsp;</td><td>x</td></tr>";	//	14-16
			echo "</table>";
		}
	}
	elseif(isset($_GET['Kudir3'])) {
		if(preg_match("/^20\d\d$/",$_GET['Kudir3'])) $Year=$_GET['Kudir3'];
		echo "<table class='npr' cellpadding=4 border=0 style='margin-bottom:2em'>";
		echo "<tr><td style='font-weight:bold;width:60%'>Год для формирования КУДиР</td><td><input type='text' name='Year' format='year' value='$Year'></td></tr>";
		echo "<tr><td colspan=2 style='font-weight:bold;'><br>Внимание!<br>Эту страницу распечатывать <u>вертикально</u> (книга, портрет)</td></tr>";
		echo "</table>";
		if(!$Year) echo "<button onclick='kudir3()'>Сформировать</button>";
		else {
			echo "<div class='kudir-header'>III. Расчет суммы убытка, уменьшающей<br>налоговую базу по налогу, уплачиваемому в связи<br>с применением упрощенной системы налогообложения<br>в $Year году</div>";
			echo "<table class='kudir3' border=1 cellpadding=4>";
			echo "<tr><td>Наименование показателя</td><td>Код<br>строки</td><td>Значения<br>показателей</td></tr>";
			echo "<tr><td>1</td><td style='width:11%'>2</td><td style='width:22%'>3</td></tr>";
			echo "<tr><td class='t'>Сумма убытков, полученных по итогам предыдущих налоговых периодов, которые не были перенесены на начало истекшего налогового периода - всего:<br>(сумма по кодам строк 020 - 110)</td><td>010</td><td formula='#(3,3)+#(4,3)+#(5,3)+#(6,3)+#(7,3)+#(8,3)+#(9,3)+#(10,3)+#(11,3)+#(12,3)'>&nbsp;</td></tr>";
			echo "<tr><td class='t'>в том числе за:</td><td>&nbsp;</td><td>&nbsp;</td></tr>";
			for($i=1;$i<11;$i++) echo "<tr><td class='t' edit>за 20__ год</td><td>".sprintf("%02d",$i+1)."0</td><td edit format='decimal'>&nbsp;</td></tr>";
			echo "<tr><td class='t'>Налоговая база за истекший налоговый период, которая может быть уменьшена на убытки предыдущих налоговых периодов<br>(код стр. 040 справочной части раздела I Книги доходов и расходов)</td><td>120</td><td edit format='decimal'>&nbsp;</td></tr>";
			echo "<tr><td class='t'>Сумма убытков, на которую налогоплательщик фактически уменьшил налоговую базу за истекший налоговый период<br>(в пределах суммы убытков, указанных по стр. 010)</td><td>130</td><td edit format='decimal'>&nbsp;</td></tr>";
			echo "<tr><td class='t'>Сумма убытка за истекший налоговый период<br>(код стр. 041 справочной части Раздела I Книги учета доходов и расходов)</td><td>140</td><td edit format='decimal'>&nbsp;</td></tr>";
			echo "<tr><td class='t'>Сумма убытков на начало следующего налогового периода, которые налогоплательщик вправе перенести на будущие налоговые периоды<br>(код стр. 010 - код стр. 130 + код стр. 140) всего:</td><td>150</td><td format='decimal' formula='#(1,3)-#(14,3)+#(15,3)'>&nbsp;</td></tr>";
			echo "<tr><td class='t'>в том числе за:</td><td>&nbsp;</td><td>&nbsp;</td></tr>";
			for($i=1;$i<11;$i++) echo "<tr><td class='t' edit>за 20__ год</td><td>".sprintf("%02d",$i+15)."0</td><td edit format='decimal'>&nbsp;</td></tr>";
			echo "</table>";
		}
	}
	elseif(isset($_GET['Kudir4'])) {
		if(!$param=read_param()) exit;
		if(preg_match("/^20\d\d$/",$_GET['Kudir4'])) $Year=$_GET['Kudir4'];
		echo "<table class='npr' cellpadding=4 border=0 style='margin-bottom:2em'>";
		echo "<tr><td style='font-weight:bold;width:60%'>Год для формирования КУДиР</td><td><input type='text' name='Year' format='year' value='$Year'></td></tr>";
		echo "<tr><td colspan=2 style='font-weight:bold;'><br>Внимание!<br>Эту страницу распечатывать <u>горизонтально</u> (альбом, пейзаж)</td></tr>";
		echo "</table>";
		if(!$Year) echo "<button onclick='kudir4()'>Сформировать</button>";
		else {
			echo "<div class='kudir-header'>IV. Расходы, предусмотренные пунктом 3.1 статьи 346.21 Налогового кодекса Российской Федерации, уменьшающие сумму<br>налога, уплачиваемого в связи с применением упрощенной системы налогообложения (авансовых платежей по налогу)<br>в $Year году</div>";
			echo "<table class='kudir2' border=1 cellpadding=4>";
			echo "<tr><th rowspan=2>№<br><nobr>п/п</nobr></th><th rowspan=2>Дата и номер первич-ного доку-мента</th>";	//	1-2
			echo "<th rowspan=2>Период, за который произведена уплата страховых взносов, выплата пособия по временной нетрудо-способности, предусмот-ренных в графах 4 - 9</th>"; //	3
			echo "<th colspan=6>Сумма</th><th rowspan=2>Итого<br>(руб.)</th></tr>";
			echo "<tr><th>Страховые взносы на обязательное пенсионное страхование (руб.)</th>"; //	4
			echo "<th>Страховые взносы на обязательное социальное страхование на случай временной нетрудо-способности и в связи с материнством (руб.)</th>"; //	5
			echo "<th>Страховые взносы на обязательное медицинское страхование (руб.)</th>"; //	6
			echo "<th>Страховые взносы на обязательное социальное страхование от несчастных случаев на производстве и профессио-нальных заболеваний (руб.)</th>"; //	7
			echo "<th>Расходы по выплате пособия по временной нетрудо-способности (руб.)</th>"; //	8
			echo "<th>Платежи (взносы) по договорам добровольного личного страхования (руб.)</th></tr><tr>"; //	9
			for($i=1;$i<11;$i++) echo "<td>$i</td>"; echo "</tr>";
			$mysqli = new mysqli(SERVER, USERNAME, DBPASS,DBNAME) or die('Ошибка подключения (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
			$num=1; $row=1; $top=1;
			for($quart=1;$quart<5;$quart++) {
				if($param['Объект']=='Доходы') {
					$date1=$Year."-".sprintf("%02d",($quart-1)*3+1)."-01";
					$date2=$Year."-".sprintf("%02d",$quart*3)."-31";
					$result = $mysqli->query("SELECT `Дата`,`Сумма`,`Содержание` FROM `Entries` WHERE `Дата`>='$date1' AND `Дата`<='$date2' AND `Кредит` REGEXP '51|50' AND (`Дебет` LIKE '69%' OR `Содержание` REGEXP 'ОПС|ОМС|ОСС|ФСС|ДЛС|ПФР|пособи') ORDER BY `Дата`") or die('Ошибка MySQL: ' . $mysqli->error);
					while($line = $result->fetch_array(MYSQLI_NUM)) {
						if(strpos($line[2],'превышени')!==false && preg_match("/20\d\d/",$line[2],$match)) $year=$match[0]; else $year=$Year;
						echo "<tr><td>$num</td><td>".toOurDate($line[0])."</td><td>$year год</td>";
						$sum=number_format($line[1],2,'.',DLMTR);
						if(preg_match("/ОПС|ПФР/",$line[2])) $col=4;
						elseif(preg_match("/ОСС|ФСС/",$line[2])) { if(strpos($line[2],'несчастн')!==false) $col=7; else $col=5; }
						elseif(strpos($line[2],'ОМС')!==false) $col=6;
						elseif(strpos($line[2],'пособи')!==false) $col=8;
						else $col=9;
						for($i=4;$i<10;$i++) echo "<td class='d' edit format='decimal'>".($col==$i?$sum:'&nbsp;')."</td>";
						echo "<td class='d' format='decimal' formula='#(4)+#(5)+#(6)+#(7)+#(8)+#(9)'>&nbsp;</td></tr>";
						$num++; $row++;
					}
				}
				switch($quart) {
					case 1: $rom='I'; break;
					case 2:	$rom='II'; $term='полугодие'; break;
					case 3: $rom='III'; $term='9 месяцев'; break;
					case 4: $rom='IV'; $term='год'; break;
				}
				echo "<tr><td colspan=3 class='t'>Итого за $rom квартал</td>";
				if($row==$top) for($i=4;$i<11;$i++) echo "<td>&nbsp;</td>";
				else {
					for($i=4;$i<11;$i++) {
						$cells=[];
						for($j=$top;$j<$row;$j++) $cells[$j]="#($j,$i)";
						echo "<td class='d' format='decimal' formula='".implode('+',$cells)."'>";
					}
				}
				echo "</tr>";
				$row++;
				if($quart>1) {
					echo "<tr><td colspan=3 class='t'>Итого за $term</td>";
					for($i=2;$i<9;$i++) {
						echo "<td class='d' format='decimal' formula='#(".($top-1).",$i)+#(".($row-1).",$i)'>";
					}
					$row++;
				}
				$top=$row;
			}
			echo "</table>";
		}
	}
	elseif(isset($_GET['Kudir5'])) {
		if(!$param=read_param()) exit;
		if(preg_match("/^20\d\d$/",$_GET['Kudir5'])) $Year=$_GET['Kudir5'];
		echo "<table class='npr' cellpadding=4 border=0 style='margin-bottom:2em'>";
		echo "<tr><td style='font-weight:bold;width:60%'>Год для формирования КУДиР</td><td><input type='text' name='Year' format='year' value='$Year'></td></tr>";
		echo "<tr><td colspan=2 style='font-weight:bold;'><br>Внимание!<br>Эту страницу распечатывать <u>вертикально</u> (книга, портрет)</td></tr>";
		echo "</table>";
		if(!$Year) echo "<button onclick='kudir5()'>Сформировать</button>";
		else {
			echo "<div class='kudir-header'>V. Сумма торгового сбора, уменьшающая сумму налога, уплачиваемого в связи с применением упрощенной системы налогообложения (авансовых платежей по налогу), исчисленного по объекту налогообложения от вида предпринимательской деятельности, в отношении которого установлен торговый сбор<br>за $Year год</div>";
			echo "<table class='kudir3' border=1 cellpadding=4 style='width:80%'>";
			echo "<tr><th>№ п/п</td><td>Дата и номер<br>первичного документа</td><td>Период, за который произведена<br>уплата торгового сбора</td><td>Сумма уплаченного<br>торгового сбора</td></tr>";
			echo "<tr><td>1</td><td>2</td><td>3</td><td>4</td></tr>";
			$mysqli = new mysqli(SERVER, USERNAME, DBPASS,DBNAME) or die('Ошибка подключения (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
			$num=1; $row=1; $top=1;
			for($quart=1;$quart<5;$quart++) {
				if($param['Объект']=='Доходы') {
					$date1=$Year."-".sprintf("%02d",($quart-1)*3+1)."-01";
					$date2=$Year."-".sprintf("%02d",$quart*3)."-31";
					$result = $mysqli->query("SELECT `Дата`,`Сумма`,`Содержание` FROM `Entries` WHERE `Дата`>='$date1' AND `Дата`<='$date2' AND `Кредит` REGEXP '51|50' AND `Содержание` REGEXP 'торгов.{2,3} +сбор' ORDER BY `Дата`") or die('Ошибка MySQL: ' . $mysqli->error);
					while($line = $result->fetch_array(MYSQLI_NUM)) {
						echo "<tr><td>$num</td><td>".toOurDate($line[0])."</td><td edit>&nbsp;</td><td>".number_format($line[1],2,'.',DLMTR)."</td></tr>";
						$num++; $row++;
					}
				}
				switch($quart) {
					case 1: $rom='I'; break;
					case 2:	$rom='II'; $term='полугодие'; break;
					case 3: $rom='III'; $term='9 месяцев'; break;
					case 4: $rom='IV'; $term='год'; break;
				}
				echo "<tr><td colspan=3 class='t'>Итого за $rom квартал</td>";
				if($row==$top)  echo "<td>&nbsp;</td>";
				else {
					$cells=[];
					for($j=$top;$j<$row;$j++) $cells[$j]="#($j,4)";
					echo "<td class='d' format='decimal' formula='".implode('+',$cells)."'>";
				}
				echo "</tr>";
				$row++;
				if($quart>1) {
					echo "<tr><td colspan=3 class='t'>Итого за $term</td><td class='d' format='decimal' formula='#(".($top-1).",2)+#(".($row-1).",2)'>";
					$row++;
				}
				$top=$row;
			}
			echo "</table>";
		}
	}
	elseif(isset($_GET['FindEntry'])) {
		if(count($_POST)==0) {
			$finfo=get_field_info('Entries');
			echo "<table class='npr' cellpadding=4 border=0 style='margin-top:1em'>";
			foreach($finfo as $key=>$value) {
				$value=preg_replace("/'/","\"",$value);
				if($key=='Дата') echo "<tr><td style='font-weight:bold'>Дата от:</td><td><input type='text' name='Дата1' format='$value'> <b>до:</b> <input type='text' name='Дата2' format='$value'></td></tr>";
				elseif($key=='Дата_Время') echo "<tr><td style='font-weight:bold'>Дата_Время от:</td><td><input type='text' name='Время1' format='$value'> <b>до:</b> <input type='text' name='Время2' format='$value'></td></tr>";
				else echo "<tr><td style='font-weight:bold'>$key:</td><td><input type='text' name='$key' format='$value'></td></tr>";
			}
			echo "</table>";
			echo "<button onclick='goFindEntry()' style='margin: 20px 37% 0'>Найти</button>";
		}
		else {
			$mysqli = new mysqli(SERVER, USERNAME, DBPASS,DBNAME) or die('Ошибка подключения (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
			$finfo=get_field_info('Entries');
			$finfo['Дата1']=$finfo['Дата2']=$finfo['Время1']=$finfo['Время2']='date';
			$query="SELECT * FROM `Entries` WHERE";
			foreach($_POST as $key=>$value) {
				if(isset($finfo[$key=iconv('UTF-8', 'windows-1251', $key)])) {
					$value=iconv('UTF-8', 'windows-1251', $value);
					$value=trim(preg_replace("/<.+>/",'',$mysqli->real_escape_string($value)));
					if($finfo[$key]=='date' && $value!='') $value=toSQLDate($value);
					elseif(substr($finfo[$key],0,7)=='decimal') $value=preg_replace("/[\s`]/",'',$value);
					if(!preg_match($reserved,$value) && $value!='') {
						if($key=='Дата1') $query.=" `Дата`>='$value' AND";
						elseif($key=='Дата2') $query.=" `Дата`<='$value' AND";
						elseif($key=='Время1') $query.=" `Дата_Время`>='$value' AND";
						elseif($key=='Время2') $query.=" `Дата_Время`<='$value' AND";
						elseif($key=='Содержание') $query.=" `Содержание` LIKE '%$value%' AND";
						else $query.=" `$key`='$value' AND"; 
					}
				}
			}
			if(strlen($query)<30) exit;
			$query=substr($query,0,strlen($query)-4);
			$query.=' ORDER BY `Дата`';
			$reply['Entries']=show_all($query);
			echo php2json($reply);
		}
	}
	elseif(isset($_GET['CounterAgents'])) {
		$query="SELECT `Счет`,`Наименование`,`Дебет`,`Кредит`,`ИНН`  FROM `Accounts` AS `Goods` WHERE `Счет` LIKE '60.%' OR `Счет` LIKE '62.%'";
		echo show_all($query);
	}
	elseif(isset($_GET['LoadBank'])) {
		if(!isset($_GET['BankLoaded'])) {
			if (!isset($_FILES["userfile"])) {
				echo "<form action='?LoadBank' method='POST' enctype='multipart/form-data' target='i_frame' onsubmit='cw.isLoading()' style='margin:10px'>";
				echo "<input type='hidden' name='MAX_FILE_SIZE' value='64000'>";
				echo "Выберите файл с выпиской банка для загрузки: <input type='file' name='userfile' format='file'> ";
				echo "<input type='submit' value='Загрузить'></form>";
				echo "<iframe name='i_frame' width='100%' height='90%' src='about:blanc' style='border:0'>Oops...</iframe>";
				exit;
			}
			else {
				if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
					$filename = $_FILES['userfile']['tmp_name'];
					if(!preg_match("/^kl_to_1c.*\.txt$/",$_FILES['userfile']['name'])) echo 'Можно указать только файл вида "kl_to_1c*.txt"';
					elseif (!move_uploaded_file($filename, "upload/bankstatement.txt")) echo 'Ошибка при перемещении файла';
					else $loaded=true;
				} 
				else echo "Ошибка при загрузке файла";
			}
		}
		if($loaded || isset($_GET['BankLoaded'])) {
			$bs=file("upload/bankstatement.txt",FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
			$bankst=[]; $sdi=0;
			for($i=0;$i<count($bs);$i++) {
				if($i==0 && $bs[0]!='1CClientBankExchange') { echo "Неизвестный формат файла"; break; }
				list($key,$value)=explode('=',$bs[$i]);
				if(!isset($srs) && $bs[$i]=='СекцияРасчСчет') { $srs=[]; continue; }
				if(isset($srs)) {
					if($key=='КонецРасчСчет') { $bankst['СекцияРасчСчет']=$srs; unset($srs); continue; }
					$srs[$key]=str_replace('\\','/',$value); continue;
				}
				if(!$sd && $key=='СекцияДокумент') { $sd=[]; $sd['Документ']=$value; continue; }
				if($sd) {
					if($key=='КонецДокумента') { $bankst['СекцияДокумент'][$sdi++]=$sd; unset($sd); continue; }
					$sd[$key]=str_replace('\\','/',$value); continue;
				}
			}
			$mysqli = new mysqli(SERVER, USERNAME, DBPASS,DBNAME) or die('Ошибка подключения (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
			if(isset($_GET['BindAccount']) && preg_match("/^51\.\d+\.\d+$|^51\.\d+$|^51$/",$_GET['BindAccount'])) {
				$result=$mysqli->query("UPDATE `Accounts` SET `Код`='{$bankst['СекцияРасчСчет']['РасчСчет']}' WHERE `Счет`='{$_GET['BindAccount']}' LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
			}
			$result=$mysqli->query("SELECT `Счет` FROM `Accounts` WHERE `Код`='{$bankst['СекцияРасчСчет']['РасчСчет']}' LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
		    if(!($line=$result->fetch_array(MYSQLI_NUM))) {
				echo "<table class='npr' cellpadding=4 border=0>";
				echo "<tr class='noHL'><td style='font-weight:bold'>Укажите счет, к которому необходимо<br>привязать загруженную выписку банка</td><td><input type='text' name='СчетБ' format='varchar(10)'></td></tr>";
				echo "</table>";
				echo "<button onclick='bindAccount()'>Привязать счет</button>";
			}
			else {
				$Acc=$line[0];
				echo "Загружается выписка по счету {$Acc}, номер счета в банке {$bankst['СекцияРасчСчет']['РасчСчет']}<br>";
				$First=toSQLDate($bankst['СекцияРасчСчет']['ДатаНачала']); 
				$End=toSQLDate($bankst['СекцияРасчСчет']['ДатаКонца']);
				$fd=substr($First,0,8).'01';
				$result = $mysqli->query("SELECT `Остаток` FROM `Balances` WHERE `Дата`='$fd' AND `Счет`='$Acc' LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
			    $line = $result->fetch_array(MYSQLI_NUM);
				$balance=$line[0];
				$result = $mysqli->query("SELECT SUM(`Сумма`) FROM `Entries` WHERE `Дата`>='$fd' AND `Дата`<'$First' AND `Дебет`='$Acc' LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
			    $line = $result->fetch_array(MYSQLI_NUM);
				$balance-=$line[0];
				$result = $mysqli->query("SELECT SUM(`Сумма`) FROM `Entries` WHERE `Дата`>='$fd' AND `Дата`<'$First' AND `Кредит`='$Acc' LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
			    $line = $result->fetch_array(MYSQLI_NUM);
				$balance+=$line[0];
				echo "Начальный остаток на {$bankst['СекцияРасчСчет']['ДатаНачала']} в учете: ".(-$balance).", в банке: {$bankst['СекцияРасчСчет']['НачальныйОстаток']} -- ";
				if(abs($balance+$bankst['СекцияРасчСчет']['НачальныйОстаток'])>0.005) echo "<b style='color:red'>не совпадает</b><br>Загрузите выписку с более ранней начальной датой";
				else {
					echo "<b>совпадает</b><br>";
					$result = $mysqli->query("TRUNCATE TABLE `LoadBank`");
					for($i=0;$i<count($bankst['СекцияДокумент']);$i++) {
						$doc=$bankst['СекцияДокумент'][$i]; $params=[]; $inout=0;
						if($doc['ПлательщикСчет']==$bankst['СекцияРасчСчет']['РасчСчет'] || $doc['ПлательщикРасчСчет']==$bankst['СекцияРасчСчет']['РасчСчет']) $inout=-1;
						elseif($doc['ПолучательСчет']==$bankst['СекцияРасчСчет']['РасчСчет'] || $doc['ПолучательРасчСчет']==$bankst['СекцияРасчСчет']['РасчСчет']) $inout=1;
						else echo "Неопознанный документ:<br><pre>".print_r($doc,true)."</pre><br>";
						if($inout!=0) {
							if($inout<0) {
								$result = $mysqli->query("SELECT `Счет` FROM `Accounts` WHERE `ИНН`='{$doc['ПолучательИНН']}' LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
							    $line = $result->fetch_array(MYSQLI_NUM);
								$params['Дата']=toSQLDate($doc['ДатаСписано']);
								$params['Дебет']=$line?$line[0]:'';
								$params['Кредит']=$Acc;
							}
							else {
								$result = $mysqli->query("SELECT `Счет` FROM `Accounts` WHERE `ИНН`='{$doc['ПлательщикИНН']}' LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
							    $line = $result->fetch_array(MYSQLI_NUM);
								$params['Дата']=toSQLDate($doc['ДатаПоступило']);
								$params['Дебет']=$Acc;
								$params['Кредит']=$line?$line[0]:'';
							}
							$params['Сумма']=$doc['Сумма'];
							$params['НомерДок']=$doc['Номер'];
							$params['НазначениеПлатежа']=$doc['НазначениеПлатежа'];
							$query="SELECT `Дебет`,`Кредит`,`Содержание` FROM `Entries` WHERE `Дата`='{$params['Дата']}' AND `Сумма`='{$params['Сумма']}'";
							if($params['Дебет']!='') $query.=" AND `Дебет`='{$params['Дебет']}'";
							if($params['Кредит']!='') $query.=" AND `Кредит`='{$params['Кредит']}'";
							$query.=" LIMIT 1";
							$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
						    $line = $result->fetch_array(MYSQLI_NUM);
							$EntryExists=(boolean)$line;
							if($EntryExists) {
								$params['НомерДок']='проведено';
								$params['Дебет']=$line[0];
								$params['Кредит']=$line[1];
								$params['Содержание']=$line[2];
							}
							else {
								$result = $mysqli->query("SELECT `Содержание` FROM `Stencil` WHERE `Дебет`='{$params['Дебет']}' AND `Кредит`='{$params['Кредит']}' LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
							    $line = $result->fetch_array(MYSQLI_NUM);
								if($line) $params['Содержание']=apply_stencil($line[0],$params);
								elseif($inout<0) $params['Содержание']="Оплата {$doc['Получатель']} {$doc['НазначениеПлатежа']}";
								else $params['Содержание']="Поступление {$doc['Плательщик']} {$doc['НазначениеПлатежа']}";
							}
							if($params['НомерДок']!='проведено') {
								$query="INSERT INTO `LoadBank` SET";
								foreach($params as $key=>$value) $query.=" `$key`='$value',";
								$query=substr($query,0,strlen($query)-1);
								$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
							}
						}
					}
					echo show_all("SELECT * FROM `LoadBank`");
					$result = $mysqli->query("SELECT `Дебет` FROM `Accounts` WHERE `Счет`='$Acc' LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
				    $line = $result->fetch_array(MYSQLI_NUM);
					$balance=-$line[0];
					$result = $mysqli->query("SELECT SUM(`Сумма`) FROM `LoadBank` WHERE `Дебет`='$Acc' AND `НомерДок`!='проведено' LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
				    $line = $result->fetch_array(MYSQLI_NUM);
					$balance-=$line[0];
					$result = $mysqli->query("SELECT SUM(`Сумма`) FROM `LoadBank` WHERE `Кредит`='$Acc' AND `НомерДок`!='проведено' LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
				    $line = $result->fetch_array(MYSQLI_NUM);
					$balance+=$line[0];
					$result = $mysqli->query("SELECT MAX(`Дата`) FROM `Entries` WHERE `Кредит`='$Acc' OR `Дебет`='$Acc' LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
				    $line = $result->fetch_array(MYSQLI_NUM);
					echo "Конечный остаток на ".toOurDate(max(toSQLDate($bankst['СекцияРасчСчет']['ДатаКонца']),$line[0]))." в учете: ".(-$balance).", в банке: {$bankst['СекцияРасчСчет']['КонечныйОстаток']} -- ";
					if(abs($balance+$bankst['СекцияРасчСчет']['КонечныйОстаток'])>0.005) echo "<b style='color:red'>не совпадает</b>"; else echo "<b>совпадает</b>";
					echo "<br><button onclick='loadBankToAccount()' disabled>Загрузить в учет</button>";
				}
			}
		}
		if(!isset($_GET['BankLoaded'])) echo "<script>
			window.parent.cw.isOK(); var script=document.createElement('script'); script.id='tempscript';
			script.text=\"cw.cnt.innerHTML=document.getElementsByTagName('iframe')[0].contentWindow.document.body.innerHTML; prepInputs(); document.body.removeChild(document.getElementById('tempscript'));\";
			window.parent.document.body.appendChild(script);
		</script>";
	}
	elseif(isset($_GET['LoadBankToAccount'])) {
		$mysqli = new mysqli(SERVER, USERNAME, DBPASS,DBNAME) or die('Ошибка подключения (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
		$result = $mysqli->query("SELECT `Дата`,`Дебет`,`Кредит`,`Сумма`,`Содержание` FROM `LoadBank`") or die('Ошибка MySQL: ' . $mysqli->error);
		while($params = $result->fetch_array(MYSQLI_ASSOC)) makeEntry($params);
		liveVidgets(ACCOUNTS | ENTRIES | GOODS | CTRAGENTS);
		$reply['reply']="Обработано и загружено {$result->num_rows} строк банковской выписки";
		echo php2json($reply);
	}
	elseif($extended) include('extended.php');
	exit;
}
function apply_stencil($content,$params) {
	$str=$content;
	while(preg_match("/{[а-яА-Я\d\-\*\/\.\,]+}/",$str,$matches)) {
		if(preg_match("/[а-яА-Я]+/",$matches[0],$field)) {
			if($field[0]=='Дата') {
				$value=toOurDate($params['Дата']);
				if(preg_match("/\-\d+/",$matches[0],$operand)) $operand=substr($operand[0],1);
				if($operand) $value=date("d.m.Y",mktime(0,0,0,substr($value,3,2),substr($value,0,2)-$operand,substr($value,6)));
				else {
					if(preg_match("/\,\d+/",$matches[0],$operand)) $operand=substr($operand[0],1);
					if($operand) {
						$value=date("d.m.Y",mktime(0,0,0,substr($value,3,2),$operand,substr($value,6)));
						if(substr($value,3,2)>substr($params['Дата'],5,2)) $value=date("d.m.Y",mktime(0,0,0,substr($value,3,2),0,substr($value,6)));
					}
				}
			}
			elseif($field[0]=='Сумма') {
				$value=$params['Сумма'];
				if(preg_match("/\*[\d\.\,]+/",$matches[0],$operand)) $operand=str_replace(',','.',substr($operand[0],1));
				if($operand) $value=sprintf("%01.2f",$value*$operand);
				else {
					if(preg_match("/\/[\d\.\,]+/",$matches[0],$operand)) $operand=str_replace(',','.',substr($operand[0],1));
					if($operand) $value=sprintf("%01.2f",$value/$operand);
				}
			}
			elseif($field[0]=='НомерДок') $value=$params['НомерДок'];
			elseif(preg_match("/{$field[0]}\s+([\d,\.]+)/",$params['НазначениеПлатежа'],$value)) $value=$value[1];
			else $value='';
			$str=str_replace($matches[0],$value,$str);
		}
	}
	return $str;
}
function read_param($flag=true) {
	$kudirdata=file('kudir.dat', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	if($kudirdata) {
		for($i=0;$i<count($kudirdata);$i++) {
			if(preg_match("/^[\w\x80-\xFF]+=[\w\d\s\.,:\+\-\*\/\"\x80-\xFF]*$/",$kudirdata[$i])) {
				list($key,$value)=explode('=',$kudirdata[$i]);
				$param[$key]=$value;
			}
			elseif(preg_match("/^[\w\d\s\.,:\+\-\*\/\"\x80-\xFF]+$/",$kudirdata[$i]) && $key) $param[$key].="\n".$kudirdata[$i];
			else echo "Нечитаемая строка в параметрах КУДиР:<br>".$kudirdata[$i]."<br>";
		}
	}
	else { if($flag) echo "Необходимо заполнить параметры титульной страницы и правила принятия в доходы и расходы"; $param=null; }
	return $param;
}
function calc_rule($rule,$str,$sum) {
	preg_match_all("/[a-zA-Zа-яА-Я]+|\d+[\.,]?\d*/",$rule,$operand,PREG_SET_ORDER);
	if(!preg_match("/[\+\-\*\/]/",$rule,$operator)) $operator=false;
	for($i=0;$i<count($operand);$i++) {
		if($operand[$i][0]=='Сумма') $operand[$i]=$sum;
		elseif(preg_match("/^\d+[\.,]?\d*$/",$operand[$i][0])) $operand[$i]=str_replace(',','.',$operand[$i]);
		elseif(preg_match("/{$operand[$i][0]}\s+(\d+[\.,]?\d*)/",$str,$match)) $operand[$i]=str_replace(',','.',$match[1]);
		else $operand[$i]='';
	}
	if($operator && $operand[0] && $operand[1])	{
		switch($operator[0]) {
			case '+': return $operand[0]+$operand[1];
			case '-': return $operand[0]-$operand[1];
			case '*': return $operand[0]*$operand[1];
			case '/': return $operand[0]/$operand[1];
		}
	}
	if($operand[0]) return $operand[0];
	else return '';
}
function format_rules($rules) {
	$ret_rules=explode("\n",$rules);
	for($i=0;$i<count($ret_rules);$i++) {
		$ret_rules[$i]=preg_replace("/,\s+/",',',$ret_rules[$i]);
		$arr=explode(' ',$ret_rules[$i]);
		$ret_rules[$i]=[];
		for($j=0;$j<count($arr);$j++) {
			if(preg_match("/^Д:|^К:/",$arr[$j])) {
				if(strpos($arr[$j],'*')!==false) $ret_rules[$i][substr($arr[$j],0,1)]="/.+/";
				else {
					$accs=explode(",",substr(str_replace('.',"\.",$arr[$j]),2));
					$ret_rules[$i][substr($arr[$j],0,1)]="/^".implode("|^",$accs)."/";
				}
			}
			elseif(preg_match("/^С:/",$arr[$j])) $ret_rules[$i]['С']=substr($arr[$j],2);
		}
	}
	return $ret_rules;
}
function make_page($strings,$mm=-1) {
	global $param;
	global $taxPayed;
	global $pfrPayed;
	global $duePayed;
	$revPage=0; $expPage=0;
	static $revQuart=0;
	static $expQuart=0;
	static $revTotal=0;
	static $expTotal=0;
	static $pageNum=2;
	?>
		<div class='page'>
			<div class='kudir-upheader'>Лист <? echo $pageNum++?></div>
			<? if($pageNum==3) echo "<div class='kudir-header'>I. Доходы и расходы</div>"; ?>
			<table class='kudir-body' border=1>
			<tr><td colspan=3>Регистрация</td><td colspan=2>Сумма</td></tr>
			<tr><td style='width:5%'>№<br>п/п</td><td style='width:14%'>Дата и номер первичного документа</td><td>Содержание операции</td><td style='width:14%'>Доходы, учитываемые при исчислении налоговой базы</td><td style='width:14%'>Расходы, учитываемые при исчислении налоговой базы</td></tr>
			<tr><td>1</td><td>2</td><td>3</td><td>4</td><td>5</td></tr>
			<?	for($i=0;$i<count($strings);$i++) {
					echo "<tr><td>{$strings[$i][0]}</td><td>{$strings[$i][1]}</td><td class='t'>{$strings[$i][2]}</td><td class='d'>".number_format($strings[$i][3],2,'.',DLMTR)."</td><td class='d'>".number_format($strings[$i][4],2,'.',DLMTR)."</td></tr>";
					$revPage+=$strings[$i][3];
					$expPage+=$strings[$i][4];
					$revQuart+=$strings[$i][3];
					$expQuart+=$strings[$i][4];
					$revTotal+=$strings[$i][3];
					$expTotal+=$strings[$i][4];
				} 
				echo "<tr><td>&nbsp;</td><td>&nbsp;</td><td class='d'>Итого на странице:</td><td class='d'>".number_format($revPage,2,'.',DLMTR)."</td><td class='d'>".number_format($expPage,2,'.',DLMTR)."</td></tr>";
				if($mm>=0) {
					if($mm>0) {
						echo "<tr><td>&nbsp;</td><td>&nbsp;</td><td class='d'>Итого за $mm квартал:</td><td class='d'>".number_format($revQuart,2,'.',DLMTR)."</td><td class='d'>".number_format($expQuart,2,'.',DLMTR)."</td></tr>";
						$revQuart=0; $expQuart=0;
					}
					switch($mm) {
						case 2: $mmm='за полугодие'; break;
						case 3: $mmm='за 9 месяцев'; break;
						case 4: $mmm='за год'; break;
					}
					if($mm==0 || $mm>1) echo "<tr><td>&nbsp;</td><td>&nbsp;</td><td class='d'>Итого $mmm:</td><td class='d'>".number_format($revTotal,2,'.',DLMTR)."</td><td class='d'>".number_format($expTotal,2,'.',DLMTR)."</td></tr>";
				}
			 ?>
			</table>
			<? if(isset($mm) && ($mm==4 || $mm==0)) { ?>
				<table class='kudir-body'>
				<tbody>
				<tr><td style='width:6%'>&nbsp;</td><td style='width:70%'>&nbsp;</td><td>&nbsp;</td></tr>
				<tr><td>&nbsp;</td><td class='t'>Справка к разделу I</td><td>&nbsp;</td></tr>
				<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
				<tr><td>010</td><td class='t'>Сумма полученных доходов за налоговый период</td><td class='d'><? echo number_format($revTotal,2,'.',DLMTR); ?></td></tr>
				<tr><td>020</td><td class='t'>Сумма произведенных расходов за налоговый период</td><td class='d'><? echo number_format($expTotal,2,'.',DLMTR); ?></td></tr>
				<tr><td>030</td><td class='t'>Сумма разницы между суммой уплаченного минимального налога и суммой исчисленного в общем порядке налога за предыдущий налоговый период</td><td class='d' <? if($param['Объект']=='Расходы') echo " edit format='decimal'"; ?> >0.00</td></tr>
				<tr><td>&nbsp;</td><td class='t'>Итого получено:</td><td>&nbsp;</td></tr>
				<tr><td>040</td><td class='t'>- доходов</td><td>&nbsp;</td></tr>
				<tr><td>&nbsp;</td><td class='t'>(код стр. 010 - код стр. 020 - код стр. 030)</td><td class='d' format='decimal' formula='(#(4,3)-#(5,3)-#(6,3))>=0?#(4,3)-#(5,3)-#(6,3):"-"'>&nbsp;</td></tr>
				<tr><td>041</td><td class='t'>- убытков</td><td>&nbsp;</td></tr>
				<tr><td>&nbsp;</td><td class='t'>(код стр. 020 + код стр. 030) - код стр. 010)</td><td class='d' format='decimal' formula='(#(4,3)-#(5,3)-#(6,3))<0?-#(4,3)+#(5,3)+#(6,3):"-"'>&nbsp;</td></tr>
				</tbody>
				<tbody class='npr' style='background-color:#e0e0f0;'>
				<tr><td colspan=3>&nbsp;</td></tr>
				<tr><td>&nbsp;</td><td colspan=2 class='t'>Дополнительная информация (не печатается) </td></tr>
				<tr><td colspan=3>&nbsp;</td></tr>
				<? if($param['Объект']=='Доходы') {
					echo "<tr><td>&nbsp;</td><td class='t'>Налог 6%</td><td class='d'>".number_format(round($revTotal*0.06),2,'.',DLMTR)."</td></tr>";
					echo "<tr><td>&nbsp;</td><td class='t'>Налог уплачен</td><td class='d'>".number_format($taxPayed,2,'.',DLMTR)."</td></tr>";
					echo "<tr><td>&nbsp;</td><td class='t'>Взносы уплачены</td><td class='d'>".number_format($pfrPayed,2,'.',DLMTR)."</td></tr>";
					echo "<tr><td>&nbsp;</td><td class='t'>Торговый сбор уплачен</td><td class='d'>".number_format($duePayed,2,'.',DLMTR)."</td></tr>";
					echo "<tr><td>&nbsp;</td><td class='t'>Налог к уплате</td><td class='d'>".((round($revTotal*0.06)-$taxPayed-$pfrPayed-$duePayed)>0?number_format(round($revTotal*0.06)-$taxPayed-$pfrPayed-$duePayed,2,'.',DLMTR):'0.00')."</td></tr>";
				}
				else {
				   	echo "<tr><td>&nbsp;</td><td class='t'>Налог 15%</td><td class='d' formula='((#(4,3)-#(5,3)-#(6,3))*0.15).toFixed()'></td></tr>";
					echo "<tr><td>&nbsp;</td><td class='t'>Налог в %% к доходам</td><td class='d' formula='#(15,3)*100/#(4,3)'></td></tr>";
					echo "<tr><td>&nbsp;</td><td class='t'>Налог уплачен</td><td class='d'>".number_format($taxPayed,2,'.',DLMTR)."</td></tr>";
					echo "<tr><td>&nbsp;</td><td class='t'>Налог 15% к уплате</td><td class='d' formula='#(15,3)-#(17,3)'></td></tr>";
					echo "<tr><td>&nbsp;</td><td class='t'>Минимальный налог</td><td class='d' formula='(#(4,3)*0.01).toFixed()'></td></tr>";
				}
				?>
				</tbody>
				</table>
			<? 	} ?>
		</div>
	<?PHP
}
function show_all($query,$noEdit=false) {
		$str='';
		$mysqli = new mysqli(SERVER, USERNAME, DBPASS,DBNAME) or die('Ошибка подключения (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
	    $result = $mysqli->query($query) or die("Query failed : " . $mysqli->error);
		if($result->num_rows>0) {
		    $str.="<table cellpadding=2 border=1>";
			if(strpos($query,'LIMIT')!==false && $result->num_rows==ELINES) $str.="<caption class=\"moreInfo\">Показать еще</caption>";
			$str.="<tr>";
			if(!$noEdit) $str.= "<th class=\"npr\"><div class=\"edit\"></div></th>";
		    $finfo = $result->fetch_fields();
			for ($i = 0; $i < $result->field_count; $i++) {
				if($finfo[$i]->max_length>0 || $noEdit) {
					$str.="<th>".$finfo[$i]->name."</th>";
				}
			}
		    $str.="</tr>";
		    while ($line = $result->fetch_array(MYSQLI_NUM)) {
				$str.="<tr>";
				if(!$noEdit) $str.= "<td class=\"npr\"><div class=\"e-edit\"></div></td>";
		        for($i=0; $i<$result->field_count; $i++) {
					if($finfo[$i]->max_length>0 || $noEdit) {
						if($finfo[$i]->type==246 || preg_match("/^\d+\.\d{2}$/",$line[$i]))	$str.= "<td class=\"decimal\">".($line[$i]!=0?number_format($line[$i],2,'.',DLMTR):"&nbsp;")."</td>";		// decimal
						elseif($finfo[$i]->type==10 && ($line[$i]=='0000-00-00' || $line[$i]=='') && !$noEdit)	$str.= "<td>&nbsp;</td>";	// date
						elseif($finfo[$i]->type==10 || $finfo[$i]->type==7)	$str.= "<td>".toOurDate($line[$i])."</td>";
						elseif($finfo[$i]->max_length>5)	$str.= "<td align=left>".(($line[$i] || $noEdit)?$line[$i]:"&nbsp;")."</td>";  
						else $str.= "<td>".(($line[$i] || $noEdit)?$line[$i]:"&nbsp;")."</td>";
					}
				}
		        $str.= "</tr>";
		    }
		    $str.= "</table>";
		}
	    $result->free();
	    $mysqli->close();
		return $str;
}
function makeEntry($params) {
	global $mysqli;
	global $quantAccs;
	if(count($params)==0) return;
	$result = $mysqli->query("LOCK TABLES `Accounts` WRITE, `Entries` WRITE, `Balances` WRITE") or die('Ошибка MySQL: ' . $mysqli->error);
	$query="INSERT INTO `Entries` SET";
	foreach($params as $key=>$value) $query.=" `$key`='$value',";
	$query=substr($query,0,strlen($query)-1);
	$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
	$result = $mysqli->query("SELECT `Дебет`,`Кредит` FROM `Accounts` WHERE `Счет`='{$params['Дебет']}' LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
    $line = $result->fetch_array(MYSQLI_ASSOC);
	$value=-$line['Дебет']+$line['Кредит']-$params['Сумма'];
	if($value<0) { $dbsumm=-$value; $crsumm=0; }
	else { $dbsumm=0; $crsumm=$value; }
//echo $params['Дебет']."<br>".$quantAccs[0]."<br>".$params['Количество']; exit;
	if(in_array(substr($params['Дебет'],0,2),$quantAccs) && ($dbquan=$params['Количество'])>0) {
		$query="UPDATE `Accounts` SET `Дебет`=$dbsumm, `Кредит`=$crsumm, `Количество`=`Количество`+$dbquan WHERE `Счет`='{$params['Дебет']}' LIMIT 1";
	}
	else $query="UPDATE `Accounts` SET `Дебет`=$dbsumm, `Кредит`=$crsumm WHERE `Счет`='{$params['Дебет']}' LIMIT 1";
	$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
	$result = $mysqli->query("SELECT `Дебет`,`Кредит` FROM `Accounts` WHERE `Счет`='{$params['Кредит']}' LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
    $line = $result->fetch_array(MYSQLI_ASSOC);
	$value=-$line['Дебет']+$line['Кредит']+$params['Сумма'];
	if($value<0) { $dbsumm=-$value; $crsumm=0; }
	else { $dbsumm=0; $crsumm=$value; }
	if(in_array(substr($params['Кредит'],0,2),$quantAccs) && ($crquan=$params['Количество'])>0) {
		$query="UPDATE `Accounts` SET `Дебет`=$dbsumm, `Кредит`=$crsumm, `Количество`=`Количество`-$crquan WHERE `Счет`='{$params['Кредит']}' LIMIT 1";
	}
	else $query="UPDATE `Accounts` SET `Дебет`=$dbsumm, `Кредит`=$crsumm WHERE `Счет`='{$params['Кредит']}' LIMIT 1";
	$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
	$today=date("Y-m-d");
	$fd=substr($today,0,8).'01';
	if($params['Дата']<$fd) {
		$cd=$params['Дата'];
		while($cd<$fd) {
			list($year,$month,$day)=explode('-',$cd);
			if(++$month>12) { $year++; $month='01'; }
			if(strlen($month)<2) $month='0'.$month;
			$cd="$year-$month-01";
			if($dbquan>0) $query="UPDATE `Balances` SET `Остаток`=`Остаток`-{$params['Сумма']}, `Количество`=`Количество`+$dbquan WHERE `Дата`='$cd' AND `Счет`='{$params['Дебет']}' LIMIT 1";
			else $query="UPDATE `Balances` SET `Остаток`=`Остаток`-{$params['Сумма']} WHERE `Дата`='$cd' AND `Счет`='{$params['Дебет']}' LIMIT 1";
			$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
			if($mysqli->affected_rows==1) {
				$result = $mysqli->query("SELECT `Остаток` FROM `Balances` WHERE `Дата`='$cd' AND `Счет`='{$params['Дебет']}' LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
			    $line = $result->fetch_array(MYSQLI_NUM);
				if($line[0]==0) $result = $mysqli->query("DELETE FROM `Balances` WHERE `Дата`='$cd' AND `Счет`='{$params['Дебет']}' LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
			}
			else {
				if($dbquan>0) $query="INSERT INTO `Balances` SET `Дата`='$cd', `Счет`='{$params['Дебет']}', `Остаток`=-{$params['Сумма']}, `Количество`=$dbquan";
				else $query="INSERT INTO `Balances` SET `Дата`='$cd', `Счет`='{$params['Дебет']}', `Остаток`=-{$params['Сумма']}";
				$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
			}
			
			if($crquan>0) $query="UPDATE `Balances` SET `Остаток`=`Остаток`+{$params['Сумма']}, `Количество`=`Количество`-$crquan WHERE `Дата`='$cd' AND `Счет`='{$params['Кредит']}' LIMIT 1";
			else $query="UPDATE `Balances` SET `Остаток`=`Остаток`+{$params['Сумма']} WHERE `Дата`='$cd' AND `Счет`='{$params['Кредит']}' LIMIT 1";
			$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
			if($mysqli->affected_rows==1) {
				$result = $mysqli->query("SELECT `Остаток` FROM `Balances` WHERE `Дата`='$cd' AND `Счет`='{$params['Кредит']}' LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
			    $line = $result->fetch_array(MYSQLI_NUM);
				if($line[0]==0) $result = $mysqli->query("DELETE FROM `Balances` WHERE `Дата`='$cd' AND `Счет`='{$params['Кредит']}' LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
			}
			else {
				if($crquan>0) $query="INSERT INTO `Balances` SET `Дата`='$cd', `Счет`='{$params['Кредит']}', `Остаток`={$params['Сумма']}, `Количество`=-$crquan";
				else $query="INSERT INTO `Balances` SET `Дата`='$cd', `Счет`='{$params['Кредит']}', `Остаток`={$params['Сумма']}";
				$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
			}
		}
	}
	$result = $mysqli->query("UNLOCK TABLES") or die('Ошибка MySQL: ' . $mysqli->error);
}
function makeBalances($date='') {
	global $mysqli;
	global $quantAccs;
	if(!isset($mysqli)) {
		$mysqli = new mysqli(SERVER, USERNAME, DBPASS,DBNAME) or die('Ошибка подключения (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
		$set=true;
	}
	$today=date("Y-m-d");
	$accs=[];
	$quants=[];
	if($date=='') {
		$result = $mysqli->query("SELECT MIN(`Дата`) FROM `Entries` LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
	    $line = $result->fetch_array(MYSQLI_NUM);
		$fd=$line[0];
	}
	else {
		$fd=substr($date,0,8).'01';
		$result = $mysqli->query("SELECT `Счет`, `Остаток`,`Количество` FROM `Balances` WHERE `Дата`='$fd'") or die('Ошибка MySQL: ' . $mysqli->error);
	    while($line = $result->fetch_array(MYSQLI_NUM)) {
			$accs['#'.$line[0]]=$line[1];
			$quants['#'.$line[0]]=$line[2];
		}
	}
	while($today>$fd) {
		list($year,$month,$day)=explode('-',$fd);
		if(++$month>12) { $year++; $month='01'; }
		$month=strlen($month)<2?'0'.$month:$month;
		$ed="$year-$month-01";
		$result = $mysqli->query("SELECT `Дебет`,`Кредит`,`Сумма`,`Количество` FROM `Entries` WHERE `Дата`>='$fd' AND `Дата`<'$ed'") or die('Ошибка MySQL: ' . $mysqli->error);
	    while($line = $result->fetch_array(MYSQLI_NUM)) {
			$accs['#'.$line[0]]-=$line[2]; 
			if(in_array(substr($line[0],0,2),$quantAccs)) $quants['#'.$line[0]]+=$line[3];
			$accs['#'.$line[1]]+=$line[2]; 
			if(in_array(substr($line[1],0,2),$quantAccs)) $quants['#'.$line[1]]-=$line[3];
		}
		$result = $mysqli->query("DELETE FROM `Balances` WHERE `Дата`='$ed'") or die('Ошибка MySQL: ' . $mysqli->error);
		if($ed<=$today) foreach($accs as $key=>$value) {
			$key=substr($key,1);
			$result = $mysqli->query("INSERT INTO `Balances` SET `Дата`='$ed', `Счет`='$key', `Остаток`='$value',`Количество`='{$quants[$key]}'") or die('Ошибка MySQL: ' . $mysqli->error);
		}
		elseif($date=='') {
			$result = $mysqli->query("SELECT `Счет`,`Дебет`,`Кредит`,`Количество` FROM `Accounts` WHERE `Дебет`!=0 OR `Кредит`!=0") or die('Ошибка MySQL: ' . $mysqli->error);
		    while($line = $result->fetch_array(MYSQLI_NUM)) {
				if(abs($line[2]-$line[1]-$accs['#'.$line[0]])>0.005) echo "Ошибка: Счет {$line[0]}=".($line[2]-$line[1]).", должно быть ".$accs['#'.$line[0]].", разница ".($line[2]-$line[1]-$accs[$line[0]])."<br>";
				else unset($accs['#'.$line[0]]);
				if($line[3]!=$quants['#'.$line[0]] && $quants['#'.$line[0]]!=0) echo "Ошибка количества: Счет {$line[0]} -> {$line[3]}, должно быть ".$quants['#'.$line[0]]."<br>";
				else unset($quants['#'.$line[0]]);
			}
			if(count($accs)>0) foreach($accs as $key=>$value) if(abs($value)>0.005) echo "Ошибка: Счет ".substr($key,1)."=0, должно быть $value<br>";
			if(count($quants)>0) foreach($quants as $key=>$value) if($value>0) echo "Ошибка количества: Счет ".substr($key,1)." -> 0, должно быть $value<br>";
		}
		$fd=$ed;
	}
//    $result->free();
	if($set) $mysqli->close();
}
function Diagnostics($date='') {
	global $mysqli;
	global $quantAccs;
	if(!isset($mysqli)) {
		$mysqli = new mysqli(SERVER, USERNAME, DBPASS,DBNAME) or die('Ошибка подключения (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
		$set=true;
	}
	$today=date("Y-m-d");
	$accs=[];
	$quants=[];
	if($date=='') {
		$result = $mysqli->query("SELECT MIN(`Дата`) FROM `Entries` LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
	    $line = $result->fetch_array(MYSQLI_NUM);
		$fd=$line[0];
	}
	else {
		$fd=substr($date,0,8).'01';
		$result = $mysqli->query("SELECT `Счет`, `Остаток` FROM `Balances` WHERE `Дата`='$fd'") or die('Ошибка MySQL: ' . $mysqli->error);
	    while($line = $result->fetch_array(MYSQLI_NUM)) {
			$accs['#'.$line[0]]=$line[1];
		}
	}
	while($today>$fd) {
		list($year,$month,$day)=explode('-',$fd);
		if(++$month>12) { $year++; $month='01'; }
		if(strlen($month)<2)$month='0'.$month;
		$ed="$year-$month-01";
		$result = $mysqli->query("SELECT `Дебет`,`Кредит`,`Сумма`,`Количество` FROM `Entries` WHERE `Дата`>='$fd' AND `Дата`<'$ed'") or die('Ошибка MySQL: ' . $mysqli->error);
	    while($line = $result->fetch_array(MYSQLI_NUM)) {
			$accs['#'.$line[0]]-=$line[2];
			if(in_array(substr($line[0],0,2),$quantAccs)) $quants['#'.$line[0]]+=$line[3];
			$accs['#'.$line[1]]+=$line[2];
			if(in_array(substr($line[1],0,2),$quantAccs)) $quants['#'.$line[1]]-=$line[3];
		}
		if($ed<=$today) {
			$result = $mysqli->query("SELECT `Счет`, `Остаток`,`Количество` FROM `Balances` WHERE `Дата`='$ed'") or die('Ошибка MySQL: ' . $mysqli->error);
		    while($line = $result->fetch_array(MYSQLI_NUM)) {
				if(abs($line[1]-$accs['#'.$line[0]])>0.005) echo "Ошибка на дату $ed: Счет {$line[0]}={$line[1]}, должно быть ".$accs['#'.$line[0]].", разница ".($line[1]-$accs['#'.$line[0]])."<br>";
			}
/*			foreach($accs as $key=>$value) {
				$key=substr($key,1);
				$result = $mysqli->query("SELECT `Счет`, `Остаток`,`Количество` FROM `Balances` WHERE `Дата`='$ed' AND `Счет`='$key' LIMIT 1") or die('Ошибка MySQL: ' . $mysqli->error);
				$line = $result->fetch_array(MYSQLI_NUM);
				if(abs($line[1]-$accs['#'.$line[0]])>0.005) echo "Ошибка на дату $ed: Счет {$line[0]}={$line[1]}, должно быть ".$accs['#'.$line[0]].", разница ".($line[1]-$accs['#'.$line[0]])."<br>";
			} */
		}
		else {
			$result = $mysqli->query("SELECT `Счет`,`Дебет`,`Кредит`,`Количество` FROM `Accounts` WHERE `Дебет`!=0 OR `Кредит`!=0") or die('Ошибка MySQL: ' . $mysqli->error);
		    while($line = $result->fetch_array(MYSQLI_NUM)) {
				if(abs($line[2]-$line[1]-$accs['#'.$line[0]])>0.005) echo "Ошибка: Счет {$line[0]}=".($line[2]-$line[1]).", должно быть ".$accs['#'.$line[0]].", разница ".($line[2]-$line[1]-$accs['#'.$line[0]])."<br>";
				else unset($accs['#'.$line[0]]);
				if($line[3]!=$quants['#'.$line[0]] && $quants['#'.$line[0]]!=0) echo "Ошибка количества: Счет {$line[0]} -> {$line[3]}, должно быть ".$quants['#'.$line[0]]."<br>";
				else unset($quants['#'.$line[0]]);
			}
			if(count($accs)>0) foreach($accs as $key=>$value) if(abs($value)>0.005) echo "Ошибка: Счет ".substr($key,1)."=0, должно быть $value<br>";
			if(count($quants)>0) foreach($quants as $key=>$value) if($value>0) echo "Ошибка количества: Счет ".substr($key,1)." -> 0, должно быть $value<br>";
		}
		$fd=$ed;
	}
	echo show_all("SELECT COUNT(`Счет`) AS 'Счетов', SUM(`Дебет`) AS 'Сумма по дебету', SUM(`Кредит`) AS 'Сумма по кредиту' FROM `Accounts`",true)."<br>";
	echo show_all("SELECT COUNT(`Номер`) AS 'Проводок', MIN(`Дата`) AS 'Начальная дата', MAX(`Дата`) AS 'Конечная дата' FROM `Entries`",true)."<br>";
	echo show_all("SELECT COUNT(`Счет`) AS 'Товарных позиций', SUM(`Количество`) AS 'Количество', SUM(`Дебет`)-SUM(`Кредит`) AS 'На сумму' FROM `Accounts` WHERE `Счет` LIKE '41.%'",true)."<br>";
	echo show_all("SELECT COUNT(`Товар`) AS 'Строк в прих. накладной', SUM(`Количество`) AS 'Количество', SUM(`Сумма`) AS 'На сумму'  FROM `Invoice`",true)."<br>";
	echo show_all("SELECT COUNT(`Номер`) AS 'Шаблонов проводок' FROM `Stencil`",true)."<br>";
//	echo show_all("SELECT SUM(`Остаток`) AS 'Итог', COUNT(`Остаток`) AS 'Записей' FROM `Balances`",true);
    $result->free();
	if($set) $mysqli->close();
}
function liveVidgets($flag) {
	global $mysqli;
	global $reply;
	global $userdata;
	if(!isset($mysqli)) {
		$mysqli = new mysqli(SERVER, USERNAME, DBPASS,DBNAME) or die('Ошибка подключения (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
		$set=true;
	}
	if($flag & ACCOUNTS) {
		$query = "SELECT COUNT(`Счет`), SUM(`Дебет`), SUM(`Кредит`) FROM `Accounts`";
		$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
	    $line = $result->fetch_array(MYSQLI_NUM);
		$line[1]=number_format($line[1],2,'.',DLMTR);
		$line[2]=number_format($line[2],2,'.',DLMTR);
		if($flag & INITIAL)	$reply['vidgetAcc']= "<table align=left><tr><td>Счетов:</td><td>{$line[0]}</td></tr><tr><td>Сумма дебет:</td><td align=right>{$line[1]}</td></tr><tr><td>Сумма кредит:</td><td align=right>{$line[2]}</td></tr></table>";
		else $reply['vidgetAcc']=implode('|',$line);
	}
	if($flag & ENTRIES) {
		$query = "SELECT COUNT(`Номер`), MIN(`Дата`), MAX(`Дата`) FROM `Entries`";
		$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
	    $line = $result->fetch_array(MYSQLI_NUM);
		$line[1]=toOurDate($line[1]);
		$line[2]=toOurDate($line[2]);
		if($flag & INITIAL)	$reply['vidgetEnt']= "<table align=left><tr><td>Проводок:</td><td>{$line[0]}</td></tr><tr><td>Нач. дата:</td><td>{$line[1]}</td></tr><tr><td>Кон. дата:</td><td>{$line[2]}</td></tr></table>";
		else $reply['vidgetEnt']=implode('|',$line);
	}
	if($flag & GOODS) {
		$query = "SELECT COUNT(`Счет`), SUM(`Количество`), SUM(`Дебет`)-SUM(`Кредит`) FROM `Accounts` WHERE `Счет` LIKE '41.%'";
		$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
	    $line = $result->fetch_array(MYSQLI_NUM);
		$line[2]=number_format($line[2],2,'.',DLMTR);
		if($flag & INITIAL)	$reply['vidgetGds']= "<table align=left><tr><td>Позиций:</td><td>{$line[0]}</td></tr><tr><td>Количество:</td><td>{$line[1]}</td></tr><tr><td>На сумму:</td><td align=right>{$line[2]}</td></tr></table>";
		else $reply['vidgetGds']=implode('|',$line);
	}
	if($flag & INVOICE) {
		$query = "SELECT COUNT(`Товар`), SUM(`Количество`), SUM(`Сумма`)  FROM `Invoice`";
		$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
	    $line = $result->fetch_array(MYSQLI_NUM);
		$line[2]=number_format($line[2],2,'.',DLMTR);
		if($flag & INITIAL)	$reply['vidgetInv']= "<table align=left><tr><td>Позиций:</td><td>{$line[0]}</td></tr><tr><td>Количество:</td><td>{$line[1]}</td></tr><tr><td>На сумму:</td><td align=right>{$line[2]}</td></tr></table>";
		else $reply['vidgetInv']=implode('|',$line);
	}
	if($flag & STENCIL) {
		$query = "SELECT COUNT(`Номер`) FROM `Stencil`";
		$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
	    $line = $result->fetch_array(MYSQLI_NUM);
		if($flag & INITIAL)	$reply['vidgetStn']= "<table align=left><tr><td>Шаблонов:</td><td>{$line[0]}</td></tr></table>";
		else $reply['vidgetStn']=implode('|',$line);
	}
	if($flag & CTRAGENTS) {
		$query = "SELECT COUNT(`Счет`), SUM(`Дебет`), SUM(`Кредит`) FROM `Accounts` WHERE `Счет` LIKE '60.%' OR `Счет` LIKE '62.%'";
		$result = $mysqli->query($query) or die('Ошибка MySQL: ' . $mysqli->error);
	    $line = $result->fetch_array(MYSQLI_NUM);
		$line[1]=number_format($line[1],2,'.',DLMTR);
		$line[2]=number_format($line[2],2,'.',DLMTR);
		if($flag & INITIAL)	$reply['vidgetCtr']= "<table align=left><tr><td>Контрагентов:</td><td>{$line[0]}</td></tr><tr><td>Сумма дебет:</td><td align=right>{$line[1]}</td></tr><tr><td>Сумма кредит:</td><td align=right>{$line[2]}</td></tr></table>";
		else $reply['vidgetCtr']=implode('|',$line);
	}
	if($flag & INITIAL) {
		for($i=3;$i<count($userdata);$i++) {
			list($key,$value)=explode('=',$userdata[$i]);
			$reply['_'.$key]=$value;
		}
	}
    $result->free();
	if($set) $mysqli->close();
}
function formatStr($str,$dec=false,$empty='') {
	if($str=='' || $str=='0000-00-00' || $str=='0.00') $str=$empty;
	elseif(preg_match("/^\d{4}-\d{2}-\d{2}/",$str)) $str=toOurDate($str);
	elseif($dec) $str=number_format($str,2,'.',DLMTR);
	return $str;
}
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
	  <meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
	  <?PHP
	  	preg_match("/(\w+)\.\w+\.\w{2,3}$/",$_SERVER['SERVER_NAME'],$matches);
		echo "<title>".ucfirst($matches[1]).".LiteAcc</title>";
	  ?>
	  <meta name='robots' content='noindex' />
	  <meta name="author" content="Сергей Гуторенко">
	  <link href="uchet.css?<?echo(md5_file($_SERVER['DOCUMENT_ROOT'].'/uchet.css')) ?>" rel="stylesheet" type="text/css" >
	  <link rel="icon" href="/favicon.ico" type="image/x-icon">
	  <script language="JavaScript" type="text/javascript" src="/uchet.js?<?echo(md5_file($_SERVER['DOCUMENT_ROOT'].'/uchet.js')) ?>">  </script>
	</head>
<body onload='fillVidgets()'>
	<div class='vidget' name='Accounts' rname='Счета' width='650' height='600'></div>
	<div class='vidget' name='Entries' rname='Проводки' width='850' height='520'></div>
	<div class='vidget' name='Goods' rname='Товары' width='850' height='600'></div>
	<div class='vidget' name='About' rname='О Приложении' width='800' height='600'>Описание приложения,<br>Руководство пользователя,<br>"Кратчайший курс" бухучета</div>
	<div class='vidget' name='Stencil' rname='Шаблоны проводок' width='700' height='480'></div>
	<div class='vidget' name='Statement' rname='Выписка' width='850' height='600'>Движение средств по любому счету за заданный период</div>
	<div class='vidget' name='newEntry' rname='Новая проводка' width='510' height='330'>Создать новую бухгалтерскую проводку</div>
	<div class='vidget' name='Invoice' rname='Приход' width='850' height='600'></div>
	<div class='vidget' name='Sale' rname='Продажа' width='510' height='345'>Отразить в учете продажу товара</div>
	<div class='vidget' name='Kudir' rname='КУДиР' width='800' height='600'>Сформировать книгу учета доходов и расходов</div>
	<div class='vidget' name='Calendar' rname='Календарь' width='200' height='266'></div>
	<div class='vidget' name='Calculator' rname='Калькулятор' width='310' height='400'></div>
	<div class='vidget' name='CounterAgents' rname='Контрагенты' width='725' height='500'></div>
	<div class='vidget' name='LoadBank' rname='Загрузить банк' width='950' height='500'>Загрузить выписку банка в формате 1С</div>
<?php if($extended) { ?>
<?PHP } ?>
</body>
</html>

