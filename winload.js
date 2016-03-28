/*
Load Dialog Windows Script v.2.0 :: by Pablo Muretto © 2015
Uses:
Variables:
Functions:
*/

// Захватывает все данные из формы со страницы и преобразует в строку параметров (k - тип сериализации: 0 - WEB, 1 - JSON)
function asx_ser(frm,k){
	var m = 30;	// Максимальное количество перебираемых id
	var s = (k) ? ";" : "&";
	var e = (k) ? ":" : "=";
	var par="";
	// Перебор всех элементов страницы
	for (var i=1;i<=m;i++){
		var a = document.getElementById(frm+"_"+i);
		if(a){
			// Исключая неинформационные типы
			if(a.type=="checkbox" && !a.checked){continue;}
			//if(a.type=="checkbox" && a.checked!=true){continue;}
			if(a.type=="radio" && !a.checked){continue;}
			//if(a.type=="radio" && a.checked!=true){continue;}
		
			// Кодирование частей строки
			par+=(k?"":s)+a.name+e+encodeURIComponent(a.value)+(k?s:"");
		}else{
			continue;
		}
	}
	// Возврат результата (строки)
	return par;
}

ewin="index.php";	// Страница для перехода
ax_dlg="";	// Значение диалогового окна для загрузки данных (f: asx_load)
ax_ep="";	// Значение дополнительных параметров (f: asx_ep)
ax_cnt="";	// Содержимое выгружаемого окна
queue=[];	// Массив очереди
qi=0;				// Счетчик очереди
qt=0;				// Ключ активации очереди
ql=0;				// Длина массива очереди
//et=0;				// Timer

// Скрыть-показать диалог (div) по name (режим mode, по-умолчанию скрывает)
function tgl(name,mode){
	if(mode){
		mode="block";
	}else{
		mode="none";
	}
	document.getElementById(name).style.display=mode;
}


function get(win,data,url){
	asx(0,url+"&"+data,"",win,0);
}

// Функция POST - собирает данные с формы form_name и отправляет на страницу url, результат в окно win
function post(win,form_name,url){
	data=asx_ser(form_name);
	asx(1,url,data,win,0);
}

function dialog(win,data,url){
	asx(0,url+"&"+data,"",win,1);
}

// Для отправки веб-страницы, в конце надо использовать "?", чтобы скрипт понял, что это страница, а не параметры
// key - ключ обработчика (qm)
// gpars - параметры передачи page?GET
// ppars - параметры передачи POST
// meth - метод загрузки 1 = post, 0 = get (по-умолчанию)
// dlg - обозначения окна для загрузки поименно (0 = service; 1 = selbody; 2 = sideselector;)
// dtp - тип окна div загрузке (0 - просто обновить, 1 - показать диалог)
// Allocator (собирает очередь и запускает её)
function asx(meth,gpars,ppars,dlg,dtp){
	ql=queue.length;
	queue[ql]=[];
	var tmp=gpars.split("?");
	if(tmp.length>1){
		queue[ql]["e"]=tmp[0];
		gpars=tmp[1];
	}
	queue[ql]["g"]=gpars+ax_ep;
	ax_ep="";
	queue[ql]["p"]=ppars;
	queue[ql]["m"]=meth;
	queue[ql]["d"]=dlg;
	queue[ql]["t"]=dtp;
	asx_go();
}

// Executor (посылает асинхронный запрос к серверу)
function asx_go(){
	if(qt==0){
		// Ключ исполнения функции (внешний)
		qt=1;
		// Если функция не отработает за 10 секунд, то сбрасывает весь запрос
		et=setTimeout("cleanTO()",15000);
		// Внутренние переменные функции
		dlg=queue[qi]["d"];
		gpars=queue[qi]["g"];
		ppars=queue[qi]["p"];
		meth=(queue[qi]["m"])?"POST":"GET";
		// Если нет страницы вызова, принять по умолчанию
		uwin=(queue[qi]["e"])?queue[qi]["e"]:ewin;
		// Внешняя переменная для хранения текущего загружаемого окна!
		ax_dlg=dlg;
		// Смена содержимого окна при загрузке данных
		//if(!queue[qi]["t"]){ax_cnt=document.getElementById(ax_dlg).innerHTML;}
		//document.getElementById(ax_dlg).innerHTML='<h3>= LOADING =</h3>';
		// Если переданы дополнительные параметры GET, преобразовать их в строку нужного формата
		if(gpars==0 || gpars.length<3){gpars="";}else{gpars=asx_pars(gpars);}
		// Параметры POST уже сериализованы как надо
		// Видимость диалога (при соответствующем ключе)
		if(queue[qi]["t"]){tgl(dlg,1);}
		// Дополнительные настройки
		if(dlg=="dlg1"){tgl("dlgs",1);}
		// -----
		//alert("AJAX-SEND\nMODE: "+meth+"\nURL: "+uwin+"?"+gpars+"\nPOST: "+ppars);
		
		// Worker	(создание объекта)
		var nxt=false;
		if(window.XMLHttpRequest){
			reqx = new XMLHttpRequest();
			nxt=true;
		}else if (window.ActiveXObject){
			reqx = new ActiveXObject("Microsoft.XMLHTTP");
			if (reqx){
				nxt=true;
			}
		}
		// Worker	(AJAX-запрос)
		if(nxt){
			reqx.onreadystatechange = asx_load;
			reqx.open(meth,uwin+"?"+gpars, true);
			if(queue[qi]["m"]){
				reqx.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
				reqx.send(ppars);
			}else{
				reqx.send();
			}
			nxt=false;
		}
	}else{
		// Если функция в работе, возвращает false и не плодится
		return false;
	}
}

// Devolver (загружает ответ сервера в указанное окно)
function asx_load(){
	if(reqx.readyState==4 && reqx.status==200){
		document.getElementById(ax_dlg).innerHTML = reqx.responseText;
		// Очистка дополнительных параметров
		clean();
	}
}

// unSerialiser (преобразует c:d; в &c=d) (подразумевается, что в запросе уже есть "?")
function asx_pars(pars){
	r="";
	a1=pars.split(";");
	a1m=a1.length;
	if(a1m>1){
		for(var i=0;i<a1m;i++){
			//if(a1[i]!=0 && a1[i]!=""){
			if(a1[i]){
				a2=a1[i].split(":");
				//if(i){r+="&";}	// без ведущего амперсанда
				//r+=a2[0]+"="+a2[1];
				r+="&"+a2[0]+"="+a2[1];
			}
		}
	}else{r=a1;}
	return r;
}

// Назначает параметры для функции asx независимо от нее (запускается перед asx)
function asx_ep(p){ax_ep+=p;}

// Cleaner (после окончания очереди очищает все счетчики и переменные)
function clean(){
	clearTimeout(et);
	ax_ep="";
	qt=0;
	if(qi<ql){
		qi++;
		asx_go();
	}else{
		// Очистка очереди
		queue=[];
		ql=0;
		qi=0;
	}
}

// Cleaner over Timeout
function cleanTO(){
	reqx.abort();
	document.getElementById(ax_dlg).innerHTML=ax_cnt;
	//alert("Can't load content! Try again!");
	if(confirm("Can't load content! Try again now?")){
		qt=0;
		asx_go();
	}else{
		clean();
	}
}


// Предварительная инициация
//asx(19,0,0,'embody');

// --------------- END of SCRIPT --------------------------

// Если необходимо выполнить сценарий и получить даннные со страницы перед asx
// REMote function - управляется из DIV согласно режиму mode (задается железно)
function rem(mode){
	if(mode==1){
		// Берет режим из окна и отправляет данные
		ds=document.getElementsByName("adate");
		for(i=0;i<3;i++){
			if(ds[i].checked){
				if(i==2){i+="."+document.getElementById("tdate").value;}
				break;
			}
		}
		asx(41,"sdt:"+i+";",0,"mainbody");
		asx(19,0,0,"embody");
	}else if(mode==2){
		// Берет количество товара и прикрепляет к строке
		tgl("qselector",0);
		qp=document.getElementById("squant").value;
		gp=document.getElementById("gidra").value;
		vp=document.getElementById("vidra").value;
		asx(62,"g:"+gp+";q:"+qp+";v:"+vp+";",0,"gl_"+gp);
	}
}