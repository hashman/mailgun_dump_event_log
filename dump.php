<?php

$config_file = __DIR__ . '/config.php';
if (!file_exists($config_file)) {
    echo "Config file not found\n";
    exit();
}

$config = require $config_file;

require __DIR__ . '/vendor/autoload.php';

use Mailgun\Mailgun;
use Mailgun\Api\Event;

$begin = date("D, d M Y H:i:00 +0800", strtotime("-10 minutes"));
$end = date("D, d M Y H:i:00 +0800");

$mg = Mailgun::create($config['api-key']);
$result = $mg->events()->get($config['domain'], [
    'begin' => $begin,
    'end' => $end,
    'ascending' => 'yes'
])->getItems();

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
