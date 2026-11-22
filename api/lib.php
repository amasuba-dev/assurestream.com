<?php
/*
 * Shared helpers for the console's server-side endpoints (claude.php, feeds.php, ingest.php).
 * Secrets live in ri-demo-secrets.php OUTSIDE the public web folder, in any folder up to seven
 * levels above this file (for example your Hostinger home folder). See README for the template.
 */
if (!defined('RI_LIB')) {
define('RI_LIB', 1);

function ri_secrets_path() {
  static $path = null;
  if ($path !== null) return $path;
  $path = '';
  $dir = __DIR__;
  for ($i = 0; $i < 7; $i++) {
    $dir = dirname($dir);
    if (is_readable($dir . '/ri-demo-secrets.php')) { $path = $dir . '/ri-demo-secrets.php'; break; }
  }
  return $path;
}

function ri_secrets() {
  static $s = null;
  if ($s !== null) return $s;
  $p = ri_secrets_path();
  $s = $p ? (require $p) : [];
  if (!is_array($s)) $s = [];
  return $s;
}

// Private working folder for caches: next to the secrets file if there is one, else the system temp folder.
function ri_cache_dir() {
  $p = ri_secrets_path();
  $dir = $p ? dirname($p) . '/ri-cache' : sys_get_temp_dir() . '/ri-cache';
  if (!is_dir($dir)) @mkdir($dir, 0700, true);
  return $dir;
}

function ri_json_out($code, $data) {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  header('X-Content-Type-Options: nosniff');
  echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

// GET (or POST) with a timeout and a polite user agent. Returns [status, body, error].
function ri_http($url, $opts = []) {
  $ch = curl_init($url);
  $headers = $opts['headers'] ?? [];
  $headers[] = 'User-Agent: RegulatoryIntelligenceConsole/1.0 (demo; contact via site owner)';
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS => 3,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => $opts['timeout'] ?? 25,
    CURLOPT_HTTPHEADER => $headers,
  ]);
  if (isset($opts['post'])) { curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, $opts['post']); }
  if (isset($opts['method'])) curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $opts['method']);
  $body = curl_exec($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $err = curl_error($ch);
  curl_close($ch);
  return [$code, $body === false ? '' : $body, $err];
}
}
