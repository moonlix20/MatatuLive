<?php
require_once 'db.php';

// Fetch all SACCOs along with their route count and active routes
$saccos_stmt = $pdo->query("SELECT s.id, s.sacco_name, 
                            COUNT(r.id) as total_routes 
                            FROM saccos s 
                            LEFT JOIN routes r ON s.id = r.sacco_id 
                            GROUP BY s.id, s.sacco_name 
                            ORDER BY total_routes DESC");
$saccos = $saccos_stmt->fetchAll();

// Fetch routes grouped by SACCO for detailed listing
$routes_stmt = $pdo->query("SELECT r.route_name, r.origin, r.destination, r.base_fare, s.id as sacco_id, c.name as county_name 
                            FROM routes r 
                            JOIN counties c ON r.county_id = c.id 
                            JOIN saccos s ON r.sacco_id = s.id 
                            ORDER BY s.sacco_name ASC");
$sacco_routes = [];
while ($row = $routes_stmt->fetch()) {
    $sacco_routes[$row['sacco_id']][] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SACCO Directory | Matatu Live</title>
    <style>
        :root {
            --bg-body: #0d1117;
            --bg-card: #161b22;
            --border-color: #30363d;
            --text-primary: #c9d1d9;
            --text-heading: #f0f6fc;
            --text-muted: #8b949e;
            --accent-blue: #58a6ff;
            --accent-green: #238636;
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
            max-width: 1000px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo { font-size: 1.3rem; font-weight: 800; color: var(--text-heading); text-decoration: none; }
        .logo span { color: var(--accent-blue); }

        .container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1rem;
            width: 100%;
            flex: 1;
        }

        .header-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 1rem;
        }

        h2 { color: var(--text-heading); font-size: 1.5rem; }
        p.subtitle { color: var(--text-muted); font-size: 0.9rem; }

        .saccos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .sacco-card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 8px 24px rgba(0,0,0,0.4);
            transition: transform 0.2s, border-color 0.2s;
        }

        .sacco-card:hover {
            transform: translateY(-3px);
            border-color: #444c56;
        }

        .sacco-name {
            font-size: 1.25rem;
            color: var(--text-heading);
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .route-count {
            display: inline-block;
            background-color: rgba(88, 166, 255, 0.15);
            color: var(--accent-blue);
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .route-list {
            list-style: none;
            margin-top: 1rem;
            border-top: 1px solid var(--border-color);
            padding-top: 0.75rem;
            font-size: 0.85rem;
        }

        .route-list li {
            margin-bottom: 0.5rem;
            color: var(--text-muted);
            display: flex;
            justify-content: space-between;
        }

        .route-list li strong {
            color: var(--text-primary);
        }

        .no-saccos {
            grid-column: 1 / -1;
            text-align: center;
            padding: 3rem;
            color: var(--text-muted);
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
        }

        footer { text-align: center; padding: 1.5rem; color: var(--text-muted); font-size: 0.85rem; border-top: 1px solid var(--border-color); background-color: var(--bg-card); }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">🇰🇪 Matatu<span>Live</span> SACCOs</a>
            <a href="index.php" style="color: var(--text-primary); text-decoration: none; font-size: 0.9rem;">← Back to Live Board</a>
        </div>
    </nav>

    <div class="container">
        <div class="header-flex">
            <div>
                <h2>Registered Transit SACCOs</h2>
                <p class="subtitle">Explore registered matatu operators, fleet coverage, and active route assignments.</p>
            </div>
            <span style="font-size: 0.85rem; color: var(--text-muted);">Verified Operators</span>
        </div>

        <div class="saccos-grid">
            <?php if (empty($saccos)): ?>
                <div class="no-saccos">
                    <h3>No SACCOs registered yet</h3>
                    <p>Add routes with assigned SACCOs from the admin dashboard.</p>
                </div>
            <?php else: ?>
                <?php foreach ($saccos as $sacco): ?>
                    <div class="sacco-card">
                        <div>
                            <h3 class="sacco-name"><?= htmlspecialchars($sacco['sacco_name']) ?></h3>
                            <span class="route-count"><?= $sacco['total_routes'] ?> Active Route(s)</span>

                            <ul class="route-list">
                                <?php if (isset($sacco_routes[$sacco['id']])): ?>
                                    <?php foreach ($sacco_routes[$sacco['id']] as $rt): ?>
                                        <li>
                                            <span><strong><?= htmlspecialchars($rt['route_name']) ?></strong><br><small><?= htmlspecialchars($rt['origin']) ?> → <?= htmlspecialchars($rt['destination']) ?></small></span>
                                            <span style="color: var(--accent-green); font-weight: 600;">KSh <?= number_format($rt['base_fare'], 0) ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li style="font-style: italic;">No specific routes assigned yet.</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        <p>&copy; <?= date('Y') ?> Matatu Live Kenya. SACCO Directory.</p>
    </footer>

</body>
</html>