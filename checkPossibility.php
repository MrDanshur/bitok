<?php
$start = microtime(true);
$urlPairs = "https://wex.nz/api/3/info?hidden=0";

$ch = curl_init();
// GET запрос указывается в строке URL
curl_setopt($ch, CURLOPT_URL, $urlPairs);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
curl_setopt($ch, CURLOPT_USERAGENT, 'PHP Bot (http://mysite.ru)');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$data = curl_exec($ch);
//curl_close($ch);

$end = microtime(true);
$jsonPairs = json_decode($data, true);


//var_dump($jsonPairs->pairs->eur_usd);
//var_dump($jsonPairs["pairs"]["btc_usd"]["min_amount"]);
$combinations = [];
$strUrl = "";

foreach ($jsonPairs["pairs"] as $pairKey => $pairValues) {
    if (!isToken($pairKey)) {
        [$combinations[$pairKey]["d"], $combinations[$pairKey]["r"]] = explode("_", $pairKey);
        $strUrl .= $pairKey . "-";
    }
}



//print_r($combinations);
//echo "COMBINATIONS:". PHP_EOL;
//foreach ($combinations as $combination) {
//    echo $combination["d"] . " => " . $combination["r"] . PHP_EOL;
//    echo $combination["r"] . " => " . $combination["d"] . PHP_EOL;
//}

$i = 0;
$j = 0;

$startCur = "usd";
//$startCur = "btc";
//$startCur = "eth";
//$startCur = "zec";
//$startCur = "dsh";
//$startCur = "nmc";
//$startCur = "nvc";
//$startCur = "ltc";

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

        $j++;
    }
    $i++;
}


$chains = [];
// Generate first 2 elements in chain. FIrst - USD, 2nd - to can USD can be converted
foreach ($possible[$startCur] as $key => $value) {
    $chains[$key][] = $startCur;
    $chains[$key][] = $value;
}

unset($possible[$startCur]); // delete USD from possible

//Generate all possibale chaines.
foreach ($possible as $kPosses => $vPosses) {
    foreach ($chains as $kChain => $vChains) {
        if (end($vChains) === $startCur) {
            continue;
        }

        if ($kPosses !== end($vChains)) {
            continue;
        }

        $puts = [];
        foreach ($vPosses as $kPos => $vPos) {
            if (in_array($vPos, $vChains) && $vChains[0] !== $vPos) {
                continue;
            }

            $puts[] = $vPos;
        }

        if (empty($puts)) {
            continue;
        }

        foreach ($puts as $put) {
            $chains[] = $chains[$kChain];
            end($chains);
            $key = key($chains);
            $chains[$key][] = $put;
        }
    }
}

// Delete chains where first and last element is not the same
foreach ($chains as $kChain => $vChains) {
    if (current($vChains) !== end($vChains)) {
        unset($chains[$kChain]);
    }
}

// Delete chains where only 3 elements. It can't be profittable
foreach ($chains as $kChain => $vChains) {
    if (3 === count($vChains)) {
        unset($chains[$kChain]);
    }
}
sort($chains);
//print_r($chains);

$urlCurrentPos = "https://wex.nz/api/3/depth/" .  substr($strUrl, 0, -1) . "?limit=1";
curl_setopt($ch, CURLOPT_URL, $urlCurrentPos);
$depth = curl_exec($ch);
curl_close($ch);
$jsonDepth = json_decode($depth, true);
//print_r($jsonDepth);
$startSum = 10; //10 usd
$profits = [];
foreach ($chains as $kChain => $vChains) {
    $num = count($vChains);
    $result = $startSum;
    $str = "10";

    for ($i = 0; $i < $num-1; $i++) {
        $pairD = $vChains[$i] . "_" . $vChains[$i + 1];
        $pairR = $vChains[$i+1] . "_" . $vChains[$i];
//        echo $pairR . PHP_EOL;
        if (array_key_exists($pairD, $jsonDepth)) {
            $result = ($result * (float)$jsonDepth[$pairD]["bids"][0][0]) * 0.998;
            $str = $str . " * " . $jsonDepth[$pairD]["bids"][0][0];
//            print_r($jsonDepth[$pairD]["bids"][0][0]);
        }
        if (array_key_exists($pairR, $jsonDepth)) {
//            print_r($jsonDepth[$pairR]["asks"][0][0]);
            $result = ($result / (float)$jsonDepth[$pairR]["asks"][0][0]) * 0.998;
            $str = $str . " / " . $jsonDepth[$pairR]["asks"][0][0];
        }
    }
    $profits[] = $result;
//    echo $result . PHP_EOL;
//    echo $str . PHP_EOL;
//    print_r($vChains);
//    die();

}
echo max($profits) . PHP_EOL;
echo min($profits) . PHP_EOL;



$end2 = microtime(true);

$requestTime = $end - $start;
$parseTime = $end2 - $end;
echo " 1: $requestTime";
echo " 2: $parseTime";


function isToken($pair)
{
    return 0 === strpos($pair, substr($pair, -3)); // first 3 chars = last 3 chars
}