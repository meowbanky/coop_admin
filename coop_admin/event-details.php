<?php
// Set page title
$pageTitle = 'OOUTH COOP - Event Details';

// Include header
include 'includes/header.php';

require_once('Connections/coop.php');

// Check authentication
if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '') || $_SESSION['role'] != 'Admin') {
    header("location: index.php");
    exit();
}

$eventId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$eventId) {
    header("location: event-management.php");
    exit();
}
?>
<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Back Button -->
    <div class="mb-6">
        <a href="event-management.php" class="text-blue-600 hover:text-blue-800">
            <i class="fas fa-arrow-left mr-2"></i>Back to Events
        </a>
    </div>

    <!-- Event Details -->
    <div id="event-details-container" class="mb-8">
        <div class="text-center py-8">
            <i class="fas fa-spinner fa-spin text-blue-600 text-2xl mb-2"></i>
            <p class="text-gray-500">Loading event details...</p>
        </div>
    </div>

    <!-- Attendance List -->
    <div class="bg-white rounded-xl shadow-lg fade-in">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-users text-blue-600 mr-2"></i>Attendance List
            </h3>
            <div class="flex gap-2">
                <button onclick="openManualCheckInModal()"
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                    <i class="fas fa-user-plus mr-2"></i>Manual Check-in
                </button>
            </div>
        </div>
        <div class="p-6">
            <div id="attendance-container">
                <div class="text-center py-8">
                    <i class="fas fa-spinner fa-spin text-blue-600 text-2xl mb-2"></i>
                    <p class="text-gray-500">Loading attendance...</p>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Manual Check-in Modal -->
<div id="manualCheckInModal"
    class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-xl font-semibold text-gray-900">Manual Check-in</h3>
            <button onclick="closeManualCheckInModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>
        <form id="manualCheckInForm" class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Search Member <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input type="text" id="manual-member-search" required autocomplete="off"
                        class="w-full px-4 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        placeholder="Type member name or Coop ID to search...">
                    <button type="button" id="clear-member-search" onclick="clearMemberSearch()"
                        class="hidden absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none">
                        <i class="fas fa-times"></i>
                    </button>
                    <input type="hidden" id="manual-user-coop-id" required>
                    <div id="member-search-results"
                        class="hidden absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto"
                        style="display: none;">
                        <!-- Search results will appear here -->
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-1">Start typing to search for members</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Device ID (optional)
                </label>
                <input type="text" id="manual-device-id"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                    placeholder="Leave empty to auto-generate">
            </div>
            <div class="flex items-center">
                <input type="checkbox" id="skip-location-check" class="mr-2">
                <label for="skip-location-check" class="text-sm text-gray-700">Skip location validation</label>
            </div>
            <div class="flex justify-end gap-3 pt-4">
                <button type="button" onclick="closeManualCheckInModal()"
                    class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg">
                    <i class="fas fa-check mr-2"></i>Check In
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Reset Device Lock Modal -->
<div id="resetDeviceModal"
    class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-xl font-semibold text-gray-900">Reset Device Lock</h3>
            <button onclick="closeResetDeviceModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <p class="text-gray-700">This will remove the check-in record(s) for this device, allowing it to be used by
                another user.</p>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Device ID <span class="text-red-500">*</span>
                </label>
                <input type="text" id="reset-device-id" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500"
                    placeholder="Enter device ID to reset">
            </div>
            <div class="flex justify-end gap-3 pt-4">
                <button type="button" onclick="closeResetDeviceModal()"
                    class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button onclick="confirmResetDevice()"
                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg">
                    <i class="fas fa-unlock mr-2"></i>Reset Device
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const apiBaseUrl = '<?php echo "https://www.emmaggi.com/coop_admin/auth_api/api"; ?>';
const eventId = <?php echo $eventId; ?>;

document.addEventListener('DOMContentLoaded', function() {
    loadEventDetails();
});

async function loadEventDetails() {
    try {
        const response = await fetch(`${apiBaseUrl}/admin/events.php/${eventId}`, {
            credentials: 'include'
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();

        if (result.success) {
            displayEventDetails(result.data);
            displayAttendance(result.data.attendance || []);
        } else {
            document.getElementById('event-details-container').innerHTML =
                `<div class="text-center py-8 text-red-500">Error: ${result.message}</div>`;
            Swal.fire('Error', result.message, 'error');
        }
    } catch (error) {
        console.error('Error loading event details:', error);
        document.getElementById('event-details-container').innerHTML =
            `<div class="text-center py-8 text-red-500">Failed to load: ${error.message}</div>`;
        Swal.fire('Error', 'Failed to load event details: ' + error.message, 'error');
    }
}

function displayEventDetails(event) {
    const container = document.getElementById('event-details-container');
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

    container.innerHTML = `
        <div class="bg-white rounded-xl shadow-lg p-6 fade-in">
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">${escapeHtml(event.title)}</h2>
                    ${event.description ? `<p class="text-gray-600">${escapeHtml(event.description)}</p>` : ''}
                </div>
                <span class="px-3 py-1 text-sm font-semibold rounded-full ${statusColor}">
                    ${status.charAt(0).toUpperCase() + status.slice(1)}
                </span>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="text-sm font-medium text-gray-500">Start Time</label>
                    <p class="text-lg text-gray-900">${formatDateTime(event.start_time)}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-500">End Time</label>
                    <p class="text-lg text-gray-900">${formatDateTime(event.end_time)}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-500">Location</label>
                    <p class="text-lg text-gray-900">${event.location_lat.toFixed(6)}, ${event.location_lng.toFixed(6)}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-500">Geofence Radius</label>
                    <p class="text-lg text-gray-900">${event.geofence_radius} meters</p>
                </div>
            </div>
            
            <div class="mt-6">
                <div id="event-map" class="w-full h-64 border border-gray-300 rounded-lg"></div>
            </div>
        </div>
    `;

    // Initialize map
    initEventMap(event.location_lat, event.location_lng, event.geofence_radius);
}

function displayAttendance(attendance) {
    const container = document.getElementById('attendance-container');

    if (!attendance || attendance.length === 0) {
        container.innerHTML = '<div class="text-center py-8 text-gray-500">No attendance records yet</div>';
        return;
    }

    let html =
        '<div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200"><thead class="bg-gray-50"><tr>';
    html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>';
    html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Coop ID</th>';
    html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Check-in Time</th>';
    html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Distance</th>';
    html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Device ID</th>';
    html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>';
    html += '</tr></thead><tbody class="bg-white divide-y divide-gray-200">';

    attendance.forEach(record => {
        const checkInDate = new Date(record.check_in_time);
        const isAdminOverride = record.admin_override === true || record.admin_override === 1;
        html += `<tr>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ${escapeHtml(record.user_name || 'Unknown')}
                ${isAdminOverride ? '<span class="ml-2 px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800" title="Checked in by admin">Admin</span>' : ''}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${escapeHtml(record.user_coop_id)}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${formatDateTime(record.check_in_time)}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${record.distance_from_event.toFixed(2)}m</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                <div class="flex items-center gap-2">
                    <span class="font-mono text-xs">${escapeHtml((record.device_id || 'N/A').substring(0, 20))}${record.device_id && record.device_id.length > 20 ? '...' : ''}</span>
                    <button onclick="openResetDeviceModal('${escapeHtml(record.device_id || '')}')" 
                        class="text-red-600 hover:text-red-800" title="Reset device lock">
                        <i class="fas fa-unlock text-xs"></i>
                    </button>
                </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">
                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">${record.status}</span>
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

let memberSearchTimeout = null;

function clearMemberSearch() {
    const searchInput = document.getElementById('manual-member-search');
    const coopIdInput = document.getElementById('manual-user-coop-id');
    const resultsDiv = document.getElementById('member-search-results');
    const clearButton = document.getElementById('clear-member-search');

    searchInput.value = '';
    coopIdInput.value = '';
    resultsDiv.style.display = 'none';
    resultsDiv.classList.add('hidden');
    resultsDiv.innerHTML = '';
    clearButton.classList.add('hidden');

    // Focus back on search input
    searchInput.focus();
}

function openManualCheckInModal() {
    document.getElementById('manualCheckInModal').classList.remove('hidden');
    document.getElementById('manual-member-search').value = '';
    document.getElementById('manual-user-coop-id').value = '';
    document.getElementById('manual-device-id').value = '';
    document.getElementById('skip-location-check').checked = false;
    const resultsDiv = document.getElementById('member-search-results');
    const clearButton = document.getElementById('clear-member-search');
    resultsDiv.style.display = 'none';
    resultsDiv.classList.add('hidden');
    resultsDiv.innerHTML = '';
    clearButton.classList.add('hidden');

    // Setup member search autocomplete
    setupMemberSearch();
}

function setupMemberSearch() {
    const searchInput = document.getElementById('manual-member-search');
    const resultsDiv = document.getElementById('member-search-results');
    const coopIdInput = document.getElementById('manual-user-coop-id');
    const clearButton = document.getElementById('clear-member-search');

    // Clear previous event listeners by cloning
    const newSearchInput = searchInput.cloneNode(true);
    searchInput.parentNode.replaceChild(newSearchInput, searchInput);

    // Update clear button reference after cloning
    const newClearButton = document.getElementById('clear-member-search');

    function toggleClearButton() {
        if (newSearchInput.value.trim().length > 0) {
            newClearButton.classList.remove('hidden');
        } else {
            newClearButton.classList.add('hidden');
        }
    }

    newSearchInput.addEventListener('input', function() {
        const query = this.value.trim();
        toggleClearButton();

        if (query.length < 2) {
            resultsDiv.style.display = 'none';
            resultsDiv.classList.add('hidden');
            resultsDiv.innerHTML = '';
            coopIdInput.value = '';
            return;
        }

        // Debounce search
        clearTimeout(memberSearchTimeout);
        memberSearchTimeout = setTimeout(() => {
            searchMembers(query);
        }, 300);
    });

    // Show/hide clear button on focus/blur
    newSearchInput.addEventListener('focus', toggleClearButton);

    // Hide results when clicking outside
    document.addEventListener('click', function(e) {
        if (!newSearchInput.contains(e.target) && !resultsDiv.contains(e.target) && !newClearButton.contains(e
                .target)) {
            resultsDiv.style.display = 'none';
            resultsDiv.classList.add('hidden');
        }
    });
}

async function searchMembers(query) {
    const resultsDiv = document.getElementById('member-search-results');
    const coopIdInput = document.getElementById('manual-user-coop-id');

    // Show loading state
    resultsDiv.innerHTML =
        '<div class="px-4 py-2 text-gray-500 text-sm"><i class="fas fa-spinner fa-spin mr-2"></i>Searching...</div>';
    resultsDiv.style.display = 'block';
    resultsDiv.classList.remove('hidden');

    try {
        const response = await fetch(`${apiBaseUrl}/admin/search-members.php?q=${encodeURIComponent(query)}`, {
            credentials: 'include'
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();

        if (result.success && result.data.length > 0) {
            let html = '';
            result.data.forEach(member => {
                const coopId = member.coop_id || '';
                const fullName = member.full_name || '';
                html += `
                    <div class="px-4 py-2 hover:bg-blue-50 cursor-pointer border-b border-gray-100 member-result-item"
                         data-coop-id="${coopId.replace(/"/g, '&quot;')}"
                         data-name="${fullName.replace(/"/g, '&quot;')}">
                        <div class="font-medium text-gray-900">${escapeHtml(fullName)}</div>
                        <div class="text-sm text-gray-500">Coop ID: ${escapeHtml(coopId)}</div>
                    </div>
                `;
            });

            resultsDiv.innerHTML = html;
            resultsDiv.style.display = 'block';
            resultsDiv.classList.remove('hidden');

            // Add click handlers
            document.querySelectorAll('.member-result-item').forEach(item => {
                item.addEventListener('click', function() {
                    const coopId = this.getAttribute('data-coop-id');
                    const name = this.getAttribute('data-name');
                    const clearButton = document.getElementById('clear-member-search');

                    document.getElementById('manual-member-search').value = `${coopId} - ${name}`;
                    coopIdInput.value = coopId;
                    resultsDiv.style.display = 'none';
                    resultsDiv.classList.add('hidden');
                    clearButton.classList.remove('hidden');
                });
            });
        } else {
            resultsDiv.innerHTML = '<div class="px-4 py-2 text-gray-500 text-sm">No members found</div>';
            resultsDiv.style.display = 'block';
            resultsDiv.classList.remove('hidden');
            coopIdInput.value = '';
        }
    } catch (error) {
        console.error('Search error:', error);
        resultsDiv.innerHTML =
            '<div class="px-4 py-2 text-red-500 text-sm"><i class="fas fa-exclamation-circle mr-2"></i>Error searching members. Please try again.</div>';
        resultsDiv.style.display = 'block';
        resultsDiv.classList.remove('hidden');
        coopIdInput.value = '';
    }
}

function closeManualCheckInModal() {
    document.getElementById('manualCheckInModal').classList.add('hidden');
}

function openResetDeviceModal(deviceId) {
    document.getElementById('resetDeviceModal').classList.remove('hidden');
    document.getElementById('reset-device-id').value = deviceId || '';
}

function closeResetDeviceModal() {
    document.getElementById('resetDeviceModal').classList.add('hidden');
}

// Manual check-in form submission
document.getElementById('manualCheckInForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const userCoopId = document.getElementById('manual-user-coop-id').value.trim();
    const deviceId = document.getElementById('manual-device-id').value.trim();
    const skipLocationCheck = document.getElementById('skip-location-check').checked;

    if (!userCoopId) {
        Swal.fire('Error', 'Please search and select a member', 'error');
        return;
    }

    try {
        Swal.fire({
            title: 'Checking in...',
            text: 'Please wait',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        const response = await fetch(`${apiBaseUrl}/admin/manual-checkin.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify({
                event_id: eventId,
                user_coop_id: userCoopId,
                device_id: deviceId || undefined,
                skip_location_check: skipLocationCheck
            })
        });

        const result = await response.json();

        if (result.success) {
            Swal.fire('Success!', result.message, 'success');
            closeManualCheckInModal();
            loadEventDetails();
        } else {
            Swal.fire('Error', result.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire('Error', 'Failed to check in user: ' + error.message, 'error');
    }
});

async function confirmResetDevice() {
    const deviceId = document.getElementById('reset-device-id').value.trim();

    if (!deviceId) {
        Swal.fire('Error', 'Device ID is required', 'error');
        return;
    }

    try {
        Swal.fire({
            title: 'Are you sure?',
            text: 'This will remove the check-in record(s) for this device',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, reset it!'
        }).then(async (result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Resetting...',
                    text: 'Please wait',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                const response = await fetch(`${apiBaseUrl}/admin/reset-device-lock.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    credentials: 'include',
                    body: JSON.stringify({
                        event_id: eventId,
                        device_id: deviceId
                    })
                });

                const result = await response.json();

                if (result.success) {
                    Swal.fire('Success!', result.message, 'success');
                    closeResetDeviceModal();
                    loadEventDetails();
                } else {
                    Swal.fire('Error', result.message, 'error');
                }
            }
        });
    } catch (error) {
        console.error('Error:', error);
        Swal.fire('Error', 'Failed to reset device: ' + error.message, 'error');
    }
}

function initEventMap(lat, lng, radius) {
    const mapContainer = document.getElementById('event-map');
    if (!mapContainer) return;

    const map = new google.maps.Map(mapContainer, {
        center: {
            lat: lat,
            lng: lng
        },
        zoom: 15,
        mapTypeControl: true,
        streetViewControl: true
    });

    // Add marker for event location
    const marker = new google.maps.Marker({
        position: {
            lat: lat,
            lng: lng
        },
        map: map,
        title: 'Event Location',
        icon: {
            path: google.maps.SymbolPath.CIRCLE,
            scale: 10,
            fillColor: '#4285F4',
            fillOpacity: 1,
            strokeColor: '#FFFFFF',
            strokeWeight: 2
        }
    });

    // Add circle for geofence
    const circle = new google.maps.Circle({
        strokeColor: '#4285F4',
        strokeOpacity: 0.8,
        strokeWeight: 2,
        fillColor: '#4285F4',
        fillOpacity: 0.15,
        map: map,
        center: {
            lat: lat,
            lng: lng
        },
        radius: radius
    });
}
</script>

<!-- Google Maps API -->
<?php
require_once('config/EnvConfig.php');
$googleMapsApiKey = EnvConfig::getGoogleMapsApiKey();
?>
<script src="https://maps.googleapis.com/maps/api/js?key=<?php echo htmlspecialchars($googleMapsApiKey); ?>"></script>

<?php include 'includes/footer.php'; ?>