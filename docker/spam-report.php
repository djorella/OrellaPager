<?php
// Monthly report of blocked posting attempts (bots, honeypot hits, failed
// captchas) built from REJECT_LOG, which index.php appends to. The report is
// written to REPORT_DIR (./logs on the host) and emailed to REPORT_EMAIL when SMTP is configured
// (see docker/entrypoint.sh). The raw log is deleted after it is summarised so
// attacker IPs are not kept longer than the reporting period.
// Run monthly by cron (see docker/certbot-renew.cron) or by hand:
//     docker compose exec web sh -c '. /etc/orellapager.env && php /usr/local/bin/spam-report.php'
$log   = getenv('REJECT_LOG') ?: '/var/log/orellapager/rejections.log';
$dir   = getenv('REPORT_DIR') ?: '/var/log/orellapager';
$to    = getenv('REPORT_EMAIL') ?: '';
$from  = getenv('SMTP_FROM') ?: 'orellapager@orellapager.me';
$now   = gmdate('Y-m-d H:i:s');
$stamp = gmdate('Y-m-d');

$rows = [];
if (is_file($log)) {
    foreach (file($log, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $f = explode("\t", $line, 4);
        if (count($f) >= 3) {
            $rows[] = ['ts' => $f[0], 'ip' => $f[1], 'reason' => $f[2], 'detail' => $f[3] ?? ''];
        }
    }
}

$byReason = $byDay = $byIp = $ipReasons = [];
foreach ($rows as $r) {
    $byReason[$r['reason']] = ($byReason[$r['reason']] ?? 0) + 1;
    $day = substr($r['ts'], 0, 10);
    $byDay[$day] = ($byDay[$day] ?? 0) + 1;
    $byIp[$r['ip']] = ($byIp[$r['ip']] ?? 0) + 1;
    $ipReasons[$r['ip']][$r['reason']] = true;
}
arsort($byReason); ksort($byDay); arsort($byIp);

$first = $rows ? $rows[0]['ts'] : $now;
$out  = "OrellaPager blocked posting attempts\n";
$out .= "Period: $first UTC to $now UTC\n";
$out .= "Total blocked attempts: " . count($rows) . " from " . count($byIp) . " IP address(es)\n\n";
if (!$rows) {
    $out .= "No blocked attempts were recorded in this period.\n";
} else {
    $out .= "By reason\n";
    foreach ($byReason as $k => $n) $out .= sprintf("  %-24s %6d\n", $k, $n);
    $out .= "\nBy day\n";
    foreach ($byDay as $k => $n) $out .= sprintf("  %s %6d\n", $k, $n);
    $out .= "\nTop IP addresses (max 25)\n";
    foreach (array_slice($byIp, 0, 25, true) as $ip => $n) {
        $out .= sprintf("  %-40s %6d  %s\n", $ip, $n, implode(',', array_keys($ipReasons[$ip])));
    }
    $out .= "\nLast 20 attempts\n";
    foreach (array_slice($rows, -20) as $r) {
        $out .= sprintf("  %s  %-40s %-22s %s\n", $r['ts'], $r['ip'], $r['reason'], $r['detail']);
    }
}
$out .= "\nReasons: honeypot = hidden form field filled (bot); no-captcha-token = posted without solving the captcha;\n"
      . "captcha-failed = hCaptcha rejected the token; captcha-wrong-hostname / captcha-stale-token = token not solved on this site or too old.\n";

@mkdir($dir, 0755, true);
$file = "$dir/spam-report-$stamp.txt";
file_put_contents($file, $out);
echo "spam-report: " . count($rows) . " blocked attempt(s) summarised in $file\n";

if ($to !== '') {
    $subject = 'OrellaPager spam report ' . gmdate('Y-m') . ' (' . count($rows) . ' blocked attempts)';
    $headers = "From: OrellaPager <$from>\r\nContent-Type: text/plain; charset=UTF-8\r\n";
    if (mail($to, $subject, $out, $headers)) {
        echo "spam-report: emailed to $to\n";
    } else {
        fwrite(STDERR, "spam-report: email to $to FAILED (check SMTP_* settings); report kept in $file\n");
    }
} else {
    echo "spam-report: REPORT_EMAIL not set, report only written to file\n";
}

// Raw log summarised: start fresh for the next period.
if (is_file($log)) {
    file_put_contents($log, '');
}
