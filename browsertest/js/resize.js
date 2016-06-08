//
//

//
// Pic-Upload Script v3.0 by kill0rz
//

//
//

!function(a){"use strict";var b=a.HTMLCanvasElement&&a.HTMLCanvasElement.prototype,c=a.Blob&&function(){try{return Boolean(new Blob)}catch(a){return!1}}(),d=c&&a.Uint8Array&&function(){try{return 100===new Blob([new Uint8Array(100)]).size}catch(a){return!1}}(),e=a.BlobBuilder||a.WebKitBlobBuilder||a.MozBlobBuilder||a.MSBlobBuilder,f=(c||e)&&a.atob&&a.ArrayBuffer&&a.Uint8Array&&function(a){var b,f,g,h,i,j;for(b=a.split(",")[0].indexOf("base64")>=0?atob(a.split(",")[1]):decodeURIComponent(a.split(",")[1]),f=new ArrayBuffer(b.length),g=new Uint8Array(f),h=0;h<b.length;h+=1)g[h]=b.charCodeAt(h);return i=a.split(",")[0].split(":")[1].split(";")[0],c?new Blob([d?g:f],{type:i}):(j=new e,j.append(f),j.getBlob(i))};a.HTMLCanvasElement&&!b.toBlob&&(b.mozGetAsFile?b.toBlob=function(a,c,d){d&&b.toDataURL&&f?a(f(this.toDataURL(c,d))):a(this.mozGetAsFile("blob",c))}:b.toDataURL&&f&&(b.toBlob=function(a,b,c){a(f(this.toDataURL(b,c)))})),"function"==typeof define&&define.amd?define(function(){return f}):a.dataURLtoBlob=f}(this);
window.resize = (function() {
	'use strict';

	function Resize() {}
	Resize.prototype = {
		init: function(outputQuality) {
			this.outputQuality = (outputQuality === 'undefined' ? 0.8 : outputQuality);
		},
		photo: function(file, maxSize, outputType, callback, files_length, i_names) {
			var _this = this;
			var reader = new FileReader();
			reader.onload = function(readerEvent, files_length) {
				_this.resize(readerEvent.target.result, maxSize, outputType, callback, i_names);
			}
			reader.readAsDataURL(file);
		},
		resize: function(dataURL, maxSize, outputType, callback, i_names) {
			var _this = this;
			var image = new Image();
			image.onload = function(imageEvent) {
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
			}
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
document.addEventListener('DOMContentLoaded', function(event) {
	'use strict';
	// Initialise resize library
	var resize = new window.resize();
	resize.init();
	// Upload photo
	var upload = function(photo, callback, files, filename) {
		var formData = new FormData();

		// wenn feld unten dann das sonst

		formData.append('ordner', ordner);
		formData.append('filename', filename);
		formData.append('photo', photo);
		var request = new XMLHttpRequest();
		request.onreadystatechange = function() {
			if (request.readyState === 4) {
				callback(request.response);
			}
		}
		request.open('POST', './process.php');
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
	if (typeof(element) != 'undefined' && element != null) {
		document.querySelector('form#resizeimgbeforeupload input[type=file]').addEventListener('change', function(event) {
			event.preventDefault();
			var files = event.target.files;
			files_length = files.length;
			files_input = $('#file_upload').prop("files");
			names = $.map(files, function(val) {
				return val.name;
			});

			for (var i in files) {
				if (typeof files[i] !== 'object') return false;
				i_names++;
				(function() {
					if (document.getElementById('resizeimgbeforeupload_status').innerHTML != "<img alt=\"ok\" src=\"./images/sanduhr.gif\">") {
						document.getElementById('resizeimgbeforeupload_status').innerHTML = "<img alt=\"ok\" src=\"./images/sanduhr.gif\">";
					}
					var initialSize = files[i].size;
					resize.photo(files[i], 1300, 'file', function(resizedFile, file, i_names) {
						var resizedSize = resizedFile.size;

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
										if (element == "") array.splice(index, 1);
									});
									textarea.value = array.sort().join("\n");
								}

								//abschluss, freigeben
								document.getElementById('resizeimgbeforeupload_status').innerHTML = "<img alt='ok' src='./images/erledigt.gif' />";
								i_curr = 0;
								i_names = 0;
								document.getElementById("file_upload").value = "";
							}
						}, files_length, names[i_names - 1]);
					}, files_length, i_names);
				}());
			}
		});
	}
});
