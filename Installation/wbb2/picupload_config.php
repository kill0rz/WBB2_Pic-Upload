<?php

//
//

//
// Pic-Upload Script v3.1 by kill0rz
//

//
//

// Konfiguration Anfang

// Wo sollen die Bilder gespeichert werden?
$subordner = "Fotoalben"; //ohne / am Ende

// IDs der Benutzergruppen, die auf den Hack zugreifen dürfen
$erlaubtegruppen = array("1", "2", "3");

// ID des Boards, in das alle Bilder gepostet werden sollen. 0 zum Deaktivieren
$fotoalben_board_id = 1;
// ID des Boards, in das alle versteckten Bilder gepostet werden sollen. 0 zum Deaktivieren
$fotoalben_hidden_board_id = 2;

// Wenn du die XY Megashoutbox 1.3 installiert hast, kannst du hier die Freigabe zum Zufallsbild an und ausschalten.
$use_randompic = true;

// Konfiguration Ende