<?php 
define('SERVER',"");				// Сервер базы данных
define('DBNAME',"");				// Имя базы данных
define('USERNAME',"");			// Имя пользователя
define('DBPASS',"");			// Пароль базы данных
header("Cache-Control: no-cache");
foreach ($_GET as $key=>$value) {
	$_GET[$key]=$value=iconv('UTF-8', 'windows-1251', $value);
	if($value!='' && !preg_match("/^[\w\.,\*\x80-\xFF]+$/",$value)) { echo "$key: $value"; die('Access denied'); }
}
$userdata=file('user.dat', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
//if(true) {}
if($_COOKIE['user'] && $_COOKIE['token']) {
	if($_COOKIE['user']!=$userdata[0] || $_COOKIE['token']!=$userdata[2]) {
		setcookie('user',false);
		setcookie('token',false);
		header("Location: /");
		exit();
	} 
}
elseif($_POST['login'] && preg_match('/^[a-zA-Z0-9]+$/',$_POST['login']) && $_POST['pass'] && preg_match('/^[a-zA-Z0-9]{6,}$/',$_POST['pass']) && count($_POST)==2) {
	if($userdata) {
		if($_POST['login']!=$userdata[0] || md5($_POST['login'].':'.$_POST['pass'])!=$userdata[1]) {
	        header("Location: /");
			exit();
		}
		$token=md5(generateCode());
		$userdata[2]=$token;
		$handle=fopen('user.dat','w');
		for($i=0;$i<count($userdata);$i++) fwrite($handle,$userdata[$i]."\r\n");
		fclose($handle);
	}
	else {
		$handle=fopen('user.dat','w');
		fwrite($handle,$_POST['login']."\r\n");
		fwrite($handle,md5($_POST['login'].':'.$_POST['pass'])."\r\n");
		$token=md5(generateCode());
		fwrite($handle,$token."\r\n");
		fclose($handle);
	}
	if((fileperms('user.dat') & 0777)!=0600) chmod('user.dat',0600);
	setcookie('user',$_POST['login']);
	setcookie('token',$token);
    header("Location: /");
	exit();
}
else {
	$errlog=isset($_POST['login']) && ($_POST['login']=='' || !preg_match('/^[a-zA-Z0-9]+$/',$_POST['login']));
	$errpass=isset($_POST['pass']) && !preg_match('/^[a-zA-Z0-9]{6,}$/',$_POST['pass']);
	header("X-Type: auth");
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
	  <meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
	  <title>Учет операций - Авторизация</title>
	  <meta name='robots' content='noindex' />
	  <meta name="author" content="Сергей Гуторенко">
	  <link href="user.css?<?echo(md5_file($_SERVER['DOCUMENT_ROOT'].'/user.css')) ?>" rel="stylesheet" type="text/css" >
	  <script language="JavaScript" type="text/javascript" src="/user.js?<?echo(md5_file($_SERVER['DOCUMENT_ROOT'].'/user.js')) ?>">  </script>
	</head>
	<body>
		<form id='authform' method='POST'>
			<table id='auth' border='0'>
				<tr><td>Логин:</td><td><input type='text' name='login' <?PHP if($errlog) echo "style='background-color:#ffe0e0'"; if($_POST['login']) echo "value='".$_POST['login']."'" ?>></td></tr>
				<?PHP if($errlog) echo "<tr><td colspan='2' class='err'>Можно: буквы латинского алфавита и цифры</td></tr>\n"; ?>
				<tr><td>Пароль:</td><td><input type='password'name='pass' <?PHP if($errpass) echo "style='background-color:#ffe0e0'" ?>></td></tr>
				<?PHP if($errpass) echo "<tr><td colspan='2' class='err'>Не менее 6 латинских букв или цифр</td></tr>\n"; ?>
				<tr><td colspan='2' align='center'><input type='submit' value='<?PHP echo $userdata?'Войти':'Зарегистрироваться'; ?>'></td></tr>
			</table>
		</form>
	</body>
	<script>prepare()</script>
</html>
<?PHP
	exit;
}
function generateCode($length=8) {
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPRQSTUVWXYZ0123456789";
    $code = "";
    $clen = strlen($chars) - 1;
    while (strlen($code) < $length) {
            $code .= $chars[mt_rand(0,$clen)];
    }
    return $code;
}
?>
