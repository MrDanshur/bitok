<?php
$start = microtime(true);
$url = "https://wex.nz/api/3/info?hidden=0";

$ch = curl_init();
// GET запрос указывается в строке URL
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
curl_setopt($ch, CURLOPT_USERAGENT, 'PHP Bot (http://mysite.ru)');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$data = curl_exec($ch);
curl_close($ch);

$end = microtime(true);
$jsonPairs = json_decode($data, true);


//var_dump($jsonPairs->pairs->eur_usd);
//var_dump($jsonPairs["pairs"]["btc_usd"]["min_amount"]);
$combinations = [];
foreach ($jsonPairs["pairs"] as $pairKey => $pairValues) {
    if (!isToken($pairKey)) {
        [$combinations[$pairKey]["d"], $combinations[$pairKey]["r"]] = explode("_", $pairKey);
    }
}


//print_r($combinations);
//echo "COMBINATIONS:". PHP_EOL;
//foreach ($combinations as $combination) {
//    echo $combination["d"] . " => " . $combination["r"] . PHP_EOL;
//    echo $combination["r"] . " => " . $combination["d"] . PHP_EOL;
//}

$chains = [];
$i = 0;
$j = 0;

$startCur = "usd";
$possible = [];
$countPairs = count($jsonPairs["pairs"]);
while ($i < $countPairs) {
    $j = 0;
    foreach ($combinations as $combination) {
        if (!array_key_exists($combination["d"], $possible)) {
            $possible[$combination["d"]] = [];
        }

        if (!in_array($combination["r"], $possible[$combination["d"]])) {
            $possible[$combination["d"]][] = $combination["r"];
        }

        if (array_key_exists($combination["r"], $possible) && !in_array($combination["d"], $possible[$combination["r"]])) {
            $possible[$combination["r"]][] = $combination["d"];
        }



//        echo $combination["d"] . " ";
//        if ($combination["r"] === $startCur) {
//            $chains[$j][] = $combination["d"];
//            continue;
//        }
//        if (in_array($combination["r"], $chains[$j])) {
//            $chains[$j][] = $combination["d"];
//        }

        $j++;
    }
    $i++;
}

$i = 0;
foreach ($possible as $key => $values) {
    if ($key !== $startCur) {
        continue;
    }
    foreach ($values as $value) {
        $chains[$i][] = $key;
        $chains[$i][] = $value;
        $i++;
    }
}

$j = 0;
foreach ($chains as $keyChain => $ChainValues) {
    foreach ($possible as $keyPos => $posValues) {
        $countFromTo = count($posValues);
        foreach ($posValues as $curency) {
            if (in_array($curency, $chains[$keyChain])) {
                if ($startCur == $curency && count($chains[$keyChain]) > 1) {
                    $chains[$keyChain][] = $curency;
                }
                continue;
            }

//            if ($startCur == end($chains[$keyChain])) {
//                continue;
//            }
            $chains[]            = $chains[$keyChain];
            $chains[$keyChain][] = $curency;
        }




//        if (end($ChainValues) != $key) {
//            continue;
//        }
//        if (!array_key_exists(current($posValues), $chains[$key])) {
//            $chains[$i][] = current($posValues);
//        }
//
//        foreach ($posValues as $value) {
//            if (!array_key_exists($value, $chains[$i])) {
//                $chains[$i][] = current($value);
//            }
//
//            $chains[$i][] = $value;
//            $i++;
//        }

    }
}



print_r($chains);

$end2 = microtime(true);

$requestTime = $end-$start;
$parseTime = $end2-$end;
echo "1: $requestTime";
echo " 2: $parseTime";



function isToken($pair) {
    return 0 === strpos($pair, substr($pair, -3)); // first 3 chars = last 3 chars
}

function endsWith($haystack, $needle)
{
    $length = strlen($needle);

    return $length === 0 ||
           (substr($haystack, -$length) === $needle);
}