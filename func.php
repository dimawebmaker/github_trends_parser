<?php

if(!function_exists('ilog')):
function ilog($data) {
    $msg = "- ".date('Y-m-d H:i:s')." ".$data.PHP_EOL;
    print($msg);
    file_put_contents('./logs/'.date('Y-m').'.txt', $msg, FILE_APPEND);
}
endif;