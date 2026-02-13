<?php
function genVar($len = 999) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    return '$' . substr(str_shuffle(str_repeat($chars, ceil($len / strlen($chars)))), 0, $len);
}

function generateLoader($encrypted, $key, $iv, $expiry, $allowedIP) {
    $vEncrypted = base64_encode($encrypted);
    $vKey = base64_encode($key);
    $vIV = base64_encode($iv);

    // Generate random long variable names (128 characters)
    $var_expiry = genVar();
    $var_ip = genVar();
    $var_data = genVar();
    $var_key = genVar();
    $var_iv = genVar();
    $var_decrypted = genVar();

    return <<<PHP
<?php {$var_data}=base64_decode("$vEncrypted");{$var_key}=base64_decode("$vKey");{$var_iv}=base64_decode("$vIV");{$var_decrypted}=openssl_decrypt({$var_data},"AES-256-CBC",{$var_key},0,{$var_iv});
if(!{$var_decrypted})exit("404 NOT FOUND\n");eval("?>".{$var_decrypted});
PHP;
}

if ($argc < 2) {
    echo "Usage: php encoder.php <script_asli.php>\n";
    exit;
}

$script = file_get_contents($argv[1]);
$key = openssl_random_pseudo_bytes(32);
$iv  = openssl_random_pseudo_bytes(16);

$encrypted = openssl_encrypt($script, 'AES-256-CBC', $key, 0, $iv);

// 🔐 Konfigurasi proteksi
$expiry = '2025-12-31';
$allowedIP = '127.0.0.1';

$loader = generateLoader($encrypted, $key, $iv, $expiry, $allowedIP);
file_put_contents('run.php', $loader);
echo "✅ Script terenkripsi disimpan di protected.php\n";
