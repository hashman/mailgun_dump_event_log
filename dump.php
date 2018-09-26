<?php

date_default_timezone_set("Asia/Taipei");
$config_file = __DIR__ . '/config.php';
if (!file_exists($config_file)) {
    echo "Config file not found\n";
    exit();
}

$config = require $config_file;

require __DIR__ . '/vendor/autoload.php';

use Mailgun\Mailgun;
use Mailgun\Api\Event;

$begin = date("D, d M Y H:i:00 +0800", strtotime("-1 hours"));
$end = date("D, d M Y H:i:00 +0800");

$mg = Mailgun::create($config['api-key']);
try {
    $result = fetchRecord($begin, $end);
} catch (\Exception $e) {
    try {
        $result = fetchRecord($begin, $end);
    } catch (\Exception $e) {
        callSlack('zd_web_team', $e->getMessage());
    }
}

if (empty($result)) {
    exit();
}

foreach ($result as $row) {
    if ($row->getDeliveryStatus()) {
        echo $row->getEventDate()->format('Y-m-d H:i:s') . "\tDelivery\t" . json_encode($row->getMessage()['headers'], JSON_UNESCAPED_UNICODE) . "\t" . json_encode($row->getDeliveryStatus()) . "\n";
        continue;
    }
    echo $row->getEventDate()->format('Y-m-d H:i:s') . "\tSend\t" . json_encode($row->getMessage()['headers'], JSON_UNESCAPED_UNICODE) . "\n";
}

function callSlack($channel, $message)
{
    global $config;

    if (!isset($config['hook-url']) || !isset($config['base-uri'])) {
        // 沒有設定 hook-url 直接 return
        return;
    }
    $hook_url = $config['hook-url'] . $channel;

    try {
        $client = new \GuzzleHttp\Client([
            'base_uri' => $config['base-uri']
        ]);
        $client->request("POST", $hook_url, [
            'body' => $message
        ]);
    } catch (\Exception $e) {
        // ignore slack pusher timeout exception
    }
}

function fetchRecord($begin, $end)
{
    global $mg, $config;

    return $mg->events()->get($config['domain'], [
        'begin' => $begin,
        'end' => $end,
        'ascending' => 'yes'
    ])->getItems();
}
