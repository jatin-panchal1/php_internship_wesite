<?php
    session_start();
    if (isset($_SESSION['user_email'])) {
    $role = $_SESSION['user_role'] ?? 'member';
    switch ($role) {
        case 'superadmin':
            header("Location: superadmin.php");
            break;
        case 'admin':
            header("Location: admin.php");
            break;
        default:
            header("Location: home.php");
            break;
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: #f7f8fa;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1.5rem;
            color: #1d1d1f;
        }

        .login-card {
            width: 100%;
            max-width: 380px;
            background: #ffffff;
            padding: 2.2rem 1.8rem;
            border-radius: 14px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04), 0 1px 3px rgba(0, 0, 0, 0.06);
            border: 1px solid #e9eaee;
        }

        h2 {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 0.3rem;
            letter-spacing: -0.3px;
        }

        .subtitle {
            font-size: 0.85rem;
            color: #86868b;
            margin-bottom: 1.8rem;
        }

        .form-group {
            margin-bottom: 1.2rem;
        }

        label {
            display: block;
            font-size: 0.8rem;
            font-weight: 500;
            color: #3a3a3c;
            margin-bottom: 0.3rem;
            letter-spacing: 0.2px;
        }

        input {
            width: 100%;
            padding: 0.7rem 0.8rem;
            font-size: 0.9rem;
            font-family: inherit;
            color: #1d1d1f;
            background: #f9f9fb;
            border: 1px solid #dcdde3;
            border-radius: 8px;
            outline: none;
            transition: border 0.15s ease, box-shadow 0.15s ease;
        }

        input:focus {
            border-color: #a4a7b3;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(120, 125, 140, 0.1);
        }

        /* HTML5 validation styles – clean and simple */
        input:user-invalid {
            border-color: #e3535b;
            background: #fffafa;
        }

        input:user-valid {
            border-color: #51a881;
            background: #f9fffb;
        }

        button {
            width: 100%;
            padding: 0.75rem;
            font-size: 0.95rem;
            font-weight: 530;
            font-family: inherit;
            color: #fff;
            background: #1d1d1f;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            margin-top: 0.6rem;
            transition: background 0.15s ease;
            letter-spacing: 0.2px;
        }

        button:hover {
            background: #2c2c2e;
        }

        .footer-text {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.8rem;
            color: #86868b;
        }

        .footer-text a {
            color: #1d1d1f;
            text-decoration: none;
            font-weight: 500;
        }

        .footer-text a:hover {
            text-decoration: underline;
        }

       
        input[type="number"]::-webkit-inner-spin-button,
        input[type="number"]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type="number"] {
            -moz-appearance: textfield;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <h2>Sign in</h2>
        <p class="subtitle">Enter your details to continue</p>

        <form action="login_process.php" method="post">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="john@example.com" required autocomplete="email">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter youe password" required >
            </div>

            <button type="submit">Sign in</button>
        </form>

        <p class="footer-text">
            No account? <a href="register.php">Sign up</a>
        </p>
    </div>
</body>
</html>