<?php

// admin/duty_assignment.php

// Include necessary files
require_once '../config/Database.php';
require_once '../utils/JWTHandler.php';

// Enable CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$jwt = new JWTHandler();
$token = $jwt->generateToken('1'); // Using default user ID 1


// Get database connection
$database = new Database();
$db = $database->getConnection();

// Get initial data
$locations_query = "SELECT * FROM locations ORDER BY location_name";
$shifts_query = "SELECT * FROM duty_shifts ORDER BY start_time";

$locations_stmt = $db->prepare($locations_query);
$shifts_stmt = $db->prepare($shifts_query);

$locations_stmt->execute();
$shifts_stmt->execute();

$locations = $locations_stmt->fetchAll(PDO::FETCH_ASSOC);
$shifts = $shifts_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Duty Assignment</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FullCalendar CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.8/main.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
    <!-- FullCalendar JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.8/main.min.js"></script>


</head>
<body class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
<div class="max-w-6xl mx-auto px-4">
    <!-- Header -->
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Duty Roaster</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">Assign duties to staff members</p>
        </div>

        <!-- Add this navigation button -->
        <a
                href="view_duties.php"
                class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700
               text-white rounded-lg transition-colors duration-200"
        >
            <svg xmlns="http://www.w3.org/2000/svg"
                 class="h-5 w-5 mr-2"
                 viewBox="0 0 20 20"
                 fill="currentColor">
                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                <path fill-rule="evenodd"
                      d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                      clip-rule="evenodd"/>
            </svg>
            View Duties
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Assignment Form -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <h2 class="text-xl font-semibold mb-6 text-gray-900 dark:text-white">New Shift</h2>

            <form id="dutyForm" class="space-y-6">
                <input type="hidden" id="selectedStaffId">

                <!-- Staff Search -->
                <div>
                    <label class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">
                        Search Staff
                    </label>
                    <input
                            type="text"
                            id="staffSearch"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600"
                            placeholder="Search by name or ID..."
                    autocomplete="off">
                    <div id="searchResults" class="hidden mt-1 bg-white dark:bg-gray-700 rounded-lg shadow-lg"></div>
                    <p id="staffError" class="mt-1 text-sm text-red-500 hidden"></p>
                </div>

                <!-- Location -->
                <div>
                    <label class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">
                        Location
                    </label>
                    <select
                            id="location"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600"
                    >
                        <option value="">Select Location</option>
                        <?php foreach ($locations as $location): ?>
                            <option value="<?= htmlspecialchars($location['location_id']) ?>">
                                <?= htmlspecialchars($location['location_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p id="locationError" class="mt-1 text-sm text-red-500 hidden"></p>
                </div>

                <!-- Shift -->
                <div>
                    <label class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">
                        Shift
                    </label>
                    <select
                            id="shift"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600"
                    >
                        <option value="">Select Shift</option>
                        <?php foreach ($shifts as $shift): ?>
                            <option value="<?= htmlspecialchars($shift['id']) ?>">
                                <?= htmlspecialchars($shift['title']) ?>
                                (<?= htmlspecialchars($shift['start_time']) ?> -
                                <?= htmlspecialchars($shift['end_time']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p id="shiftError" class="mt-1 text-sm text-red-500 hidden"></p>
                </div>

                <!-- Date -->
                <div class="calendar-container">
                    <label class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">
                        Select a Date
                    </label>
                    <div id="fullCalendar"></div>
                    <input type="hidden" id="date" name="date">
                    <p id="selectedDate" class="mt-2 text-gray-700 dark:text-gray-300"></p>
                </div>


                <button
                        type="submit"
                        class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700
                               disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Assign Duty
                </button>
            </form>
        </div>

        <!-- Recent Assignments -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <h2 class="text-xl font-semibold mb-6 text-gray-900 dark:text-white">
                Recent Assignments
            </h2>
            <div id="recentAssignments" class="space-y-4">
                <!-- Assignments will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Notification -->
<div id="notification" class="fixed top-4 right-4 z-50 hidden rounded-lg shadow-lg p-4"></div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const calendarEl = document.getElementById('fullCalendar');

        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth', // Display full month view
            selectable: true, // Enable date selection
            height: 300,
            validRange: {
                start: new Date().toISOString().split('T')[0], // Start from today
            },
            dateClick: function(info) {
                // Display the selected date
                const selectedDate = document.getElementById('selectedDate');
                selectedDate.textContent = `Selected Date: ${info.dateStr}`;

                const dateSelected = document.getElementById('date');
                dateSelected.value = info.dateStr;
            },
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth'
            }
        });

        calendar.render();
    });


    localStorage.setItem('token', '<?php echo $token; ?>');
    // document.write('Token saved to localStorage: ' + localStorage.getItem('token'));
</script>

<script>
    let searchTimeout;
    document.getElementById('staffSearch').addEventListener('input', (e) => {
        clearTimeout(searchTimeout);
        const query = e.target.value;


        if (query.length < 2) {
            document.getElementById('searchResults').classList.add('hidden');
            return;
        }

        searchTimeout = setTimeout(async () => {

            try {
                const response = await fetch(`staff_search.php?q=${encodeURIComponent(query)}`, {
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('token')}`
                    }
                });

                const data = await response.json();
                if (data.success) {
                    const resultsDiv = document.getElementById('searchResults');
                    resultsDiv.innerHTML = data.data.map(staff => `
                            <div class="p-2 hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer"
                                 onclick="selectStaff('${staff.staff_id}', '${staff.name}')">
                                <div class="font-medium">${staff.name}</div>
<!--                               <div class="text-sm text-gray-500">ID: ${staff.staff_id}</div>-->
                                <div class="text-sm text-gray-500">DEPT: ${staff.dept}</div>
                            </div>
                        `).join('');
                    resultsDiv.classList.remove('hidden');
                }
            } catch (error) {
                showNotification('Failed to search staff', 'error');
            }
        }, 300);
    });

    function selectStaff(id, name) {
        console.log(id);
        document.getElementById('staffSearch').value = name;
        document.getElementById('selectedStaffId').value = id;
        document.getElementById('searchResults').classList.add('hidden');
        document.getElementById('staffError').classList.add('hidden');
    }

    // Utility Functions
    function showNotification(message, type = 'success') {
        const notification = document.getElementById('notification');
        notification.className = `fixed top-4 right-4 z-50 rounded-lg shadow-lg p-4 ${
            type === 'error' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'
        }`;
        notification.textContent = message;
        notification.classList.remove('hidden');
        setTimeout(() => notification.classList.add('hidden'), 3000);
    }

    function formatDate(dateString) {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }

    // Load Recent Assignments
    async function loadRecentAssignments() {
        try {
            const response = await fetch('../api/duty/duty_rota.php?action=get_duties', {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
            });

            const data = await response.json();
            if (data.success) {
                document.getElementById('recentAssignments').innerHTML = data.data.map(assignment => `
                        <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-700">
                            <div class="flex justify-between">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">
                                        ${assignment.staff_name}
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        ${assignment.location_name}
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        ${formatDate(assignment.duty_date)}
                                    </p>
                                </div>
                                <span class="px-2 py-6 text-xs rounded-full ${
                    assignment.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                        assignment.status === 'completed' ? 'bg-green-100 text-green-800' :
                            'bg-red-100 text-red-800'
                }">
                                    ${assignment.status}
                                </span>
                            </div>
                        </div>
                    `).join('') || '<p class="text-center text-gray-500">No recent assignments</p>';
            }
        } catch (error) {
            showNotification('Failed to load assignments', 'error');
        }
    }

    // Form Submission
    document.getElementById('dutyForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = {
            staff_id: document.getElementById('selectedStaffId').value,
            location_id: document.getElementById('location').value,
            shift_id: document.getElementById('shift').value,
            duty_date: document.getElementById('date').value
        };

        // Validate
        if (!formData.staff_id || !formData.location_id || !formData.shift_id || !formData.duty_date) {
            showNotification('Please fill all required fields', 'error');
            return;
        }

        try {
            const response = await fetch('../api/duty/duty_rota.php?action=assign_duty', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                },
                body: JSON.stringify(formData)
            });

            const data = await response.json();
            if (data.success) {
                showNotification('Duty assigned successfully');
                e.target.reset();
                loadRecentAssignments();
            } else {
                showNotification(data.message || 'Failed to assign duty', 'error');
            }
        } catch (error) {
            showNotification('Failed to assign duty', 'error');
        }
    });

    // Initialize
    document.addEventListener('DOMContentLoaded', loadRecentAssignments);
</script>
</body>
</html>