<?php
// index.php - 修复 PHP 版本兼容性问题
ini_set('display_errors', 0);
error_reporting(E_ALL ^ E_NOTICE);

require $_SERVER['DOCUMENT_ROOT'] . '/core/db.php';
require $_SERVER['DOCUMENT_ROOT'] . '/api/collect.php';

if (session_status() == PHP_SESSION_NONE) session_start();

// === 1. 用户信息 ===
$is_login = isset($_SESSION['user_id']);
$user_display = $is_login ? ($_SESSION['user_email'] ?: $_SESSION['user_name']) : '未登录';
if ($is_login && strpos($user_display, '@') !== false) {
    $parts = explode('@', $user_display);
    $user_display = substr($parts[0], 0, 3) . '****@' . $parts[1];
}

// === 2. 参数处理 ===
$current_type = isset($_GET['type']) ? $_GET['type'] : 'video'; 
$source_id = isset($_GET['source_id']) ? intval($_GET['source_id']) : 0;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$wd = isset($_GET['wd']) ? trim($_GET['wd']) : '';

// === 3. 获取源 ===
$sources_video = [];
$sources_novel = [];
$all_sources = $conn->query("SELECT * FROM collect_sources ORDER BY id ASC");
while ($row = $all_sources->fetch_assoc()) {
    if ($row['type'] == 'video') $sources_video[] = $row;
    else $sources_novel[] = $row;
}

// 默认源逻辑
if ($source_id == 0) {
    $default = ($current_type == 'video') ? ($sources_video[0] ?? []) : ($sources_novel[0] ?? []);
    $source_id = $default['id'] ?? 0;
    $current_source_name = $default['name'] ?? '默认源';
} else {
    // === 🛠️ 修复点：使用 array_merge 替代 [...] 语法，兼容旧版 PHP ===
    $all_merged = array_merge($sources_video, $sources_novel);
    foreach($all_merged as $s) {
        if ($s['id'] == $source_id) {
            $current_source_name = $s['name'];
            $current_type = $s['type'];
            break;
        }
    }
}

// === 4. 获取数据 & 截取9个 ===
$list = [];
if ($source_id) {
    if ($wd) {
        $data = fetch_source_data($source_id, ['ac' => 'list', 'wd' => $wd, 'pg' => $page]);
    } else {
        $data = fetch_source_data($source_id, ['ac' => 'detail', 'pg' => $page]);
    }
    $raw_list = $data['list'] ?? [];
    $list = array_slice($raw_list, 0, 9); 
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>聚合资源网</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #5473e8; 
            --bg-body: #f5f6f8;
            --text-main: #333;
            --text-sub: #888;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; outline: none; -webkit-tap-highlight-color: transparent; }
        body { background: var(--bg-body); font-family: "PingFang SC", sans-serif; color: var(--text-main); padding-bottom: 80px; }
        a { text-decoration: none; color: inherit; }

        /* Header */
        .header { background: #fff; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.03); }
        .logo { font-size: 18px; font-weight: bold; color: var(--primary); display: flex; align-items: center; gap: 8px; cursor: pointer; }
        .header-right { display: flex; align-items: center; gap: 15px; font-size: 14px; color: #666; }
        .user-icon { width: 32px; height: 32px; background: var(--primary); color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; }

        /* Tabs */
        .nav-tabs { display: flex; justify-content: center; gap: 15px; margin: 20px 0; }
        .tab-btn { 
            padding: 8px 25px; border-radius: 6px; background: #fff; color: #333; font-size: 14px; cursor: pointer; transition: 0.2s; 
            border: 1px solid transparent;
        }
        .tab-btn:hover { color: var(--primary); }
        .tab-btn.active { background: var(--primary); color: #fff; box-shadow: 0 4px 10px rgba(84, 115, 232, 0.3); }

        /* Search */
        .search-container { max-width: 700px; margin: 0 auto 20px; position: relative; }
        .search-box { 
            background: #fff; border-radius: 8px; padding: 6px; display: flex; align-items: center; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .search-icon { padding: 0 15px; color: #999; }
        .search-input { flex: 1; border: none; height: 40px; font-size: 15px; width: 100%; }
        .search-btn { 
            width: 40px; height: 40px; background: var(--primary); color: #fff; border: none; border-radius: 6px; cursor: pointer; 
            display: flex; align-items: center; justify-content: center; font-size: 16px;
        }

        /* Crumb */
        .crumb-bar { max-width: 1200px; margin: 0 auto 15px; padding: 0 20px; font-size: 13px; color: #666; }
        .crumb-link { color: var(--primary); cursor: pointer; margin-left: 5px; }

        /* Grid */
        .grid-container { 
            max-width: 1200px; margin: 0 auto; padding: 0 20px;
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; 
        }
        @media (max-width: 768px) {
            .grid-container { grid-template-columns: 1fr; }
            .header-right span { display: none; } 
        }

        /* Card */
        .card { 
            background: #fff; border-radius: 10px; padding: 15px; display: flex; gap: 15px; cursor: pointer; transition: 0.3s; border: 1px solid transparent;
        }
        .card:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); border-color: var(--primary); }
        .card-cover { width: 90px; height: 120px; border-radius: 6px; object-fit: cover; background: #eee; flex-shrink: 0; }
        .card-info { flex: 1; display: flex; flex-direction: column; justify-content: flex-start; min-width: 0; }
        .card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6px; }
        .card-title { font-size: 16px; font-weight: bold; color: #333; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 70%; }
        .card-badge { background: var(--primary); color: #fff; font-size: 11px; padding: 2px 6px; border-radius: 4px; }
        .card-meta { font-size: 12px; color: #999; margin-bottom: 8px; display: flex; align-items: center; gap: 5px; }
        .card-tag { background: #eef2ff; color: var(--primary); padding: 2px 6px; border-radius: 4px; font-size: 11px; }

        /* Pagination */
        .pagination { text-align: center; margin: 30px 0; }
        .page-btn { 
            display: inline-block; padding: 8px 25px; background: #fff; border: 1px solid #ddd; border-radius: 20px; 
            color: #666; cursor: pointer; font-size: 14px; margin: 0 10px; transition: 0.2s;
        }
        .page-btn:hover { border-color: var(--primary); color: var(--primary); }

        /* Bottom Nav */
        .bottom-nav {
            position: fixed; bottom: 0; left: 0; right: 0; height: 60px; background: #fff;
            border-top: 1px solid #eee; display: flex; justify-content: space-around; align-items: center;
            z-index: 100;
        }
        .nav-item { text-align: center; color: #999; font-size: 11px; cursor: pointer; flex: 1; }
        .nav-item i { font-size: 22px; display: block; margin-bottom: 3px; }
        .nav-item.active { color: var(--primary); }

        /* Panel */
        .panel-mask { position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 200; display: none; }
        .panel { position: fixed; bottom: 0; left: 0; right: 0; background: #fff; border-radius: 20px 20px 0 0; padding: 20px; transform: translateY(100%); transition: 0.3s; z-index: 201; max-height: 60vh; overflow-y: auto; }
        .panel.show { transform: translateY(0); }
        .source-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 15px; }
        .source-btn { padding: 10px; text-align: center; background: #f5f5f5; border-radius: 8px; font-size: 13px; cursor: pointer; }
        .source-btn.active { background: #eef2ff; color: var(--primary); border: 1px solid var(--primary); }
    </style>
</head>
<body>

<div class="header">
    <div class="logo" onclick="location.reload()">
        <i class="fas fa-book-open"></i> 小绵羊聚合网
    </div>
    <div class="header-right" onclick="location.href='<?php echo $is_login ? '/user/index.php' : '/login.php'; ?>'" style="cursor:pointer">
        <span><?php echo $user_display; ?></span>
        <div class="user-icon"><i class="fas fa-user"></i></div>
    </div>
</div>

<div class="nav-tabs">
    <button class="tab-btn <?php echo $current_type=='novel'?'active':''; ?>" onclick="location.href='?type=novel'">小说</button>
    <button class="tab-btn <?php echo $current_type=='audio'?'active':''; ?>" onclick="alert('听书功能开发中')">听书</button>
    <button class="tab-btn <?php echo $current_type=='comic'?'active':''; ?>" onclick="alert('漫画功能开发中')">漫画</button>
    <button class="tab-btn <?php echo $current_type=='video'?'active':''; ?>" onclick="location.href='?type=video'">短剧</button>
</div>

<div class="search-container">
    <div class="search-box">
        <i class="fas fa-search search-icon"></i>
        <input type="text" id="wd" class="search-input" placeholder="搜索书名、作者..." value="<?php echo htmlspecialchars($wd); ?>" onkeypress="if(event.keyCode==13) doSearch()">
        <button class="search-btn" onclick="doSearch()"><i class="fas fa-search"></i></button>
    </div>
</div>

<div class="crumb-bar">
    当前分类：<span class="crumb-link" onclick="openPanel()"><?php echo $current_source_name; ?>-必看榜</span>
</div>

<div class="grid-container">
    <?php if(empty($list)): ?>
        <div style="grid-column: 1/-1; text-align: center; padding: 60px; color: #999;">
            <i class="fas fa-box-open" style="font-size: 40px; margin-bottom: 10px;"></i>
            <p>暂无数据，请切换源或检查配置</p>
        </div>
    <?php else: ?>
        <?php foreach ($list as $item): 
            $pic = $item['vod_pic'] ?: 'https://via.placeholder.com/150x200?text=NoCover';
            $status = $item['vod_remarks'] ?: '连载';
            $author = $item['vod_actor'] ?: '未知';
        ?>
        <div class="card" onclick="location.href='detail.php?id=<?php echo $item['vod_id']; ?>&source_id=<?php echo $source_id; ?>'">
            <img src="<?php echo $pic; ?>" class="card-cover" loading="lazy">
            <div class="card-info">
                <div class="card-header">
                    <div class="card-title"><?php echo $item['vod_name']; ?></div>
                    <span class="card-badge"><?php echo $current_source_name; ?></span>
                </div>
                <div class="card-meta">
                    <i class="fas fa-user"></i> <?php echo $author; ?>
                </div>
                <div class="card-meta">
                    <i class="fas fa-info-circle"></i> <?php echo $status; ?>
                </div>
                <div style="margin-top: auto;">
                    <span class="card-tag"><?php echo $item['type_name'] ?: '精选'; ?></span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="pagination">
    <?php if($page > 1): ?>
        <button class="page-btn" onclick="goPage(<?php echo $page-1; ?>)">上一页</button>
    <?php endif; ?>
    <button class="page-btn" onclick="goPage(<?php echo $page+1; ?>)">下一页</button>
</div>

<div class="bottom-nav">
    <div class="nav-item active" onclick="location.href='/'">
        <i class="fas fa-home"></i> 首页
    </div>
    <div class="nav-item" onclick="openPanel()">
        <i class="fas fa-server"></i> 平台
    </div>
    <div class="nav-item" onclick="alert('分类页面开发中')">
        <i class="fas fa-th-large"></i> 分类
    </div>
    <div class="nav-item" onclick="location.href='/user/index.php'">
        <i class="fas fa-bookmark"></i> 书架
    </div>
</div>

<div class="panel-mask" id="mask" onclick="closePanel()"></div>
<div class="panel" id="panel">
    <div style="display:flex; justify-content:space-between; font-weight:bold; margin-bottom:10px;">
        <span>切换书源平台</span>
        <i class="fas fa-times" onclick="closePanel()" style="cursor:pointer"></i>
    </div>
    <div class="source-grid">
        <?php 
        $display_srcs = ($current_type == 'video') ? $sources_video : $sources_novel;
        foreach ($display_srcs as $src): 
        ?>
        <div class="source-btn <?php echo ($src['id']==$source_id)?'active':''; ?>" 
             onclick="location.href='?type=<?php echo $current_type; ?>&source_id=<?php echo $src['id']; ?>'">
            <?php echo $src['name']; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function doSearch() {
    const wd = document.getElementById('wd').value;
    location.href = `?type=<?php echo $current_type; ?>&source_id=<?php echo $source_id; ?>&wd=` + encodeURIComponent(wd);
}
function goPage(p) {
    let url = new URL(window.location.href);
    url.searchParams.set('page', p);
    window.location.href = url.toString();
}
function openPanel() {
    document.getElementById('mask').style.display = 'block';
    setTimeout(() => document.getElementById('panel').classList.add('show'), 10);
}
function closePanel() {
    document.getElementById('panel').classList.remove('show');
    setTimeout(() => document.getElementById('mask').style.display = 'none', 300);
}
</script>

</body>
</html>