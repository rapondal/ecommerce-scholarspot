<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Login - ScholarSpot</title>
  <link rel="stylesheet" href="css/styles.css">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@800&family=Hind:wght@400;500&family=Hind+Guntur:wght@600&display=swap" rel="stylesheet">
</head>

<body class="auth-page">

  <div class="auth-container">
    <img src="images/logonew.png" width="150">
    <h2>Login to ScholarSpot</h2>

    <?php if (isset($_SESSION['error'])): ?>
      <p class="error"><?= htmlspecialchars($_SESSION['error']);
                        unset($_SESSION['error']); ?></p>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
      <p class="success"><?= htmlspecialchars($_SESSION['success']);
                          unset($_SESSION['success']); ?></p>
    <?php endif; ?>

    <form action="login_process.php" method="POST">
      <div>
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required>
      </div>

      <div style="position: relative;">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required style="padding-right: 40px;">
        <span id="togglePassword" style="position: absolute; right: 10px; top: 35px; cursor: pointer;">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#666" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
            <circle cx="12" cy="12" r="3"></circle>
          </svg>
        </span>
      </div>

      <div style="margin: 10px 0; display: flex; align-items: center;">
        <input type="checkbox" id="remember_me" name="remember_me" style="width: auto; margin-right: 8px;">
        <label for="remember_me" style="margin: 0; font-weight: normal;">Remember Me</label>
      </div>

      <button type="submit">Login</button>
    </form>

    <p class="auth-redirect">No account? <a href="signup.php">Sign up here</a></p>
  </div>

  <script>
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#password');

    togglePassword.addEventListener('click', function(e) {
      // Toggle the type attribute
      const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
      password.setAttribute('type', type);

      // Change icon color to indicate state
      this.querySelector('svg').style.stroke = type === 'text' ? '#000' : '#666';
    });
  </script>

</body>

</html>