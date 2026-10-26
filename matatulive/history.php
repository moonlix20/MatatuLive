<?php
require_once 'db.php';

// Fetch all fare history logs joined with route and county details
$logs_stmt = $pdo->query("SELECT l.*, r.route_name, r.origin, r.destination, c.name as county_name, s.sacco_name 
                          FROM fare_logs l 
                          JOIN routes r ON l.route_id = r.id 
                          JOIN counties c ON r.county_id = c.id 
                          LEFT JOIN saccos s ON r.sacco_id = s.id 
                          ORDER BY l.logged_at DESC LIMIT 50");
$logs = $logs_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surge & Fare History Log | Matatu Live</title>
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

        .card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 2rem;
            box-shadow: 0 8px 24px rgba(0,0,0,0.5);
        }

        .header-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 1rem;
        }

        h2 { color: var(--text-heading); font-size: 1.5rem; }
        p.subtitle { color: var(--text-muted); font-size: 0.9rem; }

        .timeline {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .log-item {
            background-color: var(--bg-body);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1.25rem;
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 1rem;
            align-items: center;
            transition: border-color 0.2s;
        }

        .log-item:hover { border-color: #444c56; }

        .log-title {
            font-size: 1.1rem;
            color: var(--text-heading);
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .log-meta {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .log-pricing {
            text-align: right;
        }

        .price-tag {
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--text-heading);
        }

        .status-badge {
            display: inline-block;
            font-size: 0.75rem;
            padding: 0.2rem 0.6rem;
            border-radius: 6px;
            font-weight: 700;
            text-transform: uppercase;
            margin-top: 0.3rem;
        }

        .status-surge { background-color: var(--surge-bg); color: var(--surge-red); border: 1px solid rgba(248, 81, 73, 0.4); }
        .status-normal { background-color: var(--normal-bg); color: #3fb950; border: 1px solid rgba(63, 185, 80, 0.4); }

        .notes-text {
            font-size: 0.85rem;
            color: #d29922;
            margin-top: 0.5rem;
            font-style: italic;
        }

        .no-logs {
            text-align: center;
            padding: 3rem;
            color: var(--text-muted);
        }

        footer { text-align: center; padding: 1.5rem; color: var(--text-muted); font-size: 0.85rem; border-top: 1px solid var(--border-color); background-color: var(--bg-card); }

        @media (max-width: 768px) {
            .log-item { grid-template-columns: 1fr; }
            .log-pricing { text-align: left; }
        }
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
            <div class="header-flex">
                <div>
                    <h2>Surge & Fare History Timeline</h2>
                    <p class="subtitle">Chronological record of recent fare adjustments and weather/traffic spikes.</p>
                </div>
                <span style="font-size: 0.85rem; color: var(--text-muted);">Last 50 Events</span>
            </div>

            <div class="timeline">
                <?php if (empty($logs)): ?>
                    <div class="no-logs">
                        <h3>No fare history logs recorded yet.</h3>
                        <p>When users report fare updates, they will appear here automatically.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <div class="log-item">
                            <div>
                                <div class="log-title"><?= htmlspecialchars($log['route_name']) ?></div>
                                <div class="log-meta">
                                    <strong>County:</strong> <?= htmlspecialchars($log['county_name']) ?> | 
                                    <strong>Route:</strong> <?= htmlspecialchars($log['origin']) ?> → <?= htmlspecialchars($log['destination']) ?> | 
                                    <strong>SACCO:</strong> <?= htmlspecialchars($log['sacco_name'] ?? 'Independent') ?>
                                </div>
                                <?php if (!empty($log['notes'])): ?>
                                    <div class="notes-text">💬 "<?= htmlspecialchars($log['notes']) ?>"</div>
                                <?php endif; ?>
                                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.4rem;">
                                    Logged at: <?= date('M d, Y - H:i:s', strtotime($log['logged_at'])) ?>
                                </div>
                            </div>

                            <div class="log-pricing">
                                <div class="price-tag" style="color: <?= ($log['new_fare'] > 100) ? 'var(--surge-red)' : 'var(--text-heading)' ?>;">
                                    KSh <?= number_format($log['new_fare'], 2) ?>
                                </div>
                                <?php 
                                    $status = $log['status'];
                                    $statusClass = (stripos($status, 'Surge') !== false || stripos($status, 'Hike') !== false) ? 'status-surge' : 'status-normal';
                                ?>
                                <div><span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars($status) ?></span></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; <?= date('Y') ?> Matatu Live Kenya.</p>
    </footer>

</body>
</html>