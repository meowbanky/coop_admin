<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beneficiary Management - Batch <?php echo htmlspecialchars($_SESSION['Batch'] ?? ''); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/ui-lightness/jquery-ui.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Custom CSS for Autocomplete -->
    <style>
        .ui-autocomplete {
            max-height: 300px;
            overflow-y: auto;
            overflow-x: hidden;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            background: white;
            z-index: 1000;
        }
        
        .ui-autocomplete .ui-menu-item {
            border: none;
            margin: 0;
        }
        
        .ui-autocomplete .ui-menu-item-wrapper {
            padding: 0;
            border: none;
            background: transparent;
        }
        
        .ui-autocomplete .ui-state-active {
            background: #dbeafe;
            border: none;
        }
        
        .ui-autocomplete .ui-state-hover {
            background: #f3f4f6;
            border: none;
        }
        
        /* Loading indicator */
        .ui-autocomplete-loading {
            background: white url('data:image/svg+xml;charset=utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="%23666" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>') no-repeat right center;
            background-size: 20px 20px;
        }
    </style>
</head>

<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <i class="fas fa-users text-blue-600 text-2xl mr-3"></i>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Beneficiary Management</h1>
                        <p class="text-sm text-gray-600">Batch: <span
                                class="font-semibold text-blue-600"><?php echo htmlspecialchars($_SESSION['Batch'] ?? ''); ?></span>
                        </p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="member-account-update.php"
                        class="inline-flex items-center px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition-colors">
                        <i class="fas fa-user-edit mr-2"></i>
                        Update Member Accounts
                    </a>
                    <a href="home.php"
                        class="inline-flex items-center px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700 transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Batches
                    </a>
                    <a href="export.php?BATCH=<?php echo urlencode($_SESSION['Batch'] ?? ''); ?>"
                        class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-download mr-2"></i>
                        Export
                    </a>
                    <div class="text-gray-600 text-sm">
                        <i class="fas fa-user-circle mr-1"></i>
                        <?= htmlspecialchars($_SESSION['complete_name'] ?? 'Admin') ?>
                    </div>
                    <a href="logout.php" 
                       class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors"
                       onclick="return confirm('Are you sure you want to logout?')">
                        <i class="fas fa-sign-out-alt mr-2"></i>
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Standalone Bank Edit Section -->
        <div class="bg-white rounded-lg shadow-sm border mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                    <i class="fas fa-university text-green-600 mr-2"></i>
                    Edit Member Bank Details
                </h2>
                <p class="text-sm text-gray-600 mt-1">Update bank details for any member, even if they're not in the current batch</p>
            </div>
            <div class="p-6">
                <div class="max-w-md">
                    <label for="standalone-member-search" class="block text-sm font-medium text-gray-700 mb-2">
                        Search Member
                    </label>
                    <div class="relative">
                        <input type="text" id="standalone-member-search" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                               placeholder="Type member ID or name..." autocomplete="off">
                        <button type="button" id="clear-standalone-search" 
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <button type="button" id="open-standalone-bank-edit" 
                            class="mt-3 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed"
                            disabled>
                        <i class="fas fa-university mr-2"></i>
                        Edit Bank Details
                    </button>
                </div>
            </div>
        </div>

        <!-- Add Beneficiary Form -->
        <div class="bg-white rounded-lg shadow-sm border mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                    <i class="fas fa-plus-circle text-blue-600 mr-2"></i>
                    Add New Beneficiary
                </h2>
            </div>
            <div class="p-6">
                <form id="beneficiary-form" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Beneficiary Name -->
                        <div>
                            <label for="CoopName" class="block text-sm font-medium text-gray-700 mb-2">
                                Beneficiary Name <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="text" id="CoopName" name="CoopName"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Search for employee..." autocomplete="off">
                                <button type="button" id="clear-form"
                                    class="absolute right-2 top-2 text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div id="employee-suggestions"
                                class="absolute z-10 w-full bg-white border border-gray-300 rounded-lg shadow-lg hidden max-h-60 overflow-y-auto">
                            </div>
                        </div>

                        <!-- Cooperative ID -->
                        <div>
                            <label for="txtCoopid" class="block text-sm font-medium text-gray-700 mb-2">
                                Cooperative ID
                            </label>
                            <input type="text" id="txtCoopid" name="txtCoopid" readonly
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-600">
                        </div>

                        <!-- Bank Name -->
                        <div>
                            <label for="txtBankName" class="block text-sm font-medium text-gray-700 mb-2">
                                Bank Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="txtBankName" name="txtBankName" readonly
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-600">
                        </div>

                        <!-- Account Number -->
                        <div>
                            <label for="txtBankAccountNo" class="block text-sm font-medium text-gray-700 mb-2">
                                Account Number <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="txtBankAccountNo" name="txtBankAccountNo" readonly
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-600">
                        </div>

                        <!-- Bank Code -->
                        <div>
                            <label for="txtbankcode" class="block text-sm font-medium text-gray-700 mb-2">
                                Bank Code <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="txtbankcode" name="txtbankcode" readonly
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-600">
                        </div>

                        <!-- Amount -->
                        <div>
                            <label for="txtAmount" class="block text-sm font-medium text-gray-700 mb-2">
                                Amount <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="txtAmount" name="txtAmount"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="0.00">
                        </div>

                        <!-- Narration -->
                        <div class="md:col-span-2">
                            <label for="txNarration" class="block text-sm font-medium text-gray-700 mb-2">
                                Narration <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="txNarration" name="txNarration"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                value="March 2025" placeholder="Enter narration...">
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end space-x-4 pt-4 border-t border-gray-200">
                        <button type="button" id="clear-form-btn"
                            class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            <i class="fas fa-times mr-2"></i>
                            Clear Form
                        </button>
                        <button type="submit" id="submit-btn"
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-plus mr-2"></i>
                            Add Beneficiary
                        </button>
                    </div>

                    <!-- Hidden Fields -->
                    <input type="hidden" name="Batch" value="<?php echo htmlspecialchars($_SESSION['Batch'] ?? ''); ?>">
                    <input type="hidden" name="MM_insert" value="eduEntry">
                </form>
            </div>
        </div>

        <!-- Beneficiaries List -->
        <div class="bg-white rounded-lg shadow-sm border">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-list text-green-600 mr-2"></i>
                        Beneficiaries List
                    </h2>
                    <div class="flex items-center space-x-4">
                        <div class="text-sm text-gray-600">
                            Total: <span
                                class="font-semibold text-blue-600"><?php echo count($beneficiaries ?? []); ?></span>
                            beneficiaries
                        </div>
                        <div class="text-sm text-gray-600">
                            Amount: <span
                                class="font-semibold text-green-600">₦<?php echo number_format($batchTotal ?? 0, 2); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <input type="checkbox" id="select-all"
                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                S/N</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Bank</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Account No.</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Amount</th>
                            <th
                                class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody id="beneficiaries-table" class="bg-white divide-y divide-gray-200">
                        <?php if (!empty($beneficiaries)): ?>
                        <?php foreach ($beneficiaries as $index => $beneficiary): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input type="checkbox" name="coop_id"
                                    value="<?php echo htmlspecialchars($beneficiary['BeneficiaryCode']); ?>"
                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo $index + 1; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    <span class="edit-bank-name text-blue-600 hover:text-blue-800 cursor-pointer" 
                                          data-beneficiary='<?php echo htmlspecialchars(json_encode($beneficiary), ENT_QUOTES, 'UTF-8'); ?>'
                                          title="Edit Bank Details">
                                        <?php echo htmlspecialchars($beneficiary['BeneficiaryName']); ?>
                                    </span>
                                </div>
                                <div class="text-sm text-gray-500">
                                    ID: <?php echo htmlspecialchars($beneficiary['BeneficiaryCode']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo htmlspecialchars($beneficiary['Bank']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo htmlspecialchars($beneficiary['AccountNumber']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                ₦<?php echo number_format($beneficiary['Amount'], 2); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex justify-center space-x-2">
                                    <button type="button" class="edit-bank text-green-600 hover:text-green-800" 
                                        data-beneficiary='<?php echo htmlspecialchars(json_encode($beneficiary), ENT_QUOTES, 'UTF-8'); ?>'
                                        title="Edit Bank Details">
                                        <i class="fas fa-university"></i>
                                    </button>
                                    <button type="button" class="edit-beneficiary text-blue-600 hover:text-blue-800"
                                        data-beneficiary='<?php echo htmlspecialchars(json_encode($beneficiary), ENT_QUOTES, 'UTF-8'); ?>'
                                        title="Edit Amount">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="delete-beneficiary text-red-600 hover:text-red-800"
                                        data-code="<?php echo htmlspecialchars($beneficiary['BeneficiaryCode']); ?>"
                                        title="Delete Beneficiary">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-inbox text-4xl mb-4"></i>
                                <p class="text-lg">No beneficiaries found</p>
                                <p class="text-sm">Add your first beneficiary using the form above</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!empty($beneficiaries)): ?>
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                <div class="flex justify-between items-center">
                    <div class="text-sm text-gray-600">
                        <strong>Total Amount: ₦<?php echo number_format($batchTotal ?? 0, 2); ?></strong>
                    </div>
                    <button type="button" id="delete-selected"
                        class="px-4 py-2 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700 transition-colors">
                        <i class="fas fa-trash mr-2"></i>
                        Delete Selected
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Edit Modal -->
    <div id="edit-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Edit Beneficiary</h3>
                </div>
                <form id="edit-form" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Amount</label>
                        <input type="text" id="edit-amount" name="amount"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div class="flex justify-end space-x-4 pt-4">
                        <button type="button" id="cancel-edit"
                            class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Update
                        </button>
                    </div>
                    <input type="hidden" id="edit-beneficiary-code" name="beneficiary_code">
                </form>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loading-overlay" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen">
            <div class="bg-white rounded-lg p-6 flex items-center space-x-4">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <span class="text-gray-700">Processing...</span>
            </div>
        </div>
    </div>

    <!-- Bank Edit Modal -->
    <div id="bank-edit-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                            <i class="fas fa-university text-blue-600 mr-2"></i>
                            Edit Bank Details
                        </h3>
                        <button id="close-bank-modal" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-4">
                    <form id="bank-edit-form" class="space-y-4">
                        <!-- Member Info (Read-only) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Member</label>
                            <input type="text" id="modal-member-name" readonly 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-600">
                            <input type="hidden" id="modal-member-id">
                        </div>

                        <!-- Bank -->
                        <div>
                            <label for="modal-bank" class="block text-sm font-medium text-gray-700 mb-1">
                                Bank Name <span class="text-red-500">*</span>
                            </label>
                            <select id="modal-bank" name="bank" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    required>
                                <option value="">Select Bank</option>
                                <!-- Banks will be loaded dynamically -->
                            </select>
                        </div>

                        <!-- Account Number -->
                        <div>
                            <label for="modal-account-no" class="block text-sm font-medium text-gray-700 mb-1">
                                Account Number <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="modal-account-no" name="account_no" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Enter account number" required>
                        </div>

                        <!-- Bank Code -->
                        <div>
                            <label for="modal-bank-code" class="block text-sm font-medium text-gray-700 mb-1">
                                Bank Code
                            </label>
                            <input type="text" id="modal-bank-code" name="bank_code" readonly
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-600"
                                   placeholder="Bank code will be auto-filled">
                        </div>
                    </form>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                    <button id="cancel-bank-edit" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button id="save-bank-edit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-save mr-2"></i>
                        Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="js/beneficiary-management.js"></script>
</body>

</html>