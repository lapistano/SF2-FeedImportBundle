#! /usr/bin/php
<?php
/**
 * This script translates a HTTP GET request into a HTTP POST request.
 *
 * <b>Note:</b>
 * This PHP cli script was for intermediate use to present the functionality of an existing web-service importing
 * content send via HTTP request. It just builds the bridge between a feed and the web-service to be called.
 * Do not rely on the proper and correct functionality of this script. If you want to import data from a feed
 * you should always use the app/console option of SF2.
 *
 * Call <b>php feedReader.php --help</b> to get an overview of the options.
 *
 * @package FeedImportBundle
 * @subpackage Scripts
 */

$feedUrl = null;
$serviceUrl = null;
$proxyUrl = null;

$shortopts  = "";
$shortopts .= "f::"; // Required value
$shortopts .= "s::"; // Optional value
$shortopts .= "p::"; // Optional value

$longopts  = array(
    "feed::",
    "service::",
    "help::",
    "proxy::",
);
$options = getopt($shortopts, $longopts);

if(empty($options) || (isset($options['help']) && $options['help'] === false)) {
    echo "dispatcher to forward content from a rss feed to a webservice.\n\n";
    echo "SYPNOSIS: \n\n";
    echo "$ ./feedReader.php --feed=<feed url> --service=<service url> [--proxy=<proxy address>] [--help]\n\n";
    echo "OPTIONS: \n\n";
    echo "\t --feed/-f \t URL of the feed to be read.\n";
    echo "\t --service/-s \t URL of the service to be fed.\n";
    echo "\t --proxy/-p \t URL of a proxy to be used.\n";
    echo "\t --help \t Show this information.\n";
    exit;
}

if((isset($options['f']) && $options['f']) || (isset($options['feed']) && $options['feed'])) {
    $feedUrl = empty($options['f']) ? $options['feed'] : $options['f'];
}
if((isset($options['s']) && $options['s']) || (isset($options['service']) && $options['service'])) {
    $serviceUrl = empty($options['s']) ? $options['service'] : $options['s'];
}

if((isset($options['p']) && $options['p']) || (isset($options['proxy']) && $options['proxy'])) {
    $proxyUrl = empty($options['p']) ? $options['proxy'] : $options['p'];
}

$content = file_get_contents($feedUrl);

// create a new cURL resource
$ch = curl_init();

// set URL and other appropriate options
curl_setopt($ch, CURLOPT_URL, $serviceUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
if ($proxyUrl) {
    curl_setopt($ch, CURLOPT_PROXY, $proxyUrl);
}
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: text/xml; charset=utf-8'));

curl_exec($ch);
exit;