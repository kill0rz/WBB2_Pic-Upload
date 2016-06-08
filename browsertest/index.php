<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<title>Browsertest Pic-Upload</title>
	<script src="./js/jquery.js"></script>
	<script src="./js/resize.js"></script>
</head>
<body>
	<div style="border: 1px solid black; padding: 5px; clear: both;" id="links">
		<br />Es sind nur Bilder folgender Formate erlaubt: jpg, jpeg, png, gif
		<br />
		<form id="resizeimgbeforeupload">
			<input type="file" accept="image/jpeg, image/gif, image/x-png" id="file_upload" multiple/>
			<span id="resizeimgbeforeupload_status"></span>
			<span id="resizeimgbeforeupload_ladebalken">
			</span>
		</form>
	</div>
	<br />
	<div style="border: 1px solid black; padding: 5px; clear: both;">
		<textarea rows=10 cols=150 id="linksammlung"></textarea>
	</div>
	<div id="autopostbutton"></div>

</body>
</html>