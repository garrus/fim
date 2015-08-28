<?php
if (php_sapi_name() == 'cli') {
	require 'main.php';
} else {
	require 'web.php';
}
