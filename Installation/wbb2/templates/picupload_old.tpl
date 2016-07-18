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
							<td align="left"><span class="smallfont"><b><a href="index.php{$SID_ARG_1ST}">$master_board_name</a> &raquo; Pic-Upload v3</b></span></td>
							<td align="right"><span class="smallfont"><b>$usercbar</b></span></td>
						</tr>
					</table>
				</td>
			</tr>
			<br />
			<tr>
				<td align="left">
					<table cellpadding="4" cellspacing="1" border="0" style="width:100%" class="tableinborder">
						<tr>
							<td align="left" colspan="4" nowrap="nowrap" class="tabletitle"><span class="normalfont"><b>Optionen</b></span></td>
						</tr>
						<tr align="left">
							<td colspan="2" class="tablea" align="center"><span class="smallfont">
							<span id="inhalt"></span>
							<pre><a href="./picupload.php">neues Uploadformular</a> | <b>altes Uploadformular</b></pre>
								<if($error !='' )>
									<then>Fehler: $error</then>
									</if>
									$ausgabe
									<br /> $dowithbutton
									<form action="./picupload.php?formular=old" method="post" enctype="multipart/form-data" name="uploadform">
										<br />Es sind nur Bilder folgender Formate erlaubt: jpg, jpeg, png, gif
										<br />
										<input type="file" name="file1" id="file1" onchange="fileChange('file1');" size="30" accept="image/jpeg,image/gif,image/x-png" />
										<progress style='visibility:hidden;' id="progress-file1" style="margin-top:10px"></progress> <span style='visibility:hidden;' id="prozent-file1"></span>
										<br />
										<br />
										<input type="file" name="file2" id="file2" onchange="fileChange('file2');" size="30" accept="image/jpeg,image/gif,image/x-png" />
										<progress style='visibility:hidden;' id="progress-file2" style="margin-top:10px"></progress> <span style='visibility:hidden;' id="prozent-file2"></span>
										<br />
										<br />
										<input type="file" name="file3" id="file3" onchange="fileChange('file3');" size="30" accept="image/jpeg,image/gif,image/x-png" />
										<progress style='visibility:hidden;' id="progress-file3" style="margin-top:10px"></progress> <span style='visibility:hidden;' id="prozent-file3"></span>
										<br />
										<br />
										<input type="file" name="file4" id="file4" onchange="fileChange('file4');" size="30" accept="image/jpeg,image/gif,image/x-png" />
										<progress style='visibility:hidden;' id="progress-file4" style="margin-top:10px"></progress> <span style='visibility:hidden;' id="prozent-file4"></span>
										<br />
										<br />
										<input type="file" name="file5" id="file5" onchange="fileChange('file5');" size="30" accept="image/jpeg,image/gif,image/x-png" />
										<progress style='visibility:hidden;' id="progress-file5" style="margin-top:10px"></progress> <span style='visibility:hidden;' id="prozent-file5"></span>
										<br />
										<br /> Oder per Remote aus dem Netz:
										<br />
										<input type="text" name="url1" size="30" placeholder="http://example.com/pic.jpg" />
										<br />
										<input type="text" name="url2" size="30" placeholder="http://example.com/pic.jpg" />
										<br />
										<input type="text" name="url3" size="30" placeholder="http://example.com/pic.jpg" />
										<br />
										<input type="text" name="url4" size="30" placeholder="http://example.com/pic.jpg" />
										<br />
										<input type="text" name="url5" size="30" placeholder="http://example.com/pic.jpg" />
										<br /> Oder alle Bilder in einer ZIP-Datei:
										<br />
										<input type="file" name="filezip" id="filezip" onchange="fileChange('filezip');" size="30" accept="application/zip" />
										<progress style='visibility:hidden;' id="progress-filezip" style="margin-top:10px"></progress> <span style='visibility:hidden;' id="prozent-filezip"></span>
										<br />
										<br /> In den Ordner:
										<br />
										<select name="ordner" size="1">
											$options
										</select>
										<br /> oder neuer Ordner:
										<br />
										<input type="text" name="newdir" size="30" value="$ordner_anz" />
										<input type="hidden" name="sent" value="1" />
										<input type="hidden" name="ordner_orig" value="$ordner_orig" />
										<input type="hidden" name="links" value="$ausgabelinks" />
										<br />
										<input type="checkbox" name="compress" value="true" checked /> Bilder komprimieren? |
										<input type="checkbox" name="sort" value="true" checked /> Links sortieren? |
										<input type="checkbox" name="allowrandompic" value="true" checked/> Zum Zufallsbild der Woche freigeben?
										<br />
										<input type="submit" name="submit" value="Upload" onclick="call_all_uploads();" />
										<input name="abort" value="Abbrechen" type="button" onclick="uploadAbort();" />
										<br />
									</form>
									<br /> <h4>Deine Ordner:</h4>
									<br />
									$folders
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
											<if($use_randompic)>
												<then>
													<th>freigegebene Bilder</th>
													<th>Anteil freigegebener Bilder</th>
												</then>
											</if>
										</tr>
										$statsinhalt
									</table>
								</span>
							</td>
						</tr>
					</tr>
				</td>
			</tr>
		</table>
	</tr>
</table>
$footer
