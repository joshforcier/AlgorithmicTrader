<?php

// server
//require '/home/AlgoTrader/main/data.php';
//require '/home/AlgoTrader/main/autoload.php';

// local
require './data.php';
require './autoload.php';

use Alpaca\Alpaca;

// Initialize the Alpaca class with your Alpaca Key and Secret.
// You can also set the third parameter to enable or disable paper trading.
// The default is true to enable calling against the paper trading endpoint.

// PAPER
$alpaca = new Alpaca($PAPERKEYID, $PAPERSECRETKEY, true);
// REAL MONEY
// $alpaca = new Alpaca($KEYID, $SECRETKEY, false);

$positions = $alpaca->getPositions()->getResponse();
$account = $alpaca->getAccount()->getResponse();

$filename = '/home/AlgoTrader/main/log.txt';
$date = date(' m/d/Y h:i:s') . PHP_EOL;

foreach ($symbols as $key => $symbol) {

    /////////////////
    // BUY BUY BUY //
    /////////////////

    // calculate max qty for buy
    $buyingPower = $account->buying_power;

    // Your buy logic here
    // Example, if rsi is < 40, buy 75% of your total $
    $thisRSI = reset($RSI[$key][1]['Technical Analysis: RSI']);

    if (!empty($RSI)) {
        $thisRSI = reset($RSI[$key][1]['Technical Analysis: RSI']);

        if ($thisRSI['RSI'] < 40) {
            $qty = floor(($buyingPower * 0.75) / $latestPrices[$key]);
            // Log
            $content = array('BUY: ' . $symbol, ' QTY: ' . $qty, ' RSI: ' . $thisRSI['RSI'], 'Slope: ' . $slopeOfSma5History[$key][0], $date);
            file_put_contents($filename, $content, FILE_APPEND);

            $alpaca->createOrder($symbol, $qty, 'buy', $type, $time_in_force, $limit_price = null, $stop_price = null, $client_order_id = null);
        }
    }


    ////////////////////
    // SELL SELL SELL //
    ////////////////////

    foreach ($positions as $position) {
        if ($position->symbol === $symbol) {

            // Your sell logic here
            // Example, if rsi is > 80, sell all
            $thisRSI = reset($RSI[$key][1]['Technical Analysis: RSI']);

            if ($thisRSI['RSI'] > 80) {
                $sellQty = $position->qty;
                // Log
                $content = array('SELL: ' . $symbol, ' QTY: ' . $sellQty, ' RSI: ' . $thisRSI['RSI'], $date);
                file_put_contents($filename, $content, FILE_APPEND);
                // SELL
                $alpaca->createOrder($symbol, $sellQty, 'sell', $type, $time_in_force, $limit_price = null, $stop_price = null, $client_order_id = null);
            }
        }
    }
}
