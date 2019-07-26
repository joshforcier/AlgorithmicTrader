<?php

namespace Alpaca;

use GuzzleHttp\Client;
use Carbon\Carbon;

class Alpaca
{
    private $client;
    private $key;
    private $secret;
    private $paper;

    public function __construct($key = "", $secret = "", $paper = true)
    {
        $this->key = $key;
        $this->secret = $secret;
        $this->paper = $paper;

        $this->client = new Client();
    }

    public function setKey($key = "")
    {
        $this->key = $key;
    }

    public function setSecret($secret = "")
    {
        $this->secret = $secret;
    }

    public function setPaper($paper = true)
    {
        $this->paper = $paper;
    }

    private function _buildUrl($path = "", $queryStrings = [], $domain = null, $version = "v1")
    {
        $queryString = "";

        foreach ($queryStrings as $key => $value) {
            if (is_array($value)) {
                continue;
            }

            $queryString .= "&{$key}={$value}";
        }

        if (strlen($queryString) > 0) {
            $queryString = "?" . substr($queryString, 1);
        }

        if (is_null($domain)) {
            if ($this->paper === true) {
                $domain = "https://paper-api.alpaca.markets";
            } else {
                $domain = "https://api.alpaca.markets";
            }
        }

        $path = trim($path, "/");

        return "{$domain}/{$version}/{$path}{$queryString}";
    }

    private function _request($path, $queryString = [], $type = "GET", $body = null, $domain = null)
    {
        try {
            $request = [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'APCA-API-KEY-ID' => "{$this->key}",
                    'APCA-API-SECRET-KEY' => "{$this->secret}",
                ]
            ];

            if (is_array($body)) {
                $request['body'] = json_encode($body);
            } else if (!empty($body)) {
                $request['body'] = $body;
            }

            $response = $this->client->request($type, $this->_buildUrl($path, $queryString, $domain), $request);

            return new Response($response);
        } catch (\GuzzleHttp\Exception\TransferException $e) {
            if ($e->hasResponse()) {
                return new Response($e->getResponse());
            } else {
                throw $e;
            }
        }
    }

    public function getAccount()
    {
        return $this->_request("account");
    }

    public function getOrders($status = null, $limit = null, $after = null, $until = null, $direction = null)
    {
        $qs = [];

        if (!is_null($status)) {
            $qs['status'] = $status;
        }

        if (!is_null($limit)) {
            $qs['limit'] = $limit;
        }

        if (!is_null($after)) {
            $qs['after'] = $after;
        }

        if (!is_null($until)) {
            $qs['until'] = $until;
        }

        if (!is_null($direction)) {
            $qs['direction'] = $direction;
        }

        return $this->_request("orders", $qs);
    }

    public function getOrder($order_id)
    {
        return $this->_request("orders/{$order_id}");
    }

    public function getOrderByClientId($client_order_id)
    {
        return $this->_request("orders:by_client_order_id", ['client_order_id' => $client_order_id]);
    }

    public function cancelOrder($order_id)
    {
        return $this->_request("orders/{$order_id}", [], "DELETE");
    }

    public function createOrder($symbol, $qty, $side, $type, $time_in_force, $limit_price = null, $stop_price = null, $client_order_id = null)
    {
        $body = [
            'symbol'        => $symbol,
            'qty'           => $qty,
            'side'          => $side,
            'type'          => $type,
            'time_in_force' => $time_in_force
        ];

        if (!is_null($limit_price)) {
            $body['limit_price'] = $limit_price;
        }

        if (!is_null($stop_price)) {
            $body['stop_price'] = $stop_price;
        }

        if (!is_null($client_order_id)) {
            $body['client_order_id'] = $client_order_id;
        }

        return $this->_request("orders", [], "POST", $body);
    }

    public function getPositions()
    {
        return $this->_request("positions");
    }

    public function getPosition($symbol)
    {
        return $this->_request("positions/{$symbol}");
    }

    public function getAssets($status = null, $asset_class = null)
    {
        $qs = [];

        if (!is_null($status)) {
            $qs['status'] = $status;
        }

        if (!is_null($asset_class)) {
            $qs['asset_class'] = $asset_class;
        }

        return $this->_request("assets", $qs);
    }

    public function getAsset($symbol)
    {
        return $this->_request("assets/{$symbol}");
    }

    public function getCalendar($start = null, $end = null)
    {
        $qs = [];

        if (!is_null($start)) {
            $qs['start'] = (new Carbon($start))->format('Y-m-d');
        }

        if (!is_null($end)) {
            $qs['end'] = (new Carbon($end))->format('Y-m-d');
        }

        return $this->_request("calendar", $qs);
    }

    public function getClock()
    {
        return $this->_request("clock");
    }

    public function getBars($timeframe, $symbols, $limit = null, $start = null, $end = null, $after = null, $until = null)
    {
        $qs = [];

        if (is_array($symbols)) {
            $qs['symbols'] = implode(",", $symbols);
        } else {
            $qs['symbols'] = $symbols;
        }

        if (!is_null($limit)) {
            $qs['limit'] = $limit;
        }

        if (!is_null($start)) {
            $qs['start'] = $start;
        }

        if (!is_null($end)) {
            $qs['end'] = $end;
        }

        if (!is_null($after)) {
            $qs['after'] = $after;
        }

        if (!is_null($until)) {
            $qs['until'] = $until;
        }

        return $this->_request("bars/{$timeframe}", $qs, "GET", null, "https://data.alpaca.markets");
    }

    public function __call($method, $args)
    {
        if (method_exists($this, $method)) {
            return $this->$method();
        }

        throw new \Exception("Unknown method: {$method}");
    }

    public function __get($property)
    {
        if (method_exists($this, $property)) {
            return $this->$property();
        }

        throw new \Exception("Unknown property: {$property}");
    }
}
