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

<?php include 'includes/footer.php'; ?>

<script>
// Upload functionality - runs after footer to avoid conflicts
setTimeout(function() {
    console.log('Initializing upload functionality...');
    console.log('Document ready state:', document.readyState);

    // Check if elements exist
    const fileInput = document.getElementById('fileInput');
    const uploadArea = document.getElementById('uploadArea');
    const uploadForm = document.getElementById('uploadForm');
    const periodSelect = document.getElementById('period');

    console.log('Elements found:', {
        fileInput: !!fileInput,
        uploadArea: !!uploadArea,
        uploadForm: !!uploadForm,
        periodSelect: !!periodSelect
    });

    if (!fileInput) {
        console.error('fileInput element not found');
        return;
    }
    if (!uploadArea) {
        console.error('uploadArea element not found');
        return;
    }
    if (!uploadForm) {
        console.error('uploadForm element not found');
        return;
    }
    if (!periodSelect) {
        console.error('periodSelect element not found');
        return;
    }

    console.log('All elements found, setting up listeners');

    // Test file input click
    console.log('Testing file input click...');
    fileInput.addEventListener('click', function(e) {
        console.log('File input clicked directly');
    });

    // File input change
    fileInput.addEventListener('change', function(e) {
        console.log('File input changed');
        const file = e.target.files[0];
        if (file) {
            console.log('File selected:', file.name);
            // Validate file inline
            const allowedTypes = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'text/csv'
            ];
            const allowedExtensions = ['.xlsx', '.csv'];
            const fileExtension = '.' + file.name.split('.').pop().toLowerCase();

            if (!allowedTypes.includes(file.type) && !allowedExtensions.includes(fileExtension)) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Please select a valid Excel (.xlsx) or CSV file.'
                });
                return;
            }

            if (file.size > 10 * 1024 * 1024) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'File size must be less than 10MB.'
                });
                return;
            }

            // Display file info inline
            document.getElementById('uploadContent').classList.add('hidden');
            document.getElementById('fileInfo').classList.remove('hidden');
            document.getElementById('fileName').textContent = file.name;

            // Format file size inline
            const bytes = file.size;
            if (bytes === 0) {
                document.getElementById('fileSize').textContent = '0 Bytes';
            } else {
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                const size = parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
                document.getElementById('fileSize').textContent = size;
            }
        }
    });

    // Upload area click
    uploadArea.addEventListener('click', function(e) {
        console.log('Upload area clicked');
        e.preventDefault();
        e.stopPropagation();
        console.log('Triggering file input click');
        fileInput.click();
    });

    // Also make sure the upload content is clickable
    const uploadContent = document.getElementById('uploadContent');
    if (uploadContent) {
        uploadContent.addEventListener('click', function(e) {
            console.log('Upload content clicked');
            e.preventDefault();
            e.stopPropagation();
            fileInput.click();
        });
    }

    // Drag and drop
    uploadArea.addEventListener('dragover', function(e) {
        console.log('Drag over');
        e.preventDefault();
        this.classList.add('dragover');
    });

    uploadArea.addEventListener('dragleave', function(e) {
        console.log('Drag leave');
        e.preventDefault();
        this.classList.remove('dragover');
    });

    uploadArea.addEventListener('drop', function(e) {
        console.log('File dropped');
        e.preventDefault();
        this.classList.remove('dragover');

        const files = e.dataTransfer.files;
        if (files.length > 0) {
            const file = files[0];
            console.log('File dropped:', file.name);
            fileInput.files = files;
            fileInput.dispatchEvent(new Event('change'));
        }
    });

    // Remove file
    const removeFileBtn = document.getElementById('removeFile');
    if (removeFileBtn) {
        removeFileBtn.addEventListener('click', function(e) {
            e.preventDefault();
            fileInput.value = '';
            document.getElementById('fileInfo').classList.add('hidden');
            document.getElementById('uploadContent').classList.remove('hidden');
        });
    }

    // Form submission
    uploadForm.addEventListener('submit', function(e) {
        console.log('Form submitted');
        e.preventDefault();

        if (document.getElementById('period').value == '0') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Please select a payroll period.'
            });
            return;
        }

        if (!document.getElementById('fileInput').files[0]) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Please select a file to upload.'
            });
            return;
        }

        // Show progress inline
        document.getElementById('progressSection').classList.remove('hidden');
        document.getElementById('uploadBtn').disabled = true;
        document.getElementById('progressBar').style.width = '0%';
        document.getElementById('progressPercent').textContent = '0%';
        document.getElementById('progressText').textContent = 'Preparing upload...';
        document.getElementById('statusMessages').innerHTML = '';
        document.getElementById('uploadResults').classList.add('hidden');
        document.getElementById('errorResults').classList.add('hidden');

        const formData = new FormData(e.target);

        fetch('excel_import/import_office.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.ok) {
                    return response.text();
                } else {
                    throw new Error('Upload failed');
                }
            })
            .then(result => {
                console.log('Upload response:', result);

                // Success inline
                document.getElementById('progressBar').style.width = '100%';
                document.getElementById('progressPercent').textContent = '100%';
                document.getElementById('progressText').textContent =
                    'Upload completed successfully!';

                // Extract information from the response
                const infoMatch = result.match(
                    /parent\.document\.getElementById\("information"\)\.innerHTML="([^"]+)"/);
                const messageMatch = result.match(
                    /parent\.document\.getElementById\("message"\)\.innerHTML="([^"]+)"/);

                const infoText = infoMatch ? infoMatch[1] : 'File processed successfully';
                const messageText = messageMatch ? messageMatch[1] :
                    'Import completed successfully';

                const messageDiv = document.createElement('div');
                messageDiv.className = 'flex items-center text-sm';
                messageDiv.innerHTML =
                    '<i class="fas fa-check-circle text-green-500 mr-2"></i><span>' + messageText +
                    '</span>';
                document.getElementById('statusMessages').appendChild(messageDiv);

                // Add detailed information if available
                if (infoText && infoText !== 'File processed successfully') {
                    const infoDiv = document.createElement('div');
                    infoDiv.className = 'flex items-start text-sm mt-2';
                    infoDiv.innerHTML =
                        '<i class="fas fa-info-circle text-blue-500 mr-2 mt-1"></i><span class="text-gray-700">' +
                        infoText + '</span>';
                    document.getElementById('statusMessages').appendChild(infoDiv);
                }

                document.getElementById('uploadResults').classList.remove('hidden');
                document.getElementById('successMessage').textContent =
                    'Your file has been uploaded and processed successfully.';

                // Reset form after success
                setTimeout(() => {
                    document.getElementById('uploadForm').reset();
                    document.getElementById('fileInput').value = '';
                    document.getElementById('fileInfo').classList.add('hidden');
                    document.getElementById('uploadContent').classList.remove('hidden');
                    document.getElementById('progressSection').classList.add('hidden');
                    document.getElementById('uploadBtn').disabled = false;
                }, 5000); // Increased timeout to show results longer
            })
            .catch(error => {
                console.error('Upload error:', error);

                // Error inline
                document.getElementById('progressBar').style.width = '0%';
                document.getElementById('progressPercent').textContent = '0%';
                document.getElementById('progressText').textContent = 'Upload failed';

                const messageDiv = document.createElement('div');
                messageDiv.className = 'flex items-center text-sm';
                messageDiv.innerHTML =
                    '<i class="fas fa-exclamation-circle text-red-500 mr-2"></i><span>An error occurred during upload.</span>';
                document.getElementById('statusMessages').appendChild(messageDiv);

                document.getElementById('errorResults').classList.remove('hidden');
                document.getElementById('errorMessage').textContent =
                    'An error occurred during upload.';
                document.getElementById('uploadBtn').disabled = false;
            });
    });

    // Reset form
    const resetFormBtn = document.getElementById('resetForm');
    if (resetFormBtn) {
        resetFormBtn.addEventListener('click', function(e) {
            e.preventDefault();
            uploadForm.reset();
            fileInput.value = '';
            document.getElementById('fileInfo').classList.add('hidden');
            document.getElementById('uploadContent').classList.remove('hidden');
            document.getElementById('progressSection').classList.add('hidden');
            document.getElementById('uploadBtn').disabled = false;
        });
    }

    console.log('Upload functionality initialized successfully!');
}, 100); // Small delay to ensure everything is loaded
</script>