<?php

// Alpaca keys
$KEYID = 'XXXXX';
$SECRETKEY = 'XXXXX';
$PAPERKEYID = 'XXXXX';
$PAPERSECRETKEY = 'XXXXX';

// Alpha Vantage API parameters
$apiKey = 'XXXXX';
$symbols = array('YOUR', 'STOCK', 'SYMBOLS');
$interval = '5min';
// SMA time periods
$timePeriods = array(5, 13);
$seriesType = 'close';

// alpaca defaults
$type = 'market';
$time_in_force = 'day';

foreach ($symbols as $symbol) {
    $SMA[] = getStockData($apiKey, $symbol, 'SMA', $interval, $timePeriods, $seriesType);
    $RSI[] = getStockData($apiKey, $symbol, 'RSI', $interval, $timePeriods, $seriesType);
    $price[] = getStockData($apiKey, $symbol, 'TIME_SERIES_INTRADAY', '1min', $timePeriods, $seriesType);
}

foreach ($price as $value) {
    $allprices[] = reset($value[0]['Time Series (1min)']);
}

foreach ($allprices as $allprice) {
    $latestPrices[] = $allprice['4. close'];
}

$sma5History = array();
$sma13History = array();
if (!empty($SMA[0][0]['Technical Analysis: SMA'])) {
    foreach ($SMA as $key => $value) {
        // get last 30 results
        $sma5History = array_slice($value[0]['Technical Analysis: SMA'], 0, 30);
        // array of the SMA values
        $sma5Historys[] = array_column($sma5History, 'SMA');

        // get last 30 results
        $sma13History = array_slice($value[1]['Technical Analysis: SMA'], 0, 30);
        // array of the SMA values
        $sma13Historys[] = array_column($sma13History, 'SMA');
    }
}

$differenceSmaHistory = array();
if (!empty($sma5Historys)) {
    // subtract sma5 and sma13
    foreach ($sma5Historys as $key => $value) {
        $differenceSmaHistory[] = subtractArrays($sma5Historys[$key] , $sma13Historys[$key]);
    }

    // find slopes at all points for SMA 5
    foreach ($sma5Historys as $key => $value) {
        for ($i = 0; $i < (count($value) - 1); $i++) {
            $slopeOfSma5History[$key][] = ($value[$i] - $value[$i + 1]);
        }
    }
}

// find slope at all points in $differenceSmaHistory
$slopeOfDifferenceSmaHistory = array();
if (!empty($differenceSmaHistory)) {
    foreach ($differenceSmaHistory as $key => $value) {
        for ($i = 0; $i < (count($value) - 1); $i++) {
            $slopeOfDifferenceSmaHistory[$key][] = ($value[$i] - $value[$i + 1]);
        }
    }
}

// data for ajax
$differenceSmaHistoryGraph = array_reverse($differenceSmaHistory);
$slopeOfDifferenceSmaHistoryGraph = array_reverse($slopeOfDifferenceSmaHistory);
$data = array(
    $differenceSmaHistoryGraph,
    $slopeOfDifferenceSmaHistoryGraph,
);
echo json_encode($data);

function getStockData($apiKey, $symbol, $indicator, $interval, $timePeriods, $seriesType)
{
    foreach ($timePeriods as $key => $timePeriod) {
        $stockAPI = "https://www.alphavantage.co/query?function=" . $indicator . "&symbol=" . $symbol . "&interval="
            . $interval . "&time_period=" . $timePeriod . "&series_type=" . $seriesType . "&apikey=" . $apiKey;

        $stockJson = file_get_contents($stockAPI);
        $result[$key] = json_decode($stockJson, true);
    }

    return $result;
}

function subtractArrays($arr1, $arr2) {
    $result = array();
    foreach ($arr1 as $key => $value) {
        $result[] = $arr1[$key] - $arr2[$key];
    }

    return $result;
}
