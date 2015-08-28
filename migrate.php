<?php
require 'Deal.php';

file_put_contents(__DIR__. '/data.bin', '');

Deal::build()->on('15-07-10')->buy(20073.35)->at(1.3152)->save();
Deal::build()->on('15-07-31')->buy(10000.00)->at(1.2203)->save();
Deal::build()->on('15-08-07')->sell(7000)->at(1.2478)->save();
Deal::build()->on('15-08-10')->sell(8107.43)->at(1.2981)->save();
Deal::build()->on('15-08-18')->buy(10000.00)->at(1.2197)->save();
Deal::build()->on('15-08-21')->buy(10008.80)->at(1.1475)->save();

