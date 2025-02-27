<?php
session_start();
$_SESSION['transfer'] = '';

// 检查是否传递了手机号
if (!isset($_GET['phone'])) {
    echo json_encode(['success' => false, 'message' => '手机号不能为空']);
    exit();
}

$phone = $_GET['phone'];

// 验证手机号格式（可根据实际需要做更严格的验证）
if (!preg_match("/^1[3-9]\d{9}$/", $phone)) {
    echo json_encode(['success' => false, 'message' => '无效的手机号']);
    exit();
}

// 生成6位验证码
$verification_code = rand(100000, 999999);

// 将验证码和过期时间存储在 session 中
$_SESSION['verification_code'] = $verification_code;
$_SESSION['verification_code_expire'] = time() + 300; // 5分钟有效期

// 模拟发送验证码到手机号
// 在实际应用中，这里可以通过短信接口将验证码发送到用户手机
// 这里省略实际短信发送代码，假设发送成功

// 返回验证码给前端（测试时通过alert显示）
echo json_encode([
    'success' => true,
    'message' => '验证码已发送',
    'verification_code' => $verification_code // 将验证码发送到前端，后续前端可以展示
]);

?>
