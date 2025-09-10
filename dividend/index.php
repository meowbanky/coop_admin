<?php
// db_connection.php should contain your database connection details
require_once 'db_connection.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dividend Calculator</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* Custom styles for the loading overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        .loading-spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">
<div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold mb-6 text-center text-gray-800">Dividend Calculator</h1>

    <!-- Loading Overlay -->
    <div id="loading" class="loading-overlay hidden">
        <div class="loading-spinner"></div>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-md mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="payroll_period_from" class="block text-sm font-medium text-gray-700">From Payroll Period</label>
                <select id="payroll_period_from" name="payroll_period_from" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    <option value="">Select From Period</option>
                    <?php
                    $periods = $pdo->query("SELECT id, PayrollPeriod FROM tbpayrollperiods ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($periods as $period) {
                        echo "<option value='{$period['id']}'>{$period['PayrollPeriod']}</option>";
                    }
                    ?>
                </select>
            </div>
            <div>
                <label for="payroll_period_to" class="block text-sm font-medium text-gray-700">To Payroll Period</label>
                <select id="payroll_period_to" name="payroll_period_to" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    <option value="">Select To Period</option>
                    <?php
                    foreach ($periods as $period) {
                        echo "<option value='{$period['id']}'>{$period['PayrollPeriod']}</option>";
                    }
                    ?>
                </select>
            </div>
        </div>
        <button id="calculate" class="mt-4 w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-opacity-50">Calculate Dividends</button>
    </div>

    <div id="results" class="bg-white p-6 rounded-lg shadow-md hidden">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-2xl font-semibold text-gray-800">Dividend Results</h2>
            <button id="exportExcel" class="bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-50">Export to Excel</button>
        </div>
        <div class="overflow-x-auto">
            <table id="dividendTable" class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Coop ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Savings</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Shares</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Interest</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dividend</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bank</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account No</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bank Code</th>
                </tr>
                </thead>
                <tbody id="resultsBody" class="bg-white divide-y divide-gray-200"></tbody>
            </table>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Function to format numbers with thousand separators
        function formatNumber(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }

        // Show loading overlay
        function showLoading() {
            $('#loading').removeClass('hidden');
        }

        // Hide loading overlay
        function hideLoading() {
            $('#loading').addClass('hidden');
        }

        // Export table to Excel
        function exportToExcel() {
            const table = document.getElementById('dividendTable');
            const rows = table.querySelectorAll('tr');
            let csv = [];

            // Iterate through table rows
            for (let i = 0; i < rows.length; i++) {
                const row = [];
                const cols = rows[i].querySelectorAll('th, td');
                for (let j = 0; j < cols.length; j++) {
                    let data = cols[j].innerText.replace(/"/g, '""'); // Escape double quotes
                    row.push('"' + data + '"'); // Wrap in quotes to handle commas
                }
                csv.push(row.join(','));
            }

            // Create CSV file content
            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', 'dividend_results.csv');
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        $('#calculate').click(function() {
            const periodFrom = $('#payroll_period_from').val();
            const periodTo = $('#payroll_period_to').val();

            if (!periodFrom || !periodTo) {
                alert('Please select both payroll periods');
                return;
            }

            if (parseInt(periodFrom) > parseInt(periodTo)) {
                alert('The "From" period must be greater than or equal to the "To" period');
                return;
            }

            showLoading(); // Show loading when request starts

            $.ajax({
                url: 'calculate_dividends.php',
                method: 'POST',
                data: {
                    period_from: periodFrom,
                    period_to: periodTo
                },
                dataType: 'json',
                success: function(response) {
                    hideLoading(); // Hide loading on success
                    if (response.success) {
                        let html = '';
                        $.each(response.data, function(index, row) {
                            const savings = parseFloat(row.Savings);
                            const shares = parseFloat(row.Shares);
                            const interest = parseFloat(row.Interest);
                            const dividend = savings + shares + interest;

                            html += `
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">${row.COOPID}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">${row.FullName}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">${formatNumber(savings.toFixed(2))}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">${formatNumber(shares.toFixed(2))}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">${formatNumber(interest.toFixed(2))}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">${formatNumber(dividend.toFixed(2))}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">${row.Bank_Name}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">${row.AccountNo}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">${row.bank_code}</td>
                                </tr>
                            `;
                        });
                        $('#resultsBody').html(html);
                        $('#results').removeClass('hidden');
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    hideLoading(); // Hide loading on error
                    alert('An error occurred while calculating dividends');
                }
            });
        });

        // Export button click handler
        $('#exportExcel').click(function() {
            if ($('#results').hasClass('hidden')) {
                alert('Please calculate dividends first to export data.');
                return;
            }
            exportToExcel();
        });
    });
</script>
</body>
</html>