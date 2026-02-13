<?php
require_once(__DIR__ . '/Connections/coop.php'); // Ensure DB connection exists
// Session and auth handled by header.php

$pageTitle = "Bulk SMS Center";
include 'includes/header.php';
?>

<header class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
    <div>
        <h2 class="text-2xl font-bold tracking-tight">Bulk SMS Center</h2>
        <p class="text-slate-500 mt-1">Send notifications to all members or specific contacts.</p>
    </div>
    
    <!-- Balance Check Display -->
    <div class="flex items-center gap-3 bg-white px-4 py-2 rounded-xl border border-slate-200 shadow-sm">
        <div class="p-2 rounded-full bg-emerald-100 text-emerald-600">
            <span class="material-icons-round text-xl">account_balance_wallet</span>
        </div>
        <div>
            <p class="text-xs text-slate-500 font-medium uppercase tracking-wider">SMS Balance</p>
            <p class="font-bold text-slate-900" id="smsBalanceDisplay">Loading...</p>
        </div>
    </div>
</header>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- SMS Form -->
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-surface-light border border-slate-200 rounded-2xl p-6 shadow-sm mb-6 bg-white">
            <label class="block text-sm font-medium text-slate-700 mb-2">Search & Add Recipients</label>
            <div class="relative">
                <input type="text" id="memberSearch" class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:ring-primary focus:border-primary outline-none transition-all" placeholder="Search by name or member ID...">
                <span class="material-icons-round absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">search</span>
            </div>
            <!-- Search Results -->
            <div id="searchResults" class="hidden mt-2 bg-white border border-slate-200 rounded-xl shadow-lg max-h-60 overflow-y-auto divide-y divide-slate-100 z-10 w-full absolute">
                <!-- Items appended here -->
            </div>
        </div>

        <div class="bg-surface-light border border-slate-200 rounded-2xl p-6 shadow-sm bg-white">
            
            <div class="mb-6">
                <div class="flex justify-between items-center mb-2">
                    <label class="block text-sm font-medium text-slate-700">Recipients</label>
                    <div class="flex gap-2">
                        <button onclick="addAllContacts()" class="px-3 py-1.5 text-xs font-semibold bg-emerald-100 text-emerald-700 rounded-lg hover:bg-emerald-200 transition-colors flex items-center gap-1">
                            <span class="material-icons-round text-sm">group_add</span> Add All Active
                        </button>
                        <button onclick="clearRecipients()" class="px-3 py-1.5 text-xs font-semibold bg-slate-100 text-slate-600 rounded-lg hover:bg-slate-200 transition-colors flex items-center gap-1">
                            <span class="material-icons-round text-sm">backspace</span> Clear
                        </button>
                    </div>
                </div>
                <textarea id="recipientList" rows="4" class="w-full px-4 py-3 bg-slate-50 border border-slate-300 rounded-xl focus:ring-primary focus:border-primary outline-none transition-all resize-none font-mono text-sm" placeholder="Enter mobile numbers separated by commas (e.g. 08012345678, 09087654321)"></textarea>
                <p class="text-xs text-slate-500 mt-2">Total Recipients: <span id="recipientCount" class="font-bold">0</span></p>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-slate-700 mb-2">Message</label>
                <textarea id="smsMessage" rows="5" class="w-full px-4 py-3 bg-slate-50 border border-slate-300 rounded-xl focus:ring-primary focus:border-primary outline-none transition-all resize-none" placeholder="Type your message here..."></textarea>
                <div class="flex justify-between items-center mt-2">
                    <p class="text-xs text-slate-500">
                        <span id="charLabel">Count:</span> <span id="charCount" class="font-bold text-slate-900">0</span> | 
                        Pages: <span id="pageCount" class="font-bold text-slate-900">0</span>
                        <span class="ml-1 text-slate-400"></span>
                    </p>
                    <p class="text-xs text-slate-500">
                        Est. Cost: <span id="estCost" class="font-bold text-slate-900">₦0.00</span>
                    </p>
                </div>
            </div>

            <button onclick="sendBulkSMS()" id="btnSend" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-lg shadow-primary/20 transition-all flex items-center justify-center gap-2">
                <span class="material-icons-round">send</span>
                Send Broadcast
            </button>

        </div>
    </div>

    <!-- Guidelines / Status -->
    <div class="space-y-6">
        <!-- Status Card -->
        <div class="bg-surface-light border border-slate-200 rounded-2xl p-6 shadow-sm bg-white">
            <h3 class="font-bold mb-4 flex items-center gap-2">
                <span class="material-icons-round text-slate-500">info</span>
                Usage Guidelines
            </h3>
            <ul class="space-y-3 text-sm text-slate-600">
                <li class="flex items-start gap-2">
                    <span class="material-icons-round text-primary text-base mt-0.5">check_circle</span>
                    <span>Numbers are automatically formatted (e.g., 080... becomes 23480...).</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="material-icons-round text-primary text-base mt-0.5">check_circle</span>
                    <span>Separate multiple numbers with commas.</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="material-icons-round text-amber-500 text-base mt-0.5">warning</span>
                    <span>Avoid using special characters that may break SMS encoding.</span>
                </li>
            </ul>
        </div>

        <!-- SMS History Report -->
        <div class="bg-surface-light border border-slate-200 rounded-2xl p-6 shadow-sm bg-white">
            <h3 class="font-bold mb-4 flex items-center gap-2">
                <span class="material-icons-round text-slate-500">history</span>
                SMS History
            </h3>
            
            <?php
            // Fetch History Logic (Paginated)
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $perPage = 10;
            $history = [];
            
            try {
                // Include Notification Service from classes/services subdirectory
                if (file_exists('classes/services/NotificationService.php')) {
                    require_once('classes/services/NotificationService.php');
                } else {
                    error_log("SMS History: classes/services/NotificationService.php not found");
                }

                // Debug: Check class and conn
                if (class_exists('App\Services\NotificationService') && isset($conn)) {
                     $svc = new App\Services\NotificationService($conn);
                     $history = $svc->getSMSInbox();
                } else {
                    error_log("SMS History: Class not found or Conn missing.");
                }
            } catch (Exception $e) {
                // Silent fail
                error_log("SMS History Error: " . $e->getMessage());
            }
            
            // Simple Array Pagination
            $totalItems = count($history);
            $totalPages = ceil($totalItems / $perPage);
            // Ensure page is valid
            if ($page < 1) $page = 1;
            if ($page > $totalPages && $totalPages > 0) $page = $totalPages;

            $offset = ($page - 1) * $perPage;
            $currentPageItems = array_slice($history, $offset, $perPage);

            // --- Name Resolution Logic ---
            $phoneToNameMap = [];
            if (!empty($currentPageItems) && isset($conn)) {
                $phonesToLookup = [];
                foreach ($currentPageItems as $item) {
                    if (!empty($item['receiver'])) {
                        $p = $item['receiver'];
                        if (strlen($p) >= 10) {
                             $phonesToLookup[] = substr($p, -10);
                        }
                    }
                }
                
                // Batch query
                if (!empty($phonesToLookup)) {
                    $phonesToLookup = array_unique($phonesToLookup);
                    $placeholders = implode(',', array_fill(0, count($phonesToLookup), '?'));
                    
                    $sql = "SELECT MobileNumber, FirstName, LastName FROM tblemployees 
                            WHERE RIGHT(MobileNumber, 10) IN ($placeholders)";
                    
                    try {
                        $stmt = $conn->prepare($sql);
                        $stmt->execute(array_values($phonesToLookup));
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $k = substr($row['MobileNumber'], -10);
                            $phoneToNameMap[$k] = $row['FirstName'] . ' ' . $row['LastName'];
                        }
                    } catch (Exception $e) {
                        error_log("Name Resolution Error: " . $e->getMessage());
                    }
                }
            }

            ?>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-slate-600">
                    <thead class="text-xs text-slate-700 uppercase bg-slate-50">
                        <tr>
                            <th scope="col" class="px-4 py-3 rounded-l-lg">Receiver</th>
                            <th scope="col" class="px-4 py-3">Message</th>
                            <th scope="col" class="px-4 py-3">Status</th>
                            <th scope="col" class="px-4 py-3 rounded-r-lg">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (!empty($currentPageItems)): ?>
                            <?php foreach ($currentPageItems as $msg): ?>
                                <tr class="hover:bg-slate-50 transition-colors group">
                                    <td class="px-4 py-3 font-medium text-slate-900">
                                        <?php 
                                            $rawPhone = $msg['receiver'] ?? '';
                                            $searchKey = strlen($rawPhone) >= 10 ? substr($rawPhone, -10) : $rawPhone;
                                            $displayName = $phoneToNameMap[$searchKey] ?? $rawPhone;

                                            if (isset($phoneToNameMap[$searchKey])) {
                                                echo htmlspecialchars($displayName);
                                                echo '<div class="text-xs text-slate-400 font-mono mt-0.5">' . htmlspecialchars($rawPhone) . '</div>';
                                            } else {
                                                 echo htmlspecialchars($displayName);
                                            }
                                        ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="max-w-xs truncate cursor-help" title="<?php echo htmlspecialchars($msg['message'] ?? '', ENT_QUOTES); ?>">
                                            <?php echo htmlspecialchars($msg['message'] ?? '-'); ?>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?php 
                                            $status = strtolower($msg['status'] ?? 'unknown');
                                            $color = match($status) {
                                                'delivered' => 'text-emerald-600 bg-emerald-100',
                                                'sent' => 'text-blue-600 bg-blue-100',
                                                'pending' => 'text-amber-600 bg-amber-100',
                                                'failed', 'dnd', 'rejected' => 'text-red-600 bg-red-100',
                                                default => 'text-slate-600 bg-slate-100'
                                            };
                                        ?>
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $color; ?>">
                                            <?php echo ucfirst($status); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <?php echo isset($msg['created_at']) ? date('M d, H:i', strtotime($msg['created_at'])) : '-'; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-slate-500">
                                    No SMS history found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="flex justify-between items-center mt-4">
                <span class="text-xs text-slate-500">
                    Page <?php echo $page; ?> of <?php echo $totalPages; ?>
                </span>
                <div class="flex gap-2">
                     <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>" class="px-3 py-1 text-xs font-medium bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">
                            Previous
                        </a>
                     <?php endif; ?>
                     
                     <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>" class="px-3 py-1 text-xs font-medium bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">
                            Next
                        </a>
                     <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // --- Search Logic ---
    let globalBalance = 0.00;

    $(document).ready(function() {
        fetchBalance();
        
        // Add Material Icons if not present
        if (!$('link[href*="material-icons"]').length) {
            $('head').append('<link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">');
        }
    });

    function fetchBalance() {
        $.post('coop_bulksms_api.php', { action: 'get_balance' }, function(res) {
            if (res.status === 'success') {
                globalBalance = parseFloat(res.data.balance);
                $('#smsBalanceDisplay').text('₦' + globalBalance.toLocaleString(undefined, {minimumFractionDigits: 2}));
            } else {
                 $('#smsBalanceDisplay').text('Error');
            }
        }, 'json');
    }

    // --- Search Logic ---
    let searchTimeout;
    $('#memberSearch').on('input', function() {
        const term = $(this).val();
        clearTimeout(searchTimeout);
        const resultsBox = $('#searchResults');

        if (term.length < 3) {
            resultsBox.addClass('hidden').empty();
            return;
        }

        searchTimeout = setTimeout(() => {
            $.post('coop_bulksms_api.php', { action: 'search_members', term: term }, function(res) {
                resultsBox.empty().removeClass('hidden');
                
                if (res.status === 'success' && res.data.length > 0) {
                    res.data.forEach(m => {
                        const item = `
                            <div onclick="addContact('${m.MobileNumber}', '${m.fullname}')" class="p-3 hover:bg-slate-50 cursor-pointer transition-colors flex justify-between items-center group border-b last:border-0">
                                <div>
                                    <p class="font-semibold text-sm text-slate-900">${m.fullname}</p>
                                    <p class="text-xs text-slate-500 font-mono">${m.CoopID} | ${m.MobileNumber}</p>
                                </div>
                                <span class="material-icons-round text-blue-600 opacity-0 group-hover:opacity-100 transition-opacity">add_circle</span>
                            </div>
                        `;
                        resultsBox.append(item);
                    });
                } else {
                    resultsBox.append('<div class="p-4 text-sm text-slate-500 text-center">No active members found.</div>');
                }
            }, 'json');
        }, 300);
    });

    window.addContact = function(phone, name) {
        if (!phone) return;
        const currentVal = $('#recipientList').val();
        // Check if already in list to avoid duplicates (basic check)
        if (currentVal.indexOf(phone) !== -1) {
             Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'info',
                title: `${name} is already in the list`,
                showConfirmButton: false,
                timer: 2000
            });
            $('#memberSearch').val('');
            $('#searchResults').addClass('hidden').empty();
            return;
        }

        const newVal = currentVal ? (currentVal + ', ' + phone) : phone;
        $('#recipientList').val(newVal).trigger('input');
        
        // Clear search
        $('#memberSearch').val('');
        $('#searchResults').addClass('hidden').empty();
        
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000
        });
        Toast.fire({ icon: 'success', title: `Added ${name}` });
    };

    // --- Counter & Recipient Logic ---

    $('#recipientList').on('input', function() {
        const text = $(this).val();
        const count = text.split(',').filter(s => s.trim().length > 0).length;
        $('#recipientCount').text(count);
        calculateCost();
    });



    function calculateCost() {
        const text = $('#smsMessage').val();
        const len = text.length;
        
        // Special character check
        // User list: ; // ^ { } \ [ ~ ] | € ' ”
        // Validating regex against: ^ { } \ [ ~ ] | € ”
        const specialPattern = /[\^\{\}\\\\\[\~\]\|\€\”]/;
        const isSpecial = specialPattern.test(text);

        let pages = 0;
        if (len > 0) {
            if (isSpecial) {
                // Unicode/Special: 70 chars per page
                // Concatenated: usually 67 chars
                if (len <= 70) pages = 1;
                else pages = Math.ceil(len / 67);
                
                // Show warning style or feedback if needed, optionally
                $('#charLabel').text('Count (Special detected):');
                $('#charLabel').addClass('text-amber-600');
            } else {
                // Standard: 160 chars per page
                // Concatenated: 153 chars
                if (len <= 160) pages = 1;
                else pages = Math.ceil(len / 153);
                
                $('#charLabel').text('Count:');
                $('#charLabel').removeClass('text-amber-600');
            }
        }
        
        const recipients = $('#recipientList').val().split(',').filter(s => s.trim().length > 0).length;
        const cost = pages * recipients * 5; // 5 Naira per page
        
        $('#estCost').text('₦' + cost.toLocaleString(undefined, {minimumFractionDigits: 2}));
        
        // Visual Warning
        if (cost > globalBalance) {
             $('#estCost').addClass('text-red-600').removeClass('text-slate-900');
        } else {
             $('#estCost').removeClass('text-red-600').addClass('text-slate-900');
        }
        
        $('#pageCount').text(pages);
        return cost;
    }

    $('#smsMessage').on('input', function() {
        const len = $(this).val().length;
        $('#charCount').text(len);
        
        calculateCost();
    });

    function clearRecipients() {
        $('#recipientList').val('').trigger('input');
    }

    function addAllContacts() {
        // Show loading state
        const btn = $(event.currentTarget);
        const originalText = btn.html();
        btn.html('<span class="inline-block animate-spin rounded-full h-4 w-4 border-2 border-current border-r-transparent"></span> Loading...');
        btn.prop('disabled', true);

        $.post('coop_bulksms_api.php', { action: 'fetch_all_contacts' }, function(res) {
            if (res.status === 'success') {
                const currentVal = $('#recipientList').val();
                
                // Avoid duplicates logic slightly redundant here if we just append, 
                // but user asked for "Add All", so we assume they want everyone.
                // We'll just clear and add all? Or Append?
                // Logic: Append unique numbers? That's heavy client side. 
                // Let's just append for now as per original script logic.
                
                const newNumbers = res.data.join(', ');
                const finalVal = currentVal ? (currentVal + ', ' + newNumbers) : newNumbers;
                $('#recipientList').val(finalVal).trigger('input');
                
                const count = res.data.length;
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: `Added ${count} contacts`,
                    showConfirmButton: false,
                    timer: 3000
                });
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        }, 'json').always(() => {
            btn.html(originalText);
            btn.prop('disabled', false);
        });
    }

    function sendBulkSMS() {
        const recipients = $('#recipientList').val();
        const message = $('#smsMessage').val();

        if (!recipients.trim()) {
            Swal.fire('Error', 'Please add at least one recipient.', 'warning');
            return;
        }
        if (!message.trim()) {
            Swal.fire('Error', 'Please enter a message.', 'warning');
            return;
        }


        const estimatedCost = calculateCost();
        if (estimatedCost > globalBalance) {
             Swal.fire({
                title: 'Insufficient Balance',
                text: `Estimated cost (₦${estimatedCost.toLocaleString(undefined, {minimumFractionDigits: 2})}) exceeds your balance (₦${globalBalance.toLocaleString(undefined, {minimumFractionDigits: 2})}). Please top up.`,
                icon: 'error'
             });
             return;
        }

        Swal.fire({
            title: 'Send Broadcast?',
            text: `You are about to send an SMS to ${$('#recipientCount').text()} recipients.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#2563eb',
            confirmButtonText: 'Yes, Send',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return $.post('coop_bulksms_api.php', {
                    action: 'send_bulk_sms',
                    recipients: recipients,
                    message: message
                }, null, 'json')
                .then(response => {
                    if (response.status !== 'success') {
                        throw new Error(response.message);
                    }
                    return response;
                })
                .catch(error => {
                    Swal.showValidationMessage(`Request failed: ${error}`);
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Broadcast Sent!',
                    text: result.value.message,
                    icon: 'success'
                });
                // Optional: clear form
                // clearRecipients();
                // $('#smsMessage').val('');
            }
        });
    }
</script>

<?php include 'includes/footer.php'; ?>
