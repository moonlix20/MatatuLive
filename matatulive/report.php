<?php
require_once 'db.php';

$message = '';
$error = '';

// Handle form submission when user reports a fare change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $route_id = intval($_POST['route_id']);
    $current_fare = floatval($_POST['current_fare']);
    $status = trim($_POST['status']);
    $notes = trim($_POST['notes']);

    if ($route_id > 0 && $current_fare > 0) {
        // 1. Fetch current/previous fare for logging
        $fetch_stmt = $pdo->prepare("SELECT current_fare FROM live_fares WHERE route_id = ?");
        $fetch_stmt->execute([$route_id]);
        $existing = $fetch_stmt->fetch();
        $previous_fare = $existing ? $existing['current_fare'] : $current_fare;

        if ($existing) {
            // 2. Update active live fare
            $update_stmt = $pdo->prepare("UPDATE live_fares SET current_fare = ?, status = ?, notes = ?, updated_at = NOW() WHERE route_id = ?");
            $update_stmt->execute([$current_fare, $status, $notes, $route_id]);
        } else {
            // Insert live fare if it didn't exist
            $insert_stmt = $pdo->prepare("INSERT INTO live_fares (route_id, current_fare, status, notes) VALUES (?, ?, ?, ?)");
            $insert_stmt->execute([$route_id, $current_fare, $status, $notes]);
        }

        // 3. Automatically record entry in fare_logs table
        $log_stmt = $pdo->prepare("INSERT INTO fare_logs (route_id, previous_fare, new_fare, status, notes) VALUES (?, ?, ?, ?, ?)");
        $log_stmt->execute([$route_id, $previous_fare, $current_fare, $status, $notes]);

        $message = "Fare update successfully submitted! Redirecting to live board...";
        header("refresh:2;url=index.php");
        exit;
    } else {
        $error = "Please fill in all required fields with valid numbers.";
    }
}

// Fetch all available routes for the dropdown selection
$routes_stmt = $pdo->query("SELECT r.id, r.route_name, r.origin, r.destination, c.name as county_name 
                            FROM routes r 
                            JOIN counties c ON r.county_id = c.id 
                            ORDER BY c.name, r.route_name ASC");
$routes = $routes_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Fare Hike | Matatu Live</title>
    <style>
        :root {
            --bg-body: #0d1117;
            --bg-card: #161b22;
            --border-color: #30363d;
            --text-primary: #c9d1d9;
            --text-heading: #f0f6fc;
            --text-muted: #8b949e;
            --accent-green: #238636;
            --accent-green-hover: #2ea043;
            --accent-blue: #58a6ff;
            --radius: 12px;
            --font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background-color: var(--bg-body);
            color: var(--text-primary);
            font-family: var(--font-family);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .navbar {
            background-color: rgba(22, 27, 34, 0.9);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 1.5rem;
        }

        .nav-container {
            max-width: 800px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--text-heading);
            text-decoration: none;
        }

        .logo span { color: var(--accent-blue); }

        .container {
            max-width: 650px;
            margin: 2rem auto;
            padding: 0 1rem;
            width: 100%;
            flex: 1;
        }

        .card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 2rem;
            box-shadow: 0 8px 24px rgba(0,0,0,0.5);
        }

        h2 {
            color: var(--text-heading);
            margin-bottom: 0.5rem;
            font-size: 1.5rem;
        }

        p.subtitle {
            color: var(--text-muted);
            font-size: 0.95rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        label {
            display: block;
            font-weight: 600;
            color: var(--text-heading);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .form-control {
            width: 100%;
            background-color: var(--bg-body);
            border: 1px solid var(--border-color);
            color: var(--text-heading);
            padding: 0.75rem 1rem;
            border-radius: 6px;
            font-size: 0.95rem;
            outline: none;
        }

        .form-control:focus {
            border-color: var(--accent-blue);
        }

        .btn-submit {
            background-color: var(--accent-green);
            color: #fff;
            border: none;
            width: 100%;
            padding: 0.75rem;
            border-radius: 6px;
            font-weight: 700;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.2s;
            margin-top: 1rem;
        }

        .btn-submit:hover {
            background-color: var(--accent-green-hover);
        }

        .alert-success {
            background-color: rgba(35, 134, 54, 0.15);
            border: 1px solid rgba(35, 134, 54, 0.4);
            color: #3fb950;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: 600;
        }

        .alert-error {
            background-color: rgba(248, 81, 73, 0.15);
            border: 1px solid rgba(248, 81, 73, 0.4);
            color: #f85149;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .back-link {
            display: inline-block;
            margin-top: 1rem;
            color: var(--accent-blue);
            text-decoration: none;
            font-size: 0.9rem;
        }

        .back-link:hover { text-decoration: underline; }

        footer {
            text-align: center;
            padding: 1.5rem;
            color: var(--text-muted);
            font-size: 0.85rem;
            border-top: 1px solid var(--border-color);
            background-color: var(--bg-card);
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">🇰🇪 Matatu<span>Live</span></a>
            <a href="index.php" style="color: var(--text-primary); text-decoration: none; font-size: 0.9rem;">← Back to Home</a>
        </div>
    </nav>

    <div class="container">
        <div class="card">
            <h2>Report a Live Fare / Surge</h2>
            <p class="subtitle">Help fellow commuters by sharing current stage prices and traffic or weather delays.</p>

            <?php if (!empty($message)): ?>
                <div class="alert-success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form action="report.php" method="POST">
                <div class="form-group">
                    <label for="route_id">Select Route & Stage</label>
                    <select name="route_id" id="route_id" class="form-control" required>
                        <option value="">-- Choose a Route --</option>
                        <?php foreach ($routes as $route): ?>
                            <option value="<?= $route['id'] ?>">
                                [<?= htmlspecialchars($route['county_name']) ?>] <?= htmlspecialchars($route['route_name']) ?> (<?= htmlspecialchars($route['origin']) ?> → <?= htmlspecialchars($route['destination']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="current_fare">Current Fare (KSh)</label>
                    <input type="number" step="0.01" name="current_fare" id="current_fare" class="form-control" placeholder="e.g. 150" required>
                </div>

                <div class="form-group">
                    <label for="status">Fare Status / Condition</label>
                    <select name="status" id="status" class="form-control" required>
                        <option value="Normal">Normal Fare</option>
                        <option value="Surge">Surge Price / Price Hike</option>
                        <option value="Rain Hike">Rain Hike</option>
                        <option value="Heavy Traffic">Heavy Traffic Delay</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="notes">Additional Notes (Optional)</label>
                    <input type="text" name="notes" id="notes" class="form-control" placeholder="e.g. Long queue at stage, heavy traffic near round-about">
                </div>

                <button type="submit" class="btn-submit">Submit Live Update</button>
            </form>

            <a href="index.php" class="back-link">&larr; Return to Live Dashboard</a>
        </div>
    </div>

    <footer>
        <p>&copy; <?= date('Y') ?> Matatu Live Kenya.</p>
    </footer>

</body>
</html>