<?php
session_start(); // 添加session初始化
include __DIR__ . "/configs/config.php";

$transfer = $_SESSION['transfer'];
$error = '';
if($transfer){
    $error = $transfer;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $password_verify = trim($_POST['password_verify']);
    $def_level = 1;

    // 输入验证
    if (empty($username) || empty($password)) {
        $error = '请输入用户名和密码！';
    } elseif (strlen($username) < 4) {
        $error = '用户名至少需要4个字符';
    } elseif (strlen($password) < 6) {
        $error = '密码长度至少需要6位';
    } elseif ($password != $password_verify) {
        $error = '两次输入的密码不一致！';
    } else {
        // 检查用户名是否存在
        $check_sql = "SELECT id FROM users WHERE username = ?";
        if ($check_stmt = $conn->prepare($check_sql)) {
            $check_stmt->bind_param("s", $username);
            $check_stmt->execute();
            $check_stmt->store_result();

            $usernameExists = false;
            if ($check_stmt->num_rows > 0) {
                $error = '该用户名已被注册';
                $usernameExists = true;
                $check_stmt->close();
            }else{
                $check_stmt->close();
            }
        }

        // 密码哈希处理
        $hashed_password = md5($password);

        // 插入用户数据
        if(!$usernameExists) {
            $sql = "INSERT INTO users (username, password, name, level) VALUES (?, ?, ?, ?)";
            if ($stmt = $conn->prepare($sql)) {
                $name = $username; // 默认使用用户名作为名称
                $stmt->bind_param("ssss", $username, $hashed_password, $name, $def_level);

                if ($stmt->execute()) {
                    // 自动登录处理
                    $new_user_id = $stmt->insert_id;

                    $_SESSION['state'] = true;
                    $_SESSION['user_id'] = $new_user_id;
                    $_SESSION['username'] = $username;
                    $_SESSION['name'] = $name;
                    $_SESSION['transfer'] = "用户注册成功，请继续登录";
                    header('Location: login.php');
                    exit();
                } else {
                    $error = '注册失败，请稍后再试';
                }

                $stmt->close();

            } else {
                $error = '数据库操作失败';
            }
        }
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>注册账号 - 知聊 BBS论坛</title>
    <link rel="stylesheet" href="static/login.css">
    <link rel="stylesheet" href="static/register.css">
</head>
<body>
<div class="login-container">
    <h2>注册新账号</h2>
    <?php if (!empty($error)): ?>
        <div class="error-message"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="" onsubmit="return validateForm()">
        <div class="form-group">
            <label for="username">用户名</label>
            <input type="text" id="username" name="username"
                   placeholder="4-20位字母/数字组合"
                   pattern="[a-zA-Z0-9]{4,20}"
                   required autofocus>
        </div>

        <div class="form-group">
            <label for="password">密码</label>
            <input type="password" id="password" name="password"
                   placeholder="至少6位密码"
                   minlength="6"
                   required>
            <div class="password-strength">密码强度：<span id="strength-text">-</span></div>
        </div>

        <div class="form-group">
            <label for="password_verify">确认密码</label>
            <input type="password" id="password_verify" name="password_verify"
                   placeholder="再次输入密码"
                   required>
            <div class="password-match">✓ 密码匹配</div>
            <div class="password-mismatch">✗ 密码不匹配</div>
        </div>

        <button type="submit" class="register-btn">立即注册</button>
    </form>

    <div class="footer-links">
        <a href="login.php">已有账号？立即登录</a>
    </div>
</div>

<script>
    // 实时密码验证
    const password = document.getElementById('password');
    const passwordVerify = document.getElementById('password_verify');
    const matchMsg = document.querySelector('.password-match');
    const mismatchMsg = document.querySelector('.password-mismatch');

    function checkPasswordMatch() {
        if (password.value && passwordVerify.value) {
            if (password.value === passwordVerify.value) {
                matchMsg.style.display = 'block';
                mismatchMsg.style.display = 'none';
            } else {
                matchMsg.style.display = 'none';
                mismatchMsg.style.display = 'block';
            }
        }
    }

    password.addEventListener('input', checkPasswordMatch);
    passwordVerify.addEventListener('input', checkPasswordMatch);

    // 密码强度检测
    password.addEventListener('input', function() {
        const strengthText = document.getElementById('strength-text');
        const strength = calculateStrength(this.value);
        strengthText.textContent = strength.text;
        strengthText.style.color = strength.color;
    });

    function calculateStrength(pw) {
        const hasLower = /[a-z]/.test(pw);
        const hasUpper = /[A-Z]/.test(pw);
        const hasNumber = /\d/.test(pw);
        const hasSpecial = /[!@#$%^&*]/.test(pw);

        let score = 0;
        if (pw.length >= 6) score++;
        if (pw.length >= 8) score++;
        if (hasLower && hasUpper) score++;
        if (hasNumber) score++;
        if (hasSpecial) score++;

        switch(score) {
            case 0: case 1:
                return {text: '弱', color: '#e53e3e'};
            case 2: case 3:
                return {text: '中', color: '#d69e2e'};
            default:
                return {text: '强', color: '#38a169'};
        }
    }

    function validateForm() {
        if (password.value !== passwordVerify.value) {
            alert('两次输入的密码不一致！');
            return false;
        }
        return true;
    }
</script>
</body>
</html>