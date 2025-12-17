<?php
session_start();
require_once('Connections/coop.php');

// Check if user is logged in
if (!isset($_SESSION['SESS_FIRST_NAME'])) {
    header("Location: login.php");
    exit();
}

// Get uploaded files history
$history_query = "SELECT bsf.*, tbp.PayrollPeriod, tbp.PhysicalYear, tbp.PhysicalMonth 
                 FROM bank_statement_files bsf 
                 LEFT JOIN tbpayrollperiods tbp ON bsf.period_id = tbp.id 
                 ORDER BY bsf.upload_date DESC";
$history_result = mysqli_query($coop, $history_query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Statement History</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/font-awesome.min.css" rel="stylesheet">
    <link href="datatable/datatables.min.css" rel="stylesheet">
    <style>
    .file-status {
        padding: 5px 10px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: bold;
    }

    .status-processed {
        background-color: #d4edda;
        color: #155724;
    }

    .status-pending {
        background-color: #fff3cd;
        color: #856404;
    }

    .file-info {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 5px;
        padding: 15px;
        margin: 10px 0;
    }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fa fa-history"></i> Bank Statement Upload History</h2>
                    <a href="bank_statement_upload.php" class="btn btn-primary">
                        <i class="fa fa-plus"></i> Upload New Statement
                    </a>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5><i class="fa fa-list"></i> Uploaded Files</h5>
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($history_result) > 0) { ?>
                        <div class="table-responsive">
                            <table class="table table-striped" id="historyTable">
                                <thead>
                                    <tr>
                                        <th>File Name</th>
                                        <th>Period</th>
                                        <th>Uploaded By</th>
                                        <th>Upload Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($file = mysqli_fetch_assoc($history_result)) { ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($file['filename']); ?></strong>
                                            <br>
                                            <small class="text-muted">Hash:
                                                <?php echo substr($file['file_hash'], 0, 8) . '...'; ?></small>
                                        </td>
                                        <td>
                                            <?php if ($file['PayrollPeriod']) { ?>
                                            <?php echo htmlspecialchars($file['PayrollPeriod']); ?>
                                            <br>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($file['PhysicalMonth'] . ' ' . $file['PhysicalYear']); ?>
                                            </small>
                                            <?php } else { ?>
                                            <span class="text-muted">Period not found</span>
                                            <?php } ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($file['uploaded_by']); ?></td>
                                        <td><?php echo date('M d, Y H:i', strtotime($file['upload_date'])); ?></td>
                                        <td>
                                            <span
                                                class="file-status <?php echo $file['processed'] ? 'status-processed' : 'status-pending'; ?>">
                                                <?php echo $file['processed'] ? 'Processed' : 'Pending'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-info"
                                                    onclick="viewFileDetails(<?php echo $file['id']; ?>)">
                                                    <i class="fa fa-eye"></i> View
                                                </button>
                                                <button type="button" class="btn btn-sm btn-success"
                                                    onclick="downloadFile('<?php echo $file['file_path']; ?>', '<?php echo $file['filename']; ?>')">
                                                    <i class="fa fa-download"></i> Download
                                                </button>
                                                <?php if (!$file['processed']) { ?>
                                                <button type="button" class="btn btn-sm btn-warning"
                                                    onclick="reprocessFile(<?php echo $file['id']; ?>)">
                                                    <i class="fa fa-refresh"></i> Reprocess
                                                </button>
                                                <?php } ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                        <?php } else { ?>
                        <div class="text-center py-5">
                            <i class="fa fa-inbox fa-3x text-muted mb-3"></i>
                            <h5>No bank statements uploaded yet</h5>
                            <p class="text-muted">Upload your first bank statement to get started.</p>
                            <a href="bank_statement_upload.php" class="btn btn-primary">
                                <i class="fa fa-upload"></i> Upload First Statement
                            </a>
                        </div>
                        <?php } ?>
                    </div>
                </div>

                <!-- Statistics Card -->
                <div class="row mt-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="mb-0">
                                            <?php 
                                            $total_query = "SELECT COUNT(*) as total FROM bank_statement_files";
                                            $total_result = mysqli_query($coop, $total_query);
                                            $total = mysqli_fetch_assoc($total_result)['total'];
                                            echo $total;
                                            ?>
                                        </h4>
                                        <p class="mb-0">Total Files</p>
                                    </div>
                                    <div>
                                        <i class="fa fa-file fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="mb-0">
                                            <?php 
                                            $processed_query = "SELECT COUNT(*) as processed FROM bank_statement_files WHERE processed = 1";
                                            $processed_result = mysqli_query($coop, $processed_query);
                                            $processed = mysqli_fetch_assoc($processed_result)['processed'];
                                            echo $processed;
                                            ?>
                                        </h4>
                                        <p class="mb-0">Processed</p>
                                    </div>
                                    <div>
                                        <i class="fa fa-check fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="mb-0">
                                            <?php 
                                            $pending_query = "SELECT COUNT(*) as pending FROM bank_statement_files WHERE processed = 0";
                                            $pending_result = mysqli_query($coop, $pending_query);
                                            $pending = mysqli_fetch_assoc($pending_result)['pending'];
                                            echo $pending;
                                            ?>
                                        </h4>
                                        <p class="mb-0">Pending</p>
                                    </div>
                                    <div>
                                        <i class="fa fa-clock-o fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="mb-0">
                                            <?php 
                                            $unmatched_query = "SELECT COUNT(*) as unmatched FROM unmatched_transactions WHERE resolved = 0";
                                            $unmatched_result = mysqli_query($coop, $unmatched_query);
                                            $unmatched = mysqli_fetch_assoc($unmatched_result)['unmatched'];
                                            echo $unmatched;
                                            ?>
                                        </h4>
                                        <p class="mb-0">Unmatched</p>
                                    </div>
                                    <div>
                                        <i class="fa fa-question fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- File Details Modal -->
    <div class="modal fade" id="fileDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">File Details</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="fileDetailsContent"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="datatable/datatables.min.js"></script>
    <script>
    $(document).ready(function() {
        $('#historyTable').DataTable({
            "order": [
                [3, "desc"]
            ], // Sort by upload date descending
            "pageLength": 25,
            "responsive": true
        });
    });

    function viewFileDetails(fileId) {
        fetch('bank_statement_processor.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'get_file_details',
                    file_id: fileId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayFileDetails(data.file_details);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while fetching file details.');
            });
    }

    function displayFileDetails(fileDetails) {
        const modalContent = document.getElementById('fileDetailsContent');
        let html = `
                <div class="file-info">
                    <h6>File Information</h6>
                    <p><strong>Name:</strong> ${fileDetails.filename}</p>
                    <p><strong>Upload Date:</strong> ${fileDetails.upload_date}</p>
                    <p><strong>Uploaded By:</strong> ${fileDetails.uploaded_by}</p>
                    <p><strong>File Hash:</strong> ${fileDetails.file_hash}</p>
                    <p><strong>Status:</strong> ${fileDetails.processed ? 'Processed' : 'Pending'}</p>
                </div>
            `;

        if (fileDetails.transactions) {
            html += `
                    <div class="mt-3">
                        <h6>Extracted Transactions</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Amount</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                `;

            fileDetails.transactions.forEach(transaction => {
                html += `
                        <tr>
                            <td>${transaction.name}</td>
                            <td class="${transaction.type === 'credit' ? 'text-success' : 'text-danger'}">
                                ${transaction.type === 'credit' ? '+' : '-'}₦${transaction.amount.toLocaleString()}
                            </td>
                            <td><span class="badge badge-${transaction.type === 'credit' ? 'success' : 'danger'}">${transaction.type}</span></td>
                            <td>${transaction.matched ? '<span class="text-success">Matched</span>' : '<span class="text-warning">Unmatched</span>'}</td>
                        </tr>
                    `;
            });

            html += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;
        }

        modalContent.innerHTML = html;
        $('#fileDetailsModal').modal('show');
    }

    function downloadFile(filePath, fileName) {
        // Create a temporary link to download the file
        const link = document.createElement('a');
        link.href = filePath;
        link.download = fileName;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    function reprocessFile(fileId) {
        if (confirm('Are you sure you want to reprocess this file? This will re-analyze the content with OpenAI.')) {
            fetch('bank_statement_processor.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'reprocess_file',
                        file_id: fileId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('File reprocessed successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while reprocessing the file.');
                });
        }
    }
    </script>
</body>

</html>