<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Gym coaching portal - login</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f0f2f5; margin: 40px; }
        .container { display: flex; gap: 20px; max-width: 800px; margin: 0 auto; }
        .card { background: white; padding: 25px; border-radius: 8px; flex: 1; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        input, select, button { width: 100%; padding: 10px; margin-top: 10px; box-sizing: border-box; }
        button { background: #21a46d; color: white; border: none; font-weight: bold; cursor: pointer; }
        .msg { color: red; margin-bottom: 10px; }
    </style>
</head>
<body>

<h1 style="text-align: center;">Gym coaching portal</h1>

<?php if (isset($_GET['msg'])): ?>
    <p class="msg" style="text-align: center;"><?= htmlspecialchars($_GET['msg']) ?></p>
<?php endif; ?>

<div class="container">
    <!--login-->
    <div class="card">
        <h2>Login</h2>
        <form action="actions.php?action=login" method="POST">
            <input type="email" name="email" placeholder="Email Address" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Sign in</button>
        </form>
    </div>

    <!--registration form-->
    <div class="card">
        <h2>Register</h2>
        <form action="actions.php?action=register" method="POST">
            <input type="text" name="username" placeholder="Username" required><br><br>
            <input type="email" name="email" placeholder="Email Address" required><br><br>
            <input type="password" name="password" placeholder="Password" required><br><br>

            <label>Select role:</label><br>
            <select name="role" id="roleSelect" onchange="toggleRoleFields()" required>
                <option value="member">Member</option>
                <option value="coach">Coach</option>
                <option value="admin">Admin</option>
            </select><br><br>

            <div id="memberFields">
                <label>Date of birth:</label><br>
                <input type="date" name="dob"><br><br>
                <label>Height (cm):</label><br>
                <input type="number" step="0.1" name="height"><br><br>
                <label>Weight (kg):</label><br>
                <input type="number" step="0.1" name="weight"><br><br>
            </div>

            <div id="securityKeyField" style="display: none;">
                <label style="color: #dc3545; font-weight: bold;">Role creation passcode:</label><br>
                <input type="password" name="role_key" placeholder="Enter key"><br><br>
            </div>

            <button type="submit">Create account</button>
        </form>
    </div>

    <script>
    function toggleRoleFields() {
        var role = document.getElementById('roleSelect').value;
        var memberFields = document.getElementById('memberFields');
        var securityKeyField = document.getElementById('securityKeyField');

        if (role === 'member') {
            memberFields.style.display = 'block';
            securityKeyField.style.display = 'none';
        } else {
            memberFields.style.display = 'none';
            securityKeyField.style.display = 'block';
        }
    }
    </script>
</div>

</body>
</html>