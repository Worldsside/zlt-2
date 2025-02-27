<?php
session_start();
include __DIR__ . "/configs/config.php";

// CSRF token 防护
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // 生成一个新的 CSRF token
}

$transfer = $_SESSION['transfer'];
$error = ''; // 统一错误消息变量
if($transfer){
    $error = $transfer;
}

$showVerification = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 处理第一阶段验证
    if (isset($_POST['stage1'])) {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        $phone = trim($_POST['phone']);
        $password_verify = trim($_POST['password_verify']);

        // 基础验证
        if (empty($username) || empty($password) || empty($phone)) {
            $error = '请填写所有必填项！';
        } elseif (strlen($username) < 4) {
            $error = '用户名至少需要4个字符';
        } elseif (strlen($password) < 6) {
            $error = '密码长度至少需要6位';
        } elseif ($password != $password_verify) {
            $error = '两次输入的密码不一致！';
        } else {
            // 检查用户名和手机号
            $check_user = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $check_user->bind_param("s", $username);
            $check_user->execute();
            $userExists = $check_user->get_result()->num_rows > 0;
            $check_user->close();

            $check_phone = $conn->prepare("SELECT id FROM users WHERE phone = ?");
            $check_phone->bind_param("s", $phone);
            $check_phone->execute();
            $phoneExists = $check_phone->get_result()->num_rows > 0;
            $check_phone->close();

            if ($userExists) {
                $error = '用户名已被注册';
            } elseif ($phoneExists) {
                $error = '手机号已被注册';
            } else {
                // 生成验证码并存入SESSION
                $_SESSION['verification_code'] = rand(100000, 999999);
                $_SESSION['verification_expire'] = time() + 300; // 设置验证码有效期为5分钟

                $showVerification = true;
                $_SESSION['reg_data'] = [
                    'username' => $username,
                    'password' => md5($password),
                    'phone' => $phone,
                    'def_level' => 1
                ];
            }
        }
    }

    // 处理第二阶段验证
    if (isset($_POST['stage2'])) {
        $verification_code = trim($_POST['verification_code']);

        if (!isset($_SESSION['verification_code'])) {
            $error = '请先获取验证码';
        } elseif ($verification_code != $_SESSION['verification_code']) {
            $error = '验证码错误';
        } elseif (time() > $_SESSION['verification_expire']) {
            $error = '验证码已过期';
        } else {
            // 执行注册
            $sql = "INSERT INTO users (username, phone, password, name, level) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $name = $_SESSION['reg_data']['username'];
            $stmt->bind_param("ssssi",
                $_SESSION['reg_data']['username'],
                $_SESSION['reg_data']['phone'],
                $_SESSION['reg_data']['password'],
                $name,
                $_SESSION['reg_data']['def_level']
            );

            if ($stmt->execute()) {
                unset($_SESSION['reg_data']);
                unset($_SESSION['verification_code']);
                $_SESSION['state'] = true;
                $_SESSION['user_id'] = $stmt->insert_id;
                $_SESSION['username'] = $name;
                $_SESSION['name'] = $name;
                header('Location: login.php');
                exit();
            } else {
                echo $stmt->error;
                echo $_SESSION['reg_data']['password'];
                $error = '注册失败，请稍后再试';
            }
            $stmt->close();
        }
    }

    // CSRF验证
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("非法请求");
    }
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
        <!-- 第一阶段 -->
        <?php if(!$showVerification) { ?>
            <div id="stage1">
                <div class="form-group">
                    <label for="username">用户名</label>
                    <input type="text" id="username" name="username"
                           placeholder="4-20位字母/数字组合"
                           pattern="[a-zA-Z0-9]{4,20}"
                           required autofocus>
                </div>

                <div class="form-group">
                    <label for="phone">手机号</label>
                    <input type="tel" id="phone" name="phone"
                           placeholder="请输入手机号码"
                           pattern="^1[3-9]\d{9}$"
                           required>
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
                    <div>
                        <label for="password_verify">确认密码</label>
                        <input type="password" id="password_verify" name="password_verify"
                               placeholder="再次输入密码"
                               required>
                        <div class="password-match">✓ 密码匹配</div>
                        <div class="password-mismatch">✗ 密码不匹配</div>
                    </div>
                </div>

                <input type="hidden" name="stage1" value="1">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <button type="submit" class="register-btn">下一步</button>
            </div>
        <?php } ?>

        <!-- 第二阶段 -->
        <?php if($showVerification) { ?>
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
                <button type="submit" class="register-btn">完成注册</button>
            </div>
        <?php } ?>
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
