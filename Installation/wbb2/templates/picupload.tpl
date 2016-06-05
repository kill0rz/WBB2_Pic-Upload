<?xml version="1.0" encoding="{$lang->items['LANG_GLOBAL_ENCODING']}"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="{$lang->items['LANG_GLOBAL_DIRECTION']}" lang="{$lang->items['LANG_GLOBAL_LANGCODE']}" xml:lang="{$lang->items['LANG_GLOBAL_LANGCODE']}">

<head>
	<title>$master_board_name | Pic-Upload v3</title>
	$headinclude
</head>

<body>
	$header
	<table cellpadding="{$style['tableincellpadding']}" cellspacing="{$style['tableincellspacing']}" border="{$style['tableinborder']}" style="width:{$style['tableinwidth']}" class="tableinborder">
		<tr>
			<td class="tablea">
				<table cellpadding="0" cellspacing="0" border="0" style="width:100%">
					<tr class="tablea_fc">
						<td align="left">
							<span class="smallfont"><b><a href="index.php{$SID_ARG_1ST}">$master_board_name</a> &raquo; Pic-Upload v3</b>
							</span>
						</td>
						<td align="right">
							<span class="smallfont">
								<b>$usercbar</b>
							</span>
						</td>
					</tr>
				</table>
			</td>
		</tr>
		<br />
		<tr>
			<td align="left">
				<table cellpadding="4" cellspacing="1" border="0" style="width:100%" class="tableinborder">
					<tr>
						<td align="left" colspan="4" nowrap="nowrap" class="tabletitle">
							<span class="normalfont"><b>Optionen</b></span>
						</td>
					</tr>
					<tr align="left">
						<td colspan="2" class="tablea" align="center">
							<span class="smallfont">
								<pre><b>neues Uploadformular</b> | <a href="./picupload.php?formular=old">altes Uploadformular</a></pre>
								<h3>Hier hochgeladene Dateien werden <b>vor</b> dem Upload verkleinert und sofort nach dem Auswählen abgearbeitet!</h3>
								<div style="border: 1px solid red; padding: 5px; clear: both;" id="ordner">
									In den Ordner:
									<br />
									<select name="ordner" size="1" id="ordner_name_old">
										$options
									</select>
									<br /> oder neuer Ordner:
									<br />
									<input type="text" name="newdir" size="30" value="$ordner_anz" id="ordner_name_new" />
									<br />
								</div>
								<br />
								<button onclick="changedivs();" id="changedivs" type="button">&uarr; Ordner ausw&auml;hlen</button>
								<br />
								<br />
								<div style="border: 1px solid black; padding: 5px; clear: both;" id="links">
									<br />Es sind nur Bilder folgender Formate erlaubt: jpg, jpeg, png, gif
									<br />
									<form id="resizeimgbeforeupload">
										<input type="file" accept="image/jpeg, image/gif, image/x-png" id="file_upload" multiple disabled/>
										<span id="resizeimgbeforeupload_status"></span>
										<span id="resizeimgbeforeupload_ladebalken">
										</span>
									</form>
									<input type="checkbox" id="sortlinks" disabled checked/> Linkliste sortieren?
								</div>
								<br />
								<div style="border: 1px solid black; padding: 5px; clear: both;">
									<textarea rows=10 cols=150 id="linksammlung"></textarea>
								</div>
								<div id="autopostbutton"></div>
								<br />
								<hr/>
								<br /> Deine Ordner:
								<br />
								<table border='none'>
									<tr>
										<th>Ordner</th>
										<th>zum Randompic der Woche freigegeben?</th>
									</tr>
									$folders
								</table>
								$folders_hinweis
								<br /> $unterelinks
								<br />
								<br /> $vorschauen
								<hr/>
								<table border='none'>
									<tr>
										<th>Nutzer</th>
										<th>hochgeladene Bilder</th>
										<th>Anteil hochgeladener Bilder</th>
										<th>freigegebene Bilder</th>
										<th>Anteil freigegebener Bilder</th>
									</tr>
									$statsinhalt
								</table>
							</span>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
$footer
