<?php
// api/profile/get_profile.php

ob_clean();

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400');
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

function calculateRetirementInfo($empDate, $dob) {
    $today = new DateTime();
    $employmentDate = new DateTime($empDate);
    $birthDate = new DateTime($dob);

    // Calculate current age and years in service
    $yearsInService = $today->diff($employmentDate)->y;
    $currentAge = $today->diff($birthDate)->y;

    // Calculate retirement dates
    $serviceRetirementDate = clone $employmentDate;
    $serviceRetirementDate->modify('+35 years');

    $ageRetirementDate = clone $birthDate;
    $ageRetirementDate->modify('+60 years');

    // Get the earlier date
    $retirementDate = min($serviceRetirementDate, $ageRetirementDate);
    $retirementDate = new DateTime($retirementDate->format('Y-m-d'));

    // Calculate remaining time
    $timeToRetirement = $today->diff($retirementDate);
    $isRetired = $today > $retirementDate;

    // Determine retirement type
    $retirementType = ($retirementDate == $serviceRetirementDate)
        ? 'Years of Service (35 years)'
        : 'Age Limit (60 years)';

    return [
        'retirement_info' => [
            'retirement_date' => $retirementDate->format('Y-m-d'),
            'retirement_type' => $retirementType,
            'years_remaining' => $isRetired ? 0 : $timeToRetirement->y,
            'months_remaining' => $isRetired ? 0 : $timeToRetirement->m,
            'total_months_remaining' => $isRetired ? 0 : ($timeToRetirement->y * 12 + $timeToRetirement->m),
            'is_retired' => $isRetired,
            'service_retirement_date' => $serviceRetirementDate->format('Y-m-d'),
            'age_retirement_date' => $ageRetirementDate->format('Y-m-d')
        ],
        'service_summary' => [
            'years_in_service' => $yearsInService,
            'current_age' => $currentAge,
            'years_to_service_retirement' => max(0, 35 - $yearsInService),
            'years_to_age_retirement' => max(0, 60 - $currentAge)
        ]
    ];
}

try {
    require_once '../../config/Database.php';
    require_once '../../utils/JWTHandler.php';

    // Validate JWT token...
    $headers = apache_request_headers();
    $auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : '';

    if (!$auth_header || !preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
        throw new Exception('No token provided or invalid format', 401);
    }

    $token = $matches[1];
    $jwt = new JWTHandler();
    $token = $jwt->validateToken($token);

    if (!$token) {
        throw new Exception('Invalid token', 401);
    }

    $database = new Database();
    $db = $database->getConnection();

    $staff_id = filter_var($_GET['staff_id'], FILTER_VALIDATE_INT);

    if (!$staff_id) {
        throw new Exception('Invalid staff ID', 400);
    }

    // Get basic profile information
    $query = "SELECT
        e.PPNO, 
        e.`NAME`, 
        e.EMAIL, 
        e.GENDER, 
        e.EMPDATE, 
        e.DOB, 
        e.DOPA, 
        e.DOC, 
        e.LG_ORIGIN, 
        e.S_ORIGIN, 
        d.dept, 
        e.LEVE_APT
    FROM
        employee e
        INNER JOIN
        tbl_dept d
        ON 
            e.DEPTCD = d.dept_id
        WHERE e.staff_id = :staff_id";

    $stmt = $db->prepare($query);
    $stmt->execute([':staff_id' => $staff_id]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$profile) {
        throw new Exception('Profile not found', 404);
    }

    // Get qualifications
    $query = "SELECT
        q.quaification,
        sq.field,
        sq.institution,
        sq.year_obtained
    FROM
        staff_qualification sq
        INNER JOIN
        qualification q
        ON 
            sq.qua_id = q.id
        WHERE sq.staff_id = :staff_id
        ORDER BY sq.year_obtained DESC, q.id";

    $stmt = $db->prepare($query);
    $stmt->execute([':staff_id' => $staff_id]);
    $qualifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate retirement information
    $retirementData = calculateRetirementInfo($profile['EMPDATE'], $profile['DOB']);

    // Combine all data
    $profile['qualifications'] = $qualifications;
    $profile['retirement_info'] = $retirementData['retirement_info'];
    $profile['service_summary'] = $retirementData['service_summary'];

    // Debug print
    error_log("Profile data: " . json_encode($profile));

    echo json_encode([
        'success' => true,
        'data' => $profile
    ]);

} catch (Exception $e) {
    error_log("Profile error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());

    $status_code = $e->getCode();
    if (!is_int($status_code) || $status_code < 100 || $status_code > 599) {
        $status_code = 400;
    }

    http_response_code($status_code);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}