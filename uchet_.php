<?php
if($headers['X-Type']=='XMLHttpRequest' && count($_GET)>0 || isset($_GET['LoadBank']) && isset($_FILES['userfile'])) {
	if(isset($_GET['Accounts'])) {
		if(count($notShownAccounts)==0) { $where1='1'; $where2='0'; }
		else {
			$where1="`����` NOT LIKE '".implode("%' AND `����` NOT LIKE '",$notShownAccounts)."%'";
			$where2="`����` LIKE '".implode("%' OR `����` LIKE '",$notShownAccounts)."%'";
		}
		echo show_all("SELECT `����`,`������������`,`�����`,`������`,`��` FROM `Accounts` WHERE $where1 UNION SELECT `����`,`������������`,SUM(`�����`),SUM(`������`),`��` FROM `Accounts` WHERE $where2 GROUP BY LEFT(`����`,2) ORDER BY `����`");
	}
	elseif(isset($_GET['Entries'])) {
		if($_GET['Date']) $query="SELECT * FROM `Entries` WHERE `����`='".toSQLDate($_GET['Date'])."' ORDER BY `�����` DESC";
		else $query="SELECT * FROM `Entries` ORDER BY `����` DESC, `�����` DESC LIMIT ".ELINES;
		echo show_all($query);
	}
	elseif(isset($_GET['Stencil'])) {
		if(isset($_GET['Debet'])) echo show_all("SELECT * FROM `Stencil` WHERE `�����`='{$_GET['Debet']}' ORDER BY `�����`,`������`,`����������`");
		elseif(isset($_GET['Credit'])) echo show_all("SELECT * FROM `Stencil` WHERE `������`='{$_GET['Credit']}' ORDER BY `�����`,`������`,`����������`");
		else echo show_all("SELECT * FROM `Stencil` ORDER BY `�����`,`������`,`����������`");
	}
	elseif(isset($_GET['ShowTables'])) {
		echo "������� ��:<br>".show_all("SHOW TABLES",true)."<br>";
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
//		echo show_all("SELECT * FROM `Balances` ORDER BY `����`, `����`");
	}
	elseif(isset($_GET['Start'])) {											// ������������� ����������� ��������
		$mysqli = new mysqli(SERVER, USERNAME, DBPASS,DBNAME) or die('������ ����������� (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
		$result=$mysqli->query("SELECT MAX(`����`) FROM `Balances` LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
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
		echo "<tr><td style='font-weight:bold'>����</td><td><input type='text' name='�����' format='varchar(10)' value='$Acc'></td></tr>";
		echo "<tr><td style='font-weight:bold'>�������� ��������</td><td><input type='checkbox' name='�������' format='checkbox' $checked></td></tr>";
		echo "<tr><td style='font-weight:bold'>��������� ����</td><td><input type='text' name='������' format='date' value='$First'></td></tr>";
		echo "<tr><td style='font-weight:bold'>�������� ����</td><td><input type='text' name='������' format='date' value='$End'></td></tr>";
		echo "</table>";
		echo "<button onclick='statement()'>�������</button>";
		if($Acc && $First && $End) {
			$quantum=in_array(substr($Acc,0,2),$quantAccs);
			$mysqli = new mysqli(SERVER, USERNAME, DBPASS,DBNAME) or die('������ ����������� (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
			$result = $mysqli->query("SELECT `������������`, `��` FROM `Accounts` WHERE `����`='$Acc' LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
		    $line = $result->fetch_array(MYSQLI_NUM);
			$ap=$line[1];
			echo "<table cellpadding=4 border=0 style='text-align:left'>";
			echo "<caption>������� �� ����� $Acc \"{$line[0]}\"".($checked?", ������� ��������":"")." �� ������ � $First �� $End</caption>";
			$First=toSQLDate($First); $End=toSQLDate($End);
			$fd=substr($First,0,8).'01';
			if($checked) $query="SELECT SUM(`�������`),SUM(`����������`) FROM `Balances` WHERE `����`='$fd' AND (`����`='$Acc' OR `����` LIKE '{$Acc}.%') LIMIT 1";
			else $query="SELECT `�������`,`����������` FROM `Balances` WHERE `����`='$fd' AND `����`='$Acc' LIMIT 1";
			$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
		    $line = $result->fetch_array(MYSQLI_NUM);
			$balance=$line[0];
			$quant=$line[1];
			if($checked) $query="SELECT SUM(`�����`),SUM(`����������`) FROM `Entries` WHERE `����`>='$fd' AND `����`<'$First' AND (`�����`='$Acc' OR `�����` LIKE '{$Acc}.%') LIMIT 1";
			else $query="SELECT SUM(`�����`),SUM(`����������`) FROM `Entries` WHERE `����`>='$fd' AND `����`<'$First' AND `�����`='$Acc' LIMIT 1";
			$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
		    $line = $result->fetch_array(MYSQLI_NUM);
			$balance-=$line[0];
			$quant+=$line[1];
			if($checked) $query="SELECT SUM(`�����`),SUM(`����������`) FROM `Entries` WHERE `����`>='$fd' AND `����`<'$First' AND (`������`='$Acc' OR `������` LIKE '{$Acc}.%') LIMIT 1";
			else $query="SELECT SUM(`�����`),SUM(`����������`) FROM `Entries` WHERE `����`>='$fd' AND `����`<'$First' AND `������`='$Acc' LIMIT 1";
			$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
		    $line = $result->fetch_array(MYSQLI_NUM);
			$balance+=$line[0];
			$quant-=$line[1];
			$debet=0;
			$credit=0;
			echo "<tr class='noHL'><td>��������</td>";
			if($balance<0 || $balance==0 && $ap=='�') echo "<td class='decimal'".($ap=='�'?" style='color:red'":"").">".number_format(-$balance,2,'.',DLMTR)."</td><td>&nbsp;";
			else echo "<td>&nbsp;</td><td class='decimal'".($ap=='�'?" style='color:red'":"").">".number_format($balance,2,'.',DLMTR);
			if($quantum) echo "</td><td>���:</td><td>$quant</td></tr>";
			else echo "</td><td colspan=2>&nbsp;</td></tr>";
			echo "<tr class='bord'><th>����</th><th>�����</th><th>������</th><th>����</th>".($quantum?"<th>���</th>":"").($checked?"<th>�����</th>":"")."<th>����������</th></tr>";
			if($checked) $query="SELECT `����`,`�����`,`������`,`�����`,`����������`,`����������` FROM `Entries` WHERE `����`>='$First' AND `����`<='$End' AND (`�����`='$Acc' OR `�����` LIKE '{$Acc}.%' OR `������`='$Acc' OR `������` LIKE '{$Acc}.%') ORDER BY `����`";
			else $query="SELECT `����`,`�����`,`������`,`�����`,`����������`,`����������` FROM `Entries` WHERE `����`>='$First' AND `����`<='$End' AND (`�����`='$Acc' OR `������`='$Acc') ORDER BY `����`";
			$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
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
			echo "<tr class='noHL'><td>�������</td><td class='decimal'>".number_format($debet,2,'.',DLMTR)."</td><td class='decimal'>".number_format($credit,2,'.',DLMTR)."</td><td colspan=2>&nbsp;</td></tr>";
			echo "<tr class='noHL'><td>���������</td>";
			$balance+=-$debet+$credit;
			if($balance<0 || $balance==0 && $ap=='�') echo "<td class='decimal'".($ap=='�'?" style='color:red'":"").">".number_format(-$balance,2,'.',DLMTR)."</td><td>&nbsp;";
			else echo "<td>&nbsp;</td><td class='decimal'".($ap=='�'?" style='color:red'":"").">".number_format($balance,2,'.',DLMTR);
			if($quantum) echo "</td><td>���:</td><td>$quant</td></tr>";
			else echo "</td><td colspan=2>&nbsp;</td></tr>";
			echo "</table>";
		}
	}
	elseif(isset($_GET['Goods'])) {
		$query="SELECT `����`,`������������`,`�����`,`����������`,`����_��`,`����_����`,`���`  FROM `Accounts` AS `Goods` WHERE `����` LIKE '41.%'";
		foreach($columnAliases['Goods'] as $key=>$value) $query=str_replace($key,$value,$query);
		echo show_all($query);
	}
	elseif(isset($_GET['Edit'])) {										// ������������� ������
		if(isset($Aliases[$_GET['name']])) $name=$Aliases[$_GET['name']]; else $name=$_GET['name'];
		if(!isset($nonEditable[$name])) die("������� $name �� �������");
		$mysqli = new mysqli(SERVER, USERNAME, DBPASS,DBNAME) or die('������ ����������� (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
		$finfo_=get_field_info($name);
		if($name=='Accounts') {
			$query = "SELECT `�����` FROM `Entries` WHERE `�����`='{$_GET['value']}' OR `������`='{$_GET['value']}'  LIMIT 1";
			$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
			if($result->num_rows>0) $no_delete='�� ����� ����� ���� ��������, ������� ������';
		}
		$query = "SELECT * FROM `$name` WHERE `{$_GET['field']}`='{$_GET['value']}' LIMIT 1";
		$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
		if($result->num_rows==1) {
		    $line = $result->fetch_array(MYSQLI_BOTH);
		    $finfo = $result->fetch_fields();
			if($name=='Entries' && in_array(substr($line['������'],0,2),$quantAccs)) {
				$res = $mysqli->query("SELECT `����������` FROM `Accounts` WHERE `����`='{$line['������']}' LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
				$qline=$res->fetch_array(MYSQLI_NUM);
				$quantity=$qline[0]+$line['����������'];
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
					if($_GET['name']=='LoadBank' && ($finfo[$i]->name=='�����' || $finfo[$i]->name=='������') && substr($line[$i],0,2)=='51') {
						echo "<tr><td style='font-weight:bold'>{$finfo[$i]->name}</td><td>{$line[$i]}</td></tr>";
						$no_delete="������ ������� ������ �� ���������� �������";
					}
					else {
						echo "<tr><td style='font-weight:bold'>{$finfo[$i]->name}:</td><td><input type='text' name='{$finfo[$i]->name}' value='{$line[$i]}' format='{$finfo_[$finfo[$i]->name]}'";
						if($quantity>=0 && $finfo[$i]->name=='������') echo " quant=$quantity><span class=\"remains\">������� $quantity ������</span></td></tr>";
						else echo "></td></tr>";
					}
				}
			}
			echo "</table>";
			echo "<button onclick='saveFromEdit(\"{$_GET['name']}\",\"{$_GET['field']}\",\"{$_GET['value']}\")'>���������</button>";
			echo "<button ".($no_delete?"disabled=disabled title='$no_delete'":"")." onclick='Confirm(\"������� ������. �� �������?\",\"deleteString\",\"{$_GET['name']}\",\"{$_GET['field']}\",\"{$_GET['value']}\")'>�������</button>";
		}
		else echo "������: ������ {$_GET['field']}={$_GET['value']} � ������� {$_GET['name']} �� �������";
	}
	elseif(isset($_GET['Save'])) {										// ��������� ������ ����� ��������������
		if(isset($Aliases[$_GET['name']])) $name=$Aliases[$_GET['name']]; else $name=$_GET['name'];
		if(!isset($nonEditable[$name])) die("������� $name �� �������");
		$mysqli = new mysqli(SERVER, USERNAME, DBPASS,DBNAME) or die('������ ����������� (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
		$finfo=get_field_info($name);
		if($name=='Entries') {
			$result = $mysqli->query("LOCK TABLES `Entries` WRITE, `Accounts` WRITE, `Balances` WRITE") or die('������ MySQL: ' . $mysqli->error);
			$result = $mysqli->query("SELECT * FROM `Entries` WHERE `{$_GET['field']}`='{$_GET['value']}' LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
		    $paramsOld = $result->fetch_array(MYSQLI_ASSOC);
		}
		else {
			$result = $mysqli->query("LOCK TABLES `$name` WRITE") or die('������ MySQL: ' . $mysqli->error);
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
		$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
		if($name=='LoadBank') {
			$result = $mysqli->query("SELECT `����`,`�����`,`�����������������`,`����������`,`��������` FROM `LoadBank` WHERE `{$_GET['field']}`='{$_GET['value']}' LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
		    $paramsTemp = $result->fetch_array(MYSQLI_ASSOC);
			$paramsTemp['����������']=apply_stencil($paramsTemp['����������'],$paramsTemp);
			$query="UPDATE `LoadBank` SET";
			foreach($paramsTemp as $key=>$value) $query.=" `$key`='$value',";
			$query=substr($query,0,strlen($query)-1)." WHERE `{$_GET['field']}`='{$_GET['value']}' LIMIT 1";
			$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
		}
		$columns=trim(preg_replace("/<.+>/",'',$mysqli->real_escape_string(iconv('UTF-8', 'windows-1251', $_POST['columns']))));
		if($_GET['name']!=$name && isset($columnAliases[$_GET['name']])) {
			foreach($columnAliases[$_GET['name']] as $key=>$value)
			$columns=str_replace($key,$value,$columns);
		}
		$query="SELECT $columns FROM `$name` WHERE `{$_GET['field']}`='{$_GET['value']}'";
		$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
	    $finfo = $result->fetch_fields();
	    $line = $result->fetch_array(MYSQLI_NUM);
        for($i=0; $i<count($line); $i++) {
			$line[$i]=formatStr($line[$i],$finfo[$i]->type==246,"&nbsp;");
		}
		$reply['reply']= implode('|',$line);
		if($name=='Entries') {
			// ������� ������� ������ ��������
			$result = $mysqli->query("SELECT `�����`,`������` FROM `Accounts` WHERE `����`='{$paramsOld['�����']}' LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
		    $line = $result->fetch_array(MYSQLI_ASSOC);
			$value=-$line['�����']+$line['������']+$paramsOld['�����'];
			if($value<0) { $dbsumm=-$value; $crsumm=0; }
			else { $dbsumm=0; $crsumm=$value; }
			if(in_array(substr($paramsOld['�����'],0,2),$quantAccs) && ($dbquan=$paramsOld['����������'])>0) {
				$query="UPDATE `Accounts` SET `�����`=$dbsumm, `������`=$crsumm, `����������`=`����������`-$dbquan WHERE `����`='{$paramsOld['�����']}' LIMIT 1";
			}
			else $query="UPDATE `Accounts` SET `�����`=$dbsumm, `������`=$crsumm WHERE `����`='{$paramsOld['�����']}' LIMIT 1";
			$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
			$result = $mysqli->query("SELECT `�����`,`������` FROM `Accounts` WHERE `����`='{$paramsOld['������']}' LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
		    $line = $result->fetch_array(MYSQLI_ASSOC);
			$value=-$line['�����']+$line['������']-$paramsOld['�����'];
			if($value<0) { $dbsumm=-$value; $crsumm=0; }
			else { $dbsumm=0; $crsumm=$value; }
			if(in_array(substr($paramsOld['������'],0,2),$quantAccs) && ($crquan=$paramsOld['����������'])>0) {
				$query="UPDATE `Accounts` SET `�����`=$dbsumm, `������`=$crsumm, `����������`=`����������`+$crquan WHERE `����`='{$paramsOld['������']}' LIMIT 1";
			}
			else $query="UPDATE `Accounts` SET `�����`=$dbsumm, `������`=$crsumm WHERE `����`='{$paramsOld['������']}' LIMIT 1";
			$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
			$today=date("Y-m-d");
			$fd=substr($today,0,8).'01';
			if($paramsOld['����']<$fd) {
				$cd=$paramsOld['����'];
				while($cd<$fd) {
					list($year,$month,$day)=explode('-',$cd);
					if(++$month>12) { $year++; $month='01'; }
					$month=strlen($month)<2?'0'.$month:$month;
					$cd="$year-$month-01";
					if($dbquan>0) $query="UPDATE `Balances` SET `�������`=`�������`+{$paramsOld['�����']}, `����������`=`����������`-$dbquan WHERE `����`='$cd' AND `����`='{$paramsOld['�����']}' LIMIT 1";
					else $query="UPDATE `Balances` SET `�������`=`�������`+{$paramsOld['�����']} WHERE `����`='$cd' AND `����`='{$paramsOld['�����']}' LIMIT 1";
					$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
					if($mysqli->affected_rows==1) {
						$result = $mysqli->query("SELECT `�������` FROM `Balances` WHERE `����`='$cd' AND `����`='{$paramsOld['�����']}' LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
					    $line = $result->fetch_array(MYSQLI_NUM);
						if($line[0]==0) $result = $mysqli->query("DELETE FROM `Balances` WHERE `����`='$cd' AND `����`='{$paramsOld['�����']}' LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
					}
					else {
						if($dbquan>0) $query="INSERT INTO `Balances` SET `����`='$cd', `����`='{$paramsOld['�����']}', `�������`={$paramsOld['�����']}, `����������`=-$dbquan";
						else $query="INSERT INTO `Balances` SET `����`='$cd', `����`='{$paramsOld['�����']}', `�������`={$paramsOld['�����']}";
						$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
					}
					if($crquan>0) $query="UPDATE `Balances` SET `�������`=`�������`-{$paramsOld['�����']}, `����������`=`����������`+$crquan WHERE `����`='$cd' AND `����`='{$paramsOld['������']}' LIMIT 1";
					else $query="UPDATE `Balances` SET `�������`=`�������`-{$paramsOld['�����']} WHERE `����`='$cd' AND `����`='{$paramsOld['������']}' LIMIT 1";
					$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
					if($mysqli->affected_rows==1) {
						$result = $mysqli->query("SELECT `�������` FROM `Balances` WHERE `����`='$cd' AND `����`='{$paramsOld['������']}' LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
					    $line = $result->fetch_array(MYSQLI_NUM);
						if($line[0]==0) $result = $mysqli->query("DELETE FROM `Balances` WHERE `����`='$cd' AND `����`='{$paramsOld['������']}' LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
					}
					else {
						if($crquan>0) $query="INSERT INTO `Balances` SET `����`='$cd', `����`='{$paramsOld['������']}', `�������`=-{$paramsOld['�����']}, `����������`=$crquan";
						else $query="INSERT INTO `Balances` SET `����`='$cd', `����`='{$paramsOld['������']}', `�������`=-{$paramsOld['�����']}";
						$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
					}
				}
			}
			// ����� ������ ���������� ��������
			unset($dbquan); unset($crquan);
			$result = $mysqli->query("SELECT `�����`,`������` FROM `Accounts` WHERE `����`='{$paramsNew['�����']}' LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
		    $line = $result->fetch_array(MYSQLI_ASSOC);
			$value=-$line['�����']+$line['������']-$paramsNew['�����'];
			if($value<0) { $dbsumm=-$value; $crsumm=0; }
			else { $dbsumm=0; $crsumm=$value; }
			if(in_array(substr($paramsNew['�����'],0,2),$quantAccs) && ($dbquan=$paramsNew['����������'])>0) {
				$query="UPDATE `Accounts` SET `�����`=$dbsumm, `������`=$crsumm, `����������`=`����������`+$dbquan WHERE `����`='{$paramsNew['�����']}' LIMIT 1";
			}
			else $query="UPDATE `Accounts` SET `�����`=$dbsumm, `������`=$crsumm WHERE `����`='{$paramsNew['�����']}' LIMIT 1";
			$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
			$result = $mysqli->query("SELECT `�����`,`������` FROM `Accounts` WHERE `����`='{$paramsNew['������']}' LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
		    $line = $result->fetch_array(MYSQLI_ASSOC);
			$value=-$line['�����']+$line['������']+$paramsNew['�����'];
			if($value<0) { $dbsumm=-$value; $crsumm=0; }
			else { $dbsumm=0; $crsumm=$value; }
			if(in_array(substr($paramsNew['������'],0,2),$quantAccs) && ($crquan=$paramsNew['����������'])>0) {
				$query="UPDATE `Accounts` SET `�����`=$dbsumm, `������`=$crsumm, `����������`=`����������`-$crquan WHERE `����`='{$paramsNew['������']}' LIMIT 1";
			}
			else $query="UPDATE `Accounts` SET `�����`=$dbsumm, `������`=$crsumm WHERE `����`='{$paramsNew['������']}' LIMIT 1";
			$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
			if($paramsNew['����']<$fd) {
				$cd=$paramsNew['����'];
				while($cd<$fd) {
					list($year,$month,$day)=explode('-',$cd);
					if(++$month>12) { $year++; $month='01'; }
					$month=strlen($month)<2?'0'.$month:$month;
					$cd="$year-$month-01";
					if($dbquan>0) $query="UPDATE `Balances` SET `�������`=`�������`-{$paramsNew['�����']}, `����������`=`����������`+$dbquan WHERE `����`='$cd' AND `����`='{$paramsNew['�����']}' LIMIT 1";
					else $query="UPDATE `Balances` SET `�������`=`�������`-{$paramsNew['�����']} WHERE `����`='$cd' AND `����`='{$paramsNew['�����']}' LIMIT 1";
					$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
					if($mysqli->affected_rows==1) {
						$result = $mysqli->query("SELECT `�������` FROM `Balances` WHERE `����`='$cd' AND `����`='{$paramsNew['�����']}' LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
					    $line = $result->fetch_array(MYSQLI_NUM);
						if($line[0]==0) $result = $mysqli->query("DELETE FROM `Balances` WHERE `����`='$cd' AND `����`='{$paramsNew['�����']}' LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
					}
					else {
						if($dbquan>0) $query="INSERT INTO `Balances` SET `����`='$cd', `����`='{$paramsNew['�����']}', `�������`=-{$paramsNew['�����']}, `����������`=$dbquan";
						else $query="INSERT INTO `Balances` SET `����`='$cd', `����`='{$paramsNew['�����']}', `�������`=-{$paramsNew['�����']}";
						$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
					}
					if($crquan>0) $query="UPDATE `Balances` SET `�������`=`�������`+{$paramsNew['�����']}, `����������`=`����������`-$crquan WHERE `����`='$cd' AND `����`='{$paramsNew['������']}' LIMIT 1";
					else $query="UPDATE `Balances` SET `�������`=`�������`+{$paramsNew['�����']} WHERE `����`='$cd' AND `����`='{$paramsNew['������']}' LIMIT 1";
					$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
					if($mysqli->affected_rows==1) {
						$result = $mysqli->query("SELECT `�������` FROM `Balances` WHERE `����`='$cd' AND `����`='{$paramsNew['������']}' LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
					    $line = $result->fetch_array(MYSQLI_NUM);
						if($line[0]==0) $result = $mysqli->query("DELETE FROM `Balances` WHERE `����`='$cd' AND `����`='{$paramsNew['������']}' LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
					}
					else {
						if($crquan>0) $query="INSERT INTO `Balances` SET `����`='$cd', `����`='{$paramsNew['������']}', `�������`={$paramsNew['�����']}, `����������`=-$crquan";
						else $query="INSERT INTO `Balances` SET `����`='$cd', `����`='{$paramsNew['������']}', `�������`={$paramsNew['�����']}";
						$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
					}
				}
			}
			if($_POST['columnsA']) {
				// �����������, ��� ���������� � ��������
				$accs=[];
				$str='';
				if($paramsOld['�����']==$paramsNew['�����']) {
					if($paramsOld['�����']!=$paramsNew['�����']) { $accs[]=$paramsOld['�����']; $accs[]=$paramsNew['�����']; }
					if($paramsOld['������']!=$paramsNew['������']) { $accs[]=$paramsOld['������']; $accs[]=$paramsNew['������']; }
				}
				else {
					if(!in_array($paramsOld['�����'],$accs)) $accs[]=$paramsOld['�����'];
					if(!in_array($paramsOld['������'],$accs)) $accs[]=$paramsOld['������'];
					if(!in_array($paramsNew['�����'],$accs)) $accs[]=$paramsNew['�����'];
					if(!in_array($paramsNew['������'],$accs)) $accs[]=$paramsNew['������'];
				}
				$columns=trim(preg_replace("/<.+>/",'',$mysqli->real_escape_string(iconv('UTF-8', 'windows-1251', $_POST['columnsA']))));
				for($j=0;$j<count($accs);$j++) {
					$query="SELECT $columns FROM `Accounts` WHERE `����`='{$accs[$j]}' LIMIT 1";
					$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
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
				if(substr($paramsOld['�����'],0,2)=='41') $accs[]=$paramsOld['�����'];
				if(substr($paramsOld['������'],0,2)=='41' && !in_array($paramsOld['������'],$accs)) $accs[]=$paramsOld['������'];
				if(substr($paramsNew['�����'],0,2)=='41' && !in_array($paramsNew['�����'],$accs)) $accs[]=$paramsNew['�����'];
				if(substr($paramsNew['������'],0,2)=='41' && !in_array($paramsNew['������'],$accs)) $accs[]=$paramsNew['������'];
				$columns=trim(preg_replace("/<.+>/",'',$mysqli->real_escape_string(iconv('UTF-8', 'windows-1251', $_POST['columnsG']))));
				foreach($columnAliases['Goods'] as $key=>$value)	$columns=str_replace($key,$value,$columns);
				for($j=0;$j<count($accs);$j++) {
					$query="SELECT $columns FROM `Accounts` WHERE `����`='{$accs[$j]}' LIMIT 1";
					$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
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
		$result = $mysqli->query("UNLOCK TABLES") or die('������ MySQL: ' . $mysqli->error);
	}
	elseif(isset($_GET['Delete'])) {									// ������� ������
		if(isset($Aliases[$_GET['name']])) $name=$Aliases[$_GET['name']]; else $name=$_GET['name'];
		if(!isset($nonEditable[$name])) die("������� $name �� �������");
		$mysqli = new mysqli(SERVER, USERNAME, DBPASS,DBNAME) or die('������ ����������� (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
		if($name=='Entries') {
			$query="SELECT * FROM `Entries` WHERE `{$_GET['field']}`='{$_GET['value']}' LIMIT 1";
			$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
		    $paramsOld = $result->fetch_array(MYSQLI_ASSOC);
			$result = $mysqli->query("SELECT `�����`,`������` FROM `Accounts` WHERE `����`='{$paramsOld['�����']}' LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
		    $line = $result->fetch_array(MYSQLI_ASSOC);
			$value=-$line['�����']+$line['������']+$paramsOld['�����'];
			if($value<0) { $dbsumm=-$value; $crsumm=0; }
			else { $dbsumm=0; $crsumm=$value; }
			if(in_array(substr($paramsOld['�����'],0,2),$quantAccs) && ($dbquan=$paramsOld['����������'])>0) {
				$query="UPDATE `Accounts` SET `�����`=$dbsumm, `������`=$crsumm, `����������`=`����������`-$dbquan WHERE `����`='{$paramsOld['�����']}' LIMIT 1";
			}
			else $query="UPDATE `Accounts` SET `�����`=$dbsumm, `������`=$crsumm WHERE `����`='{$paramsOld['�����']}' LIMIT 1";
			$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
			$result = $mysqli->query("SELECT `�����`,`������` FROM `Accounts` WHERE `����`='{$paramsOld['������']}' LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
		    $line = $result->fetch_array(MYSQLI_ASSOC);
			$value=-$line['�����']+$line['������']-$paramsOld['�����'];
			if($value<0) { $dbsumm=-$value; $crsumm=0; }
			else { $dbsumm=0; $crsumm=$value; }
			if(in_array(substr($paramsOld['������'],0,2),$quantAccs) && ($crquan=$paramsOld['����������'])>0) {
				$query="UPDATE `Accounts` SET `�����`=$dbsumm, `������`=$crsumm, `����������`=`����������`+$crquan WHERE `����`='{$paramsOld['������']}' LIMIT 1";
			}
			else $query="UPDATE `Accounts` SET `�����`=$dbsumm, `������`=$crsumm WHERE `����`='{$paramsOld['������']}' LIMIT 1";
			$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
			if(isset($_POST['columnsA'])) {
				$columns=trim(preg_replace("/<.+>/",'',$mysqli->real_escape_string(iconv('UTF-8', 'windows-1251', $_POST['columnsA']))));
				$query="SELECT $columns FROM `Accounts` WHERE `����`='{$paramsOld['�����']}' LIMIT 1";
				$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
			    $finfo = $result->fetch_fields();
			    $line = $result->fetch_array(MYSQLI_NUM);
		        for($i=0; $i<count($line); $i++) {
					$line[$i]=formatStr($line[$i],$finfo[$i]->type==246,"&nbsp;");
				}
				$str=implode('|',$line);
				$query="SELECT $columns FROM `Accounts` WHERE `����`='{$paramsOld['������']}' LIMIT 1";
				$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
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
				if(substr($paramsOld['�����'],0,2)=='41') {
					$query="SELECT $columns FROM `Accounts` WHERE `����`='{$paramsOld['�����']}' LIMIT 1";
					$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
				    $finfo = $result->fetch_fields();
				    $line = $result->fetch_array(MYSQLI_NUM);
			        for($i=0; $i<count($line); $i++) {
						$line[$i]=formatStr($line[$i],$finfo[$i]->type==246,"&nbsp;");
					}
					$str=implode('|',$line);
				}
				if(substr($paramsOld['������'],0,2)=='41') {
					$query="SELECT $columns FROM `Accounts` WHERE `����`='{$paramsOld['������']}' LIMIT 1";
					$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
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
			if($paramsOld['����']<$fd) {
				$cd=$paramsOld['����'];
				while($cd<$fd) {
					list($year,$month,$day)=explode('-',$cd);
					if(++$month>12) { $year++; $month='01'; }
					$month=strlen($month)<2?'0'.$month:$month;
					$cd="$year-$month-01";
					if($dbquan>0) $query="UPDATE `Balances` SET `�������`=`�������`+{$paramsOld['�����']}, `����������`=`����������`-$dbquan WHERE `����`='$cd' AND `����`='{$paramsOld['�����']}' LIMIT 1";
					else $query="UPDATE `Balances` SET `�������`=`�������`+{$paramsOld['�����']} WHERE `����`='$cd' AND `����`='{$paramsOld['�����']}' LIMIT 1";
					$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
					if($mysqli->affected_rows==1) {
						$result = $mysqli->query("SELECT `�������` FROM `Balances` WHERE `����`='$cd' AND `����`='{$paramsOld['�����']}' LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
					    $line = $result->fetch_array(MYSQLI_NUM);
						if($line[0]==0) $result = $mysqli->query("DELETE FROM `Balances` WHERE `����`='$cd' AND `����`='{$paramsOld['�����']}' LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
					}
					else {
						if(isset($dbquan)) $query="INSERT INTO `Balances` SET `����`='$cd', `����`='{$paramsOld['�����']}', `�������`={$paramsOld['�����']}, `����������`=-$dbquan";
						else $query="INSERT INTO `Balances` SET `����`='$cd', `����`='{$paramsOld['�����']}', `�������`={$paramsOld['�����']}";
						$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
					}
					if($crquan>0) $query="UPDATE `Balances` SET `�������`=`�������`-{$paramsOld['�����']}, `����������`=`����������`+$crquan WHERE `����`='$cd' AND `����`='{$paramsOld['������']}' LIMIT 1";
					else $query="UPDATE `Balances` SET `�������`=`�������`-{$paramsOld['�����']} WHERE `����`='$cd' AND `����`='{$paramsOld['������']}' LIMIT 1";
					$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
					if($mysqli->affected_rows==1) {
						$result = $mysqli->query("SELECT `�������` FROM `Balances` WHERE `����`='$cd' AND `����`='{$paramsOld['������']}' LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
					    $line = $result->fetch_array(MYSQLI_NUM);
						if($line[0]==0) $result = $mysqli->query("DELETE FROM `Balances` WHERE `����`='$cd' AND `����`='{$paramsOld['������']}' LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
					}
					else {
						if(isset($crquan)) $query="INSERT INTO `Balances` SET `����`='$cd', `����`='{$paramsOld['������']}', `�������`=-{$paramsOld['�����']}, `����������`=$crquan";
						else $query="INSERT INTO `Balances` SET `����`='$cd', `����`='{$paramsOld['������']}', `�������`=-{$paramsOld['�����']}";
						$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
					}
				}
			}
		}
		$query = "DELETE FROM `$name` WHERE `{$_GET['field']}`='{$_GET['value']}' LIMIT 1";
		$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
		$reply['reply']='Deleted';
		if($name=='Entries') $flag=ACCOUNTS | ENTRIES | GOODS | CTRAGENTS; 
		elseif($name=='Accounts') $flag=ACCOUNTS | GOODS | CTRAGENTS;
		elseif($name=='Invoice') $flag=INVOICE;
		elseif($name=='Stencil') $flag=STENCIL;
		else $flag=0;
		if($flag) liveVidgets($flag);
		echo php2json($reply);
	}
	elseif(isset($_GET['Add']) || isset($_GET['newEntry'])) {			// ����� ������ ��� ����� ��������
		$name=isset($_GET['Add'])?$_GET['name']:'Entries';
		if(isset($Aliases[$name])) $name=$Aliases[$name];
		if(!isset($nonEditable[$name])) die("������� $name �� �������");
		if(count($_POST)==0) {
			$finfo=get_field_info($name);
			echo "<table class='npr' cellpadding=4 border=0 style='margin-top:1em'>";
			foreach($finfo as $key=>$value) {
				if(!in_array($key,$nonAddable[isset($_GET['name'])?$_GET['name']:$name])) {
					$value=preg_replace("/'/","\"",$value);
					echo "<tr><td style='font-weight:bold'>$key:</td><td><input type='text' name='$key' format='$value'".($key=='����'?" value='".date("d.m.Y")."'":"")."></td></tr>";
				}
			}
			echo "</table>";
			echo "<button onclick='saveFromAdd(\"".($_GET['name']?$_GET['name']:$name)."\")' style='margin: 20px 37% 0'>���������</button>";
		}
		else {
			$mysqli = new mysqli(SERVER, USERNAME, DBPASS,DBNAME) or die('������ ����������� (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
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
				$result = $mysqli->query("LOCK TABLES `$name` WRITE") or die('������ MySQL: ' . $mysqli->error);
				$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
				$result = $mysqli->query("UNLOCK TABLES") or die('������ MySQL: ' . $mysqli->error);
			}
			if($_POST['columns']) {
				if($_POST['columns']=='unknown') {
					if($name=='Entries') $reply['reply']= show_all("SELECT * FROM `Entries` ORDER BY `����` DESC, `����_�����` DESC");
					elseif($name=='Invoice') $reply['reply']= show_all("SELECT * FROM `Invoice` ORDER BY `����`, `�����`");
					else $reply['reply']= 'Reload';
				}
				else {
					$columns=trim(preg_replace("/<.+>/",'',$mysqli->real_escape_string(iconv('UTF-8', 'windows-1251', $_POST['columns']))));
					if($_GET['name']!=$name && isset($columnAliases[$_GET['name']])) {
						foreach($columnAliases[$_GET['name']] as $key=>$value)
						$columns=str_replace($key,$value,$columns);
					}
					$query=str_replace(',',' AND ',$query);
					if($name=='Entries') $query="SELECT $columns FROM `Entries` ORDER BY `�����` DESC LIMIT 1";
					else $query="SELECT $columns FROM `$name` WHERE ".substr($query,strpos($query,'SET')+4)." LIMIT 1";
					$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
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
					$reply['Entries']=show_all("SELECT * FROM `Entries` ORDER BY `����` DESC, `����_�����` DESC");
				}
				else {
					$columns=trim(preg_replace("/<.+>/",'',$mysqli->real_escape_string(iconv('UTF-8', 'windows-1251', $_POST['columnsE']))));
					$query="SELECT $columns FROM `Entries` ORDER BY `�����` DESC LIMIT 1";
					$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
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
				$query="SELECT $columns FROM `Accounts` WHERE `����`='{$params['�����']}' LIMIT 1";
				$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
			    $finfo = $result->fetch_fields();
			    $line = $result->fetch_array(MYSQLI_NUM);
		        for($i=0; $i<count($line); $i++) {
					$line[$i]=formatStr($line[$i],$finfo[$i]->type==246,"&nbsp;");
				}
				$str=implode('|',$line);
				$query="SELECT $columns FROM `Accounts` WHERE `����`='{$params['������']}' LIMIT 1";
				$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
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
				if(substr($params['�����'],0,2)=='41') {
					$query="SELECT $columns FROM `Accounts` WHERE `����`='{$params['�����']}' LIMIT 1";
					$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
				    $finfo = $result->fetch_fields();
				    $line = $result->fetch_array(MYSQLI_NUM);
			        for($i=0; $i<count($line); $i++) {
						$line[$i]=formatStr($line[$i],$finfo[$i]->type==246,"&nbsp;");
					}
					$str=implode('|',$line);
				}
				if(substr($params['������'],0,2)=='41') {
					$query="SELECT $columns FROM `Accounts` WHERE `����`='{$params['������']}' LIMIT 1";
					$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
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
			echo "<tr><td><b>����</b></td><td><input type='text' name='����' format='date' value='".date("d.m.Y")."'></td></tr>";
			echo "<tr><td><b>�����</b></td><td><input type='text' name='�����' format='varchar(10)'></td></tr>";
			echo "<tr><td><b>�����</b></td><td><input type='text' name='�����' format='varchar(7)'></td></tr>";
			echo "<tr><td>��� <b>���</b></td><td><input type='text' name='���' format='varchar(20)'></td></tr>";
			echo "<tr><td><b>������������</b></td><td><span name='������������'></span></td></tr>";
			echo "<tr><td><b>�������, ��.</b></td><td><span name='����������'></span></td></tr>";
			echo "<tr><td><b>���� �������</b></td><td><span name='����_��'></span></td></tr>";
			echo "<tr><td><b>���� ���������</b></td><td><span name='����_����'></span></td></tr>";
			echo "<tr><td><b>����������</b></td><td><input type='text' name='����������' format='int'></td></tr>";
			echo "</table>";
			echo "<button onclick='doSale()'>�������</button>";
		}
		else {
			$mysqli = new mysqli(SERVER, USERNAME, DBPASS,DBNAME) or die('������ ����������� (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
			$finfo=['����'=>'date', '�����'=>'varchar', '�����'=>'varchar', '���'=>'varchar', '����������'=>'int'];
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
			if($paramsSale['���']) $result = $mysqli->query("SELECT `����`,`������������`,`����_����`,IF(`����������`,ROUND((`�����`-`������`)/`����������`,2),'') AS `����_��` FROM `Accounts` WHERE `���` LIKE '%{$paramsSale['���']}%' LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
			if($result->num_rows==0) $result = $mysqli->query("SELECT `����`,`������������`,`����_����`,IF(`����������`,ROUND((`�����`-`������`)/`����������`,2),'') AS `����_��` FROM `Accounts` WHERE `����`='{$paramsSale['�����']}' LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
		    $line = $result->fetch_array(MYSQLI_ASSOC);
			if(!$line) { echo "������ - �� ������ �����"; exit; }
			$params1['����']=$paramsSale['����'];
			$params1['�����']=SALESACC;
			$params1['������']=$line['����'];
			$params1['�����']=$line['����_��']*$paramsSale['����������'];
			$params1['����������']="������� {$line['������������']}, {$line['����_��']}*{$paramsSale['����������']}";
			$params1['����������']=$paramsSale['����������'];
			$params2['����']=$paramsSale['����'];
			$params2['�����']=$paramsSale['�����'];
			$params2['������']=SALESACC;
			$params2['�����']=$line['����_����']*$paramsSale['����������'];
			$params2['����������']="������� {$line['������������']}, {$line['����_����']}*{$paramsSale['����������']}";
			$params2['����������']='';
			makeEntry($params1);
			makeEntry($params2);
			if(isset($_POST['columnsE'])) {
				if($_POST['columnsE']=='unknown' || $_POST['columnsE']=='') {
					$reply['Entries']=show_all("SELECT * FROM `Entries` ORDER BY `����` DESC, `����_�����` DESC");
				}
				else {
					$columns=trim(preg_replace("/<.+>/",'',$mysqli->real_escape_string(iconv('UTF-8', 'windows-1251', $_POST['columnsE']))));
					$query="SELECT $columns FROM `Entries` ORDER BY `�����` DESC LIMIT 2";
					$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
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
				$query="SELECT $columns FROM `Accounts` WHERE `����`='".SALESACC."' LIMIT 1";
				$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
			    $finfo = $result->fetch_fields();
			    $line = $result->fetch_array(MYSQLI_NUM);
		        for($i=0; $i<count($line); $i++) {
					$line[$i]=formatStr($line[$i],$finfo[$i]->type==246,"&nbsp;");
				}
				$str=implode('|',$line);
				$query="SELECT $columns FROM `Accounts` WHERE `����`='{$paramsSale['�����']}' LIMIT 1";
				$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
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
				$query="SELECT $columns FROM `Accounts` WHERE `����`='{$params1['������']}' LIMIT 1";
				$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
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
	elseif(isset($_GET['Invoice'])) {									// ��������� ���������	
		echo show_all("SELECT * FROM `Invoice` ORDER BY `����`, `�����`");
	}
	elseif(isset($_GET['AddInvoice'])) {						// ����� ������ � ��������� ���������
		echo "<table class='npr' cellpadding=4 border=0 style='margin-top:1em'>";
		echo "<tr><td style='font-weight:bold'>����</td><td><input type='text' name='����' format='date' value='".date("d.m.Y")."'></td></tr>";
		echo "<tr><td style='font-weight:bold'>������ ������<br>����� ������</td><td><input type='text' name='������' format='varchar'><div class='tip'>��������� ��������� ������� � ������ ������ �����</div></td></tr>";
		echo "<tr><td style='font-weight:bold'>������</td><td><input type='text' name='������' format='varchar'></td></tr>";
		echo "<tr class='hr'><td style='font-weight:bold'>������������<br>��� ���</td><td><div id='anchor'></div><input type='text' name='�����' format='varchar(30)'></td></tr>";
		echo "<tr><td style='font-weight:bold'>���</td><td><input type='text' name='���' format='int'></td></tr>";
		echo "<tr><td style='font-weight:bold'>�����</td><td><input type='text' name='�����' format='varchar' disabled></td></tr>";
		echo "<tr><td style='font-weight:bold'>����</td><td><input type='text' name='����' format='decimal'></td></tr>";
		echo "<tr><td style='font-weight:bold'>����������</td><td><input type='text' name='����������' format='int'></td></tr>";
		echo "<tr><td style='font-weight:bold'>�����</td><td><input type='text' name='�����' format='decimal'></td></tr>";
		echo "<tr><td style='font-weight:bold'>����_����</td><td><input type='text' name='����_����' format='decimal'></td></tr>";
		echo "</table>";
		echo "<input type='hidden' name='����������' format='varchar'>";
		echo "<button onclick='saveFromAdd(\"Invoice\")' style='margin: 20px 37% 0'>���������</button>";
	}
	elseif(isset($_GET['LoadInvoice'])) {
		$mysqli = new mysqli(SERVER, USERNAME, DBPASS,DBNAME) or die('������ ����������� (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
		$resultI = $mysqli->query("SELECT * FROM `Invoice` ORDER BY `�����`") or die('������ MySQL: ' . $mysqli->error);
		$n=0; // ����� ����������
		$m=0; // �������� ������� ���������
		$k=0; // ���������� ������ �������������� ������
		$s=0; // ��������� �������������� ������
		while($lineI = $resultI->fetch_array(MYSQLI_ASSOC)) {
			$result = $mysqli->query("SELECT `����`,`���` FROM `Accounts` WHERE `����` = '{$lineI['�����']}' LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
			if($result->num_rows==0) {
				$query="INSERT INTO `Accounts` SET `����`='{$lineI['�����']}', `������������`='{$lineI['������������']}', `��`='�', `����_����`='{$lineI['����_����']}', `���`='{$lineI['���']}'";
				$result = $mysqli->query($query); $m++;
			}
			else {
				$query="UPDATE `Accounts` SET `������������`='{$lineI['������������']}', `����_����`='{$lineI['����_����']}'";
				$line=$result->fetch_array(MYSQLI_ASSOC);
				if($lineI['���']!='' && strpos($line['���'],$lineI['���'])===false) $query.=", `���`='".($line['���']?$line['���'].',':'').$lineI['���']."'";
				$query.=" WHERE `����` = '{$lineI['�����']}' LIMIT 1";
				$result = $mysqli->query($query);
			}
			$params['����']=$lineI['����'];
			$params['�����']=$lineI['�����'];
			$params['������']=$lineI['������'];
			$params['�����']=$lineI['�����'];
			$params['����������']="����� {$lineI['������������']} ".sprintf("%01.2f",$lineI['�����']/$lineI['����������'])."*{$lineI['����������']}";
			$params['����������']=$lineI['����������'];
			makeEntry($params);
			$k+=$lineI['����������'];
			$s+=$lineI['�����'];
			$n++;
		}
		$result = $mysqli->query("TRUNCATE TABLE `Invoice`");
		$reply['reply']="���������� <b>$n</b> ����� ��������� ���������<br>��������� <b>$m</b> ����� �������� �������<br>������������ <b>$k</b> ������ ������<br>�� ����� ����� <b>".number_format($s,2,'.',DLMTR)."</b>";
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
	elseif(isset($_GET['Reset'])) {									// ������������ ���� ������	
//echo "������ - ���� �� ���� ���������� ����...";
//exit;	
		$mysqli = new mysqli(SERVER, USERNAME, DBPASS,DBNAME) or die('������ ����������� (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
		$tpl=file('templates/tables.tpl', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		for($i=0;$i<count($tpl);$i++) {
			if($tpl[$i]=='//EXTENDED' && !$extended) break;
			if(substr($tpl[$i],0,2)!='//') $result = $mysqli->query($tpl[$i]) or die('������ MySQL: ' . $mysqli->error);
		}
		$reply['reply']='���� ������ ����������������';
		liveVidgets(ACCOUNTS | ENTRIES | GOODS | INVOICE | CTRAGENTS);
		echo php2json($reply);
	}
	elseif(isset($_GET['KudirRules'])) {
		$param=read_param(false);
		if($_GET['KudirRules']=='titul') {
			echo "<table class='npr' cellpadding=4 border=0 style='margin-top:1em'>";
			echo "<tr><td style='font-weight:bold'>�.�.�.</td><td><input type='text' name='���' format='varchar(40)' value='{$param['���']}'></td></tr>";
			echo "<tr><td style='font-weight:bold'>���</td><td><input type='text' name='���' format='int(12)' value='{$param['���']}'></td></tr>";
			echo "<tr><td style='font-weight:bold'>������ ���������������</td><td><label><input type='radio' name='������' value='������'".($param['������']=='������'?' checked':'').">������</label><label><input type='radio' name='������' value='�������'".($param['������']!='������'?' checked':'').">������ ����� �������</label></td></tr>";
			echo "<tr><td style='font-weight:bold'>�����</td><td><input type='text' name='�����' format='varchar(80)' value='{$param['�����']}'></td></tr>";
			echo "<tr><td style='font-weight:bold'>������ ������ � ������</td><td><input type='text' name='�����' format='varchar(80)' value='{$param['�����']}'></td></tr>";
			echo "</table>";
			echo "<button onclick='saveFromKudir()' style='margin: 20px 37% 0'>���������</button>";
		}
		elseif($_GET['KudirRules']=='rules') {
			echo "<table class='npr' cellpadding=4 border=0 style='margin-top:1em'>";
			echo "<tr><td style='font-weight:bold'>������� �������� �������</td><td><textarea name='�������_�������' cols=44 rows=3>{$param['�������_�������']}</textarea></td></tr>";
			echo "<tr><td style='font-weight:bold'>���������� �������</td><td><textarea name='����������_�������' cols=44 rows=3>{$param['����������_�������']}</textarea></td></tr>";
			echo "<tr><td style='font-weight:bold'>������� �������� ��������</td><td><textarea name='�������_��������' cols=44 rows=3>{$param['�������_��������']}</textarea></td></tr>";
			echo "<tr><td style='font-weight:bold'>���������� ��������</td><td><textarea name='����������_��������' cols=44 rows=3>{$param['����������_��������']}</textarea></td></tr>";
			echo "</table>";
			echo "<button onclick='saveFromKudir()' style='margin: 20px 37% 0'>���������</button>";
		}
		elseif($_GET['KudirRules']=='tunes') {
			echo "<table class='npr' cellpadding=4 border=0 style='margin-top:1em'>";
			echo "<tr><td style='font-weight:bold'>����� �� ��������</td><td><input type='text' name='�����' format='int' value='".($param['�����']==0?22:$param['�����'])."'></td></tr>";
			echo "<tr><td style='font-weight:bold'>��� ��������� �������</td><td><input type='checkbox' name='���' format='checkbox' value='��'".($param['���']=='��'?' checked':'')."></td></tr>";
			echo "<tr><td style='font-weight:bold'>��������� ������� <br>�� ���������� �������� �������� � ��������</td><td><input type='checkbox' name='�������' format='checkbox' value='��'".($param['�������']=='��'?' checked':'')."></td></tr>";
			echo "<tr><td style='font-weight:bold'>��������� ������� <br>�� ���������� ��������� �� ����� 60 � ��������</td><td><input type='checkbox' name='���������' format='checkbox' value='��'".($param['���������']=='��'?' checked':'')."></td></tr>";
			echo "<tr><td style='font-weight:bold'>��������� ������� <br>�� ���������� ���������� �� ����� 60 � ��������</td><td><input type='checkbox' name='����������' format='checkbox' value='��'".($param['����������']=='��'?' checked':'')."></td></tr>";
			echo "</table>";
			echo "<button onclick='saveFromKudir()' style='margin: 20px 37% 0'>���������</button>";
		}
		elseif($_GET['KudirRules']=='save') {
			$finfo=['���'=>'', '���'=>'', '������'=>'', '�����'=>'', '�����'=>'',
					'�������_�������'=>'', '�������_��������'=>'', '����������_�������'=>'', '����������_��������'=>'',
					'�����'=>'', '���'=>'', '�������'=>'', '���������'=>'', '����������'=>''];
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
		if($param['�������_�������']) $revRules=format_rules($param['�������_�������']);
		else { echo "�� ������� ������� �������� �������"; exit; }
		if($param['����������_�������']) $revExc=format_rules($param['����������_�������']);
		else $revExc=[];
		if($param['�������_��������']) $expRules=format_rules($param['�������_��������']);
		elseif($param['������']=='�������') { echo "�� ������� ������� �������� ��������"; exit; }
		else $expRules=[];
		if($param['����������_��������']) $expExc=format_rules($param['����������_��������']);
		else $expExc=[];
		if(preg_match("/^20\d\d$/",$_GET['Year'])) $Year=$_GET['Year']; else $Year='';
		if(preg_match("/^Quartal$|^Actual$/",$_GET['Mode'])) $Mode=$_GET['Mode']; else $Mode='';
		echo "<table class='npr' cellpadding=4 border=0>";
		echo "<tr><td style='font-weight:bold;width:60%'>��� ��� ������������ �����</td><td><input type='text' name='Year' format='year' value='$Year'></td></tr>";
		echo "<tr><td style='font-weight:bold'>����� ������������</td><td><label><input type='radio' name='Mode' value='Quartal'".($Mode=='Quartal'?' checked':'')." style='margin-left:0'>�����������</label><br><label><input type='radio' name='Mode' value='Actual'".($Mode=='Quartal'?'':' checked')." style='margin-left:0'>�������</label></td></tr>";
		echo "</table>";
		echo "<button onclick='kudirForm()'>������������</button>";
		if($Year && $Mode) {
			if($Mode=='Quartal') {	?>
				<div class='page'>
					<div class='kudir-upheader'>���������� � 1<br>� ������� ������������ ��������<br>���������� ���������<br>�� 22.10.2012 � 135�</div>
					<div class='kudir-header' style='margin-top:5em'>�����<br>����� ������� � �������� ����������� � �������������� ����������������,<br>����������� ���������� ������� ���������������</div>
					<table class='kudir-titul' border=0>
						<tr><td style='width:85%'>&nbsp;</td><td colspan=3 class='b'>����</td></tr>
						<tr><td style='text-align:right'>����� �� ����</td><td colspan=3 class='b'>&nbsp;</td></tr>
						<tr><td><span style='margin-left:5em'>�� <u><b><? echo $Year ?></b></u> ���</span><span style='float:right'>���� (���, �����, �����)</span></td><td class='b'>&nbsp;</td><td class='b'>&nbsp;</td><td class='b' style='width:7%'>&nbsp;</td></tr>
						<tr><td>&nbsp;</td><td colspan=3 class='blr'>&nbsp;</td></tr>
						<tr><td>���������������� (������������</td><td colspan=3 class='blr'>&nbsp;</td></tr>
						<tr><td>�����������/�������, ���, ��������</td><td colspan=3 class='blr'>&nbsp;</td></tr>
						<tr><td>��������������� ���������������) <u><b><? echo $param['���'] ?></b></u></td><td colspan=3 class='blr'>&nbsp;</td></tr>
						<tr><td style='text-align:right'>�� ����</td><td colspan=3 class='blr'>&nbsp;</td></tr>
						<tr><td>&nbsp;</td><td colspan=3 class='blr'>&nbsp;</td></tr>
						<tr><td>����������������� ����� ����������������� - �����������/��� ������� ���������� ��</td><td colspan=3 class='blr'>&nbsp;</td></tr>
						<tr><td>���� � ��������� ������ (���/���)</td><td colspan=3 class='blr'>&nbsp;</td></tr>
						<tr><td>&nbsp;</td><td colspan=3 class='blr'>&nbsp;</td></tr>
						<tr><td><table style='width:77%'><tr><? for($i=0;$i<12;$i++) echo "<td class='b'>&nbsp;</td>" ?><td class='b'>/</td><? for($i=0;$i<9;$i++) echo "<td class='b'>&nbsp;</td>" ?></tr></table></td><td colspan=3 class='blr'>&nbsp;</td></tr>
						<tr><td>&nbsp;</td><td colspan=3 class='blr'>&nbsp;</td></tr>
						<tr><td>����������������� ����� ����������������� - ��������������� ��������������� (���)</td><td colspan=3 class='blr'>&nbsp;</td></tr>
						<tr><td>&nbsp;</td><td colspan=3 class='blr'>&nbsp;</td></tr>
						<tr><td><table style='width:42%'><tr><? for($i=0;$i<12;$i++) echo "<td class='b'>{$param['���'][$i]}</td>" ?></tr></table></td><td colspan=3 class='blr'>&nbsp;</td></tr>
						<tr><td>&nbsp;</td><td colspan=3 class='blr'>&nbsp;</td></tr>
						<tr><td>������ ��������������� <u><b><? echo $param['������']=='������'?'������':'������ ����� �������' ?></b></u></td><td colspan=3 class='blr'>&nbsp;</td></tr>
						<tr><td style='text-align:center'>(������������ ���������� ������� ���������������<br>� ������������ �� ������� 346.14 ���������� ������� ���������� ���������)</td><td colspan=3 class='blr'>&nbsp;</td></tr>
						<tr><td>&nbsp;</td><td colspan=3 class='blr'>&nbsp;</td></tr>
						<tr><td>������� ���������: ���.<span style='float:right'>�� ����</span></td><td colspan=3 class='b'>383</td></tr>
					</table>
					<div class='kudir-text'>����� ����� ���������� �����������<br>(����� ���������� ���������������<br>���������������) <u><b><? echo $param['�����'] ?></b></u></div>
					<div class='kudir-text'>������ ��������� � ���� ������, �������� � ����������� ������ <u><b><? echo $param['�����'] ?></b></u></div>
				</div>	<?PHP
				if(($m=date("m"))<='03') { echo "<b>������ ������� ��� �� ����������</b>"; exit; }
				elseif($m<='06') $endDate=$Year."-03-31";
				elseif($m<='09') $endDate=$Year."-06-30";
				else $endDate=$Year."-09-30";
			}
			else $endDate=date("Y-m-d");
			if($Year<date("Y")) { $endDate=$Year."-12-31"; $Mode='Quartal'; }
			$strings=[];
			$strnum=0;
			$mysqli = new mysqli(SERVER, USERNAME, DBPASS,DBNAME) or die('������ ����������� (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
			$day=1; $num=1; $prevDate='';
			$osQuart=0; $osTotal=0;
			while(($curDate=date("Y-m-d",mktime(0,0,0,1,$day,(int)$Year)))<=$endDate) {
				if($prevDate && substr($prevDate,5,2)!=($mm=substr($curDate,5,2)) && (int)$mm%3==1) {
					$mm=(int)($mm/3);
					$startQuart=$Year.'-'.sprintf("%02d",$mm*3-2).'-01';
					$endQuart=date("Y-m-d",mktime(0,0,0,1,$day-1,(int)$Year));
					if($param['������']=='�������') {
						$osTotal+=$osQuart/(5-$mm);
						if($osTotal>0) {
							$strings[$strnum]=[];
							$strings[$strnum][0]=$num++;
							$strings[$strnum][1]=$prevDate;
							$strings[$strnum][2]="�������� �������� �� ������������ �������� ������� � $mm ��������";
							$strings[$strnum][3]='';
							$strings[$strnum][4]=$osTotal;
							if(++$strnum==$param['�����']) {
								make_page($strings); $strings=[]; $strnum=0;
							}
						}
						if($param['�������']=='��') {
							$result = $mysqli->query("SELECT -SUM(`�������`) FROM `Balances` WHERE `����`='$startQuart' AND `����` LIKE '41%' AND `�������`<0 LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
							$line = $result->fetch_array(MYSQLI_NUM);
							$rests1=$line[0];
							$result = $mysqli->query("SELECT -SUM(`�������`) FROM `Balances` WHERE `����`='$curDate' AND `����` LIKE '41%' AND `�������`<0 LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
							$line = $result->fetch_array(MYSQLI_NUM);
							$rests2=$line[0];
							if(($rests1>0 || $rests2>0)) {
								$strings[$strnum]=[];
								$strings[$strnum][0]=$num++;
								$strings[$strnum][1]=toOurDate($endQuart);
								$strings[$strnum][2]="�������� ������� �� ".toOurDate($startQuart).": ".number_format($rests1,2,'.',DLMTR)."<br>�������� ������� �� ".toOurDate($curDate).": ".number_format($rests2,2,'.',DLMTR)."<br>".($rests1>$rests2?'����������':'����������')." �������� �������� � $mm ��������: ".number_format(abs($rests1-$rests2),2,'.',DLMTR);
								$strings[$strnum][3]='';
								$strings[$strnum][4]=$rests1-$rests2;
								if(++$strnum==$param['�����']) {
									make_page($strings); $strings=[]; $strnum=0;
								}
							}
						}
						if($param['���������']=='��') {
							$result = $mysqli->query("SELECT -SUM(`�������`) FROM `Balances` WHERE `����`='$startQuart' AND `����` LIKE '60%' AND `�������`<0 LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
							$line = $result->fetch_array(MYSQLI_NUM);
							$debts1=$line[0];
							$result = $mysqli->query("SELECT -SUM(`�������`) FROM `Balances` WHERE `����`='$curDate' AND `����` LIKE '60%' AND `�������`<0 LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
							$line = $result->fetch_array(MYSQLI_NUM);
							$debts2=$line[0];
							if($debts1>0 || $debts2>0) {
								$strings[$strnum]=[];
								$strings[$strnum][0]=$num++;
								$strings[$strnum][1]=toOurDate($endQuart);
								$strings[$strnum][2]="��������� ������� �� ��.60 �� ".toOurDate($startQuart).": ".number_format($debts1,2,'.',DLMTR)."<br>��������� ������� �� ��.60 �� ".toOurDate($curDate).": ".number_format($debts2,2,'.',DLMTR)."<br>".($debts1>$debts2?'����������':'����������')." ��������� �������� � $mm ��������: ".number_format(abs($debts1-$debts2),2,'.',DLMTR);
								$strings[$strnum][3]='';
								$strings[$strnum][4]=$debts1-$debts2;
								if(++$strnum==$param['�����']) {
									make_page($strings); $strings=[]; $strnum=0;
								}
							}
						}
						if($param['����������']=='��') {
							$result = $mysqli->query("SELECT SUM(`�������`) FROM `Balances` WHERE `����`='$startQuart' AND `����` LIKE '60%' AND `�������`>0 LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
							$line = $result->fetch_array(MYSQLI_NUM);
							$credts1=$line[0];
							$result = $mysqli->query("SELECT SUM(`�������`) FROM `Balances` WHERE `����`='$curDate' AND `����` LIKE '60%' AND `�������`>0 LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
							$line = $result->fetch_array(MYSQLI_NUM);
							$credts2=$line[0];
							if($credts1>0 || $credts2>0) {
								$strings[$strnum]=[];
								$strings[$strnum][0]=$num++;
								$strings[$strnum][1]=toOurDate($endQuart);
								$strings[$strnum][2]="���������� ������� �� ��.60 �� ".toOurDate($startQuart).": ".number_format($credts1,2,'.',DLMTR)."<br>���������� ������� �� ��.60 �� ".toOurDate($curDate).": ".number_format($credts2,2,'.',DLMTR)."<br>".($credts1>$credts2?'����������':'����������')." ���������� �������� � $mm ��������: ".number_format(abs($credts1-$credts2),2,'.',DLMTR);
								$strings[$strnum][3]='';
								$strings[$strnum][4]=$credts1-$credts2;
								if(++$strnum==$param['�����']) {
									make_page($strings); $strings=[]; $strnum=0;
								}
							}
						}
					}
					make_page($strings,$mm); $strings=[]; $strnum=0; $osQuart=0;
				}
				$prevDate=$curDate;
				$result = $mysqli->query("SELECT `����`, `�����`, `������`, `�����`, `����������` FROM `Entries` WHERE `����`='$curDate'") or die('������ MySQL: ' . $mysqli->error);
				while($line = $result->fetch_array(MYSQLI_NUM)) {
					$revPass=false; $expPass=false; $revSumRule=''; $expSumRule='';
					for($i=0;$i<count($revExc);$i++) if(preg_match($revExc[$i]['�'],$line[1]) && preg_match($revExc[$i]['�'],$line[2])) break;
					if($i==count($revExc)) {
						for($i=0;!$revPass && $i<count($revRules);$i++) {
							$revPass=preg_match($revRules[$i]['�'],$line[1]) && preg_match($revRules[$i]['�'],$line[2]);
							if($revPass && isset($revRules[$i]['�'])) $revSumRule=$revRules[$i]['�'];
						}
					}
					if($param['������']=='�������') {
						for($i=0;$i<count($expExc);$i++) if(preg_match($expExc[$i]['�'],$line[1]) && preg_match($expExc[$i]['�'],$line[2])) break;
						if($i==count($expExc)) {
							for($i=0;!$expPass && $i<count($expRules);$i++) {
								$expPass=preg_match($expRules[$i]['�'],$line[1]) && preg_match($expRules[$i]['�'],$line[2]);
								if($expPass && isset($expRules[$i]['�'])) $expSumRule=$expRules[$i]['�'];
							}
						}
					}
					if($revPass || $expPass) {
						$strings[$strnum]=[];
						$strings[$strnum][0]=$num++;
						if(preg_match("/��\s+(\d\d[\.,]\d\d[\.,]\d{2,4})/",$line[4],$match)) $docDate=str_replace(',','.',$match[1]); else $docDate=toOurDate($line[0]);
						if(substr($line[1],0,2)=='51') $docNum='���.�����';
						elseif(substr($line[1],0,2)=='50') $docNum='z-�����';
						elseif(preg_match("/[�N]\s+([^\s,;]+)|[��]����\s+([^\s,;]+)/",$line[4],$match)) $docNum=$match[1];
						else $docNum='�/�';
						$strings[$strnum][1]=$docDate.' '.$docNum;
						if($param['���']=='��' && preg_match("/���\s+(\d{1,2}\%\s+)?(-\s+)?(\d+[\.,]?\d{0,2})/",$line[4],$match)) $nds=str_replace(',','.',$match[3]); else $nds=0;
						$strings[$strnum][2]=$nds?substr($line[4],0,strpos($line[4],'���')):$line[4];
						if($revSumRule=='') $strings[$strnum][3]=$revPass?$line[3]:'';
						else $strings[$strnum][3]=calc_rule($revSumRule,$line[4],$line[3]);
						if($nds) $strings[$strnum][4]=$line[3]-$nds;
						elseif($expSumRule=='') $strings[$strnum][4]=$expPass?$line[3]:'';
						else $strings[$strnum][4]=calc_rule($expSumRule,$line[4],$line[3]);
						if(++$strnum==$param['�����']) {
							make_page($strings); $strings=[]; $strnum=0;
						}
						if($nds) {
							$strings[$strnum]=[];
							$strings[$strnum][0]=$num++;
							$strings[$strnum][1]=$docDate.' '.$docNum;
							$strings[$strnum][2]=$line[4];
							$strings[$strnum][3]='';
							if($nds) $strings[$strnum][4]=$nds;
							if(++$strnum==$param['�����']) {
								make_page($strings); $strings=[]; $strnum=0;
							}
						}
					}
					if(substr($line[1],0,2)=='01' && substr($line[2],0,2)!='01') $osQuart+=$line[3];
				}
				$day++;
			}
			$result = $mysqli->query("SELECT SUM(`�����`) FROM `Entries` WHERE `�����` LIKE '68%' AND `����������` LIKE '%$Year%' LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
			$line = $result->fetch_array(MYSQLI_NUM);
			$taxPayed=$line[0];
			if($param['������']=='������') {
				$result = $mysqli->query("SELECT SUM(`�����`) FROM `Entries` WHERE `����`>='".$Year.'-01-01'."' AND `����`<='$endDate' AND `������` REGEXP '51|50' AND (`�����` LIKE '69%' OR `����������` REGEXP '���|���|���|���|���|���|������') LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
				$line = $result->fetch_array(MYSQLI_NUM);
				$pfrPayed=$line[0];
				$result = $mysqli->query("SELECT SUM(`�����`) FROM `Entries` WHERE `����`>='".$Year.'-01-01'."' AND `����`<='$endDate' AND `������` REGEXP '51|50' AND `����������` REGEXP '������.{2,3} +����' LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
				$line = $result->fetch_array(MYSQLI_NUM);
				$duePayed=$line[0];
			}
			if(substr($endDate,5)=='12-31') $mm=4; else $mm=0;
			if($mm>0) $osTotal+=$osQuart/(5-$mm);
			if($param['������']=='�������')	{
				if($mm>0 && $osTotal>0) {
					$strings[$strnum]=[];
					$strings[$strnum][0]=$num++;
					$strings[$strnum][1]=$endDate;
					$strings[$strnum][2]="�������� �������� �� ������������ �������� ������� � 4 ��������";
					$strings[$strnum][3]='';
					$strings[$strnum][4]=$osTotal;
					if(++$strnum==$param['�����']) {
						make_page($strings); $strings=[]; $strnum=0;
					}
				}
				$startDate=date("Y-m-d",mktime(0,0,0,(int)(((int)substr($curDate,5,2)-1)/3)*3+1,1,(int)$Year));
				$m=(int)(((int)substr($curDate,5,2)+2)/3);
				if($param['�������']=='��') {
					$result = $mysqli->query("SELECT -SUM(`�������`) FROM `Balances` WHERE `����`='$startDate' AND `����` LIKE '41%' AND `�������`<0 LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
					$line = $result->fetch_array(MYSQLI_NUM);
					$rests1=$line[0];
					if($Mode=='Quartal') $query="SELECT -SUM(`�������`) FROM `Balances` WHERE `����`='$curDate' AND `����` LIKE '41%' AND `�������`<0 LIMIT 1";
					else $query="SELECT SUM(`�����`) FROM `Accounts` WHERE `����` LIKE '41%' AND `�����`>0 LIMIT 1";
					$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
					$line = $result->fetch_array(MYSQLI_NUM);
					$rests2=$line[0];
					if(($rests1>0 || $rests2>0)) {
						$strings[$strnum]=[];
						$strings[$strnum][0]=$num++;
						$strings[$strnum][1]=toOurDate($endDate);
						$strings[$strnum][2]="�������� ������� �� ".toOurDate($startDate).": ".number_format($rests1,2,'.',DLMTR)."<br>�������� ������� �� ".toOurDate($curDate).": ".number_format($rests2,2,'.',DLMTR)."<br>".($rests1>$rests2?'����������':'����������')." �������� �������� � $m ��������: ".number_format(abs($rests1-$rests2),2,'.',DLMTR);
						$strings[$strnum][3]='';
						$strings[$strnum][4]=$rests1-$rests2;
						if(++$strnum==$param['�����']) {
							make_page($strings); $strings=[]; $strnum=0;
						}
					}
				}
				if($param['���������']=='��') {
					$result = $mysqli->query("SELECT -SUM(`�������`) FROM `Balances` WHERE `����`='$startDate' AND `����` LIKE '60%' AND `�������`<0 LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
					$line = $result->fetch_array(MYSQLI_NUM);
					$debts1=$line[0];
					if($Mode=='Quartal') $query="SELECT -SUM(`�������`) FROM `Balances` WHERE `����`='$curDate' AND `����` LIKE '60%' AND `�������`<0 LIMIT 1";
					else $query="SELECT SUM(`�����`) FROM `Accounts` WHERE `����` LIKE '60%' AND `�����`>0 LIMIT 1";
					$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
					$line = $result->fetch_array(MYSQLI_NUM);
					$debts2=$line[0];
					if($debts1>0 || $debts2>0) {
						$strings[$strnum]=[];
						$strings[$strnum][0]=$num++;
						$strings[$strnum][1]=toOurDate($endDate);
						$strings[$strnum][2]="��������� ������� �� ��.60 �� ".toOurDate($startDate).": ".number_format($debts1,2,'.',DLMTR)."<br>��������� ������� �� ��.60 �� ".toOurDate($curDate).": ".number_format($debts2,2,'.',DLMTR)."<br>".($debts1>$debts2?'����������':'����������')." ��������� �������� � $m ��������: ".number_format(abs($debts1-$debts2),2,'.',DLMTR);
						$strings[$strnum][3]='';
						$strings[$strnum][4]=$debts1-$debts2;
						if(++$strnum==$param['�����']) {
							make_page($strings); $strings=[]; $strnum=0;
						}
					}
				}
				if($param['����������']=='��') {
					$result = $mysqli->query("SELECT SUM(`�������`) FROM `Balances` WHERE `����`='$startDate' AND `����` LIKE '60%' AND `�������`>0 LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
					$line = $result->fetch_array(MYSQLI_NUM);
					$credts1=$line[0];
					if($Mode=='Quartal') $query="SELECT SUM(`�������`) FROM `Balances` WHERE `����`='$curDate' AND `����` LIKE '60%' AND `�������`>0 LIMIT 1";
					else $query="SELECT SUM(`������`) FROM `Accounts` WHERE `����` LIKE '60%' AND `������`>0 LIMIT 1";
					$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
					$line = $result->fetch_array(MYSQLI_NUM);
					$credts2=$line[0];
					if($credts1>0 || $credts2>0) {
						$strings[$strnum]=[];
						$strings[$strnum][0]=$num++;
						$strings[$strnum][1]=toOurDate($endDate);
						$strings[$strnum][2]="���������� ������� �� ��.60 �� ".toOurDate($startDate).": ".number_format($credts1,2,'.',DLMTR)."<br>���������� ������� �� ��.60 �� ".toOurDate($curDate).": ".number_format($credts2,2,'.',DLMTR)."<br>".($credts1>$credts2?'����������':'����������')." ���������� �������� � $m ��������: ".number_format(abs($credts1-$credts2),2,'.',DLMTR);
						$strings[$strnum][3]='';
						$strings[$strnum][4]=$credts1-$credts2;
						if(++$strnum==$param['�����']) {
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
		echo "<tr><td style='font-weight:bold;width:60%'>��� ��� ������������ �����</td><td><input type='text' name='Year' format='year' value='$Year'></td></tr>";
		echo "<tr><td colspan=2 style='font-weight:bold;'><br>��������!<br>��� �������� ������������� <u>�������������</u> (������, ������)</td></tr>";
		echo "</table>";
		if(!$Year) echo "<button onclick='kudir2()'>������������</button>";
		else {
			$mysqli = new mysqli(SERVER, USERNAME, DBPASS,DBNAME) or die('������ ����������� (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
			$result = $mysqli->query("SELECT `����`,`�����`,`����������` FROM `Entries` WHERE YEAR(`����`)='$Year' AND `�����` LIKE '01%' ORDER BY `����`") or die('������ MySQL: ' . $mysqli->error);
			echo "<div class='kudir-header'>II. ������ �������� �� ������������ (����������, ������������) �������� �������<br>� �� ������������ (�������� ����� ������������������) �������������� �������,<br>����������� ��� ���������� ��������� ���� �� ������<br>�� $Year ���</div>";
			echo "<table class='kudir2' border=1 cellpadding=1>";
			echo "<tr><th rowspan=2>� �/�</th>"; // 1
			echo "<th rowspan=2>�������-����� ������� �������� ������� ��� ��������-������ �������</th>"; // 2
			echo "<th rowspan=2>���� ������ ������� �������� ������� ��� ��������-������ �������</th>"; //3
			echo "<th rowspan=2>���� ������ �������-��� �� �������-�������� ��������-��� �������� �������� �������</th>"; //4
			echo "<th rowspan=2>���� ����� � ������-������ (�������� � ������-�������� �����) ������� �������� ������� ��� ������-�������� �������</th>"; //5
			echo "<th rowspan=2>�������-������� ��������� ������� �������� ������� ��� ������-�������� ������� (���.)</th>"; //6
			echo "<th rowspan=2>���� ��������� ��������-����� ������� �������� ������� ��� ������-�������� ������� (���������� ���)</th>"; //7
			echo "<th rowspan=2>�������-��� ��������� ������� �������� ������� ��� ������-�������� ������� (���.)</th>"; //8
			echo "<th rowspan=2>���������� ��������� ���������-��� ������� �������� ������� ��� ������-�������� ������� � ��������� �������</th>"; //9
			echo "<th rowspan=2>���� ��������� ������� �������� ������� ��� ������-�������� �������, �����-������ � ������� �� ��������� ������ (%)</th>"; //10
			echo "<th rowspan=2>���� ��������� ������� �������� ������� ��� ������-�������� �������, �����-������ � ������� �� ������ ������� ���������� ������� (%) (��. 10 / ��. 9)</th>"; //11
			echo "<th colspan=2>����� ��������, ����������� ��� ���������� ��������� ���� (���.)</th>"; //12-13
			echo "<th rowspan=2>�������� � ������� �� �����-����� ��������� ������� ���������� ���������� ������� ������-��������� (���.) (��. 13 ������� �� ���������� ��������� �������)</th>"; //14
			echo "<th rowspan=2>���������� ����� ��������, ���������� �������� � �����-������ ��������� �������� (���.) (��. 8 - ��. 13 - ��. 14)</th>"; //15
			echo "<th rowspan=2>���� ������� (�����-�����) ������� �������� ������� ��� ������-�������� �������</th>"; //16
			echo "</tr><tr><th>�� ������ ������� ���������� ������� (��. 6 ��� ��. 8 � ��. 11 / 100)</th>"; //12
			echo "<th>�� ��������� ������ (��. 12 � ��. 9)</th></tr><tr>"; //13
			for($i=1;$i<17;$i++) echo "<td>$i</td>";
			echo "</tr>";
			$n=1;
			while(($line = $result->fetch_array(MYSQLI_NUM)) && $param['������']=='�������') {
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
			echo "<tr><td>����� �� �����-��� (����-�����) ������</td><td>x</td><td>x</td><td>x</td><td>x</td>";	//1-5
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
		echo "<tr><td style='font-weight:bold;width:60%'>��� ��� ������������ �����</td><td><input type='text' name='Year' format='year' value='$Year'></td></tr>";
		echo "<tr><td colspan=2 style='font-weight:bold;'><br>��������!<br>��� �������� ������������� <u>�����������</u> (�����, �������)</td></tr>";
		echo "</table>";
		if(!$Year) echo "<button onclick='kudir3()'>������������</button>";
		else {
			echo "<div class='kudir-header'>III. ������ ����� ������, �����������<br>��������� ���� �� ������, ������������� � �����<br>� ����������� ���������� ������� ���������������<br>� $Year ����</div>";
			echo "<table class='kudir3' border=1 cellpadding=4>";
			echo "<tr><td>������������ ����������</td><td>���<br>������</td><td>��������<br>�����������</td></tr>";
			echo "<tr><td>1</td><td style='width:11%'>2</td><td style='width:22%'>3</td></tr>";
			echo "<tr><td class='t'>����� �������, ���������� �� ������ ���������� ��������� ��������, ������� �� ���� ���������� �� ������ ��������� ���������� ������� - �����:<br>(����� �� ����� ����� 020 - 110)</td><td>010</td><td formula='#(3,3)+#(4,3)+#(5,3)+#(6,3)+#(7,3)+#(8,3)+#(9,3)+#(10,3)+#(11,3)+#(12,3)'>&nbsp;</td></tr>";
			echo "<tr><td class='t'>� ��� ����� ��:</td><td>&nbsp;</td><td>&nbsp;</td></tr>";
			for($i=1;$i<11;$i++) echo "<tr><td class='t' edit>�� 20__ ���</td><td>".sprintf("%02d",$i+1)."0</td><td edit format='decimal'>&nbsp;</td></tr>";
			echo "<tr><td class='t'>��������� ���� �� �������� ��������� ������, ������� ����� ���� ��������� �� ������ ���������� ��������� ��������<br>(��� ���. 040 ���������� ����� ������� I ����� ������� � ��������)</td><td>120</td><td edit format='decimal'>&nbsp;</td></tr>";
			echo "<tr><td class='t'>����� �������, �� ������� ���������������� ���������� �������� ��������� ���� �� �������� ��������� ������<br>(� �������� ����� �������, ��������� �� ���. 010)</td><td>130</td><td edit format='decimal'>&nbsp;</td></tr>";
			echo "<tr><td class='t'>����� ������ �� �������� ��������� ������<br>(��� ���. 041 ���������� ����� ������� I ����� ����� ������� � ��������)</td><td>140</td><td edit format='decimal'>&nbsp;</td></tr>";
			echo "<tr><td class='t'>����� ������� �� ������ ���������� ���������� �������, ������� ���������������� ������ ��������� �� ������� ��������� �������<br>(��� ���. 010 - ��� ���. 130 + ��� ���. 140) �����:</td><td>150</td><td format='decimal' formula='#(1,3)-#(14,3)+#(15,3)'>&nbsp;</td></tr>";
			echo "<tr><td class='t'>� ��� ����� ��:</td><td>&nbsp;</td><td>&nbsp;</td></tr>";
			for($i=1;$i<11;$i++) echo "<tr><td class='t' edit>�� 20__ ���</td><td>".sprintf("%02d",$i+15)."0</td><td edit format='decimal'>&nbsp;</td></tr>";
			echo "</table>";
		}
	}
	elseif(isset($_GET['Kudir4'])) {
		if(!$param=read_param()) exit;
		if(preg_match("/^20\d\d$/",$_GET['Kudir4'])) $Year=$_GET['Kudir4'];
		echo "<table class='npr' cellpadding=4 border=0 style='margin-bottom:2em'>";
		echo "<tr><td style='font-weight:bold;width:60%'>��� ��� ������������ �����</td><td><input type='text' name='Year' format='year' value='$Year'></td></tr>";
		echo "<tr><td colspan=2 style='font-weight:bold;'><br>��������!<br>��� �������� ������������� <u>�������������</u> (������, ������)</td></tr>";
		echo "</table>";
		if(!$Year) echo "<button onclick='kudir4()'>������������</button>";
		else {
			echo "<div class='kudir-header'>IV. �������, ��������������� ������� 3.1 ������ 346.21 ���������� ������� ���������� ���������, ����������� �����<br>������, ������������� � ����� � ����������� ���������� ������� ��������������� (��������� �������� �� ������)<br>� $Year ����</div>";
			echo "<table class='kudir2' border=1 cellpadding=4>";
			echo "<tr><th rowspan=2>�<br><nobr>�/�</nobr></th><th rowspan=2>���� � ����� ������-���� ����-�����</th>";	//	1-2
			echo "<th rowspan=2>������, �� ������� ����������� ������ ��������� �������, ������� ������� �� ��������� �������-�����������, ���������-������ � ������ 4 - 9</th>"; //	3
			echo "<th colspan=6>�����</th><th rowspan=2>�����<br>(���.)</th></tr>";
			echo "<tr><th>��������� ������ �� ������������ ���������� ����������� (���.)</th>"; //	4
			echo "<th>��������� ������ �� ������������ ���������� ����������� �� ������ ��������� �������-����������� � � ����� � ������������ (���.)</th>"; //	5
			echo "<th>��������� ������ �� ������������ ����������� ����������� (���.)</th>"; //	6
			echo "<th>��������� ������ �� ������������ ���������� ����������� �� ���������� ������� �� ������������ � ���������-������� ����������� (���.)</th>"; //	7
			echo "<th>������� �� ������� ������� �� ��������� �������-����������� (���.)</th>"; //	8
			echo "<th>������� (������) �� ��������� ������������� ������� ����������� (���.)</th></tr><tr>"; //	9
			for($i=1;$i<11;$i++) echo "<td>$i</td>"; echo "</tr>";
			$mysqli = new mysqli(SERVER, USERNAME, DBPASS,DBNAME) or die('������ ����������� (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
			$num=1; $row=1; $top=1;
			for($quart=1;$quart<5;$quart++) {
				if($param['������']=='������') {
					$date1=$Year."-".sprintf("%02d",($quart-1)*3+1)."-01";
					$date2=$Year."-".sprintf("%02d",$quart*3)."-31";
					$result = $mysqli->query("SELECT `����`,`�����`,`����������` FROM `Entries` WHERE `����`>='$date1' AND `����`<='$date2' AND `������` REGEXP '51|50' AND (`�����` LIKE '69%' OR `����������` REGEXP '���|���|���|���|���|���|������') ORDER BY `����`") or die('������ MySQL: ' . $mysqli->error);
					while($line = $result->fetch_array(MYSQLI_NUM)) {
						if(strpos($line[2],'���������')!==false && preg_match("/20\d\d/",$line[2],$match)) $year=$match[0]; else $year=$Year;
						echo "<tr><td>$num</td><td>".toOurDate($line[0])."</td><td>$year ���</td>";
						$sum=number_format($line[1],2,'.',DLMTR);
						if(preg_match("/���|���/",$line[2])) $col=4;
						elseif(preg_match("/���|���/",$line[2])) { if(strpos($line[2],'��������')!==false) $col=7; else $col=5; }
						elseif(strpos($line[2],'���')!==false) $col=6;
						elseif(strpos($line[2],'������')!==false) $col=8;
						else $col=9;
						for($i=4;$i<10;$i++) echo "<td class='d' edit format='decimal'>".($col==$i?$sum:'&nbsp;')."</td>";
						echo "<td class='d' format='decimal' formula='#(4)+#(5)+#(6)+#(7)+#(8)+#(9)'>&nbsp;</td></tr>";
						$num++; $row++;
					}
				}
				switch($quart) {
					case 1: $rom='I'; break;
					case 2:	$rom='II'; $term='���������'; break;
					case 3: $rom='III'; $term='9 �������'; break;
					case 4: $rom='IV'; $term='���'; break;
				}
				echo "<tr><td colspan=3 class='t'>����� �� $rom �������</td>";
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
					echo "<tr><td colspan=3 class='t'>����� �� $term</td>";
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
		echo "<tr><td style='font-weight:bold;width:60%'>��� ��� ������������ �����</td><td><input type='text' name='Year' format='year' value='$Year'></td></tr>";
		echo "<tr><td colspan=2 style='font-weight:bold;'><br>��������!<br>��� �������� ������������� <u>�����������</u> (�����, �������)</td></tr>";
		echo "</table>";
		if(!$Year) echo "<button onclick='kudir5()'>������������</button>";
		else {
			echo "<div class='kudir-header'>V. ����� ��������� �����, ����������� ����� ������, ������������� � ����� � ����������� ���������� ������� ��������������� (��������� �������� �� ������), ������������ �� ������� ��������������� �� ���� ������������������� ������������, � ��������� �������� ���������� �������� ����<br>�� $Year ���</div>";
			echo "<table class='kudir3' border=1 cellpadding=4 style='width:80%'>";
			echo "<tr><th>� �/�</td><td>���� � �����<br>���������� ���������</td><td>������, �� ������� �����������<br>������ ��������� �����</td><td>����� �����������<br>��������� �����</td></tr>";
			echo "<tr><td>1</td><td>2</td><td>3</td><td>4</td></tr>";
			$mysqli = new mysqli(SERVER, USERNAME, DBPASS,DBNAME) or die('������ ����������� (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
			$num=1; $row=1; $top=1;
			for($quart=1;$quart<5;$quart++) {
				if($param['������']=='������') {
					$date1=$Year."-".sprintf("%02d",($quart-1)*3+1)."-01";
					$date2=$Year."-".sprintf("%02d",$quart*3)."-31";
					$result = $mysqli->query("SELECT `����`,`�����`,`����������` FROM `Entries` WHERE `����`>='$date1' AND `����`<='$date2' AND `������` REGEXP '51|50' AND `����������` REGEXP '������.{2,3} +����' ORDER BY `����`") or die('������ MySQL: ' . $mysqli->error);
					while($line = $result->fetch_array(MYSQLI_NUM)) {
						echo "<tr><td>$num</td><td>".toOurDate($line[0])."</td><td edit>&nbsp;</td><td>".number_format($line[1],2,'.',DLMTR)."</td></tr>";
						$num++; $row++;
					}
				}
				switch($quart) {
					case 1: $rom='I'; break;
					case 2:	$rom='II'; $term='���������'; break;
					case 3: $rom='III'; $term='9 �������'; break;
					case 4: $rom='IV'; $term='���'; break;
				}
				echo "<tr><td colspan=3 class='t'>����� �� $rom �������</td>";
				if($row==$top)  echo "<td>&nbsp;</td>";
				else {
					$cells=[];
					for($j=$top;$j<$row;$j++) $cells[$j]="#($j,4)";
					echo "<td class='d' format='decimal' formula='".implode('+',$cells)."'>";
				}
				echo "</tr>";
				$row++;
				if($quart>1) {
					echo "<tr><td colspan=3 class='t'>����� �� $term</td><td class='d' format='decimal' formula='#(".($top-1).",2)+#(".($row-1).",2)'>";
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
				if($key=='����') echo "<tr><td style='font-weight:bold'>���� ��:</td><td><input type='text' name='����1' format='$value'> <b>��:</b> <input type='text' name='����2' format='$value'></td></tr>";
				elseif($key=='����_�����') echo "<tr><td style='font-weight:bold'>����_����� ��:</td><td><input type='text' name='�����1' format='$value'> <b>��:</b> <input type='text' name='�����2' format='$value'></td></tr>";
				else echo "<tr><td style='font-weight:bold'>$key:</td><td><input type='text' name='$key' format='$value'></td></tr>";
			}
			echo "</table>";
			echo "<button onclick='goFindEntry()' style='margin: 20px 37% 0'>�����</button>";
		}
		else {
			$mysqli = new mysqli(SERVER, USERNAME, DBPASS,DBNAME) or die('������ ����������� (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
			$finfo=get_field_info('Entries');
			$finfo['����1']=$finfo['����2']=$finfo['�����1']=$finfo['�����2']='date';
			$query="SELECT * FROM `Entries` WHERE";
			foreach($_POST as $key=>$value) {
				if(isset($finfo[$key=iconv('UTF-8', 'windows-1251', $key)])) {
					$value=iconv('UTF-8', 'windows-1251', $value);
					$value=trim(preg_replace("/<.+>/",'',$mysqli->real_escape_string($value)));
					if($finfo[$key]=='date' && $value!='') $value=toSQLDate($value);
					elseif(substr($finfo[$key],0,7)=='decimal') $value=preg_replace("/[\s`]/",'',$value);
					if(!preg_match($reserved,$value) && $value!='') {
						if($key=='����1') $query.=" `����`>='$value' AND";
						elseif($key=='����2') $query.=" `����`<='$value' AND";
						elseif($key=='�����1') $query.=" `����_�����`>='$value' AND";
						elseif($key=='�����2') $query.=" `����_�����`<='$value' AND";
						elseif($key=='����������') $query.=" `����������` LIKE '%$value%' AND";
						else $query.=" `$key`='$value' AND"; 
					}
				}
			}
			if(strlen($query)<30) exit;
			$query=substr($query,0,strlen($query)-4);
			$query.=' ORDER BY `����`';
			$reply['Entries']=show_all($query);
			echo php2json($reply);
		}
	}
	elseif(isset($_GET['CounterAgents'])) {
		$query="SELECT `����`,`������������`,`�����`,`������`,`���`  FROM `Accounts` AS `Goods` WHERE `����` LIKE '60.%' OR `����` LIKE '62.%'";
		echo show_all($query);
	}
	elseif(isset($_GET['LoadBank'])) {
		if(!isset($_GET['BankLoaded'])) {
			if (!isset($_FILES["userfile"])) {
				echo "<form action='?LoadBank' method='POST' enctype='multipart/form-data' target='i_frame' onsubmit='cw.isLoading()' style='margin:10px'>";
				echo "<input type='hidden' name='MAX_FILE_SIZE' value='64000'>";
				echo "�������� ���� � �������� ����� ��� ��������: <input type='file' name='userfile' format='file'> ";
				echo "<input type='submit' value='���������'></form>";
				echo "<iframe name='i_frame' width='100%' height='90%' src='about:blanc' style='border:0'>Oops...</iframe>";
				exit;
			}
			else {
				if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
					$filename = $_FILES['userfile']['tmp_name'];
					if(!preg_match("/^kl_to_1c.*\.txt$/",$_FILES['userfile']['name'])) echo '����� ������� ������ ���� ���� "kl_to_1c*.txt"';
					elseif (!move_uploaded_file($filename, "upload/bankstatement.txt")) echo '������ ��� ����������� �����';
					else $loaded=true;
				} 
				else echo "������ ��� �������� �����";
			}
		}
		if($loaded || isset($_GET['BankLoaded'])) {
			$bs=file("upload/bankstatement.txt",FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
			$bankst=[]; $sdi=0;
			for($i=0;$i<count($bs);$i++) {
				if($i==0 && $bs[0]!='1CClientBankExchange') { echo "����������� ������ �����"; break; }
				list($key,$value)=explode('=',$bs[$i]);
				if(!isset($srs) && $bs[$i]=='��������������') { $srs=[]; continue; }
				if(isset($srs)) {
					if($key=='�������������') { $bankst['��������������']=$srs; unset($srs); continue; }
					$srs[$key]=str_replace('\\','/',$value); continue;
				}
				if(!$sd && $key=='��������������') { $sd=[]; $sd['��������']=$value; continue; }
				if($sd) {
					if($key=='��������������') { $bankst['��������������'][$sdi++]=$sd; unset($sd); continue; }
					$sd[$key]=str_replace('\\','/',$value); continue;
				}
			}
			$mysqli = new mysqli(SERVER, USERNAME, DBPASS,DBNAME) or die('������ ����������� (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
			if(isset($_GET['BindAccount']) && preg_match("/^51\.\d+\.\d+$|^51\.\d+$|^51$/",$_GET['BindAccount'])) {
				$result=$mysqli->query("UPDATE `Accounts` SET `���`='{$bankst['��������������']['��������']}' WHERE `����`='{$_GET['BindAccount']}' LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
			}
			$result=$mysqli->query("SELECT `����` FROM `Accounts` WHERE `���`='{$bankst['��������������']['��������']}' LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
		    if(!($line=$result->fetch_array(MYSQLI_NUM))) {
				echo "<table class='npr' cellpadding=4 border=0>";
				echo "<tr class='noHL'><td style='font-weight:bold'>������� ����, � �������� ����������<br>��������� ����������� ������� �����</td><td><input type='text' name='�����' format='varchar(10)'></td></tr>";
				echo "</table>";
				echo "<button onclick='bindAccount()'>��������� ����</button>";
			}
			else {
				$Acc=$line[0];
				echo "����������� ������� �� ����� {$Acc}, ����� ����� � ����� {$bankst['��������������']['��������']}<br>";
				$First=toSQLDate($bankst['��������������']['����������']); 
				$End=toSQLDate($bankst['��������������']['���������']);
				$fd=substr($First,0,8).'01';
				$result = $mysqli->query("SELECT `�������` FROM `Balances` WHERE `����`='$fd' AND `����`='$Acc' LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
			    $line = $result->fetch_array(MYSQLI_NUM);
				$balance=$line[0];
				$result = $mysqli->query("SELECT SUM(`�����`) FROM `Entries` WHERE `����`>='$fd' AND `����`<'$First' AND `�����`='$Acc' LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
			    $line = $result->fetch_array(MYSQLI_NUM);
				$balance-=$line[0];
				$result = $mysqli->query("SELECT SUM(`�����`) FROM `Entries` WHERE `����`>='$fd' AND `����`<'$First' AND `������`='$Acc' LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
			    $line = $result->fetch_array(MYSQLI_NUM);
				$balance+=$line[0];
				echo "��������� ������� �� {$bankst['��������������']['����������']} � �����: ".(-$balance).", � �����: {$bankst['��������������']['����������������']} -- ";
				if(abs($balance+$bankst['��������������']['����������������'])>0.005) echo "<b style='color:red'>�� ���������</b><br>��������� ������� � ����� ������ ��������� �����";
				else {
					echo "<b>���������</b><br>";
					$result = $mysqli->query("TRUNCATE TABLE `LoadBank`");
					for($i=0;$i<count($bankst['��������������']);$i++) {
						$doc=$bankst['��������������'][$i]; $params=[]; $inout=0;
						if($doc['��������������']==$bankst['��������������']['��������'] || $doc['������������������']==$bankst['��������������']['��������']) $inout=-1;
						elseif($doc['��������������']==$bankst['��������������']['��������'] || $doc['������������������']==$bankst['��������������']['��������']) $inout=1;
						else echo "������������ ��������:<br><pre>".print_r($doc,true)."</pre><br>";
						if($inout!=0) {
							if($inout<0) {
								$result = $mysqli->query("SELECT `����` FROM `Accounts` WHERE `���`='{$doc['�������������']}' LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
							    $line = $result->fetch_array(MYSQLI_NUM);
								$params['����']=toSQLDate($doc['�����������']);
								$params['�����']=$line?$line[0]:'';
								$params['������']=$Acc;
							}
							else {
								$result = $mysqli->query("SELECT `����` FROM `Accounts` WHERE `���`='{$doc['�������������']}' LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
							    $line = $result->fetch_array(MYSQLI_NUM);
								$params['����']=toSQLDate($doc['�������������']);
								$params['�����']=$Acc;
								$params['������']=$line?$line[0]:'';
							}
							$params['�����']=$doc['�����'];
							$params['��������']=$doc['�����'];
							$params['�����������������']=$doc['�����������������'];
							$query="SELECT `�����`,`������`,`����������` FROM `Entries` WHERE `����`='{$params['����']}' AND `�����`='{$params['�����']}'";
							if($params['�����']!='') $query.=" AND `�����`='{$params['�����']}'";
							if($params['������']!='') $query.=" AND `������`='{$params['������']}'";
							$query.=" LIMIT 1";
							$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
						    $line = $result->fetch_array(MYSQLI_NUM);
							$EntryExists=(boolean)$line;
							if($EntryExists) {
								$params['��������']='���������';
								$params['�����']=$line[0];
								$params['������']=$line[1];
								$params['����������']=$line[2];
							}
							else {
								$result = $mysqli->query("SELECT `����������` FROM `Stencil` WHERE `�����`='{$params['�����']}' AND `������`='{$params['������']}' LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
							    $line = $result->fetch_array(MYSQLI_NUM);
								if($line) $params['����������']=apply_stencil($line[0],$params);
								elseif($inout<0) $params['����������']="������ {$doc['����������']} {$doc['�����������������']}";
								else $params['����������']="����������� {$doc['����������']} {$doc['�����������������']}";
							}
							if($params['��������']!='���������') {
								$query="INSERT INTO `LoadBank` SET";
								foreach($params as $key=>$value) $query.=" `$key`='$value',";
								$query=substr($query,0,strlen($query)-1);
								$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
							}
						}
					}
					echo show_all("SELECT * FROM `LoadBank`");
					$result = $mysqli->query("SELECT `�����` FROM `Accounts` WHERE `����`='$Acc' LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
				    $line = $result->fetch_array(MYSQLI_NUM);
					$balance=-$line[0];
					$result = $mysqli->query("SELECT SUM(`�����`) FROM `LoadBank` WHERE `�����`='$Acc' AND `��������`!='���������' LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
				    $line = $result->fetch_array(MYSQLI_NUM);
					$balance-=$line[0];
					$result = $mysqli->query("SELECT SUM(`�����`) FROM `LoadBank` WHERE `������`='$Acc' AND `��������`!='���������' LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
				    $line = $result->fetch_array(MYSQLI_NUM);
					$balance+=$line[0];
					$result = $mysqli->query("SELECT MAX(`����`) FROM `Entries` WHERE `������`='$Acc' OR `�����`='$Acc' LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
				    $line = $result->fetch_array(MYSQLI_NUM);
					echo "�������� ������� �� ".toOurDate(max(toSQLDate($bankst['��������������']['���������']),$line[0]))." � �����: ".(-$balance).", � �����: {$bankst['��������������']['���������������']} -- ";
					if(abs($balance+$bankst['��������������']['���������������'])>0.005) echo "<b style='color:red'>�� ���������</b>"; else echo "<b>���������</b>";
					echo "<br><button onclick='loadBankToAccount()' disabled>��������� � ����</button>";
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
		$mysqli = new mysqli(SERVER, USERNAME, DBPASS,DBNAME) or die('������ ����������� (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
		$result = $mysqli->query("SELECT `����`,`�����`,`������`,`�����`,`����������` FROM `LoadBank`") or die('������ MySQL: ' . $mysqli->error);
		while($params = $result->fetch_array(MYSQLI_ASSOC)) makeEntry($params);
		liveVidgets(ACCOUNTS | ENTRIES | GOODS | CTRAGENTS);
		$reply['reply']="���������� � ��������� {$result->num_rows} ����� ���������� �������";
		echo php2json($reply);
	}
	elseif($extended) include('extended.php');
	exit;
}
function apply_stencil($content,$params) {
	$str=$content;
	while(preg_match("/{[�-��-�\d\-\*\/\.\,]+}/",$str,$matches)) {
		if(preg_match("/[�-��-�]+/",$matches[0],$field)) {
			if($field[0]=='����') {
				$value=toOurDate($params['����']);
				if(preg_match("/\-\d+/",$matches[0],$operand)) $operand=substr($operand[0],1);
				if($operand) $value=date("d.m.Y",mktime(0,0,0,substr($value,3,2),substr($value,0,2)-$operand,substr($value,6)));
				else {
					if(preg_match("/\,\d+/",$matches[0],$operand)) $operand=substr($operand[0],1);
					if($operand) {
						$value=date("d.m.Y",mktime(0,0,0,substr($value,3,2),$operand,substr($value,6)));
						if(substr($value,3,2)>substr($params['����'],5,2)) $value=date("d.m.Y",mktime(0,0,0,substr($value,3,2),0,substr($value,6)));
					}
				}
			}
			elseif($field[0]=='�����') {
				$value=$params['�����'];
				if(preg_match("/\*[\d\.\,]+/",$matches[0],$operand)) $operand=str_replace(',','.',substr($operand[0],1));
				if($operand) $value=sprintf("%01.2f",$value*$operand);
				else {
					if(preg_match("/\/[\d\.\,]+/",$matches[0],$operand)) $operand=str_replace(',','.',substr($operand[0],1));
					if($operand) $value=sprintf("%01.2f",$value/$operand);
				}
			}
			elseif($field[0]=='��������') $value=$params['��������'];
			elseif(preg_match("/{$field[0]}\s+([\d,\.]+)/",$params['�����������������'],$value)) $value=$value[1];
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
			else echo "���������� ������ � ���������� �����:<br>".$kudirdata[$i]."<br>";
		}
	}
	else { if($flag) echo "���������� ��������� ��������� ��������� �������� � ������� �������� � ������ � �������"; $param=null; }
	return $param;
}
function calc_rule($rule,$str,$sum) {
	preg_match_all("/[a-zA-Z�-��-�]+|\d+[\.,]?\d*/",$rule,$operand,PREG_SET_ORDER);
	if(!preg_match("/[\+\-\*\/]/",$rule,$operator)) $operator=false;
	for($i=0;$i<count($operand);$i++) {
		if($operand[$i][0]=='�����') $operand[$i]=$sum;
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
			if(preg_match("/^�:|^�:/",$arr[$j])) {
				if(strpos($arr[$j],'*')!==false) $ret_rules[$i][substr($arr[$j],0,1)]="/.+/";
				else {
					$accs=explode(",",substr(str_replace('.',"\.",$arr[$j]),2));
					$ret_rules[$i][substr($arr[$j],0,1)]="/^".implode("|^",$accs)."/";
				}
			}
			elseif(preg_match("/^�:/",$arr[$j])) $ret_rules[$i]['�']=substr($arr[$j],2);
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
			<div class='kudir-upheader'>���� <? echo $pageNum++?></div>
			<? if($pageNum==3) echo "<div class='kudir-header'>I. ������ � �������</div>"; ?>
			<table class='kudir-body' border=1>
			<tr><td colspan=3>�����������</td><td colspan=2>�����</td></tr>
			<tr><td style='width:5%'>�<br>�/�</td><td style='width:14%'>���� � ����� ���������� ���������</td><td>���������� ��������</td><td style='width:14%'>������, ����������� ��� ���������� ��������� ����</td><td style='width:14%'>�������, ����������� ��� ���������� ��������� ����</td></tr>
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
				echo "<tr><td>&nbsp;</td><td>&nbsp;</td><td class='d'>����� �� ��������:</td><td class='d'>".number_format($revPage,2,'.',DLMTR)."</td><td class='d'>".number_format($expPage,2,'.',DLMTR)."</td></tr>";
				if($mm>=0) {
					if($mm>0) {
						echo "<tr><td>&nbsp;</td><td>&nbsp;</td><td class='d'>����� �� $mm �������:</td><td class='d'>".number_format($revQuart,2,'.',DLMTR)."</td><td class='d'>".number_format($expQuart,2,'.',DLMTR)."</td></tr>";
						$revQuart=0; $expQuart=0;
					}
					switch($mm) {
						case 2: $mmm='�� ���������'; break;
						case 3: $mmm='�� 9 �������'; break;
						case 4: $mmm='�� ���'; break;
					}
					if($mm==0 || $mm>1) echo "<tr><td>&nbsp;</td><td>&nbsp;</td><td class='d'>����� $mmm:</td><td class='d'>".number_format($revTotal,2,'.',DLMTR)."</td><td class='d'>".number_format($expTotal,2,'.',DLMTR)."</td></tr>";
				}
			 ?>
			</table>
			<? if(isset($mm) && ($mm==4 || $mm==0)) { ?>
				<table class='kudir-body'>
				<tbody>
				<tr><td style='width:6%'>&nbsp;</td><td style='width:70%'>&nbsp;</td><td>&nbsp;</td></tr>
				<tr><td>&nbsp;</td><td class='t'>������� � ������� I</td><td>&nbsp;</td></tr>
				<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
				<tr><td>010</td><td class='t'>����� ���������� ������� �� ��������� ������</td><td class='d'><? echo number_format($revTotal,2,'.',DLMTR); ?></td></tr>
				<tr><td>020</td><td class='t'>����� ������������� �������� �� ��������� ������</td><td class='d'><? echo number_format($expTotal,2,'.',DLMTR); ?></td></tr>
				<tr><td>030</td><td class='t'>����� ������� ����� ������ ����������� ������������ ������ � ������ ������������ � ����� ������� ������ �� ���������� ��������� ������</td><td class='d' <? if($param['������']=='�������') echo " edit format='decimal'"; ?> >0.00</td></tr>
				<tr><td>&nbsp;</td><td class='t'>����� ��������:</td><td>&nbsp;</td></tr>
				<tr><td>040</td><td class='t'>- �������</td><td>&nbsp;</td></tr>
				<tr><td>&nbsp;</td><td class='t'>(��� ���. 010 - ��� ���. 020 - ��� ���. 030)</td><td class='d' format='decimal' formula='(#(4,3)-#(5,3)-#(6,3))>=0?#(4,3)-#(5,3)-#(6,3):"-"'>&nbsp;</td></tr>
				<tr><td>041</td><td class='t'>- �������</td><td>&nbsp;</td></tr>
				<tr><td>&nbsp;</td><td class='t'>(��� ���. 020 + ��� ���. 030) - ��� ���. 010)</td><td class='d' format='decimal' formula='(#(4,3)-#(5,3)-#(6,3))<0?-#(4,3)+#(5,3)+#(6,3):"-"'>&nbsp;</td></tr>
				</tbody>
				<tbody class='npr' style='background-color:#e0e0f0;'>
				<tr><td colspan=3>&nbsp;</td></tr>
				<tr><td>&nbsp;</td><td colspan=2 class='t'>�������������� ���������� (�� ����������) </td></tr>
				<tr><td colspan=3>&nbsp;</td></tr>
				<? if($param['������']=='������') {
					echo "<tr><td>&nbsp;</td><td class='t'>����� 6%</td><td class='d'>".number_format(round($revTotal*0.06),2,'.',DLMTR)."</td></tr>";
					echo "<tr><td>&nbsp;</td><td class='t'>����� �������</td><td class='d'>".number_format($taxPayed,2,'.',DLMTR)."</td></tr>";
					echo "<tr><td>&nbsp;</td><td class='t'>������ ��������</td><td class='d'>".number_format($pfrPayed,2,'.',DLMTR)."</td></tr>";
					echo "<tr><td>&nbsp;</td><td class='t'>�������� ���� �������</td><td class='d'>".number_format($duePayed,2,'.',DLMTR)."</td></tr>";
					echo "<tr><td>&nbsp;</td><td class='t'>����� � ������</td><td class='d'>".((round($revTotal*0.06)-$taxPayed-$pfrPayed-$duePayed)>0?number_format(round($revTotal*0.06)-$taxPayed-$pfrPayed-$duePayed,2,'.',DLMTR):'0.00')."</td></tr>";
				}
				else {
				   	echo "<tr><td>&nbsp;</td><td class='t'>����� 15%</td><td class='d' formula='((#(4,3)-#(5,3)-#(6,3))*0.15).toFixed()'></td></tr>";
					echo "<tr><td>&nbsp;</td><td class='t'>����� � %% � �������</td><td class='d' formula='#(15,3)*100/#(4,3)'></td></tr>";
					echo "<tr><td>&nbsp;</td><td class='t'>����� �������</td><td class='d'>".number_format($taxPayed,2,'.',DLMTR)."</td></tr>";
					echo "<tr><td>&nbsp;</td><td class='t'>����� 15% � ������</td><td class='d' formula='#(15,3)-#(17,3)'></td></tr>";
					echo "<tr><td>&nbsp;</td><td class='t'>����������� �����</td><td class='d' formula='(#(4,3)*0.01).toFixed()'></td></tr>";
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
		$mysqli = new mysqli(SERVER, USERNAME, DBPASS,DBNAME) or die('������ ����������� (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
	    $result = $mysqli->query($query) or die("Query failed : " . $mysqli->error);
		if($result->num_rows>0) {
		    $str.="<table cellpadding=2 border=1>";
			if(strpos($query,'LIMIT')!==false && $result->num_rows==ELINES) $str.="<caption class=\"moreInfo\">�������� ���</caption>";
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
	$result = $mysqli->query("LOCK TABLES `Accounts` WRITE, `Entries` WRITE, `Balances` WRITE") or die('������ MySQL: ' . $mysqli->error);
	$query="INSERT INTO `Entries` SET";
	foreach($params as $key=>$value) $query.=" `$key`='$value',";
	$query=substr($query,0,strlen($query)-1);
	$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
	$result = $mysqli->query("SELECT `�����`,`������` FROM `Accounts` WHERE `����`='{$params['�����']}' LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
    $line = $result->fetch_array(MYSQLI_ASSOC);
	$value=-$line['�����']+$line['������']-$params['�����'];
	if($value<0) { $dbsumm=-$value; $crsumm=0; }
	else { $dbsumm=0; $crsumm=$value; }
//echo $params['�����']."<br>".$quantAccs[0]."<br>".$params['����������']; exit;
	if(in_array(substr($params['�����'],0,2),$quantAccs) && ($dbquan=$params['����������'])>0) {
		$query="UPDATE `Accounts` SET `�����`=$dbsumm, `������`=$crsumm, `����������`=`����������`+$dbquan WHERE `����`='{$params['�����']}' LIMIT 1";
	}
	else $query="UPDATE `Accounts` SET `�����`=$dbsumm, `������`=$crsumm WHERE `����`='{$params['�����']}' LIMIT 1";
	$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
	$result = $mysqli->query("SELECT `�����`,`������` FROM `Accounts` WHERE `����`='{$params['������']}' LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
    $line = $result->fetch_array(MYSQLI_ASSOC);
	$value=-$line['�����']+$line['������']+$params['�����'];
	if($value<0) { $dbsumm=-$value; $crsumm=0; }
	else { $dbsumm=0; $crsumm=$value; }
	if(in_array(substr($params['������'],0,2),$quantAccs) && ($crquan=$params['����������'])>0) {
		$query="UPDATE `Accounts` SET `�����`=$dbsumm, `������`=$crsumm, `����������`=`����������`-$crquan WHERE `����`='{$params['������']}' LIMIT 1";
	}
	else $query="UPDATE `Accounts` SET `�����`=$dbsumm, `������`=$crsumm WHERE `����`='{$params['������']}' LIMIT 1";
	$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
	$today=date("Y-m-d");
	$fd=substr($today,0,8).'01';
	if($params['����']<$fd) {
		$cd=$params['����'];
		while($cd<$fd) {
			list($year,$month,$day)=explode('-',$cd);
			if(++$month>12) { $year++; $month='01'; }
			if(strlen($month)<2) $month='0'.$month;
			$cd="$year-$month-01";
			if($dbquan>0) $query="UPDATE `Balances` SET `�������`=`�������`-{$params['�����']}, `����������`=`����������`+$dbquan WHERE `����`='$cd' AND `����`='{$params['�����']}' LIMIT 1";
			else $query="UPDATE `Balances` SET `�������`=`�������`-{$params['�����']} WHERE `����`='$cd' AND `����`='{$params['�����']}' LIMIT 1";
			$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
			if($mysqli->affected_rows==1) {
				$result = $mysqli->query("SELECT `�������` FROM `Balances` WHERE `����`='$cd' AND `����`='{$params['�����']}' LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
			    $line = $result->fetch_array(MYSQLI_NUM);
				if($line[0]==0) $result = $mysqli->query("DELETE FROM `Balances` WHERE `����`='$cd' AND `����`='{$params['�����']}' LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
			}
			else {
				if($dbquan>0) $query="INSERT INTO `Balances` SET `����`='$cd', `����`='{$params['�����']}', `�������`=-{$params['�����']}, `����������`=$dbquan";
				else $query="INSERT INTO `Balances` SET `����`='$cd', `����`='{$params['�����']}', `�������`=-{$params['�����']}";
				$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
			}
			
			if($crquan>0) $query="UPDATE `Balances` SET `�������`=`�������`+{$params['�����']}, `����������`=`����������`-$crquan WHERE `����`='$cd' AND `����`='{$params['������']}' LIMIT 1";
			else $query="UPDATE `Balances` SET `�������`=`�������`+{$params['�����']} WHERE `����`='$cd' AND `����`='{$params['������']}' LIMIT 1";
			$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
			if($mysqli->affected_rows==1) {
				$result = $mysqli->query("SELECT `�������` FROM `Balances` WHERE `����`='$cd' AND `����`='{$params['������']}' LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
			    $line = $result->fetch_array(MYSQLI_NUM);
				if($line[0]==0) $result = $mysqli->query("DELETE FROM `Balances` WHERE `����`='$cd' AND `����`='{$params['������']}' LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
			}
			else {
				if($crquan>0) $query="INSERT INTO `Balances` SET `����`='$cd', `����`='{$params['������']}', `�������`={$params['�����']}, `����������`=-$crquan";
				else $query="INSERT INTO `Balances` SET `����`='$cd', `����`='{$params['������']}', `�������`={$params['�����']}";
				$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
			}
		}
	}
	$result = $mysqli->query("UNLOCK TABLES") or die('������ MySQL: ' . $mysqli->error);
}
function makeBalances($date='') {
	global $mysqli;
	global $quantAccs;
	if(!isset($mysqli)) {
		$mysqli = new mysqli(SERVER, USERNAME, DBPASS,DBNAME) or die('������ ����������� (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
		$set=true;
	}
	$today=date("Y-m-d");
	$accs=[];
	$quants=[];
	if($date=='') {
		$result = $mysqli->query("SELECT MIN(`����`) FROM `Entries` LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
	    $line = $result->fetch_array(MYSQLI_NUM);
		$fd=$line[0];
	}
	else {
		$fd=substr($date,0,8).'01';
		$result = $mysqli->query("SELECT `����`, `�������`,`����������` FROM `Balances` WHERE `����`='$fd'") or die('������ MySQL: ' . $mysqli->error);
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
		$result = $mysqli->query("SELECT `�����`,`������`,`�����`,`����������` FROM `Entries` WHERE `����`>='$fd' AND `����`<'$ed'") or die('������ MySQL: ' . $mysqli->error);
	    while($line = $result->fetch_array(MYSQLI_NUM)) {
			$accs['#'.$line[0]]-=$line[2]; 
			if(in_array(substr($line[0],0,2),$quantAccs)) $quants['#'.$line[0]]+=$line[3];
			$accs['#'.$line[1]]+=$line[2]; 
			if(in_array(substr($line[1],0,2),$quantAccs)) $quants['#'.$line[1]]-=$line[3];
		}
		$result = $mysqli->query("DELETE FROM `Balances` WHERE `����`='$ed'") or die('������ MySQL: ' . $mysqli->error);
		if($ed<=$today) foreach($accs as $key=>$value) {
			$key=substr($key,1);
			$result = $mysqli->query("INSERT INTO `Balances` SET `����`='$ed', `����`='$key', `�������`='$value',`����������`='{$quants[$key]}'") or die('������ MySQL: ' . $mysqli->error);
		}
		elseif($date=='') {
			$result = $mysqli->query("SELECT `����`,`�����`,`������`,`����������` FROM `Accounts` WHERE `�����`!=0 OR `������`!=0") or die('������ MySQL: ' . $mysqli->error);
		    while($line = $result->fetch_array(MYSQLI_NUM)) {
				if(abs($line[2]-$line[1]-$accs['#'.$line[0]])>0.005) echo "������: ���� {$line[0]}=".($line[2]-$line[1]).", ������ ���� ".$accs['#'.$line[0]].", ������� ".($line[2]-$line[1]-$accs[$line[0]])."<br>";
				else unset($accs['#'.$line[0]]);
				if($line[3]!=$quants['#'.$line[0]] && $quants['#'.$line[0]]!=0) echo "������ ����������: ���� {$line[0]} -> {$line[3]}, ������ ���� ".$quants['#'.$line[0]]."<br>";
				else unset($quants['#'.$line[0]]);
			}
			if(count($accs)>0) foreach($accs as $key=>$value) if(abs($value)>0.005) echo "������: ���� ".substr($key,1)."=0, ������ ���� $value<br>";
			if(count($quants)>0) foreach($quants as $key=>$value) if($value>0) echo "������ ����������: ���� ".substr($key,1)." -> 0, ������ ���� $value<br>";
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
		$mysqli = new mysqli(SERVER, USERNAME, DBPASS,DBNAME) or die('������ ����������� (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
		$set=true;
	}
	$today=date("Y-m-d");
	$accs=[];
	$quants=[];
	if($date=='') {
		$result = $mysqli->query("SELECT MIN(`����`) FROM `Entries` LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
	    $line = $result->fetch_array(MYSQLI_NUM);
		$fd=$line[0];
	}
	else {
		$fd=substr($date,0,8).'01';
		$result = $mysqli->query("SELECT `����`, `�������` FROM `Balances` WHERE `����`='$fd'") or die('������ MySQL: ' . $mysqli->error);
	    while($line = $result->fetch_array(MYSQLI_NUM)) {
			$accs['#'.$line[0]]=$line[1];
		}
	}
	while($today>$fd) {
		list($year,$month,$day)=explode('-',$fd);
		if(++$month>12) { $year++; $month='01'; }
		if(strlen($month)<2)$month='0'.$month;
		$ed="$year-$month-01";
		$result = $mysqli->query("SELECT `�����`,`������`,`�����`,`����������` FROM `Entries` WHERE `����`>='$fd' AND `����`<'$ed'") or die('������ MySQL: ' . $mysqli->error);
	    while($line = $result->fetch_array(MYSQLI_NUM)) {
			$accs['#'.$line[0]]-=$line[2];
			if(in_array(substr($line[0],0,2),$quantAccs)) $quants['#'.$line[0]]+=$line[3];
			$accs['#'.$line[1]]+=$line[2];
			if(in_array(substr($line[1],0,2),$quantAccs)) $quants['#'.$line[1]]-=$line[3];
		}
		if($ed<=$today) {
			$result = $mysqli->query("SELECT `����`, `�������`,`����������` FROM `Balances` WHERE `����`='$ed'") or die('������ MySQL: ' . $mysqli->error);
		    while($line = $result->fetch_array(MYSQLI_NUM)) {
				if(abs($line[1]-$accs['#'.$line[0]])>0.005) echo "������ �� ���� $ed: ���� {$line[0]}={$line[1]}, ������ ���� ".$accs['#'.$line[0]].", ������� ".($line[1]-$accs['#'.$line[0]])."<br>";
			}
/*			foreach($accs as $key=>$value) {
				$key=substr($key,1);
				$result = $mysqli->query("SELECT `����`, `�������`,`����������` FROM `Balances` WHERE `����`='$ed' AND `����`='$key' LIMIT 1") or die('������ MySQL: ' . $mysqli->error);
				$line = $result->fetch_array(MYSQLI_NUM);
				if(abs($line[1]-$accs['#'.$line[0]])>0.005) echo "������ �� ���� $ed: ���� {$line[0]}={$line[1]}, ������ ���� ".$accs['#'.$line[0]].", ������� ".($line[1]-$accs['#'.$line[0]])."<br>";
			} */
		}
		else {
			$result = $mysqli->query("SELECT `����`,`�����`,`������`,`����������` FROM `Accounts` WHERE `�����`!=0 OR `������`!=0") or die('������ MySQL: ' . $mysqli->error);
		    while($line = $result->fetch_array(MYSQLI_NUM)) {
				if(abs($line[2]-$line[1]-$accs['#'.$line[0]])>0.005) echo "������: ���� {$line[0]}=".($line[2]-$line[1]).", ������ ���� ".$accs['#'.$line[0]].", ������� ".($line[2]-$line[1]-$accs['#'.$line[0]])."<br>";
				else unset($accs['#'.$line[0]]);
				if($line[3]!=$quants['#'.$line[0]] && $quants['#'.$line[0]]!=0) echo "������ ����������: ���� {$line[0]} -> {$line[3]}, ������ ���� ".$quants['#'.$line[0]]."<br>";
				else unset($quants['#'.$line[0]]);
			}
			if(count($accs)>0) foreach($accs as $key=>$value) if(abs($value)>0.005) echo "������: ���� ".substr($key,1)."=0, ������ ���� $value<br>";
			if(count($quants)>0) foreach($quants as $key=>$value) if($value>0) echo "������ ����������: ���� ".substr($key,1)." -> 0, ������ ���� $value<br>";
		}
		$fd=$ed;
	}
	echo show_all("SELECT COUNT(`����`) AS '������', SUM(`�����`) AS '����� �� ������', SUM(`������`) AS '����� �� �������' FROM `Accounts`",true)."<br>";
	echo show_all("SELECT COUNT(`�����`) AS '��������', MIN(`����`) AS '��������� ����', MAX(`����`) AS '�������� ����' FROM `Entries`",true)."<br>";
	echo show_all("SELECT COUNT(`����`) AS '�������� �������', SUM(`����������`) AS '����������', SUM(`�����`)-SUM(`������`) AS '�� �����' FROM `Accounts` WHERE `����` LIKE '41.%'",true)."<br>";
	echo show_all("SELECT COUNT(`�����`) AS '����� � ����. ���������', SUM(`����������`) AS '����������', SUM(`�����`) AS '�� �����'  FROM `Invoice`",true)."<br>";
	echo show_all("SELECT COUNT(`�����`) AS '�������� ��������' FROM `Stencil`",true)."<br>";
//	echo show_all("SELECT SUM(`�������`) AS '����', COUNT(`�������`) AS '�������' FROM `Balances`",true);
    $result->free();
	if($set) $mysqli->close();
}
function liveVidgets($flag) {
	global $mysqli;
	global $reply;
	global $userdata;
	if(!isset($mysqli)) {
		$mysqli = new mysqli(SERVER, USERNAME, DBPASS,DBNAME) or die('������ ����������� (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
		$set=true;
	}
	if($flag & ACCOUNTS) {
		$query = "SELECT COUNT(`����`), SUM(`�����`), SUM(`������`) FROM `Accounts`";
		$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
	    $line = $result->fetch_array(MYSQLI_NUM);
		$line[1]=number_format($line[1],2,'.',DLMTR);
		$line[2]=number_format($line[2],2,'.',DLMTR);
		if($flag & INITIAL)	$reply['vidgetAcc']= "<table align=left><tr><td>������:</td><td>{$line[0]}</td></tr><tr><td>����� �����:</td><td align=right>{$line[1]}</td></tr><tr><td>����� ������:</td><td align=right>{$line[2]}</td></tr></table>";
		else $reply['vidgetAcc']=implode('|',$line);
	}
	if($flag & ENTRIES) {
		$query = "SELECT COUNT(`�����`), MIN(`����`), MAX(`����`) FROM `Entries`";
		$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
	    $line = $result->fetch_array(MYSQLI_NUM);
		$line[1]=toOurDate($line[1]);
		$line[2]=toOurDate($line[2]);
		if($flag & INITIAL)	$reply['vidgetEnt']= "<table align=left><tr><td>��������:</td><td>{$line[0]}</td></tr><tr><td>���. ����:</td><td>{$line[1]}</td></tr><tr><td>���. ����:</td><td>{$line[2]}</td></tr></table>";
		else $reply['vidgetEnt']=implode('|',$line);
	}
	if($flag & GOODS) {
		$query = "SELECT COUNT(`����`), SUM(`����������`), SUM(`�����`)-SUM(`������`) FROM `Accounts` WHERE `����` LIKE '41.%'";
		$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
	    $line = $result->fetch_array(MYSQLI_NUM);
		$line[2]=number_format($line[2],2,'.',DLMTR);
		if($flag & INITIAL)	$reply['vidgetGds']= "<table align=left><tr><td>�������:</td><td>{$line[0]}</td></tr><tr><td>����������:</td><td>{$line[1]}</td></tr><tr><td>�� �����:</td><td align=right>{$line[2]}</td></tr></table>";
		else $reply['vidgetGds']=implode('|',$line);
	}
	if($flag & INVOICE) {
		$query = "SELECT COUNT(`�����`), SUM(`����������`), SUM(`�����`)  FROM `Invoice`";
		$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
	    $line = $result->fetch_array(MYSQLI_NUM);
		$line[2]=number_format($line[2],2,'.',DLMTR);
		if($flag & INITIAL)	$reply['vidgetInv']= "<table align=left><tr><td>�������:</td><td>{$line[0]}</td></tr><tr><td>����������:</td><td>{$line[1]}</td></tr><tr><td>�� �����:</td><td align=right>{$line[2]}</td></tr></table>";
		else $reply['vidgetInv']=implode('|',$line);
	}
	if($flag & STENCIL) {
		$query = "SELECT COUNT(`�����`) FROM `Stencil`";
		$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
	    $line = $result->fetch_array(MYSQLI_NUM);
		if($flag & INITIAL)	$reply['vidgetStn']= "<table align=left><tr><td>��������:</td><td>{$line[0]}</td></tr></table>";
		else $reply['vidgetStn']=implode('|',$line);
	}
	if($flag & CTRAGENTS) {
		$query = "SELECT COUNT(`����`), SUM(`�����`), SUM(`������`) FROM `Accounts` WHERE `����` LIKE '60.%' OR `����` LIKE '62.%'";
		$result = $mysqli->query($query) or die('������ MySQL: ' . $mysqli->error);
	    $line = $result->fetch_array(MYSQLI_NUM);
		$line[1]=number_format($line[1],2,'.',DLMTR);
		$line[2]=number_format($line[2],2,'.',DLMTR);
		if($flag & INITIAL)	$reply['vidgetCtr']= "<table align=left><tr><td>������������:</td><td>{$line[0]}</td></tr><tr><td>����� �����:</td><td align=right>{$line[1]}</td></tr><tr><td>����� ������:</td><td align=right>{$line[2]}</td></tr></table>";
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
	  <meta name="author" content="������ ���������">
	  <link href="uchet.css?<?echo(md5_file($_SERVER['DOCUMENT_ROOT'].'/uchet.css')) ?>" rel="stylesheet" type="text/css" >
	  <link rel="icon" href="/favicon.ico" type="image/x-icon">
	  <script language="JavaScript" type="text/javascript" src="/uchet.js?<?echo(md5_file($_SERVER['DOCUMENT_ROOT'].'/uchet.js')) ?>">  </script>
	</head>
<body onload='fillVidgets()'>
	<div class='vidget' name='Accounts' rname='�����' width='650' height='600'></div>
	<div class='vidget' name='Entries' rname='��������' width='850' height='520'></div>
	<div class='vidget' name='Goods' rname='������' width='850' height='600'></div>
	<div class='vidget' name='About' rname='� ����������' width='800' height='600'>�������� ����������,<br>����������� ������������,<br>"���������� ����" ��������</div>
	<div class='vidget' name='Stencil' rname='������� ��������' width='700' height='480'></div>
	<div class='vidget' name='Statement' rname='�������' width='850' height='600'>�������� ������� �� ������ ����� �� �������� ������</div>
	<div class='vidget' name='newEntry' rname='����� ��������' width='510' height='330'>������� ����� ������������� ��������</div>
	<div class='vidget' name='Invoice' rname='������' width='850' height='600'></div>
	<div class='vidget' name='Sale' rname='�������' width='510' height='345'>�������� � ����� ������� ������</div>
	<div class='vidget' name='Kudir' rname='�����' width='800' height='600'>������������ ����� ����� ������� � ��������</div>
	<div class='vidget' name='Calendar' rname='���������' width='200' height='266'></div>
	<div class='vidget' name='Calculator' rname='�����������' width='310' height='400'></div>
	<div class='vidget' name='CounterAgents' rname='�����������' width='725' height='500'></div>
	<div class='vidget' name='LoadBank' rname='��������� ����' width='950' height='500'>��������� ������� ����� � ������� 1�</div>
<?php if($extended) { ?>
<?PHP } ?>
</body>
</html>

