//
//

//
// Pic-Upload Script v3.1 by kill0rz
//

//
//

'use strict';
window.resize = (function() {
	function Resize() {}
	Resize.prototype = {
		init: function(outputQuality) {
			this.outputQuality = (outputQuality === 'undefined' ? 0.8 : outputQuality);
		},
		photo: function(file, maxSize, outputType, callback, files_length, i_names) {
			var _this = this;
			var reader = new FileReader();
			reader.onload = function(readerEvent) {
				_this.resize(readerEvent.target.result, maxSize, outputType, callback, i_names);
			};
			reader.readAsDataURL(file);
		},
		resize: function(dataURL, maxSize, outputType, callback, i_names) {
			var _this = this;
			var image = new Image();
			image.onload = function() {
				// Resize image
				var canvas = document.createElement('canvas'),
					width = image.width,
					height = image.height;
				if (width > height) {
					if (width > maxSize) {
						height *= maxSize / width;
						width = maxSize;
					}
				} else {
					if (height > maxSize) {
						width *= maxSize / height;
						height = maxSize;
					}
				}
				canvas.width = width;
				canvas.height = height;
				canvas.getContext('2d').drawImage(image, 0, 0, width, height);
				_this.output(canvas, outputType, callback, i_names);
			};
			image.src = dataURL;
		},
		output: function(canvas, outputType, callback, i_names) {
			switch (outputType) {
				case 'file':
					canvas.toBlob(function(blob) {
						callback(blob, null, i_names);
					}, 'image/jpeg', 0.8);
					break;
				case 'dataURL':
					callback(canvas.toDataURL('image/jpeg', 0.8), null, i_names);
					break;
			}
		}
	};
	return Resize;
}());

var ordner = "default";
var table_append = true;
document.addEventListener('DOMContentLoaded', function() {
	// Initialise resize library
	var resize = new window.resize();
	resize.init();
	// Upload photo
	var upload = function(photo, callback, files, filename) {

		var formData = new FormData();

		// wenn Freifeld unten gesetzt, dann das sonst Dropdown
		if ($('#ordner_name_new').val().trim() !== "") {
			ordner = $('#ordner_name_new').val().trim();
		} else {
			$('#ordner_name_old option:selected').each(function() {
				ordner = this.value.replace("<option>", "");
			});
		}

		//check if folder already exists on disk
		var request5 = new XMLHttpRequest();
		var formData5 = new FormData();
		request5.onreadystatechange = function() {
			if (request5.readyState === 4 && request5.response.status == 1) {
				table_append = false;
			}
		};
		request5.open('GET', './picupload_process.php?action=checkexistingfolder&folder=' + btoa(ordner));
		request5.responseType = 'json';
		request5.send(formData5);

		formData.append('ordner', ordner);
		formData.append('filename', filename);
		formData.append('photo', photo);
		formData.append('overwrite', $('#overwrite').is(':checked'));
		var request = new XMLHttpRequest();
		request.onreadystatechange = function() {
			if (request.readyState === 4) {
				callback(request.response);
			}
		};
		request.open('POST', './picupload_process.php');
		request.responseType = 'json';
		request.send(formData);
	};

	var files_length;
	var i_curr = 0;
	var i_names = 0;
	var files_input;
	var names;
	var inhalt;

	var element = document.querySelector('form#resizeimgbeforeupload input[type=file]');
	if (typeof(element) != 'undefined' && element !== null) {
		document.querySelector('form#resizeimgbeforeupload input[type=file]').addEventListener('change', function(event) {
			event.preventDefault();
			var files = event.target.files;
			files_length = files.length;
			files_input = $('#file_upload').prop("files");
			names = $.map(files, function(val) {
				return val.name;
			});

			for (var i in files) {
				// roten Rand weiterschieben
				$('#links').css('border-color', 'black');
				$('#linksammlungcontainer').css('border-color', 'red');

				if (typeof files[i] !== 'object') return false;
				i_names++;
				(function() {
					if (document.getElementById('resizeimgbeforeupload_status').innerHTML != "<img alt=\"ok\" src=\"./images/sanduhr.gif\">") {
						document.getElementById('resizeimgbeforeupload_status').innerHTML = "<img alt=\"ok\" src=\"./images/sanduhr.gif\">";
					}
					resize.photo(files[i], 1300, 'file', function(resizedFile, file, i_names) {
						$('#links').find('input, textarea, button, select').prop("disabled", true);
						$('#linksammlung').prop("disabled", true);
						$('#changedivs').prop("disabled", true);

						upload(resizedFile, function(response) {
							i_curr++;
							var oldlinks = $('#linksammlung').val();
							$('#linksammlung').val(oldlinks + response.links);
							document.getElementById('resizeimgbeforeupload_ladebalken').innerHTML = '<progress style="visibility: visible;" id="progress-file1" value="' + i_curr / files.length + '"></progress>';
							if (i_curr == files.length) {
								//alle Dateien fertig

								//sortieren
								var textarea = document.getElementById("linksammlung");
								if ($('#sortlinks').is(':checked')) {
									textarea.toString().replace(/^\s*\n/gm, "");
									var array = textarea.value.split("\n");
									array.forEach(function(element, index, array) {
										if (element === "") array.splice(index, 1);
									});
									textarea.value = array.sort().join("\n");
								}

								//autopost
								var request2 = new XMLHttpRequest();
								var formData2 = new FormData();
								request2.onreadystatechange = function() {
									if (request2.readyState === 4 && request2.response.boardid > 0) {
										// Ordner freigeben?
										if ($('#allowrandompic').is(':checked')) {
											var request3 = new XMLHttpRequest();
											var formData3 = new FormData();
											request3.onreadystatechange = function() {
												if (request3.readyState === 4 && request3.response.boardid > 0) {
													var freigabeicon;
													if (request3.response.status == "1") {
														//ok, freigabe erfolgt
														freigabeicon = "<img src='./images/erledigt.gif' alt='erledigt' />";
													} else {
														freigabeicon = "<img src='./images/delete.png' alt='delete' />";
													}

													if (request2.response.action == "addreplaytothread") {
														inhalt = "<form action='./addreply.php' method='post'>";
														inhalt += "<input type='hidden' name='threadid' value='" + request2.response.usenumber + "' />";
														inhalt += "<input type='hidden' name='inhalt' value='" + btoa(textarea.value) + "' />";
														inhalt += "<input type='hidden' name='autosubmit' value='true' />";
														inhalt += "<input type='submit' id='addreplaytothread' value=\"Antworte auf Thread '" + request2.response.usetopic + "'\" />";
														inhalt += "</form>";
														document.getElementById('autopostbutton').innerHTML = inhalt;
													} else {
														inhalt = "<form action='./newthread.php?boardid=" + request2.response.boardid + "' method='post'>";
														inhalt += "<input type='hidden' name='inhalt' value='" + btoa(textarea.value) + "' />";
														inhalt += "<input type='hidden' name='title' value='" + btoa(ordner) + "' />";
														inhalt += "<input type='hidden' name='autosubmit' value='true' />";
														inhalt += "<input type='submit' id='submittonewthread' value=\"Er&ouml;ffne neuen Thread '" + ordner + "'\" />";
														inhalt += "</form>";
														document.getElementById('autopostbutton').innerHTML = inhalt;
													}

													if (table_append && request2.response.ordner_shrink !== "") {
														// Ordneruebersicht aktualisieren
														$("#ordneruebersicht").append(
															"<tr>" +
															"<td>" +
															"<a href='./picupload.php?folder=" + request2.response.ordner_shrink + "&formular=#inhalt'>" +
															request2.response.ordner_shrink +
															"</a>" +
															"</td>" +
															"<td align='center'>" +
															"<a href='./picupload.php?folder=" + encodeURI(ordner) + "&amp;action=togglefreigabe&amp;formular='>" +
															//used out of scope
															freigabeicon +
															"</a>" +
															"</td>" +
															"</tr>"
														);
													}
												}
											};
											request3.open('GET', './picupload_process.php?action=setallowrandompic&folder=' + btoa(ordner));
											request3.responseType = 'json';
											request3.send(formData3);
										}
									}
								};
								request2.open('GET', './picupload_process.php?action=autopost&folder=' + btoa(ordner));
								request2.responseType = 'json';
								request2.send(formData2);

								//abschluss, freigeben
								document.getElementById('resizeimgbeforeupload_status').innerHTML = "<img alt='ok' src='./images/erledigt.gif' />";
								i_curr = 0;
								i_names = 0;
								document.getElementById("file_upload").value = "";
								// $('#links').find('input, textarea, button, select').prop("disabled", false);
								$('#linksammlung').prop("disabled", false);
								// $('#changedivs').prop("disabled", false);
							}
						}, files_length, names[i_names - 1]);
					}, files_length, i_names);
				}());
			}
		});
	}
});

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
