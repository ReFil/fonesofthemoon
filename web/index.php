<?php
session_start();

$dbHost = getenv('DB_HOST') ?: 'postgres';
$dbPort = getenv('DB_PORT') ?: '5432';
$dbName = getenv('DB_NAME') ?: 'kamailio';
$dbUser = getenv('DB_USER') ?: 'kamailio';
$dbPass = getenv('DB_PASSWORD') ?: '';
$defaultDomain = getenv('SIP_DOMAIN') ?: 'sip.friendsofthemoon.space';
$adminHash = getenv('WEB_ADMIN_HASH') ?: '';

$dsn = "pgsql:host=$dbHost;port=$dbPort;dbname=$dbName";
try {
    $pdo = new PDO($dsn, $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
}

$schema = 'kamailio';
$message = '';
$authError = '';

function ha1($user, $domain, $pass) {
    return md5("$user:$domain:$pass");
}

function ha1b($user, $domain, $pass) {
    return md5("$user@$domain:$domain:$pass");
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $password = $_POST['admin_password'] ?? '';
    if ($adminHash && password_verify($password, $adminHash)) {
        $_SESSION['authenticated'] = true;
        $message = 'Logged in successfully.';
    } else {
        $authError = 'Invalid password.';
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /');
    exit;
}

$authenticated = !empty($_SESSION['authenticated']);

// Handle add/edit/delete only when authenticated
if ($authenticated && $_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['login'])) {
    $action = $_POST['action'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $domain = trim($_POST['domain'] ?? '');
    $password = $_POST['password'] ?? '';
    $friendlyName = trim($_POST['friendly_name'] ?? '');
    $groupFlags = 0;
    for ($i = 1; $i <= 5; $i++) {
        if (!empty($_POST["group$i"])) {
            $groupFlags |= (1 << ($i - 1));
        }
    }

    if ($action === 'delete' && $username !== '' && $domain !== '') {
        $stmt = $pdo->prepare("DELETE FROM $schema.subscriber WHERE username = ? AND domain = ?");
        $stmt->execute([$username, $domain]);
        $message = 'Subscriber deleted.';
    } elseif (($action === 'add' || $action === 'edit') && $username !== '' && $domain !== '' && $password !== '') {
        $h1 = ha1($username, $domain, $password);
        $h1b = ha1b($username, $domain, $password);
        $stmt = $pdo->prepare("INSERT INTO $schema.subscriber (username, domain, password, ha1, ha1b, friendly_name, group_flags) VALUES (?, ?, ?, ?, ?, ?, ?)
            ON CONFLICT (username, domain) DO UPDATE SET password = EXCLUDED.password, ha1 = EXCLUDED.ha1, ha1b = EXCLUDED.ha1b, friendly_name = EXCLUDED.friendly_name, group_flags = EXCLUDED.group_flags");
        $stmt->execute([$username, $domain, $password, $h1, $h1b, $friendlyName, $groupFlags]);
        $message = $action === 'add' ? 'Subscriber added.' : 'Subscriber updated.';
    }
}

$stmt = $pdo->query("SELECT id, username, domain, password, email_address, friendly_name, group_flags FROM $schema.subscriber ORDER BY username");
$subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Kamailio Subscriber Manager</title>
    <style>
        body { font-family: sans-serif; max-width: 1000px; margin: 2em auto; padding: 0 1em; }
        h1, h2 { color: #333; }
        table { border-collapse: collapse; width: 100%; margin-top: 1em; }
        th, td { border: 1px solid #ccc; padding: 0.5em; text-align: left; }
        th { background: #f0f0f0; }
        form { display: inline; }
        input[type=text], input[type=password] { padding: 0.4em; width: 140px; }
        button { padding: 0.4em 1em; cursor: pointer; }
        .message { background: #e6ffe6; border: 1px solid #0c0; padding: 0.8em; margin-bottom: 1em; border-radius: 4px; }
        .error { background: #ffe6e6; border: 1px solid #c00; padding: 0.8em; margin-bottom: 1em; border-radius: 4px; }
        .danger { background: #ffe6e6; border-color: #c00; }
        .groups label { margin-right: 0.8em; font-size: 0.9em; }
        .login-box { background: #f9f9f9; border: 1px solid #ccc; padding: 1em; margin-bottom: 1em; border-radius: 4px; max-width: 400px; }
        .logout { float: right; }
    </style>
</head>
<body>
    <h1>Kamailio Subscriber Manager</h1>
    <?php if ($message): ?><div class="message"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($authError): ?><div class="error"><?= htmlspecialchars($authError) ?></div><?php endif; ?>

    <?php if (!$authenticated): ?>
        <div class="login-box">
            <h2>Admin Login</h2>
            <p>Public users can view phone numbers and names only. Login to edit or delete subscribers.</p>
            <form method="POST">
                <input type="hidden" name="login" value="1">
                <label>Password: <input type="password" name="admin_password" required></label>
                <button type="submit">Login</button>
            </form>
        </div>
    <?php else: ?>
        <a href="?logout=1" class="logout"><button>Logout</button></a>

        <h2>Add Subscriber</h2>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <label>Username: <input type="text" name="username" required></label>
            <label>Domain: <input type="text" name="domain" value="<?= htmlspecialchars($defaultDomain) ?>" required></label>
            <label>Friendly Name: <input type="text" name="friendly_name"></label>
            <label>Password: <input type="password" name="password" required></label>
            <span class="groups">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                <label><input type="checkbox" name="group<?= $i ?>" value="1"> G<?= $i ?></label>
                <?php endfor; ?>
            </span>
            <button type="submit">Add</button>
        </form>
    <?php endif; ?>

    <h2>Subscribers</h2>
    <table>
        <tr>
            <th>Username</th>
            <th>Domain</th>
            <th>Friendly Name</th>
            <?php if ($authenticated): ?>
                <th>Password</th>
                <th>Email</th>
                <th>Groups</th>
                <th>Actions</th>
            <?php endif; ?>
        </tr>
        <?php foreach ($subscribers as $row): ?>
        <tr>
            <td><?= htmlspecialchars($row['username']) ?></td>
            <td><?= htmlspecialchars($row['domain']) ?></td>
            <td><?= htmlspecialchars($row['friendly_name'] ?? '') ?></td>
            <?php if ($authenticated): ?>
                <td><?= htmlspecialchars($row['password']) ?></td>
                <td><?= htmlspecialchars($row['email_address'] ?? '') ?></td>
                <td>
                    <?php
                    $g = [];
                    for ($i = 1; $i <= 5; $i++) {
                        if (($row['group_flags'] ?? 0) & (1 << ($i - 1))) {
                            $g[] = "G$i";
                        }
                    }
                    echo htmlspecialchars(implode(', ', $g));
                    ?>
                </td>
                <td>
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="username" value="<?= htmlspecialchars($row['username']) ?>">
                        <input type="hidden" name="domain" value="<?= htmlspecialchars($row['domain']) ?>">
                        <input type="text" name="friendly_name" value="<?= htmlspecialchars($row['friendly_name'] ?? '') ?>" placeholder="name" style="width:100px">
                        <input type="password" name="password" placeholder="new password" required style="width:120px">
                        <span class="groups">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <label><input type="checkbox" name="group<?= $i ?>" value="1" <?= (($row['group_flags'] ?? 0) & (1 << ($i - 1))) ? 'checked' : '' ?>> G<?= $i ?></label>
                            <?php endfor; ?>
                        </span>
                        <button type="submit">Update</button>
                    </form>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete <?= htmlspecialchars($row['username']) ?>?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="username" value="<?= htmlspecialchars($row['username']) ?>">
                        <input type="hidden" name="domain" value="<?= htmlspecialchars($row['domain']) ?>">
                        <button type="submit" class="danger">Delete</button>
                    </form>
                </td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
