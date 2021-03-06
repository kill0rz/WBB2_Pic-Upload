//
//

//
// Pic-Upload Script v3.0 by kill0rz
//

//
//

'use strict';

function open_picupload(threadname) {
	var areaOption = document.getElementById("thread_title");
	var title;
	if (threadname.trim() !== '') {
		title = threadname.trim();
	} else if (areaOption) {
		title = document.getElementById("thread_title").value;
	} else {
		title = '';
	}
	title = btoa(title);
	var d = new Date();
	window.location = 'picupload.php?title=' + encodeURI(title), 'picupload' + d.getTime();
}

function addreplaytothread(inhalt, threadid) {
	//autopost
	window.location = './addreply.php?threadid=' + threadid + '&inhalt=' + inhalt + '&autosubmit=true';
}

function submittonewthread(inhalt, threadname) {
	//autopost
	var request = new XMLHttpRequest();
	var formData = new FormData();
	request.onreadystatechange = function() {
		if (request.readyState === 4) {
			window.location = './newthread.php?boardid=' + request.response.boardid + '&inhalt=' + inhalt + '&title=' + threadname + '&autosubmit=true';
		}
	};
	request.open('GET', './picupload_process.php?action=getboardid');
	request.responseType = 'json';
	request.send(formData);
}

function do_form_submit_newthread(title, inhalt, autosubmit) {
	document.getElementById("tbmessage").value = inhalt.trim();
	if (title.trim() !== "") document.getElementById("thread_title").value = title.trim();
	if (autosubmit == 1) document.forms["bbform"].submit();
}

var submitted = false;
var userinput = false;
$(document).ready(function() {
	$("form").submit(function() {
		submitted = true;
	});
	$("#tbmessage").change(function() {
		userinput = true;
	});
	window.onbeforeunload = function() {
		if (userinput && !submitted) {
			return 'Sie haben das Formular noch nicht abgesendet.\nMöchten Sie die Seite wirklich verlassen?';
		}
	};
});

function fileChange(fileid) {
	var fileList = document.getElementById(fileid).files;
	var file = fileList[0];
	if (!file) return;
	document.getElementById("progress-" + fileid).value = 0;
	document.getElementById("prozent-" + fileid).innerHTML = "0%";
	document.getElementById("progress-" + fileid).style.visibility = 'visible';
	document.getElementById("prozent-" + fileid).style.visibility = 'visible';
}

var client = null;

function call_all_uploads() {
	uploadFile('file1');
	uploadFile('file2');
	uploadFile('file3');
	uploadFile('file4');
	uploadFile('file5');
	uploadFile('filezip');

	//deactivate all input fields
	$('form[name="uploadform"]').find('input[type="text"], button, select').prop("disabled", true);
}

function uploadFile(fileid) {
	var file = document.getElementById(fileid).files[0];
	var formData = new FormData();
	client = new XMLHttpRequest();
	var prog = document.getElementById("progress-" + fileid);

	if (!file) return;
	prog.value = 0;
	prog.max = 100;

	formData.append("datei", file);

	client.onerror = function(e) {
		alert("onError");
	};

	client.onload = function(e) {
		document.getElementById("prozent-" + fileid).innerHTML = "100%";
		prog.value = prog.max;
	};

	client.upload.onprogress = function(e) {
		var p = Math.round(100 / e.total * e.loaded);
		document.getElementById("progress-" + fileid).value = p;
		document.getElementById("prozent-" + fileid).innerHTML = p + "%";
	};

	// client.onabort = function(e) {
	// 	alert("Upload abgebrochen");
	// };

	client.open("POST", "picupload.php");
	client.send(formData);
}

function uploadAbort() {
	if (client instanceof XMLHttpRequest) client.abort();
}

var toggle = 1;

function changedivs() {
	if (toggle == 1) {
		$('#changedivs').html("&darr; Bilder ausw&auml;hlen");
		$('#ordner').css('border-color', 'black');
		$('#links').css('border-color', 'red');
		$('#ordner').find('input, textarea, button, select').prop("disabled", true);
		$('#links').find('input, textarea, button, select').prop("disabled", false);
		toggle = 2;
	} else {
		$('#changedivs').html("&uarr; Ordner ausw&auml;hlen");
		$('#ordner').find('input, textarea, button, select').prop("disabled", false);
		$('#links').find('input, textarea, button, select').prop("disabled", true);
		$('#ordner').css('border-color', 'red');
		$('#links').css('border-color', 'black');
		toggle = 1;
	}
}

function appandDateToFoldername(day) {
	Date.prototype.ddmmyyyy = function() {
		var yyyy = this.getFullYear().toString();
		var mm = (this.getMonth() + 1).toString(); // getMonth() is zero-based
		var dd = this.getDate().toString();
		return (dd[1] ? dd : "0" + dd[0]) + "." + (mm[1] ? mm : "0" + mm[0]) + "." + yyyy; // padding
	};

	var date = new Date();
	var formattedDate;
	switch (day) {
		case "today":
			formattedDate = date.ddmmyyyy();
			break;
		case "yesterday":
			date.setDate(date.getDate() - 1);
			formattedDate = date.ddmmyyyy();
			break;
	}

	$('#ordner_name_new').val($('#ordner_name_new').val().trim() + " " + formattedDate);
	$('#ordner_name_new').focus();

	return;
}