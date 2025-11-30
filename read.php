<?php
// read.php - 沉浸式小说阅读器 (自动正文提取版)
error_reporting(0);
require $_SERVER['DOCUMENT_ROOT'] . '/core/db.php';
require $_SERVER['DOCUMENT_ROOT'] . '/api/collect.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$source_id = isset($_GET['source_id']) ? intval($_GET['source_id']) : 0;
$chapter_idx = isset($_GET['chapter']) ? intval($_GET['chapter']) : 0;

if ($id == 0 || $source_id == 0) die("参数错误");

// 1. 获取书籍详情 (带缓存)
// 这里我们简化逻辑，直接调 fetch，实际建议复用 detail.php 的缓存逻辑
$data = fetch_source_data($source_id, ['ac' => 'detail', 'ids' => $id]);
$info = $data['list'][0] ?? null;
if (!$info) die("书籍不存在");

// 2. 解析章节列表
$vod_play_url = $info['vod_play_url'];
$chapters = [];
$lines = explode('#', $vod_play_url);
foreach ($lines as $line) {
    $part = explode('$', $line);
    $chapters[] = [
        'name' => count($part) > 1 ? $part[0] : '第'.(count($chapters)+1).'章',
        'url'  => count($part) > 1 ? $part[1] : $part[0]
    ];
}

// 3. 确定当前章节
if ($chapter_idx < 0) $chapter_idx = 0;
if ($chapter_idx >= count($chapters)) $chapter_idx = count($chapters) - 1;
$current = $chapters[$chapter_idx];

// 4. 【核心】获取正文内容
// 如果 URL 是 http 开头，说明需要去采集内容
// 如果不是 http，说明内容就在 URL 字段里 (某些采集源是这样的)
$content = "正在加载内容...";
if (strpos($current['url'], 'http') === 0) {
    // 这是一个链接，我们需要去抓取它
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $current['url']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    // 自动跟随跳转
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $raw_html = curl_exec($ch);
    curl_close($ch);

    // 简单的正文提取算法 (针对通用小说站)
    // 1. 尝试转码 (防止乱码)
    $encoding = mb_detect_encoding($raw_html, ['UTF-8', 'GBK', 'GB2312']);
    if ($encoding != 'UTF-8') $raw_html = mb_convert_encoding($raw_html, 'UTF-8', $encoding);

    // 2. 提取 body
    preg_match('/<body[^>]*>(.*?)<\/body>/is', $raw_html, $matches);
    $body = $matches[1] ?? $raw_html;

    // 3. 过滤掉无用标签 (script, style, a)
    $body = preg_replace('/<(script|style|iframe)[^>]*>.*?<\/\1>/si', '', $body);
    $body = strip_tags($body, '<p><br><div>'); // 只保留段落标签

    // 4. 智能识别正文块 (找字数最多的 div)
    // 这里做一个简化处理，直接显示处理后的文本
    $content = $body; 
    
    // 如果抓取失败
    if (!$raw_html) $content = "内容获取失败，请尝试访问源链接：<a href='{$current['url']}' target='_blank'>点击跳转</a>";
} else {
    // 内容直接在字段里
    $content = $current['url'];
}

// 格式化内容：把换行符换成 <p>
$content = str_replace(["\r\n", "\n"], '<br>', $content);
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo $current['name']; ?> - <?php echo $info['vod_name']; ?></title>
    <link href="https://cdn.staticfile.org/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        /* === 沉浸式阅读器 UI === */
        :root {
            --bg-reader: #f6f4ec; /* 羊皮纸色 */
            --text-reader: #333;
            --font-size: 18px;
        }
        
        /* 夜间模式 */
        [data-theme="dark"] { --bg-reader: #1a1a1d; --text-reader: #888; }
        /* 护眼模式 */
        [data-theme="green"] { --bg-reader: #cce8cf; --text-reader: #0d2e12; }

        body {
            margin: 0; padding: 0;
            background-color: var(--bg-reader); color: var(--text-reader);
            font-family: "PingFang SC", "Microsoft YaHei", sans-serif;
            line-height: 1.8; transition: background 0.3s, color 0.3s;
        }

        /* 顶部工具栏 (默认隐藏，点击屏幕显示) */
        .toolbar-top {
            position: fixed; top: 0; left: 0; right: 0;
            background: rgba(255,255,255,0.95); box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 10px 15px; display: flex; justify-content: space-between; align-items: center;
            transform: translateY(-100%); transition: transform 0.3s; z-index: 999;
        }
        .toolbar-top.active { transform: translateY(0); }

        /* 底部工具栏 */
        .toolbar-bottom {
            position: fixed; bottom: 0; left: 0; right: 0;
            background: rgba(255,255,255,0.95); box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
            padding: 15px; display: flex; justify-content: space-around; align-items: center;
            transform: translateY(100%); transition: transform 0.3s; z-index: 999;
        }
        .toolbar-bottom.active { transform: translateY(0); }

        /* 阅读区域 */
        .read-area {
            max-width: 800px; margin: 0 auto; padding: 20px 20px 80px;
            min-height: 100vh; font-size: var(--font-size);
            text-align: justify; cursor: pointer;
        }
        .chapter-title { font-size: 1.4em; font-weight: bold; margin: 40px 0 30px; }
        
        /* 按钮 */
        .tool-btn { border: none; background: none; color: #333; font-size: 12px; display: flex; flex-direction: column; align-items: center; gap: 5px; cursor: pointer; }
        .tool-btn i { font-size: 20px; }
        .back-btn { font-size: 16px; color: #333; text-decoration: none; font-weight: bold; }

        /* 目录抽屉 */
        .drawer-mask { position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; display: none; }
        .drawer {
            position: fixed; top: 0; bottom: 0; left: 0; width: 80%; max-width: 300px;
            background: #fff; z-index: 1001; transform: translateX(-100%); transition: transform 0.3s;
            display: flex; flex-direction: column;
        }
        .drawer.active { transform: translateX(0); }
        .drawer-header { padding: 20px; border-bottom: 1px solid #eee; font-weight: bold; }
        .drawer-list { flex: 1; overflow-y: auto; padding: 10px; }
        .drawer-item { 
            display: block; padding: 12px 10px; border-bottom: 1px solid #f5f5f5; 
            color: #333; text-decoration: none; font-size: 14px; 
        }
        .drawer-item.active { color: #0d6efd; font-weight: bold; }

        /* 上一章/下一章 浮动按钮 (底部常驻) */
        .nav-float {
            display: flex; justify-content: space-between; margin-top: 50px; gap: 20px;
        }
        .nav-btn {
            flex: 1; padding: 12px; border: 1px solid #ddd; background: transparent;
            border-radius: 50px; text-align: center; text-decoration: none; color: inherit;
            font-size: 14px;
        }
        .nav-btn:hover { background: rgba(0,0,0,0.05); }

    </style>
</head>
<body onclick="toggleToolbar()">

    <div class="toolbar-top" id="topBar" onclick="event.stopPropagation()">
        <a href="detail.php?id=<?php echo $id; ?>&source_id=<?php echo $source_id; ?>" class="back-btn"><i class="bi bi-chevron-left"></i> 返回详情</a>
        <button class="tool-btn" onclick="alert('加入书架')"><i class="bi bi-bookmark"></i></button>
    </div>

    <div class="read-area">
        <div class="chapter-title"><?php echo $current['name']; ?></div>
        
        <div id="content">
            <?php echo $content; ?>
        </div>

        <div class="nav-float" onclick="event.stopPropagation()">
            <?php if($chapter_idx > 0): ?>
                <a href="?id=<?php echo $id; ?>&source_id=<?php echo $source_id; ?>&chapter=<?php echo $chapter_idx-1; ?>" class="nav-btn">上一章</a>
            <?php endif; ?>
            
            <?php if($chapter_idx < count($chapters)-1): ?>
                <a href="?id=<?php echo $id; ?>&source_id=<?php echo $source_id; ?>&chapter=<?php echo $chapter_idx+1; ?>" class="nav-btn">下一章</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="toolbar-bottom" id="bottomBar" onclick="event.stopPropagation()">
        <button class="tool-btn" onclick="openDrawer()"><i class="bi bi-list-ul"></i><span>目录</span></button>
        <button class="tool-btn" onclick="changeTheme('light')"><i class="bi bi-brightness-high"></i><span>日间</span></button>
        <button class="tool-btn" onclick="changeTheme('green')"><i class="bi bi-eye"></i><span>护眼</span></button>
        <button class="tool-btn" onclick="changeTheme('dark')"><i class="bi bi-moon"></i><span>夜间</span></button>
        <button class="tool-btn" onclick="changeFont()"><i class="bi bi-type"></i><span>字体</span></button>
    </div>

    <div class="drawer-mask" id="drawerMask" onclick="closeDrawer()"></div>
    <div class="drawer" id="drawer">
        <div class="drawer-header">目录 (<?php echo count($chapters); ?>章)</div>
        <div class="drawer-list">
            <?php foreach ($chapters as $index => $ch): ?>
                <a href="?id=<?php echo $id; ?>&source_id=<?php echo $source_id; ?>&chapter=<?php echo $index; ?>" 
                   class="drawer-item <?php echo $index==$chapter_idx?'active':''; ?>">
                   <?php echo $ch['name']; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

<script>
    function toggleToolbar() {
        document.getElementById('topBar').classList.toggle('active');
        document.getElementById('bottomBar').classList.toggle('active');
    }

    function changeTheme(theme) {
        if (theme === 'light') document.body.removeAttribute('data-theme');
        else document.body.setAttribute('data-theme', theme);
    }

    let fontSize = 18;
    function changeFont() {
        fontSize = (fontSize >= 24) ? 16 : fontSize + 2;
        document.documentElement.style.setProperty('--font-size', fontSize + 'px');
    }

    function openDrawer() {
        document.getElementById('drawer').classList.add('active');
        document.getElementById('drawerMask').style.display = 'block';
    }
    function closeDrawer() {
        document.getElementById('drawer').classList.remove('active');
        document.getElementById('drawerMask').style.display = 'none';
    }
</script>

</body>
</html>