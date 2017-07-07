document.all?document.attachEvent('onclick',checkClick):document.addEventListener('click',checkClick,false);
document.all?document.attachEvent('onmousedown',checkMouseDown):document.addEventListener('mousedown',checkMouseDown,false);
document.all?document.attachEvent('onmouseup',checkMouseUp):document.addEventListener('mouseup',checkMouseUp,false);
	
var is_chrome = navigator.userAgent.indexOf('Chrome') > -1;
var is_explorer = navigator.userAgent.indexOf('MSIE') > -1;
var is_firefox = navigator.userAgent.indexOf('Firefox') > -1;
var is_safari = navigator.userAgent.indexOf("Safari") > -1;
var is_opera = navigator.userAgent.toLowerCase().indexOf("op") > -1;
if ((is_chrome)&&(is_safari)) {is_safari=false;}
if ((is_chrome)&&(is_opera)) {is_chrome=false;}
var is_touch=('ontouchstart' in window) || window.DocumentTouch && document instanceof DocumentTouch;
if(is_touch) {
	document.all?document.attachEvent('ontouchstart',checkTouchStart):document.addEventListener('touchstart',checkTouchStart,false);
	document.all?document.attachEvent('ontouchend',checkTouchEnd):document.addEventListener('touchend',checkTouchEnd,false);
}

// Для IE8 - определение document.getElementsByClassName
if(document.getElementsByClassName==undefined) document.getElementsByClassName=function(ClassName) {
	var elements=document.getElementsByTagName('div');	// ищем только среди div !!!
	var cn=[];
	for(var i=0;i<elements.length;i++) if(elements[i].className==ClassName) cn[cn.length]=elements[i];
	return cn;
}

var tables={		// Таблица:[[0]Имя ключевого поля,[1]Название вторичного окна,[2]Ширина вт.окна,[3]Высота вт.окна]
	Accounts:['Счет','Корректировка счета',500,180],
	newAccounts: ['','Добавить счет',500,200],
	Entries: ['Номер','Изменение проводки',500,285],
	newEntries: ['','Новая проводка',500,280],
	FindEntry: ['','Найти проводки',500,340],
	Goods: ['Счет','Корректировка товара',500,204],
	newGoods: ['','Новый товар',500,240],
	Invoice: ['Номер','Изменение строки',500,340],
	newInvoice: ['','Новая строка',500,480],
	Stencil: ['Номер','Изменение шаблона',460,220],
	newStencil: ['','Новый шаблон',460,220],
	Diagnostics:['','Диагностика БД',400,400],
	ShowTables:['','Состав БД',530,420],
	Reset:['','Инициализация БД',280,120],
	titulPage:['','Параметры титульной страницы КУДиР',550,260],
	kudirRules:['','Правила принятия в доходы и расходы',540,350],
	kudirTunes:['','Настройки I раздела КУДиР',540,350],
	CounterAgents:['Счет','Корректировка контрагента',500,204],
	newCounterAgents:['','Новый контрагент',500,240],
	LoadBank:['Номер','Корректировка документа',600,350]
}
var tunes={			// Таблица:[Функция,Текст строки[,Функция,Текст строки[...]]]
	Accounts:['addString','Добавить счет','winOpened','Обновить'],
	Entries:['addString','Добавить проводку','findEntry','Найти','winOpened','Обновить'],
	Goods:['addString','Добавить товар','winOpened','Обновить'],
	Invoice:['addString','Добавить строку','loadInvoice','Загрузить','winOpened','Обновить'],
	Stencil:['addString','Добавить шаблон','winOpened','Обновить'],
	About:['diagnostics','Диагностика БД','showTables','Состав БД','reset','Инициализация БД'],
	Kudir:['titulPage','Параметры титульной страницы','kudirRules','Правила принятия в КУДиР','kudirTunes','Настройки I раздела КУДиР','kudir2','Раздел II КУДиР','kudir3','Раздел III КУДиР','kudir4','Раздел IV КУДиР','kudir5','Раздел V КУДиР'],
	Calculator:['precision(0)','&nbsp;Точность 0 знаков','precision(2)','&bull;Точность 2 знака','precision(3)','&nbsp;Точность 3 знака','precision(4)','&nbsp;Точность 4 знака','precision(-1)','&nbsp;Плавающая точность'],
	CounterAgents:['addString','Добавить контрагента','winOpened','Обновить']
}
var notEmptyFields={		//Таблица:[список полей, обязательных к заполнению]
	Accounts:['Счет','Наименование'],
	Entries:['Дата','Дебет','Кредит','Сумма','Содержание'],
	Goods:['Счет','Наименование','Цена_Розн'],
	Sale:['Дата','Дебет','Количество','Код'],
	Invoice:['Дата','Товар','Кредит','Сумма','Количество','Цена','Цена_Розн'],
	Stencil:['Содержание'],
	CounterAgents:['Счет','Наименование','ИНН'],
	LoadBank:['Дебет','Кредит','содержание']
}
var quantAccs=['41'];		//Список счетов с разрешенным количественным учетом
var CODETEMPL=/^\d+$/;		//Регулярка для (штрих)кода
var daysOfWeek=['Воскресенье','Понедельник','Вторник','Среда','Четверг','Пятница','Суббота'];
var shortDOW=["Вс","Пн","Вт","Ср","Чт","Пт","Сб"];
var Months=["Январь","Февраль","Март","Апрель","Май","Июнь","Июль","Август","Сентябрь","Октябрь","Ноябрь","Декабрь"];
var months=['января','февраля','марта','апреля','мая','июня','июля','августа','сентября','октября','ноября','декабря'];
if(notEmptyFields.Accounts.indexOf==undefined) notEmptyFields.Accounts.indexOf=arrIndexOf;
if(notEmptyFields.Entries.indexOf==undefined) notEmptyFields.Entries.indexOf=arrIndexOf;
if(notEmptyFields.Goods.indexOf==undefined) notEmptyFields.Goods.indexOf=arrIndexOf;
if(notEmptyFields.Sale.indexOf==undefined) notEmptyFields.Sale.indexOf=arrIndexOf;
if(notEmptyFields.Invoice.indexOf==undefined) notEmptyFields.Invoice.indexOf=arrIndexOf;
if(notEmptyFields.Stencil.indexOf==undefined) notEmptyFields.Stencil.indexOf=arrIndexOf;
if(quantAccs.indexOf==undefined) quantAccs.indexOf=arrIndexOf;
function arrIndexOf(a){for(var i=0;i<this.length;i++) if(this[i]===a) return i; return -1;}
var response={};
var vidgets={};
var cntCal=null;
var moveTimerId=0;
var currentElem = null;
var cw={
	shiftX:0,
	shiftY:0,
	obj:null,
	cnt:null,
	sts:null,
	tgt:null,
	name:'',
	refstr:null,
	input:null,
	searchRes:null,
	inputs:[],
	searches:[],
	inpIndex:-1,
	searchIndex:-1,
	columns:'',
	isPrimary:false,
	move:false,
	transform:0,	// 0 - move, 1 - resize
	timerId:0,
	price:0,		// 0 - вводится Цена, 1 - вводится Сумма
	addParam:'',
	callBack:null,
	isBusy:false,
	nRows:0,
	isOK: function() { clearTimeout(this.timerId); this.sts.style.backgroundPosition='-80px 0px'; this.isBusy=false; },
	isLoading: function() { 
		this.sts.style.backgroundPosition='0px -16px';
		this.isBusy=true;
		this.timerId=setTimeout(function loading() {
				var shift=parseInt(cw.sts.style.backgroundPosition)-16;
				if(shift==-96) shift=0;
				cw.sts.style.backgroundPosition=shift+'px -16px';
				cw.timerId=setTimeout(loading,100);
			},100)
	},
	close: function() {
		this.obj=null;
		this.cnt=null;
		this.sts=null;
		this.tgt=null;
		this.refstr=null;
		this.input=null;
		this.searchRes=null;
		this.callBack=null;
		this.columns='';
		this.name='';
		this.addParam='';
		this.inputs=[];
		this.searches=[];
		this.inpIndex=-1;
		this.searchIndex=-1;
		this.move=false;
	}
}
function getHtmlCollectionByClassName(className) {
	var v=document.getElementsByClassName(className);
	var w=[];
	for(var i=0;i<v.length;i++) { w[i]=v[i]; w[v[i].name]=v[i]; }
	return w;
}
function fillVidgets() {		// Оформление виджетов при загрузке
	var v=document.getElementsByClassName('vidget');
	var width=v[0].offsetWidth;
	var height=v[0].offsetHeight;
	var clientWidth=document.body.clientWidth;
	var margin=20;
	var top=margin;
	var left=margin;
	for(var i=0;i<v.length;i++) {
		var div=document.createElement('div');
		div.className='title';
		div.innerHTML=v[i].getAttribute('rname')?v[i].getAttribute('rname'):v[i].getAttribute('name');
		var cnt=document.createElement('div');
		cnt.className='content';
		cnt.innerHTML=v[i].innerHTML;
		v[i].style.visibility='hidden';
		v[i].cnt=cnt;
		v[i].innerHTML='';
		v[i].name=v[i].getAttribute('name');
		v[i].insertBefore(div,v[i].firstChild);
		v[i].appendChild(cnt);
		if(left+margin+width>clientWidth) { left=margin; top+=margin+height; }
		v[i].style.left=left+'px';
		v[i].style.top=top+'px';
		vidgets[v[i].name]={ left:left, top:top }
		left+=margin+width;
		if(v[i].name=='Calendar') cntCal=cnt;
		if(v[i].name=='Calculator') cnt.innerHTML="<div id='calcScreen0'>0.00</div>";
	}
	var xhr = new XMLHttpRequest();
	xhr.open('GET', location.pathname+'?Start', true);
	xhr.setRequestHeader('X-Type', 'XMLHttpRequest');
	xhr.send(); // (1)
	xhr.onreadystatechange = function() { // (3)
	  if (xhr.readyState != 4) return;
	  if(xhr.getResponseHeader('X-Type')=='auth') { document.write(xhr.responseText); return; }
	  if(xhr.responseText.substring(0,1)=='{') {
		  response=JSON.parse(xhr.responseText);
		  setTimeout(liveVidgets,0);
		  setTimeout(liveCalendar,0);
	  }
	  else for(i=0;i<v.length;i++) v[i].style.visibility='';
	}
}
function liveVidgets() {
	var v=getHtmlCollectionByClassName('vidget');
	for(var key in response) {
		switch(key) {
			case 'vidgetAcc' :	{ fill('Accounts',response[key]); break; }
			case 'vidgetEnt' :	{ fill('Entries',response[key]); break; }
			case 'vidgetGds' :	{ fill('Goods',response[key]); break; }
			case 'vidgetInv' :	{ fill('Invoice',response[key]); break; }
			case 'vidgetStn' :	{ fill('Stencil',response[key]); break; }
			case 'vidgetCtr' :	{ fill('CounterAgents',response[key]); break; }
			default: {
				if(key.substring(0,1)=='_') {
					var value=response[key].split(',');
					key=key.substring(1);
					if(v[key]!=undefined) {
						v[key].style.left=(vidgets[key].left=value[0])+'px';
						v[key].style.top=(vidgets[key].top=value[1])+'px';
					}
				}
			}
		}
	}
	for(var i=0;i<v.length;i++) if(v[i].style.visibility!='visible') v[i].style.visibility='visible';
	function fill(name,content) {
		if(v[name]!=undefined) {
			var respArr=content.toString().split('|');
			if(respArr.length==1 && content.toString().substring(0,1)=='<') v[name].cnt.innerHTML=content;
			else {
				var tab=v[name].cnt.firstChild;
				for(i=0;i<tab.rows.length;i++) tab.rows[i].cells[1].innerHTML=respArr[i];
			}
		}
	}
}
function saveVidgets() {
	var str='';
	for(var key in vidgets) str+=(str?'&':'')+key+'='+vidgets[key].left+','+vidgets[key].top;
	var xhr = new XMLHttpRequest();
	xhr.open('POST', location.pathname+'?SaveVidgets', true);
	xhr.setRequestHeader('X-Type', 'XMLHttpRequest');
	xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	xhr.send(str); // (1)
	xhr.onreadystatechange = function() { // (3)
	  if (xhr.readyState != 4) return;
	  if(xhr.getResponseHeader('X-Type')=='auth') { document.write(xhr.responseText); return; }
	}
}
function liveCalendar() {
	var date=new Date();
	var str=add0(date.getHours())+':'+add0(date.getMinutes())+', '+daysOfWeek[date.getDay()]+' '+date.getDate()+' '+months[date.getMonth()];
	cntCal.style.fontSize=str.length>29?'12px':'';
	cntCal.innerHTML=str;
	setTimeout(liveCalendar,60100-date.getSeconds()*1000);
}
function checkClick(e) {
	var evt=e?e:event;
	var target=evt.target || event.srcElement;
	var cse=target;
	var v=document.getElementsByClassName('tune-up');
	if(v.length>0 && v[0]!=cse) v[0].parentNode.removeChild(v[0]);
	if(cw.isBusy) return;
	var v=document.getElementsByClassName('search');
	if(v.length>0) {
		while(cse.parentNode) {
			if(cse.className && cse.className=='search') break;
			cse=cse.parentNode;
		}
		if(cse.className!='search')	 { v[0].parentNode.removeChild(v[0]); cw.searchRes=null; cw.searches=[];}
	}
	cse=target;
	var v=document.getElementsByClassName('calendar');
	if(v.length>0) {
		while(cse.parentNode) {
			if(cse.className && cse.className=='calendar') break;
			cse=cse.parentNode;
		}
		if(cse.className!='calendar') v[0].parentNode.removeChild(v[0]);
	}
	cse=target;
	while(cse.parentNode) {
		if(cse.className && cse.className.substring(0,6)=='vidget') break;
		cse=cse.parentNode;
	}
	cw.tgt=target;
	if(cse.className=='vidget') { 
		if(target.className!='title' && !cw.move)	vidgetOpen(cse); 
	}
	if(cse.className=='vidgetOpen') {
		switch(target.className) {
	 		case 'xclose':		setTimeout(closeVidget,0); break;
			case 'e-edit':		setTimeout(eEdit,0); break;
			case 'tune':		setTimeout(tune,0); break;
			case 'magnifier':	setTimeout(search,0); break;
			case 'searchStr':	cw.searchIndex=+target.getAttribute('tabindex')-1; setTimeout(searchPickup,0); break;
			case 'moreInfo':	cw.nRows=+target.getAttribute('rows'); setTimeout(moreEntries,0); break;
			case 'button':		setTimeout(button,0); break;
			case 'calendar-icon':	setTimeout(createCalendar,0); break;
			default: 			if(target.tagName=='TD') {
									if(target.parentNode.style.backgroundColor) setTimeout(pickUpStencil,0);
									else if(target.hasAttribute('edit')) setTimeout(editCell,0);
									else if(target.id.substring(0,2)=='cw' && !isNaN(target.innerHTML)) setTimeout(pickupDate,0);
								} break;
		}
	}
}
function editCell() {
	cw.tgt.innerHTML="<input type='text' value='"+(cw.tgt.innerHTML=='&nbsp;'?'':cw.tgt.innerHTML)+"'>";
	var inp=cw.tgt.firstChild;
	switch(cw.tgt.getAttribute('format')) {
		case 'date'		: inp.format=/\d{1,2}[-\.,\/]\d{1,2}[-\.,\/]\d{0,4}|\d{1,2}[-\.,\/]\d{0,2}|\d{1,2}/; inp.on_change=formatDate;
						    if (inp.addEventListener) {
						      if ('onwheel' in document) inp.addEventListener("wheel", onWheel);
						      else if ('onmousewheel' in document) inp.addEventListener("mousewheel", onWheel);
						      else inp.addEventListener("MozMousePixelScroll", onWheel);
						    } 
							else inp.attachEvent("onmousewheel", onWheel);
							break;
		case 'int'		: inp.format=/^\d*/; break;
		case 'decimal'	: inp.format=/^-?[\d\s`]*[\.,-]?\d{0,2}/; inp.on_change=formatDec; break;
		case 'percent'	: inp.format=/^100|\d{1,2}\.\d{0,2}|\d{1,2}/; inp.on_change=formatDec; break;
		default			: inp.format=/.+/; break;
	}
	if(inp.format!=undefined) {
		inp.oninput=checkInput;
		inp.onkeydown=function(e) {
			var evt=e?e:event;
			if(evt.keyCode==13) { inp.blur(); return false; }
		}
	}
	inp.onblur=function() {
		if(this.on_change!=undefined && this.value!=this.invalue) this.on_change();
		this.parentNode.innerHTML=this.value?this.value:'&nbsp;';
		setTimeout(calcFormula,0);
	}
	inp.focus();
}
function pickUpStencil() {
	var t=cw.tgt;
	while(t=t.parentNode) {	if(t.tagName=='TR') var tr=t; else if(t.tagName=='TABLE') break; }
	if(!t) return;
	var par={};
	if(cw.name=='Statement') {
		for(var i=0;i<tr.cells.length;i++) par[t.rows[1].cells[i].innerHTML]=tr.cells[i].innerHTML.substring(0,1)=='&'?'':tr.cells[i].innerHTML;
		if(par['СчетВ']==undefined) par['СчетВ']=cw.inputs['СчетВ'].value;
	}
	else for(var i=1;i<tr.cells.length-1;i++) par[t.rows[0].cells[i].innerHTML]=tr.cells[i].innerHTML.substring(0,1)=='&'?'':tr.cells[i].innerHTML;
	var v=getHtmlCollectionByClassName('vidgetOpen');
	if(cw.name=='Stencil') {
		var vE=null;
		if(v['LoadBank']!=undefined) {
			str='';
			for(i=1;i<tr.cells.length;i++) switch(t.rows[0].cells[i].innerHTML) { case 'Дебет':; case 'Кредит':; case 'Содержание': str+=(str?'&':'')+t.rows[0].cells[i].innerHTML+'='+tr.cells[i].innerHTML};
			cw.obj=v['Stencil']; cw.obj.style.transitionDuration='0.01s'; setTimeout(closeVidget,0);
			setTimeout(function() {zIndexUp(v['LoadBank']); saveFromEdit('LoadBank','Номер',v['LoadBank'].refstr.cells[1].innerHTML,str);},100);
		}
		else {
			if(v['newEntry']!=undefined) vE=v['newEntry'];
			if(v['Новая проводка']!=undefined && (!vE || (+vE.style.zIndex)<(+v['Новая проводка'].parentNode.style.zIndex))) vE=v['Новая проводка'];
			if(vE) { zIndexUp(vE); fillEntryWithPar(); }
			else { 
				var v=document.getElementsByClassName('vidget');
				for(i=0;i<v.length;i++) if(v[i].name=='newEntry') { vidgetOpen(v[i]); break; }
				cw.callBack=fillEntryWithPar; 
			}
		}
	}
	else if(cw.name=='Statement') {
		if(v['Entries']!=undefined) {cw.obj=v['Entries']; cw.obj.style.transitionDuration='0.01s'; setTimeout(closeVidget,0); setTimeout(openEntries,100);}
		else openEntries();
	}
	else if(cw.name=='LoadBank') {
		tr.style.backgroundColor='Moccasin'; 
		cw.obj.refstr=tr;
		if(v['Stencil']!=undefined) {cw.obj=v['Stencil']; cw.obj.style.transitionDuration='0.01s'; setTimeout(closeVidget,0); setTimeout(openStensil,100);}
		else openStensil();
	}
	function openEntries() {
		var v=getHtmlCollectionByClassName('vidget');
		cw.addParam='Date='+par['Дата'];
		vidgetOpen(v['Entries']);
		cw.callBack=findEntryByPar; 
	}
	function openStensil() {
		var columns={};
		for(i=1;i<tr.cells.length;i++) columns[t.rows[0].cells[i].innerHTML]=tr.cells[i].innerHTML;
		var v=getHtmlCollectionByClassName('vidget');
		if(columns['Дебет'].substring(0,2)=='51') cw.addParam='Debet='+columns['Дебет'];
		else if(columns['Кредит'].substring(0,2)=='51') cw.addParam='Credit='+columns['Кредит'];
		vidgetOpen(v['Stencil']);
	}
	function fillEntryWithPar() { 
		for(var key in par) if(cw.inputs[key] && par[key]) cw.inputs[key].value=par[key];
		var match; var field; var value='ОШИБКА'; var operand;
		while(match=cw.inputs['Содержание'].value.match(/{[а-яА-Я\d\-\*\/\.\,]+}/)) {
			if(field=match[0].match(/[а-яА-Я]+/)) {
				if(field[0]=='Дата') {
					value=cw.inputs['Дата'].value;
					if(operand=match[0].match(/\-\d+/)) operand=operand[0].substring(1);
					if(operand) {
						var data=new Date(value.substring(6),value.substring(3,5)-1,value.substring(0,2)-operand);
						value=add0(data.getDate())+'.'+add0(data.getMonth()+1)+'.'+data.getFullYear();
					}
					else {
						if(operand=match[0].match(/\,\d+/)) operand=operand[0].substring(1);
						if(operand) {
							var data=new Date(value.substring(6),value.substring(3,5)-1,operand);
							if(data.getMonth()>(value.substring(3,5)-1)) data=new Date(value.substring(6),value.substring(3,5),0)
							value=add0(data.getDate())+'.'+add0(data.getMonth()+1)+'.'+data.getFullYear();
						}
					}
				}
				else if(field[0]=='Сумма') {
					value=+cw.inputs['Сумма'].value.replace(/\s/g,'');
					if(operand=match[0].match(/\*[\d\.\,]+/)) operand=+(operand[0].substring(1).replace(',','.'));
					if(operand) value=(value*operand).toFixed(2);
					else {
						if(operand=match[0].match(/\/[\d\.\,]+/)) operand=+(operand[0].substring(1).replace(',','.'));
						if(operand) value=(value/operand).toFixed(2);
					}
				}
				else value='';
				cw.inputs['Содержание'].value=cw.inputs['Содержание'].value.replace(match[0],value);
			}
		}
		var quantum=cw.inputs['Дебет']!=undefined && quantAccs.indexOf(cw.inputs['Дебет'].value.substring(0,2))>=0 || cw.inputs['Кредит']!=undefined && quantAccs.indexOf(cw.inputs['Кредит'].value.substring(0,2))>=0  || cw.inputs['Товар']!=undefined && quantAccs.indexOf(cw.inputs['Товар'].value.substring(0,2))>=0;
		var inp;
		if((inp=cw.inputs['Количество'])!=undefined) { if(!quantum) { inp.disabled=true; inp.value=''; } else inp.disabled=false; }
		cw.callBack=null;
	}
	function findEntryByPar() {
		var t=cw.cnt.getElementsByTagName('table');
		if(!t) return;
		t=t[0];
		for(var i=1;i<t.rows[0].cells.length-2;i++) switch(t.rows[0].cells[i].innerHTML) {
			case 'Дебет': var ndb=i; break;
			case 'Кредит': var ncr=i; break;
			case 'Сумма': var nsu=i; break;
			case 'Содержание': var nct=i; break;
		}
		for(i=1;i<t.rows.length;i++) {
			var cells=t.rows[i].cells;
			if(par['Дебет'] && par['Дебет']==cells[nsu].innerHTML && par['СчетВ']==cells[ndb].innerHTML && par['Счет']==cells[ncr].innerHTML && par['Содержание']==cells[nct].innerHTML) break;
			else if(par['Кредит'] && par['Кредит']==cells[nsu].innerHTML && par['СчетВ']==cells[ncr].innerHTML && par['Счет']==cells[ndb].innerHTML && par['Содержание']==cells[nct].innerHTML) break;
		}
		if(i<t.rows.length) t.rows[i].style.backgroundColor='Moccasin';
		t.rows[i].scrollIntoView();
		cw.callBack=null;
	}
}
function zIndexUp(tgt) {
	while(tgt.className!='vidgetOpen' && tgt.className!='vidget' && tgt.parentNode) tgt=tgt.parentNode;
	if(tgt==document) return;
	if(cw.obj!=tgt) {
		cw.obj=tgt;
		cw.name=tgt.getAttribute('name');
		cw.columns=tgt.columns==undefined?'':tgt.columns;
		cw.isPrimary=(tgt.parentNode==document.body);
		for(var i=0;i<tgt.childNodes.length;i++) {
			if(tgt.childNodes[i].className=='title' && tgt.childNodes[i].firstChild.className=='status') cw.sts=tgt.childNodes[i].firstChild;
			if(tgt.childNodes[i].className=='content') { cw.cnt=tgt.childNodes[i]; break; }
		}
		cw.inputs=[];
		var v=cw.cnt.getElementsByTagName('input');
		for(i=0;i<v.length;i++)	{ if(v[i].type=='text' || v[i].type=='checkbox') cw.inputs[cw.inputs.length]=v[i]; cw.inputs[v[i].name]=v[i]; }
		v=cw.cnt.getElementsByTagName('button');
		if(v.length>0) cw.inputs[cw.inputs.length]=v[0];
	}
	while(tgt.parentNode!=document.body) tgt=tgt.parentNode;
	var isopen=tgt.className=='vidgetOpen';
	var cz=0;
	var v=document.body.childNodes;
	for(var i=0;i<v.length;i++) if(v[i].tagName=='DIV' && (isopen || v[i].className=='vidget') && +v[i].style.zIndex>cz && v[i]!=tgt)cz=+v[i].style.zIndex;
	if(cz>=+tgt.style.zIndex) tgt.style.zIndex=++cz;
}
function checkMouseDown(e) {
	if(cw.isBusy) return;
	var evt=e?e:event;
	var cse=evt.target?evt.target:evt.srcElement;
	zIndexUp(cse);
	if(cse.className=='title') {
		var parent=cse.parentNode;
		cw.shiftX=evt.clientX-parent.offsetLeft;
		cw.shiftY=evt.clientY-parent.offsetTop;
		cw.transform=0;
		parent.style.transitionProperty='width,height';
		document.all?document.attachEvent('onmousemove',checkMouseMove):document.addEventListener('mousemove',checkMouseMove,false);
		document.onselectstart=function() {return false};
	}
	else if(cse.className=='corner') {
		var parent=cse.parentNode;
		cw.shiftX=evt.clientX-parent.offsetWidth;
		cw.shiftY=evt.clientY-parent.offsetHeight;
		cw.transform=1;
		parent.style.transitionProperty='left,top';
		document.all?document.attachEvent('onmousemove',checkMouseMove):document.addEventListener('mousemove',checkMouseMove,false);
		document.onselectstart=function() {return false};
	}
}
function checkTouchStart(e) {
	if(cw.isBusy) return;
	var evt=e?e:event;
	var cse=evt.target?evt.target:evt.srcElement;
	zIndexUp(cse);
	if(cse.className=='title') {
		var parent=cse.parentNode;
		cw.shiftX=evt.targetTouches[0].clientX-parent.offsetLeft;
		cw.shiftY=evt.targetTouches[0].clientY-parent.offsetTop;
		cw.transform=0;
		parent.style.transitionProperty='width,height';
		document.all?document.attachEvent('ontouchmove',checkTouchMove):document.addEventListener('touchmove',checkTouchMove,false);
	}
	else if(cse.className=='corner') {
		var parent=cse.parentNode;
		cw.shiftX=evt.targetTouches[0].clientX-parent.offsetWidth;
		cw.shiftY=evt.targetTouches[0].clientY-parent.offsetHeight;
		cw.transform=1;
		parent.style.transitionProperty='left,top';
		document.all?document.attachEvent('ontouchmove',checkTouchMove):document.addEventListener('touchmove',checkTouchMove,false);
	}
}
function checkMouseUp(e) {
	document.all?document.detachEvent('onmousemove',checkMouseMove):document.removeEventListener('mousemove',checkMouseMove,false);
	if(cw.obj) {
		cw.obj.style.transitionProperty='';
		document.onselectstart=null;
		cw.move=false;
		if(cw.obj.className=='vidget' && (vidgets[cw.obj.name].top!=cw.obj.offsetTop || vidgets[cw.obj.name].left!=cw.obj.offsetLeft)) {
			vidgets[cw.obj.name].left=cw.obj.offsetLeft;
			vidgets[cw.obj.name].top=cw.obj.offsetTop;
			moveTimerId=setTimeout(saveVidgets,2000);
		}
	}
}
function checkTouchEnd(e) {
	document.all?document.detachEvent('ontouchmove',checkTouchMove):document.removeEventListener('touchmove',checkTouchMove,false);
	if(cw.obj) { 
		cw.obj.style.transitionProperty='';
		cw.move=false;
		if(cw.obj.className=='vidget' && (vidgets[cw.obj.name].top!=cw.obj.offsetTop || vidgets[cw.obj.name].left!=cw.obj.offsetLeft)) {
			vidgets[cw.obj.name].left=cw.obj.offsetLeft;
			vidgets[cw.obj.name].top=cw.obj.offsetTop;
			moveTimerId=setTimeout(saveVidgets,2000);
		}
	}
}
function checkMouseMove(e) {
	var evt=e?e:event;
	var c;
	if(cw.transform==0) {
		if(cw.isPrimary) {
			if(cw.obj.className=='vidget') clearTimeout(moveTimerId);
			var top=evt.clientY-cw.shiftY;
			if(top<0)top=0;
			cw.obj.style.left=evt.clientX-cw.shiftX+'px';
			cw.obj.style.top=top+'px';
		}
		else {
			var left=evt.clientX-cw.shiftX;
			if(left<0) left=0;
			else {
				var right=cw.obj.parentNode.offsetWidth-cw.obj.offsetWidth;
				if(left>right) left=right;
			}
			var top=evt.clientY-cw.shiftY;
			if(top<20)top=20;
			else {
				var bottom=cw.obj.parentNode.offsetHeight-cw.obj.offsetHeight;
				if(top>bottom) top=bottom;
			}
			cw.obj.style.left=left+'px';
			cw.obj.style.top=top+'px';
		}
	}
	else if(cw.transform==1) {
		var width=evt.clientX-cw.shiftX;
		var right=cw.obj.parentNode.offsetWidth-cw.obj.offsetLeft;
		if(width>right) width=right;
		var height=evt.clientY-cw.shiftY;
		if(!cw.isPrimary) {
			var bottom=cw.obj.parentNode.offsetHeight-cw.obj.offsetTop;
			if(height>bottom) height=bottom;
		}
		cw.obj.style.width=width+'px';
		cw.obj.style.height=height+'px';
		cw.cnt.style.height=height-30+'px'; // 20-высота tittle+10 на corner
	}
	cw.move=true;
}
function checkTouchMove(e) {
	var evt=e?e:event;
    var touch = evt.targetTouches[0];
	if(cw.transform==0) {
		if(cw.isPrimary) {
			if(cw.obj.className=='vidget') clearTimeout(moveTimerId);
			var top=touch.clientY-cw.shiftY;
			if(top<0)top=0;
			cw.obj.style.left = touch.clientX-cw.shiftX + 'px';
	    	cw.obj.style.top = top + 'px';
		}
		else {
			var left=touch.clientX-cw.shiftX;
			if(left<0) left=0;
			else {
				var right=cw.obj.parentNode.offsetWidth-cw.obj.offsetWidth;
				if(left>right) left=right;
			}
			var top=touch.clientY-cw.shiftY;
			if(top<20)top=20;
			else {
				var bottom=cw.obj.parentNode.offsetHeight-cw.obj.offsetHeight;
				if(top>bottom) top=bottom;
			}
			cw.obj.style.left=left+'px';
			cw.obj.style.top=top+'px';
		}
	}
	else if(cw.transform==1) {
		var width=touch.clientX-cw.shiftX;
		var right=cw.obj.parentNode.offsetWidth-cw.obj.offsetLeft;
		if(width>right) width=right;
		var height=touch.clientY-cw.shiftY;
		if(!cw.isPrimary) {
			var bottom=cw.obj.parentNode.offsetHeight-cw.obj.offsetTop;
			if(height>bottom) height=bottom;
		}
		cw.obj.style.width=width+'px';
		cw.obj.style.height=height+'px';
		cw.cnt.style.height=height-30+'px'; // 20-высота tittle+10 на corner
	}
	cw.move=true;
	evt.preventDefault();
}
function winOpened() {		// Заполнение контента первичного окна виджета
	if(cw.obj.name=='About') httpRequest('About','',prepLinks);
	else if(cw.obj.name=='Calendar') calendar();
	else if(cw.obj.name=='Calculator') calculator();
	else { httpRequest(cw.obj.getAttribute('name')+(cw.addParam?'&'+cw.addParam:''),'',function(){prepInputs();liveVidgets();}); cw.addParam=''; }
}
function vidgetOpen(cse) {		// Открываем первичное окно виджета
	var v=getHtmlCollectionByClassName('vidgetOpen');
	if(v[cse.name]!=undefined) return;
	var active=cse.cloneNode(false);
	var clientWidth=document.body.clientWidth;
	var clientHeight=window.innerHeight==undefined?screen.availHeight-150:window.innerHeight;
	var margin=10;
	var width=cse.getAttribute('width')?cse.getAttribute('width'):600;
	var height=cse.getAttribute('height')?cse.getAttribute('height'):400;
	var left=cse.offsetLeft>clientWidth-width-margin?clientWidth-width-margin:cse.offsetLeft;
	var top=cse.offsetTop>clientHeight-height-margin?clientHeight-height-margin:cse.offsetTop;
	if(top<0) top=0;
	active.className='vidgetOpen';
	active.name=active.getAttribute('name');
	document.body.appendChild(active);
//	cse.style.visibility='hidden';
	var rname=cse.getAttribute('rname')?cse.getAttribute('rname'):cse.getAttribute('name');
	var tune=tunes[cse.getAttribute('name')]?"<div class='tune'></div>":"";
	setTimeout(function(){active.style.width=width+'px';active.style.height=height+'px';active.style.left=left+'px';active.style.top=top+'px'},0);
	active.open=true;
	active.innerHTML="<div class='title'><div class='status'></div>"+rname+"<div class='xclose'></div>"+tune+"</div><div class='content'></div><div class='corner'></div";
	zIndexUp(active);
	cw.cnt.style.height=height-30+'px';
	setTimeout(winOpened,500);
	if(!document.all)active.addEventListener('transitionend', function(e) {
		var evt=e?e:event;
		if(!active.open && evt.propertyName=='width') {
//			cse.style.visibility='';
			document.body.removeChild(active);
			cw.close();
		}
	});
}
function newVidgetOpen(parent,name) {	// Открываем вторичное окно (Элемент, куда вставляем), (Имя таблицы, к которой отрывается вторичное окно)
// Таблица:[[0]Имя ключевого поля,[1]Название вторичного окна,[2]Ширина вт.окна,[3]Высота вт.окна]
	var div=document.createElement('div');
	div.className='vidgetOpen';
	div.setAttribute('name',tables[name][1]);
	div.name=tables[name][1];
	div.style.width=tables[name][2]+'px';
	div.style.height=tables[name][3]+'px';
	div.style.top='50px';
	div.style.left='50px';
	div.refstr=cw.refstr;
	div.columns=cw.obj.columns;
	div.innerHTML="<div class='title'><div class='status'></div>"+div.getAttribute('name')+"<div class='xclose'></div></div><div class='content'></div><div class='corner'></div";
	parent.appendChild(div);
	zIndexUp(div);
	cw.cnt.style.height=cw.obj.offsetHeight-30+'px';
}
function closeVidget() {		// Закрыть первичное или вторичное окно
	var cse=cw.obj;
	var name=cse.getAttribute('name');
	if(name=='Calculator') document.getElementById('calcScreen0').innerHTML=document.getElementById('calcScreen').innerHTML;
	var v=getHtmlCollectionByClassName('vidget');
	if(v[name]!=undefined) {
		var cse_=v[name];
		cse.style.width=cse_.offsetWidth+'px';
		cse.style.height=cse_.offsetHeight+'px';
		cse.style.left=cse_.offsetLeft+'px';
		cse.style.top=cse_.offsetTop+'px';
		cse.open=false;
		if(document.all || is_safari) {
//			cse_.style.visibility='';
			document.body.removeChild(cse);
			cw.close();
		}
	}
	else { cse.parentNode.removeChild(cse); cw.close(); }
}
function eEdit() {		// Открыть окно редактирования строки
	for(var i=0;i<cw.obj.childNodes.length;i++) if(cw.obj.childNodes[i].className=='vidgetOpen') return;
	var tab=cw.tgt;
	while(tab.tagName!='TABLE' && tab!=document) tab=tab.parentNode;
	if(tab==document) return;
	for(i=1;i<tab.rows[0].cells.length;i++) {
		if(tab.rows[0].cells[i].innerHTML==tables[cw.name][0]) break;
	} 
	if(i==tab.rows[0].cells.length) return;
	var tr=cw.tgt;
	while(tr.tagName!='TR' && tr!=tab) tr=tr.parentNode;
	cw.refstr=tr;
	var name=cw.name;						// имя таблицы
	var field=tables[name][0];				// имя ключевого поля
	var value=tr.cells[i].innerHTML;		// значение ключевого поля
	if(value=='&nbsp;') value='';
	newVidgetOpen(cw.obj,name);
	httpRequest('Edit&name='+encodeURIComponent(name)+'&field='+encodeURIComponent(field)+'&value='+encodeURIComponent(value),'',prepInputs);
}
function prepInputs() {		//	подготовить все инпуты окна
	var v=cw.cnt.getElementsByTagName('input');
	cw.inputs=[];
	var quantum=false;
	if(cw.name=='Новая строка' || cw.name=='Изменение строки') quantum=true;
	if(cw.name=='Sale') quantum=true;
	for(var i=0;i<v.length;i++) {
		if(v[i].type=='text' || v[i].type=='checkbox') {
			var match=v[i].getAttribute('format').match(/\d+/);
			if(match ) match=+match[0]; else match=0;
			if(isNaN(match))match=0; else if(match>40) match=40;
			if(v[i].type=='text') v[i].style.width=match<10?'80px':match*8+'px';
			var format=v[i].getAttribute('format').match(/^\w+/);
			if(format && format.length>0) format=format[0]; else format='';
			switch(format) {
				case 'timestamp':
				case 'date'		: v[i].format=/\d{1,2}[-\.,\/]\d{1,2}[-\.,\/]\d{0,4}|\d{1,2}[-\.,\/]\d{0,2}|\d{1,2}/; v[i].on_change=formatDate;
								    if (v[i].addEventListener) {
								      if ('onwheel' in document) v[i].addEventListener("wheel", onWheel);
								      else if ('onmousewheel' in document) v[i].addEventListener("mousewheel", onWheel);
								      else v[i].addEventListener("MozMousePixelScroll", onWheel);
								    } 
									else v[i].attachEvent("onmousewheel", onWheel);
									if(navigator.userAgent.indexOf('MSIE 8')<0) {
										var div=document.createElement('div'); div.className='calendar-icon'; v[i].parentNode.insertBefore(div,v[i].nextSibling);
									}
									break;
				case 'int'		: v[i].format=/^\d*/; if(v[i].name=='Количество') {v[i].disabled=!quantum; v[i].on_change=checkQuant;} break;
				case 'decimal'	: v[i].format=/^-?[\d\s`]*[\.,-]?\d{0,2}/; v[i].on_change=formatDec; break;
				default: {
					switch(v[i].getAttribute('name')) {
						case 'Товар':	v[i].format=/41[\.,]\d+[\.,]\d*|41[\.,]\d*|41|4/; if(v[i].value=='')v[i].value='41.'; v[i].on_change=formatAcc; insertMagnifier(v[i]); break;
						case 'СчетБ':	v[i].format=/51[\.,]\d+[\.,]\d*|51[\.,]\d*|51|5/; if(v[i].value=='')v[i].value='51.'; v[i].on_change=formatAcc; insertMagnifier(v[i]); break;
						case 'Счет':	if(cw.name=='Новый товар') { v[i].format=/41[\.,]\d+[\.,]\d*|41[\.,]\d*|41|4/; v[i].value='41.'; v[i].on_change=formatAcc; break; }
										if(cw.name=='Новый контрагент') { v[i].format=/6[02][\.,]\d+[\.,]\d*|6[02][\.,]\d*|6[02]|6/; v[i].value='6'; v[i].on_change=formatAcc; break; }
						case 'Кредит':	cw.obj.quantity=v[i].getAttribute('quant') ? +v[i].getAttribute('quant'): -1; // Нет break;!!!
						case 'СчетВ':
						case 'Дебет':	if(quantAccs.indexOf(v[i].value.substring(0,2))>=0) quantum=true;  if(cw.name!='Добавить счет') insertMagnifier(v[i]);
										v[i].format=/\d\d[\.,]\d+[\.,]\d*|\d\d[\.,]\d*|\d{1,2}/; v[i].on_change=formatAcc; break;
						case 'АП' :		v[i].format=/[апАП]/; v[i].on_change=formatAP; break;
						case 'ИНН':		v[i].format=/^\d{1,12}/; break;
						case 'Код':		v[i].format=/[\d,\.;\s\/-]+/; v[i].on_change=findCode; break;
						case 'Шаблон':	v[i].format=/41[\.,]\d+[\.,]\d*\**|41[\.,]\d*\**|41|4/; v[i].value='41.****'; v[i].on_change=formatTempl; break;
						case 'Поиск':	v[i].oninput=searchInvoice;	v[i].onkeydown=checkKey;
										v[i].onpropertychange = function() {if (event.propertyName == "value") searchInvoice();}; break;
						case 'Year':	v[i].format=/^20\d{1,2}|^\d{1,2}/; v[i].on_change=formatYear; break;
						default:		v[i].format=/.*/;
					}
				}
			}
			if(v[i].format!=undefined) {
				v[i].oninput=checkInput;
				v[i].onkeydown=checkKey;
			}
			v[i].onkeypress=checkKeyPress;
			v[i].onfocus=focusInput;
			v[i].onblur=blur;
			v[i].index=i;
			cw.inputs[cw.inputs.length]=v[i];
		}
		cw.inputs[v[i].name]=v[i];
	}
	v=cw.cnt.getElementsByTagName('button');
	if(v.length>0) {
		v[0].onkeydown=checkKey;
		v[0].onkeypress=checkKeyPress;
		cw.inputs[cw.inputs.length]=v[0];
	}
	if(cw.inputs.length>0 && !cw.inputs[0].disabled) { cw.inputs[0].focus(); cw.inpIndex=0; }
	if(cw.isPrimary) {
		var tab=cw.cnt.getElementsByTagName('table');
		if(tab.length==0) {cw.columns=''; cw.obj.columns=''; return;}
		tab=tab[0];
		var columns=[];
		if(tab.rows[0].cells[1]!=undefined && tab.rows[0].cells[1].innerHTML.indexOf('<')<0) {
			for(i=1;i<tab.rows[0].cells.length;i++) columns[i-1]=tab.rows[0].cells[i].innerHTML;
			cw.columns='`'+columns.join('`,`')+'`';
			cw.obj.columns=cw.columns;
		} 
		if(cw.name=='Stencil' || cw.name=='Statement' || cw.name=='Kudir' || cw.name=='LoadBank') {
			var table=cw.cnt.getElementsByTagName('table');
			var ntab=0;
			if(cw.name=='Statement') ntab=1;
			else if (cw.name=='Kudir') ntab=table.length-1;
			if(table && table.length>ntab) {
				table[ntab].onmouseover=cw.name=='Kudir'?highlightCell:highlightString;
				table[ntab].onmouseout=dislightString;
			}
			if(cw.name=='LoadBank') {
				var canDoEntry=true;
				for(i=1;i<tab.rows.length;i++)
					for(var j=1;j<tab.rows[i].cells.length;j++) 
						if(tab.rows[i].cells[j].innerHTML=='&nbsp;' || tab.rows[i].cells[j].innerHTML=='') {
							tab.rows[i].cells[j].style.backgroundColor='Moccasin';
							canDoEntry=false;
						}
						else tab.rows[i].cells[j].style.backgroundColor='';
				var b=cw.cnt.getElementsByTagName('b');
				for(i=0;i<b.length;i++) if(b[i].style.color!='') canDoEntry=false;
				cw.cnt.getElementsByTagName('button')[0].disabled=!canDoEntry;
			}
		}
		else if(cw.name=='Accounts') {
			for(i=1;i<tab.rows[0].cells.length;i++) switch(tab.rows[0].cells[i].innerHTML) {
				case 'Дебет': var ndb=i; break;
				case 'Кредит': var ncr=i; break;
				case 'АП': var nap=i; break;
			}
			for(i=1;i<tab.rows.length;i++) {
				if(tab.rows[i].cells[nap].innerHTML=='А') tab.rows[i].cells[ncr].style.color='red';
				else if(tab.rows[i].cells[nap].innerHTML=='П') tab.rows[i].cells[ndb].style.color='red';
			}
		}
		else if(cw.name=='Entries') {
			if(tab.caption) tab.caption.setAttribute('rows',(tab.rows.length-1).toString());
		}
	}
	if(cw.callBack) cw.callBack();
}
function onWheel(e) {
	var evt=e?e:event;
	var target=evt.target?evt.target:evt.srcElement;
	var delta = e.deltaY || e.detail || e.wheelDelta;
	var dateObj=new Date(target.value.substring(6),target.value.substring(3,5)-1,+target.value.substring(0,2)+(delta<0?1:-1));
	target.value=add0(dateObj.getDate()+'')+'.'+add0((dateObj.getMonth()+1)+'')+'.'+dateObj.getFullYear();
	evt.preventDefault ? evt.preventDefault() : (evt.returnValue = false);
}
function highlightString(e) {
	if (currentElem) return;
	var evt=e?e:event;
	var target=evt.target?evt.target:evt.srcElement;
	while (target != this) {
		if(target.tagName=='TH' || target.className=='npr' || target.className=='noHL') return;
		if (target.tagName == 'TR') break;
		target = target.parentNode;
	}
	if (target == this) return;
	currentElem = target;
	target.style.backgroundColor = 'powderblue';
	if(target.style.cursor=='') target.style.cursor='default';
}
function dislightString(e) {
	if (!currentElem) return;
	var evt=e?e:event;
	var target=evt.target?evt.target:evt.srcElement;
	if(evt.relatedTarget===undefined) {
		if (evt.type == 'mouseover') evt.relatedTarget = evt.fromElement;
		if (evt.type == 'mouseout') evt.relatedTarget = evt.toElement;
	}
	var relatedTarget = evt.relatedTarget;
	if (relatedTarget) {
		while (relatedTarget) {
			if (relatedTarget == currentElem) return;
			relatedTarget = relatedTarget.parentNode;
		}
	}
	if(currentElem.style.backgroundColor=='powderblue' || currentElem.style.backgroundColor=='rgb(176, 224, 230)') currentElem.style.backgroundColor = '';
	currentElem = null;
}
function highlightCell(e) {
	if (currentElem) return;
	var evt=e?e:event;
	var target=evt.target?evt.target:evt.srcElement;
	while (target != this) {
		if(target.tagName=='TH' || target.className=='npr' || target.className=='noHL') return;
		if (target.tagName == 'TD') break;
		target = target.parentNode;
	}
	if (target == this || !target.hasAttribute('edit')) return;
	currentElem = target;
	target.style.background = 'powderblue';
}
function insertMagnifier(inp) {
	if(inp.disabled) return;
	var div=document.createElement('div');
	div.className='magnifier';
  	inp.parentNode.appendChild(div);
}
function focusInput() {
	cw.inpIndex=this.index;
	this.invalue=this.value;
	cw.input=this;
}
function blur() {
	if(this.on_change!=undefined && this.value!=this.invalue) this.on_change();
}
function formatDec() {
	this.value=this.value.replace(/(^-?\d+)[,-]/g,'$1.');
	var val=this.value.replace(/[\s`]+/g,'');
	val=val!=''?(+val).toFixed(2):'';
	if(cw.name=='Новая строка') {
		if(this.name=='Цена') {
			cw.inputs['Сумма'].value=(val * cw.inputs['Количество'].value).toFixed(2).replace(/\B(?=(?:\d{3})+(?!\d))/g, ' ');
			cw.price=0;
		}
		if(this.name=='Сумма') {
			cw.inputs['Цена'].value=(val / cw.inputs['Количество'].value).toFixed(2).replace(/\B(?=(?:\d{3})+(?!\d))/g, ' ');
			cw.price=1;
		}
	}
	this.value = val.replace(/\B(?=(?:\d{3})+(?!\d))/g, ' ');
}
function formatAP() {
	this.value=this.value.toUpperCase();
}
function formatTempl() {
	this.value=this.value.replace(/,/g,'.');
}
function checkQuant() {
	if(cw.name=='Новая строка' && this.name=='Количество') {
		if(cw.price==0) cw.inputs['Сумма'].value=(cw.inputs['Цена'].value.replace(/\s+/g,'') * this.value).toFixed(2).replace(/\B(?=(?:\d{3})+(?!\d))/g, ' ');
		else cw.inputs['Цена'].value=(cw.inputs['Сумма'].value.replace(/\s+/g,'') / this.value).toFixed(2).replace(/\B(?=(?:\d{3})+(?!\d))/g, ' ');
	}
	if(cw.obj.quantity==undefined) cw.obj.quantity=0;
	if(cw.obj.quantity<0) return;
	if(this.value>cw.obj.quantity) {
		this.value=cw.obj.quantity;
		this.style.transitionDuration='0.2s';
		this.style.backgroundColor='OrangeRed';
		var input=this;
		input.focus();
		setTimeout(function(){input.style.transitionDuration='1s'; input.style.backgroundColor=''},400);
	}
	if(this.value=='0') this.value='';
}
function formatAcc() {
	if(this.value=='') {
	  if(cw.name=='Sale' && this.name=='Товар') {
	  	var v=cw.cnt.getElementsByTagName('span');
	  	for(var i=0;i<v.length;i++) if(v[i].className!='alert') v[i].innerHTML='';
	  }
	  if(this.nextSibling && this.nextSibling.className=='alert') {
	  	this.parentNode.removeChild(this.nextSibling);
	  }
	  cw.obj.quantity=0;
	  return;
	}
	this.value=this.value.replace(/,/g,'.');
	if(cw.name=='Изменение строки' && this.name=='Товар') return;
	var newAcc=(cw.name=='Добавить счет' || cw.name=='Новый товар' || cw.name=='Новый контрагент');
	if(!newAcc && (this.name=='Дебет' || this.name=='Кредит') && cw.name!='Sale' && cw.name!='Новая строка') {
		var quantum=cw.inputs['Дебет']!=undefined && quantAccs.indexOf(cw.inputs['Дебет'].value.substring(0,2))>=0 || cw.inputs['Кредит']!=undefined && quantAccs.indexOf(cw.inputs['Кредит'].value.substring(0,2))>=0  || cw.inputs['Товар']!=undefined && quantAccs.indexOf(cw.inputs['Товар'].value.substring(0,2))>=0;
		var inp;
		if((inp=cw.inputs['Количество'])!=undefined) { if(!quantum) { inp.disabled=true; inp.value=''; } else inp.disabled=false; }
	}
	var input=this;
	var xhr = new XMLHttpRequest();
	xhr.open('GET', location.pathname+'?CheckAccount='+encodeURIComponent(this.value)+(this.name=='Товар'?'&wide':''), true);
	xhr.setRequestHeader('X-Type', 'XMLHttpRequest');
	xhr.send(); // (1)
	xhr.onreadystatechange = function() { // (3)
	  if (xhr.readyState != 4) return;
	  if(xhr.getResponseHeader('X-Type')=='auth') { document.write(xhr.responseText); return; }
	  response=JSON.parse(xhr.responseText);
	  if(response.reply!='OK' && !newAcc || response.reply=='OK' && newAcc) {
	  	if(!input.nextSibling || input.nextSibling.tagName!='SPAN') {
			var span=document.createElement('span');
		  	input.parentNode.insertBefore(span,input.nextSibling);
		}
		else span=input.nextSibling;
		span.className='alert';
		span.innerHTML=newAcc?'Счет существует':'Счет не найден';
	  }
	  else if(input.nextSibling && input.nextSibling.className=='alert') {
	  	input.parentNode.removeChild(input.nextSibling);
	  }
	  if(cw.name=='Sale' && input.name=='Товар') {
	  	var v=cw.cnt.getElementsByTagName('span');
	  	for(var i=0;i<v.length;i++) if(v[i].className!='alert' && v[i].className!='remains') { if(response[v[i].getAttribute('name')]!=undefined) v[i].innerHTML=response[v[i].getAttribute('name')]; else v[i].innerHTML=''; }
		cw.obj.quantity=+response['Количество'];
		if(cw.inputs['Код']!=undefined) cw.inputs['Код'].value='';
	  }
	  else if(input.name=='Кредит' && quantAccs.indexOf(input.value.substring(0,2))>=0) {
		if(response['Количество']!=undefined) {
			cw.obj.quantity=+response['Количество'];
		  	if(!input.nextSibling || input.nextSibling.tagName!='SPAN') {
				var span=document.createElement('span');
			  	input.parentNode.insertBefore(span,input.nextSibling);
			}
			else span=input.nextSibling;
			span.className='remains';
			span.innerHTML=remains(cw.obj.quantity);
		}
		else { cw.obj.quantity=0; cw.inputs['Количество'].value=''; }
	  }
	  else if(input.nextSibling && input.nextSibling.className=='remains') {
	  	input.parentNode.removeChild(input.nextSibling);
		cw.obj.quantity=-1;
	  }
	  cw.isOK();
	}
	cw.isLoading(); // (2)
}
function remains(q) {
	var str='Остаток '+cw.obj.quantity+' единиц';
	if(q%100<10 || q%100>20) switch(q % 10) {
		case 1: str+='а'; break;
		case 2:
		case 3:
		case 4: str+='ы'; break;
	}
	return str;
}
function findCode() {
	if(this.value=='') {
	  if(cw.name=='Sale' && this.name=='Код') {
	  	var v=cw.cnt.getElementsByTagName('span');
	  	for(var i=0;i<v.length;i++) if(v[i].className!='alert') v[i].innerHTML='';
	  }
	  if(this.nextSibling && this.nextSibling.className=='alert') {
	  	this.parentNode.removeChild(this.nextSibling);
	  }
	  cw.obj.quantity=0;
	  return;
	}
	this.value=this.value.replace(/[^\d]/g,',');
	this.value=this.value.replace(/,,+/g,',');
	if(cw.name!='Sale') return;
	var input=this;
	var xhr = new XMLHttpRequest();
	xhr.open('GET', '?findCode='+encodeURIComponent(this.value)+(this.name=='Товар'?'&wide':''), true);
	xhr.setRequestHeader('X-Type', 'XMLHttpRequest');
	xhr.send(); // (1)
	xhr.onreadystatechange = function() { // (3)
	  if (xhr.readyState != 4) return;
	  if(xhr.getResponseHeader('X-Type')=='auth') { document.write(xhr.responseText); return; }
	  response=JSON.parse(xhr.responseText);
	  if(response.reply!='OK') {
	  	if(!input.nextSibling || input.nextSibling.className!='alert') {
			var span=document.createElement('span');
			span.className='alert';
			span.innerHTML='Код не найден';
		  	input.parentNode.insertBefore(span,input.nextSibling);
		}
	  }
	  else if(input.nextSibling && input.nextSibling.className=='alert') {
	  	input.parentNode.removeChild(input.nextSibling);
	  }
	  if(cw.name=='Sale' && input.name=='Код') {
	  	var v=cw.cnt.getElementsByTagName('span');
	  	for(var i=0;i<v.length;i++) if(v[i].className!='alert') { if(response[v[i].getAttribute('name')]!=undefined) v[i].innerHTML=response[v[i].getAttribute('name')]; else v[i].innerHTML=''; }
		cw.obj.quantity=+response['Количество'];
		if(cw.inputs['Товар']!=undefined) cw.inputs['Товар'].value='';
	  }
	  cw.isOK();
	}
	cw.isLoading(); // (2)
}
function formatYear() {
	var curYear=(new Date()).getFullYear().toString();
	this.value=curYear.substring(0,4-this.value.length)+this.value;
}
function formatDate() {
	var date=new Date();
	var dateNowText=''+add0(date.getDate())+'.'+add0(date.getMonth()+1)+'.'+date.getFullYear();
	var dd=this.value.match(/\d+/g);
	if(!dd) return;
	if(this.value=='0') this.value=dateNowText;
	else if(dd.length==1) this.value=add0(dd[0])+dateNowText.substring(2);
	else if(dd.length==2) this.value=add0(dd[0])+'.'+add0(dd[1])+dateNowText.substring(5);
	else if(dd[2].length<=4) this.value=add0(dd[0])+'.'+add0(dd[1])+dateNowText.substring(5,10-dd[2].length)+dd[2];
	else if(dd[2].length>4) this.value=add0(dd[0])+'.'+add0(dd[1])+'.'+dd[2].substring(0,4);
	var dateObj=new Date(this.value.substring(6),this.value.substring(3,5)-1,this.value.substring(0,2));
	var cdate=new Date(dateNowText.substring(6),dateNowText.substring(3,5)-1,dateNowText.substring(0,2));
	var mindate=new Date(2000,0,1)
	if(dateObj>cdate ||dateObj<mindate) {
		if(!this.nextSibling || this.nextSibling.className!='alert') {
			var span=document.createElement('span');
			span.className='alert';
			span.innerHTML='Нельзя указывать '+(dateObj>cdate?'будущую дату':'прошлый век');
		  	this.parentNode.insertBefore(span,this.nextSibling);
		}
	}
    else if(this.nextSibling && this.nextSibling.className=='alert') {
	  	this.parentNode.removeChild(this.nextSibling);
	}
	this.value=add0(dateObj.getDate()+'')+'.'+add0((dateObj.getMonth()+1)+'')+'.'+dateObj.getFullYear();
}
function add0(str) {
	str=str.toString();
	if(str.length==1) return '0'+str;
	return str;
}
function checkKeyPress(e) {
	var evt=e?e:event;
	if(evt.keyCode==13) return false;
}
function checkKey(e) {
	var evt=e?e:event;
	if(cw.inpIndex<0) return;
	if(this!=cw.inputs[cw.inpIndex]) {
		cw.inpIndex=0;
		while(this!=cw.inputs[cw.inpIndex] && cw.inpIndex<cw.inputs.length) cw.inpIndex++;
	}
	if(evt.keyCode==13) {
		if(this.type && this.type=='checkbox') this.checked=!this.checked;
		if (cw.inpIndex<cw.inputs.length-1) { for(cw.inpIndex++;cw.inpIndex<cw.inputs.length && cw.inputs[cw.inpIndex].disabled;cw.inpIndex++);	cw.inputs[cw.inpIndex].focus(); }
		else cw.inputs[cw.inpIndex].click();
	}
	else if(evt.keyCode==40 && cw.inputs[cw.inpIndex] && cw.inputs[cw.inpIndex].name=='Поиск' && cw.searches.length>0) { cw.searches[++cw.searchIndex].focus(); return false; }
	else if(evt.keyCode==40 && cw.inpIndex<cw.inputs.length-1) { for(cw.inpIndex++;cw.inpIndex<cw.inputs.length && cw.inputs[cw.inpIndex].disabled;cw.inpIndex++);	cw.inputs[cw.inpIndex].focus(); }
	else if(evt.keyCode==38 && cw.inpIndex>0) { for(cw.inpIndex--;cw.inpIndex>0 && cw.inputs[cw.inpIndex].disabled;cw.inpIndex--);	cw.inputs[cw.inpIndex].focus(); }
	else if(evt.keyCode==39 && this.type=='text' && (this.selectionStart==undefined && this.value.length==0 || this.selectionStart==this.value.length) && this.nextSibling) {
		if(this.nextSibling.className=='magnifier') {this.value=''; cw.tgt=this.nextSibling; setTimeout(search,0);}
		else if(this.nextSibling.nextSibling.className=='magnifier') {this.value=''; cw.tgt=this.nextSibling.nextSibling; setTimeout(search,0);}
	}
}
function checkInput() {
	var match=this.value.match(this.format);
	var start=this.selectionStart;
	this.value=match?match[0]:'';
	this.selectionStart=this.selectionEnd=start;
}
function search() {				// окно для поиска счета или товара по названию
	for(var i=0;i<cw.obj.childNodes.length;i++) if(cw.obj.childNodes[i].className=='search') cw.obj.removeChild(cw.obj.childNodes[i]);
	var inp=cw.tgt; while(inp.previousSibling) {inp=inp.previousSibling; if(inp.tagName=='INPUT') {cw.input=inp; cw.inpIndex=inp.index; break; }}
	if(inp.nextSibling && inp.nextSibling.tagName=='SPAN') inp.parentNode.removeChild(inp.nextSibling);
	inp.value='';
	var template=inp.name=='Товар'?'41':'';
	var div=document.createElement('div');
	div.className='search';
	div.style.right=cw.cnt.offsetWidth-cw.cnt.clientWidth+'px';
	div.style.left=cw.tgt.offsetLeft+'px';
	div.style.top=cw.tgt.offsetTop-4+'px';
	var text="Ищем: <input id='search' type='text'>";
	div.innerHTML=text;
	cw.obj.appendChild(div);
	inp=document.getElementById('search');
	inp.onkeydown=checkSearchKey;
	inp.focus();
	var timerId;
	var res=null;
	inp.oninput=on_inpt;
	inp.onpropertychange = function() {
		if (event.propertyName == "value") on_inpt();
	}
	function on_inpt() {
		if(inp.value.length<3) {if(res){div.removeChild(res); res=null; cw.searchRes=null; cw.searches=[];}return;}
		clearTimeout(timerId);
		timerId=setTimeout(function() {
			if(inp.value.length<3) {if(res){div.removeChild(res); res=null; cw.searchRes=null; cw.searches=[];}return;}
			var xhr = new XMLHttpRequest();
			xhr.open('POST', location.pathname+'?Search'+(template?'&template='+template:''), true);
			xhr.setRequestHeader('X-Type', 'XMLHttpRequest');
			xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
			xhr.send('search='+inp.value); // (1)
			xhr.onreadystatechange = function() { // (3)
				if (xhr.readyState != 4) return;
				if(xhr.getResponseHeader('X-Type')=='auth') { document.write(xhr.responseText); return; }
				if(xhr.responseText!='') {
					if(inp.nextSibling && inp.nextSibling.className=='searchRes') res=inp.nextSibling;
					else {
						res=document.createElement('div');
						res.style.maxHeight=cw.cnt.offsetHeight-div.offsetTop+'px';
						res.className='searchRes';
						div.appendChild(res);
						cw.searchRes=res;
						res.onkeydown=checkSearchKey;
					}
					var respArr=xhr.responseText.split('|');
					cw.searchRes.innerHTML="<div class='searchStr'>"+respArr.join("</div><div class='searchStr'>")+"</div>";
					for(var i=0;i<res.childNodes.length;i++) {cw.searches[cw.searches.length]=res.childNodes[i]; res.childNodes[i].setAttribute('tabindex',i+1);}
					cw.searchIndex=-1;
				}
				else if(res){div.removeChild(res); res=null; cw.searchRes=null; cw.searches=[];}
				cw.isOK();
			}
			cw.isLoading();
		},	500);
	};
}
function searchInvoice() {
	if(cw.isBusy) return;
	var input=this;
	if(input.value.length<3) {
		if(cw.searchRes){cw.obj.removeChild(cw.searchRes); cw.searchRes=null; cw.searches=[];}
		if(cw.inputs['Товар'].nextSibling && cw.inputs['Товар'].nextSibling.tagName=='SPAN')	
				cw.inputs['Товар'].parentNode.removeChild(cw.inputs['Товар'].nextSibling);
		cw.inputs['Товар'].value='';
		return;
	}
	clearTimeout(cw.timerId);
	var template='41';
	cw.timerId=setTimeout(function() {
		if(input.value.length<3) {if(cw.searchRes){cw.obj.removeChild(cw.searchRes); cw.searchRes=null; cw.searches=[];}return;}
		var xhr = new XMLHttpRequest();
		xhr.open('POST', location.pathname+'?SearchInvoice'+'&template='+template, true);
		xhr.setRequestHeader('X-Type', 'XMLHttpRequest');
		xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
		xhr.send('search='+input.value); // (1)
		xhr.onreadystatechange = function() { // (3)
			if (xhr.readyState != 4) return;
			if(xhr.getResponseHeader('X-Type')=='auth') { document.write(xhr.responseText); return; }
			if(xhr.responseText!='') {
				if(cw.obj.lastChild.className=='searchResInvoice') cw.searchRes=cw.obj.lastChild;
				else {
					var res=document.createElement('div');
					res.className='searchResInvoice';
					var a=document.getElementById('anchor');
					res.style.top=a.offsetTop+input.offsetHeight+'px';
					res.style.left=a.offsetLeft+'px';
					res.style.right=cw.cnt.offsetWidth-cw.cnt.clientWidth+'px';
					res.style.maxHeight=cw.cnt.offsetHeight-a.offsetTop+'px'; 
					cw.obj.appendChild(res);
					cw.searchRes=res;
					res.onkeydown=checkSearchKey;
				}
				var respArr=xhr.responseText.split('|');
				cw.searchRes.innerHTML="<div class='searchStr'>"+respArr.join("</div><div class='searchStr'>")+"</div>";
				for(var i=0;i<cw.searchRes.childNodes.length;i++) {cw.searches[cw.searches.length]=cw.searchRes.childNodes[i]; cw.searchRes.childNodes[i].setAttribute('tabindex',i+1);}
				if(cw.inputs['Товар'].nextSibling && cw.inputs['Товар'].nextSibling.tagName=='SPAN')	
						cw.inputs['Товар'].parentNode.removeChild(cw.inputs['Товар'].nextSibling);
				cw.searchIndex=-1;
				if(respArr.length==1 && input.value.match(CODETEMPL)) {cw.searchIndex=0; searchPickup(input);}
			}
			else {
				if(cw.searchRes){cw.obj.removeChild(cw.searchRes); cw.searchRes=null; cw.searches=[];}
				if(input.value.match(CODETEMPL)) {cw.inputs['Код'].value=input.value; input.value=''; input.focus();}
				if(!cw.inputs['Товар'].nextSibling || cw.inputs['Товар'].nextSibling.tagName!='SPAN') {
					var span=document.createElement('span');
					span.className='tip';
					span.innerHTML='Новый товар';
				  	cw.inputs['Товар'].parentNode.insertBefore(span,cw.inputs['Товар'].nextSibling);
				}
				else span=cw.inputs['Товар'].nextSibling;
				span.className='tip';
				span.innerHTML='Новый товар';
				setTimeout(createNewGood,0);
			}
			cw.isOK();
		}
		cw.isLoading();
	},	500);
};
function createNewGood() {
	var xhr = new XMLHttpRequest();
	xhr.open('GET', location.pathname+'?GoodByTemplate='+cw.inputs['Шаблон'].value, true);
	xhr.setRequestHeader('X-Type', 'XMLHttpRequest');
	xhr.send(); // (1)
	xhr.onreadystatechange = function() { // (3)
	  if (xhr.readyState != 4) return;
	  if(xhr.getResponseHeader('X-Type')=='auth') { document.write(xhr.responseText); return; }
	  if(xhr.responseText.match(/^41\.\d+\.\d+|41\.\d+$/)) cw.inputs['Товар'].value=xhr.responseText;
	  else if(xhr.responseText=='OVERFLOW') {
	  	cw.inputs['Товар'].nextSibling.innerHTML='Номера в заданном шаблоне исчерпаны'; 
		cw.inputs['Товар'].nextSibling.className='alert';
		cw.inputs['Товар'].value='';
	  }
	  cw.isOK();
	}
	cw.isLoading(); // (2)
}
function checkSearchKey(e) {
	var evt=e?e:event;
	if(evt.keyCode==13) searchPickup();
	else if(evt.keyCode==40 && cw.searchIndex<cw.searches.length-1) { cw.searches[++cw.searchIndex].focus(); return false; }
	else if(evt.keyCode==38 && cw.searchIndex>0) { cw.searches[--cw.searchIndex].focus(); return false; }
	else if(evt.keyCode==27 || evt.keyCode==37) {
		cw.input.focus();
		for(var i=0;i<cw.obj.childNodes.length;i++) 
			if(cw.obj.childNodes[i].className=='search' || cw.obj.childNodes[i].className=='searchResInvoice') {cw.obj.removeChild(cw.obj.childNodes[i]); cw.searchRes=null; cw.searches=[]; cw.searchIndex=-1; break;}
	}
}
function searchPickup(inp) {
	if(cw.searchIndex<0)return;
	var input=inp?inp:cw.input;
	var s=cw.searches[cw.searchIndex].innerHTML;
	var i=s.indexOf(' ');
	var j=s.indexOf('#');
	var k=s.indexOf('=');
	if(k>0 && k<j) j=k;
	if(j>=0) var itemName=s.substring(i+1,j-1); else itemName=s.substring(i+1);
	if(input.name!='Поиск') input.value=s.substring(0,i);
	else {
		if(input.value.match(CODETEMPL)) cw.inputs['Код'].value=input.value; else cw.inputs['Код'].value='';
		cw.inputs['Товар'].value=s.substring(0,i);
		cw.isBusy=true; setTimeout('cw.isBusy=false',200);
		input.value=itemName;
	}
	if(quantAccs.indexOf(s.substring(0,2))>=0 && cw.name!='Statement') {
		var r=s.match(/=\d+\.\d{2}/);
		var price=r?+r[0].substring(1):0;
		if(price && cw.inputs['Цена_Розн']!=undefined) cw.inputs['Цена_Розн'].value=price;
		if(cw.inputs['Наименование']!=undefined) cw.inputs['Наименование'].value=itemName;
		var r=s.match(/#\d+$/);
		var quantity=r?+r[0].substring(1):0;
		if(cw.name!='Новая строка' && cw.name!='Изменение строки') cw.inputs['Количество'].value='';
		cw.inputs['Количество'].disabled=false;
		if(cw.name=='Sale' && input.name=='Товар') {
			input.on_change();
			cw.inpIndex+=2;
		}
		else {
			if(input.name=='Кредит'){
				var span=document.createElement('span');
				span.className='remains';
				span.innerHTML=remains(quantity);
				cw.obj.quantity=quantity;
			  	input.parentNode.insertBefore(span,input.nextSibling);
			}
			cw.inpIndex+=1;
		}
	}
	else cw.inpIndex+=1;
	if(input && input.name=='Поиск') cw.obj.removeChild(cw.searchRes);
	else cw.obj.removeChild(cw.searchRes.parentNode);
	cw.inputs[cw.inpIndex].focus();
	cw.searchRes=null; cw.searches=[]; cw.searchIndex=-1;
}
function tune() {		// Открытие меню настройки
	for(var i=0;i<cw.obj.childNodes.length;i++) if(cw.obj.childNodes[i].className=='tune-up' || cw.obj.childNodes[i].className=='vidgetOpen') return;
	var div=document.createElement('div');
	div.className='tune-up';
	div.style.right=cw.cnt.offsetWidth-cw.cnt.clientWidth+'px';
	var text=''; i=0;
	while(i<tunes[cw.name].length) {
		text+="<span onclick='"+tunes[cw.name][i];
		text+=tunes[cw.name][i++].indexOf('(')<0?"()'>":"'>";
		text+=tunes[cw.name][i++]+"</span>";
	}
	div.innerHTML=text;
	cw.obj.appendChild(div);
}
function addString() {		// Открыть окно для добавления строки в таблицу (из меню настройки)
	var name=cw.name;
	newVidgetOpen(cw.obj,'new'+name);
	httpRequest(name=='Invoice'?'AddInvoice':'Add&name='+encodeURIComponent(name),'',prepInputs);
}
function saveFromEdit(name,field,value,str2) {		// Сохранение отредактированной строки
	var alerts=cw.obj.getElementsByTagName('span');
	for(var i=0;i<alerts.length;i++) if(alerts[i].className=='alert') return;
	var fields=cw.obj.getElementsByTagName('input');
	var str='columns='+cw.obj.columns;
	var v=getHtmlCollectionByClassName('vidgetOpen');
	if(name=='Entries') {
		if(v['Accounts']!=undefined) str+=(str?'&':'')+'columnsA='+v['Accounts'].columns; 
		if(v['Goods']!=undefined) {
			for(var j=0;j<fields.length;j++) if((fields[j].name=='Дебет' || fields[j].name=='Кредит') && fields[j].value.substring(0,3)=='41.') {
				str+=(str?'&':'')+'columnsG='+v['Goods'].columns;
				break;
			}
		}
	}
	for(i=0;i<fields.length;i++) {
		str+=(str?'&':'')+fields[i].name+'='+fields[i].value;
		if(fields[i].value=='' && notEmptyFields[name].indexOf(fields[i].name)>=0) {
			fields[i].style.transitionDuration='0.2s';
			fields[i].style.backgroundColor='OrangeRed';
			var input=fields[i];
			input.focus();
			setTimeout(function(){input.style.transitionDuration='1s'; input.style.backgroundColor=''},400);
			return;
		}
	}
	if(str2!=undefined) str+=(str?'&':'')+str2;
	var xhr = new XMLHttpRequest();
	xhr.open('POST', location.pathname+'?Save&name='+encodeURIComponent(name)+'&field='+encodeURIComponent(field)+'&value='+encodeURIComponent(value), true);
	xhr.setRequestHeader('X-Type', 'XMLHttpRequest');
	xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	xhr.send(str); // (1)
	xhr.onreadystatechange = function() { // (3)
		if (xhr.readyState != 4) return;
		if(xhr.getResponseHeader('X-Type')=='auth') { document.write(xhr.responseText); return; }
		var reply=xhr.responseText.substring(0,5).toUpperCase();
		if(reply=='ERROR' || reply=='ОШИБК') {cw.cnt.innerHTML = xhr.responseText; return;}
		var refstr=cw.obj.refstr;
		response=JSON.parse(xhr.responseText);
		var cells=response.reply.split('|');
		for(i=1;i<refstr.cells.length;i++) {
			refstr.cells[i].innerHTML=cells[i-1];
//			refstr.cells[i].style.backgroundColor='';
		}
		refstr.style.transitionDuration='0.5s';
		refstr.style.backgroundColor='Moccasin';
		setTimeout(function(){refstr.style.transitionDuration='2s'; refstr.style.backgroundColor=''},1000);
		setTimeout(function(){refstr.style.transitionDuration=''; },3000);
		if(response.Accounts!=undefined) {
			var tab=v['Accounts'].getElementsByTagName('table')[0];
			var respArr=response.Accounts.split('^');
			for(i=0;i<respArr.length;i++) replaceString(respArr[i],tab);
		}
		if(response.Goods!=undefined) {
			var tab=v['Goods'].getElementsByTagName('table')[0];
			var respArr=response.Goods.split('^');
			for(i=0;i<respArr.length;i++) replaceString(respArr[i],tab);
		}
		cw.isOK();
		if(cw.name!='LoadBank') {
			cw.obj.parentNode.removeChild(cw.obj); 
			cw.close();
			setTimeout(liveVidgets,0);
		}
		if(v['LoadBank']!=undefined){
			var canDoEntry=true;
			var tab=v['LoadBank'].getElementsByTagName('table')[0];
			for(i=1;i<tab.rows.length;i++)
				for(var j=1;j<tab.rows[i].cells.length;j++) 
					if(tab.rows[i].cells[j].innerHTML=='&nbsp;' || tab.rows[i].cells[j].innerHTML=='') {
						tab.rows[i].cells[j].style.backgroundColor='Moccasin';
						canDoEntry=false;
					}
					else tab.rows[i].cells[j].style.backgroundColor='';
			v['LoadBank'].getElementsByTagName('button')[0].disabled=!canDoEntry;
		}
	}
	cw.isLoading(); // (2)
}
function saveFromAdd(name) {		// Сохранение новой строки
	var alerts=cw.obj.getElementsByTagName('span');
	for(var i=0;i<alerts.length;i++) if(alerts[i].className=='alert') return;
	var fields=cw.obj.getElementsByTagName('input');
	var str='';
	var v=getHtmlCollectionByClassName('vidgetOpen');
	if(cw.isPrimary) {
		if(v['Entries']!=undefined) {
			if(v['Entries'].columns==undefined) str+=(str?'&':'')+'columnsE=unknown';
			else str+=(str?'&':'')+'columnsE='+v['Entries'].columns;
		}
		if(v['Accounts']!=undefined) str+=(str?'&':'')+'columnsA='+v['Accounts'].columns;
		if(v['Goods']!=undefined) {
			for(var j=0;j<fields.length;j++) if((fields[j].name=='Дебет' || fields[j].name=='Кредит') && fields[j].value.substring(0,3)=='41.') {
				str+=(str?'&':'')+'columnsG='+v['Goods'].columns;
				break;
			}
		}
	}
	else {
		if(cw.columns=='') str+=(str?'&':'')+'columns=unknown';
		else  str+=(str?'&':'')+'columns='+cw.columns;
		if(name=='Entries') {
			if(v['Accounts']!=undefined) str+=(str?'&':'')+'columnsA='+v['Accounts'].columns;
			if(v['Goods']!=undefined) {
				for(var j=0;j<fields.length;j++) if((fields[j].name=='Дебет' || fields[j].name=='Кредит') && fields[j].value.substring(0,3)=='41.') {
					str+=(str?'&':'')+'columnsG='+v['Goods'].columns;
					break;
				}
			}
		}
	}
	for(i=0;i<fields.length;i++) {
		if(name=='Invoice' && fields[i].name=='Поиск') str+=(str?'&':'')+'Наименование='+fields[i].value;
		else if(fields[i].value!='') str+=(str?'&':'')+fields[i].name+'='+fields[i].value;
		else if(notEmptyFields[name].indexOf(fields[i].name)>=0) {
			fields[i].style.transitionDuration='0.2s';
			fields[i].style.backgroundColor='OrangeRed';
			var input=fields[i];
			input.focus();
			setTimeout(function(){input.style.transitionDuration='1s'; input.style.backgroundColor=''},400);
			return;
		}
	}
	if(name=='Goods') str+=(str?'&':'')+'АП=А';
	var xhr = new XMLHttpRequest();
	xhr.open('POST', location.pathname+'?Add&name='+encodeURIComponent(name), true);
	xhr.setRequestHeader('X-Type', 'XMLHttpRequest');
	xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	xhr.send(str); // (1)
	xhr.onreadystatechange = function() { // (3)
	  if (xhr.readyState != 4) return;
	  if(xhr.getResponseHeader('X-Type')=='auth') { document.write(xhr.responseText); return; }
	  cw.isOK();
	  var reply=xhr.responseText.substring(0,5).toUpperCase();
	  if(reply=='ERROR' || reply=='ОШИБК') {cw.cnt.innerHTML = xhr.responseText; return;}
	  response=JSON.parse(xhr.responseText);
//	  var v=document.getElementsByClassName('vidgetOpen');
	  if(response.Entries!=undefined) {
		if(v['Entries']!=undefined) {
			var obj=v['Entries'];
			for(i=0;i<obj.childNodes.length;i++) if(obj.childNodes[i].className=='content') {
				insertString(response.Entries,obj.childNodes[i]);
				break;
			}
		}
	  }
	  if(response.Accounts!=undefined) {
		var respArr=response.Accounts.split('^');
		if(v['Accounts']!=undefined) {
			var tab=v['Accounts'].getElementsByTagName('table')[0];
			replaceString(respArr[0],tab);
			replaceString(respArr[1],tab);
		}
	  }
	  if(response.Goods!=undefined) {
		var respArr=response.Goods.split('^');
		if(v['Goods']!=undefined) {
			var tab=v['Goods'].getElementsByTagName('table')[0];
			replaceString(respArr[0],tab);
			if(respArr.length>1) replaceString(respArr[1],tab);
		}
	  }
	  if(cw.isPrimary) {
	  	for(i=0;i<fields.length;i++) if(fields[i].name!='Дата') fields[i].value='';	
		fields[0].focus();
	  }
	  else if(cw.name=='Новая строка') {
	  	for(i=3;i<fields.length;i++) fields[i].value='';	
		fields[3].focus();
		v=cw.obj.parentNode.getElementsByTagName('div');
		for(i=0;i<v.length;i++) if(v[i].className=='content') {insertString(response.reply,v[i]); break;}
	  }
	  else {
	  	var obj=cw.obj.parentNode;
	  	cw.obj.parentNode.removeChild(cw.obj);
		zIndexUp(obj);
		insertString(response.reply,cw.cnt);
	  }
	  setTimeout(liveVidgets,0);
	}
	cw.isLoading(); // (2)
}
function insertString(text,cnt) {
	if(text.indexOf('|')<0) {
		if(text=='Reload') { setTimeout(winOpened,0); return; }
		cnt.innerHTML=text;
		var tab=cnt.getElementsByTagName('table');
		if(tab.length==0) {cw.columns=''; return;}
		tab=tab[0];
		var columns=[];
		for(i=1;i<tab.rows[0].cells.length;i++) {
			columns[i-1]=tab.rows[0].cells[i].innerHTML;
			if(columns[i-1].indexOf('<')>=0) return;
		} 
		cw.columns='`'+columns.join('`,`')+'`';
		cw.obj.columns=cw.columns;
	}
	else {
		var cells=text.split('|');
		var tab=cnt.getElementsByTagName('table')[0];
		var tr=tab.rows[1].cloneNode(true);
		for(var j=1;j<tr.cells.length;j++) tr.cells[j].innerHTML=cells[j-1];
		tab.rows[1].parentNode.insertBefore(tr, tab.rows[1]);
		tr.style.transitionDuration='0.5s';
	    tr.style.backgroundColor='Moccasin';
	    setTimeout(function(){tr.style.transitionDuration='2s'; tr.style.backgroundColor=''},1000);
	    setTimeout(function(){tr.style.transitionDuration=''; },3000);
	}
}
function replaceString(text,tab) {				// Замена строки ИСКЛЮЧИТЕЛЬНО в Accounts
	var cells=text.split('|');
	for(var i=0;i<tab.rows[0].cells.length;i++) if(tab.rows[0].cells[i].innerHTML=='Счет') break;
	for(var j=1;j<tab.rows.length;j++) if(tab.rows[j].cells[i].innerHTML==cells[i-1]) break;
	if(j==tab.rows.length) return;
	var tr=tab.rows[j];
	for(i=1;i<tr.cells.length;i++) tr.cells[i].innerHTML=cells[i-1];
	tr.style.transitionDuration='0.5s';
    tr.style.backgroundColor='Moccasin';
    setTimeout(function(){tr.style.transitionDuration='2s'; tr.style.backgroundColor=''},1000);
}
function deleteString(name,field,value) {		// Удаление текущей строки
	var xhr = new XMLHttpRequest();
	var fields=cw.obj.getElementsByTagName('input');
	var str='';
	if(name=='Entries') {
		var v=getHtmlCollectionByClassName('vidgetOpen');
		if(v['Accounts']!=undefined) str+=(str?'&':'')+'columnsA='+v['Accounts'].columns; 
		if(v['Goods']!=undefined) {
			for(var j=0;j<fields.length;j++) if((fields[j].name=='Дебет' || fields[j].name=='Кредит') && fields[j].value.substring(0,3)=='41.') {
				str+=(str?'&':'')+'columnsG='+v['Goods'].columns;
				break;
			}
		}
	}
	xhr.open('POST', location.pathname+'?Delete&name='+encodeURIComponent(name)+'&field='+encodeURIComponent(field)+'&value='+encodeURIComponent(value), true);
	xhr.setRequestHeader('X-Type', 'XMLHttpRequest');
	xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	xhr.send(str); // (1)
	xhr.onreadystatechange = function() { // (3)
		if (xhr.readyState != 4) return;
		if(xhr.getResponseHeader('X-Type')=='auth') { document.write(xhr.responseText); return; }
		var reply=xhr.responseText.substring(0,5).toUpperCase();
		if(reply=='ERROR' || reply=='ОШИБК') {cw.cnt.innerHTML = xhr.responseText; return;}
	    response=JSON.parse(xhr.responseText);
		if(response.reply=='Deleted') {
			var refstr=cw.obj.refstr;
			refstr.style.transitionDuration='0.5s';
			refstr.style.backgroundColor='Moccasin';
			setTimeout(function(){refstr.style.transitionDuration=''; refstr.parentNode.removeChild(refstr)},500);
			if(response.Accounts!=undefined) {
				var tab=v['Accounts'].getElementsByTagName('table')[0];
				var respArr=response.Accounts.split('^');
				for(i=0;i<respArr.length;i++) replaceString(respArr[i],tab);
			}
			if(response.Goods!=undefined) {
				var tab=v['Goods'].getElementsByTagName('table')[0];
				var respArr=response.Goods.split('^');
				for(i=0;i<respArr.length;i++) replaceString(respArr[i],tab);
			}
			var tab=refstr;
			while(tab.tagName!='TABLE' && tab!=document) tab=tab.parentNode;
			if(tab.tagName=='TABLE' && tab.rows.length<=2) cw.obj.parentNode.columns='';
		}
	 	cw.isOK();
		cw.obj.parentNode.removeChild(cw.obj); 
		cw.close();
		setTimeout(liveVidgets,0);
	}
	cw.isLoading(); // (2)
}
function Confirm() {			// аргументы: текст, функция "да", аргументы для передачи этой функции через запятую
//	for(var i=0;i<document.body.childNodes.length;i++) if(document.body.childNodes[i].className=='confirm') return;
	var cover=document.createElement('div');
	cover.id='coverDiv';
	cw.obj.appendChild(cover);
	var div=document.createElement('div');
	div.id='confirm';
	var args=[];
	for(var i=2;i<arguments.length;i++) args[args.length]=arguments[i];
	args="\""+args.join("\",\"")+"\"";
	div.innerHTML=arguments[0]+"<br><button onclick='"+arguments[1]+"("+args+");confirmDone()'>Да</button><button onclick='confirmDone()'>Нет</button>";
	cw.obj.appendChild(div);
}
function confirmDone() {
	document.getElementById('coverDiv').parentNode.removeChild(document.getElementById('coverDiv'));
	document.getElementById('confirm').parentNode.removeChild(document.getElementById('confirm'));
}
function statement() {
	var alerts=cw.obj.getElementsByTagName('span');
	for(var i=0;i<alerts.length;i++) if(alerts[i].className=='alert') return;
	var v=cw.cnt.getElementsByTagName('input');
	for(i=0;i<v.length;i++) {
		if(v[i].value=='' && v[i].type=='text') return;
		switch (v[i].getAttribute('name')) {
			case 'СчетВ': var Acc=v[i].value; break;
			case 'ДатаОт': var First=v[i].value; break;
			case 'ДатаДо': var End=v[i].value; break;
			case 'ВклСбсч': var Sub=v[i].checked?'Yes':'No'; break;
		}
	}
	httpRequest('Statement&Acc='+Acc+'&First='+First+'&End='+End+'&Sub='+Sub,'',prepInputs);
}
function doSale() {
	var alerts=cw.obj.getElementsByTagName('span');
	for(var i=0;i<alerts.length;i++) if(alerts[i].className=='alert') return;
	var fields=cw.obj.getElementsByTagName('input');
	var str='';
	var v=getHtmlCollectionByClassName('vidgetOpen');
	if(v['Entries']!=undefined) {
		if(v['Entries'].columns==undefined) str+=(str?'&':'')+'columnsE=unknown';
		else str+=(str?'&':'')+'columnsE='+v['Entries'].columns;
	}
	if(v['Accounts']!=undefined) str+=(str?'&':'')+'columnsA='+v['Accounts'].columns;
	if(v['Goods']!=undefined) {
		if(cw.name=='Sale') var j=0;
		else for(var j=0;j<fields.length;j++) if((fields[j].name=='Дебет' || fields[j].name=='Кредит') && fields[j].value.substring(0,3)=='41.') break;
		if(j<fields.length) str+=(str?'&':'')+'columnsG='+v['Goods'].columns;
	}
	for(i=0;i<fields.length;i++) {
		if(fields[i].value) str+=(str?'&':'')+fields[i].name+'='+fields[i].value;
		else if(notEmptyFields['Sale'].indexOf(fields[i].name)>=0 && (fields[i].name!='Код' || !str.match(/Товар=41\.\d/))) {
			fields[i].style.transitionDuration='0.2s';
			fields[i].style.backgroundColor='OrangeRed';
			var input=fields[i];
			input.focus();
			setTimeout(function(){input.style.transitionDuration='1s'; input.style.backgroundColor=''},400);
			return;
		}
	}
	var xhr = new XMLHttpRequest();
	xhr.open('POST', location.pathname+'?Sale', true);
	xhr.setRequestHeader('X-Type', 'XMLHttpRequest');
	xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	xhr.send(str); // (1)
	xhr.onreadystatechange = function() { // (3)
	  if (xhr.readyState != 4) return;
	  if(xhr.getResponseHeader('X-Type')=='auth') { document.write(xhr.responseText); return; }
	  cw.isOK();
	  var reply=xhr.responseText.substring(0,5).toUpperCase();
	  if(reply=='ERROR' || reply=='ОШИБК') {cw.cnt.innerHTML = xhr.responseText; return;}
	  response=JSON.parse(xhr.responseText);
//	  var v=document.getElementsByClassName('vidgetOpen');
	  if(response.Entries!=undefined) {
		var respArr=response.Entries.split('^');
		if(v['Entries']!=undefined) {
			var obj=v['Entries'];
			for(i=0;i<obj.childNodes.length;i++) if(obj.childNodes[i].className=='content') {
				insertString(respArr[1],obj.childNodes[i]);
				insertString(respArr[0],obj.childNodes[i]);
				break;
			}
		}
	  }
	  if(response.Accounts!=undefined) {
		var respArr=response.Accounts.split('^');
		if(v['Accounts']!=undefined) {
			var tab=v['Accounts'].getElementsByTagName('table')[0];
			replaceString(respArr[0],tab);
			replaceString(respArr[1],tab);
		}
	  }
	  if(response.Goods!=undefined) {
		var respArr=response.Goods.split('^');
		if(v['Goods']!=undefined) {
			var tab=v['Goods'].getElementsByTagName('table')[0];
			replaceString(respArr[0],tab);
			if(respArr.length>1) replaceString(respArr[1],tab);
		}
	  }
  	  for(i=0;i<fields.length;i++) if(fields[i].name!='Дата' && fields[i].name!='Дебет') fields[i].value='';	
	  fields[2].focus();
	  setTimeout(liveVidgets,0);
	}
	cw.isLoading(); // (2)
}
function loadInvoice() {
	httpRequest('LoadInvoice','',liveVidgets);
}
function httpRequest(getstr,poststr,callback) {
	var xhr = new XMLHttpRequest();
	xhr.open(poststr?'POST':'GET', location.pathname+'?'+getstr, true);
	xhr.setRequestHeader('X-Type', 'XMLHttpRequest');
	if(poststr) xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	xhr.send(poststr);
	xhr.onreadystatechange = function() {
	  if (xhr.readyState != 4) return;
	  if(xhr.getResponseHeader('X-Type')=='auth') { document.write(xhr.responseText); return; }
	  cw.isOK();
	  if(xhr.responseText.substring(0,1)!='{')  { cw.cnt.innerHTML = xhr.responseText; response=null; }
	  else {
		response=JSON.parse(xhr.responseText);
		if(response.reply!=undefined) cw.cnt.innerHTML=response.reply;
	  }
	  if(callback) setTimeout(callback,0);
	}
	cw.isLoading();
}
function diagnostics() {
	newVidgetOpen(cw.obj,'Diagnostics');
	httpRequest('Diagnostics');
}
function showTables() {
	newVidgetOpen(cw.obj,'ShowTables');
	httpRequest('ShowTables');
}
function reset() {
	Confirm("Информация из базы данных будет уничтожена. Вы уверены?","_reset");
}
function _reset() {
	newVidgetOpen(cw.obj,'Reset');
	httpRequest('Reset','',liveVidgets);
}
function prepLinks() {
	var a=cw.cnt.getElementsByTagName('a');
	for(var i=0;i<a.length;i++) if(a[i].hasAttribute('link')) a[i].onclick=function() {httpRequest(this.getAttribute('link'),'',prepLinks); return false; }
}
function moreEntries() {
	var xhr = new XMLHttpRequest();
	xhr.open('GET', location.pathname+'?MoreEntries='+cw.nRows, true);
	xhr.setRequestHeader('X-Type', 'XMLHttpRequest');
	xhr.send();
	xhr.onreadystatechange = function() {
		if (xhr.readyState != 4) return;
		if(xhr.getResponseHeader('X-Type')=='auth') { document.write(xhr.responseText); return; }
		cw.isOK();
		if(xhr.responseText.substring(0,1)=='{') {
			response=JSON.parse(xhr.responseText);
			var tab=cw.cnt.getElementsByTagName('table')[0];
			for(var i=0;i<response.Entries.length;i++) {
				var tr=tab.rows[1].cloneNode(true);
				var cells=response.Entries[i].split('|');
				for(var j=1;j<cells.length;j++) tr.cells[j].innerHTML=cells[j];
				tab.rows[1].parentNode.appendChild(tr);
			}
			if(response.More=='More') tab.caption.setAttribute('rows',(tab.rows.length-1).toString());
			else tab.removeChild(tab.caption);
		}
	}
	cw.isLoading();
}
function titulPage() {
	newVidgetOpen(cw.obj,'titulPage');
	httpRequest('KudirRules=titul','',prepInputs);
}
function kudirRules() {
	newVidgetOpen(cw.obj,'kudirRules');
	httpRequest('KudirRules=rules','',prepInputs);
}
function kudirTunes() {
	newVidgetOpen(cw.obj,'kudirTunes');
	httpRequest('KudirRules=tunes','',prepInputs);
}
function kudir2() {
	if(cw.inputs['Year'].value.match(/^20\d\d$/)) httpRequest('Kudir2='+cw.inputs['Year'].value,'',function(){prepInputs();calcFormula()});
}
function kudir3() {
	if(cw.inputs['Year'].value.match(/^20\d\d$/)) httpRequest('Kudir3='+cw.inputs['Year'].value,'',function(){prepInputs();calcFormula()});
}
function kudir4() {
	if(cw.inputs['Year'].value.match(/^20\d\d$/)) httpRequest('Kudir4='+cw.inputs['Year'].value,'',function(){prepInputs();calcFormula()});
}
function kudir5() {
	if(cw.inputs['Year'].value.match(/^20\d\d$/)) httpRequest('Kudir5='+cw.inputs['Year'].value,'',function(){prepInputs();calcFormula()});
}
function saveFromKudir() {
	var str='';
	var v=cw.cnt.getElementsByTagName('input');
	for(var i=0;i<v.length;i++) switch(v[i].type){
		case 'text': str+=(str?'&':'')+v[i].name+'='+v[i].value; break
		case 'radio': if(v[i].checked) str+=(str?'&':'')+v[i].name+'='+v[i].value; break;
		case 'checkbox': str+=(str?'&':'')+v[i].name+'='+(v[i].checked?'Да':'Нет'); break;
	}
	v=cw.cnt.getElementsByTagName('textarea');
	for(i=0;i<v.length;i++) str+=(str?'&':'')+v[i].name+'='+encodeURIComponent(v[i].value);
	httpRequest('KudirRules=save',str,closeVidget);
}
function kudirForm() {
	var str='Kudir';
	var v=cw.cnt.getElementsByTagName('input');
	for(var i=0;i<v.length;i++) if(v[i].type!='radio' || v[i].checked) str+=(str?'&':'')+v[i].name+'='+v[i].value;
	httpRequest(str,'',function(){prepInputs();calcFormula()});
}
// n = n.replace(/\B(?=(?:\d{3})+(?!\d))/g, ' ');
// n = n.replace(/\s/g, '')
function calcFormula() {
	var tab=cw.cnt.getElementsByTagName('table');
	for(var i=tab.length-1;tab[i].className=='npr';i--);
	tab=tab[i];
	if(tab==undefined) return;
	var top=-1;
nextrow:
	for(var i=0;i<tab.rows.length;i++) {
		var tr=tab.rows[i];
		var tophere=true;
		for(var j=0;j<tr.cells.length;j++) {
			var td=tr.cells[j]; var f;
			if(top<0 && td.innerHTML!=j+1) tophere=false;
			if(td.tagName!='TD') continue nextrow;
			if(f=td.getAttribute('formula')) {
				if(f=='TOTAL') {
					var s=0;
					for(var k=top+1;k<i;k++) {
						var c=tab.rows[k].cells[j].innerHTML.replace(/\s+/g,'');
						if(c!='&nbsp;') s+=+c;
					}
					td.innerHTML=s!=0?s.toFixed(2).replace(/\B(?=(?:\d{3})+(?!\d))/g, ' '):'&nbsp;';
				}
				else {
					f=f.replace(/#\(\d{1,2},\d{1,2}\)|#\(\d{1,2}\)/g,replacer);
					f=eval(f);
					if(typeof(f)!='number') {
						if(f.match(/^-?\d+\.?\d*$/)) td.innerHTML=(+f).toFixed(2).replace(/\B(?=(?:\d{3})+(?!\d))/g, ' ');
						else td.innerHTML=f;
					}
					else if(!isFinite(f)) td.innerHTML='-';
					else if(f==0) td.innerHTML='&nbsp;';
					else {
						td.innerHTML=(+f).toFixed(2).replace(/\B(?=(?:\d{3})+(?!\d))/g, ' ');
						if(td.hasAttribute('float')) td.value=f;
					}
				}
			}
		}
		if(top<0 && tophere) top=i;
	}
	function replacer(str) {
		var ret=0;
		var match=str.match(/(\d+),(\d+)/);
		if(match) ret=cellGetValue(tab.rows[+match[1]+top].cells[match[2]-1]);
		else {
			match=str.match(/\d+/);
			if(match) ret=cellGetValue(tr.cells[match[0]-1]);
		}
		if(ret=='&nbsp;') return 0;
		ret=ret.replace(/\s+/g,'');
		if(ret.match(/^-\d/)) return '('+ret+')';
		return ret;
	}
	function cellGetValue(c) {
		if(c.value!=undefined) return c.value.toString();
		else return c.innerHTML;
	}
}
var datenow=new Date();
var datenow=new Date(datenow.getFullYear(),datenow.getMonth(),datenow.getDate());
var currentMonth=datenow.getMonth();
var currentYear=datenow.getFullYear();
function makeCalTable(className) {
	var str="<table class='"+className+"' border=0>";
	str+="<tr><td colspan=7 class='today'>Сегодня: "+shortDOW[datenow.getDay()]+', '+add0(datenow.getDate())+'.'+add0(datenow.getMonth()+1)+'.'+datenow.getFullYear()+"</td></tr>";
	str+="<tr><td class='scroll' onclick='calendar(-1)'><<</td><td colspan=5 id='currentMonth'></td><td class='scroll' onclick='calendar(1)'>>></td></tr>";
	str+="<tr style='background-color:#e0e0e0'><td>Пн</td><td>Вт</td><td>Ср</td><td>Чт</td><td>Пт</td><td>Сб</td><td>Вс</td></tr>";
	var k=1;
	for(var i=0;i<6;i++) {
		str+="<tr>";
		for(var j=0;j<7;j++) str+="<td id='cw"+(k++)+"'>&nbsp;</td>";
		str+="</tr>";
	}
	str+="</table>";
	return str;
}
function fillCalendar() {
	document.getElementById('currentMonth').innerHTML=Months[currentMonth]+' '+currentYear;
	var day=new Date(currentYear,currentMonth,1);
	firstday=day.getDay();
	if(firstday==0) firstday=7;
	var curdate;
	var cell;
	for(var i=1;i<=42;i++) {
		(cell=document.getElementById('cw'+i)).innerHTML=(i-firstday>=0)&&(curdate=new Date(currentYear,currentMonth,i-firstday+1)).getMonth()==currentMonth?i-firstday+1:'&nbsp;';
		if(curdate!=undefined && curdate-datenow==0) cell.style.backgroundColor='powderblue'; else cell.style.backgroundColor='';
	}
	if(isNaN(document.getElementById('cw36').innerHTML))  document.getElementById('cw36').parentNode.style.display='none';
	else document.getElementById('cw36').parentNode.style.display='';
}
function calendar(step) {
	if(step<0) { if(--currentMonth<0) {currentMonth=11; currentYear--;}	}
	else if(step>0) { if(++currentMonth==12) {currentMonth=0; currentYear++;} }
	if(cw.cnt.innerHTML=='') cw.cnt.innerHTML=makeCalTable('calendarWidget');
	fillCalendar();
}
function createCalendar() {
	var v=getHtmlCollectionByClassName('vidgetOpen');
	if(v['Calendar']!=undefined) v['Calendar'].parentNode.removeChild(v['Calendar']);
	var div=document.createElement('div');
	div.className='calendar';
	div.innerHTML=makeCalTable('calendarTab');
	div.style.top=(cw.tgt.offsetTop+169>cw.cnt.offsetHeight?cw.cnt.offsetHeight-169:cw.tgt.offsetTop)+'px';
	div.style.left=(cw.tgt.offsetLeft+162>cw.cnt.offsetWidth?cw.cnt.offsetWidth-162:cw.tgt.offsetLeft)+'px';
	cw.cnt.appendChild(div);
	fillCalendar();
	for(var t=cw.tgt.previousSibling;t && t.tagName!='INPUT';t=t.previousSibling);
	cw.input=t;
	cw.inpIndex=cw.input.index;
	cw.input.focus();
}
function pickupDate() {
	cw.input.value=add0(cw.tgt.innerHTML)+'.'+add0(currentMonth+1)+'.'+currentYear;
	var t=cw.tgt.parentNode;
	while(t=t.parentNode) if(t.className=='calendar') { t.parentNode.removeChild(t); cw.tgt=null; break; }
	cw.input.on_change();
	cw.input.focus();
}
function calculator() {
	var signs=['C','&larr;','%','GT','7','8','9','+','4','5','6','-','1','2','3','*','.','0','=','/'];
	var str="<table id='calculatorWidget' border=0><tr><td colspan=4><div id='calcScreen'>0.00</div></td></tr>";
	for(var i=0;i<5;i++) {
		str+="<tr>";
		for(var j=0;j<4;j++) str+="<td><div class='button'>"+signs[i*4+j]+"</div></td>";
		str+="</tr>";
	}
	str+="</table>";
	cw.cnt.innerHTML=str;
	var v=document.getElementsByClassName('button');
	for(i=0;i<v.length;i++) {
		v[i].onmousedown=buttonMouse;
		v[i].onmouseup=buttonMouse;
		v[i].onmouseout=buttonMouse;
	}
	document.getElementById('calcScreen').innerHTML=document.getElementById('calcScreen0').innerHTML;
}
var calc={
	greatTotal:0,
	operand:'',
	operator:'',
	second:true,
	prec:2
}
function precision(n) {
	var m='0234-1'.indexOf(n.toString());
	for(var i=0;i<5;i++) tunes['Calculator'][i*2+1]=(i==m?'&bull;':'&nbsp;')+tunes['Calculator'][i*2+1].replace(/^&.+;/,'');
	calc.prec=n;
	var scr=calcFormat(document.getElementById('calcScreen').innerHTML.replace(/`/g, ''));
	var arr=scr.split('.');
	scr=arr[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, '`');
	if(arr[1]!=undefined) scr+='.'+arr[1];
	document.getElementById('calcScreen').innerHTML=scr;
}
function button() {
	var sign=cw.tgt.innerHTML;
	var scr=document.getElementById('calcScreen').innerHTML.replace(/`/g, '');
	if('0123456789.'.indexOf(sign)>=0) {
		if(calc.second) { scr=calcFormat(0); calc.second=false; }
		if(sign=='.') { if(scr==calcFormat(0)) scr='0.'; else if(scr.indexOf('.')<0) scr+='.'; }
		else { if(scr==calcFormat(0)) scr=sign; else scr+=sign; }
	}
	else switch(sign) {
		case 'C':		scr=calcFormat(0); calc.greatTotal=0; calc.operand=''; calc.operator=''; break;
		case '-':		if(calc.operand=='') { scr='-'; calc.second=false; break; }
		case '+':	case '*':
		case '/':		if(calc.operand!='' && calc.operator!='' && !calc.second) scr=calcFormat(eval(calc.operand+calc.operator+scr)); calc.operand=scr; calc.operator=sign; calc.second=true; break;
		case '=':		if(calc.operand!='' && calc.operator!='') scr=calcFormat(eval(calc.operand+calc.operator+scr)); calc.second=true; calc.greatTotal+=(+scr); break;
		case '%':		if(calc.operand!='') switch(calc.operator) {
							case '+': scr=calcFormat((+calc.operand)+(+calc.operand)*(+scr)/100); calc.second=true; break;
							case '-': scr=calcFormat((+calc.operand)-(+calc.operand)*(+scr)/100); calc.second=true; break;
							case '*': scr=calcFormat((+calc.operand)*(+scr)/100); calc.second=true; break;
							case '/': scr=calcFormat((+calc.operand)/(+scr)*100); calc.second=true; break;
						}
						calc.greatTotal+=(+scr); break;
		case 'GT':		scr=calcFormat(calc.greatTotal); break;
		default:		if(sign.charCodeAt()==8592 && scr!=calcFormat(0)) { if(scr.length>1) scr=scr.substring(0,scr.length-1); else scr=calcFormat(0); } break;
	}
	var arr=scr.split('.');
	scr=arr[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, '`');
	if(arr[1]!=undefined) scr+='.'+arr[1];
	document.getElementById('calcScreen').innerHTML=scr;
}
function calcFormat(x) {
	if(calc.prec>=0) return (+x).toFixed(calc.prec);
	else return (+x).toFixed(12).replace(/\.0+$|0+$/,'');
}
function buttonMouse(e) {
	var evt=e?e:event;
	var cse=evt.target?evt.target:evt.srcElement;
	if(cse.className=='button') {
		if(evt.type=='mousedown') { cse.style.borderStyle='inset'; cse.style.backgroundColor='#c0c0c0'; }
		else { cse.style.borderStyle='outset'; cse.style.backgroundColor='#d0d0d0'; }
	}
}
function warn() {
	var cover=document.createElement('div');
	cover.id='coverDiv';
	document.body.appendChild(cover);
	var div=document.createElement('div');
	div.id='warning';
	div.innerHTML="<span class='close' onclick='xclose()'>Закрыть</span><div>ВНИМАНИЕ!<br>Доступ к <b>демо-версии</b> имеют все пользователи.<br>"+
		"Не размещайте здесь конфиденциальную информацию.<br>"+
		"Другие пользователи <b>демо-версии</b> одновременно с вами<br> могут добавлять, изменять и удалять информацию.<br>"+
		"Не удивляйтесь неожиданным изменениям введенной Вами информации.</div>"
	document.body.appendChild(div);
}
function xclose() {
	document.body.removeChild(document.getElementById('coverDiv'));
	document.body.removeChild(document.getElementById('warning'));
}
if(location.href.indexOf('demo.liteacc.ru')>0) setTimeout(warn,2000);
function findEntry() {
	var name=cw.name;
	newVidgetOpen(cw.obj,'FindEntry');
	httpRequest('FindEntry','',prepInputs);
}
function goFindEntry() {									// Поиск проводок
	var alerts=cw.obj.getElementsByTagName('span');
	for(var i=0;i<alerts.length;i++) if(alerts[i].className=='alert') return;
	var fields=cw.obj.getElementsByTagName('input');
	var str='';
	for(i=0;i<fields.length;i++) if(fields[i].value!='') str+=(str?'&':'')+fields[i].name+'='+fields[i].value;
	if(str!='') httpRequest('FindEntry',str,function() {
	  	var obj=cw.obj.parentNode;
	  	cw.obj.parentNode.removeChild(cw.obj);
		zIndexUp(obj);
		cw.cnt.innerHTML=response.Entries;
	});
}
function bindAccount() {
	var alerts=cw.obj.getElementsByTagName('span');
	for(var i=0;i<alerts.length;i++) if(alerts[i].className=='alert') return;
	if(!cw.inputs['СчетБ'].value.match(/^51/)) return;
	var str='LoadBank&BankLoaded&BindAccount='+cw.inputs['СчетБ'].value;
	httpRequest(str,'',prepInputs);
}
function loadBankToAccount() {
	httpRequest('LoadBankToAccount','',liveVidgets);
}
