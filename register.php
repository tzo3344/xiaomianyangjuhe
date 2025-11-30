<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>注册 - 聚合认证中心</title>
    <link href="https://cdn.staticfile.org/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.staticfile.org/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            min-height: 100vh; display: flex; align-items: center; justify-content: center; 
            font-family: "PingFang SC", sans-serif; padding: 20px;
        }
        
        .auth-card {
            background: #fff; width: 100%; max-width: 450px; 
            border-radius: 20px; overflow: hidden;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2); position: relative; z-index: 10;
        }
        
        .card-header-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px 20px; text-align: center; color: white;
        }
        
        .input-group-text { background: #f5f7fa; border: none; color: #888; border-radius: 50px 0 0 50px; padding-left: 15px;}
        .form-control { 
            background: #f5f7fa; border: none; padding: 10px; border-radius: 0 50px 50px 0; 
            font-size: 14px; box-shadow: none !important;
        }
        .input-group { margin-bottom: 15px; border: 1px solid #eee; border-radius: 50px; overflow: hidden; }
        .input-group:focus-within { border-color: #764ba2; background: #fff; }
        .input-group:focus-within .input-group-text { background: #fff; color: #764ba2; }
        .input-group:focus-within .form-control { background: #fff; }

        .btn-send {
            border-radius: 0 50px 50px 0; font-size: 12px; padding: 0 20px;
            background: #764ba2; color: white; border: none;
        }
        .btn-send:disabled { background: #ccc; }

        .btn-reg {
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            border: none; width: 100%; padding: 12px; border-radius: 50px;
            color: #fff; font-weight: bold; letter-spacing: 1px;
            box-shadow: 0 5px 15px rgba(118, 75, 162, 0.4);
        }
        
        .warning-box {
            background: #fff5f5; border: 1px dashed #fc8181; color: #c53030;
            padding: 10px; border-radius: 10px; font-size: 12px; margin-bottom: 15px;
            display: flex; align-items: flex-start; gap: 8px;
        }

        /* 弹窗样式 */
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
        .modal-body { padding: 30px; max-height: 60vh; overflow-y: auto; font-size: 14px; line-height: 1.8; color: #555; }
        .modal-body h5 { color: #764ba2; font-size: 15px; margin-top: 15px; margin-bottom: 5px; font-weight: bold; }
    </style>
</head>
<body>

<div class="auth-card">
    <div class="card-header-bg">
        <h4 class="fw-bold m-0"><i class="bi bi-person-plus-fill me-2"></i>注册账号</h4>
    </div>

    <div class="card-body p-4">
        <form id="regForm">
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person"></i></span>
                <input type="text" name="username" class="form-control" placeholder="设置用户名 (至少3位)" required>
            </div>
            
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-key"></i></span>
                <input type="password" name="password" class="form-control" placeholder="设置登录密码 (至少5位)" required>
            </div>

            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" name="email" id="emailAddr" class="form-control" placeholder="您的邮箱地址" required>
            </div>

            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-shield-check"></i></span>
                <input type="text" name="vcode" class="form-control" placeholder="邮箱验证码" required>
                <button type="button" class="btn-send" id="sendBtn" onclick="sendCode()">发送验证码</button>
            </div>

            <div class="input-group mb-2">
                <span class="input-group-text"><i class="bi bi-ticket-perforated"></i></span>
                <input type="text" name="invite" class="form-control" placeholder="邀请码 (选填，不填也行)">
            </div>

            <div class="warning-box">
                <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                <div>
                    <strong>温馨提示：</strong>
                    为了保证平台质量，未填写邀请码的普通用户可能会被系统不定期清理。
                    <a href="#" class="text-danger fw-bold text-decoration-underline">建议充值VIP</a> 以获得永久权限。
                </div>
            </div>

            <div class="mb-3" style="font-size: 12px; color:#666">
                <input type="checkbox" id="agree" required>
                <label for="agree">我已阅读并同意 <a href="javascript:;" onclick="showModal('user')">用户协议</a> 和 <a href="javascript:;" onclick="showModal('privacy')">隐私条款</a></label>
            </div>

            <button type="submit" class="btn-reg">立即注册</button>
        </form>

        <div class="text-center mt-3 small">
            已有账号? <a href="login.php" style="color:#764ba2;font-weight:bold">直接登录</a>
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
// 复用协议内容
const protocols = {
    'user': `
        <h5>1. 服务内容</h5><p>本网站为用户提供在线服务。我们会根据实际情况对服务内容进行调整。</p>
        <h5>2. 账户安全</h5><p>用户应提供真实信息并对自己的账户安全负责。</p>
        <h5>3. 用户行为</h5><p>用户应遵守法律法规，不得进行任何违法或不道德的行为。</p>
        <h5>4. 版权保护</h5><p>用户上传的内容应保证拥有合法版权或已获得授权。</p>
        <h5>5. 隐私保护</h5><p>我们采取措施保护用户隐私信息。</p>
        <h5>6. 服务终止</h5><p>我们保留终止任何用户服务的权利。</p>
        <h5>7. 免责条款</h5><p>用户对其使用本服务所产生的所有后果自行承担责任。</p>
    `,
    'privacy': `
        <h5>1. 内容来源</h5><p>本网站内容源于网络公开渠道，我们无法保证其真实性和准确性。</p>
        <h5>2. 版权声明</h5><p>内容仅供个人学习使用，不得用于商业目的。</p>
        <h5>3. 用户责任</h5><p>用户应遵守法律法规和社会公德。</p>
        <h5>4. 免责说明</h5><p>对于因技术故障、网络异常等原因导致的损失，本网站不承担责任。</p>
        <h5>5. 数据安全</h5><p>用户自行承担因密码泄露导致的所有损失。</p>
        <h5>6. 服务变更</h5><p>我们有权随时变更服务内容而无需另行通知。</p>
    `
};

function showModal(type) {
    document.getElementById('modalTitle').innerText = type === 'user' ? '用户协议' : '免责声明';
    document.getElementById('modalContent').innerHTML = protocols[type];
    const overlay = document.getElementById('protocolModal');
    overlay.style.display = 'flex';
    setTimeout(() => overlay.classList.add('show'), 10);
}
function closeModal() {
    const overlay = document.getElementById('protocolModal');
    overlay.classList.remove('show');
    setTimeout(() => overlay.style.display = 'none', 300);
}
document.getElementById('protocolModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

// 发送验证码
function sendCode() {
    const email = document.getElementById('emailAddr').value;
    const btn = document.getElementById('sendBtn');
    if (!email || !email.includes('@')) { Swal.fire({toast:true, position:'top', icon:'error', title:'请输入正确的邮箱'}); return; }
    btn.disabled = true; btn.innerText = '发送中...';
    
    const formData = new FormData(); formData.append('email', email);
    fetch('/api/user_action.php?act=send_code', { method: 'POST', body: formData })
        .then(res => res.json()).then(data => {
            if (data.code === 1) {
                Swal.fire({ title: '邮件发送成功', text: '（演示模式）验证码：' + data.debug_code, icon: 'success' });
                let count = 60;
                const timer = setInterval(() => {
                    btn.innerText = count + 's 后重试'; count--;
                    if (count < 0) { clearInterval(timer); btn.disabled = false; btn.innerText = '发送验证码'; }
                }, 1000);
            } else {
                Swal.fire('失败', data.msg, 'error'); btn.disabled = false; btn.innerText = '发送验证码';
            }
        });
}

// 注册
document.getElementById('regForm').addEventListener('submit', function(e) {
    e.preventDefault();
    if (!document.getElementById('agree').checked) { Swal.fire({icon:'warning', title:'请勾选协议'}); return; }
    const formData = new FormData(this);
    fetch('/api/user_action.php?act=reg', { method: 'POST', body: formData })
        .then(res => res.json()).then(data => {
            if(data.code === 1) {
                Swal.fire({icon:'success', title:'注册成功', text:'正在跳转登录...'}).then(() => location.href = 'login.php');
            } else {
                Swal.fire({icon:'error', title:data.msg});
            }
        });
});
</script>

</body>
</html>