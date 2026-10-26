<?php
require_once 'db.php';

// Fetch all routes with their current live fares for the calculator dropdown
$routes_stmt = $pdo->query("SELECT r.id, r.route_name, r.origin, r.destination, r.base_fare, c.name as county_name, f.current_fare, f.status 
                            FROM routes r 
                            JOIN counties c ON r.county_id = c.id 
                            LEFT JOIN live_fares f ON r.id = f.route_id 
                            ORDER BY r.route_name ASC");
$routes = $routes_stmt->fetchAll();

// Convert routes array to JSON for client-side JavaScript calculation
$routes_json = json_encode($routes);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fare & Budget Calculator | Matatu Live</title>
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
            --accent-green-hover: #2ea043;
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
            max-width: 750px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo { font-size: 1.3rem; font-weight: 800; color: var(--text-heading); text-decoration: none; }
        .logo span { color: var(--accent-blue); }

        .container {
            max-width: 750px;
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

        h2 { color: var(--text-heading); font-size: 1.5rem; margin-bottom: 0.5rem; }
        p.subtitle { color: var(--text-muted); font-size: 0.95rem; margin-bottom: 1.5rem; }

        .form-group { margin-bottom: 1.25rem; }
        label { display: block; font-weight: 600; color: var(--text-heading); margin-bottom: 0.5rem; font-size: 0.9rem; }
        
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
        .form-control:focus { border-color: var(--accent-blue); }

        /* RESULTS DISPLAY BOX */
        .results-box {
            background-color: var(--bg-body);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 1.5rem;
        }

        .result-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border-color);
        }

        .result-row:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .result-label { font-size: 0.9rem; color: var(--text-muted); }
        .result-value { font-size: 1.15rem; font-weight: 700; color: var(--text-heading); }

        .total-highlight {
            font-size: 1.5rem !important;
            color: var(--accent-green) !important;
        }

        footer { text-align: center; padding: 1.5rem; color: var(--text-muted); font-size: 0.85rem; border-top: 1px solid var(--border-color); background-color: var(--bg-card); }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">🇰🇪 Matatu<span>Live</span> Calculator</a>
            <a href="index.php" style="color: var(--text-primary); text-decoration: none; font-size: 0.9rem;">← Back to Live Board</a>
        </div>
    </nav>

    <div class="container">
        <div class="card">
            <h2>Commuter Fare & Budget Calculator</h2>
            <p class="subtitle">Estimate your daily, weekly, and monthly transport expenses based on live stage prices.</p>

            <div class="form-group">
                <label for="route_select">Select Your Route</label>
                <select id="route_select" class="form-control" onchange="calculateBudget()">
                    <option value="">-- Choose a Route --</option>
                    <!-- Populated via PHP -->
                </select>
            </div>

            <div class="form-group">
                <label for="trip_type">Trip Type</label>
                <select id="trip_type" class="form-control" onchange="calculateBudget()">
                    <option value="1">One-Way Trip (Single)</option>
                    <option value="2" selected>Return Trip (To & Fro)</option>
                </select>
            </div>

            <div class="form-group">
                <label for="working_days">Commute Days per Week</label>
                <input type="number" id="working_days" class="form-control" value="5" min="1" max="7" oninput="calculateBudget()">
            </div>

            <!-- RESULTS BOX -->
            <div class="results-box">
                <div class="result-row">
                    <span class="result-label">Current Applicable Fare:</span>
                    <span class="result-value" id="res_single_fare">KSh 0.00</span>
                </div>
                <div class="result-row">
                    <span class="result-label">Estimated Daily Cost:</span>
                    <span class="result-value" id="res_daily">KSh 0.00</span>
                </div>
                <div class="result-row">
                    <span class="result-label">Weekly Budget (Est.):</span>
                    <span class="result-value" id="res_weekly">KSh 0.00</span>
                </div>
                <div class="result-row">
                    <span class="result-label">Monthly Budget (Est. 4 Weeks):</span>
                    <span class="result-value result-highlight" id="res_monthly">KSh 0.00</span>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; <?= date('Y') ?> Matatu Live Kenya. Budget Calculator.</p>
    </footer>

    <script>
        // Load routes data passed from PHP
        const routesData = <?= $routes_json; ?>;

        // Populate route dropdown on page load
        window.addEventListener('DOMContentLoaded', () => {
            const select = document.getElementById('route_select');
            routesData.forEach(route => {
                const opt = document.createElement('option');
                opt.value = route.id;
                const fare = route.current_fare !== null ? route.current_fare : route.base_fare;
                opt.textContent = `[${route.county_name}] ${route.route_name} (${route.origin} → ${route.destination}) - KSh ${parseFloat(fare).toFixed(2)}`;
                select.appendChild(opt);
            });
        });

        function calculateBudget() {
            const routeId = document.getElementById('route_select').value;
            const tripMultiplier = parseInt(document.getElementById('trip_type').value);
            const daysPerWeek = parseInt(document.getElementById('working_days').value) || 0;

            if (!routeId) {
                document.getElementById('res_single_fare').textContent = 'KSh 0.00';
                document.getElementById('res_daily').textContent = 'KSh 0.00';
                document.getElementById('res_weekly').textContent = 'KSh 0.00';
                document.getElementById('res_monthly').textContent = 'KSh 0.00';
                return;
            }

            // Find selected route
            const selectedRoute = routesData.find(r => r.id == routeId);
            const activeFare = parseFloat(selectedRoute.current_fare !== null ? selectedRoute.current_fare : selectedRoute.base_fare);

            const dailyCost = activeFare * tripMultiplier;
            const weeklyCost = dailyCost * daysPerWeek;
            const monthlyCost = weeklyCost * 4; // Approx 4 weeks per month

            document.getElementById('res_single_fare').textContent = `KSh ${activeFare.toFixed(2)}`;
            document.getElementById('res_daily').textContent = `KSh ${dailyCost.toFixed(2)}`;
            document.getElementById('res_weekly').textContent = `KSh ${weeklyCost.toFixed(2)}`;
            document.getElementById('res_monthly').textContent = `KSh ${monthlyCost.toFixed(2)}`;
        }
    </script>
</body>
</html>