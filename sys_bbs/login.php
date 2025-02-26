<?php
    session_start(); // 添加session启动
    include __DIR__ . "/configs/config.php";

    $transfer = $_SESSION['transfer'];
    $error = ''; // 统一错误消息变量
    if($transfer){
        $error = $transfer;
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        $hashed_password = md5($password);

        // 验证输入
        if (empty($username) || empty($password)) {
            $error = '请输入用户名和密码';
        } else {
            // 修改后的SQL查询（只查询用户名）
            $sql = "SELECT id, name, username, password FROM users WHERE username = ?";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows == 1) {
                    $row = $result->fetch_assoc();
                    if ($hashed_password == $row['password']) {
                        $_SESSION['state'] = true;
                        $_SESSION['user_id'] = $row['id'];
                        $_SESSION['username'] = $row['username'];
                        $_SESSION['name'] = $row['name'];
                        $_SESSION['transfer'] = '登录成功';
                        header('Location: #index.php');
                    } else {
                        $error = '用户名或密码错误';
                    }
                } else {
                    $error = '用户名或密码错误';
                }
                $stmt->close();
            } else {
                $error = '数据库查询失败';
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
    <title>登录 - 知聊 BBS论坛</title>
    <link rel="stylesheet" href="static/login.css">
</head>
<body>
<div class="login-container">
    <h2>欢迎登录</h2>
    <?php if (!empty($error)): ?>
        <div class="error-message"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <input type="text" id="username" name="username" placeholder="请输入用户名" required autofocus>
        </div>

        <div class="form-group">
            <input type="password" id="password" name="password" placeholder="请输入密码" required>
        </div>

        <button type="submit">立即登录</button>
    </form>

    <div class="footer-links">
        <a href="#forgot-password">忘记密码？</a>
        <span> | </span>
        <a href="register.php">注册账号</a>
    </div>
</div>
</body>
</html>