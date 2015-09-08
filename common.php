<?php
require_once 'Deal.php';
require 'migrate.php';
$cash = 40000;
$stock = 0;

$deals = Deal::loadAll();