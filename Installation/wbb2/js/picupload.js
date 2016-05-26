function open_picupload(threadname) {
	var areaOption = document.getElementById("thread_title");
	if (threadname.trim() !== '') {
		var title = threadname.trim();
	} else if (areaOption) {
		var title = document.getElementById("thread_title").value;
	} else {
		var title = '';
	}
	var d = new Date();
	window.open('picupload.php?title=' + title, 'picupload' + d.getTime());
}

function addreplaytothread(inhalt, threadid) {
	window.location = './addreply.php?threadid=' + threadid + '&inhalt=' + inhalt + '&autosubmit=true';
}

function submittonewthread(inhalt, threadname) {
	window.location = './newthread.php?boardid=1&inhalt=' + inhalt + '&title=' + threadname + '&autosubmit=true';
}

function do_form_submit_newthread(title, inhalt, autosubmit) {
	document.getElementById("tbmessage").value = inhalt.trim();
	if (title.trim() != '') document.getElementById("thread_title").value = title.trim();
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
	}
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

	client.onabort = function(e) {
		alert("Upload abgebrochen");
	};

	client.open("POST", "picupload.php");
	client.send(formData);
}

function uploadAbort() {
	if (client instanceof XMLHttpRequest) client.abort();
}
