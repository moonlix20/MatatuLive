<?php
require_once 'db.php';

// Fetch all counties for the dropdown filter
$counties_stmt = $pdo->query("SELECT * FROM counties ORDER BY name ASC");
$counties = $counties_stmt->fetchAll();

// Get filter inputs from URL parameters
$selected_county = isset($_GET['county_id']) ? $_GET['county_id'] : '';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build dynamic SQL query for routes and live fares
$sql = "SELECT r.*, c.name as county_name, s.sacco_name, f.current_fare, f.status, f.notes, f.updated_at 
        FROM routes r 
        JOIN counties c ON r.county_id = c.id 
        LEFT JOIN saccos s ON r.sacco_id = s.id 
        LEFT JOIN live_fares f ON r.id = f.route_id 
        WHERE 1=1";

$params = [];

if (!empty($selected_county)) {
    $sql .= " AND r.county_id = :county_id";
    $params[':county_id'] = $selected_county;
}

if (!empty($search_query)) {
    $sql .= " AND (r.route_name LIKE :search OR r.origin LIKE :search OR r.destination LIKE :search)";
    $params[':search'] = '%' . $search_query . '%';
}

$sql .= " ORDER BY f.updated_at DESC LIMIT 12";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$routes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Matatu Live | Real-Time Fare Tracking & Surge Detection</title>
    <style>
        /* DARK THEME DESIGN SYSTEM */
        :root {
            --bg-body: #0d1117;
            --bg-card: #161b22;
            --bg-card-hover: #1c2128;
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
            --font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-body);
            color: var(--text-primary);
            font-family: var(--font-family);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* NAVIGATION BAR */
        .navbar {
            background-color: rgba(22, 27, 34, 0.9);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--text-heading);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logo span {
            color: var(--accent-blue);
        }

        .nav-links {
            display: flex;
            gap: 1.5rem;
            list-style: none;
            align-items: center;
        }

        .nav-links a {
            color: var(--text-primary);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            transition: color 0.2s ease;
        }

        .nav-links a:hover {
            color: var(--accent-blue);
        }

        .btn-action {
            background-color: var(--accent-green);
            color: #ffffff !important;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 600;
            transition: background-color 0.2s ease !important;
        }

        .btn-action:hover {
            background-color: var(--accent-green-hover) !important;
        }

        /* HERO SECTION WITH BLENDED BUS/MATATU BACKGROUND */
        .hero-section {
            position: relative;
            background: linear-gradient(rgba(13, 17, 23, 0.85), rgba(13, 17, 23, 0.95)), 
                        url('https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&w=1600&q=80') center/cover no-repeat;
            border-bottom: 1px solid var(--border-color);
            padding: 4rem 1.5rem;
            text-align: center;
        }

        .hero-content {
            max-width: 800px;
            margin: 0 auto;
        }

        .hero-title {
            font-size: 2.5rem;
            color: var(--text-heading);
            margin-bottom: 1rem;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .hero-subtitle {
            color: var(--text-muted);
            font-size: 1.1rem;
            margin-bottom: 2.5rem;
        }

        /* SEARCH & FILTER FORM */
        .filter-form {
            background-color: rgba(22, 27, 34, 0.9);
            border: 1px solid var(--border-color);
            padding: 1.25rem;
            border-radius: var(--radius);
            display: grid;
            grid-template-columns: 1fr 1.5fr auto;
            gap: 1rem;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
        }

        .form-control {
            background-color: var(--bg-body);
            border: 1px solid var(--border-color);
            color: var(--text-heading);
            padding: 0.75rem 1rem;
            border-radius: 6px;
            font-size: 0.95rem;
            outline: none;
            width: 100%;
            transition: border-color 0.2s;
        }

        .form-control:focus {
            border-color: var(--accent-blue);
        }

        .btn-search {
            background-color: var(--accent-blue);
            color: #ffffff;
            border: none;
            padding: 0 1.5rem;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        .btn-search:hover {
            opacity: 0.9;
        }

        /* CONTAINER */
        .container {
            max-width: 1200px;
            margin: 2.5rem auto;
            padding: 0 1.5rem;
            flex: 1;
            width: 100%;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 0.75rem;
        }

        .section-title {
            font-size: 1.35rem;
            color: var(--text-heading);
            font-weight: 700;
        }

        /* FARE CARDS GRID */
        .fares-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }

        .fare-card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform 0.2s ease, border-color 0.2s ease;
        }

        .fare-card:hover {
            transform: translateY(-3px);
            border-color: #444c56;
        }

        .card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .route-name {
            font-size: 1.15rem;
            color: var(--text-heading);
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .county-badge {
            font-size: 0.75rem;
            background-color: #21262d;
            color: var(--accent-blue);
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            border: 1px solid var(--border-color);
            font-weight: 600;
            text-transform: uppercase;
        }

        /* STATUS BADGES */
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.6rem;
            border-radius: 6px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .status-surge {
            background-color: var(--surge-bg);
            color: var(--surge-red);
            border: 1px solid rgba(248, 81, 73, 0.4);
        }

        .status-normal {
            background-color: var(--normal-bg);
            color: #3fb950;
            border: 1px solid rgba(63, 185, 80, 0.4);
        }

        .route-details {
            margin: 1rem 0;
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        .route-details p {
            margin-bottom: 0.3rem;
        }

        .fare-pricing {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: var(--bg-body);
            padding: 0.85rem 1rem;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            margin-top: 1rem;
        }

        .base-price {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .current-price {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--text-heading);
        }

        .notes-box {
            font-size: 0.85rem;
            color: #d29922;
            margin-top: 0.75rem;
            font-style: italic;
        }

        .no-results {
            grid-column: 1 / -1;
            text-align: center;
            padding: 3rem;
            color: var(--text-muted);
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
        }

        /* FOOTER */
        footer {
            background-color: var(--bg-card);
            border-top: 1px solid var(--border-color);
            padding: 2rem 0;
            margin-top: 4rem;
            text-align: center;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        /* RESPONSIVE DESIGN */
        @media (max-width: 768px) {
            .filter-form {
                grid-template-columns: 1fr;
            }
            .hero-title {
                font-size: 1.9rem;
            }
        }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">🇰🇪 Matatu<span>Live</span></a>
            <ul class="nav-links">
                <li><a href="index.php">Live Fares</a></li>
                <li><a href="admin.php" style="color: var(--accent-blue);">Admin Panel</a></li>
                 <li><a href="route.php" style="color: var(--accent-blue);">route</a></li>
                 <li><a href="history.php" style="color: var(--accent-blue);">history panel</a></li>
                 <li><a href="analtic.php" style="color: var(--accent-blue);">Analytics</a></li>
                 <li><a href="sacco.php" style="color: var(--text-primary);">SACCOS</a></li>
                 <li><a href="calculator.php" style="color: var(--text-primary);">calculator panel</a></li
                 <li><a href="API/fare.php" target="_blank" style="color: var(--accent-blue);">API</a></li>
                <li><a href="report.php" class="btn-action">+ Report Fare Hike</a></li>
               
                <li><a href="logout.php" class="btn-action">logout</a></li>
            </ul>
        </div>
    </nav>

    <!-- HERO SECTION WITH BLENDED TRANSIT BACKGROUND -->
    <header class="hero-section">
        <div class="hero-content">
            <h1 class="hero-title">Live Matatu Fares & Surge Tracker</h1>
            <p class="hero-subtitle">Check real-time fare hikes, avoid surprise surges, and track stage prices across Nairobi and all 47 counties.</p>
            
            <!-- COUNTY & ROUTE FILTER FORM -->
            <form action="index.php" method="GET" class="filter-form">
                <select name="county_id" class="form-control">
                    <option value="">Select County (All)</option>
                    <?php foreach ($counties as $county): ?>
                        <option value="<?= $county['id'] ?>" <?= $selected_county == $county['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($county['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <input type="text" name="search" class="form-control" placeholder="Search stage, route or destination (e.g. Westlands, Rongai)..." value="<?= htmlspecialchars($search_query) ?>">

                <button type="submit" class="btn-search">Filter Fares</button>
            </form>
        </div>
    </header>

    <!-- MAIN FARES CONTAINER -->
    <main class="container">
        <div class="section-header">
            <h2 class="section-title">
                <?= !empty($selected_county) ? 'Filtered Routes' : 'Live Updated Routes & Fares' ?>
            </h2>
            <span style="font-size: 0.85rem; color: var(--text-muted);">Updated in real-time</span>
        </div>

        <div class="fares-grid">
            <?php if (empty($routes)): ?>
                <div class="no-results">
                    <h3>No active routes found</h3>
                    <p>Try resetting your search filters or selecting a different county.</p>
                </div>
            <?php else: ?>
                <?php foreach ($routes as $route): ?>
                    <div class="fare-card">
                        <div>
                            <div class="card-top">
                                <div>
                                    <h3 class="route-name">
    <a href="route.php?id=<?= $route['id'] ?>" style="color: inherit; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='var(--accent-blue)'" onmouseout="this.style.color='inherit'">
        <?= htmlspecialchars($route['route_name']) ?>
    </a>
</h3>
                                </div>
                                <?php 
                                    $status = $route['status'] ?? 'Normal';
                                    $statusClass = (stripos($status, 'Surge') !== false || stripos($status, 'Hike') !== false) ? 'status-surge' : 'status-normal';
                                ?>
                                <span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars($status) ?></span>
                            </div>

                            <div class="route-details">
                                <p><strong>Route:</strong> <?= htmlspecialchars($route['origin']) ?> → <?= htmlspecialchars($route['destination']) ?></p>
                                <p><strong>SACCO:</strong> <?= htmlspecialchars($route['sacco_name'] ?? 'Independent / General') ?></p>
                            </div>
                        </div>

                        <div>
                            <?php if (!empty($route['notes'])): ?>
                                <div class="notes-box">⚠️ <?= htmlspecialchars($route['notes']) ?></div>
                            <?php endif; ?>

                            <div class="fare-pricing">
                                <div>
                                    <span class="base-price">Normal: KSh <?= number_format($route['base_fare'], 2) ?></span>
                                </div>
                                <div style="text-align: right;">
                                    <span class="current-price" style="color: <?= ($route['current_fare'] > $route['base_fare']) ? 'var(--surge-red)' : 'var(--text-heading)' ?>;">
                                        KSh <?= number_format($route['current_fare'] ?? $route['base_fare'], 2) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- FOOTER -->
    <footer>
        <div class="container" style="margin: 0 auto; padding: 0;">
            <p>&copy; <?= date('Y') ?> Matatu Live Kenya. Built for commuters across Nairobi and all 47 Counties.</p>
        </div>
    </footer>

</body>
</html>