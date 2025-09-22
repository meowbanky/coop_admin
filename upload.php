<?php
require_once('Connections/coop.php');
include_once('classes/model.php');
session_start();

// Check whether the session variable SESS_MEMBER_ID is present or not
if (!isset($_SESSION['SESS_MEMBER_ID']) || trim($_SESSION['SESS_MEMBER_ID']) == '') {
    header("location: index.php");
    exit();
}

// Set page title
$pageTitle = 'OOUTH COOP - File Upload';

// Include header
include 'includes/header.php';

// Get user info
$userName = $_SESSION['SESS_FIRST_NAME'] ?? 'User';
$userRole = $_SESSION['role'] ?? 'User';
?>
<div class="container mx-auto px-4 py-8">
    <!-- Upload Instructions -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8 fade-in">
        <div class="flex items-start">
            <i class="fas fa-info-circle text-blue-600 text-xl mt-1 mr-3"></i>
            <div>
                <h3 class="text-lg font-semibold text-blue-900 mb-2">Upload Instructions</h3>
                <ul class="text-blue-800 space-y-1 text-sm">
                    <li>• Select the appropriate payroll period for your data</li>
                    <li>• Choose an Excel (.xlsx) or CSV file to upload</li>
                    <li>• Ensure your file has proper headers in the first row</li>
                    <li>• The system will process your data and provide feedback</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Upload Form -->
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden fade-in">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">Upload Data File</h2>
                <p class="text-gray-600 text-sm">Select a period and upload your data file</p>
            </div>

            <form id="uploadForm" enctype="multipart/form-data" class="p-6 space-y-6">
                <!-- Period Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-calendar-alt mr-2"></i>Select Period
                    </label>
                    <select id="period" name="period" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="0">Choose a payroll period...</option>
                        <?php
                            mysqli_select_db($coop, $database);
                            $query = "SELECT * FROM tbpayrollperiods ORDER BY id DESC";
                            $result = mysqli_query($coop, $query) or die(mysqli_error($coop));
                            while ($row = mysqli_fetch_assoc($result)) {
                                echo '<option value="' . htmlspecialchars($row['id']) . '">' . htmlspecialchars($row['PayrollPeriod']) . '</option>';
                            }
                            mysqli_free_result($result);
                            ?>
                    </select>
                </div>

                <!-- File Upload Area -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-file-upload mr-2"></i>Select File
                    </label>
                    <div id="uploadArea"
                        class="upload-area rounded-lg p-8 text-center cursor-pointer border-2 border-dashed border-gray-300 hover:border-blue-400 transition-colors">
                        <div id="uploadContent">
                            <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-4"></i>
                            <p class="text-lg font-medium text-gray-600 mb-2">Click to select file or drag and drop</p>
                            <p class="text-sm text-gray-500">Excel (.xlsx) or CSV files only</p>
                            <input type="file" id="fileInput" name="file" accept=".xlsx,.csv" class="hidden" required>
                        </div>
                        <div id="fileInfo" class="hidden">
                            <i class="fas fa-file text-4xl text-green-500 mb-4"></i>
                            <p id="fileName" class="text-lg font-medium text-gray-900 mb-2"></p>
                            <p id="fileSize" class="text-sm text-gray-500 mb-4"></p>
                            <button type="button" id="removeFile" class="text-red-600 hover:text-red-800 text-sm">
                                <i class="fas fa-times mr-1"></i>Remove File
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Options -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="flex items-center">
                        <input type="checkbox" id="hasHeaders" name="hasHeaders" checked
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="hasHeaders" class="ml-3 text-sm text-gray-700">
                            <i class="fas fa-check-square mr-1"></i>
                            File contains headers in the first row
                        </label>
                    </div>
                </div>

                <!-- Upload Button -->
                <div class="flex justify-end space-x-4">
                    <button type="button" id="resetForm"
                        class="px-6 py-3 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        <i class="fas fa-undo mr-2"></i>Reset
                    </button>
                    <button type="submit" id="uploadBtn"
                        class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-upload mr-2"></i>Upload File
                    </button>
                </div>
            </form>
        </div>

        <!-- Progress Section -->
        <div id="progressSection" class="hidden mt-8 bg-white rounded-lg shadow-lg p-6 fade-in">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-spinner mr-2"></i>Upload Progress
            </h3>

            <!-- Progress Bar -->
            <div class="mb-4">
                <div class="flex justify-between text-sm text-gray-600 mb-2">
                    <span id="progressText">Preparing upload...</span>
                    <span id="progressPercent">0%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3">
                    <div id="progressBar" class="progress-bar bg-blue-600 h-3 rounded-full" style="width: 0%"></div>
                </div>
            </div>

            <!-- Status Messages -->
            <div id="statusMessages" class="space-y-2">
                <!-- Status messages will be added here -->
            </div>

            <!-- Results -->
            <div id="uploadResults" class="hidden mt-6">
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-600 text-xl mr-3"></i>
                        <div>
                            <h4 class="text-lg font-semibold text-green-900">Upload Complete!</h4>
                            <p id="successMessage" class="text-green-800 text-sm"></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Error Results -->
            <div id="errorResults" class="hidden mt-6">
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle text-red-600 text-xl mr-3"></i>
                        <div>
                            <h4 class="text-lg font-semibold text-red-900">Upload Failed</h4>
                            <p id="errorMessage" class="text-red-800 text-sm"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading Modal -->
<div id="loadingModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen">
        <div class="bg-white rounded-lg shadow-xl p-8 text-center">
            <div class="loading-spinner mx-auto mb-4"></div>
            <p class="text-gray-600">Processing your file...</p>
        </div>
    </div>
</div>

<style>
.upload-area.dragover {
    border-color: #3b82f6 !important;
    background-color: #eff6ff;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #f3f4f6;
    border-top: 4px solid #3b82f6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% {
        transform: rotate(0deg);
    }

    100% {
        transform: rotate(360deg);
    }
}

.fade-in {
    animation: fadeIn 0.5s ease-in;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

<script>
class FileUploadManager {
    constructor() {
        this.initialized = false;
        this.init();
    }

    init() {
        if (this.initialized) return;
        this.initialized = true;
        this.bindEvents();
    }

    bindEvents() {
        // Remove any existing event handlers to prevent duplicates
        $('#fileInput').off('change');
        $('#uploadArea').off('click dragover dragleave drop');
        $('#removeFile').off('click');
        $('#uploadForm').off('submit');
        $('#resetForm').off('click');

        // File input change
        $('#fileInput').on('change', (e) => this.handleFileSelect(e));

        // Upload area click
        $('#uploadArea').on('click', (e) => {
            e.preventDefault();
            $('#fileInput').click();
        });

        // Drag and drop
        $('#uploadArea').on('dragover', (e) => this.handleDragOver(e));
        $('#uploadArea').on('dragleave', (e) => this.handleDragLeave(e));
        $('#uploadArea').on('drop', (e) => this.handleDrop(e));

        // Remove file
        $('#removeFile').on('click', (e) => {
            e.preventDefault();
            this.removeFile();
        });

        // Form submission
        $('#uploadForm').on('submit', (e) => this.handleSubmit(e));

        // Reset form
        $('#resetForm').on('click', (e) => {
            e.preventDefault();
            this.resetForm();
        });
    }

    handleFileSelect(e) {
        const file = e.target.files[0];
        if (file) {
            this.displayFileInfo(file);
        }
    }

    handleDragOver(e) {
        e.preventDefault();
        $('#uploadArea').addClass('dragover');
    }

    handleDragLeave(e) {
        e.preventDefault();
        $('#uploadArea').removeClass('dragover');
    }

    handleDrop(e) {
        e.preventDefault();
        $('#uploadArea').removeClass('dragover');

        const files = e.originalEvent.dataTransfer.files;
        if (files.length > 0) {
            const file = files[0];
            if (this.validateFile(file)) {
                $('#fileInput')[0].files = files;
                this.displayFileInfo(file);
            }
        }
    }

    validateFile(file) {
        const allowedTypes = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv'];
        const allowedExtensions = ['.xlsx', '.csv'];
        const fileExtension = '.' + file.name.split('.').pop().toLowerCase();

        if (!allowedTypes.includes(file.type) && !allowedExtensions.includes(fileExtension)) {
            this.showError('Please select a valid Excel (.xlsx) or CSV file.');
            return false;
        }

        if (file.size > 10 * 1024 * 1024) { // 10MB limit
            this.showError('File size must be less than 10MB.');
            return false;
        }

        return true;
    }

    displayFileInfo(file) {
        $('#uploadContent').addClass('hidden');
        $('#fileInfo').removeClass('hidden');
        $('#fileName').text(file.name);
        $('#fileSize').text(this.formatFileSize(file.size));
    }

    removeFile() {
        $('#fileInput').val('');
        $('#fileInfo').addClass('hidden');
        $('#uploadContent').removeClass('hidden');
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    async handleSubmit(e) {
        e.preventDefault();

        if ($('#period').val() == '0') {
            this.showError('Please select a payroll period.');
            return;
        }

        if (!$('#fileInput')[0].files[0]) {
            this.showError('Please select a file to upload.');
            return;
        }

        this.showProgress();
        this.resetProgress();

        const formData = new FormData(e.target);

        try {
            const response = await fetch('excel_import/import_office.php', {
                method: 'POST',
                body: formData
            });

            if (response.ok) {
                const result = await response.text();
                this.handleUploadSuccess(result);
            } else {
                this.handleUploadError('Upload failed. Please try again.');
            }
        } catch (error) {
            console.error('Upload error:', error);
            this.handleUploadError('An error occurred during upload.');
        }
    }

    showProgress() {
        $('#progressSection').removeClass('hidden');
        $('#uploadBtn').prop('disabled', true);
        this.updateProgress(0, 'Preparing upload...');
    }

    resetProgress() {
        $('#progressBar').css('width', '0%');
        $('#progressPercent').text('0%');
        $('#statusMessages').empty();
        $('#uploadResults').addClass('hidden');
        $('#errorResults').addClass('hidden');
    }

    updateProgress(percent, text) {
        $('#progressBar').css('width', percent + '%');
        $('#progressPercent').text(percent + '%');
        $('#progressText').text(text);
    }

    addStatusMessage(message, type = 'info') {
        const iconClass = type === 'error' ? 'fa-exclamation-circle text-red-500' :
            type === 'success' ? 'fa-check-circle text-green-500' :
            'fa-info-circle text-blue-500';

        const messageDiv = $(`
                <div class="flex items-center text-sm">
                    <i class="fas ${iconClass} mr-2"></i>
                    <span>${message}</span>
                </div>
            `);

        $('#statusMessages').append(messageDiv);
    }

    handleUploadSuccess(result) {
        this.updateProgress(100, 'Upload completed successfully!');
        this.addStatusMessage('File processed successfully', 'success');

        $('#uploadResults').removeClass('hidden');
        $('#successMessage').text('Your file has been uploaded and processed successfully.');

        // Reset form after success
        setTimeout(() => {
            this.resetForm();
            $('#progressSection').addClass('hidden');
        }, 3000);
    }

    handleUploadError(message) {
        this.updateProgress(0, 'Upload failed');
        this.addStatusMessage(message, 'error');

        $('#errorResults').removeClass('hidden');
        $('#errorMessage').text(message);

        $('#uploadBtn').prop('disabled', false);
    }

    resetForm() {
        $('#uploadForm')[0].reset();
        this.removeFile();
        $('#progressSection').addClass('hidden');
        $('#uploadBtn').prop('disabled', false);
    }

    showError(message) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: message
        });
    }
}

// Initialize the file upload manager when DOM is ready
$(document).ready(function() {
    // Only initialize if not already initialized
    if (typeof window.uploadManager === 'undefined') {
        window.uploadManager = new FileUploadManager();
    }
});
</script>

<?php include 'includes/footer.php'; ?>