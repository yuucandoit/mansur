<!DOCTYPE html>
<html>
<head>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Serif+Display&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="{{ asset('css/login.css') }}">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ config('app.name') }}</title>
</head>
<body>
<div class="login-wrapper">

  <div class="left-panel">
    <div class="logo-area">
      <div class="logo-mark">{{Str::of(config('app.name'))->explode(' ')->map(fn($n) => Str::substr($n, 0, 1))->join('') }}</div>
      <div>
        <div class="logo-text">Protask</div>
        <div class="logo-sub">Team Plan</div>
      </div>
    </div>
    <div class="form-section">
        <div id="status-login" style="display: none;"></div>
        <div class="greeting">Welcome back</div>
        <div class="greeting-sub">Sign in to continue to your workspace.</div>

        <div class="field-group">
            <label class="field-label">Email address</label>
            <input class="field-input" type="email" placeholder="you@example.com" id="email_input" />
        </div>

        <div class="field-group">
            <label class="field-label">Password</label>
            <div class="field-input-wrap">
            <input class="field-input" type="password" id="pass-input" placeholder="••••••••" id="pass_input" />
            <button class="eye-btn" onclick="togglePass()" id="eye-btn">
                <i class="fas fa-eye" id="eye-icon"></i>
            </button>
            </div>
            <div class="forgot-row">
            <a href="#" class="forgot-link">Forgot password?</a>
            </div>
        </div>

        <button class="btn-login" id="btn-submit">Sign in</button>


        <div class="signup-row">
            Don't have an account? <a href="#">Sign up free</a>
        </div>
    </div>

    <div class="bottom-note">
      By signing in, you agree to our <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>.
    </div>
  </div>

  <div class="right-panel">
    <div class="bg-orb orb1"></div>
    <div class="bg-orb orb2"></div>

    <div class="rp-top">
      <div class="tag-pill">Protask — Active</div>
    </div>

    <div class="rp-content">
      <div class="rp-headline">Manage your<br>projects with <span>clarity.</span></div>
      <div class="rp-desc">Track progress, assign tasks, and collaborate seamlessly — all in one place built for creative teams.</div>

      <div class="stat-row">
        <div class="stat-item">
          <div class="stat-num">48</div>
          <div class="stat-label">Active tasks</div>
        </div>
        <div class="stat-item">
          <div class="stat-num">6</div>
          <div class="stat-label">Projects</div>
        </div>
        <div class="stat-item">
          <div class="stat-num">12</div>
          <div class="stat-label">Team members</div>
        </div>
      </div>

      <div class="rp-mockup">
        <div class="mini-board">
          <div class="mini-col">
            <div class="mini-col-header mc-red">Backlogs</div>
            <div class="mini-card">
              <div class="mc-title">POS System</div>
              <div class="mc-avatars"><div class="mc-av av1">A</div><div class="mc-av av2">B</div></div>
            </div>
            <div class="mini-card">
              <div class="mc-title">Reservation</div>
              <div class="mc-avatars"><div class="mc-av av3">C</div></div>
            </div>
          </div>
          <div class="mini-col">
            <div class="mini-col-header mc-amber">In Progress</div>
            <div class="mini-card">
              <div class="mc-title">Floor Plan</div>
              <div class="mc-avatars"><div class="mc-av av1">A</div></div>
            </div>
            <div class="mini-card">
              <div class="mc-title">Promo list</div>
              <div class="mc-avatars"><div class="mc-av av2">B</div><div class="mc-av av3">C</div></div>
            </div>
          </div>
          <div class="mini-col">
            <div class="mini-col-header mc-purple">Revision</div>
            <div class="mini-card">
              <div class="mc-title">Flow Payment</div>
              <div class="mc-avatars"><div class="mc-av av1">A</div><div class="mc-av av3">C</div></div>
            </div>
          </div>
          <div class="mini-col">
            <div class="mini-col-header mc-green">Check PM</div>
            <div class="mini-card">
              <div class="mc-title">Waitlist</div>
              <div class="mc-avatars"><div class="mc-av av2">B</div></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>

<script src="{{ asset('js/login.js') }}"></script>
</body>
</html>

