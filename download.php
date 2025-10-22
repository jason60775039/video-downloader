<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);
header('Content-Type: application/json');

$PASSWORD = 'y'; // 请修改成你自己的密码

$inputPassword = $_POST['password'] ?? $_GET['password'] ?? '';

if ($inputPassword !== $PASSWORD) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => '认证失败']);
    exit;
}

$outputDir = __DIR__ . '/downloads/';
if (!file_exists($outputDir)) mkdir($outputDir, 0775, true);

// 自动清理10分钟前的旧文件
$files = glob($outputDir . '*');
$now = time();
foreach ($files as $file) {
    if (is_file($file) && ($now - filemtime($file) > 600)) {
        unlink($file);
    }
}

// 轮询状态接口
if (isset($_GET['task_id']) && !empty($_GET['task_id'])) {
    $taskId = preg_replace('/[^a-zA-Z0-9_\.-]/', '', $_GET['task_id']);
    $taskFile = $outputDir . $taskId . '.json';
    $logFile = $outputDir . $taskId . '.log';

    if (!file_exists($taskFile)) {
        echo json_encode(['success' => false, 'message' => '任务不存在']);
        exit;
    }

    $taskData = json_decode(file_get_contents($taskFile), true);
    if (!$taskData) {
        echo json_encode(['success' => false, 'message' => '任务数据损坏']);
        exit;
    }

    $pid = $taskData['pid'] ?? 0;
    $running = false;
    if ($pid > 0) {
        $running = posix_getpgid($pid) !== false;
    }

    // 查找已下载文件
    $files = glob($outputDir . $taskId . '.*');
    $downloadedFile = null;
    foreach ($files as $f) {
        $ext = pathinfo($f, PATHINFO_EXTENSION);
        if ($ext !== 'log' && $ext !== 'json') {
            $downloadedFile = basename($f);
            break;
        }
    }

    if (!$running && $downloadedFile) {
        echo json_encode([
            'success' => true,
            'status' => 'finished',
            'download_link' => 'downloads/' . $downloadedFile,
        ]);
        exit;
    } elseif (!$running && !$downloadedFile) {
        $logContent = file_exists($logFile) ? file_get_contents($logFile) : '';
        echo json_encode([
            'success' => false,
            'status' => 'failed',
            'message' => '任务失败或无输出文件',
            'log' => $logContent,
        ]);
        exit;
    } else {
        $logContent = file_exists($logFile) ? file_get_contents($logFile) : '';
        $logTail = mb_substr($logContent, -1000);
        echo json_encode([
            'success' => true,
            'status' => 'running',
            'log' => $logTail,
        ]);
        exit;
    }
}

// 启动下载任务
if (!isset($_POST['url']) || empty($_POST['url'])) {
    echo json_encode(['success' => false, 'message' => '缺少URL']);
    exit;
}

$url_raw = $_POST['url'];
$url_clean = stripslashes($url_raw);
$url_esc = escapeshellarg($url_clean);

$audioOnly = isset($_POST['audioOnly']) && $_POST['audioOnly'] === '1';

$uuid = uniqid('yt_', true);
$outputTemplate = $outputDir . $uuid . '.%(ext)s';

$ytDlpPath = '/usr/local/bin/yt-dlp';
$cacheDir = '/tmp/yt-dlp-cache';
$cookiesFile = '/var/www/wordpress/yt-dlp/cookies.txt';

if (!file_exists($cacheDir)) mkdir($cacheDir, 0777, true);

if ($audioOnly) {
    $cmd = "$ytDlpPath -x --audio-format mp3 --no-playlist --no-mtime --cache-dir " . escapeshellarg($cacheDir) .
           " --cookies " . escapeshellarg($cookiesFile) .
           " -o " . escapeshellarg($outputTemplate) . " $url_esc";
} else {
    $cmd = "$ytDlpPath --no-playlist --no-mtime --cache-dir " . escapeshellarg($cacheDir) .
           " --cookies " . escapeshellarg($cookiesFile) .
           " -o " . escapeshellarg($outputTemplate) . " $url_esc";
}

$logFile = $outputDir . $uuid . '.log';
$taskFile = $outputDir . $uuid . '.json';

// 后台执行命令，输出到日志文件，& 让进程后台运行，echo $! 输出进程PID
$fullCmd = "$cmd > " . escapeshellarg($logFile) . " 2>&1 & echo $!";

$pid = (int)shell_exec($fullCmd);

file_put_contents($taskFile, json_encode([
    'pid' => $pid,
    'start_time' => time(),
    'url' => $url_clean,
    'audioOnly' => $audioOnly,
]));

echo json_encode([
    'success' => true,
    'task_id' => $uuid,
]);
