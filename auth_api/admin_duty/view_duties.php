<?php
require_once '../config/Database.php';
require_once '../utils/JWTHandler.php';

// Enable CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

$jwt = new JWTHandler();
$token = $jwt->generateToken('1');

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Get locations
$locations_query = "SELECT * FROM locations ORDER BY location_name";
$locations_stmt = $db->prepare($locations_query);
$locations_stmt->execute();
$locations = $locations_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Staff Duties</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.8/main.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
</head>
<body class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
    <div class="max-w-7xl mx-auto px-4">
        <!-- Header in view_duties.php -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Staff Duty Schedule</h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">View staff duties by date and location</p>
            </div>

            <a
                    href="dashboard.php"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700
               text-white rounded-lg transition-colors duration-200"
            >
                <svg xmlns="http://www.w3.org/2000/svg"
                     class="h-5 w-5 mr-2"
                     viewBox="0 0 20 20"
                     fill="currentColor">
                    <path fill-rule="evenodd"
                          d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z"
                          clip-rule="evenodd"/>
                </svg>
                Assign New Duty
            </a>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">
                        Location
                    </label>
                    <select
                        id="locationFilter"
                        class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600"
                        onchange="loadStaffDuties()"
                    >
                        <option value="">All Locations</option>
                        <?php foreach ($locations as $location): ?>
                            <option value="<?= htmlspecialchars($location['location_id']) ?>">
                                <?= htmlspecialchars($location['location_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">
                        Select Date
                    </label>
                    <div id="calendar" class="h-64"></div>
                    <input type="hidden" id="selectedDate">
                </div>
            </div>
        </div>

        <!-- Staff Duties List -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <h2 class="text-xl font-semibold mb-6 text-gray-900 dark:text-white">
                Staff Duties
                <span id="dateDisplay" class="text-sm font-normal text-gray-500"></span>
                <span id="locationDisplay" class="text-sm font-normal text-gray-500"></span>
            </h2>
            <div id="loadingIndicator" class="hidden">
                <div class="flex items-center justify-center p-6">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
                    <span class="ml-3 text-gray-600 dark:text-gray-400">Loading duties...</span>
                </div>
            </div>
            <div id="staffDutiesList" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Staff Name
                            </th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Location
                            </th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Shift
                            </th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Time
                            </th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="dutiesTableBody">
                        <!-- Data will be loaded here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize FullCalendar
        const calendarEl = document.getElementById('calendar');
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            selectable: true,
            height: '300',
            dateClick: function(info) {
                document.getElementById('selectedDate').value = info.dateStr;
                document.getElementById('dateDisplay').textContent = ` for ${info.dateStr}`;
                loadStaffDuties();
            },
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: ''
            }
        });
        calendar.render();

        // Initial load
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('selectedDate').value = today;
        document.getElementById('dateDisplay').textContent = ` for ${today}`;
        loadStaffDuties();
    });

    localStorage.setItem('token', '<?php echo $token; ?>');

    async function loadStaffDuties() {
        // Show loading indicator
        const loadingIndicator = document.getElementById('loadingIndicator');
        const dutiesList = document.getElementById('staffDutiesList');

        loadingIndicator.classList.remove('hidden');
        dutiesList.classList.add('opacity-50');

        const date = document.getElementById('selectedDate').value;
        const locationId = document.getElementById('locationFilter').value;
        const locationName = document.getElementById('locationFilter').options[document.getElementById('locationFilter').selectedIndex].text;

        document.getElementById('locationDisplay').textContent = locationId ? ` at ${locationName}` : '';

        try {
            const response = await fetch(`get_staff_duties.php?date=${date}${locationId ? '&location_id=' + locationId : ''}`, {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
            });

            const data = await response.json();

            if (data.success) {
                const tableBody = document.getElementById('dutiesTableBody');

                if (data.data.length === 0) {
                    tableBody.innerHTML = `
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                            No duties found for the selected criteria
                        </td>
                    </tr>
                `;
                } else {
                    tableBody.innerHTML = data.data.map(duty => `
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">${duty.staff_name}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">${duty.location_name}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">${duty.shift_title}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">${duty.start_time} - ${duty.end_time}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                                ${duty.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                        duty.status === 'completed' ? 'bg-green-100 text-green-800' :
                            'bg-red-100 text-red-800'}">
                                ${duty.status}
                            </span>
                        </td>
                    </tr>
                `).join('');
                }
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            console.error('Error loading staff duties:', error);
            document.getElementById('dutiesTableBody').innerHTML = `
            <tr>
                <td colspan="5" class="px-6 py-4 text-center text-red-500">
                    Error loading staff duties
                </td>
            </tr>
        `;
        } finally {
            // Hide loading indicator
            loadingIndicator.classList.add('hidden');
            dutiesList.classList.remove('opacity-50');
        }
    }
    </script>
</body>
</html>