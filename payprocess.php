<?php
// Set page title
$pageTitle = 'OOUTH COOP - Deductions Processing';

// Include header
include 'includes/header.php';

?>

<?php
ini_set('max_execution_time', '0');
session_start();

include_once('classes/model.php');
require_once('Connections/coop.php');
if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '')) {
    header("location: index.php");
    exit();
}

$userName = $_SESSION['SESS_FIRST_NAME'] ?? 'User';
$userRole = $_SESSION['SESS_ROLE'] ?? 'Administrator';
?>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page Header -->
        <div class="mb-8 fade-in">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">
                        <i class="fas fa-cogs text-blue-600 mr-3"></i>Deductions Processing
                    </h2>
                    <p class="text-gray-600">Run the final monthly processing sequence for payroll deductions</p>
                </div>
                <div class="flex space-x-3">
                    <button onclick="window.location.reload()"
                        class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition-colors">
                        <i class="fas fa-refresh mr-2"></i>Refresh
                    </button>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8 fade-in">
            <div class="bg-white rounded-xl shadow-lg p-6 card-hover">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-users text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Members</p>
                        <p class="text-2xl font-bold text-gray-900">
                            <?php
                            $total_employees = $conn->query("SELECT COUNT(*) FROM tblemployees")->fetchColumn();
                            echo number_format($total_employees);
                            ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-6 card-hover">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <i class="fas fa-user-check text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Active Members</p>
                        <p class="text-2xl font-bold text-gray-900">
                            <?php
                            $active_employees = $conn->query("SELECT COUNT(*) FROM tblemployees WHERE Status = 'Active'")->fetchColumn();
                            echo number_format($active_employees);
                            ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-6 card-hover">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                        <i class="fas fa-calendar text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Payroll Periods</p>
                        <p class="text-2xl font-bold text-gray-900">
                            <?php
                            $total_periods = $conn->query("SELECT COUNT(*) FROM tbpayrollperiods")->fetchColumn();
                            echo number_format($total_periods);
                            ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-6 card-hover">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                        <i class="fas fa-cogs text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Processing Status</p>
                        <p class="text-2xl font-bold text-gray-900">Ready</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Warning Card -->
        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6 mb-8 fade-in">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-yellow-400 text-xl"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-lg font-medium text-yellow-800 mb-2">Important Notice</h3>
                    <div class="text-sm text-yellow-700">
                        <p>Before running the final payroll sequence, please ensure all prerequisites regarding member's
                            deductions have been fulfilled.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Processing Form -->
        <div class="bg-white rounded-xl shadow-lg fade-in">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center">
                    <i class="fas fa-cogs text-blue-600 mr-3"></i>
                    <h3 class="text-lg font-semibold text-gray-900">Run Final Monthly Processing Sequence</h3>
                </div>
            </div>

            <div class="p-6">
                <form id="payrollForm" class="space-y-6">
                    <!-- Member Selection -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="member_id" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-users mr-1"></i>Members
                            </label>
                            <select id="member_id" name="member_id"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Select Members</option>
                                <option value="0">Process ALL</option>
                                <?php 
                                $query = $conn->prepare('SELECT * FROM `tblemployees` WHERE status = "Active" ORDER BY LastName');
                                $res = $query->execute();
                                $out = $query->fetchAll(PDO::FETCH_ASSOC);
                                
                                while ($row = array_shift($out)) {
                                    echo '<option value="' . htmlspecialchars($row['CoopID']) . '">';
                                    echo htmlspecialchars($row['LastName'] . ' ' . $row['FirstName'] . ' ' . $row['MiddleName'] . ' - ' . $row['CoopID']);
                                    echo '</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <div>
                            <label for="PeriodID" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-calendar mr-1"></i>Period
                            </label>
                            <select id="PeriodID" name="PeriodID"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Select Period</option>
                                <?php 
                                $query = $conn->prepare('SELECT * FROM tbpayrollperiods ORDER BY id DESC');
                                $res = $query->execute();
                                $out = $query->fetchAll(PDO::FETCH_ASSOC);
                                
                                while ($row = array_shift($out)) {
                                    echo '<option value="' . htmlspecialchars($row['id']) . '">';
                                    echo htmlspecialchars($row['PayrollPeriod']);
                                    echo '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <!-- Progress Section -->
                    <div class="bg-gray-50 rounded-xl p-6">
                        <label class="block text-sm font-medium text-gray-700 mb-4">
                            <i class="fas fa-chart-line mr-1"></i>Processing Progress
                        </label>
                        <div class="space-y-4">
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div id="progressBar" class="progress-bar h-3 rounded-full" style="width: 0%"></div>
                            </div>
                            <div id="progressInfo" class="text-sm text-gray-600">
                                <div id="progressText">Ready to process...</div>
                                <div id="progressDetails" class="mt-1 text-xs text-gray-500"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-center pt-6 border-t border-gray-200">
                        <button type="submit" id="processBtn"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-8 rounded-lg transition-colors flex items-center space-x-2">
                            <i class="fas fa-cogs"></i>
                            <span>Process Monthly Payroll</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Processing Results -->
        <div id="resultsContainer" class="mt-8 hidden fade-in">
            <div class="bg-white rounded-xl shadow-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-600 mr-3"></i>
                        <h3 class="text-lg font-semibold text-gray-900">Processing Results</h3>
                    </div>
                </div>
                <div class="p-6">
                    <div id="resultsContent" class="text-sm text-gray-700"></div>
                </div>
            </div>
        </div>
    </main>


    <!-- Hidden iframe for processing -->
    <iframe id="loadarea" style="display:none;"></iframe>

    <script>
    class PayrollProcessor {
        constructor() {
            this.isProcessing = false;
            this.init();
        }

        init() {
            this.bindEvents();
        }

        bindEvents() {
            $('#payrollForm').on('submit', (e) => this.handleFormSubmit(e));
        }

        handleFormSubmit(e) {
            e.preventDefault();

            const memberId = $('#member_id').val();
            const periodId = $('#PeriodID').val();

            if (!memberId) {
                this.showError('Please select type of process');
                $('#member_id').focus();
                return false;
            }

            if (!periodId) {
                this.showError('Please select period to process');
                $('#PeriodID').focus();
                return false;
            }

            const periodText = $('#PeriodID option:selected').text();
            this.showConfirmation(memberId, periodId, periodText);
        }

        showConfirmation(memberId, periodId, periodText) {
            Swal.fire({
                title: 'Confirm Deductions Processing',
                html: `
                        <div class="text-left">
                            <p class="mb-2">Are you sure you want to run <strong>${periodText}</strong> processing?</p>
                            <div class="bg-yellow-50 border border-yellow-200 rounded p-3 mt-3">
                                <p class="text-sm text-yellow-800">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                    This action cannot be undone. Please ensure all prerequisites are met.
                                </p>
                            </div>
                        </div>
                    `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3b82f6',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, Process Payroll',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    this.startProcessing(memberId, periodId);
                }
            });
        }

        startProcessing(memberId, periodId) {
            this.isProcessing = true;
            this.showLoadingModal();
            this.updateProgress(0, 'Initializing processing...');

            // Disable form
            $('#processBtn').prop('disabled', true);
            $('#processBtn').html('<i class="fas fa-spinner fa-spin mr-2"></i>Processing...');

            // Start AJAX request
            $.ajax({
                type: "GET",
                url: `classes/process.php?PeriodID=${periodId}&member_id=${memberId}`,
                xhrFields: {
                    onprogress: (e) => {
                        this.handleProgress(e);
                    }
                },
                success: (response, message) => {
                    this.handleSuccess(response, message);
                },
                error: (xhr, status, error) => {
                    this.handleError(xhr, status, error);
                }
            });
        }

        handleProgress(e) {
            const responseText = e.target.responseText;
            // console.log('Progress Response:', responseText);

            // Look for progress data lines
            const lines = responseText.split('\n');
            let lastProgressLine = '';

            for (const line of lines) {
                if (line.includes('PROGRESS_DATA:')) {
                    lastProgressLine = line;
                    // console.log('Found progress line:', line);
                }
            }

            // console.log('Last progress line:', lastProgressLine);

            if (lastProgressLine) {
                // Extract progress information from the clean format
                const progressMatch = lastProgressLine.match(
                    /PROGRESS_DATA:\s+Processing\s+(\w+)\s+\((\d+)\s+of\s+(\d+)\s+employees\)\s+-\s+(\d+)%/);
                if (progressMatch) {
                    const coopId = progressMatch[1];
                    const current = parseInt(progressMatch[2]);
                    const total = parseInt(progressMatch[3]);
                    const percentage = parseInt(progressMatch[4]);

                    // console.log(`Progress: ${current}/${total} = ${percentage}% for ${coopId}`);

                    // Update progress bar
                    $('#progressBar').css('width', percentage + '%');
                    $('#progressText').text(`Processing ${coopId} (${current} of ${total} employees)`);
                    $('#progressDetails').text(`Progress: ${percentage}%`);

                    // Also update the loading modal progress
                    $('#loadingModal #progressBar').css('width', percentage + '%');
                    $('#loadingModal #progressText').text(
                        `Processing ${coopId} (${current} of ${total} employees)`);
                } else {
                    // Handle initialization message
                    const initMatch = lastProgressLine.match(/PROGRESS_DATA:\s+(.+?)\s+-\s+(\d+)%/);
                    if (initMatch) {
                        const message = initMatch[1];
                        const percentage = parseInt(initMatch[2]);

                        // console.log(`Initialization: ${message} - ${percentage}%`);

                        // Update progress bar
                        $('#progressBar').css('width', percentage + '%');
                        $('#progressText').text(message);
                        $('#progressDetails').text(`Progress: ${percentage}%`);

                        // Also update the loading modal progress
                        $('#loadingModal #progressBar').css('width', percentage + '%');
                        $('#loadingModal #progressText').text(message);
                    }
                }
            } else {
                // Fallback: try to extract any percentage from the response
                const percentMatch = responseText.match(/(\d+)%/);
                if (percentMatch) {
                    const progress = parseInt(percentMatch[1]);
                    $('#progressBar').css('width', progress + '%');
                    $('#loadingModal #progressBar').css('width', progress + '%');
                }

                // Update text with raw response (but clean it up)
                const cleanText = responseText.replace(/<script[^>]*>[\s\S]*?<\/script>/gi, '').trim();
                if (cleanText) {
                    $('#progressText').text(cleanText);
                    $('#loadingModal #progressText').text(cleanText);
                }
            }
        }

        updateProgress(percentage, text) {
            $('#progressBar').css('width', percentage + '%');
            $('#progressText').text(text);
        }

        handleSuccess(response, message) {
            this.isProcessing = false;

            // Check for completion or error messages in the response
            if (response.includes('COMPLETION: SUCCESS')) {
                this.hideLoadingModal();
                this.showSuccess('Deduction for the month successfully processed!');
                this.showResults(response);
            } else if (response.includes('ERROR:')) {
                this.hideLoadingModal();
                const errorMatch = response.match(/ERROR:\s*(.+)/);
                const errorMessage = errorMatch ? errorMatch[1] : 'An error occurred during processing';
                this.showError(errorMessage);
                this.showResults(response);
            } else {
                this.hideLoadingModal();
                this.showSuccess('Processing completed!');
                this.showResults(response);
            }

            this.resetForm();
        }

        handleError(xhr, status, error) {
            this.isProcessing = false;
            this.hideLoadingModal();
            this.showError('An error occurred during processing: ' + error);
            this.resetForm();
        }

        showLoadingModal() {
            $('#loadingModal').removeClass('hidden');
        }

        hideLoadingModal() {
            $('#loadingModal').addClass('hidden');
        }

        showResults(response) {
            // Clean up the response for display
            const cleanResponse = response
                .replace(/PROGRESS_DATA:.*\n/g, '')
                .replace(/COMPLETION:.*\n/g, '')
                .replace(/ERROR:.*\n/g, '')
                .trim();

            let resultClass = 'text-green-700';
            let resultIcon = 'fa-check-circle';
            let resultMessage = 'Deductions processing completed successfully!';

            if (response.includes('ERROR:')) {
                resultClass = 'text-red-700';
                resultIcon = 'fa-exclamation-triangle';
                resultMessage = 'Processing completed with errors';
            }

            $('#resultsContent').html(`
                    <div class="space-y-2">
                        <p class="${resultClass} font-medium">
                            <i class="fas ${resultIcon} mr-2"></i>
                            ${resultMessage}
                        </p>
                        ${cleanResponse ? `<div class="bg-gray-50 rounded p-3 mt-3">
                            <pre class="text-xs text-gray-600 whitespace-pre-wrap">${cleanResponse}</pre>
                        </div>` : ''}
                    </div>
                `);
            $('#resultsContainer').removeClass('hidden').addClass('fade-in');
        }

        resetForm() {
            $('#processBtn').prop('disabled', false);
            $('#processBtn').html('<i class="fas fa-cogs mr-2"></i>Process Monthly Payroll');
            this.updateProgress(0, 'Ready to process...');
        }

        showSuccess(message) {
            Swal.fire({
                title: 'Success!',
                text: message,
                icon: 'success',
                confirmButtonColor: '#10b981'
            });
        }

        showError(message) {
            Swal.fire({
                title: 'Error!',
                text: message,
                icon: 'error',
                confirmButtonColor: '#ef4444'
            });
        }
    }

    // Initialize when document is ready
    $(document).ready(function() {
        new PayrollProcessor();
    });
    </script>

<?php include 'includes/footer.php'; ?>
