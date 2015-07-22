$(window).load(function(){
	$('#press-application input[name=previous_pass]').bind("change keyup", function() {
		if ($('input[name=previous_pass]:checked', '#press-application').val() === "Yes") {
			$("#previous_pass_link").attr('required', '');
		}
		else {
			$("#previous_pass_link").removeAttr('required');
		}
	});
	$('#press-application input[name=media_type]').bind("change keyup", function() {
		if ($('input[name=media_type]:checked', '#press-application').val() === "Other") {
			$("#other").attr('required', '');
		}
		else {
			$("#other").removeAttr('required');
		}
	});

	/*
	if (navigator.appVersion.indexOf("Mac")!=-1) {
		console.log('Mac detected, applying class...');
    	$('#toptoolbarlinklist ul').addClass('safari-mac');
	} else {
   		console.log('PC detected, applying class...');
		$('body').addClass('pc');
	}
	*/
});

function Redirect(_newLocation) {
	window.location.href = _newLocation;
};

function AddProfileEmail() {
	$('#profileemailcontainer').append('<div class="useremailitem"><div class="useremailitemdelete" onclick="javascript: $(this).parent().remove();">&nbsp;</div><input name="newemaillist[]" type="text" size="20" class="swifttextlarge" /></div>');
};

function AddTicketFile() {
	$('#ticketattachmentcontainer').append('<div class="ticketattachmentitem"><div class="ticketattachmentitemdelete" onclick="javascript: $(this).parent().remove();">&nbsp;</div><input name="ticketattachments[]" type="file" size="20" class="swifttextlarge" /></div>');
};

function AddFolder() {
	$('#addBtn').show();
	$('#foldercontainer').append('<input name="folder[]" type="text" size="20" style="WIDTH: 120px; BACKGROUND: #FFFFFF URL(../images/inputtextbg.gif) REPEAT-X TOP LEFT; COLOR: #000000; BORDER: 1px SOLID #cdc2ab; PADDING: 2px 2px 2px 2px; MARGIN: 0px; vertical-align: middle;" />');
};

function AddPolicy() {
	$('#policycontainer').append('<tr id="newpolicy"><td align="left" valign="middle" class="zebraodd">Question:</td><td><textarea name="new_question[]" cols="25" rows="5" id="new_question" class="swifttextareawide"></textarea></td></tr><tr><td align="left" valign="middle" class="zebraodd">Response:</td><td><textarea name="new_response[]" cols="25" rows="5" id="new_response" class="swifttextareawide"></textarea></td></tr><tr><td align="left" valign="middle" class="zebraodd">Clipboard:</td><td><select class="swiftselect" name="new_clipboard[]"><option value="">&nbsp;</option><option value="1">No</option><option value="2">Yes</option></select></td></tr>');
};

function PopupSmallWindow(url) {
	screen_width = screen.width;
	screen_height = screen.height;
	widthm = (screen_width-400)/2;
	heightm = (screen_height-300)/2;
	window.open(url,"infowindow"+GetRandom(), "toolbar=0,location=0,directories=0,status=0,menubar=0,scrollbars=1,resizable=1,width=400,height=300,left="+widthm+",top="+heightm);
};

function GetRandom()
{
	var num;
	now=new Date();
	num=(now.getSeconds());
	num=num+1;
	return num;
};

function setGetParameter(paramName, paramValue)
{
	var url = window.location.href;
	if (url.indexOf(paramName + "=") >= 0)
	{
		var prefix = url.substring(0, url.indexOf(paramName));
		var suffix = url.substring(url.indexOf(paramName)).substring(url.indexOf("=") + 1);
		suffix = (suffix.indexOf("&") >= 0) ? suffix.substring(suffix.indexOf("&")) : "";
		url = prefix + paramName + "=" + paramValue + suffix;
	}
	else
	{
		if (url.indexOf("?") < 0)
			url += "?" + paramName + "=" + paramValue;
		else
			url += "&" + paramName + "=" + paramValue;
	}
	window.location.href = url;
}

$(document).ready(function(){
	$('select').change(function (){
		$(this).closest('form[name="delete-email"]').submit();
		$(this).closest('form[name="delete-press-app"]').submit();
		$(this).closest('form[name="delete-volunteer-app"]').submit();
		$(this).closest('form[name="delete-topic"]').submit();
		$(this).closest('form[name="subscribe-topic"]').submit();
	});
});