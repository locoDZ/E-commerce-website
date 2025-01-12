<?php
require_once '../user_db_config.php';

$error = "";

// Initialize login attempts if not set
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}

// Check if the request method is POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    try {
        // Prepare SQL statement to fetch user details
        $stmt = $conn->prepare("SELECT id, username, password, is_privileged FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        // Fetch user details
        if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['is_privileged'] = $user['is_privileged'];
                $_SESSION['login_attempts'] = 0;
                
                // Redirect based on user privilege
                header("Location: " . ($user['is_privileged'] == 1 ? "../admin dashboard/admin_dashboard.php" : "../unprivelaged user/market.php"));
                exit();
            } else {
                // Increment login attempts and set error message
                $_SESSION['login_attempts']++;
                $error = "Wrong username or password";
            }
        } else {
            // Increment login attempts and set error message
            $_SESSION['login_attempts']++;
            $error = "Wrong username or password";
        }

        // Suggest sign up after 3 failed attempts
        if ($_SESSION['login_attempts'] >= 3) {
            $error .= "<br>If you don't have an account, please <a href='signup.php'>sign up</a>";
        }
    } catch(PDOException $e) {
        // Set error message on exception
        $error = "Login failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1 class="title">Sign In</h1>
        
        <!-- Display error message if any -->
        <?php if ($error): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Sign in form -->
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn">Sign In</button>
            
            <div class="form-footer">
                <p>Don't have an account? <a href="signup.php">Sign up</a></p>
            </div>
        </form>
    </div>
</body>
</html>