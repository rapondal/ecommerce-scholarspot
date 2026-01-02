<?php
// Start session for handling messages
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Sign Up - ScholarSpot</title>

  <!-- External CSS -->
  <link rel="stylesheet" href="css/styles.css">

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@800&family=Hind:wght@400;500&family=Hind+Guntur:wght@600&display=swap" rel="stylesheet">
</head>
<body class="auth-page">

  <div class="auth-container">
    <img src="images/logonew.png" width="150">
    <h2>Create Your Account</h2>
    <p class="auth-subtitle">Join ScholarSpot and get started today</p>

    <?php if (isset($_SESSION['error'])): ?>
      <div class="error"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <form action="signup_process.php" method="POST">
      <div>
        <label for="name">Full Name</label>
        <input type="text" id="name" name="name" required>
      </div>

      <div>
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required>
      </div>

      <div>
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
      </div>

      <div>
        <label for="confirm_password">Confirm Password</label>
        <input type="password" id="confirm_password" name="confirm_password" required>
      </div>

      <button type="submit">Sign Up</button>
    </form>

    <p class="auth-redirect">Already have an account? <a href="login.php">Log in</a></p>
  </div>

</body>
</html>