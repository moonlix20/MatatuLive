<?php
session_start();

// Block access if admin session is not active
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
require_once 'db.php';

$message = '';
$error = '';

// Handle form submission to add a new route
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_route') {
    $county_id = intval($_POST['county_id']);
    $route_name = trim($_POST['route_name']);
    $origin = trim($_POST['origin']);
    $destination = trim($_POST['destination']);
    $base_fare = floatval($_POST['base_fare']);
    $sacco_id = !empty($_POST['sacco_id']) ? intval($_POST['sacco_id']) : null;

    if ($county_id > 0 && !empty($route_name) && !empty($origin) && !empty($destination) && $base_fare > 0) {
        $stmt = $pdo->prepare("INSERT INTO routes (county_id, route_name, origin, destination, base_fare, sacco_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$county_id, $route_name, $origin, $destination, $base_fare, $sacco_id]);
        
        // Also create a default initial live_fare entry for this new route
        $route_id = $pdo->lastInsertId();
        $fare_stmt = $pdo->prepare("INSERT INTO live_fares (route_id, current_fare, status, notes) VALUES (?, ?, 'Normal', 'Initial base price set')");
        $fare_stmt->execute([$route_id, $base_fare]);

        $message = "New route successfully added!";
    } else {
        $error = "Please fill in all required fields correctly.";
    }
}

// Handle route deletion
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $del_stmt = $pdo->prepare("DELETE FROM routes WHERE id = ?");
    $del_stmt->execute([$delete_id]);
    header("Location: admin.php");
    exit;
}

// Fetch dropdown data
$counties = $pdo->query("SELECT * FROM counties ORDER BY name ASC")->fetchAll();
$saccos = $pdo->query("SELECT * FROM saccos ORDER BY sacco_name ASC")->fetchAll();

// Fetch all existing routes with details
$routes = $pdo->query("SELECT r.*, c.name as county_name, s.sacco_name 
                       FROM routes r 
                       JOIN counties c ON r.county_id = c.id 
                       LEFT JOIN saccos s ON r.sacco_id = s.id 
                       ORDER BY r.id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Matatu Live</title>
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
            --danger-red: #f85149;
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
            max-width: 1100px;
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
            max-width: 1100px;
            margin: 2rem auto;
            padding: 0 1rem;
            width: 100%;
            flex: 1;
        }

        .grid-layout {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
        }

        .card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: 0 8px 24px rgba(0,0,0,0.5);
            height: fit-content;
        }

        h2, h3 {
            color: var(--text-heading);
            margin-bottom: 1rem;
            font-size: 1.25rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        label {
            display: block;
            font-weight: 600;
            color: var(--text-heading);
            margin-bottom: 0.3rem;
            font-size: 0.85rem;
        }

        .form-control {
            width: 100%;
            background-color: var(--bg-body);
            border: 1px solid var(--border-color);
            color: var(--text-heading);
            padding: 0.6rem 0.8rem;
            border-radius: 6px;
            font-size: 0.9rem;
            outline: none;
        }

        .form-control:focus { border-color: var(--accent-blue); }

        .btn-submit {
            background-color: var(--accent-green);
            color: #fff;
            border: none;
            width: 100%;
            padding: 0.7rem;
            border-radius: 6px;
            font-weight: 700;
            cursor: pointer;
            font-size: 0.95rem;
            transition: background-color 0.2s;
            margin-top: 0.5rem;
        }

        .btn-submit:hover { background-color: var(--accent-green-hover); }

        .alert-success {
            background-color: rgba(35, 134, 54, 0.15);
            border: 1px solid rgba(35, 134, 54, 0.4);
            color: #3fb950;
            padding: 0.75rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            text-align: center;
        }

        .alert-error {
            background-color: rgba(248, 81, 73, 0.15);
            border: 1px solid rgba(248, 81, 73, 0.4);
            color: var(--danger-red);
            padding: 0.75rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            text-align: center;
        }

        /* TABLE STYLING */
        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
            text-align: left;
        }

        th, td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            background-color: rgba(22, 27, 34, 0.6);
            color: var(--text-heading);
            font-weight: 600;
        }

        td { color: var(--text-primary); }

        .btn-delete {
            background-color: rgba(248, 81, 73, 0.15);
            color: var(--danger-red);
            border: 1px solid rgba(248, 81, 73, 0.4);
            padding: 0.3rem 0.6rem;
            border-radius: 4px;
            font-size: 0.8rem;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.2s;
        }

        .btn-delete:hover {
            background-color: var(--danger-red);
            color: #fff;
        }

        footer {
            text-align: center;
            padding: 1.5rem;
            color: var(--text-muted);
            font-size: 0.85rem;
            border-top: 1px solid var(--border-color);
            background-color: var(--bg-card);
        }

        @media (max-width: 900px) {
            .grid-layout { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">🇰🇪 Matatu<span>Live</span> Admin</a>
            <a href="index.php" style="color: var(--text-primary); text-decoration: none; font-size: 0.9rem;">← View Live Board</a>
        </div>
    </nav>

    <div class="container">
        <div class="grid-layout">
            
            <!-- LEFT COLUMN: ADD ROUTE FORM -->
            <div class="card">
                <h3>Add New Route</h3>
                
                <?php if (!empty($message)): ?>
                    <div class="alert-success"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div class="alert-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form action="admin.php" method="POST">
                    <input type="hidden" name="action" value="add_route">

                    <div class="form-group">
                        <label for="county_id">County</label>
                        <select name="county_id" id="county_id" class="form-control" required>
                            <option value="">-- Select County --</option>
                            <?php foreach ($counties as $county): ?>
                                <option value="<?= $county['id'] ?>"><?= htmlspecialchars($county['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="route_name">Route Name</label>
                        <input type="text" name="route_name" id="route_name" class="form-control" placeholder="e.g. CBD to Ruiru" required>
                    </div>

                    <div class="form-group">
                        <label for="origin">Origin Stage</label>
                        <input type="text" name="origin" id="origin" class="form-control" placeholder="e.g. Bus Station" required>
                    </div>

                    <div class="form-group">
                        <label for="destination">Destination</label>
                        <input type="text" name="destination" id="destination" class="form-control" placeholder="e.g. Ruiru Town" required>
                    </div>

                    <div class="form-group">
                        <label for="base_fare">Normal Base Fare (KSh)</label>
                        <input type="number" step="0.01" name="base_fare" id="base_fare" class="form-control" placeholder="e.g. 80" required>
                    </div>

                    <div class="form-group">
                        <label for="sacco_id">SACCO (Optional)</label>
                        <select name="sacco_id" id="sacco_id" class="form-control">
                            <option value="">-- Independent / None --</option>
                            <?php foreach ($saccos as $sacco): ?>
                                <option value="<?= $sacco['id'] ?>"><?= htmlspecialchars($sacco['sacco_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn-submit">Add Route</button>
                </form>
            </div>

            <!-- RIGHT COLUMN: EXISTING ROUTES TABLE -->
            <div class="card">
                <h3>Managed Routes Database</h3>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>County</th>
                                <th>Route Name</th>
                                <th>Base Fare</th>
                                <th>SACCO</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($routes)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; color: var(--text-muted);">No routes configured yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($routes as $r): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($r['county_name']) ?></td>
                                        <td><strong><?= htmlspecialchars($r['route_name']) ?></strong><br><small style="color: var(--text-muted);"><?= htmlspecialchars($r['origin']) ?> → <?= htmlspecialchars($r['destination']) ?></small></td>
                                        <td>KSh <?= number_format($r['base_fare'], 2) ?></td>
                                        <td><?= htmlspecialchars($r['sacco_name'] ?? 'N/A') ?></td>
                                        <td>
                                            <a href="admin.php?delete_id=<?= $r['id'] ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this route?')">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <footer>
        <p>&copy; <?= date('Y') ?> Matatu Live Kenya. Admin Panel.</p>
    </footer>

</body>
</html>