<?php

require_once '../vendor/autoload.php';
require_once '../conf/config.php';
use Symfony\Component\Yaml\Parser;
require_once 'ScreenHandler.php';
require_once 'HtmlLineFormatter.php';

// @TODO Validate that the payload is from GitHub.
exit;

// create a new cURL resource
$payload = json_decode(file_get_contents('php://input'));
// print_r($payload);
$branch = explode('/', $payload->ref)[2];


//echo $url;


function getBookConfigs($config)
{
    $yaml = new Parser();
    $files = glob("{$config['root']}/conf/books/*.yml");
    foreach ($files as $file) {
        $configs[basename($file, '.yml')] = $yaml->parse(file_get_contents("$file"));
    }

    return $configs;
}

$repoFromPayload = $payload->repository->html_url;

$bookConfigs = getBookConfigs($config);
foreach ($bookConfigs as $bookName => $bookConfig) {
    foreach ($bookConfig as $bookLang => $bookLangDetails) {
        if($bookLangDetails['repo'] == $repoFromPayload){
            $book = $bookName;
            $lang = $bookLang;
        }
    }
}

$url = "http://docs/publish.php?book={$book}&branch={$branch}&lang={$lang}";

$ch = curl_init();

// set URL and other appropriate options
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HEADER, 0);

// grab URL and pass it to the browser
curl_exec($ch);

// close cURL resource, and free up system resources
curl_close($ch);
