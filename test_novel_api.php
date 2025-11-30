<?php
header("Content-type: text/html; charset=utf-8");
$domain = "http://reader.778878.vip";
echo "<h2>📡 域名反代测试</h2>";
echo "目标: $domain <br><hr>";

// 测试1: 首页 (证明反代通了)
$url1 = $domain . "/";
echo "正在连接首页: $url1 ... <br>";
$html = file_get_contents($url1);
if ($html) echo "<span style='color:green'>✅ 首页连接成功！长度: ".strlen($html)."</span><br><br>";
else echo "<span style='color:red'>❌ 首页连接失败</span><br><br>";

// 测试2: 获取书源列表 (这是一个已知的有效接口)
$url2 = $domain . "/reader3/getBookSources"; // 之前截图里这个是通的
echo "正在连接已知接口: $url2 ... <br>";
$json = file_get_contents($url2);
if ($json) {
    echo "<span style='color:green'>✅ API 连接成功！</span><br>";
    echo "返回数据片段: " . substr($json, 0, 100);
} else {
    echo "<span style='color:red'>❌ API 连接失败 (404或其他)</span>";
}
?>