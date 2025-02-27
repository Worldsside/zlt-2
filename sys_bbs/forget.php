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
    <title>注册账号 - 知聊 BBS论坛</title>
    <link rel="stylesheet" href="static/login.css">
    <link rel="stylesheet" href="static/register.css">
</head>
<body>
<div class="login-container">
    <h2>注册新账号</h2>
    <?php if (!empty($error)): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" id="regForm">
            <div id="stage2">
                <div class="form-group">
                    <label>验证手机号：<?= isset($_SESSION['reg_data']['phone']) ? substr_replace($_SESSION['reg_data']['phone'], '****', 3, 4) : '' ?></label>
                    <div class="verification-group" style="align-items: center">
                        <div>
                            <input type="text" id="verification-code" name="verification_code"
                                   placeholder="请输入6位验证码"
                                   pattern="\d{6}"
                                   required>
                        </div>
                        <button type="button" id="send-code-btn" onclick="sendVerificationCode()" style="width: 110px; height: 50px; align-items: center; font-size: 13px">
                            获取验证码
                        </button>
                    </div>
                    <div class="timer" id="timer"></div>
                </div>

                <input type="hidden" name="stage2" value="1">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <button type="submit" class="register-btn">修改密码</button>
            </div>
    </form>

    <div class="footer-links">
        <a href="login.php">已有账号？立即登录</a>
    </div>
</div>

<script>
    // 实时密码验证
    <?php if(!$showVerification) {?>
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

    <?php }?>



    function previousStage() {
        document.getElementById('stage2').style.display = 'none';
        document.getElementById('stage1').style.display = 'block';
    }

    // 验证码功能
    let countdown = 0;

    function sendVerificationCode() {
        // 如果倒计时中，禁止再次点击
        if (countdown > 0) return;

        const phone = <?php echo $_SESSION['reg_data']['phone'];?>;
        const btn = document.getElementById('send-code-btn');

        // 禁用按钮并开始倒计时
        btn.disabled = true
        countdown = 60;

        // 向后端请求验证码
        fetch('send_code.php?phone=' + phone)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 显示从后端获取到的验证码
                    alert('验证码已发送，验证码是：' + data.verification_code);
                    // 如果后端返回验证码，存储验证码供后续验证使用
                    sessionStorage.setItem('verification_code', data.verification_code);
                } else {
                    alert('验证码发送失败：' + data.message);
                }
            })
            .catch(error => {
                console.error('请求失败:', error);
                alert('发送验证码时出现问题');
            });

        // 更新按钮文本为倒计时
        const timer = setInterval(() => {
            btn.innerHTML = `重新发送(${countdown})`;
            countdown--;
            if (countdown < 0) {
                clearInterval(timer);
                btn.innerHTML = '获取验证码';
                btn.disabled = false;
            }
        }, 1000);
    }
</script>
</body>
</html>