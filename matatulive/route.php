<?php
require_once 'db.php';

$route_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = '';
$error = '';

if ($route_id <= 0) {
    header("Location: index.php");
    exit;
}

// Handle quick-report submission from the route detail page
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_fare = floatval($_POST['current_fare']);
    $status = trim($_POST['status']);
    $notes = trim($_POST['notes']);

    if ($current_fare > 0) {
        // Check if live fare exists
        $check = $pdo->prepare("SELECT id FROM live_fares WHERE route_id = ?");
        $check->execute([$route_id]);
        
        if ($check->fetch()) {
            $update = $pdo->prepare("UPDATE live_fares SET current_fare = ?, status = ?, notes = ?, updated_at = NOW() WHERE route_id = ?");
            $update->execute([$current_fare, $status, $notes, $route_id]);
        } else {
            $insert = $pdo->prepare("INSERT INTO live_fares (route_id, current_fare, status, notes) VALUES (?, ?, ?, ?)");
            $insert->execute([$route_id, $current_fare, $status, $notes]);
        }
        $message = "Live fare successfully updated!";
    } else {
        $error = "Please enter a valid fare amount.";
    }
}

// Fetch route and live fare details
$stmt = $pdo->prepare("SELECT r.*, c.name as county_name, s.sacco_name, f.current_fare, f.status, f.notes, f.updated_at 
                       FROM routes r 
                       JOIN counties c ON r.county_id = c.id 
                       LEFT JOIN saccos s ON r.sacco_id = s.id 
                       LEFT JOIN live_fares f ON r.id = f.route_id 
                       WHERE r.id = ?");
$stmt->execute([$route_id]);
$route = $stmt->fetch();

if (!$route) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><%= htmlspecialchars($route['route_name']) ?> | Matatu Live</title>
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
            --surge-red: #f85149;
            --surge-bg: rgba(248, 81, 73, 0.15);
            --normal-bg: rgba(35, 134, 54, 0.15);
            --radius: 12px;
            --font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
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

        .logo { font-size: 1.3rem; font-weight: 800; color: var(--text-heading); text-decoration: none; }
        .logo span { color: var(--accent-blue); }

        .container {
            max-width: 800px;
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
            margin-bottom: 2rem;
        }

        .route-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 1rem;
        }

        h1 { color: var(--text-heading); font-size: 1.75rem; margin-bottom: 0.5rem; }
        
        .county-badge {
            font-size: 0.8rem;
            background-color: #21262d;
            color: var(--accent-blue);
            padding: 0.25rem 0.7rem;
            border-radius: 20px;
            border: 1px solid var(--border-color);
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-badge {
            font-size: 0.8rem;
            padding: 0.3rem 0.8rem;
            border-radius: 6px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .status-surge { background-color: var(--surge-bg); color: var(--surge-red); border: 1px solid rgba(248, 81, 73, 0.4); }
        .status-normal { background-color: var(--normal-bg); color: #3fb950; border: 1px solid rgba(63, 185, 80, 0.4); }

        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .detail-box {
            background-color: var(--bg-body);
            border: 1px solid var(--border-color);
            padding: 1rem;
            border-radius: 8px;
        }

        .detail-title { font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.25rem; }
        .detail-value { font-size: 1.15rem; color: var(--text-heading); font-weight: 700; }

        .notes-box {
            background-color: rgba(210, 153, 34, 0.1);
            border: 1px solid rgba(210, 153, 34, 0.3);
            color: #d29922;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
        }

        /* FORM STYLING */
        h3 { color: var(--text-heading); margin-bottom: 1rem; font-size: 1.2rem; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; font-weight: 600; color: var(--text-heading); margin-bottom: 0.4rem; font-size: 0.85rem; }
        .form-control {
            width: 100%;
            background-color: var(--bg-body);
            border: 1px solid var(--border-color);
            color: var(--text-heading);
            padding: 0.7rem 0.9rem;
            border-radius: 6px;
            font-size: 0.95rem;
            outline: none;
        }
        .form-control:focus { border-color: var(--accent-blue); }

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
        }
        .btn-submit:hover { background-color: var(--accent-green-hover); }

        .alert-success { background-color: rgba(35, 134, 54, 0.15); border: 1px solid rgba(35, 134, 54, 0.4); color: #3fb950; padding: 0.75rem; border-radius: 6px; margin-bottom: 1rem; text-align: center; }
        .alert-error { background-color: rgba(248, 81, 73, 0.15); border: 1px solid rgba(248, 81, 73, 0.4); color: #f85149; padding: 0.75rem; border-radius: 6px; margin-bottom: 1rem; text-align: center; }

        .back-link { display: inline-block; margin-top: 1rem; color: var(--accent-blue); text-decoration: none; font-size: 0.9rem; }
        .back-link:hover { text-decoration: underline; }

        footer { text-align: center; padding: 1.5rem; color: var(--text-muted); font-size: 0.85rem; border-top: 1px solid var(--border-color); background-color: var(--bg-card); }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">🇰🇪 Matatu<span>Live</span></a>
            <a href="index.php" style="color: var(--text-primary); text-decoration: none; font-size: 0.9rem;">← Back to Live Board</a>
        </div>
    </nav>

    <div class="container">
        <div class="card">
            <div class="route-header">
                <div>
                    <span class="county-badge"><?= htmlspecialchars($route['county_name']) ?></span>
                    <h1><?= htmlspecialchars($route['route_name']) ?></h1>
                    <p style="color: var(--text-muted); font-size: 0.95rem; margin-top: 0.25rem;">
                        <?= htmlspecialchars($route['origin']) ?> &rarr; <?= htmlspecialchars($route['destination']) ?>
                    </p>
                </div>
                <?php 
                    $status = $route['status'] ?? 'Normal';
                    $statusClass = (stripos($status, 'Surge') !== false || stripos($status, 'Hike') !== false) ? 'status-surge' : 'status-normal';
                ?>
                <span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars($status) ?></span>
            </div>

            <div class="details-grid">
                <div class="detail-box">
                    <div class="detail-title">Normal Base Fare</div>
                    <div class="detail-value">KSh <?= number_format($route['base_fare'], 2) ?></div>
                </div>
                <div class="detail-box">
                    <div class="detail-title">Current Live Fare</div>
                    <div class="detail-value" style="color: <?= ($route['current_fare'] > $route['base_fare']) ? 'var(--surge-red)' : '#3fb950' ?>;">
                        KSh <?= number_format($route['current_fare'] ?? $route['base_fare'], 2) ?>
                    </div>
                </div>
                <div class="detail-box">
                    <div class="detail-title">Operating SACCO</div>
                    <div class="detail-value" style="font-size: 1rem;"><?= htmlspecialchars($route['sacco_name'] ?? 'Independent / General') ?></div>
                </div>
                <div class="detail-box">
                    <div class="detail-title">Last Updated</div>
                    <div class="detail-value" style="font-size: 0.95rem;"><?= $route['updated_at'] ? date('M d, Y - H:i', strtotime($route['updated_at'])) : 'Never' ?></div>
                </div>
            </div>

            <?php if (!empty($route['notes'])): ?>
                <div class="notes-box">
                    <strong>⚠️ Latest Commuter / SACCO Report:</strong> <?= htmlspecialchars($route['notes']) ?>
                </div>
            <?php endif; ?>

            <!-- QUICK REPORT FORM FOR THIS ROUTE -->
            <div style="margin-top: 2rem; border-top: 1px solid var(--border-color); padding-top: 1.5rem;">
                <h3>Update Fare for this Route</h3>

                <?php if (!empty($message)): ?>
                    <div class="alert-success"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div class="alert-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form action="route.php?id=<?= $route['id'] ?>" method="POST">
                    <div class="form-group">
                        <label for="current_fare">New Current Fare (KSh)</label>
                        <input type="number" step="0.01" name="current_fare" id="current_fare" class="form-control" value="<?= htmlspecialchars($route['current_fare'] ?? $route['base_fare']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="status">Status / Condition</label>
                        <select name="status" id="status" class="form-control" required>
                            <option value="Normal" <?= $status == 'Normal' ? 'selected' : '' ?>>Normal Fare</option>
                            <option value="Surge" <?= $status == 'Surge' ? 'selected' : '' ?>>Surge Price / Price Hike</option>
                            <option value="Rain Hike" <?= $status == 'Rain Hike' ? 'selected' : '' ?>>Rain Hike</option>
                            <option value="Heavy Traffic" <?= $status == 'Heavy Traffic' ? 'selected' : '' ?>>Heavy Traffic Delay</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="notes">Notes / Reason (Optional)</label>
                        <input type="text" name="notes" id="notes" class="form-control" placeholder="e.g. Queue at stage is long, traffic building up">
                    </div>

                    <button type="submit" class="btn-submit">Submit Update</button>
                </form>
            </div>

            <a href="index.php" class="back-link">&larr; Return to All Routes</a>
        </div>
    </div>

    <footer>
        <p>&copy; <?= date('Y') ?> Matatu Live Kenya.</p>
    </footer>

</body>
</html>