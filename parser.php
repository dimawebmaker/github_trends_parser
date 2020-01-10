<?php

require_once('./vendor/autoload.php');
require_once('./func.php');

use \Curl\Curl;
use DiDom\Document;
use DiDom\Query;

ilog("Start Github trending parser....");
ilog("This script can accept arguments. Default language is any and period daily.");
ilog("Example: php parser.php language period");
ilog("Example: php parser.php php daily");
ilog("Example: php parser.php ruby weekly");


$language = "";
$period = "";

//Here we check if arguments was passed.
if(sizeof($argv) > 1) {
    if(!empty($argv[1])) {
        $language = trim($argv[1]);
    }
    if(!empty($argv[2])) {
        $period = trim($argv[2]);
    }
}

$url = "https://github.com/trending";

if($language || $period) {
    $url = "https://github.com/trending/{$language}?since={$period}";
}

ilog("Parsing url is {$url}");

//init curl
$curl = new Curl();
$curl->get($url);

//check if our request have error.
if($curl->error) {
    ilog("url: {$url} ".'Error: ' . $curl->errorCode . ': ' . $curl->errorMessage);
    exit(1);
}

//init DiDom with response
$html = new Document($curl->response);
$data_array = array(); //new array for storing other array with parsed data.

//check if response have repositories
if(!$html->has('article.Box-row') && sizeof($html->find('.article.Box-row')) <= 0) {
    ilog("No main block of elements, nothing to parse....");
    exit(1);
}

//start parsing info for each repository
foreach($html->find('article.Box-row') as $repo) {
    $new_arr = array();
    $new_arr['name'] = trim($repo->first('.h3.lh-condensed')->first('a')->attr('href'));
    $new_arr['name'] = ltrim($new_arr['name'], '/');

    $new_arr['url'] = "https://github.com/".trim($repo->first('.h3.lh-condensed')->first('a')->attr('href'));

    //there need check, becouse some repositories don't have description and we will get error without this check.
    if($repo->has('p.col-9.text-gray.my-1.pr-4')) {
        $new_arr['description'] = trim($repo->first('p.col-9.text-gray.my-1.pr-4')->text());
    }
    $data_array[] = $new_arr;
}

//Uncomment line below if you want to see result.
//print_r($data_array);

if(sizeof($data_array) <= 0) {
    ilog("Finish parsing, there is nothing to save into MySQL.");
    exit(1);
}

//now lets make new MySQL connection and insert all data in database.
$db = new MysqliDb ('host', 'username', 'password', 'databaseName');

foreach($data_array as $data) {
    $insert_data = array(
                "url"           => $data['url'],
                "name"          => $data['name'],
                "description"   => $data['description'],
                "dt"            => date('Y-m-d H:i:s'),
    );
    $id = $db->insert('trends', $insert_data);
    if($id) {
        ilog('Data was successfully inserted. Id=' . $id);
    }
}

ilog("Finish! All data was inserted into MySQL database.");
exit();