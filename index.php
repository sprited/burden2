<?php

//print "YOU ARE WELCOME!";
//exit;

// Блок переменных базы данных
$db_host="mysql.hostinger.ru";
$db_user="u858706044_pen";
$db_keycode="kamilla6";
$db_name="u858706044_word";

// Открываем врата в ад
$db_link=mysqli_connect($db_host, $db_user, $db_keycode, $db_name);

// Проверка подключения. Если подключения нет, то и работать с сайтом нет смысла
if(mysqli_connect_errno($db_link)){
	print "S_ERROR#1: Not avaliable connection to MySQL: ".mysqli_connect_error();
	exit;
}

// Имена таблиц БД
$dbt_ulist="all_user_list";
$dbt_uset="wrd_user_settings";
$dbt_umate="wrd_user_mates";
$dbt_ilist="wrd_item_list";
$dbt_items="wrd_item_base";
$dbt_share="wrd_item_share";
$dbt_flink="wrd_file_link";
$dbt_alink="wrd_file_alink";

$dbt_mrkt="wrd_mark_tags";
$dbt_mrkl="wrd_mark_links";
$dbt_mrkv="wrd_mark_vars";

$dbt_mrkg="wrd_mark_group";

// SECTION/#/MODE/NODE/EXTRA
// Входящие параметры (перенастроить на цифровые индексы?)
$zt = ( isset($_GET["a"]) )?$_GET["a"]:false;			// Тип
$zp = ( isset($_GET["b"]) )?$_GET["b"]:false;			// Указатель
$zm = ( isset($_GET["c"]) )?$_GET["c"]:false;			// Режим
$qm = ( isset($_GET["m"]) )?$_GET["m"]:false;			// QM-Режим

//print "REGIME:<br>Type = [".$zt."]<br>Item = [".$zp."]<br>Mode = [".$zm."]<br><br>";

// Авторизация
$mode_in="logon";
$mode_out="logoff";

// Переменные страницы
$pg_this = basename($_SERVER["PHP_SELF"]);	// Имя текущей страницы
$pg_full = "http://pm.96.lt/";

include "uni_func.php";

// Пояснение к структуре
// 1. Используется вертикальное управление по триггерам и флагам.
// 2. данные о пользователе сосредоточены в массиве $u.
// 3. Вывод происходит в переменную $hb_main.


{// AUTHENTICATION ENGINE

	// Функция кодирования пароля Password Crypt (что, чем) + убирает из паролей слэши для корректного cookie
	function pc($n1,$n2){
		return preg_replace('/\\\\|\//','',crypt(md5($n1),md5($n2)));
	}

	// Функция логина
	function loginsystem($name=0,$pass=0,$mem=0,$type=0){
		global $dbt_ulist,$dbt_uset;	// Входные данные
		global $u;	// Выходные переменные - массив данных пользователя
		
		$auth_uptime=time()+60*60*24;	// Время для работы без запоминания (час)
		
		// Проверка логина и пароля на соответствие базе
		
		// Очистка логина и пароля от лишнего
		$auth_ln = mres(preg_replace('/\b\W+\b/i','', $name));
		$auth_pc = mres(preg_replace('/\'|\||\\\\|"|=|\//','', $pass));
		$auth_mm = ($mem=="on")?true:false;
			
		// В случае, если авторизация происходит не через куки
		if($type==1){
			// Формирование запроса на соль
			$query='SELECT `salt` FROM `'.$dbt_ulist.'` WHERE `login`="'.$auth_ln.'" LIMIT 1';
			$mq=mq($query,1);
			// Если пользователь существует
			if($mq){
				// Криптокодирование пароля
				$auth_pc = pc($auth_pc.neat()["salt"],$auth_ln);
			}else{
				// Если записи не найдены
				if($mq!==false){
					// Вернуть отрицательный результат
					return false;
				}
				
				// Если нед доступа к ДБ, сообщить
				sp("No connection to DB! Please, try again.",2);
			}
		}

		// Запрос данных пользователя
		$query='SELECT `u`.`id`,`u`.`login`,`u`.`power`,`u`.`pcode`  
				FROM `'.$dbt_ulist.'` AS `u` 
				WHERE `u`.`login`="'.$auth_ln.'" 
				AND `u`.`pcode`="'.$auth_pc.'" 
				LIMIT 1';
		$mq=mq($query,1);
		// Если пользователь опознан
		if($mq){
			// Принять его информацию
			$data=neat();
			$u["id"]=$data["id"];
			$u["l"]=$data["login"];
			$u["r"]=$data["type"];
			$u["p"]=$data["pcode"];
			// Настройки
			$u["ppl"]=$data["pl_n"];
			
			// Если стоит флаг запоминания, то продлить всё на год
			if($auth_mm){
				$auth_uptime+=60*60*24*365;
				// Значение "on" в соответствии с чекбоксом
				setCookie("memento","on",$auth_uptime,"/");
			}

			// Прописать новые куки	// BMS: C
			setCookie("lognamer",$auth_ln,$auth_uptime,"/");
			setCookie("passcode",$auth_pc,$auth_uptime,"/");
		
		// Если пользователь не опознан
		}else{
			// Если записи не найдены
			if($mq!==false){
				// Вернуть отрицательный результат
				return false;
			}
			
			// Если нед доступа к ДБ, сообщить
			sp("No connection to DB! Please, try again later.",2);
		}
		
		// Если всё прошло успешно, вернуть положительный результат
		return true;
	}
} // END of AUTHENTICATION ENGINE
{// AUTENTICATION ALGORITHM
	// Если куки на пароль установлены
	if( isset($_COOKIE["passcode"]) ){
		
		// Если не установлена куки на логин - перейти к авторизации
		if (!isset($_COOKIE["logname"])){ 
			$flag_auth=1;
		}
		
		if(!$flag_auth){
			// Если установлен режим выхода	// BMS: C
			if($zt==$mode_out){
				$auth_bt=time()-60*60*24*365;			// Backtime: Откат на год для гарантированого удаления куки
				setCookie("passcode","",$auth_bt,"/");
				if(isset($_COOKIE["memento"])){setCookie("memento","0",$auth_bt,"/");}
				ref($pg_full,1);
				print "EXIT SUCCESS # COOKIES CLEARED<br>";
				exit;
			}
				
			// Вызываем функцию авторизации
			if(loginsystem($_COOKIE["logname"],$_COOKIE["passcode"],$_COOKIE["memento"])){
				// Если успешно авторизован - перейти к содержимому
				$flag_cont=1;
			}else{
				// Если запись в базе данных не была найдена - перейти к авторизации
				$flag_auth=1;
				sp("LOGIN FAILED # NO LEGAL DATABASE ENTRIES FOR &laquo;".$_COOKIE["logname"]."&raquo;!",2);
			}
		}

		// Если куки не установлены
	}else{

		// Если режим входа
		if($zt==$mode_in){
			
			// Вызываем функцию авторизации
			if(loginsystem($_POST["login"],$_POST["password"],$_POST["mem"],1)){
				// Если установлена таймзона, запомнить её
				if( isset($_POST["tzo"]) ){
					setCookie("tz",(int)$_POST["tzo"],time()+60*60*24*365*3,"/");
				}
				// Если успешно авторизован - перейти к содержимому
				$flag_cont=1;
			}else{
				// Если запись в базе данных не была найдена, срыв авторизации
				$flag_auth=1;
				if(isset($_POST["login"])){print "LOGIN FAILED # NO LOGIN LIKE &laquo;".$_POST["login"]."&raquo; or WRONG PASSWORD!<br>";}
			}
		}else{
			// Показать форму логина
			$flag_auth=1;
		}
	}

	if($flag_auth){
		print '
			<form name="login" method="post" action="'.$pg_full.'logon">
				<table border=0 cellpadding=5 cellspacing=0>
					<tr>
						<td align="right">Логин</td>
						<td><input type="text" name="login" value="" /></td>
					</tr>
					<tr>
						<td align="right">Пароль</td>
						<td><input type="password" name="password" value="" /></td>
					</tr>
					<tr>
						<td align="right"><label for="mem">Запомнить</label></td>
						<td><input type="checkbox" name="mem" id="mem" /></td>
					</tr>
					<tr><td align="center" colspan=2><input type="submit" value="LOG IN" /></td></tr>
				</table>
			</form>
		';
		exit;
	}

	if(!$flag_cont){
		//header("Refresh:0;url=ind.php");
		sp("ACCESS DENIED # WRONG AUTHORIZATION",2);
		exit;
	}
}// END of AUTENTICATION ALGORITHM

// Секции связаны с цифрами и кодами. Алиас (названия) секций преобразуется в код и идет в линейку триггеров.
// Структура секций определяется функциями, достаточно следить за их соответствием:

// Функция Check Rights - проверяет права пользователя
function cr($r){
	global $u;
	if($u["r"]>=$r){
		return true;
	}
	return false;
}

// Функция aliasTOcode: По входящему алиасу определяет код секции
function a2c($w = false){
	if($w=="" || $w=="node"){$w=1;}else
	if($w=="tag"){$w=2;}else
	if($w=="theme"){$w=3;}else
	if($w=="settings"){$w=4;}else
	if($w=="user"){$w=5;}else
	{$w=1;}
	return $w;
}

// Функция codeTOalias: По входящему коду определяет тип секции
function c2a($n = false){
	switch((int)$n){
		case 1:$r="node";break;
		case 2:$r="tag";break;
		case 3:$r="theme";break;
		case 4:$r="settings";break;
		case 5:$r="user";break;
		default:$r=false;
	}
	return $r;
}

//Типы: "" (default) = epic, tag, theme, settings
$types=array("","epic","tag","theme","settings","user");

// TO INCLUDE:
//Service Print :: $m - message, $t - type (color), $e - binary switcher
function sp($m=false,$t=0,$e=false){
	global $hb_main;
	if($e){if($m){$m="true";}else{$m="false";}}
	if($m){
		switch ($t){
			case "ok":case "good":
			case 1:$t="green";break;
			case "bad":case "err":case "error":
			case 2:$t="red";break;
			case 3:$t="blue";break;
			case 4:$t="purple";break;
			default:
		}
		if(is_array($m)){
			print '<pre style="font-family:Calibri;color:'.$t.';">';
			print_r($m);
			print '</pre><br>';
		}else{
			//print '<font color="'.$t.'"><b>'.$m.'</b></font><br>';
			$hb_main.= '<font color="'.$t.'"><b>'.$m.'</b></font><br>';
		}
		return true;
	}
	return $m;
}

// MASKS
$mask_login='/\W+/';
$mask_pass='/\'|\||\\\\|"|=|\/|`/';
$mask_tag='/["|\'|`|\/|\\\\]*/u';
$mask_text = '/(^|\s+)"(.*)"(\s|,|\.|\)|\]|\:|;|\?|$)/U';

//COOKIE VARs
$h_mk=isset($_COOKIE["mk"])?$_COOKIE["mk"]:"0";		// Menu Key
$h_ck=isset($_COOKIE["ck"])?$_COOKIE["ck"]:"1";		// Control Key
$h_hi=isset($_COOKIE["hi"])?$_COOKIE["hi"]:"1";		// HIde menu Key

{// Quick Mode Addition (&m=...)
	if($qm){
		/* QM GROUP
		
		21 - Load Menu
		
		31 - Load Submenu
		
		41 - Load Node
		
		*/
		
		$qi=floor($qm/10);	// Секция
		$qii=$qm%10;		// Подсекция
		
		$asx_id=isset($_GET["i"])?ci($_GET["i"]):0;
		$asx_lvl=isset($_GET["l"])?ci($_GET["l"]):0;
		$asx_lim=isset($_GET["f"])?ci($_GET["f"]):1;
		
		// Функция генерирует нумерацию id для отправки POST-форм (используется внешняя перeменная $apsi_max)
		function apsi($f=false,$n=0){
			global $apsi_max;
			if($f){
				$n=ci($n);
				if($n){
					if($n>$apsi_max){$apsi_max=$n;}
					$f=' id="'.$f.'_'.$n.'"';
				}else{
					$f='<input type="hidden" name="idcount" id="'.$f.'_0" value="'.$apsi_max.'" />';
					$apsi_max=0;
				}
			}
			return $f;
		}
		
		if($qm==1){
			print_r($_GET);
		/*
		// MENU - ITEM
		}elseif($qm==21){
			$query='SELECT `id`,`label`,`parent_id`,`level`,`ord`,`fill`
					FROM `'.$dbt_ilist.'`
					WHERE `owner_id`="'.$u["id"].'"
					AND `level`='.$asx_lvl.'
					ORDER BY `ord`';
			$mq=mq($query,1);
			if($mq){
				$items=neat($mq);
				
				print '<table border=1 cellpadding=6 cellspacing=0 width=100%>';
				
				$imax=count($items);
				for($i=0;$i<$imax;$i++){
					print '<tr><td>';
					if($items[$i]["fill"]){
						print '<b><a href="#'.$items[$i]["id"].'" onClick="asx(31,\'i:'.$items[$i]["id"].';f:'.$items[$i]["fill"].';\',\'n_'.$items[$i]["id"].'\');return false;" id="ae_'.$items[$i]["id"].'">+</a></b>';
					}
					print '<a href="#/node/'.$items[$i]["id"].'" onClick="asx(41,\'i:'.$items[$i]["id"].';\',\'content\');">'.$items[$i]["label"].'</a>';
					if($items[$i]["fill"]){
						print '<div id="n_'.$items[$i]["id"].'" class="subnode"></div>';
					}
					print '</td></tr>';
				}
				print '</table>';
			}else{
				nomq();
			}
		*/
		
		// MENU
		}elseif($qi==3){
			
			// Форма создания/сохранения
			if($qii==2){
				$asx_type=isset($_GET["type"])?(($_GET["type"]=="edit")?"edit":"new"):false;
				
				if($asx_type){
					// Сохранить или создать?
					$item=array();
					if($asx_type=="edit"){
						$query='SELECT `parent_id`,`label`,`icon_id`,`f_share` FROM `'.$dbt_ilist.'` WHERE `owner_id`="'.$u["id"].'" AND `id`="'.$asx_id.'" LIMIT 1';
						$mq=mq($query,1);
						if($mq){
							$item=neat();
						}
					}
	
					$h_chk1=$item["f_share"]?"checked":"";
					$h_fn="item_edit";
					// Выбор id диалога для загрузки
					$h_dlg=($asx_type=="new")?$asx_id:$item["parent_id"];
					
					// Вывод формы
					print '
						<form name="">
							<table border=0 cellpadding=4 cellspacing=0>
								<tr><td colspan=2 align="center"><h4>'.(($asx_type=="new")?"NEW":"EDIT").' ITEM</h4></td></tr>
								<tr><td align="right">Label:</td><td><input type="text" name="label" value="'.$item["label"].'"'.apsi($h_fn,1).' /></td></tr>
								<tr><td align="right">Icon:</td><td><input type="text" name="icon" value="'.$item["icon_id"].'"'.apsi($h_fn,2).' /></td></tr>
								<tr><td align="right">Share:</td><td><input type="checkbox" name="share" value="1" '.$h_chk1.''.apsi($h_fn,3).' /></td></tr>
								<tr><td colspan=2 align="right"><input type="button" value="'.(($asx_type=="new")?"CREATE":"SAVE").'" onClick="tgl(\'dlgs\');asx(34,pp(\''.$h_fn.'\'),\'n_'.$h_dlg.'\',0,1);" /></td></tr>
							</table>
							<input type="hidden" name="type" value="'.$asx_type.'"'.apsi($h_fn,4).' />
							<input type="hidden" name="node" value="'.$asx_id.'"'.apsi($h_fn,5).' />
						'.apsi($h_fn).'</form>
					';
					sp($_GET);
				}
			}
			
			// MENU - ITEM - SAVE
			if($qii==4){
				// Получить ID нода
				$h_node=ci($_POST["node"]);
				// Если нод задан
				if($h_node){
					$parent=false;
					$asx_type=isset($_POST["type"])?(($_POST["type"]=="edit")?"edit":"new"):false;
					

					// Формирование запроса на получение уровня родителя
					if($asx_type=="new"){
						$query='SELECT `parent_id` AS `pid`,`level`,`fill` FROM `'.$dbt_ilist.'` WHERE `owner_id`="'.$u["id"].'" AND `id`="'.$h_node.'" LIMIT 1';
					}elseif($asx_type=="edit"){
						$query='SELECT `id` AS `pid`,`level`,`fill` FROM `'.$dbt_ilist.'` WHERE `owner_id`="'.$u["id"].'" AND `id`=(SELECT `parent_id` FROM `'.$dbt_ilist.'` WHERE `id`="'.$h_node.'" LIMIT 1) LIMIT 1';
					}
					$mq=mq($query,1);
					if($mq){
						$parent=neat();
					}
					
					
					$h_label=$_POST["label"];
					$h_icon=ci($_POST["icon"]);
					$h_share=isset($_POST["share"])?1:0;
					
					// Установка дополнительных значений
					if($asx_type=="new"){
						$qadd_1='INSERT INTO';
						$qadd_2=',`owner_id`="'.$u["id"].'",`level`="'.($parent["level"]+1).'",`ord`="'.($parent["fill"]+1).'",`parent_id`="'.$h_node.'"';
					}elseif($asx_type=="edit"){
						$qadd_1='UPDATE';
						$qadd_2='WHERE `id`="'.$h_node.'" AND `owner_id`="'.$u["id"].'" LIMIT 1';
					}
					
					$query=$qadd_1.' `'.$dbt_ilist.'`
							SET
							`label`="'.$h_label.'",
							`icon_id`="'.$h_icon.'",
							`f_share`="'.$h_share.'"
							'.$qadd_2;
					$mq=mq($query,0,$ar,$lin);
					// Если удалось
					if($mq && $ar){
						if($asx_type=="new"){
							$query='UPDATE `'.$dbt_ilist.'` SET `fill`=`fill`+1 WHERE `id`="'.$h_node.'" AND `owner_id`="'.$u["id"].'" LIMIT 1';
							$mq=mq($query,0,$ar);
							if($mq && $ar){
								// Если родитель обновлен успешно
							}else{
								print "ERROR ".__LINE__;
							}
						}
						
						$asx_id=$parent["pid"];
						$asx_lim=$parent["fill"];
						
						$qii=1;
					}
				}
			}
			
			
			// MENU - ITEM
			if($qii==1){
				$query='SELECT `id`,`label`,`parent_id`,`ord`,`fill`
						FROM `'.$dbt_ilist.'`
						WHERE `owner_id`="'.$u["id"].'"
						AND `parent_id`='.$asx_id.'
						ORDER BY `ord`
						LIMIT '.$asx_lim;
				$mq=mq($query,1);
				if($mq){
					$items=neat($mq);
					
					print '<table border=0 cellpadding=0 cellspacing=4 width=100%>';
					
					$imax=count($items);
					for($i=0;$i<$imax;$i++){
						print '<tr><td valign="top" align="center">';
						$h_nexp="";
						
						if($items[$i]["fill"]){
							$h_nexp='expand(\'n_'.$items[$i]["id"].'\',\'i:'.$items[$i]["id"].';f:'.$items[$i]["fill"].'\');return false;';
							print '<b><a href="#'.$items[$i]["id"].'" onClick="'.$h_nexp.'" id="ae_'.$items[$i]["id"].'">+</a></b>';
						}
						
						print '</td><td>';
						print '<a href="#/node/'.$items[$i]["id"].'" onClick="copen('.$items[$i]["id"].');" onDblClick="'.$h_nexp.'">'.$items[$i]["label"].'</a>';
						print '&ensp;<a href="#/node/'.$items[$i]["id"].'/edit" onClick="modify('.$items[$i]["id"].',\'edit\');" title="Edit node">[E]</a>';
						print '&ensp;<a href="#/node/'.$items[$i]["id"].'/sub" onClick="modify('.$items[$i]["id"].',\'new\');" title="Add subnode">[+]</a>';
						//if($items[$i]["fill"]){
							print '<div id="n_'.$items[$i]["id"].'" class="subnode"></div>';
						//}
						print '</td></tr>';
					}
					print '</table>';
				}else{
					nomq();
				}
			}
			
		// NODE
		}elseif($qi==4){
			
			// NODE - SAVE
			if($qii==2){
				
				$h_nid=isset($_POST["node_id"])?ci($_POST["node_id"]):0;
				
				if($h_nid){
					$query='SELECT `f_lock` FROM `'.$dbt_items.'` WHERE `id`="'.$h_nid.'" AND `owner_id`="'.$u["id"].'" LIMIT 1';
					$mq=mq($query,1);
					// Если запись существует
					if($mq){
						// Сохранить её
						$flag_lk=neat()["f_lock"];
						
						// Если нет ключа запрета
						if(!$flag_lk){
							$h_cnt=mres($_POST["node"]);
							$h_lock=isset($_POST["sil"])?ci($_POST["sil"]):0;
							
							$query='UPDATE `'.$dbt_items.'`
									SET
									`content`="'.$h_cnt.'",
									'.($h_lock?'`f_lock`="1",':"").'
									`date_e`="'.date("Y-m-d").'"
									WHERE `id`="'.$h_nid.'"
									LIMIT 1';
							$mq=mq($query,0,$ar);
							if($mq){
								if($ar){
									sp("SAVING SUCCESS! AR=".$ar,1);
									// Переход к показу узла
									$asx_id=$h_nid;
									$qii=1;
								}
							}else{
								nomq($mq);
							}
						
						// Если есть ключ запрета
						}else{
							sp("LOCK KEY IS ENABLED!",2);
						}
					
					// Если записи нет
					}else{
						// Если запись не найдена
						if($mq!==false){
							sp("CREATION MODE",3);
							
						// Если нет соединения с сервером
						}else{
							nomq(false);
						}
					}
				
				}
				/*
				print_r($_GET);
				print "<hr>";
				print_r($_POST);
				*/
				
				
			// NODE - SAVEXX
			}elseif($qii==3){
				
				$query='SELECT 
						`l`.``,
						`l`.``,
						`l`.``,
						`l`.``,
						`i`.`f_lock`
						FROM `'.$dbt_ilist.'` AS `l`,`'.$dbt_items.'` AS `i`
						WHERE `l`.`id`="'.$asx_id.'"
						AND `l`.`owner_id`="'.$u["id"].'"
						AND `i`.`id`=`l`.`id`
						LIMIT 1';
			}
			
			// NODE - LOCK/UNLOCK
			if($qii==9){
				$asx_r=isset($_GET["r"])?ci($_GET["r"]):69;
				if($asx_r==96){
					$h_lock="0";
				}else{
					$h_lock=1;
				}
				
				$query='UPDATE `'.$dbt_items.'`
						SET
						`f_lock`="'.$h_lock.'"
						WHERE `id`="'.$asx_id.'"
						LIMIT 1';
				$mq=mq($query,0,$ar);
				$qii=1;
			}
			
			// NODE - LOADING
			if($qii==1){
				// Имя формы
				$h_fn="fsn";
				
				// Формирование запроса
				$query='SELECT `content`,`f_lock`,`date_c`,`date_e`
						FROM `'.$dbt_items.'`
						WHERE `id`="'.$asx_id.'"
						AND `owner_id`="'.$u["id"].'"
						LIMIT 1';
				$mq=mq($query,1);
				if($mq){
					$item=neat();
					
					if(!$item["f_lock"]){
						$item["content"]='
							<textarea name="node" style="width:99%;height:99%"'.apsi($h_fn,1).'>'.$item["content"].'</textarea>
							<input type="hidden" name="node_id" value="'.$asx_id.'"'.apsi($h_fn,2).' />';
					}else{
						$item["content"]=preg_replace('/\n/','<br>',$item["content"]);
					}
					
					print (!$item["f_lock"])?'<form name="cnt_edit" style="width:99%;height:99%">':"";
					
					print '
						<table border=0 cellpadding=4 cellspacing=0 width=100% height=100%>
							<tr bgcolor="#ffffaa" height=10>
								<td>'.($item["f_lock"]?"LOCKED!":'<input type="button" onClick="asx(42,pp(\''.$h_fn.'\'),\'content\',0,1);" value="SAVE" />').'</td>
								<td>'.($item["f_lock"]?"":'<label for="'.$h_fn.'_3"><input type="checkbox" name="sil" value="1"'.apsi($h_fn,3).' />Save & Lock</label>').'</td>
								<td><input type="button" '.($item["f_lock"]?'onClick="get(\'content\',\'m:49;i:'.$asx_id.';r:96;\');" value="UNLOCK"':'onClick="get(\'content\',\'m:49;i:'.$asx_id.';r:69;\');" value="LOCK"').' /></td>
								<td align="right">Дата изменения: '.$item["date_e"].'</td>
								<td align="right">Дата создания: '.$item["date_c"].'</td>
							</tr>
							<tr>
								<td colspan=5 valign="top" height=100%><div style="height:300px;width:600px;border:solid 1px black">'.$item["content"].'</div></td>
							</tr>
						</table>
					';
					print (!$item["f_lock"])?apsi($h_fn).'</form>':"";
					
				}else{
					if($mq!==false){
						$h_sbtn='<input type="button" onClick="asx(42,pp(\''.$h_fn.'\'),\'content\',0,1);" value="SAVE" />';
						
						print '
						<div style="width:100%;height:10%;">'.$h_sbtn.'</div>
						<div style="width:100%;height:80%;">
							<form style="width:100%;height:100%;">
								<textarea name="node" style="width:98%;height:100%;font-family:Arial;font-size:16pt;"'.apsi("fsn",1).'>'.$item["content"].'</textarea>
							'.apsi("fsn").'</form>
						</div>
						<div style="width:100%;height:10%;">'.$h_sbtn.'</div>
						';
					}
				}
			}
			
			
		// _else_
		}else{
			print "Incorrect Question!";
		}
		
		// SERVICE
		//print "QM:".$qm."; Q<sub>i</sub>=".$qi."; Q<sub>ii</sub>=".$qii."<br>";
		
		// Завершение страницы
		exit;
	}
}// END of QM ENGINE

{// ==== TAGS ENGINE ====

	// Получает все теги элемента по его номеру $n
	function getTags($n=false){
		if($n){
			global $dbt_mrkt,$dbt_mrkl;
			$query='SELECT 
					`n`.`id`, 
					`n`.`name` 
					FROM  `'.$dbt_mrkt.'` AS `n`, `'.$dbt_mrkl.'` AS `t` 
					WHERE `t`.`item_id`="'.$n.'" 
					AND `t`.`mark_id`=`n`.`id` 
					ORDER BY `n`.`name`';
			$mq=mq($query,1);
			if($mq){
				$tags_base=neat($mq);
			}elseif($mq===false){
				print "CAN'T CONNECT TO TAGS DB!<br>";
				$tags_base=false;
			}
			return $tags_base;
		}
	}

	// Получает все варианты тега по его номеру $n
	function getVars($n=false){
		if($n){
			global $dbt_mrkv;
			$vars_base=array();
			$query='SELECT `id`,`var` 
					FROM  `'.$dbt_mrkv.'` 
					WHERE `mark_id`="'.$n.'" 
					ORDER BY `var`';
			$mq=mq($query,1);
			if($mq){
				$vars_base=neat($mq);
			}elseif($mq===false){
				print "CAN'T CONNECT TO VARS DB!<br>";
				$vars_base=false;
			}
			return $vars_base;
		}
	}
	
	// Получает данные о группе ярлыка по его id
	function getGroup($n=false){
		if($n){
			global $dbt_mrkg,$dbt_mrkt;
			$g_base=array();
			$query='SELECT `g`.`id`,`g`.`name`,`g`.`colt`,`g`.`colb` 
					FROM  `'.$dbt_mrkg.'` AS `g`, `'.$dbt_mrkt.'` AS `n` 
					WHERE `n`.`id`="'.$n.'" 
					AND `g`.`id`=`n`.`group_id` 
					LIMIT 1';
			$mq=mq($query,1);
			if($mq){
				$g_base=neat();
			}elseif($mq===false){
				print "CAN'T CONNECT TO GROUP DB!<br>";
				$g_base=false;
			}
			return $g_base;
		}
	}

}// ==== TAGS ENGINE ====

// TYPE: TABLE | Режим таблицы - создать таблицы в базе данных
if($zt=="table" || $zt=="tables"){

	$h_ext="";
	
	if($zp){
		if(cr(9) && ($zp=="trunc" || $zp=="delete")){
			// Очистка таблиц
			
			// Параметр уверенности
			$flag_tr=$_POST["delconf"]?true:false;
			
			// Уточнение действия
			if($zp=="delete"){$h_qadd="DROP";}else{$h_qadd="TRUNCATE";}
			
			// Если очистка подтверждена
			if($flag_tr){
				
				$query="";
				if($_POST["t_tv"]){
					$query=$h_qadd.' TABLE `'.$dbt_mrkv.'`;';
					$mq=mq($query,0);
					if($mq){$h_ext.="TABLE <b>`".$dbt_mrkv."`</b> WAS SUCCESFULLY ".$h_qadd."ED !<br>";}
				}
				if($_POST["t_tl"]){
					$query=$h_qadd.' TABLE `'.$dbt_mrkl.'`;';
					$mq=mq($query,0);
					if($mq){$h_ext.="TABLE <b>`".$dbt_mrkl."`</b> WAS SUCCESFULLY ".$h_qadd."ED !<br>";}
				}
				if($_POST["t_tn"]){
					$query=$h_qadd.' TABLE `'.$dbt_mrkt.'`;';
					$mq=mq($query,0);
					if($mq){$h_ext.="TABLE <b>`".$dbt_mrkt."`</b> WAS SUCCESFULLY ".$h_qadd."ED !<br>";}
				}
				if($_POST["t_tt"]){
					$query=$h_qadd.' TABLE `'.$dbt_mrkg.'`;';
					$mq=mq($query,0);
					if($mq){$h_ext.="TABLE <b>`".$dbt_mrkg."`</b> WAS SUCCESFULLY ".$h_qadd."ED !<br>";}
				}
				if($_POST["t_pb"]){
					$query=$h_qadd.' TABLE `'.$dbt_items.'`;';
					$mq=mq($query,0);
					if($mq){$h_ext.="TABLE <b>`".$dbt_items."`</b> WAS SUCCESFULLY ".$h_qadd."ED !<br>";}
				}
			
			// Если очистка не подтверждена
			}else{
				print '
				Очистка таблиц. Выберите необходимые:
				<form name="del" method="POST" action="'.$pg_full.$zt.'/'.$zp.'">
					<input type="hidden" name="delconf" value="1" />
					<input type="checkbox" name="t_pb" value="1" />Element Base<br>
					<input type="checkbox" name="t_tt" value="1" />Tag Themes<br>
					<input type="checkbox" name="t_tn" value="1" />Tag Names<br>
					<input type="checkbox" name="t_tl" value="1" />Tag Links<br>
					<input type="checkbox" name="t_tv" value="1" />Tag Variants<br>
					(Lower is less significant)<br>
					<input type="reset" value="CLEAR SELECTED" />
					<input type="submit" value="'.$h_qadd.'" />
				</form>
				';
			}
			
			
		}elseif($zp=="create"){
			// Создание таблиц
			
			// Данные для администратора логин/пароль
			$db_auth=array("0"=>"admin","1"=>"admin","2"=>"1a2b3c4d5e");
			
			// Пользователи (не более 100)
			$query='CREATE TABLE IF NOT EXISTS `'.$dbt_ulist.'` (
					id SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					login VARCHAR(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL, 
					pcode VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL, 
					salt VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL, 
					email VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL, 
					power TINYINT(3) UNSIGNED NOT NULL DEFAULT 0, 
					date_c DATE NOT NULL, 
					date_l DATE NOT NULL, 
					lip VARCHAR(15) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL)';
			$mq=mq($query,0);
			if($mq){$h_ext.="TABLE <b>`".$dbt_ulist."`</b> WAS SUCCESFULLY CREATED !<br>";}
			
			// Настройки пользователя
			$query='CREATE TABLE IF NOT EXISTS `'.$dbt_uset.'` (
					id SMALLINT(3) UNSIGNED NOT NULL PRIMARY KEY, 
					quota_f  MEDIUMINT(9) UNSIGNED NOT NULL DEFAULT 100, 
					clr_bg  TINYINT(1) UNSIGNED NOT NULL DEFAULT 0, 
					clr_txt TINYINT(1) UNSIGNED NOT NULL DEFAULT 0, 
					lili TINYINT(1) UNSIGNED NOT NULL DEFAULT 0)';
			$mq=mq($query,0);
			if($mq){$h_ext.="TABLE <b>`".$dbt_uset."`</b> WAS SUCCESFULLY CREATED !<br>";}
			
			$query='INSERT INTO `'.$dbt_ulist.'` SET `id`="1",`login`="'.$db_auth[0].'",`pcode`="'.pc($db_auth[1].$db_auth[2],$db_auth[0]).'",`salt`="'.$db_auth[2].'",`power`="9",`date_c`="'.date("Y-m-d").'"';
			if(mq($query,0)){
				$query='INSERT INTO `'.$dbt_uset.'` SET `id`="1"';
				if(mq($query,0)){
					$h_ext.="Administrator ID is created (".$db_auth[0]."/".$db_auth[1].") !<br>";
				}
			}
			
			// Союзники
			$query='CREATE TABLE IF NOT EXISTS `'.$dbt_umate.'` (
					id SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					owner_id SMALLINT(5) UNSIGNED NOT NULL DEFAULT 0, 
					mate_id SMALLINT(5) UNSIGNED NOT NULL DEFAULT 0, 
					status TINYINT(1) UNSIGNED NOT NULL DEFAULT 0)';
			$mq=mq($query,0);
			if($mq){$h_ext.="TABLE <b>`".$dbt_umate."`</b> WAS SUCCESFULLY CREATED !<br>";}
			
			// Записи
			$query='CREATE TABLE IF NOT EXISTS `'.$dbt_ilist.'` (
					id MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					owner_id SMALLINT(5) UNSIGNED NOT NULL DEFAULT 0, 
					label VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL, 
					parent_id MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT 0, 
					level TINYINT(1) UNSIGNED NOT NULL DEFAULT 0, 
					ord SMALLINT(5) UNSIGNED NOT NULL DEFAULT 0, 
					icon_id TINYINT(3) UNSIGNED NOT NULL, 
					fill SMALLINT(5) UNSIGNED NOT NULL, 
					f_share TINYINT(1) UNSIGNED NOT NULL)';
			$mq=mq($query,0);
			if($mq){$h_ext.="TABLE <b>`".$dbt_ilist."`</b> WAS SUCCESFULLY CREATED !<br>";}
			
			$query='CREATE TABLE IF NOT EXISTS `'.$dbt_items.'` (
					id MEDIUMINT(8) UNSIGNED NOT NULL PRIMARY KEY, 
					owner_id SMALLINT(5) UNSIGNED NOT NULL DEFAULT 0, 
					content TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL, 
					f_lock TINYINT(1) UNSIGNED NOT NULL, 
					date_c DATE NOT NULL, 
					date_e DATE NOT NULL)';
			$mq=mq($query,0);
			if($mq){$h_ext.="TABLE <b>`".$dbt_items."`</b> WAS SUCCESFULLY CREATED !<br>";}
			
			$query='CREATE TABLE IF NOT EXISTS `'.$dbt_share.'` (
					id MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					node_id MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT 0, 
					owner_id SMALLINT(5) UNSIGNED NOT NULL DEFAULT 0, 
					guest_id SMALLINT(5) UNSIGNED NOT NULL DEFAULT 0, 
					status TINYINT(1) UNSIGNED NOT NULL)';
			$mq=mq($query,0);
			if($mq){$h_ext.="TABLE <b>`".$dbt_share."`</b> WAS SUCCESFULLY CREATED !<br>";}
			
			$query='CREATE TABLE IF NOT EXISTS `'.$dbt_flink.'` (
					id MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					node_id MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT 0, 
					owner_id SMALLINT(5) UNSIGNED NOT NULL DEFAULT 0, 
					label VARCHAR(60) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL, 
					about VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL, 
					insert_id TINYINT(1) UNSIGNED NOT NULL, 
					file_name VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL, 
					file_ext VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL, 
					file_save VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL, 
					f_share TINYINT(1) UNSIGNED NOT NULL, 
					date_c DATE NOT NULL)';
			$mq=mq($query,0);
			if($mq){$h_ext.="TABLE <b>`".$dbt_flink."`</b> WAS SUCCESFULLY CREATED !<br>";}
			
			// Система ярлыков
			$query='CREATE TABLE IF NOT EXISTS `'.$dbt_mrkt.'` (
					id MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					name VARCHAR(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL, 
					essence TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL, 
					group_id SMALLINT(5) UNSIGNED NOT NULL, 
					owner_id SMALLINT(5) UNSIGNED NOT NULL, 
					date_c DATE NOT NULL)';
			$mq=mq($query,0);
			if($mq){$h_ext.="TABLE <b>`".$dbt_mrkt."`</b> WAS SUCCESFULLY CREATED !<br>";}
			
			$query='CREATE TABLE IF NOT EXISTS `'.$dbt_mrkl.'` (
					id MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					mark_id MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT 0, 
					item_type TINYINT(3) UNSIGNED NOT NULL DEFAULT 0, 
					item_id VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL)';
			$mq=mq($query,0);
			if($mq){$h_ext.="TABLE <b>`".$dbt_mrkl."`</b> WAS SUCCESFULLY CREATED !<br>";}
			
			$query='CREATE TABLE IF NOT EXISTS `'.$dbt_mrkv.'` (
					id MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					mark_id MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT 0, 
					var VARCHAR(30) CHARACTER SET utf8 COLLATE utf8_general_ci UNIQUE NOT NULL)';
			$mq=mq($query,0);
			if($mq){$h_ext.="TABLE <b>`".$dbt_mrkv."`</b> WAS SUCCESFULLY CREATED !<br>";}
			
			$query='CREATE TABLE IF NOT EXISTS `'.$dbt_mrkg.'` (
					id SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					name VARCHAR(30) CHARACTER SET utf8 COLLATE utf8_general_ci UNIQUE NOT NULL, 
					essence TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL, 
					colt VARCHAR(15) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL, 
					colb VARCHAR(15) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL, 
					date_c DATE NOT NULL)';
			$mq=mq($query,0);
			if($mq){$h_ext.="TABLE <b>`".$dbt_mrkg."`</b> WAS SUCCESFULLY CREATED !<br>";}

		}
	}else{
		print '
		<h3>What do you want to do?</h3>
		<a href="'.$pg_full.$zt.'/create">CREATE ALL TABLES</a><br>
		<a href="'.$pg_full.$zt.'/trunc">TRUNCATE SELECTED TABLES</a><br>
		<a href="'.$pg_full.$zt.'/delete">DELETE SELECTED TABLES</a><br>
		';
	}
	
	$h_ext.='<br><form method="GET" action="'.$pg_full.'/table"><input type="submit" value="TABLES" /></form>';
	$h_ext.='<form method="GET" action="'.$pg_full.'"><input type="submit" value="BASE" /></form>';
	
	print $h_ext;
	exit;
}

// ---- START of TRIGGERS ----

// Run-trigger set:
$t_show=1;
$t_vote=0;
$t_edit=0;
$t_new=0;
$t_del=0;

// Переход по режиму: $zm -> $zt

// Если задан режим вместо указателя, переопределить их
if(!is_numeric($zp)){$zm=$zp;$zp=0;}

// MODE: VOTE
if($zm=="vote"){
	// Голосование только на ePIC
	//if($zt==$types[0] || $zt==$types[1]){
	if(a2c($zt)==0 || a2c($zt)==1){
		$t_vote=1;
	}

// MODE: SAVE
}elseif($zm=="save" && count($_POST)>0){
	//$t_show=0;
	
	// TYPE: TAG
	if(a2c($zt)==2){
		$t_save=2;
		
	// TYPE: THEME
	}elseif($zt==$types[3]){
		$t_save=3;
		
	// TYPE: SETTINGS
	}elseif($zt==$types[4]){
		$t_save=4;
		// Преобразование указателя в режим (цифра не используется)
		$zm==(string)$zp;
	
	// TYPE: USER
	}elseif($zt==$types[5]){
		$t_save=5;
		
	// TYPE: PICTURE (default)
	}else{
		$t_save=1;
	}
	
	
// MODE: EDIT
}elseif($zm=="edit"){
	// Запретить просмотр
	$t_show=0;

	$t_edit=a2c($zt);
	
// MODE: NEW
}elseif($zm=="new"){
	// Запретить просмотр
	$t_show=0;
	
	// Глобально: проверка на указатель
	if($zp){$zp=ci($zp);}else{$zp=0;}
	
	$t_new=a2c($zt);
	
// MODE: DELETE
}elseif($zm=="delete"){
	// Запретить просмотр
	$t_show=0;
	
	$t_del=a2c($zt);
	
// MODE: _else_
}else{
	// Все остальные режимы приводят к просмотру
	$t_show=a2c($zt);
}


// ---- END of TRIGGERS ----



// MODE AT_WORKING:

// R-MODE: VOTE
if($t_vote==1){
	
	// Если нет силы, не голосовать
	if(isset($_POST["power"])){
		// Get Variables:
		$h_bid=(int)$_POST["scale"];
		if($h_bid<1 || $h_bid>3){$h_bid=1;}
		$h_pm=($_POST["power"]<0)?"-1":"+1";
		// Обновление рейтинга
		$query='UPDATE `'.$dbt_items.'` 
				SET `bonus_'.$h_bid.'`=`bonus_'.$h_bid.'`'.$h_pm.' 
				WHERE `id`="'.$zp.'"
				LIMIT 1';
		$mq=mq($query,0);
		if($mq){
			//sp("BONUS ".$h_bid." UPDATED",1);
		}else{
			//sp("BONUS ".$h_bid." NOT UPDATED",2);
			nomq($mq);
		}
	}
}

// ------------------------------------------------------------

// ----------	/##    #    # #   ###
// ----------	 \    # #   # #   ##
// ----------	##/   # #    #    ###

// ------------------------------------------------------------

// R-MODE: SAVE
if($t_save){
	$t_show=0;
	
	// Установка режима сохранения (1 - новый, 2 - обновление)
	if($_POST["sat"]!="upd"){$t_sat=1;}else{$t_sat=2;}
	
	// Функция генерации случайного набора символов длиной $n (не более 32) из представленного набора символов $s
	// Если надо использовать различный кейс, то последний флаг устанавливается в true
	function genCode($n=10,$s=false,$c=false){
		if($s===true){$c=true;$s=false;}
		if(!$s){$s="abcdefghijklmnopqrstuvwxyz0123456789";}
		$n=(int)$n;
		if(!$n){$n=10;}elseif($n>32){$n=32;}
		$r="";
		for($i=0;$i<$n;$i++){
			$l=mb_strlen($s)-1;
			$r1=$s[rand(0,$l)];
			if($r1==$r2){$i--;continue;}
			if($c){$c=rand(1,20);if($c%2==0){$r1=mb_strtoupper($r1);}}
			$r.=$r1;
			$r2=$r1;
		}
		return $r;
	}
	
	// SAVE - PICTURE
	if($t_save==1){
		
		// Корректировка имени
		$_POST["label"]=preg_replace($mask_tag,'',$_POST["label"]);
		$_POST["label"]=mres($_POST["label"]);
		
		// Обработка текста
		//$h_about = preg_replace('/\r\n/',PHP_EOL,$_POST["about"]);
		$h_about = preg_replace($mask_text,'$1&laquo;$2&raquo$3',$_POST["about"]);
		$h_about = preg_replace('/\-\s+/','— ',$h_about);
		
		// Если сохранение новой картинки
		if($t_sat==1){
			// Задание директории
			$h_fold_maxnum=10;
			$fld_img="base_".rand(1,$h_fold_maxnum)."/";
			
			// Проверка наличия директории (и её создание)
			$t_extfld=0;
			if(!is_dir($fld_img)){
				if(mkdir($fld_img)){
					$t_extfld=1;
				}
			}else{
				$t_extfld=1;
			}
			
			if($t_extfld){
				// Обработка файлов
				if(isset($_FILES["picture"])){
					if(!$_FILES["picture"]["error"]){
						$h_ext = explode(".",$_FILES["picture"]["name"]);
						$h_img = genCode(20).".".$h_ext[count($h_ext)-1];
						$h_pic = $fld_img.$h_img;
						if (move_uploaded_file($_FILES["picture"]["tmp_name"], $h_pic)) {
							sp("Файл успешно загружен.",1);
							$flag_pload=1;
						} else {
							sp("Файл не был загружен!",2);
						}
					}
				}
			}
		}
		
		
		$flag_go=1;
		
		// Предварительный запрос при новом файле для создания нумерации без дыр
		if($t_sat==1){
			$query='SELECT MAX(`ranid`) AS `m` FROM `tos_pic_base`';
			$mq=mq($query,1);
			if($mq){
				$h_ranid=neat()["m"]+1;
			}else{
				$flag_go=0;
			}
		}
		
		if($flag_go){
			// Формирование запроса в зависимости от типа сохранения
			if($t_sat==1){
				$qadd_1='INSERT INTO';
				$qadd_2='
					,`fold_name`="'.$fld_img.'" 
					,`base_name`="'.$h_img.'" 
					,`date_c`="'.date("Y-m-d").'" 
					,`ranid`="'.$h_ranid.'" 
					';
			}else{
				$qadd_1='UPDATE';
				$qadd_2='WHERE `id`="'.$zp.'" LIMIT 1';
			}
			
			// Попытка сохранить на указанное место (при наличии прав)
			$h_newnum=ci($_POST["trynew"]);
			if(cr(9) && $t_sat==1 && $h_newnum){
				// Проверка отсутствия записи
				$query='SELECT `id` FROM `'.$dbt_items.'` WHERE `id`="'.$h_newnum.'" LIMIT 1';
				$mq=mq($query,1);
				if(!$mq || $mq!==false){
					$qadd_2.=',`id`="'.$h_newnum.'"';
				}
			}
			
			$query=$qadd_1.' `'.$dbt_items.'` 
					SET
					`name`="'.$_POST["label"].'",
					`about`="'.$h_about.'" 
					'.$qadd_2;
			$mq=mq($query,0);
			if($mq){
				// Если создание - задать id
				if($t_sat==1){
					$zp=$h_newnum?$h_newnum:mysqli_insert_id($db_link);
				}
			}else{
				nomq($mq);
			}
		}
		
		
		// Обработка ярлыков (элемент, ярлык, вариант, связь, тема)
		{// ==== TAGS ENGINE ====
			
			// TE:BLOCK:1 - Создание массива входных ярлыков
			$a1=array();
			$h_tagline=mres(preg_replace($mask_tag,'',$_POST["tags"]));
			$tmp_1 = explode(",",$h_tagline);
			foreach($tmp_1 as $t){$tmp_2[]=trim($t);}
			$tmp_2=array_unique($tmp_2);
			foreach($tmp_2 as $t){
				if($t!=""){
					$a1[] = array("tag"=>$t,"rtag"=>mb_strtolower($t,"utf-8"));
				}
			}
			unset($tmp_1,$tmp_2,$h_tagline,$t);
			
			// TEST
			//sp($a1);
			
			// TE:BLOCK:2 - Поиск вариантов входных ярлыков по вариантам
			$a2=array();
			foreach ($a1 as $k=>$t){
				$query='SELECT `mark_id` 
						FROM `'.$dbt_mrkv.'` 
						WHERE `var`="'.$t["rtag"].'" 
						LIMIT 1';
				$mq=mq($query,1);
				// Если такой вариант найден
				if($mq){
					$tmp_1=neat()["mark_id"];
					// Прописать ID ярлыка в массив входящих ярлыков
					$a1[$k]["tag_id"]=$tmp_1;
					// Записать ID ярлыка для массива "новых" ярлыков $a2
					$a2[$k]=$tmp_1;
				}
			}
			unset($tmp_1,$k,$t);
			
			// TEST
			//sp($a1);
			//sp($a2);			
			
			// TE:BLOCK:3 - Получить список ярлыков, закрепленных за элементом
			$a3=array();
			$query='SELECT `id`,`mark_id` 
					FROM `'.$dbt_mrkl.'` 
					WHERE `item_id`="'.$zp.'"';
			$mq=mq($query,1);
			if($mq){
				$tmp_1=neat($mq);
				foreach($tmp_1 as $t){
					// Записать ID ярлыка для массива "старых" ярлыков $a3
					$a3[]=$t["mark_id"];
				}
				unset($tmp_1,$t);
			}else{
				if($mq===false){
					unset($a3);
				}
			}
			
			// TEST
			//sp($a3);
			
			// TE:BLOCK:4 - Обработка полученных ярлыков
			// Если нет ошибок с получение существующих элементов из базы (чтобы не плодить сущности)
			if(isset($a3)){
				
				// TE:BLOCK:4.1 - Проверка пересечений (common, add, delete)
				$a_com=array_intersect($a2,$a3);
				$a_add=array_diff($a2,$a_com);
				$a_del=array_diff($a3,$a_com);
				unset($a_com);
				
				// TEST
				//sp($a_com,3);
				//sp($a_add,1);
				//sp($a_del,2);
				
				// TE:BLOCK:4.2 - Добавление новых ярлыков в базу и создание вариантов
				foreach($a1 as $k=>$v){
					// Если у ярлыка отсутствует ID (нет в базе)
					if(!isset($v["tag_id"])){
						// Добавить ярлык в базу
						$query='INSERT INTO `'.$dbt_mrkt.'` 
								SET
								`name`="'.$v["tag"].'",
								`date_c`="'.date("Y-m-d").'"';
						$mq=mq($query,0);
						// Если успешно добавлен
						if($mq){
							// Получить номер строки
							$lin=mysqli_insert_id($db_link);
							//Добавить id в массив на добавление связи**
							$a_add[]=$lin;
							
							// Добавить вариант ярлыка в базу
							$query='INSERT INTO `'.$dbt_mrkv.'` 
									SET
									`mark_id`="'.$lin.'",
									`var`="'.$v["rtag"].'"';
							$mq=mq($query,0);
							if($mq){
								// Вариант добавлен успешно
								sp("Add TAG `".$v["tag"]."` & VAR `".$v["rtag"]."` into DB (id=".$lin.")!",1);
								
							}else{
								// Вариант не добавлен!
								sp("Error insert VARIANT `".$v["rtag"],2);
							}
						}else{
							// Ярлык и вариант не добавлены!
							sp("Error insert TAG `".$v["tag"],2);
						}
					}
				}
				
				// Удаление массива создания ярлыков
				unset($a1);
				
				//TEST
				//sp($a1);
				
				
				// === LINK WORK ===
				
				// TE:BLOCK:4.3 - Добавление связей
				// Удаление неуникальных записей
				$a_add=array_unique($a_add);
				// Формирование запроса на добавление связей
				$qadd="";
				foreach($a_add as $v){
					if($qadd){$qadd.=",";}
					$qadd.='("'.$v.'","'.$zp.'")';
				}
				
				// Добавление связей
				if($qadd){
					$query='INSERT INTO `'.$dbt_mrkl.'` 
							(`mark_id`,`item_id`) 
							VALUES 
							'.$qadd.' 
							';
					$mq=mq($query,0,$ar);
					if($mq){
						// Связь добавлена
						sp("LINKS (".$ar.") sucessfully added!",1);
					}else{
						// Связь не добавлена
						nomq($mq,"^Can't insert LINKS!");
					}
				}
				
				// TE:BLOCK:4.4 - Удаление связей
				//Формирование запроса на удаление связей
				$ac=count($a_del);
				if($ac>0){
					$query='DELETE FROM `'.$dbt_mrkl.'` WHERE `item_id`="'.$zp.'" AND `mark_id` IN ('.implode(",",$a_del).') LIMIT '.$ac;
					$mq=mq($query,0);
					if($mq){
						// Связь удалена
						$ar=mysqli_affected_rows($db_link);
						sp("Delete LINK successful (".$ar." of ".$ac.") !",1);
					}else{
						// Связь не удалена
						sp("LINK not deleted (".$ac." ROWS) !",2);
					}
				}
			}
		} // ==== TAGS ENGINE ====
		
		// Действие после сохранения
		if($_POST["ret"]=="new"){
			$t_new=1;
		}else{
			//ref($pg_full.$zp),0);
			$t_show=1;
		}
	
	// SAVE - TAG
	}elseif($t_save==2){
		// Флаг движения по алгоритму
		$flag_go=1;
		
		// Формирование запроса на существующий ярлык
		$query='SELECT `id`,`name` 
				FROM `'.$dbt_mrkt.'` 
				WHERE `id`="'.$zp.'" 
				LIMIT 1';
		$mq=mq($query,1);
		if($mq){
			// Получение параметров ярлыка
			$tag=neat();
			// Приведенное имя
			$tag["rname"]=mb_strtolower($tag["name"],"utf-8");
			
			// Получить новое имя
			$tag["nname"]=preg_replace($mask_tag,'',$_POST["label"]);
			$tag["nname"]=preg_replace('/\,/','_',mres(trim($tag["nname"])));
			// Новое приведенное имя
			$tag["nrname"]=mb_strtolower($tag["nname"],"utf-8");
			
			// Проверка на смену имени ярлыка
			if($tag["rname"]!=$tag["nrname"]){$flag_chname=1;}
			
			// Получить группу
			$tag["gname"]=mres(preg_replace($mask_tag,'',trim($_POST["group"])));
			// Приведенное имя группы
			$tag["rgname"]=mb_strtolower($tag["gname"]);
			if($tag["gname"]){
				$query='SELECT `id` FROM `'.$dbt_mrkg.'` WHERE LOWER(`name`)="'.$tag["rgname"].'" LIMIT 1';
				$mq=mq($query,1);
				if($mq){
					// Группа найдена
					sp("THEME has been FOUND",1);
					$tag["ngid"]=neat()["id"];
				}else{
					// Группа не найдена, стереть текущую
					sp("THEME hasn't been FOUND",2);
					if($mq!==false){
						$tag["ngid"]=0;
					}
				}
			}else{
				// Если группа не установлена, стереть ее
				$tag["ngid"]=0;
			}
			
			// Добавка для запроса (добавление группы)
			$qadd_1="";
			if(isset($tag["ngid"])){
				$qadd_1=',`group_id`="'.$tag["ngid"].'"';
			}
			
			// Если имя сменяется
			if($flag_chname){
				// Проверка на наличие одноименного варианта вне этого тега
				$query='SELECT `id` FROM `'.$dbt_mrkv.'` WHERE `mark_id`!="'.$tag["id"].'" AND `var`="'.$tag["nrname"].'" LIMIT 1';
				$mq=mq($query,1);
				if($mq || $mq===false){
					// Если ярлык найден или ошибка подключения
					$flag_go=0;
					sp("Wrong name of TAG (already exists)",2);
				}
			}
			
			// Если ярлык можно создать
			if($flag_go){
				
				// Обработка текста описания ярлыка
				$h_about = preg_replace($mask_text,'$1&laquo;$2&raquo$3',$_POST["about"]);
				$h_about = preg_replace('/\-\s+/','— ',$h_about);
				
				// Запрос на обновление ярлыка
				$query='UPDATE `'.$dbt_mrkt.'` 
						SET
						`name`="'.$tag["nname"].'", 
						`essence`="'.$h_about.'" 
						'.$qadd_1.'
						WHERE `id`="'.$tag["id"].'" 
						LIMIT 1';
				$mq=mq($query,0);
				// Если ярлык изменён
				if($mq){
					// Имя ярлыка успешно изменено
					sp("TAG is saved",1);
				}else{
					// Имя ярлыка не изменено!
					sp("Can't save TAG!",2);
				}
				
				// Обработка вариантов
				{// ==== TAGS ENGINE ==== VARS
					
					// TE-V:BLOCK:1 - Создание массива входных вариантов
					$_POST["vars"] = preg_replace($mask_tag,'',$_POST["vars"]);
					$h_varline = mres($_POST["vars"]);
					$tmp_1 = explode(",",$h_varline);
					foreach($tmp_1 as $t){
						$tmp_2=trim($t);
						if($tmp_2!=""){
							// Разложение вариантов в массив
							$a1[] = mb_strtolower($tmp_2,"utf-8");
						}
					}
					unset($tmp_1,$tmp_2,$h_tagline,$t);
					
					
					// TE-V:BLOCK:2 - Обработка дополнительного варианта от смены имени ярлыка
					// Добавление во входящие варианты варианта текущего ярлыка
					$a1[]=$tag["nrname"];
					// Проверка, что все элементы уникальные
					$a1=array_unique($a1);
					
					
					// TEST
					//sp($a1);
					
					// TE-V:BLOCK:3 - Получить список вариантов, закрепленных за ярлыком
					$tmp_1=getVars($tag["id"]);
					if(is_array($tmp_1)){
						if(count($tmp_1)>0){
							foreach($tmp_1 as $t){
								// Массив для сравнения имеющихся вариантов
								$a2[]=$t["id"];
								$a3[]=$t["var"];
							}
						}else{
							$a3=array();
						}
					}
					unset($tmp_1);
					
					// TEST
					//sp($a3);
					
					// TE-V:BLOCK:4 - Манипуляции с вариантами
					// Если нет ошибок с получение существующих вариантов из базы (чтобы не плодить сущности)
					if(isset($a3)){
						// TE-V:BLOCK:4.1 - Проверить пересечения вариантов
						$a_com=array_intersect($a1,$a3);
						$a_add=array_diff($a1,$a_com);
						$a_del=array_diff($a3,$a_com);
						
						// TEST
						//sp($a_com,3);
						//sp($a_add,1);
						//sp($a_del,2);
						
						// TE-V:BLOCK:4.2 - Добавление вариантов
						// Формирование запроса на добавление вариантов
						$qadd="";
						foreach($a_add as $v){
							if($qadd){$qadd.=",";}
							$qadd.='("'.$v.'","'.$tag["id"].'")';
						}
						// Добавление вариантов
						if($qadd){
							$query='INSERT INTO `'.$dbt_mrkv.'` 
									(`var`,`mark_id`) 
									VALUES 
									'.$qadd.' 
									';
							$mq=mq($query,0);
							if($mq){
								sp("VARIANT `".$v."` sucessfully added!",1);
								
							}else{
								$t_err=mysqli_error($db_link);
								if(mb_strpos($t_err,"Duplicate entry")===0){
									$t_err="Вариант уже существует";
								}else{
									$t_err="Ошибка соединения";
								}
								nomq($mq,"^Can't insert VARIANT `".$v."` (".$t_err.")!");
							}
						}
						
						// TE-V:BLOCK:4.3 - Удаление вариантов
						// Формирование запроса на удаление вариантов
						$ac=count($a_del);
						if($ac>0){
							$qadd="";
							foreach($a_del as $k=>$v){
								// Собрать id вариантов
								if($qadd){$qadd.=",";}
								$qadd.=$a2[$k];
							}
							
							// Удаление элементов
							if($qadd){
								$query='DELETE FROM `'.$dbt_mrkv.'` WHERE `mark_id`="'.$tag["id"].'" AND `id` IN ('.$qadd.') LIMIT '.$ac;
								$mq=mq($query,0);
								if($mq){
									$ar=mysqli_affected_rows($db_link);
									sp("Delete VARIANTS successful (".$ar." of ".$ac.") !",1);
								}else{
									sp("VARIANTS not deleted (".$ac." ROWS) !",2);
								}
							}
						}
					}
				} // ==== TAGS ENGINE ==== VARS
				
			
			}else{
				sp("TAG `".$tag["nname"]."` ALREADY EXISTED!",2);
			}
			
		}else{
			sp("DON'T SAVE ANYTHING",2);
		}
		
		// Действие после сохранения
		//ref($pg_full.$zp),0);
		$t_show=2;
		
	// SAVE - THEME
	}elseif($t_save==3){
		$zp=ci($zp);
		
		// Корректировка имени
		$_POST["label"]=preg_replace($mask_tag,'',$_POST["label"]);
		$_POST["label"]=mres($_POST["label"]);
		
		// Обработка текста
		$h_about = preg_replace($mask_text,'$1&laquo;$2&raquo$3',$_POST["essence"]);
		$h_about = preg_replace('/\-\s+/','— ',$h_about);
		
		// Обработка цветов
		$h_ct = preg_replace('/[^0-9A-z#]/','',$_POST["col_text"]);
		$h_cb = preg_replace('/[^0-9A-z#]/','',$_POST["col_back"]);
		
		// Формирование запроса в зависимости от типа сохранения
		if($t_sat==1){
			$qadd_1='INSERT INTO';
			$qadd_2='
				,`date_c`="'.date("Y-m-d").'"
				';
		}else{
			$qadd_1='UPDATE';
			$qadd_2='WHERE `id`="'.$zp.'" LIMIT 1';
		}
		
		// Попытка сохранить на указанное место
		$h_newnum=ci($_POST["trynew"]);
		if(cr(8) && $t_sat==1 && $h_newnum){
			// Проверка отсутствия записи
			$query='SELECT `id` FROM `'.$dbt_mrkg.'` WHERE `id`="'.$h_newnum.'" LIMIT 1';
			$mq=mq($query,1);
			if(!$mq || $mq!==false){
				$qadd_2.=',`id`="'.$h_newnum.'"';
			}
		}
		
		$query=$qadd_1.' `'.$dbt_mrkg.'` 
				SET
				`name`="'.$_POST["label"].'", 
				`essence`="'.$h_about.'", 
				`colt`="'.$h_ct.'", 
				`colb`="'.$h_cb.'" 
				'.$qadd_2;
		$mq=mq($query,0);
		if($mq){
			// Если создание - задать id
			if($t_sat==1){
				$zp=$h_newnum?$h_newnum:mysqli_insert_id($db_link);
			}
		}else{
			nomq($mq);
		}

		// Действие после сохранения
		if($_POST["ret"]=="new"){
			$t_new=3;
		}else{
			//ref($pg_full.$zp),0);
			$t_show=3;
		}
	
	
	// SAVE - SETTINGS (отвязан от кода)
	}elseif($t_save==a2c("settings")){
		
		// Check POST
		$h_arf=$_POST["rota"]?"1":"0";
		$h_ara=ci($_POST["rots"]);
			if($h_ara<1 || $h_ara>3){$h_ara=1;$h_arf=0;}
		$h_ssd=ci($_POST["ss_dur"]);
			if($h_ssd>60){$h_ssd=60;}
		$h_ssf=$_POST["ss_load"]?"1":"0";
		$h_pln=ci($_POST["pl_num"]);
			if($h_pln<2 || $h_pln>10){$h_pln=5;}
		
		// Формирование запроса
		$query='UPDATE `'.$dbt_uset.'` 
				SET 
				`pl_n`="'.$h_pln.'", 
				`ss_d`="'.$h_ssd.'", 
				`ss_f`="'.$h_ssf.'", 
				`ar_a`="'.$h_ara.'", 
				`ar_f`="'.$h_arf.'" 
				WHERE `id`="'.$u["id"].'" 
				LIMIT 1';
		$mq=mq($query,1);
		if($mq){
			// Перейти к настройкам
			$t_show=$t_save;
			sp("Settings is saved!",1);
		}else{
			sp("Can't UPDATE settings!",2);
		}
	
	// SAVE - USER (отвязан от кода)
	}elseif($t_save==a2c("user")){
		$zp=ci($zp);
		
		function checkLogin($l,$mask){
			// Подготовка логина
			$reg_ln = mb_strtolower($l);
			$reg_ln = mres(preg_replace($mask,'',trim($reg_ln)));
		
			// Проверка, соответствует ли введенный логин преобразованному
			if( mb_strlen($reg_ln)<3 || $reg_ln != $l){
				return false;
			}
			return $reg_ln;
		}
		
		function checkPassword($p,$p2,$mask){
			// Проверка пароля
			$reg_pc=$p;
			
			// Пароль длиной от 3 символов, отсутствуют запрещённые символы
			if( mb_strlen($reg_pc)<3 || preg_match($mask, $reg_pc)){
				$p=false;
			}
			
			if($p){
				// Секьюр-пароль
				$reg_pc=mres($reg_pc);
				//	Проверка совпадения паролей
				$reg_pc2=$p2;
				if($reg_pc==$reg_pc2){
					return $reg_pc;
				}
			}
			return false;
		}
		
		// Функция генерации соли для пароля
		function ps(){
			return bin2hex(random_bytes(5));	// 10 symbols
		}

		// Презумпция виновности (потому что ошибка вероятнее)
		$flag_go=0;

		// Если режим создания пользователя
		if($t_sat==1){
			
			// Подготовка логина
			$reg_ln=checkLogin($_POST["login"],$mask_login);
			
			// Если логин легален
			if($reg_ln){
			
				// Запрос в базу по логину
				$query='SELECT `login` 
						FROM `'.$dbt_ulist.'` 
						WHERE `login`="'.$reg_ln.'" 
						LIMIT 1';
				$mq=mq($query,1);
				
				// Если такой логин не зарегестрирован
				if(!$mq && $mq!==false){
					// Начать процедуру регистрации в базе данных
					
					// Подготовка пароля
					$reg_pc=checkPassword($_POST["pass"],$_POST["pass2"],$mask_pass);
					
					// Если пароль легален
					if($reg_pc){
						// Криптокодирование пароля
						$reg_slt = ps();	// salt
						$reg_pc = pc($reg_pc.$reg_slt, $reg_ln);

						// Формирование запроса к базе данных на создание строки
						$query='INSERT INTO `'.$dbt_ulist.'` 
								SET 
								`login`="'.$reg_ln.'", 
								`pcode`="'.$reg_pc.'", 
								`salt`="'.$reg_slt.'", 
								`date_c`="'.date("Y-m-d").'", 
								`power`="1"';
						$mq=mq($query,0,$ar,$lin);
					
						// Если запрос выполнен успешно
						if($mq){
							
							$t_show=1;
							
							if($_POST["autologin"]==1){
								// Установить соответствующие куки // BMS: C
								$auth_ut=time()+60*60*24;	// Uptime: На день по времени сервера
								setCookie("logname",$reg_ln,$auth_ut,"/");
								setCookie("passcode",$reg_pc,$auth_ut,"/");
								
								// Установить значения переменных пользователя
								$u["l"]=$reg_ln;
								$u["r"]=1;
								
								// Перейти к странице пользователей
								$t_show=a2c("user");
							}
							
							sp("Регистрация успешно пройдена!",1);
							
							// Формирование запроса к базе данных на создание настроек
							$query='INSERT INTO `'.$dbt_uset.'` 
									SET 
									`id`="'.$lin.'"';
							$mq=mq($query,0);
							// Если запрос выполнен успешно
							if($mq){
								sp("Настройка успешно проведена!",1);
							}else{
								nomq($mq);
							}
							
							// Перейти к содержанию страницы
							$flag_go=1;
							$zp=0;
						
						// Если запрос выполнен не успешно (вставка не удалась)
						}else{
							$h_err="ОШИБКА БД: Невозможно сохранить учетную запись!";
						}
					
					// Пароль не легален
					}else{
						$h_err="Пароль содержит запрещенные символы или не равен контрольному!";
					}
				
				// Если логин уже существует
				}else{
					$h_err="Пользователь с логином &laquo;".$reg_ln."&raquo; уже существует!";
				}
			
			// Если логин не легален
			}else{
				$h_err="Ошибка: пользователь не найден!";
			}
			
		// Если режим смены логина/пароля
		}else{

			// Если есть новый пароль
			if(isset($_POST["npass"])){
				// РЕЖИМ СМЕНЫ ПАРОЛЯ
				
				$reg_pc=checkPassword($_POST["pass"],$_POST["pass"],$mask_pass);
				$chg_pc=checkPassword($_POST["npass"],$_POST["npass2"],$mask_pass);
				
				// Получение соли //ADD PASS
				$query='SELECT `salt` FROM `'.$dbt_ulist.'` WHERE `login`="'.$u["l"].'" LIMIT 1';
				$mq=mq($query,1);
				if($mq){
					$u_salt=neat()["salt"];
				}else{
					$u_salt=false;
				}
				
				// Если соль получена и пароль верный
				if($u_salt && pc($reg_pc.$u_salt,$u["l"])==$u["p"]){
					// Если пароли легальны
					if($reg_pc && $chg_pc){
						// Криптокодировать новый пароль
						$chg_salt = ps();
						$chg_pc=pc($chg_pc.$chg_salt,$u["l"]);
						
						// Сменить логин и пароль (+соль)
						$query='UPDATE `'.$dbt_ulist.'` 
								SET 
								`pcode`="'.$chg_pc.'",
								`salt`="'.$chg_salt.'" 
								WHERE `id`="'.$u["id"].'" 
								LIMIT 1';
						$mq=mq($query,0);
						// Если успешно
						if($mq){
							// Установить соответствующие куки // BMS: C
							$auth_ut=time()+60*60*24;	// Uptime: На день по времени сервера
							setCookie("passcode",$chg_pc,$auth_ut,"/");
							$flag_go=1;
							sp("Пароль успешно изменен!",1);
						}else{
							$h_err="Пароль не был сменен!";
						}
					}else{
						$h_err="Неверные символы в пароле или сам пароль не соответствует!";
					}
				}else{
					$h_err="Пароль не соответствует истине!";
				}
				
			// Если нет нового пароля
			}else{
				// РЕЖИМ СМЕНЫ ЛОГИНА
				
				$chg_ln=checkLogin($_POST["login2"],$mask_login);
				$chg_pc=checkPassword($_POST["pass"],$_POST["pass"],$mask_pass);
				
				// Получение соли// ADD PASS
				$query='SELECT `salt`,`pcode` FROM `'.$dbt_ulist.'` WHERE `login`="'.$u["l"].'" LIMIT 1';
				$mq=mq($query,1);
				if($mq){
					$u_salt=neat()["salt"];
				}else{
					$u_salt=false;
				}
				
				// Если пароль верный
				if(pc($chg_pc.$u_salt,$u["l"])==$u["p"]){
					// Если логин и пароль легальны
					if($chg_ln && $chg_pc){
						// Проверка доступности
											
						$query='SELECT `login` 
								FROM `'.$dbt_ulist.'` 
								WHERE `login`="'.$chg_ln.'" 
								LIMIT 1';
						$mq=mq($query,1);
						
						// Если логин не занят
						if($chg_ln!=$u["l"] && !$mq && $mq!==false){
							
							// Криптокодировать новый пароль
							$chg_salt = ps();
							$chg_pc=pc($chg_pc.$chg_salt,$chg_ln);
							
							// Сменить логин и пароль
							$query='UPDATE `'.$dbt_ulist.'` 
									SET 
									`login`="'.$chg_ln.'", 
									`pcode`="'.$chg_pc.'", 
									`salt`="'.$chg_salt.'" 
									WHERE `id`="'.$u["id"].'"
									LIMIT 1';
							$mq=mq($query,0);
							// Если успешно
							if($mq){
								// Установить соответствующие куки // BMS: C
								$auth_ut=time()+60*60*24;	// Uptime: На день по времени сервера
								setCookie("logname",$chg_ln,$auth_ut,"/");
								setCookie("passcode",$chg_pc,$auth_ut,"/");
								
								$flag_go=1;
								sp("Логин успешно изменен!",1);
							}else{
								$h_err="Логин не был сменен!";
							}
							
						}else{
							$h_err="Такой логин уже занят!";
						}
						
					}else{
						$h_err="Неверные символы в логине или пароле!";
					}
				}else{
					$h_err="Пароль не соответствует истине!";
				}
			}
		}
		
		// Если есть ошибки
		if(!$flag_go){
			// Вывод ошибок
			if($h_err!=""){sp($h_err,2);}else{sp("Что-то пошло не так!",2);}
			exit;
		}
	}
}

// ------------------------------------------------------------

// ----------	##  #   ###   #   #
// ----------	# # #   ##    # # #
// ----------	#  ##   ###    # #

// ------------------------------------------------------------

// R-MODE: NEW
if($t_new){
	// NEW - PICTURE (отвязан от цифры)
	if($t_new==a2c("node")){
		//<label for=""><input type="checkbox" name="ss_load" value="1" id="c_sl" '.$h_arf.'/>&nbsp;Do not wait for loading!</label>
		$h_ins=(cr(9) && $zp)?'<input type="hidden" name="trynew" value="'.$zp.'" />':'';
		$hb_main.='
		<form name="epic_new" method="POST" action="'.$pg_full.$zt.'/'.$zp.'/save" enctype="multipart/form-data">
			<input type="hidden" name="sat" value="new" />
			'.$h_ins.'
			<table border=0 cellpadding=4 cellspacing=0>
			<tr><td colspan=2 align="center"><h4>NEW ITEM</h4></td></tr>
			<tr><td align="right">Name:</td><td><input type="text" name="label" value="" size="20" maxlength="60" /></td></tr>
			<tr><td align="right">Source:</td><td><input type="file" name="picture" /></td></tr>
			<tr><td align="right">Tags:</td><td><input type="text" name="tags" value="" size="30" /></td></tr>
			<tr><td align="right">About:</td><td><textarea name="about"></textarea></td></tr>
			<tr><td align="right">Return:</td><td><label for="r_n"><input type="radio" name="ret" value="new" id="r_n" />New</label><br><label for="r_p"><input type="radio" name="ret" value="pic" id="r_p" checked />Loaded</label></td></tr>
			<tr><td colspan=2 align="right"><input type="submit" value="SAVE" /></td></tr>
			</table>
		</form>
		
		';
	
	// NEW - THEME (отвязан от цифры)
	}elseif($t_new==a2c("theme")){

		$h_ins=($zp)?'<input type="hidden" name="trynew" value="'.$zp.'" />':'';
		$hb_main.='
		<form name="theme_new" method="POST" action="'.$pg_full.$zt.'/'.$zp.'/save">
				'.$h_ins.'
				<table border=0 cellpadding=4 cellspacing=0>
				<tr><td colspan=2 align="center"><h4>NEW THEME</h4></td></tr>
				<tr><td align="right">Name:</td><td><input type="text" name="label" value="" size="20" maxlength="30" /></td></tr>
				<tr><td align="right">Text color:</td><td><input type="text" name="col_text" value="#000000" maxlength="15" /></td></tr>
				<tr><td align="right">Background color:</td><td><input type="text" name="col_back" value="#ffffff" maxlength="15" /></td></tr>
				<tr><td align="right">About:</td><td><textarea name="about"></textarea></td></tr>
				<tr><td align="right">Return:</td><td><label for="r_n"><input type="radio" name="ret" value="new" id="r_n" checked />New</label><br><label for="r_p"><input type="radio" name="ret" value="pic" id="r_p" />Loaded</label></td></tr>
				<tr><td colspan=2 align="right"><input type="submit" value="SAVE" /></td></tr>
				</table>
			</form>
		
		';
	
	// NEW - USER (отвязан от цифры)		// ADMIN POWER
	}elseif(cr(9) && $t_new==a2c("user")){

		$hb_main.='
		<form name="user_new" method="POST" action="'.$pg_full.$zt.'/save">
				<input type="hidden" name="sat" value="new" />
				<table border=0 cellpadding=4 cellspacing=0>
				<tr><td colspan=2 align="center"><h4>NEW USER</h4></td></tr>
				<tr><td align="right">Login:</td><td><input type="text" name="login" value="" size="20" maxlength="20" /></td></tr>
				<tr><td align="right">Password:</td><td><input type="password" name="pass" value="" maxlength="20" /></td></tr>
				<tr><td align="right">Repeat password:</td><td><input type="password" name="pass2" value="" maxlength="20" /></td></tr>
				<tr><td align="right"><label for="c_al">Autologin:</label></td><td><input type="checkbox" name="autologin" id="c_al" value="1" /></td></tr>
				<tr><td colspan=2 align="right"><input type="submit" value="CREATE USER" /></td></tr>
				</table>
			</form>
		
		';
		
	// NEW - _else_
	}else{
		// Показать вместо нового
		$t_show=$t_new;
	}
}

// ------------------------------------------------------------

// ----------	###   ###    #   ###
// ----------	##    #  #   #    #
// ----------	###   ###    #    #

// ------------------------------------------------------------

// R-MODE: EDIT
if($t_edit){
	
	// EDIT - PICTURE
	if($t_edit==a2c("node")){
		// Приведение номера элемента
		$zp=ci($zp);
		
		// Проверка наличия элемента
		$query='SELECT `id`,`name`,`about`,`fold_name`,`base_name` 
				FROM `'.$dbt_items.'` 
				WHERE `id`="'.$zp.'" 
				LIMIT 1';
		$mq=mq($query,1);
		if($mq){
			$img=neat();
			
			// Получить теги (id, name)
			$tags=getTags($zp);
			$img["tags"]="";
			if($tags){
				foreach($tags as $t){
					if($img["tags"]){$img["tags"].=", ";}
					$img["tags"].=$t["name"];
				}
			}
			$hb_main.='
			<form name="epic_upd" method="POST" action="'.$pg_full.$zt.'/'.$img["id"].'/save" enctype="multipart/form-data">
				<input type="hidden" name="sat" value="upd" />
				<input type="hidden" name="ret" value="pic" />
				<table border=1 cellpadding=4 cellspacing=0>
					<tr>
						<td>Source<br>
						<img src="'.$pg_full.$img["fold_name"].$img["base_name"].'" width=100 /></td>
						<td rowspan=2>
							Name:<input type="text" name="label" value="'.$img["name"].'" size="20" maxlength="60" /><br>
							Tags:<input type="text" name="tags" value="'.$img["tags"].'" /><br>
							About:<textarea name="about">'.$img["about"].'</textarea><br>
							<input type="submit" value="^ SEND ^" />
						</td>
					</tr>
					<tr>
						<td><input type="file" name="picture" /></form></td>
					</tr>
					<tr>
						<td colspan=2><form action="'.$pg_full.$zt.'/'.$zp.'/delete" method="get"><input type="submit" value="DELETE" /></form></td>
					</tr>
				</table>
			
			';
		}else{
			nomq($mq);
		}
		
	// EDIT - TAG
	}elseif($t_edit==a2c("tag")){
		// Приведение номера элемента
		$zp=ci($zp);
		
		// Проверка наличия ярлыка
		$query='SELECT `id`,`name`,`essence` FROM `'.$dbt_mrkt.'` WHERE `id`="'.$zp.'" LIMIT 1';
		$mq=mq($query,1);
		// Если тег найден
		if($mq){
			$tag=neat();
			
			// Получить варианты тега (id, name)
			$vars=getVars($zp);
			
			// Привести варианты к вводимому виду (вариант_1, вариант_2, ...)
			$tag["vars"]="";
			foreach($vars as $v){
				if($tag["vars"]){$tag["vars"].=", ";}
				$tag["vars"].=$v["var"];
			}
			// Получить группу
			$group=getGroup($zp);
			
			// Получить все группы для добавления
			$h_gsel='';
			$query='SELECT `id`,`name` FROM  `'.$dbt_mrkg.'` WHERE 1 ORDER BY `name`';
			$mq=mq($query,1);
			if($mq){
				$groups=neat($mq);
			}elseif($mq===false){
				print "CAN'T CONNECT TO GROUP DB!<br>";
			}
			// Создание списка групп
			if(is_array($groups)){
				foreach($groups as $g){
					$h_gsel.='<option value="'.$g["id"].'">'.$g["name"].'</option>';
				}
				$h_gsel='<select name="autogroup">'.$h_gsel.'</select>';
			}
			
			
			// Вывод таблицы редактирования
			$hb_main.='
			<form name="tag_upd" method="POST" action="'.$pg_full.$zt.'/'.$tag["id"].'/save" enctype="multipart/form-data">
				Name:<input type="text" name="label" value="'.$tag["name"].'" size="20" maxlength="30" /><br>
				Group:<input type="text" name="group" value="'.$group["name"].'" maxlength="15" />'.$h_gsel.'<br>
				Vars:<input type="text" name="vars" value="'.$tag["vars"].'" /><br>
				About:<textarea name="about">'.$tag["essence"].'</textarea><br>
				<input type="submit" value="^ SEND ^" />
			</form>
			';
			$hb_main.='<form action="'.$pg_full.$zt.'/'.$tag["id"].'/delete" method="get">...OR MAYBE <input type="submit" value="DELETE" /></form>';
		
		
		// Если тег не найден
		}else{
			nomq($mq);
		}
		
	// EDIT - THEME
	}elseif($t_edit==a2c("theme")){
		// Приведение номера элемента
		$zp=ci($zp);
		
		// Проверка наличия группы
		$query='SELECT `id`,`name`,`essence`,`colt`,`colb` FROM `'.$dbt_mrkg.'` WHERE `id`="'.$zp.'" LIMIT 1';
		$mq=mq($query,1);
		// Если группа найдена
		if($mq){
			$group=neat();
			
			// Вывод таблицы редактирования
			$hb_main.='
			<form name="tag_upd" method="POST" action="'.$pg_full.$zt.'/'.$group["id"].'/save">
				Name:<input type="text" name="label" value="'.$group["name"].'" size="20" maxlength="30" /><br>
				Text color:<input type="text" name="col_text" value="'.$group["colt"].'" /><br>
				Background color:<input type="text" name="col_back" value="'.$group["colb"].'" /><br>
				About:<textarea name="about">'.$group["essence"].'</textarea><br>
				<input type="submit" value="> SEND <" />
			</form>
			';
			$hb_main.='<form action="'.$pg_full.$zt.'/'.$group["id"].'/delete" method="get">...OR MAYBE <input type="submit" value="DELETE" /></form>';
		
		// Если тег не найден
		}else{
			nomq($mq);
		}
	
	// EDIT - USER (отвязан от кода)
	}elseif($t_edit==a2c("user")){
		
		// Смена логина
		if($_POST["xmode"]=="l"){
			$hb_main.='
			<h4>Смена логина</h4>
			<form name="user_login" method="POST" action="'.$pg_full.$zt.'/save">
				<input type="hidden" name="sat" value="upd" />
				New login:<input type="text" name="login2" value="'.$u["l"].'" size="20" maxlength="30" /><br>
				Password:<input type="password" name="pass" value="" /><br>
				<input type="submit" value="CHANGE LOGIN" />
			</form>
			';
			
		// Смена пароля
		}elseif($_POST["xmode"]=="p"){
			$hb_main.='
			<h4>Смена пароля</h4>
			<form name="user_password" method="POST" action="'.$pg_full.$zt.'/save">
				<input type="hidden" name="sat" value="upd" />
				Password:<input type="password" name="pass" value="" /><br>
				New password:<input type="password" name="npass" value="" /><br>
				Repeat new password:<input type="password" name="npass2" value="" /><br>
				<input type="submit" value="CHANGE PASSWORD" />
			</form>
			';
			
		// Не определено	
		}else{
			$hb_main.='<h4>Выберите режим</h4>';
			$hb_main.='<form action="'.$pg_full.$zt.'/edit" method="post"><input type="hidden" name="xmode" value="l" /><input type="submit" value="CHANGE LOGIN" /></form>';
			$hb_main.='<form action="'.$pg_full.$zt.'/edit" method="post"><input type="hidden" name="xmode" value="p" /><input type="submit" value="CHANGE PASSWORD" /></form>';
		}
	
	}
}

// ------------------------------------------------------------

// ----------	###    ###   #    ###   ###  ###
// ----------	#  #   ##    #    ##     #   ##
// ----------	###    ###   ###  ###    #   ###

// ------------------------------------------------------------

// R-MODE: DELETE
if($t_del){
	
	// Установка флага решительного удаления
	$flag_del=($_POST["delconf"]==1)?true:false;
	
	// DELETE - PICTURE
	if($t_del==a2c("node")){
		// Если есть подтверждение удаления
		if($flag_del){
			
			$zp=ci($zp);
			
			// Проверка на аргумент
			if($zp){
				$query='SELECT 
						`p`.`fold_name` AS `f`, 
						`p`.`base_name` AS `n`, 
						`p`.`ranid` AS `r`, 
						COUNT(`t`.`id`) AS `t` 
						FROM `'.$dbt_items.'` AS `p`, `'.$dbt_mrkl.'` AS `t` 
						WHERE `p`.`id`="'.$zp.'" 
						AND `t`.`item_id`=`p`.`id` 
						LIMIT 1';
				$mq=mq($query,1);
				if($mq){
					// Запись найдена
					$item=neat();
					// Если файл присутствует или пустой
					if($item["n"]!=null || $item["n"]==""){
						
						// Удаление связей
						$query='DELETE
								FROM `'.$dbt_mrkl.'` 
								WHERE `item_id`="'.$zp.'"
								LIMIT '.$item["t"];
						$mq=mq($query,0,$ar);
						if($mq){
							if($ar==0){
								sp("Связи не обнаружены!",3);
							}else{
								// Связи с элементом удалены
								sp("Удалено ".$ar." связей!",1);
							}
							
						}else{
							// Связи не удалены!
							nomq($mq);
						}
						
						// Удалить файл
						$h_pic=$item["f"].$item["n"];
						// Если файла нет или он удален
						if(!is_file($h_pic) || unlink($h_pic)){
							// Переставить непрерывный номер на последнюю запись
							
							// Определить максимальный элемент
							$query='SELECT MAX(`ranid`) AS `m` FROM `'.$dbt_items.'`';
							$mq=mq($query,0);
							if($mq){
								$h_ranid=neat()["m"];
								// Смена непрерывного номера
								$query='UPDATE `'.$dbt_items.'` SET `ranid`="'.$item["r"].'" WHERE `ranid`="'.$h_ranid.'" LIMIT 1';
								$mq=mq($query,0);
								if($mq){
								
									// Удалить запись
									$query='DELETE
											FROM `'.$dbt_items.'` 
											WHERE `id`="'.$zp.'"
											';
									$mq=mq($query,0,$ar);
									if($mq){
										// Если запись удалена, вывести все записи
										$zp=0;
										$t_show=1;
										sp("Запись успешно удалена!",1);
									}else{
										nomq($mq);
									}
								}else{
									sp("Не удалось создать непрерывный индекс!",2);
								}
							}else{
								sp("Не удалось определить непрерывный индекс!",2);
							}
						}else{
							sp("Файл не удалось удалить!",2);
						}
					
					}else{
						// Если номер некорректен, просто вывести записи
						$zp=0;
						$t_show=1;
					}
				// Элемент не найден!
				}else{
					nomq($mq);
				}
			}else{
				// Если номер некорректен, просто вывести записи
				$t_show=1;
			}
		
		// Если нет подтверждения удаления
		}else{
			$hb_main.='
			<form name="del" method="POST" action="'.$pg_full.$zt.'/'.$zp.'/delete">
				<input type="hidden" name="delconf" value="1" />
				Хотите удалить элемент /<b>'.$zp.'</b>/ ?
				<input type="submit" value="DELETE" />
				<input type="button" value="NOPE" onClick="history.go(-1);" />
			</form>
			';
		}
	
	// DELETE - TAG
	}elseif($t_del==a2c("tag")){
		// Привести номер к целому
		$zp=ci($zp);
		
		// Если есть подтверждение удаления
		if($flag_del){
			
			// Проверка на аргумент
			if($zp){
				
				// Удаление вариантов
				$query='DELETE 
						FROM `'.$dbt_mrkv.'` 
						WHERE `mark_id`="'.$zp.'"';
				$mq=mq($query,0,$ar);
				if($mq){
					// Варианты удалены
					sp("Варианты успешно удалены! (количество: ".$ar.")",1);
					
					// Удаление связей
					$query='DELETE
							FROM `'.$dbt_mrkl.'` 
							WHERE `mark_id`="'.$zp.'"';
					$mq=mq($query,0,$ar);
					if($mq){
						// Связи с элементом удалены
						sp("Связи успешно удалены!".($ar?"(количество: ".$ar.")":""),1);

						// Удалить ярлык
						$query='DELETE FROM `'.$dbt_mrkt.'` WHERE `id`="'.$zp.'" LIMIT 1';
						$mq=mq($query,0);
						if($mq){
							// Если ярлык удален, вывести все ярлыки
							$flag_null=1;
							sp("Тег успешно удален!",1);
						}else{
							// Ярлык не удален!
							nomq($mq);
						}
						
					// Связи не удалены!
					}else{
						nomq($mq);
					}
					
				// Варианты не удалены
				}else{
					nomq($mq);
				}
			}else{
				// Если номер неккоректен, просто вывести теги
				$flag_null=1;
			}
		
		// Если нет подтверждения удаления
		}else{
			// Запрос на параметры тега
			$query='SELECT 
					`n`.`name` AS `nn`, 
					COUNT(`v`.`id`) AS `nv` 
					FROM `'.$dbt_mrkt.'` AS `n`, `'.$dbt_mrkv.'` AS `v` 
					WHERE `n`.`id`="'.$zp.'" 
					AND `v`.`mark_id`=`n`.`id` 
					LIMIT 1';
			$mq=mq($query,1);
			if($mq){
				$tag=neat();
				if($tag["nv"]){
					$hb_main.='
					<form name="del" method="POST" action="'.$pg_full.$zt.'/'.$zp.'/delete">
						<input type="hidden" name="delconf" value="1" />
						Хотите удалить тег &laquo;<b>'.$tag["nn"].'</b>&raquo;, его варианты ('.$tag["nv"].') и все связи?
						<input type="submit" value="DELETE" />
						<input type="button" value="NOPE" onClick="history.go(-1);" />
					</form>
					';
				}else{
					// Если нет тега
					$flag_null=1;
				}
			}else{
				$flag_null=1;
				nomq($mq);
			}
		}
		
		// Если какая-либо ошибка/неточность
		if($flag_null){
			$zp=0;
			$t_show=2;
		}
		
	// DELETE - THEME
	}elseif($t_del==a2c("theme")){
		// Привести номер к целому
		$zp=ci($zp);
		
		// Если есть подтверждение удаления
		if($flag_del){
			
			// Проверка на аргумент
			if($zp){
				
				// Удаление тегов из группы
				$query='UPDATE `'.$dbt_mrkt.'` 
						SET 
						`group_id`="0" 
						WHERE `group_id`="'.$zp.'"';
				$mq=mq($query,0,$ar);
				if($mq || $mq!==false){
					// Группировка удалена
					sp($ar?"Разгруппировка успешно проведена! (тегов: ".($ar).")":"Разгруппировка пропущена!",1);
					
					// Удалить группу
					$query='DELETE FROM `'.$dbt_mrkg.'` WHERE `id`="'.$zp.'" LIMIT 1';
					$mq=mq($query,1);
					if($mq){
						// Если группа удалена, вывести все группы
						$flag_null=1;
						sp("Тема успешно удалена!",1);
					}else{
						// Группа не удален!
						nomq($mq);
					}
					
				// Группировка не удалена!
				}else{
					nomq($mq);
				}
			}else{
				// Если номер неккоректен, просто вывести теги
				$flag_null=1;
			}
		
		// Если нет подтверждения удаления
		}else{
			// Запрос на параметры группы
			$query='SELECT 
					`g`.`name` AS `ng`, 
					COUNT(`n`.`id`) AS `nn` 
					FROM `'.$dbt_mrkt.'` AS `n`, `'.$dbt_mrkg.'` AS `g` 
					WHERE `g`.`id`="'.$zp.'" 
					AND `n`.`group_id`=`g`.`id` 
					LIMIT 1';
			$mq=mq($query,1);
			if($mq){
				// Если группа существует
				$group=neat();
				$hb_main.='
				<form name="del" method="POST" action="'.$pg_full.$zt.'/'.$zp.'/delete">
					<input type="hidden" name="delconf" value="1" />
					Хотите удалить тему &laquo;<b>'.$group["ng"].'</b>&raquo; и упоминания ('.$group["nn"].')?
					<input type="submit" value="DELETE" />
					<input type="button" value="NOPE" onClick="history.go(-1);" />
				</form>
				';
			}else{
				// Если группы нет
				$flag_null=1;
				nomq($mq);
			}
		}
		
		// Если какая-либо ошибка/неточность
		if($flag_null){
			$zp=0;
			$t_show=3;
		}
	
	// DELETE - USER		// ADMIN POWER
	}elseif(cr(8) && $t_del==a2c("user")){
		// Привести номер к целому
		$zp=ci($zp);
		
		// Если есть подтверждение удаления
		if($flag_del){
			
			// Проверка на аргумент
			if($zp){
				
				// Удаление пользователя
				$query='DELETE FROM `'.$dbt_ulist.'` WHERE `id`="'.$zp.'" AND `power`<"'.$u["r"].'" LIMIT 1';
				$mq=mq($query,0);
				// Пользователь удален
				if($mq){
					sp("Пользователь успешно удален.",1);
				// Пользователь не удален
				}else{
					sp("Пользователь не удален!",2);
				}
				
				// Удалить настройки
				$query='DELETE FROM `'.$dbt_uset.'` WHERE `id`="'.$zp.'" AND `power`<"'.$u["r"].'" LIMIT 1';
				$mq=mq($query,0);
				// Настройки удалены
				if($mq){
					sp("Настройки пользователя успешно удалены.",1);
				}else{
					// Настройки не удалены
					sp("Настройки не удалены!",2);
				}

			}else{
				sp("Номер пользователя не корректен!",2);
			}
			
			$flag_null=1;
		
		// Если нет подтверждения удаления
		}else{
			// Запрос на пользователя
			$query='SELECT `login` FROM `'.$dbt_ulist.'` WHERE `id`="'.$zp.'" AND `power`<"'.$u["r"].'" LIMIT 1';
			$mq=mq($query,1);
			if($mq){
				// Если пользователь есть и он - не я
				$user=neat();
				$hb_main.='
				<form name="del_user" method="POST" action="'.$pg_full.$zt.'/'.$zp.'/delete">
					<input type="hidden" name="delconf" value="1" />
					Хотите удалить пользователя &laquo;<b>'.$user["login"].'</b>&raquo;?
					<input type="submit" value="DELETE" />
					<input type="button" value="NOPE" onClick="history.go(-1);" />
				</form>
				';
			}else{
				// Если пользователя нет
				$flag_null=1;
				nomq($mq);
			}
		}
		
		// Если какая-либо ошибка/неточность
		if($flag_null){
			$zp=0;
			// Если пользователь из администрации, то к списку юзеров
			if(cr(7)){
				$t_show=a2c("user");
			// Если обычный пользователь - к картинкам
			}else{
				$t_show=a2c("node");
			}
		}
	}
}

// ------------------------------------------------------------

// ----------	 /##   # #    ##    #   #
// ----------	  \    ###   #  #   # # #
// ----------	 ##/   # #    ##     # #

// ------------------------------------------------------------

// MODE: SHOW (default)
if($t_show){
	
	// SHOW - PICTURE
	if($t_show==a2c("node")){
		if(is_numeric($zt)){
			$zp=ci($zt);
		}
		
		// Если задан номер или режим рандом
		if($zp || $zm=="rand"){
			$qadd="";
			
			// Выбор случайной картинки (при соответствующем режиме)
			if($zm=="rand"){
				// Если не последовательный
				if(!$zp){
					// Запрос на максимальный номер элемента случайности
					$query='SELECT COUNT(`id`) AS `c` FROM `'.$dbt_items.'`';
					$mq=mq($query,1);
					if($mq){
						// Определение случайной картинки
						$h_max=neat()["c"];
						$qadd='`ranid`="'.rand(1,$h_max).'"';
					}
				// Если последовательный
				}else{
					$zp=ci($zp);
					$qadd='`ranid`="'.$zp.'"';
				}
			}
			
			// Нормализация запроса
			if(!$qadd){$qadd='`id`="'.$zp.'"';}
			
			// Формирование запроса
			$query='SELECT `id`,`ranid`,`fold_name`,`base_name`,`bonus_1`,`date_l` 
					FROM `'.$dbt_items.'` 
					WHERE '.$qadd.' 
					LIMIT 1';
			$mq=mq($query,1);
			if($mq){
				$img=neat();
				
				$h_td=date("Y-m-d");
				if($img["date_l"]!=$h_td){
					$query='UPDATE `'.$dbt_items.'` SET `date_l`="'.$h_td.'" WHERE `id`="'.$img["id"].'" LIMIT 1';
					$mq=mq($query,0);
				}
				
				// Замена переводов строки	// Or PHP_EOL
				//$img["about"] = preg_replace('/\r\n/','<br>',$img["about"]);
				
				// Получить теги (id, name)
				$tags=getTags($img["id"]);
				$h_tagline="";
				if($tags){
					foreach($tags as $t){
						if($h_tagline){$h_tagline.=",";}
						$h_tagline.='<a href="'.$pg_full.'tag/'.$t["id"].'">'.$t["name"].'</a>';
					}
				}
				if(!$h_ck){$hb_main.=$h_tagline.'<br>';}
				$hb_main.='<img src="'.$pg_full.$img["fold_name"].$img["base_name"].'"><br>';
				if(!$h_ck){
					$hb_main.='BONUS:'.$img["bonus_1"];
					$hb_main.='<form action="'.$pg_full.'epic/'.$zp.'/vote" method="post"><input type="hidden" name="scale" value="1" /><input type="hidden" name="power" value="1" /><input type="submit" value="+" /></form>';
					$hb_main.='<form action="'.$pg_full.'epic/'.$zp.'/vote" method="post"><input type="hidden" name="scale" value="1" /><input type="hidden" name="power" value="-1" /><input type="submit" value="–" /></form><br>';
					$hb_main.='<form action="'.$pg_full.'epic/'.$zp.'/edit" method="get"><input type="submit" value="EDIT" /></form><br>';
				}
				// Флаг только для картинки (отображать в режиме "под размер")
				$flag_op=1;
			}else{
				// Если элемент не найден
				if($mq!==false){
					// Создать запись
					$hb_main.='<form action="'.$pg_full.'epic/'.$zp.'/new" method="get"><input type="submit" value="MAKE ONE!" /></form><br>';
					$hb_main.='<input type="button" value="<== BACK" onClick="history.go(-1);" />';
				}else{
					nomq($mq);
				}
			}
			
		// Если номер не задан
		}else{
			// Получить все записи
			$query='SELECT `id`,`label` 
					FROM `'.$dbt_items.'` 
					WHERE `owner_id`="'.$u["id"].'" 
					ORDER BY `ord`';
			$mq=mq($query,1);
			if($mq){
				$imgs=neat($mq);
				$hb_main.='<table border=0 cellpadding=0 cellspacing=10 width=100%><tr class="tr_ep">';
				$h_i=0;
				foreach($imgs as $img){
					if($h_i==$u["ppl"]){$h_i=0;$hb_main.='</tr><tr class="tr_ep">';}
					$hb_main.='<td class="td_ep"><a href="'.$pg_full.'epic/'.$img["id"].'"><img src="'.$pg_full.$img["fold_name"].$img["base_name"].'" width=100 border=0"></a></td>';
					$h_i++;
				}
				$hb_main.='</tr></table>';
				
			}else{
				if($mq!==false){
					$hb_nodes.='<form action="'.$pg_full.'/epic/new" method="get"><input type="submit" value="NEW ITEM" /></form>';
				}else{
					sp("NO CONNECTION TO items DB",2);
				}
			}
		
		}
		
	// SHOW - TAG	// CHECK THIS flag_null***
	}elseif($t_show==a2c("tag")){
				
		// Если тег задан
		if($zp){
			if(!is_numeric($zp)){
				$query='SELECT `mark_id` AS `id` FROM `'.$dbt_mrkv.'` WHERE `var`="'.mb_strtolower(mres($zp)).'" LIMIT 1';
			}else{
				$query='SELECT `name` FROM `'.$dbt_mrkt.'` WHERE `id`="'.ci($zp).'" LIMIT 1';
			}
			$mq=mq($query,1);
			if($mq){
				$tag=neat();
				$tagname=isset($tag["name"])?$tag["name"]:$zp;
				$zp=isset($tag["id"])?$tag["id"]:ci($zp);
				
				$vars=getVars($zp);
				$h_varline="";
				if($vars){
					foreach($vars as $v){
						if($h_varline){$h_varline.="&ensp;|&ensp;";}
						$h_varline.=$v["var"];
					}
				}
				
				// Вставка группы
				$h_group="";
				$group=getGroup($zp);
				if($group){
					$h_group='<a href="'.$pg_full.'theme/'.$group["id"].'" style="background:'.$group["colb"].';color:'.$group["colt"].';padding:4px;border:solid 1px black;">'.$group["name"].'</a>:&emsp;';
				}
					
				$hb_main.='<h3>'.$h_group.$tagname.'</h3>';
				$hb_main.='<span style="font-family:Arial;color:gray;font-size:10pt;">'.$h_varline.'</span><br>';
				$hb_main.='<form action="'.$pg_full.$zt.'/'.$zp.'/edit" method="get"><input type="submit" value="EDIT" /></form>';
				// Запрос на наличие записей с тегом
				$query='SELECT
						`p`.`id`, 
						`p`.`fold_name`, 
						`p`.`base_name`, 
						`p`.`name` 
						FROM `'.$dbt_mrkl.'` AS `t`, `'.$dbt_items.'` AS `p` 
						WHERE `t`.`mark_id`="'.$zp.'"
						AND `p`.`id`=`t`.`item_id` 
						ORDER BY `p`.`date_c`';
				$mq=mq($query,1);
				if($mq){
					$imgs=neat($mq);
					
					foreach($imgs as $img){
						$hb_main.='<a href="'.$pg_full.'epic/'.$img["id"].'"><img src="'.$pg_full.$img["fold_name"].$img["base_name"].'" width=100 border=0"></a><br>';
					}
				}else{
					nomq($mq);
				}
			}else{
				// Имя/номер ярлыка некорректен
				sp("Тег не найден!",2);
				$flag_null=1;
			}
		// Если тег не задан
		}else{
			$flag_null=1;
		}
		
		// Если с ярлыком что-то пошло не так, вывести все
		if($flag_null){
			$query='SELECT `id`,`name` FROM `'.$dbt_mrkt.'` WHERE 1 ORDER BY `name`';
			$mq=mq($query,1);
			if($mq){
				$tags=neat($mq);
				foreach($tags as $t){
					$hb_main.='<a href="'.$pg_full.$zt.'/'.$t["id"].'">'.$t["name"].'</a>&emsp;';
				}
			}else{
				nomq($mq);
			}
		}
		
	// SHOW - THEME
	}elseif($t_show==a2c("theme")){
				
		// Если тема задана
		if($zp){
			if(!is_numeric($zp)){
				$query='SELECT `id`,`colt`,`colb` FROM `'.$dbt_mrkg.'` WHERE `var`="'.mb_strtolower(mres($zp)).'" LIMIT 1';
			}else{
				$query='SELECT `name`,`colt`,`colb` FROM `'.$dbt_mrkg.'` WHERE `id`="'.ci($zp).'" LIMIT 1';
			}
			$mq=mq($query,1);
			if($mq){
				// Тема найдена
				$group=neat();
				$h_grpname=isset($group["name"])?$group["name"]:$zp;
				$zp=isset($group["id"])?$group["id"]:ci($zp);
				
				$group["colt"]=($group["colt"]!="")?$group["colt"]:"#000000";
				$group["colb"]=($group["colb"]!="")?$group["colb"]:"#ffffff";
				
				$hb_main.='<h3 style="background:'.$group["colb"].';color:'.$group["colt"].';padding:6px;border:solid 1px black">'.$h_grpname.'</h3>';
				$hb_main.='<form action="'.$pg_full.$zt.'/'.$zp.'/edit" method="get"><input type="submit" value="EDIT" /></form>';
				// Запрос на ярлыки с группой
				$query='SELECT `id`, `name` 
						FROM `'.$dbt_mrkt.'` 
						WHERE `group_id`="'.$zp.'" 
						ORDER BY `name`';
				$mq=mq($query,1);
				if($mq){
					$tags=neat($mq);
					
					foreach($tags as $tag){
						$hb_main.='<a href="'.$pg_full.'tag/'.$tag["id"].'" style="color:'.$group["colt"].';background:'.$group["colb"].'" padding:4px;margin:4px;>'.$tag["name"].'</a><br>';
					}
				}else{
					nomq($mq);
				}
			}else{
				// Имя/номер темы некорректен
				sp("Тема не найдена!",2);
				$flag_null=1;
			}
		// Если тема не задана
		}else{
			$flag_null=1;
		}
		
		// Если с темой что-то пошло не так, вывести все
		if($flag_null){
			$query='SELECT `id`,`name`,`colt`,`colb` FROM `'.$dbt_mrkg.'` WHERE 1 ORDER BY `name`';
			$mq=mq($query,1);
			if($mq){
				$group=neat($mq);
				foreach($group as $g){
					$g["colt"]=($g["colt"]!="")?$g["colt"]:"#000000";
					$g["colb"]=($g["colb"]!="")?$g["colb"]:"#ffffff";
					$hb_main.='<a href="'.$pg_full.'theme/'.$g["id"].'" style="color:'.$g["colt"].';background:'.$g["colb"].'";padding:4px;margin:4px;border:solid 1px black;>'.$g["name"].'</a>&emsp;';
				}
			}else{
				if($mq!==false){
					$hb_main.='<form action="'.$pg_full.$zt.'/new" method="get"><input type="submit" value="NEW THEME" /></form>';
				}else{
					sp("NO CONNECTION TO DB",2);
				}
			}
		}
	
	// SHOW - SETTINGS
	}elseif($t_show==a2c("settings")){
		
		$query='SELECT `ss_d`,`ss_f`,`ar_a`,`ar_f`,`pl_n` FROM `'.$dbt_uset.'` WHERE `id`="'.$u["id"].'" LIMIT 1';
		$mq=mq($query,1);
		
		// Если настройки приняты
		if($mq){
			$sets=neat();
			
			// Form Checker Array
			$fca=array_fill(0,3,"");
			$fca[$sets["ar_a"]-1]="checked ";
			
			// Checkboxes
			$h_arf=$sets["ar_f"]?"checked":"";
			$h_ssf=$sets["ss_f"]?"checked":"";
			
			$hb_main.='<h3>SETTINGS</h3>';
			$hb_main.='
				<form name="settings" action="'.$pg_full.'settings/save" method="post">
				<fieldset>
				<legend>&emsp;Pictures&emsp;</legend>
				<label for="t_pl">Items in line:&ensp;<input type="text" name="pl_num" value="'.$sets["pl_n"].'" size=1 maxlength=2 id="t_pl" /></label><br>
				</fieldset>
				
				<fieldset>
				<legend>&emsp;<label for="c_ar"><input type="checkbox" name="rota" value="1" id="c_ar" '.$h_arf.'/>&nbsp;Auto-rotation</label>&emsp;</legend>
				<label for="r_r1"><input type="radio" name="rots" value="1" id="r_r1" '.$fca[0].'/>&nbsp;90&deg; CW</label><br>
				<label for="r_r2"><input type="radio" name="rots" value="2" id="r_r2" '.$fca[1].'/>&nbsp;180&deg;</label><br>
				<label for="r_r3"><input type="radio" name="rots" value="3" id="r_r3" '.$fca[2].'/>&nbsp;90&deg; CCW</label>
				</fieldset>
				
				<fieldset>
				<legend>&emsp;Slideshow&emsp;</legend>
				<label for="t_sd">Minimum Delay:&ensp;<input type="text" name="ss_dur" value="'.$sets["ss_d"].'" size=2 maxlength=3 id="t_sd" />&nbsp;s</label><br>
				<label for="c_sl"><input type="checkbox" name="ss_load" value="1" id="c_sl" '.$h_arf.'/>&nbsp;Do not wait for loading!</label>
				</fieldset>
				
				<input type="reset" value="PREVIOUS" />
				<input type="submit" value="SAVE SETTINGS" />
				</form>
			';
			//<label for=""><input type="checkbox" name="" value="" id="" />&nbsp;</label>
			
		}else{
			nomq($mq);
		}
	
	// SHOW - USER		// ADMIN POWER
	}elseif(cr(7) && $t_show==a2c("user")){
		
		// Получить все записи
		$query='SELECT `id`,`login`,`power`,`date_c` 
				FROM `'.$dbt_ulist.'` 
				WHERE `power`<"'.$u["r"].'" 
				LIMIT 0,30';
		$mq=mq($query,1);
		if($mq){
			$users=neat($mq);
			
			$hb_main.='<h3>Список пользователей</h3>';
			
			foreach($users as $user){
				$hb_main.='<span title="Дата регистрации: '.$user["date_c"].'"><b>['.$user["type"].']</b>&nbsp;'.$user["login"].'&ensp;<a href="'.$pg_full.'user/'.$user["id"].'/delete">&times;</a></span><br>';
			}
			
		}else{
			if($mq===false){
				sp("NO CONNECTION TO DB",2);
			}
		}
		
		if(cr(8)){
			$hb_main.='<form action="'.$pg_full.$zt.'/new" method="get"><input type="submit" value="NEW USER" /></form>';
		}
	}
}

// HTML preparing SECTION

$hb_start='
<!DOCTYPE HTML>
	<html>
		<title>\\\\`// WORD NOTES</title>
		<meta charset="utf-8" />
		<link rel="shortcut icon" href="'.$pg_full.'wn.ico" type="image/x-icon">
		<body bgcolor="white" text="black" style="font-family:Calibri;font-size:12pt;margin:0;">
';


// USES CookieVars

$hb_js='
<script type="text/javascript" src="winload.js"></script>
<script type="text/javascript"><!--
// Захватывает все данные из формы со страницы и преобразует в строку параметров (k - тип сериализации)
function pp(frm,k){
	k=1;
	var m =  document.getElementById(frm+"_0").value;
	var s = (k) ? ";" : "&";
	var e = (k) ? ":" : "=";
	
	if(m>0){
		var par="";
		// Перебор всех элементов страницы
		for (i=1;i<=m;i++){
			var a = document.getElementById(frm+"_"+i);
			
			// Исключая неинформационные типы
			if(a.type=="checkbox" && a.checked!=true){continue;}
			if(a.type=="radio" && a.checked!=true){continue;}
		
			// Кодирование частей строки
			par+=(k?"":s)+a.name+e+encodeURIComponent(a.value)+(k?s:"");
		}
		// Возврат результата (строки)
		return par;
	}
	return false;
}

var dgx=0;

function dg(){dgx+=1;}
function ds(k){dgx+=1;if(k || dgx<2){document.getElementById("dlgs").style.display="none";}dgx=0;}

function claw(){
	if(dgx<2){document.getElementById("dlgs").style.display="none";}dgx=0;
}
	
function expand(win,data){
	get(win,"m:31;"+data);
}

function copen(node){
	get("content","m:41;i:"+node+";","");
}

function modify(node,type){
	dialog("dlg1","m:32;i:"+node+";type:"+type+";");
}

// --></script>
';
$hb_non='
<script type="text/javascript"><!--
ratio=1/2;
hi='.$h_hi.';
function g(link){window.location.href="'.$pg_full.'"+link;}
/*
window.onresize=function(){
p=document.getElementById("pt");
bw=document.body.clientWidth || document.body.offsetWidth;
bh=document.body.clientHeight || document.body.offsetHeight;
r0=bh/bw;
if(r0>ratio){p.className="w";}else{p.className="h";}
}*/
function him(s){
	// s - показывает, присвоить свойства (0) или переключить (1)
	if(!s){if(hi==1){hi=0;}else{hi=1;}
	document.cookie = "hi="+hi+";path=/;expires='.date("r",strtotime("+1year")).'";}
	men=document.getElementById("menu");
	if(hi==1){
		if(!s){jar_2=men.innerHTML;}
		men.innerHTML=jar_1;
		men.style.width="100%";
	}else{
		if(!s){jar_1=men.innerHTML;}
		men.innerHTML=jar_2;
		men.style.width="30px";
	}
}
him(1);
// --></script>
';


//body {margin:0;padding:0;color:white;overflow:hidden;}
$hb_css='
<style type="text/css"><!--
html {height:100%;}
body {margin:0;padding:0;height:100%;}

div {}
	.mw		{height:100%;width:100%;background:black;}
	.mm		{height:100%;min-height:20px;width:100%;background:green;}
	.sm		{height:100%;min-height:26px;width:100%;background:crimson;}
	.nm		{height:100%;min-height:26px;width:100%;background:blue;}
	.nds	{height:95%;width:100%;background:orange;overflow:scroll;}
	.cnt	{height:90%;width:100%;background:#ffffcc;}
	.ad		{height:10%;width:100%;background:red;overflow-y:scroll;}
	.dxdlg	{display:none;position:fixed;top:0;left:0;height:100%;width:100%;background:url("shade.png");}
	
	.pb_h	{height:100%;max-height:240px;}
	.pb_w	{width:100%;max-width:480px;}
	.ctr	{height:100%;width:100%;z-index:10;}
	.mn		{height:30px;width:100%;background:green;z-index:20;}
table {}
	.tpic	{width:100%;height:100%;padding:0;border:0;}
tr {}
	.mp		{font-family:Calibri;font-size:12pt;}
	.tr_ep	{text-align:center;vertical-align:middle;}
td {}
	.tdm:hover {background:lime;cursor:pointer;}
	.td_ep	{text-align:center;vertical-align:middle;}
img {}
	.w		{width:100%;}
	.h		{height:100%;}
form {display:inline-block;}
textarea{}
	.cnt_edit {position:relative;width:100%;font-family:Arial;font-size:10pt;top:0px;bottom:0px;}
// --></style>
';

$hb_prec='
<div id="mainwin" class="mw">
	<div id="mainmenu" class="mm" style="width:100%;height:5%;min-height:26px;">
		<table border=0 cellpadding=5 cellspacing=0 width=100% height=100%>
			<tr align="center">
				<td><a href="http://pm.96.lt">HOME</a></td>
				<td>WordNotes</td>
				<td>ABOUT</td>
			</tr>
		</table>
	</div>
	<div id="main2" style="width:100%;height:95%;">
		<div id="submain1" style="width:20%;height:100%;float:left;">
			<div id="servmenu" class="sm" style="width:100%;height:5%;min-height:26px;">
				<table border=0 cellpadding=5 cellspacing=0 width=100% height=100%>
					<tr align="center">
						<td><a href="#" onClick="get(\'nodes\',\'m=31\',\'index.php?\');return false;">[A]</a></td>
						<td>[B]</td>
						<td>[C]</td>
					</tr>
				</table>
			</div>
			<div id="nodes" class="nds">'.$hb_nodes.'</div>
		</div>
		<div id="submain2" style="width:80%;height:100%;float:left;">
			<div id="nodemenu" class="nm" style="width:100%;height:5%;min-height:26px;">
				<table border=0 cellpadding=5 cellspacing=0 width=100% height=100%>
					<tr align="left">
						<td>[Z]</td>
						<td>[Y]</td>
						<td>[X]</td>
					</tr>
				</table>
			</div>
			<div id="submain3" style="width:100%;height:95%;">
				<div id="content" class="cnt" style="float:left;">'.$hb_main.'</div>
				<div id="adds" class="ad"></div>
			</div>
		</div>
	</div>
	<div id="dlgs" class="dxdlg" onClick="ds();">
		<table width=100% height=100%>
			<tr><td align="center" valign="middle">
				<div id="dlg1" onClick="dg();" style="width:50%;border:solid 1px black;padding:20px;background:white;z-index:10;">
					DIALOG 1
				</div>
			</td></tr>
		</table>
	</div>
</div>
';

$hb_end='</body></html>';

$hb_full=$hb_start.$hb_prec.$hb_css.$hb_js.$hb_end;

print $hb_full;

?>