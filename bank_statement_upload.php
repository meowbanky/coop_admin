<?php
session_start();
require_once('Connections/coop.php');
require_once('config/EnvConfig.php');

// Check if user is logged in
if (!isset($_SESSION['SESS_FIRST_NAME'])) {
    header("Location: login.php");
    exit();
}

// Check if OpenAI key is configured
$openai_configured = EnvConfig::hasOpenAIKey();

// Get payroll periods for dropdown
$periods_query = "SELECT id, PayrollPeriod, PhysicalYear, PhysicalMonth FROM tbpayrollperiods ORDER BY id DESC";
$periods_result = mysqli_query($coop, $periods_query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Statement Upload & Analysis</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/font-awesome.min.css" rel="stylesheet">
    <link href="datatable/datatables.min.css" rel="stylesheet">
    <style>
    .upload-area {
        border: 2px dashed #ccc;
        border-radius: 10px;
        padding: 40px;
        text-align: center;
        background: #f9f9f9;
        transition: all 0.3s ease;
    }

    .upload-area:hover {
        border-color: #007bff;
        background: #f0f8ff;
    }

    .upload-area.dragover {
        border-color: #28a745;
        background: #f0fff0;
    }

    .file-item {
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 10px;
        margin: 5px 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .file-item .file-info {
        flex: 1;
    }

    .file-item .file-actions {
        display: flex;
        gap: 10px;
    }

    .progress {
        height: 20px;
        margin-top: 10px;
    }

    .analysis-result {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 5px;
        padding: 15px;
        margin: 10px 0;
    }

    .match-item {
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 10px;
        margin: 5px 0;
    }

    .match-item.matched {
        border-left: 4px solid #28a745;
    }

    .match-item.unmatched {
        border-left: 4px solid #dc3545;
    }

    .manual-match {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 5px;
        padding: 10px;
        margin: 5px 0;
    }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fa fa-upload"></i> Bank Statement Upload & Analysis</h2>
                    <div>
                        <a href="test_bank_statement_system.php" class="btn btn-warning me-2" target="_blank">
                            <i class="fa fa-cog"></i> System Test
                        </a>
                        <a href="bank_statement_history.php" class="btn btn-info">
                            <i class="fa fa-history"></i> View History
                        </a>
                    </div>
                </div>

                <!-- Upload Section -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fa fa-file-upload"></i> Upload Bank Statements</h5>
                    </div>
                    <div class="card-body">
                        <form id="uploadForm" enctype="multipart/form-data">
                            <?php if (!$openai_configured): ?>
                            <div class="alert alert-warning">
                                <i class="fa fa-exclamation-triangle"></i>
                                <strong>OpenAI API Key Not Configured</strong><br>
                                Please add your OpenAI API key to the <code>config.env</code> file to use this feature.
                                <br><br>
                                                                    <a href="config_manager.php" class="btn btn-sm btn-outline-warning">
                                        <i class="fa fa-cog"></i> Configure API Key
                                    </a>
                            </div>
                            <?php endif; ?>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="period">Select Period:</label>
                                        <select class="form-control" id="period" name="period" required
                                            <?php echo !$openai_configured ? 'disabled' : ''; ?>>
                                            <option value="">Select a period...</option>
                                            <?php while ($period = mysqli_fetch_assoc($periods_result)) { ?>
                                            <option value="<?php echo $period['id']; ?>">
                                                <?php echo $period['PayrollPeriod'] . ' (' . $period['PhysicalMonth'] . ' ' . $period['PhysicalYear'] . ')'; ?>
                                            </option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>OpenAI API Status:</label>
                                        <div class="form-control-plaintext">
                                            <?php if ($openai_configured): ?>
                                            <span class="text-success">
                                                <i class="fa fa-check-circle"></i> API Key Configured
                                            </span>
                                            <?php else: ?>
                                            <span class="text-danger">
                                                <i class="fa fa-times-circle"></i> API Key Not Configured
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="upload-area" id="uploadArea">
                                <i class="fa fa-cloud-upload fa-3x text-muted mb-3"></i>
                                <h5>Drag & Drop files here or click to browse</h5>
                                <p class="text-muted">Supported formats: PDF, Excel (.xlsx, .xls), Images (.jpg, .jpeg,
                                    .png)</p>
                                <input type="file" id="fileInput" name="files[]" multiple
                                    accept=".pdf,.xlsx,.xls,.jpg,.jpeg,.png" style="display: none;">
                                <button type="button" class="btn btn-primary"
                                    onclick="document.getElementById('fileInput').click()">
                                    <i class="fa fa-folder-open"></i> Browse Files
                                </button>
                            </div>

                            <div id="fileList" class="mt-3"></div>

                            <div class="mt-3">
                                <button type="submit" class="btn btn-success" id="uploadBtn"
                                    <?php echo !$openai_configured ? 'disabled' : ''; ?>>
                                    <i class="fa fa-upload"></i> Upload & Analyze
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="clearFiles()">
                                    <i class="fa fa-trash"></i> Clear Files
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Analysis Results -->
                <div class="card mt-4" id="analysisCard" style="display: none;">
                    <div class="card-header">
                        <h5><i class="fa fa-chart-bar"></i> Analysis Results</h5>
                    </div>
                    <div class="card-body">
                        <div id="analysisResults"></div>
                    </div>
                </div>

                <!-- Manual Matching Section -->
                <div class="card mt-4" id="manualMatchCard" style="display: none;">
                    <div class="card-header">
                        <h5><i class="fa fa-user-edit"></i> Manual Name Matching</h5>
                    </div>
                    <div class="card-body">
                        <div id="manualMatchResults"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Manual Match Modal -->
    <div class="modal fade" id="manualMatchModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Manual Name Matching</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="manualMatchContent"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveManualMatch">Save Match</button>
                </div>
            </div>
        </div>
    </div>

    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="datatable/datatables.min.js"></script>
    <script>
    let uploadedFiles = [];
    let analysisData = [];
    let currentManualMatch = null;

    // Drag and drop functionality
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('fileInput');

    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.classList.add('dragover');
    });

    uploadArea.addEventListener('dragleave', () => {
        uploadArea.classList.remove('dragover');
    });

    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        const files = e.dataTransfer.files;
        handleFiles(files);
    });

    fileInput.addEventListener('change', (e) => {
        handleFiles(e.target.files);
    });

    function handleFiles(files) {
        Array.from(files).forEach(file => {
            if (isValidFile(file)) {
                uploadedFiles.push(file);
                displayFile(file);
            } else {
                alert(`Invalid file type: ${file.name}. Please upload PDF, Excel, or image files only.`);
            }
        });
    }

    function isValidFile(file) {
        const validTypes = [
            'application/pdf',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel',
            'image/jpeg',
            'image/jpg',
            'image/png'
        ];
        return validTypes.includes(file.type);
    }

    function displayFile(file) {
        const fileList = document.getElementById('fileList');
        const fileItem = document.createElement('div');
        fileItem.className = 'file-item';
        fileItem.innerHTML = `
                <div class="file-info">
                    <strong>${file.name}</strong> (${formatFileSize(file.size)})
                </div>
                <div class="file-actions">
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeFile('${file.name}')">
                        <i class="fa fa-trash"></i>
                    </button>
                </div>
            `;
        fileList.appendChild(fileItem);
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function removeFile(fileName) {
        uploadedFiles = uploadedFiles.filter(file => file.name !== fileName);
        updateFileList();
    }

    function updateFileList() {
        const fileList = document.getElementById('fileList');
        fileList.innerHTML = '';
        uploadedFiles.forEach(file => displayFile(file));
    }

    function clearFiles() {
        uploadedFiles = [];
        updateFileList();
        document.getElementById('fileInput').value = '';
    }

    // Form submission
    document.getElementById('uploadForm').addEventListener('submit', function(e) {
        e.preventDefault();

        if (uploadedFiles.length === 0) {
            alert('Please select at least one file to upload.');
            return;
        }

        const formData = new FormData();
        formData.append('period', document.getElementById('period').value);

        uploadedFiles.forEach(file => {
            formData.append('files[]', file);
        });

        uploadAndAnalyze(formData);
    });

    function uploadAndAnalyze(formData) {
        const uploadBtn = document.getElementById('uploadBtn');
        uploadBtn.disabled = true;
        uploadBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Processing...';

        fetch('bank_statement_processor.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                uploadBtn.disabled = false;
                uploadBtn.innerHTML = '<i class="fa fa-upload"></i> Upload & Analyze';

                if (data.success) {
                    analysisData = data.data;
                    displayAnalysisResults(data.data);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                uploadBtn.disabled = false;
                uploadBtn.innerHTML = '<i class="fa fa-upload"></i> Upload & Analyze';
                console.error('Error:', error);
                alert('An error occurred during processing.');
            });
    }

    function displayAnalysisResults(data) {
        const resultsDiv = document.getElementById('analysisResults');
        const analysisCard = document.getElementById('analysisCard');
        const manualMatchCard = document.getElementById('manualMatchCard');

        let html = '<div class="row">';

        // Summary
        html += '<div class="col-12 mb-3">';
        html += '<div class="alert alert-info">';
        html +=
            `<strong>Analysis Summary:</strong> ${data.total_transactions} transactions found, ${data.matched_count} matched, ${data.unmatched_count} unmatched`;
        html += '</div>';
        html += '</div>';

        // Matched transactions
        if (data.matched_transactions.length > 0) {
            html += '<div class="col-12 mb-3">';
            html += '<h6><i class="fa fa-check-circle text-success"></i> Matched Transactions</h6>';
            html += '<div class="table-responsive">';
            html += '<table class="table table-sm table-striped">';
            html +=
                '<thead><tr><th>Name</th><th>Coop ID</th><th>Amount</th><th>Type</th><th>Action</th></tr></thead><tbody>';

            data.matched_transactions.forEach(transaction => {
                html += `<tr>
                        <td>${transaction.name}</td>
                        <td>${transaction.coop_id}</td>
                        <td class="${transaction.type === 'credit' ? 'text-success' : 'text-danger'}">
                            ${transaction.type === 'credit' ? '+' : '-'}₦${transaction.amount.toLocaleString()}
                        </td>
                        <td><span class="badge badge-${transaction.type === 'credit' ? 'success' : 'danger'}">${transaction.type}</span></td>
                        <td>
                            <button class="btn btn-sm btn-success" onclick="insertTransaction('${transaction.coop_id}', ${transaction.amount}, '${transaction.type}', ${data.period})">
                                <i class="fa fa-save"></i> Insert
                            </button>
                        </td>
                    </tr>`;
            });

            html += '</tbody></table></div></div>';
        }

        // Unmatched transactions
        if (data.unmatched_transactions.length > 0) {
            html += '<div class="col-12 mb-3">';
            html += '<h6><i class="fa fa-exclamation-triangle text-warning"></i> Unmatched Transactions</h6>';
            html += '<div class="table-responsive">';
            html += '<table class="table table-sm table-striped">';
            html += '<thead><tr><th>Name</th><th>Amount</th><th>Type</th><th>Action</th></tr></thead><tbody>';

            data.unmatched_transactions.forEach(transaction => {
                html += `<tr>
                        <td>${transaction.name}</td>
                        <td class="${transaction.type === 'credit' ? 'text-success' : 'text-danger'}">
                            ${transaction.type === 'credit' ? '+' : '-'}₦${transaction.amount.toLocaleString()}
                        </td>
                        <td><span class="badge badge-${transaction.type === 'credit' ? 'success' : 'danger'}">${transaction.type}</span></td>
                        <td>
                            <button class="btn btn-sm btn-warning" onclick="showManualMatch('${transaction.name}', ${transaction.amount}, '${transaction.type}')">
                                <i class="fa fa-user-edit"></i> Manual Match
                            </button>
                        </td>
                    </tr>`;
            });

            html += '</tbody></table></div></div>';
        }

        html += '</div>';

        resultsDiv.innerHTML = html;
        analysisCard.style.display = 'block';

        if (data.unmatched_transactions.length > 0) {
            manualMatchCard.style.display = 'block';
        }
    }

    function insertTransaction(coopId, amount, type, period) {
        fetch('bank_statement_processor.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'insert_transaction',
                    coop_id: coopId,
                    amount: amount,
                    type: type,
                    period: period
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Transaction inserted successfully!');
                    // Refresh the analysis results
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while inserting the transaction.');
            });
    }

    function showManualMatch(name, amount, type) {
        currentManualMatch = {
            name,
            amount,
            type
        };

        fetch('bank_statement_processor.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'search_employees',
                    search_term: name
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayManualMatchModal(name, amount, type, data.employees);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while searching for employees.');
            });
    }

    function displayManualMatchModal(name, amount, type, employees) {
        const modalContent = document.getElementById('manualMatchContent');
        let html = `
                <div class="mb-3">
                    <strong>Transaction:</strong> ${name} - ${type === 'credit' ? '+' : '-'}₦${amount.toLocaleString()}
                </div>
                <div class="mb-3">
                    <label>Select Employee:</label>
                    <select class="form-control" id="manualCoopId">
                        <option value="">Select an employee...</option>
            `;

        employees.forEach(employee => {
            const fullName = `${employee.FirstName} ${employee.MiddleName} ${employee.LastName}`.trim();
            html += `<option value="${employee.CoopID}">${fullName} (${employee.CoopID})</option>`;
        });

        html += `
                    </select>
                </div>
            `;

        modalContent.innerHTML = html;
        $('#manualMatchModal').modal('show');
    }

    document.getElementById('saveManualMatch').addEventListener('click', function() {
        const coopId = document.getElementById('manualCoopId').value;
        if (!coopId) {
            alert('Please select an employee.');
            return;
        }

        insertTransaction(coopId, currentManualMatch.amount, currentManualMatch.type, document.getElementById(
            'period').value);
        $('#manualMatchModal').modal('hide');
    });
    </script>
</body>

</html>