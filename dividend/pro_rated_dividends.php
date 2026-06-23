<?php
require_once 'db_connection.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pro-rated Dividend Calculator</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
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
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Pro-rated Dividend Calculator</h1>
        <a href="index.php" class="text-indigo-600 hover:text-indigo-800 font-medium">← Back to Main Calculator</a>
    </div>

    <!-- Loading Overlay -->
    <div id="loading" class="loading-overlay hidden">
        <div class="loading-spinner"></div>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-md mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label for="payroll_period_start" class="block text-sm font-medium text-gray-700">Dividend Year Start Period</label>
                <select id="payroll_period_start" name="payroll_period_start" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    <?php
                    $periods = $pdo->query("SELECT id, PayrollPeriod FROM tbpayrollperiods ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($periods as $period) {
                        $selected = ($period['PayrollPeriod'] == 'Jan 2024' || $period['id'] == 185) ? 'selected' : '';
                        echo "<option value='{$period['id']}' $selected>{$period['PayrollPeriod']}</option>";
                    }
                    ?>
                </select>
            </div>
            <div>
                <label for="payroll_period_end" class="block text-sm font-medium text-gray-700">Dividend Year End Period</label>
                <select id="payroll_period_end" name="payroll_period_end" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    <?php
                    foreach ($periods as $period) {
                        $selected = ($period['PayrollPeriod'] == 'Dec 2024' || $period['id'] == 196) ? 'selected' : '';
                        echo "<option value='{$period['id']}' $selected>{$period['PayrollPeriod']}</option>";
                    }
                    ?>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label for="shares_savings_perc" class="block text-sm font-medium text-gray-700">Shares & Savings Percentage</label>
                <input type="number" id="shares_savings_perc" name="shares_savings_perc" step="0.001" value="0.017" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
            </div>
            <div>
                <label for="interest_perc" class="block text-sm font-medium text-gray-700">Interest Percentage</label>
                <input type="number" id="interest_perc" name="interest_perc" step="0.001" value="0.245" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
            </div>
        </div>

        <div class="mb-4">
            <label for="member_ids" class="block text-sm font-medium text-gray-700">Member COOP IDs (one per line or comma-separated)</label>
            <p class="text-xs text-gray-500 mb-1">Format: `COOPID` or `COOPID:MonthName/Number` (e.g., `COOP-00045:March` or `1234:6` for June)</p>
            <textarea id="member_ids" name="member_ids" rows="5" placeholder="e.g. 1001, 1002:March, 1003:6" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 font-mono text-sm"></textarea>
        </div>

        <div class="mb-6">
            <div class="flex justify-between items-center mb-1">
                <label for="member_csv" class="block text-sm font-medium text-gray-700">Upload Member CSV</label>
                <button type="button" id="clear_members" class="text-xs text-red-600 hover:text-red-800 font-medium">Clear All</button>
            </div>
            <input type="file" id="member_csv" accept=".csv" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
            <p class="mt-1 text-xs text-gray-500">CSV should contain COOP IDs in the first column. (Headers are automatically skipped)</p>
        </div>

        <button id="calculate" class="w-full bg-indigo-600 text-white py-3 px-4 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-opacity-50 text-lg font-bold">Calculate Pro-rated Dividends</button>
    </div>

    <div id="results" class="bg-white p-6 rounded-lg shadow-md hidden">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-2xl font-semibold text-gray-800">Pro-rated Results</h2>
            <button id="exportExcel" class="bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-50">Export to Excel</button>
        </div>
        <div class="overflow-x-auto">
            <table id="proRatedTable" class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Coop ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bank Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account No</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bank Code</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Period</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Months</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Savings</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Shares</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Interest</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Full Div</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pro-rated Div</th>
                </tr>
                </thead>
                <tbody id="resultsBody" class="bg-white divide-y divide-gray-200"></tbody>
            </table>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        function formatNumber(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }

        function showLoading() { $('#loading').removeClass('hidden'); }
        function hideLoading() { $('#loading').addClass('hidden'); }

        const monthMap = {
            'jan': 1, 'january': 1, '01': 1, '1': 1,
            'feb': 2, 'february': 2, '02': 2, '2': 2,
            'mar': 3, 'march': 3, '03': 3, '3': 3,
            'apr': 4, 'april': 4, '04': 4, '4': 4,
            'may': 5, '05': 5, '5': 5,
            'jun': 6, 'june': 6, '06': 6, '6': 6,
            'jul': 7, 'july': 7, '07': 7, '7': 7,
            'aug': 8, 'august': 8, '08': 8, '8': 8,
            'sep': 9, 'september': 9, 'sept': 9, '09': 9, '9': 9,
            'oct': 10, 'october': 10, '10': 10,
            'nov': 11, 'november': 11, '11': 11,
            'dec': 12, 'december': 12, '12': 12
        };

        $('#member_csv').change(function(e) {
            const file = e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function(event) {
                const text = event.target.result;
                const rows = text.split(/\r?\n/).filter(line => line.trim() !== '');
                const results = [];
                rows.forEach((row, index) => {
                    const columns = row.split(',').map(c => c.trim().replace(/"/g, ''));
                    if (columns.length < 1) return;

                    let rawMonth = '';
                    let rawId = '';

                    // Check if it's Month, ID or ID, Month based on the image (Month is 1st)
                    if (columns.length >= 2) {
                        // If first col looks like a month, it's Month, ID
                        const col0Lower = columns[0].toLowerCase();
                        if (monthMap[col0Lower] || col0Lower === 'month') {
                            rawMonth = columns[0];
                            rawId = columns[1];
                        } else {
                            rawId = columns[0];
                            rawMonth = columns[1];
                        }
                    } else {
                        rawId = columns[0];
                    }
                    
                    // Skip header row
                    if (index === 0 && (rawId.toLowerCase().includes('id') || rawMonth.toLowerCase().includes('month'))) {
                        return;
                    }
                    
                    if (rawId) {
                        const monthNum = monthMap[rawMonth.toLowerCase()] || '';
                        results.push(monthNum ? `${rawId}:${monthNum}` : rawId);
                    }
                });
                if (results.length > 0) {
                    const currentVal = $('#member_ids').val().trim();
                    const currentArray = currentVal ? currentVal.split(/[,\n]/).map(s => s.trim()) : [];
                    const combinedArray = [...currentArray, ...results];
                    const uniqueResults = [...new Set(combinedArray)].filter(s => s !== '');
                    $('#member_ids').val(uniqueResults.join('\n'));
                    alert(results.length + ' members added from CSV');
                } else {
                    alert('No valid IDs found in the CSV');
                }
                $('#member_csv').val('');
            };
            reader.readAsText(file);
        });

        $('#clear_members').click(function() {
            if (confirm('Are you sure you want to clear all IDs?')) {
                $('#member_ids').val('');
            }
        });

        $('#calculate').click(function() {
            const memberIds = $('#member_ids').val().trim();
            const startPeriod = $('#payroll_period_start').val();
            const endPeriod = $('#payroll_period_end').val();
            const sharesSavingsPerc = $('#shares_savings_perc').val();
            const interestPerc = $('#interest_perc').val();

            if (!memberIds) {
                alert('Please enter at least one Member ID');
                return;
            }

            showLoading();

            $.ajax({
                url: 'calculate_pro_rated_dividends.php',
                method: 'POST',
                data: {
                    member_ids: memberIds,
                    period_start: startPeriod,
                    period_end: endPeriod,
                    shares_savings_perc: sharesSavingsPerc,
                    interest_perc: interestPerc
                },
                dataType: 'json',
                success: function(response) {
                    hideLoading();
                    if (response.success) {
                        let html = '';
                        $.each(response.data, function(index, row) {
                            const savings = parseFloat(row.Savings) || 0;
                            const shares = parseFloat(row.Shares) || 0;
                            const interest = parseFloat(row.Interest) || 0;
                            const coveragePerc = parseFloat(row.CoveragePercentage) || 0;
                            
                            const sharesSavingsPercVal = parseFloat(sharesSavingsPerc) || 0;
                            const interestPercVal = parseFloat(interestPerc) || 0;
                            const fullDividend = ((savings + shares) * sharesSavingsPercVal) + (interest * interestPercVal);
                            const proRatedDividend = fullDividend * (coveragePerc / 100);

                            html += `
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">${row.COOPID}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-700">${row.FullName}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-700">${row.Bank_Name || ''}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-700">${row.AccountNo || ''}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-700">${row.bank_code || ''}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-700">${row.LastPeriodName || row.LastPeriod}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-700">${row.MonthsActive} (${coveragePerc}%)</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-700">${formatNumber(savings.toFixed(2))}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-700">${formatNumber(shares.toFixed(2))}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-700">${formatNumber(interest.toFixed(2))}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-700">${formatNumber(fullDividend.toFixed(2))}</td>
                                    <td class="px-6 py-4 whitespace-nowrap font-bold text-indigo-600">${formatNumber(proRatedDividend.toFixed(2))}</td>
                                </tr>
                            `;
                        });
                        $('#resultsBody').html(html);
                        $('#results').removeClass('hidden');
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function(xhr) {
                    hideLoading();
                    alert('An error occurred: ' + xhr.statusText);
                }
            });
        });

        $('#exportExcel').click(function() {
            const table = document.getElementById('proRatedTable');
            const rows = table.querySelectorAll('tr');
            let csv = [];
            for (let i = 0; i < rows.length; i++) {
                const row = [];
                const cols = rows[i].querySelectorAll('th, td');
                for (let j = 0; j < cols.length; j++) {
                    let data = cols[j].innerText.replace(/"/g, '""');
                    row.push('"' + data + '"');
                }
                csv.push(row.join(','));
            }
            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.setAttribute('href', URL.createObjectURL(blob));
            link.setAttribute('download', 'pro_rated_dividend_results.csv');
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
    });
</script>
</body>
</html>
