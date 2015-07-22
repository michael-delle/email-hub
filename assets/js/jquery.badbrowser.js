function badBrowser(){
	//if($.browser.safari && parseInt($.browser.version) > 7){ return true;}

	return false;
}

function getBadBrowser(c_name)
{
	if (document.cookie.length>0)
	{
		c_start=document.cookie.indexOf(c_name + "=");
		if (c_start!=-1)
		{
			c_start=c_start + c_name.length+1;
			c_end=document.cookie.indexOf(";",c_start);
			if (c_end==-1) c_end=document.cookie.length;
			return unescape(document.cookie.substring(c_start,c_end));
		}
	}
	return "";
}

function setBadBrowser(c_name,value,expiredays)
{
	var exdate=new Date();
	exdate.setDate(exdate.getDate()+expiredays);
	document.cookie=c_name+ "=" +escape(value) + ((expiredays==null) ? "" : ";expires="+exdate.toGMTString());
}

if(badBrowser() && getBadBrowser('browserWarning') != 'seen' ){
	$(function(){
		$("<div id='browserWarning'>You are using an unsupported browser. Please switch to <a href='https://www.google.com/intl/en/chrome/browser/' title='Download Chrome'>Chrome</a>, <a href='http://getfirefox.com' title='Download Firefox'>FireFox</a>, <a href='http://www.opera.com/download/' title='Download Opera'>Opera</a>, or <a href='http://windows.microsoft.com/en-CA/internet-explorer/downloads/ie-9/worldwide-languages' title='Download Internet Explorer'>Internet Explorer</a>. Thanks!&nbsp;&nbsp;&nbsp;[<a href='#' id='warningClose'>close</a>]</div> ")
		.css({
			backgroundColor: '#fcfdde',
			'width': '100%',
			'border-top': 'solid 1px #000',
			'border-bottom': 'solid 1px #000',
			'text-align': 'center',
			padding:'20px 0px 20px 0px'
		})
		.prependTo("body");

		$('#warningClose').click(function(){
			setBadBrowser('browserWarning','seen');
			$('#browserWarning').slideUp('slow');
			return false;
		});
	});
}
