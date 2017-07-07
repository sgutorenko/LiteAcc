//document.all?body.attachEvent('onload',prepare):body.addEventListener('load',prepare,false);
function prepare() {
	var obj=document.getElementById('authform');
	obj.style.left=(document.documentElement.clientWidth-obj.offsetWidth-2)/2 + 'px';
	obj.style.top=(document.documentElement.clientHeight-obj.offsetHeight-2)/2.2 + 'px';
	obj.style.paddingTop=(obj.offsetHeight-document.getElementsByTagName('table')[0].offsetHeight)/2 +'px';
	obj.style.visibility='visible';
}
