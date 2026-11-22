<?php
/* AssureStream contact form: stores the enquiry in Supabase (table qx_leads) and emails a copy if qx_notify_email is set.
 * Secrets come from ri-demo-secrets.php outside public_html (see lib.php); nothing secret is in this file. */
require __DIR__ . '/lib.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') ri_json_out(405, ['error' => 'POST only']);
$in = json_decode(file_get_contents('php://input'), true);
if (!is_array($in)) ri_json_out(400, ['error' => 'Invalid request.']);
if (!empty($in['website'])) ri_json_out(200, ['ok' => true]); // honeypot: quietly accept bots
$f = fn($k, $n) => mb_substr(trim((string)($in[$k] ?? '')), 0, $n);
$lead = ['name' => $f('name', 120), 'org' => $f('org', 160), 'email' => strtolower($f('email', 160)), 'phone' => $f('phone', 40), 'country' => $f('country', 80), 'need' => $f('need', 120), 'message' => $f('message', 3000)];
if (mb_strlen($lead['name']) < 2 || mb_strlen($lead['org']) < 2 || !filter_var($lead['email'], FILTER_VALIDATE_EMAIL)) ri_json_out(422, ['error' => 'Please add your name, organisation and a valid email address.']);

// Rate limit: 5 enquiries per IP per hour.
$ip = $_SERVER['REMOTE_ADDR'] ?? 'x';
$rf = ri_cache_dir() . '/qx_rate_' . md5($ip);
$hits = array_filter(json_decode(@file_get_contents($rf) ?: '[]', true) ?: [], fn($t) => $t > time() - 3600);
if (count($hits) >= 5) ri_json_out(429, ['error' => 'Too many requests from this connection. Please try again later.']);
$hits[] = time(); @file_put_contents($rf, json_encode(array_values($hits)));

$s = ri_secrets();
$url = rtrim($s['supabase_url'] ?? '', '/'); $key = $s['supabase_secret'] ?? ($s['supabase_service_key'] ?? '');
$stored = false;
if ($url && $key) {
  $lead['source'] = 'assurestream.com'; $lead['ip_hash'] = hash('sha256', $ip . ($s['qx_salt'] ?? 'qx'));
  [$code] = ri_http($url . '/rest/v1/qx_leads', ['method' => 'POST', 'post' => json_encode($lead, JSON_UNESCAPED_UNICODE), 'headers' => ['apikey: ' . $key, 'Content-Type: application/json', 'Prefer: return=minimal']]);
  $stored = $code >= 200 && $code < 300;
}
$mailed = false;
if (!empty($s['qx_notify_email'])) {
  $body = "New enquiry from assurestream.com\n\n"; foreach ($lead as $k => $v) if ($k !== 'ip_hash') $body .= ucfirst($k) . ': ' . $v . "\n";
  $mailed = @mail($s['qx_notify_email'], 'AssureStream enquiry: ' . $lead['org'] . ' (' . $lead['need'] . ')', $body, "From: no-reply@assurestream.com\r\nReply-To: " . $lead['email']);
}
if (!$stored && !$mailed) ri_json_out(503, ['error' => 'The request could not be sent right now.']);
ri_json_out(200, ['ok' => true]);
