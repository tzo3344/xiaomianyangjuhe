<?php
// detail.php - 无缓存稳定版 (解决权限报错)
ini_set('display_errors', 0); // 关闭报错显示，防止干扰页面
error_reporting(E_ALL ^ E_NOTICE);

// 1. 开启 Gzip
if (extension_loaded('zlib')) { ob_start('ob_gzhandler'); }

// 2. 引入核心
require $_SERVER['DOCUMENT_ROOT'] . '/core/db.php';
require $_SERVER['DOCUMENT_ROOT'] . '/api/collect.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$source_id = isset($_GET['source_id']) ? intval($_GET['source_id']) : 0;
$is_playing = isset($_GET['episode_index']);
$current_index = $is_playing ? intval($_GET['episode_index']) : 0;

if ($id == 0 || $source_id == 0) die("参数错误");

// === ⚡ 直接请求接口 (移除本地缓存，防止报错) ===
$data = fetch_source_data($source_id, ['ac' => 'detail', 'ids' => $id]);
$info = $data['list'][0] ?? null;

if (!$info) die("资源加载失败，请刷新重试");

// 数据处理
$type_name = $info['type_name'];
$is_novel = (strpos($type_name, '小说') !== false || strpos($type_name, '书') !== false);

$vod_play_url = $info['vod_play_url'];
$vod_pic = $info['vod_pic'];
$vod_name = $info['vod_name'];
$vod_content = strip_tags($info['vod_content']);
$vod_actor = $info['vod_actor'] ?: '全网热播';
$vod_remarks = $info['vod_remarks'] ?: '更新中';
$vod_year = $info['vod_year'] ?: '2025';
$vod_score = $info['vod_score'] ?: '9.0';

// 解析列表
$playlist = [];
$lines = explode('#', $vod_play_url);
foreach ($lines as $line) {
    $part = explode('$', $line);
    $playlist[] = ['name' => (count($part)>=2 ? $part[0] : '正片'), 'url' => (count($part)>=2 ? $part[1] : $part[0])];
}
$chapter_count = count($playlist);

// 播放链接
$play_url = "";
if ($is_playing && !$is_novel) {
    if ($current_index >= count($playlist)) $current_index = 0;
    $play_url = "/api/proxy.php?url=" . urlencode($playlist[$current_index]['url']);
}

// 记录历史
require_once $_SERVER['DOCUMENT_ROOT'] . '/core/tracker.php';
if ($is_playing) { record_history($id, $info['vod_name']); }
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $vod_name; ?></title>
    <link href="https://cdn.staticfile.org/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.staticfile.org/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css">
    <script src="https://cdn.staticfile.org/hls.js/1.1.5/hls.min.js"></script>
    <script src="https://cdn.staticfile.org/dplayer/1.26.0/DPlayer.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        /* === 🎨 统一淡蓝背景 === */
        body { 
            background: linear-gradient(120deg, #e0f2f1 0%, #e3f2fd 100%); 
            font-family: "PingFang SC", sans-serif; color: #333;
            padding-bottom: 50px; min-height: 100vh;
        }
        a { text-decoration: none; }

        /* === 🌀 呼吸加载球 === */
        #page-loader {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(120deg, #e0f2f1 0%, #e3f2fd 100%);
            z-index: 99999; 
            display: flex; flex-direction: column; justify-content: center; align-items: center;
            transition: opacity 0.4s ease-out, visibility 0.4s;
        }
        .loading-ball {
            width: 50px; height: 50px;
            background: linear-gradient(135deg, #0d6efd, #6ea8fe);
            border-radius: 50%;
            box-shadow: 0 10px 30px rgba(13, 110, 253, 0.4);
            animation: breathe 1.5s infinite ease-in-out;
        }
        .loading-text { margin-top: 15px; font-size: 13px; color: #666; letter-spacing: 1px; }
        @keyframes breathe {
            0%, 100% { transform: scale(0.9); box-shadow: 0 0 0 0 rgba(13, 110, 253, 0.6); }
            50% { transform: scale(1.1); box-shadow: 0 0 0 15px rgba(13, 110, 253, 0); }
        }

        /* === 交互特效 === */
        .hover-pop { transition: 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); cursor: pointer; }
        .hover-pop:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(13, 110, 253, 0.15) !important; z-index: 5; }
        .hover-pop:active { transform: scale(0.97); }

        /* 布局 */
        .container { max-width: 1000px; margin: 0 auto; padding-top: 20px; }
        .main-card {
            background: #fff; border-radius: 24px; padding: 30px;
            box-shadow: 0 10px 30px rgba(13, 110, 253, 0.05); 
            border: 2px solid #fff; margin-bottom: 20px;
        }

        /* 播放器 (纯黑) */
        .player-box {
            width: 100%; background-color: #000; height: 0; padding-bottom: 56.25%; 
            position: relative; border-radius: 16px; overflow: hidden;
            box-shadow: 0 15px 40px rgba(0,0,0,0.2); margin-bottom: 20px;
        }
        #dplayer { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }

        /* 详情内容 */
        .poster { width: 100%; border-radius: 16px; aspect-ratio: 2/3; object-fit: cover; box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
        .info-box { background: #f0f7ff; border-radius: 12px; padding: 10px; text-align: center; height: 100%; border: 1px solid #eef; }
        .info-label { font-size: 12px; color: #888; margin-bottom: 4px; }
        .info-val { font-size: 14px; font-weight: bold; color: #333; }
        .intro-box { background: #f8fbff; padding: 15px; border-radius: 16px; font-size: 13px; color: #666; line-height: 1.6; max-height: 80px; overflow-y: auto; margin-top: 20px; border: 1px solid #eef; }

        /* 按钮 */
        .btn-play { background: linear-gradient(135deg, #0d6efd 0%, #6ea8fe 100%); color: white !important; border-radius: 50px; padding: 12px; width: 100%; font-weight: bold; border: none; display: flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 8px 20px rgba(13, 110, 253, 0.3); }
        .btn-read { background: linear-gradient(135deg, #20c997 0%, #4be1b3 100%); color: white !important; border-radius: 50px; padding: 12px; width: 100%; font-weight: bold; border: none; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .btn-action { background: #fff; border: 1px solid #eef; color: #333; border-radius: 16px; padding: 12px 0; width: 100%; font-weight: 600; font-size: 14px; display: flex; align-items: center; justify-content: center; gap: 6px; box-shadow: 0 4px 10px rgba(0,0,0,0.03); }
        .btn-action:hover { background: #f8fbff; color: #0d6efd; border-color: #0d6efd; }

        /* 选集 */
        .ep-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(70px, 1fr)); gap: 10px; margin-top: 15px; }
        .ep-item { background: #f0f7ff; border-radius: 10px; text-align: center; padding: 10px 0; font-size: 13px; color: #555; display: block; text-decoration: none; border: 1px solid transparent; }
        .ep-item:hover { background: #e0f0ff; color: #0d6efd; border-color: #0d6efd; }
        .ep-item.active { background: #0d6efd; color: #fff; box-shadow: 0 5px 15px rgba(13, 110, 253, 0.3); }
        
        /* 滚动条 */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-thumb { background: #ccc; border-radius: 3px; }
        ::-webkit-scrollbar-track { background: transparent; }
    </style>
</head>
<body>

<div id="page-loader">
    <div class="loading-ball"></div>
    <div class="loading-text">资源加载中...</div>
</div>

<div class="container">
    
    <div class="mb-3">
        <a href="/" class="text-dark hover-pop d-inline-block px-3 py-2 bg-white rounded-pill shadow-sm small fw-bold transition-link">
            <i class="bi bi-arrow-left"></i> 返回首页
        </a>
    </div>

    <?php if ($is_playing && !$is_novel): ?>
        <div class="player-box">
            <div id="dplayer"></div>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-4 px-2">
            <h5 class="fw-bold mb-0 text-dark">正在播放：第 <?php echo $playlist[$current_index]['name']; ?> 集</h5>
            <a href="detail.php?id=<?php echo $id; ?>&source_id=<?php echo $source_id; ?>" class="btn btn-sm btn-light border hover-pop px-3 rounded-pill transition-link">退出播放</a>
        </div>
    <?php endif; ?>

    <?php if (!$is_playing): ?>
    <div class="main-card hover-pop">
        <div class="row">
            <div class="col-4 col-md-3">
                <img src="<?php echo $vod_pic; ?>" class="poster hover-pop" alt="封面">
            </div>
            <div class="col-8 col-md-9">
                <h3 class="fw-bold mb-2 text-dark"><?php echo $vod_name; ?></h3>
                <div class="mb-3">
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-10 px-2 py-1 rounded-3"><?php echo $info['type_name']; ?></span>
                    <span class="badge bg-secondary bg-opacity-10 text-secondary px-2 py-1 rounded-3"><?php echo $vod_year; ?></span>
                </div>
                
                <div class="row g-2">
                    <div class="col-4">
                        <div class="info-box hover-pop">
                            <div class="info-label"><?php echo $is_novel?'作者':'主演'; ?></div>
                            <div class="info-val text-truncate"><?php echo $vod_actor; ?></div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="info-box hover-pop">
                            <div class="info-label">状态</div>
                            <div class="info-val"><?php echo $vod_remarks; ?></div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="info-box hover-pop">
                            <div class="info-label">评分</div>
                            <div class="info-val text-warning"><?php echo $vod_score; ?></div>
                        </div>
                    </div>
                </div>

                <div class="intro-box hover-pop">
                    <strong>📝 剧情简介：</strong><br>
                    <?php echo $vod_content ?: '暂无详细简介...'; ?>
                </div>
            </div>
        </div>

        <div class="row g-2 mt-4">
            <div class="col-12 col-md-4">
                <?php if ($is_novel): ?>
                    <?php 
    // 判断是否为 Legado 聚合源 (ID以 LEGADO_ 开头)
    $is_legado = (strpos($info['vod_id'], 'LEGADO_') === 0);
    $read_link = $is_legado 
        ? "read_spider.php?url=" . urlencode($ep['url']) . "&title=" . urlencode($ep['name'])
        : "read.php?id=$id&source_id=$source_id&chapter=$index";
?>
<a href="<?php echo $read_link; ?>" class="ep-item hover-pop">
    <i class="bi bi-book-half"></i> 立即阅读
</a>
                <?php else: ?>
                    <a href="?id=<?php echo $id; ?>&source_id=<?php echo $source_id; ?>&type=video&episode_index=0" class="btn-play hover-pop transition-link"><i class="bi bi-play-fill fs-5"></i> 立即播放</a>
                <?php endif; ?>
            </div>
            <div class="col-6 col-md-4"><button class="btn-action hover-pop" onclick="toggleBook(this)"><i class="bi bi-bookmark"></i> 加入书架</button></div>
            <div class="col-6 col-md-4"><button class="btn-action hover-pop" onclick="downloadApp()"><i class="bi bi-cloud-arrow-down"></i> 下载全集</button></div>
        </div>
    </div>
    <?php endif; ?>

    <div class="main-card hover-pop">
        <h5 class="fw-bold border-start border-4 border-primary ps-2 mb-3 text-dark"><i class="bi bi-list-ul"></i> 列表</h5>
        <div class="ep-grid">
            <?php foreach ($playlist as $index => $ep): ?>
                <?php if ($is_novel): ?>
                    <a href="read.php?id=<?php echo $id; ?>&source_id=<?php echo $source_id; ?>&chapter=<?php echo $index; ?>" class="ep-item hover-pop">
   <?php echo $ep['name']; ?>
</a>
                <?php else: ?>
                    <a href="?id=<?php echo $id; ?>&source_id=<?php echo $source_id; ?>&type=video&episode_index=<?php echo $index; ?>" 
                       class="ep-item hover-pop <?php echo ($is_playing && $index==$current_index)?'active':''; ?> transition-link"><?php echo $ep['name']; ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php if ($is_playing && !$is_novel): ?>
<script>
    const dp = new DPlayer({
        container: document.getElementById('dplayer'),
        autoplay: true,
        theme: '#0d6efd',
        video: { url: '<?php echo $play_url; ?>', type: 'hls' },
    });
</script>
<?php endif; ?>

<script>
    window.addEventListener('load', function() {
        setTimeout(() => { document.getElementById('page-loader').style.opacity = '0'; setTimeout(() => document.getElementById('page-loader').style.display = 'none', 300); }, 300);
    });
    document.querySelectorAll('.transition-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const target = this.getAttribute('href');
            document.getElementById('page-loader').style.visibility = 'visible';
            document.getElementById('page-loader').style.opacity = '1';
            setTimeout(() => { window.location.href = target; }, 300);
        });
    });
    function toggleBook(btn) { Swal.fire({toast:true, position:'top', icon:'success', title:'已加入书架', timer:1500, showConfirmButton:false}); btn.innerHTML = '<i class="bi bi-bookmark-fill text-primary"></i> 已在书架'; btn.style.borderColor = '#0d6efd'; btn.style.color = '#0d6efd'; }
    function downloadApp() { Swal.fire({title:'下载APP', text:'高清无广告体验', icon:'info', confirmButtonText:'去下载', confirmButtonColor:'#0d6efd'}); }
</script>
</body>
</html>