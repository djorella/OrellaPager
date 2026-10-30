<?php
// Removes visitor IPs that were recorded more than RETENTION_DAYS (30) days
// ago and never posted a beep. Their pings go with them. Before deleting,
// the rows are appended to PURGE_LOG so there is a record of what was removed.
// Run daily by cron inside the web container (see docker/certbot-renew.cron).
$days = (int) (getenv('RETENTION_DAYS') ?: 30);
$log  = getenv('PURGE_LOG') ?: '/var/log/orellapager/purged-ips.log';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = mysqli_connect(getenv('DB_HOST') ?: 'localhost', getenv('DB_USER') ?: 'root',
                       getenv('DB_PASS') ?: '', getenv('DB_NAME') ?: 'orellapager');

mysqli_begin_transaction($conn);
$stmt = mysqli_prepare($conn,
    "select u.uid, u.ip, u.created_at from users u
      where u.created_at < now() - interval ? day
        and not exists (select 1 from beeps b where b.uid = u.uid)
      order by u.created_at, u.uid for update");
mysqli_stmt_bind_param($stmt, 'i', $days);
mysqli_stmt_execute($stmt);
$stale = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

if (!$stale) {
    mysqli_rollback($conn);
    echo "purge-stale-ips: nothing older than $days days without beeps\n";
    exit(0);
}

$now   = gmdate('Y-m-d H:i:s');
$lines = "==== $now UTC: purged " . count($stale) . " IP(s) first seen more than $days days ago with no beeps ====\n";
$lines .= "uid\tip\tfirst_seen\n";
foreach ($stale as $r) {
    $lines .= "{$r['uid']}\t{$r['ip']}\t{$r['created_at']}\n";
}
$lines .= "\n";

@mkdir(dirname($log), 0750, true);
if (file_put_contents($log, $lines, FILE_APPEND | LOCK_EX) === false) {
    mysqli_rollback($conn);
    fwrite(STDERR, "purge-stale-ips: cannot write $log, nothing deleted\n");
    exit(1);
}

$uids = array_map(fn($r) => (int) $r['uid'], $stale);
$in   = implode(',', $uids);
mysqli_query($conn, "delete from pings where uid in ($in) or beeper in ($in)");
mysqli_query($conn, "delete from users where uid in ($in)");
mysqli_commit($conn);
echo "purge-stale-ips: removed " . count($uids) . " IP(s), logged to $log\n";
