<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录 - 聚合认证中心</title>
    <link href="https://cdn.staticfile.org/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.staticfile.org/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            height: 100vh; display: flex; align-items: center; justify-content: center; 
            font-family: "PingFang SC", sans-serif; 
        }
        
        .auth-card {
            background: #fff; width: 100%; max-width: 420px; 
            border-radius: 20px; overflow: hidden;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2); position: relative; z-index: 10;
        }
        
        .card-header-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 20px; text-align: center; color: white;
        }
        
        .logo-shield {
            width: 60px; height: 60px; background: rgba(255,255,255,0.2);
            backdrop-filter: blur(5px); border-radius: 50%; 
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 15px; font-size: 30px; border: 2px solid rgba(255,255,255,0.5);
        }
        
        .card-body { padding: 30px; }
        
        .input-group-text { background: #f5f7fa; border: none; color: #888; border-radius: 50px 0 0 50px; padding-left: 20px;}
        .form-control { 
            background: #f5f7fa; border: none; padding: 12px; border-radius: 0 50px 50px 0; 
            font-size: 14px; box-shadow: none !important;
        }
        .input-group { margin-bottom: 20px; border: 1px solid #eee; border-radius: 50px; overflow: hidden; }
        .input-group:focus-within { border-color: #764ba2; background: #fff; }
        .input-group:focus-within .input-group-text { background: #fff; color: #764ba2; }
        .input-group:focus-within .form-control { background: #fff; }

        .btn-login {
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            border: none; width: 100%; padding: 12px; border-radius: 50px;
            color: #fff; font-weight: bold; letter-spacing: 2px;
            box-shadow: 0 5px 15px rgba(118, 75, 162, 0.4);
            transition: 0.3s;
        }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(118, 75, 162, 0.6); }

        .agreement-box { font-size: 12px; color: #666; display: flex; align-items: center; margin-bottom: 20px; }
        .form-check-input { margin-top: 0; margin-right: 8px; cursor: pointer; }
        .form-check-input:checked { background-color: #764ba2; border-color: #764ba2; }
        
        .bottom-links { text-align: center; margin-top: 20px; font-size: 13px; }
        .bottom-links a { color: #764ba2; text-decoration: none; font-weight: bold; }

        /* === 弹窗样式 (仿截图) === */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5); backdrop-filter: blur(5px);
            z-index: 999; display: none; justify-content: center; align-items: center;
            opacity: 0; transition: opacity 0.3s;
        }
        .modal-overlay.show { opacity: 1; }
        
        .modal-box {
            background: #fff; width: 90%; max-width: 500px; border-radius: 20px; overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3); transform: scale(0.9); transition: transform 0.3s;
        }
        .modal-overlay.show .modal-box { transform: scale(1); }
        
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px; display: flex; justify-content: space-between; align-items: center; color: white;
        }
        .modal-title { font-weight: bold; font-size: 18px; margin: 0; }
        .modal-close {
            background: rgba(255,255,255,0.2); border: none; color: white; width: 30px; height: 30px;
            border-radius: 50%; cursor: pointer; font-size: 16px; line-height: 1;
        }
        .modal-close:hover { background: rgba(255,255,255,0.4); }
        
        .modal-body { padding: 30px; max-height: 60vh; overflow-y: auto; font-size: 14px; line-height: 1.8; color: #555; }
        .modal-body h5 { color: #764ba2; font-size: 15px; margin-top: 15px; margin-bottom: 5px; font-weight: bold; }
        .modal-body p { margin-bottom: 10px; }
    </style>
</head>
<body>

<div class="auth-card">
    <div class="card-header-bg">
        <div class="logo-shield"><i class="bi bi-shield-lock-fill"></i></div>
        <h4 class="fw-bold mb-1">登 录</h4>
        <small class="opacity-75">聚合认证中心</small>
    </div>

    <div class="card-body">
        <form id="loginForm">
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="text" name="username" class="form-control" placeholder="账号 / 邮箱地址" required>
            </div>
            
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" name="password" class="form-control" placeholder="密码" required>
            </div>

            <div class="d-flex justify-content-between mb-3 small px-2">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="remember">
                    <label class="form-check-label text-muted" for="remember">记住密码</label>
                </div>
                <a href="#" class="text-primary text-decoration-none">忘记密码?</a>
            </div>

            <div class="agreement-box p-2 bg-light rounded">
                <input type="checkbox" class="form-check-input" id="agree" required>
                <label for="agree">
                    我同意 <a href="javascript:;" onclick="showModal('user')">用户协议</a> 和 <a href="javascript:;" onclick="showModal('privacy')">免责声明</a>
                </label>
            </div>

            <button type="submit" class="btn-login" id="submitBtn">
                <i class="bi bi-box-arrow-in-right"></i> 登 录
            </button>
        </form>

        <div class="bottom-links">
            还没有账户? <a href="register.php">立即注册</a>
        </div>
    </div>
</div>

<div class="modal-overlay" id="protocolModal">
    <div class="modal-box">
        <div class="modal-header">
            <h5 class="modal-title" id="modalTitle">协议条款</h5>
            <button class="modal-close" onclick="closeModal()">×</button>
        </div>
        <div class="modal-body">
            <div id="modalContent"></div>
        </div>
    </div>
</div>

<script>
// 协议内容配置
const protocols = {
    'user': `
        <h5>1. 服务内容</h5>
        <p>本网站为用户提供在线服务。我们会根据实际情况对服务内容进行调整。</p>
        <h5>2. 账户安全</h5>
        <p>用户应提供真实信息并对自己的账户安全负责。</p>
        <h5>3. 用户行为</h5>
        <p>用户应遵守法律法规，不得进行任何违法或不道德的行为。</p>
        <h5>4. 版权保护</h5>
        <p>用户上传的内容应保证拥有合法版权或已获得授权。</p>
        <h5>5. 隐私保护</h5>
        <p>我们采取措施保护用户隐私信息。</p>
        <h5>6. 服务终止</h5>
        <p>我们保留终止任何用户服务的权利。</p>
        <h5>7. 免责条款</h5>
        <p>用户对其使用本服务所产生的所有后果自行承担责任。</p>
    `,
    'privacy': `
        <h5>1. 内容来源</h5>
        <p>本网站内容源于网络公开渠道，我们无法保证其真实性和准确性。</p>
        <h5>2. 版权声明</h5>
        <p>内容仅供个人学习使用，不得用于商业目的。</p>
        <h5>3. 用户责任</h5>
        <p>用户应遵守法律法规和社会公德。</p>
        <h5>4. 免责说明</h5>
        <p>对于因技术故障、网络异常等原因导致的损失，本网站不承担责任。</p>
        <h5>5. 数据安全</h5>
        <p>用户自行承担因密码泄露导致的所有损失。</p>
    `
};

// 显示弹窗
function showModal(type) {
    const overlay = document.getElementById('protocolModal');
    const title = document.getElementById('modalTitle');
    const content = document.getElementById('modalContent');
    
    title.innerText = type === 'user' ? '用户协议' : '免责声明';
    content.innerHTML = protocols[type];
    
    overlay.style.display = 'flex';
    setTimeout(() => overlay.classList.add('show'), 10); // 延时加动画
}

// 关闭弹窗
function closeModal() {
    const overlay = document.getElementById('protocolModal');
    overlay.classList.remove('show');
    setTimeout(() => overlay.style.display = 'none', 300);
}

// 点击遮罩关闭
document.getElementById('protocolModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

// 登录逻辑
document.getElementById('loginForm').addEventListener('submit', function(e) {
    e.preventDefault();
    if (!document.getElementById('agree').checked) {
        Swal.fire({ icon: 'warning', title: '请先同意协议', text: '您必须勾选用户协议才能继续', confirmButtonColor: '#764ba2' });
        return;
    }
    const formData = new FormData(this);
    fetch('/api/user_action.php?act=login', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.code === 1) {
                Swal.fire({icon:'success', title:'登录成功', timer:1000, showConfirmButton:false}).then(() => {
                    location.href = 'user/index.php';
                });
            } else {
                Swal.fire({icon:'error', title:data.msg});
            }
        });
});
</script>

</body>
</html>