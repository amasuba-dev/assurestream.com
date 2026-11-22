<?php
/* Machine translation of the site for languages without a reviewed file in /i18n.
 * Translates the fixed list in /i18n/en.json once per language with the OpenAI key in ri-demo-secrets.php,
 * then serves the cached result. Visitors cannot send their own text, so the cost is bounded.
 * To publish a reviewed translation, save it as /i18n/<lang>.json; the page uses that file first. */
require __DIR__ . '/lib.php';
$NAMES = ['es' => 'Spanish', 'ar' => 'Modern Standard Arabic', 'zh' => 'Simplified Chinese', 'sw' => 'Swahili', 'de' => 'German', 'pt' => 'European Portuguese', 'fr' => 'French'];
$lang = $_GET['lang'] ?? '';
if (!isset($NAMES[$lang])) ri_json_out(404, ['error' => 'Language not offered']);
$static = dirname(__DIR__) . "/i18n/$lang.json";
if (is_readable($static)) { header('Cache-Control: public, max-age=3600'); ri_json_out(200, json_decode(file_get_contents($static), true)); }
$en = json_decode(@file_get_contents(dirname(__DIR__) . '/i18n/en.json'), true);
if (!is_array($en)) ri_json_out(500, ['error' => 'Source strings missing']);
$ver = substr(md5(json_encode($en)), 0, 10);
$cf = ri_cache_dir() . "/as_i18n_{$lang}_{$ver}.json";
if (is_readable($cf)) { header('Cache-Control: public, max-age=3600'); ri_json_out(200, json_decode(file_get_contents($cf), true)); }

// One translation at a time per language; others wait briefly, then get the English page.
$lock = fopen($cf . '.lock', 'c');
if (!flock($lock, LOCK_EX | LOCK_NB)) { ri_json_out(503, ['error' => 'Translation in progress, try again shortly']); }
set_time_limit(240);
$s = ri_secrets(); $key = $s['api_key'] ?? '';
if (!$key) ri_json_out(503, ['error' => 'Translation unavailable']);
$models = $s['models'] ?? (isset($s['model']) ? [$s['model']] : ['gpt-5.6-sol']);
$url = rtrim($s['base_url'] ?? 'https://api.openai.com/v1', '/') . '/chat/completions';
$sys = "You translate the website of AssureStream, AI quality assurance and agent-assist software for regulated contact centres, into {$NAMES[$lang]}. "
  . "Write the way a professional B2B technology company writes for executives in that language: clear, natural, concise, formal register. Keep product and company names unchanged: AssureStream, Agent Assist, TrainStream, Cisco Finesse, Qextec, Afrosoltech (Pty) Ltd, WhatsApp, IT acronyms such as ERP. "
  . "You receive a JSON object of id to English text. Return a JSON object with exactly the same ids and the translated text as values. Do not add or remove ids.";
$out = [];
foreach (array_chunk($en, 60, true) as $chunk) {
  $done = null;
  foreach ($models as $m) {
    $p = ['model' => $m, 'response_format' => ['type' => 'json_object'], 'max_completion_tokens' => 8000, 'messages' => [['role' => 'system', 'content' => $sys], ['role' => 'user', 'content' => json_encode((object)$chunk, JSON_UNESCAPED_UNICODE)]]];
    if (!empty($s['reasoning_effort'])) $p['reasoning_effort'] = $s['reasoning_effort'];
    [$code, $res] = ri_http($url, ['post' => json_encode($p), 'headers' => ['Content-Type: application/json', 'Authorization: Bearer ' . $key], 'timeout' => 120]);
    if ($code >= 200 && $code < 300) { $done = json_decode(json_decode($res, true)['choices'][0]['message']['content'] ?? '', true); break; }
  }
  if (!is_array($done)) ri_json_out(502, ['error' => 'Translation failed']);
  foreach ($chunk as $i => $text) if (isset($done[(string)$i]) && is_string($done[(string)$i]) && trim($done[(string)$i]) !== '') $out[$text] = trim($done[(string)$i]);
}
if (count($out) < count($en) * 0.9) ri_json_out(502, ['error' => 'Translation incomplete']);
file_put_contents($cf, json_encode($out, JSON_UNESCAPED_UNICODE));
header('Cache-Control: public, max-age=3600');
ri_json_out(200, $out);
