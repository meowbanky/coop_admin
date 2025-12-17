<?php
// Set page title
$pageTitle = 'OOUTH COOP - Event Management';

// Include header
include 'includes/header.php';

require_once('Connections/coop.php');

// Check authentication
if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '') || $_SESSION['role'] != 'Admin') {
    header("location: index.php");
    exit();
}
?>
<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="mb-8 fade-in">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-3xl font-bold text-gray-900 mb-2">
                    <i class="fas fa-calendar-alt text-blue-600 mr-3"></i>Event Management
                </h2>
                <p class="text-gray-600">Create and manage events with location-based attendance</p>
            </div>
            <button onclick="openCreateEventModal()"
                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg text-sm font-medium transition-colors shadow-lg">
                <i class="fas fa-plus mr-2"></i>Create Event
            </button>
        </div>
    </div>

    <!-- Events Table -->
    <div class="bg-white rounded-xl shadow-lg fade-in">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-list text-blue-600 mr-2"></i>All Events
            </h3>
        </div>
        <div class="p-6">
            <div id="events-table-container">
                <div class="text-center py-8">
                    <i class="fas fa-spinner fa-spin text-blue-600 text-2xl mb-2"></i>
                    <p class="text-gray-500">Loading events...</p>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Attendance Modal -->
<div id="attendanceModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center">
            <h3 id="attendance-modal-title" class="text-xl font-semibold text-gray-900">Attendance List</h3>
            <button onclick="closeAttendanceModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>
        <div class="p-6">
            <div class="mb-4 flex justify-between items-center">
                <div id="attendance-summary" class="text-sm text-gray-600"></div>
                <button id="export-attendance-btn" onclick="exportAttendanceToExcel()"
                    class="hidden bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                    <i class="fas fa-file-excel mr-2"></i>Export to Excel
                </button>
            </div>
            <div id="attendance-list-container">
                <div class="text-center py-8">
                    <i class="fas fa-spinner fa-spin text-blue-600 text-2xl mb-2"></i>
                    <p class="text-gray-500">Loading attendance...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create/Edit Event Modal -->
<div id="eventModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center">
            <h3 id="modal-title" class="text-xl font-semibold text-gray-900">Create Event</h3>
            <button onclick="closeEventModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>

        <form id="eventForm" class="p-6 space-y-6">
            <input type="hidden" id="event-id">

            <!-- Event Title -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Event Title <span class="text-red-500">*</span>
                </label>
                <input type="text" id="event-title" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Enter event title">
            </div>

            <!-- Event Description -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Description
                </label>
                <textarea id="event-description" rows="3"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Enter event description (optional)"></textarea>
            </div>

            <!-- Date and Time -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Start Date & Time <span class="text-red-500">*</span>
                    </label>
                    <input type="datetime-local" id="event-start-time" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        End Date & Time <span class="text-red-500">*</span>
                    </label>
                    <input type="datetime-local" id="event-end-time" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
            </div>

            <!-- Location Selection -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Event Location <span class="text-red-500">*</span>
                </label>
                <div class="flex gap-2 mb-2">
                    <button type="button" onclick="useCurrentLocation()"
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                        <i class="fas fa-location-arrow mr-2"></i>Use My Location
                    </button>
                    <button type="button" onclick="selectOnMap()"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                        <i class="fas fa-map-marker-alt mr-2"></i>Select on Map
                    </button>
                </div>
                <div id="map-container" class="hidden w-full h-64 border border-gray-300 rounded-lg mb-2"></div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Latitude</label>
                        <input type="number" id="event-lat" step="any" required readonly
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50"
                            placeholder="Select location">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Longitude</label>
                        <input type="number" id="event-lng" step="any" required readonly
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50"
                            placeholder="Select location">
                    </div>
                </div>
            </div>

            <!-- Geofence Radius -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Geofence Radius (meters) <span class="text-red-500">*</span>
                </label>
                <div class="flex items-center gap-4">
                    <input type="range" id="event-radius" min="10" max="500" value="50" step="10" class="flex-1"
                        oninput="document.getElementById('radius-value').textContent = this.value + 'm'">
                    <span id="radius-value" class="text-lg font-semibold text-blue-600 w-20 text-right">50m</span>
                </div>
                <p class="text-xs text-gray-500 mt-1">Users must be within this distance to check in</p>
            </div>

            <!-- Grace Period -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Grace Period (minutes after event ends) <span class="text-red-500">*</span>
                </label>
                <div class="flex items-center gap-4">
                    <input type="range" id="event-grace-period" min="0" max="120" value="20" step="5" class="flex-1"
                        oninput="document.getElementById('grace-value').textContent = this.value + ' min'">
                    <span id="grace-value" class="text-lg font-semibold text-blue-600 w-24 text-right">20 min</span>
                </div>
                <p class="text-xs text-gray-500 mt-1">Users can check in up to this many minutes after the event ends
                </p>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                <button type="button" onclick="closeEventModal()"
                    class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                    <i class="fas fa-save mr-2"></i>Save Event
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Google Maps API -->
<?php
require_once('config/EnvConfig.php');
$googleMapsApiKey = EnvConfig::getGoogleMapsApiKey();
?>
<script
    src="https://maps.googleapis.com/maps/api/js?key=<?php echo htmlspecialchars($googleMapsApiKey); ?>&libraries=places">
</script>

<script>
const apiBaseUrl = '<?php echo "https://www.emmaggi.com/coop_admin/auth_api/api"; ?>';
const testApiUrl = '<?php echo "https://www.emmaggi.com/coop_admin/auth_api/api/admin/test-events.php"; ?>';
let map;
let marker;
let events = [];

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // First test the API endpoint
    testApiConnection().then(() => {
        loadEvents();
    }).catch(error => {
        console.error('API test failed:', error);
        // Still try to load events
        loadEvents();
    });
});

// Test API connection
async function testApiConnection() {
    try {
        const response = await fetch(testApiUrl, {
            credentials: 'include',
            headers: {
                'Accept': 'application/json'
            }
        });

        if (response.ok) {
            const result = await response.json();
            console.log('API Test Results:', result);
            if (result.tests) {
                if (!result.tests.env_file_exists) {
                    console.warn('WARNING: .env file not found at:', result.tests.env_file_path);
                }
                if (!result.tests.database_file_exists) {
                    console.error('ERROR: Database.php file not found at:', result.tests.database_file_path);
                }
                if (result.tests.database_connection_error) {
                    console.error('ERROR: Database connection failed:', result.tests.database_connection_error);
                }
            }
            return result;
        } else {
            const text = await response.text();
            console.error('API test failed with status:', response.status, text);
            throw new Error(`API test failed: ${response.status}`);
        }
    } catch (error) {
        console.error('API test error:', error);
        throw error;
    }
}

// Load events list
async function loadEvents() {
    try {
        const container = document.getElementById('events-table-container');
        container.innerHTML =
            '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-blue-600 text-2xl mb-2"></i><p class="text-gray-500">Loading events...</p></div>';

        const response = await fetch(`${apiBaseUrl}/admin/events.php`, {
            credentials: 'include',
            headers: {
                'Accept': 'application/json'
            }
        });

        if (!response.ok) {
            // Try to get error message from response
            let errorMessage = `HTTP error! status: ${response.status}`;
            try {
                const errorData = await response.json();
                errorMessage = errorData.message || errorMessage;
            } catch (e) {
                const text = await response.text();
                errorMessage = text || errorMessage;
            }
            throw new Error(errorMessage);
        }

        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Non-JSON response:', text);
            throw new Error('Server returned non-JSON response. Please check server logs.');
        }

        const result = await response.json();

        if (result.success) {
            events = result.data || [];
            displayEvents(events);
        } else {
            container.innerHTML =
                `<div class="text-center py-8 text-red-500">Error: ${result.message || 'Unknown error'}</div>`;
            Swal.fire('Error', result.message || 'Failed to load events', 'error');
        }
    } catch (error) {
        console.error('Error loading events:', error);
        const container = document.getElementById('events-table-container');
        let errorMsg = error.message || 'Failed to load events';
        if (error.message === 'Failed to fetch') {
            errorMsg = 'Network error: Unable to connect to server. Please check your connection and try again.';
        }
        container.innerHTML = `<div class="text-center py-8 text-red-500">${errorMsg}</div>`;
        Swal.fire('Error', errorMsg, 'error');
    }
}

function displayEvents(eventsList) {
    const container = document.getElementById('events-table-container');

    if (!eventsList || eventsList.length === 0) {
        container.innerHTML =
            '<div class="text-center py-8 text-gray-500">No events found. Create your first event!</div>';
        return;
    }

    let html =
        '<div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200"><thead class="bg-gray-50"><tr>';
    html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>';
    html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Start Time</th>';
    html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">End Time</th>';
    html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Location</th>';
    html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Radius</th>';
    html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Attendance</th>';
    html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>';
    html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>';
    html += '</tr></thead><tbody class="bg-white divide-y divide-gray-200">';

    eventsList.forEach(event => {
        const startDate = new Date(event.start_time);
        const endDate = new Date(event.end_time);
        const now = new Date();

        let status = 'upcoming';
        let statusColor = 'bg-blue-100 text-blue-800';
        if (now >= startDate && now <= endDate) {
            status = 'active';
            statusColor = 'bg-green-100 text-green-800';
        } else if (now > endDate) {
            status = 'ended';
            statusColor = 'bg-gray-100 text-gray-800';
        }

        html += `<tr>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${escapeHtml(event.title)}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${formatDateTime(event.start_time)}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${formatDateTime(event.end_time)}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${event.location_lat.toFixed(6)}, ${event.location_lng.toFixed(6)}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${event.geofence_radius}m</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">
                <button onclick="viewAttendance(${event.id}, '${escapeHtml(event.title)}')" 
                    class="text-blue-600 hover:text-blue-800 font-medium flex items-center">
                    <i class="fas fa-users mr-1"></i>
                    ${event.attendance_count || 0}
                </button>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">
                <span class="px-2 py-1 text-xs font-semibold rounded-full ${statusColor}">${status.charAt(0).toUpperCase() + status.slice(1)}</span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">
                <button onclick="viewEventDetails(${event.id})" class="text-blue-600 hover:text-blue-800 mr-3">
                    <i class="fas fa-eye"></i> View
                </button>
                <button onclick="editEvent(${event.id})" class="text-green-600 hover:text-green-800 mr-3">
                    <i class="fas fa-edit"></i> Edit
                </button>
                <button onclick="deleteEvent(${event.id}, '${escapeHtml(event.title)}')" class="text-red-600 hover:text-red-800">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </td>
        </tr>`;
    });

    html += '</tbody></table></div>';
    container.innerHTML = html;
}

function formatDateTime(dateTimeString) {
    const date = new Date(dateTimeString);
    return date.toLocaleString('en-NG', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Modal functions
function openCreateEventModal() {
    document.getElementById('event-id').value = '';
    document.getElementById('event-title').value = '';
    document.getElementById('event-description').value = '';
    document.getElementById('event-start-time').value = '';
    document.getElementById('event-end-time').value = '';
    document.getElementById('event-lat').value = '';
    document.getElementById('event-lng').value = '';
    document.getElementById('event-radius').value = '50';
    document.getElementById('radius-value').textContent = '50m';
    document.getElementById('event-grace-period').value = '20';
    document.getElementById('grace-value').textContent = '20 min';
    document.getElementById('modal-title').textContent = 'Create Event';
    document.getElementById('map-container').classList.add('hidden');
    document.getElementById('eventModal').classList.remove('hidden');
}

function closeEventModal() {
    document.getElementById('eventModal').classList.add('hidden');
    if (map) {
        map = null;
        marker = null;
    }
}

function editEvent(eventId) {
    const event = events.find(e => e.id === eventId);
    if (!event) return;

    document.getElementById('event-id').value = event.id;
    document.getElementById('event-title').value = event.title;
    document.getElementById('event-description').value = event.description || '';

    // Format datetime for input fields
    const startDate = new Date(event.start_time);
    const endDate = new Date(event.end_time);
    document.getElementById('event-start-time').value = formatDateTimeLocal(startDate);
    document.getElementById('event-end-time').value = formatDateTimeLocal(endDate);

    document.getElementById('event-lat').value = event.location_lat;
    document.getElementById('event-lng').value = event.location_lng;
    document.getElementById('event-radius').value = event.geofence_radius;
    document.getElementById('radius-value').textContent = event.geofence_radius + 'm';
    document.getElementById('event-grace-period').value = event.grace_period_minutes || 20;
    document.getElementById('grace-value').textContent = (event.grace_period_minutes || 20) + ' min';

    document.getElementById('modal-title').textContent = 'Edit Event';
    document.getElementById('eventModal').classList.remove('hidden');

    // Initialize map with existing location
    initMap(event.location_lat, event.location_lng);
}

function formatDateTimeLocal(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${year}-${month}-${day}T${hours}:${minutes}`;
}

function viewEventDetails(eventId) {
    window.location.href = `event-details.php?id=${eventId}`;
}

let currentEventId = null;

async function viewAttendance(eventId, eventTitle) {
    currentEventId = eventId;
    document.getElementById('attendance-modal-title').textContent = `Attendance: ${eventTitle}`;
    document.getElementById('attendanceModal').classList.remove('hidden');

    const container = document.getElementById('attendance-list-container');
    const exportBtn = document.getElementById('export-attendance-btn');
    container.innerHTML =
        '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-blue-600 text-2xl mb-2"></i><p class="text-gray-500">Loading attendance...</p></div>';
    exportBtn.classList.add('hidden');

    try {
        const response = await fetch(`${apiBaseUrl}/admin/events.php/${eventId}`, {
            credentials: 'include'
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();

        if (result.success && result.data.attendance) {
            displayAttendanceList(result.data.attendance);
            exportBtn.classList.remove('hidden');
        } else {
            container.innerHTML = '<div class="text-center py-8 text-gray-500">No attendance records found</div>';
        }
    } catch (error) {
        console.error('Error loading attendance:', error);
        container.innerHTML = `<div class="text-center py-8 text-red-500">Failed to load: ${error.message}</div>`;
        Swal.fire('Error', 'Failed to load attendance: ' + error.message, 'error');
    }
}

function exportAttendanceToExcel() {
    if (!currentEventId) {
        Swal.fire('Error', 'No event selected', 'error');
        return;
    }

    // Open export URL in new window
    const exportUrl = `${apiBaseUrl}/admin/export-attendance.php?event_id=${currentEventId}`;
    window.open(exportUrl, '_blank');
}

function displayAttendanceList(attendance) {
    const container = document.getElementById('attendance-list-container');

    if (!attendance || attendance.length === 0) {
        container.innerHTML = '<div class="text-center py-8 text-gray-500">No attendance records yet</div>';
        return;
    }

    let html =
        '<div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200"><thead class="bg-gray-50"><tr>';
    html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>';
    html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>';
    html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Coop ID</th>';
    html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Check-in Time</th>';
    html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Distance</th>';
    html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Device ID</th>';
    html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>';
    html += '</tr></thead><tbody class="bg-white divide-y divide-gray-200">';

    attendance.forEach((record, index) => {
        const checkInDate = new Date(record.check_in_time);
        const isAdminOverride = record.admin_override === true || record.admin_override === 1;
        html += `<tr>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${index + 1}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                ${escapeHtml(record.user_name || 'Unknown')}
                ${isAdminOverride ? '<span class="ml-2 px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800" title="Checked in by admin">Admin</span>' : ''}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${escapeHtml(record.user_coop_id)}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${formatDateTime(record.check_in_time)}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${parseFloat(record.distance_from_event).toFixed(2)}m</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                <span class="font-mono text-xs">${escapeHtml((record.device_id || 'N/A').substring(0, 15))}${record.device_id && record.device_id.length > 15 ? '...' : ''}</span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">
                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">${record.status}</span>
            </td>
        </tr>`;
    });

    html += '</tbody></table></div>';

    const summaryHtml = `<div class="text-sm text-gray-600">
        <i class="fas fa-info-circle mr-1"></i>
        Total: <strong>${attendance.length}</strong> ${attendance.length === 1 ? 'person' : 'people'}
    </div>`;
    document.getElementById('attendance-summary').innerHTML = summaryHtml;

    container.innerHTML = html;
}

function closeAttendanceModal() {
    document.getElementById('attendanceModal').classList.add('hidden');
}

async function deleteEvent(eventId, eventTitle) {
    const result = await Swal.fire({
        title: 'Delete Event?',
        text: `Are you sure you want to delete "${eventTitle}"? This will also delete all attendance records.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    });

    if (result.isConfirmed) {
        try {
            const response = await fetch(`${apiBaseUrl}/admin/events.php/${eventId}`, {
                method: 'DELETE',
                credentials: 'include'
            });

            const data = await response.json();

            if (data.success) {
                Swal.fire('Deleted!', 'Event has been deleted.', 'success');
                loadEvents();
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        } catch (error) {
            Swal.fire('Error', 'Failed to delete event: ' + error.message, 'error');
        }
    }
}

// Location functions
function useCurrentLocation() {
    if (!navigator.geolocation) {
        Swal.fire('Error', 'Geolocation is not supported by your browser', 'error');
        return;
    }

    Swal.fire({
        title: 'Getting your location...',
        text: 'Please allow location access',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    navigator.geolocation.getCurrentPosition(
        (position) => {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;

            document.getElementById('event-lat').value = lat;
            document.getElementById('event-lng').value = lng;

            initMap(lat, lng);
            Swal.close();
        },
        (error) => {
            Swal.fire('Error', 'Failed to get your location: ' + error.message, 'error');
        }, {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        }
    );
}

function selectOnMap() {
    const mapContainer = document.getElementById('map-container');
    mapContainer.classList.remove('hidden');

    const lat = parseFloat(document.getElementById('event-lat').value) || 6.5244; // Default to Nigeria center
    const lng = parseFloat(document.getElementById('event-lng').value) || 3.3792;

    initMap(lat, lng);
}

function initMap(centerLat, centerLng) {
    const mapContainer = document.getElementById('map-container');

    if (!map) {
        map = new google.maps.Map(mapContainer, {
            center: {
                lat: centerLat,
                lng: centerLng
            },
            zoom: 15,
            mapTypeControl: true,
            streetViewControl: true
        });
    } else {
        map.setCenter({
            lat: centerLat,
            lng: centerLng
        });
    }

    // Remove existing marker
    if (marker) {
        marker.setMap(null);
    }

    // Add marker
    marker = new google.maps.Marker({
        position: {
            lat: centerLat,
            lng: centerLng
        },
        map: map,
        draggable: true,
        title: 'Event Location'
    });

    // Update lat/lng when marker is dragged
    marker.addListener('dragend', function() {
        const position = marker.getPosition();
        document.getElementById('event-lat').value = position.lat();
        document.getElementById('event-lng').value = position.lng();
    });

    // Update lat/lng when map is clicked
    map.addListener('click', function(event) {
        const lat = event.latLng.lat();
        const lng = event.latLng.lng();

        document.getElementById('event-lat').value = lat;
        document.getElementById('event-lng').value = lng;

        if (marker) {
            marker.setPosition({
                lat: lat,
                lng: lng
            });
        } else {
            marker = new google.maps.Marker({
                position: {
                    lat: lat,
                    lng: lng
                },
                map: map,
                draggable: true,
                title: 'Event Location'
            });
        }
    });
}

// Form submission
document.getElementById('eventForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const eventId = document.getElementById('event-id').value;
    const eventData = {
        title: document.getElementById('event-title').value.trim(),
        description: document.getElementById('event-description').value.trim(),
        start_time: document.getElementById('event-start-time').value + ':00',
        end_time: document.getElementById('event-end-time').value + ':00',
        location_lat: parseFloat(document.getElementById('event-lat').value),
        location_lng: parseFloat(document.getElementById('event-lng').value),
        geofence_radius: parseInt(document.getElementById('event-radius').value),
        grace_period_minutes: parseInt(document.getElementById('event-grace-period').value)
    };

    // Validation
    if (!eventData.title) {
        Swal.fire('Error', 'Event title is required', 'error');
        return;
    }

    if (!eventData.location_lat || !eventData.location_lng) {
        Swal.fire('Error', 'Please select event location', 'error');
        return;
    }

    try {
        const url = eventId ? `${apiBaseUrl}/admin/events.php/${eventId}` :
            `${apiBaseUrl}/admin/events.php`;
        const method = eventId ? 'PUT' : 'POST';

        Swal.fire({
            title: eventId ? 'Updating...' : 'Creating...',
            text: 'Please wait',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify(eventData)
        });

        const result = await response.json();

        if (result.success) {
            Swal.fire('Success!', eventId ? 'Event updated successfully' : 'Event created successfully',
                'success');
            closeEventModal();
            loadEvents();
        } else {
            Swal.fire('Error', result.message, 'error');
        }
    } catch (error) {
        Swal.fire('Error', 'Failed to save event: ' + error.message, 'error');
    }
});
</script>

<?php include 'includes/footer.php'; ?>