<?php
// test_db.php - 数据库连接诊断脚本
ini_set('display_errors', 1);
error_reporting(E_ALL);
header("Content-type: text/html; charset=utf-8");

echo "<h2>🕵️‍♂️ 数据库连接自检程序</h2>";

// 1. 检查文件位置
echo "<p>1. 正在寻找数据库配置文件...</p>";
$file = __DIR__ . '/core/db.php';
if (file_exists($file)) {
    echo "<p style='color:green'>✅ 找到了文件: core/db.php</p>";
} else {
    die("<p style='color:red'>❌ 致命错误：找不到 /core/db.php 文件！请检查 core 文件夹是否存在。</p>");
}

// 2. 尝试加载
echo "<p>2. 正在加载配置...</p>";
require $file;
echo "<p style='color:green'>✅ 加载成功！</p>";

// 3. 测试连接
echo "<p>3. 正在尝试连接 MySQL...</p>";
if (isset($conn) && !$conn->connect_error) {
    echo "<h3 style='color:green'>🎉 恭喜！数据库连接完全正常！</h3>";
    echo "<p>如果注册还是 500 错误，那就是 api/user_action.php 代码里有语法错误。</p>";
} else {
    echo "<h3 style='color:red'>❌ 连接失败</h3>";
    if (isset($conn)) {
        echo "错误信息: " . $conn->connect_error;
    } else {
        echo "变量 \$conn 不存在，请检查 core/db.php 代码是否正确。";
    }
}
?>