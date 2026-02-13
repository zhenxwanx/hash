<?php
/* =====================================
   PHP CLI OBFUSCATOR - FINAL STABLE
   ===================================== */

$sourceFile = "run.php";
$outputFile = "encoded.php";

/* ===== CONFIG ===== */
$PASSPHRASE = "CLI_SECRET_KEY_2026";
$KEY_B64    = base64_encode(hash('sha256', $PASSPHRASE, true));
$IV_SEED    = "IV_CLI_2026";
$EXPIRED_AT = strtotime("2026-12-31");
/* ================== */

// machine lock
function machine_id() {
    return hash('sha256', implode('|', [
        php_uname(),
        get_current_user(),
        PHP_OS
    ]));
}
$LOCK_MACHINE = machine_id();

/* ===== READ SOURCE ===== */
$code = file_get_contents($sourceFile);
$code = preg_replace('/^\<\?php/', '', $code);

/* ===== COMPRESS ===== */
$code = gzdeflate($code, 9);

/* ===== ENCRYPT ===== */
$encrypted = openssl_encrypt(
    $code,
    'AES-256-CBC',
    base64_decode($KEY_B64),
    OPENSSL_RAW_DATA,
    substr(hash('sha256', $IV_SEED), 0, 16)
);

if (!$encrypted) die("Encrypt failed");

/* ===== BASE64 + SPLIT ===== */
$b64    = base64_encode($encrypted);
$chunks = str_split($b64, 6); // split saja, NO shuffle
$array  = var_export($chunks, true);

/* ===== LOADER ===== */
$loader = <<<PHP
<?php
if (php_sapi_name() !== 'cli') die("CLI only");

// expired check
if (time() > $EXPIRED_AT) die("License expired");

// machine lock
function __mid(){
    return hash('sha256', implode('|', [
        php_uname(),
        get_current_user(),
        PHP_OS
    ]));
}
if (__mid() !== "$LOCK_MACHINE") die("Invalid machine");

// rebuild payload
\$_ = [];
\$_['p'] = $array;
\$_['b'] = implode('', \$_['p']);
\$_['c'] = base64_decode(\$_['b']);

\$_['d'] = openssl_decrypt(
    \$_['c'],
    'AES-256-CBC',
    base64_decode('$KEY_B64'),
    OPENSSL_RAW_DATA,
    substr(hash('sha256', '$IV_SEED'), 0, 16)
);

if (!\$_['d']) die("Decrypt failed");

\$_['e'] = gzinflate(\$_['d']);
if (!\$_['e']) die("Payload corrupted");

eval(\$_['e']);
PHP;

file_put_contents($outputFile, $loader);
echo "🔥 ENCODE CLI FINAL BERHASIL → encoded.php\n";
