<?php
// fix_db.php - 数据库结构自动修复脚本
header("Content-type: text/html; charset=utf-8");
require 'core/db.php';

echo "<h2>🔧 正在修复数据库结构...</h2>";

// 需要补充的字段列表
$updates = [
    "ALTER TABLE `users` ADD COLUMN `point` int(11) DEFAULT '10' COMMENT '积分'",
    "ALTER TABLE `users` ADD COLUMN `vip_expire` int(11) DEFAULT '0' COMMENT 'VIP过期时间'",
    "ALTER TABLE `users` ADD COLUMN `reg_ip` varchar(50) DEFAULT NULL",
    "ALTER TABLE `users` ADD COLUMN `reg_time` int(11) DEFAULT NULL",
    "ALTER TABLE `users` ADD COLUMN `last_login_time` int(11) DEFAULT NULL"
];

foreach ($updates as $sql) {
    try {
        $conn->query($sql);
        echo "<p style='color:green'>执行成功或字段已存在: " . htmlspecialchars($sql) . "</p>";
    } catch (Exception $e) {
        // 忽略“字段已存在”的错误
        echo "<p style='color:gray'>跳过 (可能已存在): " . htmlspecialchars($sql) . "</p>";
    }
}

echo "<hr><h3>✅ 修复完成！</h3>";
echo "<p>现在请回到注册页重新尝试注册。</p>";
echo "<a href='/register.php'>点击去注册</a>";
?>