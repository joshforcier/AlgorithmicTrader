<?php

require './data.php';
require './autoload.php';
use Alpaca\Alpaca;
// Initialize the Alpaca class with your Alpaca Key and Secret.
// You can also set the third parameter to enable or disable paper trading.
// The default is true to enable calling against the paper trading endpoint.
$alpaca = new Alpaca($PAPERKEYID, $PAPERSECRETKEY, true);
$positions = $alpaca->getPositions()->getResponse();

$sellQty = 0;
if (!empty($positions)) {
    foreach ($positions as $position) {

        $sellSymbol = $position->symbol;
        $sellQty = $position->qty;

        // Log
        $filename = '/home/AlgoTrader/main/log.txt';
        $date = date('m/d/Y h:i:s');
        $content = array('SELL ALL - COB ', $date);
        file_put_contents($filename, $content,FILE_APPEND);
        // SELL ALL
        $alpaca->createOrder($sellSymbol, $sellQty, 'sell', $type, $time_in_force, $limit_price = null, $stop_price = null, $client_order_id = null);
    }
}

