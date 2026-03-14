<?php
/**
 * Broodle Engage — WHMCS Server Status Script
 *
 * Upload this file to your WHMCS root (or any public URL on your server).
 * Then in WHMCS Admin → Setup → Servers → Edit Server, set:
 *   Server Status Address = https://yourdomain.com/broodle-status.php
 *
 * WHMCS fetches this URL and parses:
 *   <load>   — shown as "Avg Load"  → we display active account count
 *   <uptime> — shown as "Uptime"    → we display days since Chatwoot server first responded
 *
 * Config — edit these two lines:
 */
define('BE_CHATWOOT_URL',   'https://engage.broodle.one');
define('BE_PLATFORM_TOKEN', '9CHEbLL65iLZ2GEJiU73Dgtr');

// ─────────────────────────────────────────────────────────────────────────────

error_reporting(0);
header('Content-Type: text/xml; charset=utf-8');

// ── 1. Ping Chatwoot — check it's reachable ───────────────────────────────
$pingStart = microtime(true);
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => BE_CHATWOOT_URL . '/auth/sign_in',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 8,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_NOBODY         => true,   // HEAD-style — just check reachability
    CURLOPT_FOLLOWLOCATION => true,
]);
curl_exec($ch);
$httpCode  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$pingMs    = round((microtime(true) - $pingStart) * 1000);
curl_close($ch);

// If Chatwoot is unreachable, output nothing — WHMCS will mark server Offline
if ($httpCode === 0 || $httpCode >= 500) {
    http_response_code(503);
    echo "<load>offline</load>\n";
    echo "<uptime>Unreachable</uptime>\n";
    exit;
}

// ── 2. Get active account count from our WHMCS DB ────────────────────────
// We read directly from the mod_broodleengage table if this script is inside WHMCS root.
// If placed outside WHMCS, falls back to a Chatwoot API call.
$activeAccounts = null;

// Try WHMCS DB (works when this file is in the WHMCS root or a subfolder)
$whmcsRoot = __DIR__;
// Walk up to find WHMCS init file
foreach (['/../../../', '/../../', '/../', '/'] as $rel) {
    $candidate = realpath($whmcsRoot . $rel . 'init.php');
    if ($candidate && file_exists($candidate)) {
        // Don't actually include WHMCS — just connect to DB using its config
        $configFile = realpath($whmcsRoot . $rel . 'configuration.php');
        if ($configFile && file_exists($configFile)) {
            include_once $configFile;
            if (isset($db_host, $db_username, $db_password, $db_name)) {
                try {
                    $pdo = new PDO(
                        "mysql:host={$db_host};dbname={$db_name};charset=utf8",
                        $db_username,
                        $db_password,
                        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 3]
                    );
                    $stmt = $pdo->query(
                        "SELECT COUNT(*) FROM mod_broodleengage WHERE chatwoot_status = 'active'"
                    );
                    $activeAccounts = (int) $stmt->fetchColumn();
                } catch (Exception $e) {
                    // DB unavailable — fall through to API
                }
            }
        }
        break;
    }
}

// Fallback: count via Chatwoot platform API (slower but works anywhere)
if ($activeAccounts === null) {
    $ch2 = curl_init();
    curl_setopt_array($ch2, [
        CURLOPT_URL            => BE_CHATWOOT_URL . '/platform/api/v1/accounts?page=1',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER     => ['api_access_token: ' . BE_PLATFORM_TOKEN],
    ]);
    $resp = curl_exec($ch2);
    curl_close($ch2);
    $data = json_decode($resp, true);
    // Response is an array of accounts — count them
    $activeAccounts = is_array($data) ? count($data) : 0;
}

// ── 3. Calculate uptime string ────────────────────────────────────────────
// We use the cache file to track when the server was first seen online.
// This gives a meaningful "uptime" — days the service has been running.
$cacheFile = sys_get_temp_dir() . '/be_status_first_seen.txt';
if (!file_exists($cacheFile)) {
    file_put_contents($cacheFile, time());
}
$firstSeen  = (int) file_get_contents($cacheFile);
$uptimeSecs = time() - $firstSeen;
$days       = floor($uptimeSecs / 86400);
$hours      = str_pad(floor(($uptimeSecs % 86400) / 3600), 2, '0', STR_PAD_LEFT);
$mins       = str_pad(floor(($uptimeSecs % 3600) / 60), 2, '0', STR_PAD_LEFT);
$secs       = str_pad($uptimeSecs % 60, 2, '0', STR_PAD_LEFT);
$uptimeStr  = "{$days} Days {$hours}:{$mins}:{$secs}";

// ── 4. Output XML that WHMCS parses ──────────────────────────────────────
// <load>   → displayed as "Avg Load" in the widget  → we show active accounts
// <uptime> → displayed as "Uptime" in the widget    → we show days online
echo "<load>{$activeAccounts} accounts</load>\n";
echo "<uptime>{$uptimeStr}</uptime>\n";
