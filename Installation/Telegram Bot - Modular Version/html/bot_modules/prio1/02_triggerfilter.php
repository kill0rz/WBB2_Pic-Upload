<?php

// Trigger-Filter
foreach ($triggerstricker as $triggerword => $triggerstrickerid) {
	if (preg_replace($triggerword, "", strtolower($update["message"]["text"])) != strtolower($update["message"]["text"])) {
		send_sticker($triggerstrickerid);
		exit();
	}
}