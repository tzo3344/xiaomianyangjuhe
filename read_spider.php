<?php
// read_spider.php - 适配 novel.php 接口
require $_SERVER['DOCUMENT_ROOT'] . '/core/db.php';

$url = isset($_GET['url']) ? $_GET['url'] : ''; 
$title = isset($_GET['title']) ? $_GET['title'] : '阅读';

// 调用本地 API 获取正文
$api_url = "http://" . $_SERVER['HTTP_HOST'] . "/api/novel.php?ac=content&url=" . urlencode($url);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$json = curl_exec($ch);
curl_close($ch);

$data = json_decode($json, true);
$content = $data['content'] ?? '<div style="text-align:center;padding:50px;color:#999">正在加载正文...<br>如果是第一次打开，可能需要几秒钟抓取</div><script>setTimeout(function(){location.reload()}, 3000);</script>';
?>

<!DOCTYPE html>
<html lang="zh-CN" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link href="https://cdn.staticfile.org/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root { --bg: #f6f4ec; --text: #333; }
        [data-theme="dark"] { --bg: #1a1a1d; --text: #888; }
        body { background: var(--bg); color: var(--text); font-family: sans-serif; line-height: 1.8; padding: 20px; font-size: 18px; transition: 0.3s; }
        .content { max-width: 800px; margin: 0 auto; text-align: justify; min-height: 80vh; padding-bottom: 60px;}
        .title { font-weight: bold; font-size: 24px; margin-bottom: 30px; border-bottom: 1px solid #ddd; padding-bottom: 10px; }
        .bar { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(255,255,255,0.95); padding: 12px; display: flex; justify-content: space-around; box-shadow: 0 -2px 10px rgba(0,0,0,0.1); backdrop-filter: blur(5px); }
        .btn { border: none; background: none; font-size: 12px; display: flex; flex-direction: column; align-items: center; color: #666; cursor: pointer; }
        .btn i { font-size: 20px; margin-bottom: 2px; }
    </style>
</head>
<body>
    <div class="content">
        <div class="title"><?php echo $title; ?></div>
        <div id="article"><?php echo $content; ?></div>
    </div>
    
    <div class="bar">
        <button class="btn" onclick="history.back()"><i class="bi bi-arrow-left"></i>返回</button>
        <button class="btn" onclick="toggleTheme()"><i class="bi bi-moon"></i>夜间</button>
        <button class="btn" onclick="location.reload()"><i class="bi bi-arrow-clockwise"></i>刷新</button>
    </div>

    <script>
        function toggleTheme() {
            let body = document.body;
            let theme = body.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            body.setAttribute('data-theme', theme);
        }
    </script>
</body>
</html>