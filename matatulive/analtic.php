<?php
require_once 'db.php';

// 1. Fetch Key Statistics
$total_routes = $pdo->query("SELECT COUNT(*) FROM routes")->fetchColumn();
$active_surges = $pdo->query("SELECT COUNT(*) FROM live_fares WHERE status != 'Normal'")->fetchColumn();
$total_reports = $pdo->query("SELECT COUNT(*) FROM fare_logs")->fetchColumn();

// 2. Fetch Average Base Fare vs Current Fare by County
$county_stats = $pdo->query("SELECT c.name as county_name, 
                             COUNT(r.id) as route_count, 
                             AVG(r.base_fare) as avg_base, 
                             AVG(f.current_fare) as avg_current 
                             FROM counties c 
                             LEFT JOIN routes r ON c.id = r.county_id 
                             LEFT JOIN live_fares f ON r.id = f.route_id 
                             GROUP BY c.id, c.name 
                             ORDER BY route_count DESC")->fetchAll();

// 3. Fetch Most Volatile / Frequently Updated Routes
$volatile_routes = $pdo->query("SELECT r.route_name, r.origin, r.destination, c.name as county_name, COUNT(l.id) as update_count 
                                FROM fare_logs l 
                                JOIN routes r ON l.route_id = r.id 
                                JOIN counties c ON r.county_id = c.id 
                                GROUP BY r.id 
                                ORDER BY update_count DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fare Analytics & Insights | Matatu Live</title>
    <style>
        :root {
            --bg-body: #0d1117;
            --bg-card: #161b22;
            --border-color: #30363d;
            --text-primary: #c9d1d9;
            --text-heading: #f0f6fc;
            --text-muted: #8b949e;
            --accent-blue: #58a6ff;
            --surge-red: #f85149;
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
            max-width: 1100px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo { font-size: 1.3rem; font-weight: 800; color: var(--text-heading); text-decoration: none; }
        .logo span { color: var(--accent-blue); }

        .container {
            max-width: 1100px;
            margin: 2rem auto;
            padding: 0 1rem;
            width: 100%;
            flex: 1;
        }

        /* METRICS CARDS */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .metric-card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: 0 8px 24px rgba(0,0,0,0.4);
        }

        .metric-title { font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.5rem; }
        .metric-value { font-size: 2rem; font-weight: 800; color: var(--text-heading); }

        /* TABLES SECTION */
        .card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 24px rgba(0,0,0,0.4);
        }

        h2, h3 { color: var(--text-heading); margin-bottom: 1rem; font-size: 1.25rem; }

        .table-responsive { overflow-x: auto; }

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

        th { background-color: rgba(22, 27, 34, 0.6); color: var(--text-heading); font-weight: 600; }
        td { color: var(--text-primary); }

        footer { text-align: center; padding: 1.5rem; color: var(--text-muted); font-size: 0.85rem; border-top: 1px solid var(--border-color); background-color: var(--bg-card); }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">🇰🇪 Matatu<span>Live</span> Analytics</a>
            <a href="index.php" style="color: var(--text-primary); text-decoration: none; font-size: 0.9rem;">← Back to Live Board</a>
        </div>
    </nav>

    <div class="container">
        
        <!-- TOP METRICS SUMMARY -->
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-title">Total Monitored Routes</div>
                <div class="metric-value"><?= $total_routes ?></div>
            </div>
            <div class="metric-card">
                <div class="metric-title">Active Surges / Delays</div>
                <div class="metric-value" style="color: var(--surge-red);"><?= $active_surges ?></div>
            </div>
            <div class="metric-card">
                <div class="metric-title">Total Crowdsourced Reports</div>
                <div class="metric-value" style="color: var(--accent-blue);"><?= $total_reports ?></div>
            </div>
        </div>

        <!-- COUNTY BREAKDOWN TABLE -->
        <div class="card">
            <h3>County-wise Fare Overview</h3>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>County</th>
                            <th>Active Routes</th>
                            <th>Avg. Base Fare</th>
                            <th>Avg. Current Live Fare</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($county_stats as $stat): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($stat['county_name']) ?></strong></td>
                                <td><?= $stat['route_count'] ?></td>
                                <td>KSh <?= number_format($stat['avg_base'] ?? 0, 2) ?></td>
                                <td style="font-weight: 700; color: <?= ($stat['avg_current'] > $stat['avg_base']) ? 'var(--surge-red)' : '#3fb950' ?>;">
                                    KSh <?= number_format($stat['avg_current'] ?? 0, 2) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MOST VOLATILE ROUTES -->
        <div class="card">
            <h3>Most Volatile Routes (Frequent Price Hikes)</h3>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Route Name</th>
                            <th>County</th>
                            <th>Stage Progression</th>
                            <th>Total Price Updates</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($volatile_routes)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: var(--text-muted);">No volatility data recorded yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($volatile_routes as $v): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($v['route_name']) ?></strong></td>
                                    <td><?= htmlspecialchars($v['county_name']) ?></td>
                                    <td><?= htmlspecialchars($v['origin']) ?> → <?= htmlspecialchars($v['destination']) ?></td>
                                    <td><span style="background: rgba(88, 166, 255, 0.15); color: var(--accent-blue); padding: 0.2rem 0.6rem; border-radius: 4px; font-weight: 600;"><?= $v['update_count'] ?> updates</span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <footer>
        <p>&copy; <?= date('Y') ?> Matatu Live Kenya. Transit Intelligence Dashboard.</p>
    </footer>

</body>
</html>