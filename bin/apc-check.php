<?php

$item = $argv[1];
function file_get_contents_curl($url) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1/apc-stats.php?apc=' . $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);

    $data = curl_exec($ch);
    curl_close($ch);

    return $data;
}

switch($item) {
                case 'mem.used':
                        $results = file_get_contents_curl("sma_info");
                        if ($results) {
                                $results = unserialize($results);
                                echo $results["seg_size"] * $results["num_seg"] - $results["avail_mem"];
                        }
                        else
                                exit;
                        break;
                case 'mem.avail':
                        $results = file_get_contents_curl("sma_info");
                        if ($results) {
                                $results = unserialize($results);
                                echo $results["avail_mem"];
                        }
                        else
                                exit;
                        break;
                case 'hits':
                        $results = file_get_contents_curl("cache_info");
                        if ($results) {
                                $results = unserialize($results);
                                echo $results["num_hits"];
                        }
                        else
                                exit;
                        break;
                case 'misses':
                        $results = file_get_contents_curl("cache_info");
                        if ($results) {
                                $results = unserialize($results);
                                echo $results["num_misses"];
                        }
                        else
                                exit;
                        break;
                case 'hits_ratio':
                        $results = file_get_contents_curl("cache_info");
                        if ($results) {
                                $results = unserialize($results);
                                echo ($results["num_hits"] / ($results["num_hits"] - $results["num_misses"]))*100;
                        }
                        else
                                exit;
                        break;
                case 'entries':
                        $results = file_get_contents_curl("cache_info");
                        if ($results) {
                                $results = unserialize($results);
                                echo $results["num_entries"];
                        }
                        else
                                exit;
                        break;
                case 'user.entries':
                        $results = file_get_contents_curl("user_cache_info");
                        if ($results) {
                                $results = unserialize($results);
                                echo $results["num_entries"];
                        }
                        else
                                exit;
                        break;
                default:
                        exit;
        }
?>