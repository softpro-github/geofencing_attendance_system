<?php
session_start();
if (isset($_SESSION['matric_number'])) {
    header("Location: student_dashboard");
    exit;
}
if (isset($_SESSION['lecturer_id'])) {
    header("Location: lecturer_dashboard");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Geofencing Attendance System</title>
    <link rel="icon" type="image/x-icon" href="assets/img/logo.png">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="plugins/sweetalerts/sweetalert2.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .form-container.outer {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 15px;
        }

        .form-form { width: 100%; max-width: 480px; }

        .form-form-wrap {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.25);
            overflow: hidden;
        }

        .form-container-inner { padding: 40px 40px 30px; }

        /* branding */
        .form-content { text-align: center; }
        .form-content img { height: 80px; margin-bottom: 12px; }
        .form-content h4 { font-size: 1.18rem; font-weight: 700; color: #1a202c; margin-bottom: 3px; }
        .form-content .system-sub { font-size: 0.83rem; color: #718096; margin-bottom: 24px; }

        /* main tabs */
        .auth-tabs {
            display: flex;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid #e2e8f0;
            margin-bottom: 22px;
        }
        .auth-tab-btn {
            flex: 1; padding: 9px 6px;
            background: #fff; border: none;
            font-size: 0.88rem; font-weight: 600;
            color: #718096; cursor: pointer;
            transition: all 0.2s ease;
        }
        .auth-tab-btn.active {
            background: linear-gradient(135deg, #0066cc, #0052a3);
            color: #fff;
        }

        /* panels */
        .auth-panel { display: none; }
        .auth-panel.active { display: block; animation: fadeUp 0.25s ease; }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* role pills */
        .role-pills { display: flex; gap: 10px; margin-bottom: 20px; }
        .role-pill {
            flex: 1; padding: 9px 8px;
            border: 2px solid #e2e8f0; border-radius: 8px;
            background: #f8fafc;
            font-size: 0.88rem; font-weight: 600;
            color: #718096; cursor: pointer;
            transition: all 0.2s ease;
            display: flex; align-items: center; justify-content: center; gap: 6px;
        }
        .role-pill.active {
            border-color: #0066cc;
            background: #eff6ff;
            color: #0066cc;
        }

        /* field wrapper */
        .field-wrapper { position: relative; margin-bottom: 18px; text-align: left; }
        .field-wrapper label {
            display: block; font-size: 0.76rem; font-weight: 700;
            letter-spacing: 0.06em; color: #4a5568; margin-bottom: 7px;
        }
        .field-wrapper > svg.field-icon {
            position: absolute; left: 13px;
            top: calc(50% + 13px); transform: translateY(-50%);
            color: #a0aec0; pointer-events: none;
        }
        .field-wrapper .form-control {
            padding: 11px 42px;
            border: 2px solid #e2e8f0; border-radius: 8px;
            font-size: 0.95rem; color: #1a202c; width: 100%;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .field-wrapper .form-control:focus {
            outline: none;
            border-color: #0066cc;
            box-shadow: 0 0 0 4px rgba(0,102,204,0.12);
        }
        .field-wrapper select.form-control { appearance: none; }

        /* eye toggle */
        .eye-toggle {
            position: absolute; right: 13px;
            top: calc(50% + 13px); transform: translateY(-50%);
            cursor: pointer; color: #a0aec0;
        }
        .eye-toggle:hover { color: #0066cc; }

        .forgot-pass-link {
            font-size: 0.8rem; color: #0066cc;
            font-weight: 600; text-decoration: none;
        }
        .forgot-pass-link:hover { text-decoration: underline; }

        /* submit buttons */
        .btn-login {
            width: 100%; padding: 12px;
            background: linear-gradient(135deg, #0066cc, #0052a3);
            color: #fff; border: none; border-radius: 8px;
            font-size: 0.97rem; font-weight: 700; cursor: pointer;
            transition: all 0.25s ease; margin-top: 4px;
            display: flex; align-items: center; justify-content: center; gap: 7px;
        }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,102,204,0.35); }
        .btn-login:disabled { opacity: 0.7; transform: none; cursor: not-allowed; }

        /* footer */
        .form-footer {
            background: #f8fafc; border-top: 1px solid #e2e8f0;
            padding: 13px 40px; text-align: center;
            font-size: 0.8rem; color: #718096;
        }

        @media (max-width: 480px) {
            .form-container-inner { padding: 28px 20px 22px; }
            .form-footer { padding: 12px 20px; }
        }
    </style>
</head>
<body>

<div class="form-container outer">
  <div class="form-form">
    <div class="form-form-wrap">
      <div class="form-container-inner">

        <div class="main-content" id="main-content">
          <div class="form-content">
            <img src="assets/img/logo.png" alt="Logo">
            <h4>Geofencing Attendance System</h4>
            <p class="system-sub">Smart Location-Based Attendance Tracking</p>
          </div>

          <!-- tabs -->
          <div class="auth-tabs">
            <button class="auth-tab-btn active" id="tab-login" onclick="switchTab('login')">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;margin-right:4px"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
              Login
            </button>
            <button class="auth-tab-btn" id="tab-register" onclick="switchTab('register')">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;margin-right:4px"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
              Register
            </button>
          </div>

          <!-- LOGIN PANEL -->
          <div id="panel-login" class="auth-panel active">
            <div class="role-pills">
              <button class="role-pill active" id="pill-student" onclick="switchRole('student')">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                Student
              </button>
              <button class="role-pill" id="pill-lecturer" onclick="switchRole('lecturer')">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                Lecturer
              </button>
            </div>

            <form id="login-form" novalidate>
              <input type="hidden" id="login-role" value="student">

              <div class="field-wrapper">
                <label id="identifier-label">MATRIC NUMBER</label>
                <svg class="field-icon" xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <input class="form-control" type="text" id="login_identifier"
                    placeholder="e.g., ICT/225230090" required>
              </div>

              <div class="field-wrapper">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <label style="margin-bottom:0">PASSWORD</label>
                  <a href="#" class="forgot-pass-link" onclick="forgotPassword(event)">Forgot Password?</a>
                </div>
                <svg class="field-icon" xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <input class="form-control" type="password" id="login_password"
                    placeholder="Enter your password" required>
                <svg class="eye-toggle" id="toggle-login-pw" xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              </div>

              <button type="submit" class="btn-login" id="login-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                Log In
              </button>
            </form>
          </div>

          <!-- REGISTER PANEL -->
          <div id="panel-register" class="auth-panel">
            <p style="font-size:0.83rem;color:#718096;text-align:left;margin-bottom:16px;">
              <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#f6ad55" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px;margin-right:4px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              Student self-registration. Lecturers contact the admin.
            </p>

            <form id="register-form" novalidate>
              <div class="field-wrapper">
                <label>FULL NAME</label>
                <svg class="field-icon" xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <input class="form-control" type="text" id="reg_name" placeholder="Enter your full name" required>
              </div>

              <div class="field-wrapper">
                <label>MATRIC NUMBER</label>
                <svg class="field-icon" xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                <input class="form-control" type="text" id="reg_matric" placeholder="e.g., ICT/225230090" required>
              </div>

              <div class="field-wrapper">
                <label>DEPARTMENT</label>
                <svg class="field-icon" xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                <select class="form-control" id="reg_department" required>
                  <option value="">Select Department</option>
                  <option value="Computer Sci.">Computer Science</option>
                  <option value="Mass Comm.">Mass Communication</option>
                  <option value="Statistics">Statistics</option>
                  <option value="OTM">Office Technology &amp; Management</option>
                  <option value="Library & Info. Sci.">Library &amp; Information Science</option>
                </select>
              </div>

              <div class="field-wrapper">
                <label>ACADEMIC LEVEL</label>
                <svg class="field-icon" xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="17 11 21 7 17 3"/><line x1="21" y1="7" x2="9" y2="7"/><polyline points="7 21 3 17 7 13"/><line x1="15" y1="17" x2="3" y2="17"/></svg>
                <select class="form-control" id="reg_level" required>
                  <option value="">Select Level</option>
                  <option value="ND1">ND Year 1</option>
                  <option value="ND2">ND Year 2</option>
                  <option value="HND1">HND Year 1</option>
                  <option value="HND2">HND Year 2</option>
                </select>
              </div>

              <div class="field-wrapper">
                <label>PASSWORD</label>
                <svg class="field-icon" xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <input class="form-control" type="password" id="reg_password"
                    placeholder="At least 6 characters" required minlength="6">
                <svg class="eye-toggle" id="toggle-reg-pw" xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              </div>

              <button type="submit" class="btn-login" id="register-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
                Create Account
              </button>
            </form>
          </div>

        </div><!-- /main-content -->

      </div><!-- /form-container-inner -->

      <div class="form-footer">
        Smart Geofencing Attendance &mdash; &copy; 2026. All rights reserved.
      </div>
    </div><!-- /form-form-wrap -->
  </div><!-- /form-form -->
</div>

<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="plugins/sweetalerts/sweetalert2.min.js"></script>
<script>
  function switchTab(tab) {
    ['login','register'].forEach(function(t) {
      document.getElementById('panel-' + t).classList.remove('active');
      document.getElementById('tab-' + t).classList.remove('active');
    });
    document.getElementById('panel-' + tab).classList.add('active');
    document.getElementById('tab-' + tab).classList.add('active');
  }

  function switchRole(role) {
    document.getElementById('login-role').value = role;
    document.getElementById('pill-student').classList.toggle('active', role === 'student');
    document.getElementById('pill-lecturer').classList.toggle('active', role === 'lecturer');

    var label = document.getElementById('identifier-label');
    var input = document.getElementById('login_identifier');
    if (role === 'student') {
      label.textContent = 'MATRIC NUMBER';
      input.placeholder = 'e.g., ICT/225230090';
    } else {
      label.textContent = 'USERNAME';
      input.placeholder = 'Enter your username';
    }
    input.value = '';
    input.focus();
  }

  function makeEyeToggle(toggleId, inputId) {
    document.getElementById(toggleId).addEventListener('click', function () {
      var input = document.getElementById(inputId);
      var show  = input.type === 'password';
      input.type = show ? 'text' : 'password';
      this.innerHTML = show
        ? '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>'
        : '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
    });
  }
  makeEyeToggle('toggle-login-pw', 'login_password');
  makeEyeToggle('toggle-reg-pw',   'reg_password');

  function forgotPassword(e) {
    e.preventDefault();
    Swal.fire({
      icon: 'info', title: 'Forgot Password',
      text: 'Please contact your administrator to reset your password.',
      confirmButtonColor: '#0066cc'
    });
  }

  document.getElementById('login-form').addEventListener('submit', function (e) {
    e.preventDefault();
    var btn        = document.getElementById('login-btn');
    var identifier = document.getElementById('login_identifier').value.trim();
    var password   = document.getElementById('login_password').value;
    var role       = document.getElementById('login-role').value;

    if (!identifier || !password) {
      Swal.fire({ icon: 'warning', title: 'Missing Fields', text: 'Please fill in all fields.', confirmButtonColor: '#0066cc' });
      return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>&nbsp; Logging in...';

    $.post('backend/unified_login', { role: role, identifier: identifier, password: password }, function (res) {
      if (res.success) {
        Swal.fire({
          icon: 'success', title: 'Welcome!',
          text: 'Login successful. Redirecting...',
          timer: 1400, showConfirmButton: false, timerProgressBar: true
        }).then(function () { window.location.href = res.redirect; });
      } else {
        Swal.fire({ icon: 'error', title: 'Login Failed', text: res.message, confirmButtonColor: '#0066cc' });
        btn.disabled = false;
        btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg> Log In';
      }
    }, 'json').fail(function () {
      Swal.fire({ icon: 'error', title: 'Server Error', text: 'Could not reach the server.', confirmButtonColor: '#0066cc' });
      btn.disabled = false;
      btn.innerHTML = 'Log In';
    });
  });

  document.getElementById('register-form').addEventListener('submit', function (e) {
    e.preventDefault();
    var btn  = document.getElementById('register-btn');
    var data = {
      name:          document.getElementById('reg_name').value.trim(),
      matric_number: document.getElementById('reg_matric').value.trim(),
      department:    document.getElementById('reg_department').value,
      level:         document.getElementById('reg_level').value,
      password:      document.getElementById('reg_password').value
    };

    if (!data.name || !data.matric_number || !data.department || !data.level || !data.password) {
      Swal.fire({ icon: 'warning', title: 'Missing Fields', text: 'Please fill in all fields.', confirmButtonColor: '#0066cc' });
      return;
    }
    if (data.password.length < 6) {
      Swal.fire({ icon: 'warning', title: 'Weak Password', text: 'Password must be at least 6 characters.', confirmButtonColor: '#0066cc' });
      return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>&nbsp; Creating account...';

    $.post('backend/register', data, function (res) {
      if (res.success) {
        Swal.fire({
          icon: 'success', title: 'Account Created!',
          text: 'Registration successful. You can now log in.',
          confirmButtonColor: '#0066cc'
        }).then(function () {
          document.getElementById('register-form').reset();
          switchTab('login');
        });
      } else {
        Swal.fire({ icon: 'error', title: 'Registration Failed', text: res.message, confirmButtonColor: '#0066cc' });
      }
      btn.disabled = false;
      btn.innerHTML = 'Create Account';
    }, 'json').fail(function () {
      Swal.fire({ icon: 'error', title: 'Server Error', text: 'Could not reach the server.', confirmButtonColor: '#0066cc' });
      btn.disabled = false;
      btn.innerHTML = 'Create Account';
    });
  });
</script>
</body>
</html>
