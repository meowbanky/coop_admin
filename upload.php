<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '')) {
    header("location: index.php");
    exit();
}

// Database connection
include 'Connections/coop.php';

$pageTitle = 'File Upload - OOUTH COOP';
include 'includes/header.php';
?>

<div class="max-w-4xl mx-auto">
    <!-- Page Header -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">
                    <i class="fas fa-file-upload text-blue-600 mr-3"></i>File Upload
                </h1>
                <p class="text-gray-600">Upload Excel files for data processing and management</p>
            </div>
            <div class="bg-blue-100 p-4 rounded-lg">
                <i class="fas fa-info-circle text-blue-600 text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Upload Form -->
    <div class="bg-white rounded-xl shadow-lg p-8">
        <form id="uploadForm" enctype="multipart/form-data" class="space-y-6">
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
                        <!-- Top Browse Button -->
                        <label for="fileInput"
                            class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors cursor-pointer inline-block mt-4">
                            <i class="fas fa-folder-open mr-2"></i>Browse Files
                        </label>
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

            <!-- Progress Section -->
            <div id="progressSection" class="hidden">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-blue-800">Upload Progress</span>
                        <span id="progressPercent" class="text-sm font-medium text-blue-800">0%</span>
                    </div>
                    <div class="w-full bg-blue-200 rounded-full h-2">
                        <div id="progressBar" class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                            style="width: 0%"></div>
                    </div>
                    <p id="progressText" class="text-sm text-blue-700 mt-2">Preparing upload...</p>
                    <div id="statusMessages" class="mt-3 space-y-1"></div>
                </div>
            </div>

            <!-- Results -->
            <div id="uploadResults" class="hidden">
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 mr-3"></i>
                        <p id="successMessage" class="text-green-800 font-medium">Upload successful!</p>
                    </div>
                </div>
            </div>

            <div id="errorResults" class="hidden">
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                        <p id="errorMessage" class="text-red-800 font-medium">Upload failed!</p>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end space-x-4">
                <button type="button" id="resetForm"
                    class="px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-colors">
                    <i class="fas fa-undo mr-2"></i>Reset
                </button>
                <button type="submit" id="uploadBtn"
                    class="px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors font-medium">
                    <i class="fas fa-upload mr-2"></i>Upload File
                </button>
            </div>
        </form>


    </div>
</div>

<style>
.upload-area {
    transition: all 0.3s ease;
}

.upload-area:hover {
    border-color: #3b82f6;
    background-color: #f8fafc;
}

.upload-area.dragover {
    border-color: #3b82f6;
    background-color: #eff6ff;
    transform: scale(1.02);
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

<?php   include 'includes/footer.php'; ?>

<script>
// Wait until the DOM is fully loaded
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('fileInput');
    const uploadArea = document.getElementById('uploadArea');
    const uploadContent = document.getElementById('uploadContent');
    const fileInfo = document.getElementById('fileInfo');
    const fileNameElem = document.getElementById('fileName');
    const fileSizeElem = document.getElementById('fileSize');
    const removeFileBtn = document.getElementById('removeFile');
    const uploadForm = document.getElementById('uploadForm');
    const progressSection = document.getElementById('progressSection');
    const progressBar = document.getElementById('progressBar');
    const progressPercent = document.getElementById('progressPercent');
    const progressText = document.getElementById('progressText');
    const statusMessages = document.getElementById('statusMessages');
    const uploadResults = document.getElementById('uploadResults');
    const errorResults = document.getElementById('errorResults');
    const uploadBtn = document.getElementById('uploadBtn');
    const resetFormBtn = document.getElementById('resetForm');

    // Helper: Display selected file info
    function displayFileInfo(file) {
        uploadContent.classList.add('hidden');
        fileInfo.classList.remove('hidden');
        fileNameElem.textContent = file.name;

        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(file.size) / Math.log(1024));
        fileSizeElem.textContent = (file.size / Math.pow(1024, i)).toFixed(2) + ' ' + sizes[i];
    }

    // Helper: Validate file
    function validateFile(file) {
        const allowedTypes = [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/csv'
        ];
        const allowedExtensions = ['.xlsx', '.csv'];
        const fileExt = '.' + file.name.split('.').pop().toLowerCase();

        if (!allowedTypes.includes(file.type) && !allowedExtensions.includes(fileExt)) {
            Swal.fire('Error', 'Please select a valid Excel (.xlsx) or CSV file.', 'error');
            return false;
        }
        if (file.size > 10 * 1024 * 1024) {
            Swal.fire('Error', 'File size must be less than 10MB.', 'error');
            return false;
        }
        return true;
    }

    // File input change
    fileInput.addEventListener('change', function(e) {
        if (fileInput.files.length === 0) return;
        const file = fileInput.files[0];
        if (!validateFile(file)) {
            fileInput.value = '';
            return;
        }
        displayFileInfo(file);
    });

    // Drag-and-drop
    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('dragover');
    });
    uploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
    });
    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
        if (e.dataTransfer.files.length === 0) return;
        const file = e.dataTransfer.files[0];
        if (!validateFile(file)) return;
        fileInput.files = e.dataTransfer.files; // Set input files
        displayFileInfo(file);
    });

    // Remove selected file
    removeFileBtn.addEventListener('click', function() {
        fileInput.value = '';
        fileInfo.classList.add('hidden');
        uploadContent.classList.remove('hidden');
    });

    // Form submission
    uploadForm.addEventListener('submit', function(e) {
        e.preventDefault();

        if (document.getElementById('period').value === '0') {
            Swal.fire('Error', 'Please select a payroll period.', 'error');
            return;
        }
        if (!fileInput.files[0]) {
            Swal.fire('Error', 'Please select a file to upload.', 'error');
            return;
        }

        const formData = new FormData(uploadForm);

        // Reset progress/status
        progressSection.classList.remove('hidden');
        progressBar.style.width = '0%';
        progressPercent.textContent = '0%';
        progressText.textContent = 'Preparing upload...';
        statusMessages.innerHTML = '';
        uploadResults.classList.add('hidden');
        errorResults.classList.add('hidden');
        uploadBtn.disabled = true;

        fetch('excel_import/import_office.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.ok ? response.text() : Promise.reject('Upload failed'))
            .then(result => {
                progressBar.style.width = '100%';
                progressPercent.textContent = '100%';
                progressText.textContent = 'Upload completed successfully!';

                // Extract information from the response
                const infoMatch = result.match(/parent\.document\.getElementById\("information"\)\.innerHTML="([^"]+)"/);
                const messageMatch = result.match(/parent\.document\.getElementById\("message"\)\.innerHTML="([^"]+)"/);
                
                const infoText = infoMatch ? infoMatch[1] : 'File processed successfully';
                const messageText = messageMatch ? messageMatch[1] : 'Import completed successfully';

                const messageDiv = document.createElement('div');
                messageDiv.className = 'flex items-center text-sm';
                messageDiv.innerHTML = '<i class="fas fa-check-circle text-green-500 mr-2"></i><span>' + messageText + '</span>';
                statusMessages.appendChild(messageDiv);

                // Add detailed information if available
                if (infoText && infoText !== 'File processed successfully') {
                    const infoDiv = document.createElement('div');
                    infoDiv.className = 'flex items-start text-sm mt-2';
                    infoDiv.innerHTML = '<i class="fas fa-info-circle text-blue-500 mr-2 mt-1"></i><span class="text-gray-700">' + infoText + '</span>';
                    statusMessages.appendChild(infoDiv);
                }

                uploadResults.classList.remove('hidden');
                uploadBtn.disabled = false;
            })
            .catch(error => {
                console.error(error);
                progressBar.style.width = '0%';
                progressPercent.textContent = '0%';
                progressText.textContent = 'Upload failed';

                const messageDiv = document.createElement('div');
                messageDiv.className = 'flex items-center text-sm';
                messageDiv.innerHTML =
                    '<i class="fas fa-exclamation-circle text-red-500 mr-2"></i><span>An error occurred during upload.</span>';
                statusMessages.appendChild(messageDiv);

                errorResults.classList.remove('hidden');
                uploadBtn.disabled = false;
            });
    });

    // Reset form
    resetFormBtn.addEventListener('click', function() {
        uploadForm.reset();
        fileInput.value = '';
        fileInfo.classList.add('hidden');
        uploadContent.classList.remove('hidden');
        progressSection.classList.add('hidden');
        uploadResults.classList.add('hidden');
        errorResults.classList.add('hidden');
        statusMessages.innerHTML = '';
        uploadBtn.disabled = false;
    });
});
</script>