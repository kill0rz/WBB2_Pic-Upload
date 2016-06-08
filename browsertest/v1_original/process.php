<?php

$response = (object) [
	'status' => "true",
	'links' => "http://example.com/this/is/an/url/to/a/pic/pic.jpg\n",
];

echo json_encode($response);