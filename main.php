<?php
require 'common.php';
/**
 * @var Deal[] $deal
 * @var float $cash
 * @var float $stock
 */

$total = $cash;
display_deals($deals, $cash, $stock);
display_predict($total, $cash, $stock);

/**
 * @param Deal[] $deals
 * @param float $cash
 * @param float $stock
 */
function display_deals($deals, &$cash, &$stock){

	echo '-----------------------------------------------------------------', PHP_EOL;
	printf(' %4s | %5s|%9s|  %5s |  %4s  |  %3s | %6s | %4s'. PHP_EOL,
		'Date', 'Price', 'Cash chg.', 'Stock', 'Cash', 'Fee', 'Profit', 'Avg.');
	echo '-----------------------------------------------------------------', PHP_EOL;

	$total = $cash;
	foreach ($deals as $deal) {
		$deal->run($cash, $stock);
		$profit = ($cash + $stock * $deal->price) - $total;
		printf('%5s |%6s|%9s|%8s|%8s|%6s|%8s|%6s'. PHP_EOL,
			substr($deal->date, 3),
			sprintf('%1.4f', $deal->price),
			sprintf('%+5.2f', $deal->getCashChange()),
			sprintf('%5.2f', $stock),
			sprintf('%5.2f', $cash),
			//sprintf('%5.2f', $stock * $deal->price),
			sprintf('%3.2f', $deal->getFee()),
			sprintf('%+4.2f', $profit),
			sprintf('%1.4f', ($total - $cash) / $stock)
		);
	}

	echo '-----------------------------------------------------------------', PHP_EOL;
}

function display_predict($total, $cash, $stock){
	$info = Deal::predict($total, $cash, $stock);
	if ($info === null) {
		echo 'No stock in hand.', PHP_EOL;
		return;
	}

	foreach ($info as $level => $price) {
		printf('  %3s%% -- %1.4f'. PHP_EOL, sprintf('%+2d', $level), $price);
	}

}