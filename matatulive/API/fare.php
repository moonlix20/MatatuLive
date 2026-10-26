<?php
// Set headers for RESTful JSON API response and CORS support
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../db.php';

// Support URL parameters for filtering:
// Example queries:
// - /api/fares.php (Returns all live fares)
// - /api/fares.php?county_id=1 (Filters by County ID)
// - /api/fares.php?status=Surge (Filters by Surge, Normal, Rain Hike, or Heavy Traffic)
// - /api/fares.php?search=Westlands (Searches route name, origin, or destination)

$county_id = isset($_GET['county_id']) ? intval($_GET['county_id']) : null;
$status = isset($_GET['status']) ? trim($_GET['status']) : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : null;

try {
    $sql = "SELECT r.id AS route_id, 
                   r.route_name, 
                   r.origin, 
                   r.destination, 
                   r.base_fare, 
                   c.id AS county_id, 
                   c.name AS county_name, 
                   s.sacco_name, 
                   COALESCE(f.current_fare, r.base_fare) AS current_fare, 
                   COALESCE(f.status, 'Normal') AS status, 
                   f.notes, 
                   f.updated_at 
            FROM routes r
            JOIN counties c ON r.county_id = c.id
            LEFT JOIN saccos s ON r.sacco_id = s.id
            LEFT JOIN live_fares f ON r.id = f.route_id
            WHERE 1=1";

    $params = [];

    if (!empty($county_id)) {
        $sql .= " AND r.county_id = :county_id";
        $params[':county_id'] = $county_id;
    }

    if (!empty($status)) {
        $sql .= " AND f.status = :status";
        $params[':status'] = $status;
    }

    if (!empty($search)) {
        $sql .= " AND (r.route_name LIKE :search OR r.origin LIKE :search OR r.destination LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }

    $sql .= " ORDER BY f.updated_at DESC, r.id ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format response structure
    $response = [
        "status" => "success",
        "timestamp" => date("Y-m-d H:i:s"),
        "total_results" => count($results),
        "data" => array_map(function($row) {
            return [
                "route_id" => (int)$row['route_id'],
                "route_name" => $row['route_name'],
                "origin" => $row['origin'],
                "destination" => $row['destination'],
                "county" => [
                    "id" => (int)$row['county_id'],
                    "name" => $row['county_name']
                ],
                "sacco" => $row['sacco_name'] ?? "Independent",
                "pricing" => [
                    "base_fare" => (float)$row['base_fare'],
                    "current_fare" => (float)$row['current_fare'],
                    "is_surge" => ((float)$row['current_fare'] > (float)$row['base_fare'])
                ],
                "condition" => [
                    "status" => $row['status'],
                    "notes" => $row['notes'] ?? ""
                ],
                "last_updated" => $row['updated_at'] ?? null
            ];
        }, $results)
    ];

    http_response_code(200);
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ], JSON_PRETTY_PRINT);
}