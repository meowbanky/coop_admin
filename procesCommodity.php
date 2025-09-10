<?php 
ini_set('max_execution_time','300');
require_once('Connections/coop.php'); 
include_once('classes/model.php'); 

// Set page title
$pageTitle = 'OOUTH COOP - Commodity Processing';

// Include header
include 'includes/header.php';
	


$currentPage = $_SERVER["PHP_SELF"];






$today = date('Y-m-d');
$userName = $_SESSION['SESS_FIRST_NAME'] ?? 'User';
$userRole = $_SESSION['role'] ?? 'Admin';
?>
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">



        <!-- Page Header -->
        <div class="mb-8 fade-in">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">
                        <i class="fas fa-box text-blue-600 mr-3"></i>Commodity Management
                    </h2>
                    <p class="text-gray-600">Update and view member commodity records</p>
                </div>
            </div>
        </div>

        <!-- Breadcrumb -->
        <nav class="mb-6 fade-in">
            <ol class="flex items-center space-x-2 text-sm">
                <li><a href="home.php" class="text-blue-600 hover:text-blue-800"><i
                            class="fas fa-home mr-1"></i>Dashboard</a></li>
                <li><i class="fas fa-chevron-right text-gray-400"></i></li>
                <li class="text-gray-500">Commodity Management</li>
            </ol>
        </nav>

        <!-- Messages -->
        <?php if (isset($_SESSION['msg'])): ?>
        <div class="mb-6 fade-in">
            <div
                class="bg-<?php echo $_SESSION['alertcolor'] === 'success' ? 'green' : 'red'; ?>-50 border border-<?php echo $_SESSION['alertcolor'] === 'success' ? 'green' : 'red'; ?>-200 text-<?php echo $_SESSION['alertcolor'] === 'success' ? 'green' : 'red'; ?>-700 px-4 py-3 rounded-lg">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <i
                            class="fas fa-<?php echo $_SESSION['alertcolor'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?> mr-2"></i>
                        <span><?php echo $_SESSION['msg']; ?></span>
                    </div>
                    <button onclick="this.parentElement.parentElement.style.display='none'"
                        class="text-<?php echo $_SESSION['alertcolor'] === 'success' ? 'green' : 'red'; ?>-500 hover:text-<?php echo $_SESSION['alertcolor'] === 'success' ? 'green' : 'red'; ?>-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
        <?php 
            unset($_SESSION['msg']);
            unset($_SESSION['alertcolor']);
            ?>
        <?php endif; ?>
        <!-- Search Section -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8 fade-in">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-search text-blue-600 mr-2"></i>
                Search Employee
            </h3>
            <form action="pfa.php" method="post" accept-charset="utf-8" id="add_item_form" autocomplete="off">
                <div class="flex items-center space-x-4">
                    <div class="flex-1">
                        <label for="item" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-user mr-1"></i>Employee Search
                        </label>
                        <div class="relative">
                            <input type="text" name="item" id="item"
                                class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                                placeholder="Enter staff name or staff number..." autocomplete="off">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center space-x-2">
                                <button type="button" id="clear-search"
                                    class="text-gray-400 hover:text-gray-600 transition-colors" title="Clear Search">
                                    <i class="fas fa-times"></i>
                                </button>
                                <span id="ajax-loader" class="hidden">
                                    <i class="fas fa-spinner fa-spin text-blue-600"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>


        <!-- Employee Info Display -->
        <div id="employee-info"
            class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-4 mb-6 border border-blue-200 fade-in"
            style="display: none;">
            <div class="flex items-center space-x-4">
                <div class="bg-blue-100 p-3 rounded-full">
                    <i class="fas fa-user text-blue-600 text-xl"></i>
                </div>
                <div>
                    <h3 id="namee" class="text-lg font-semibold text-gray-900">Select an employee to view details</h3>
                    <p class="text-sm text-gray-600">Employee information will appear here</p>
                </div>
            </div>
        </div>

        <!-- Period Selection -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                <i class="fas fa-calendar-alt mr-2"></i>Select Period
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="period-select" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-calendar mr-1"></i>Payroll Period
                    </label>
                    <select id="period-select"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors">
                        <option value="">Select Period to View Commodities</option>
                        <?php  
                        $query = $conn->prepare('SELECT * FROM tbpayrollperiods order by id desc');
                        $res = $query->execute();
                        $out = $query->fetchAll(PDO::FETCH_ASSOC);
                        while ($row = array_shift($out)) {
                            echo('<option value="' . $row['id'] .'">' . $row['PayrollPeriod'] . '</option>');
                        }
                        ?>
                    </select>
                </div>
                <div class="flex items-end">
                    <button id="load-commodities"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        disabled>
                        <i class="fas fa-search mr-2"></i>Load Commodities
                    </button>
                </div>
            </div>
        </div>

        <!-- Commodity Form -->
        <div id="commodity-form" class="bg-white rounded-xl shadow-lg p-6 mb-6 fade-in" style="display: none;">
            <h3 class="text-xl font-semibold text-gray-900 mb-6 flex items-center">
                <i class="fas fa-box text-green-600 mr-2"></i>
                Commodity Details
            </h3>

            <div id="cont">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <!-- Commodity Name -->
                    <div>
                        <label for="Commodity" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-tag mr-1"></i>Name of Commodity
                        </label>
                        <input type="text" id="Commodity" name="Commodity" value=""
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                            placeholder="Enter commodity name...">
                    </div>

                    <!-- Commodity Type -->
                    <div>
                        <label for="CommodityType" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-list mr-1"></i>Commodity Type
                        </label>
                        <select id="CommodityType" name="CommodityType"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors">
                            <option value="0">Select Type</option>
                            <option value="1" selected>Commodity</option>
                            <option value="2">Non-Commodity</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <!-- Amount -->
                    <div>
                        <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-money-bill mr-1"></i>Amount
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">₦</span>
                            </div>
                            <input type="number" id="amount" name="amount" value=""
                                class="w-full pl-8 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                                placeholder="0.00" step="0.01" min="0">
                        </div>
                    </div>

                    <!-- Period -->
                    <div>
                        <label for="period" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-calendar mr-1"></i>Period
                        </label>
                        <select id="period" name="period"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors">
                            <option value="0">Select Period</option>
                            <?php  
                            $query = $conn->prepare('SELECT * FROM tbpayrollperiods order by id desc');
                            $res = $query->execute();
                            $out = $query->fetchAll(PDO::FETCH_ASSOC);
                            while ($row = array_shift($out)) {
                                echo('<option value="' . $row['id'] .'">' . $row['PayrollPeriod'] . '</option>');
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Commodity Balance -->
                    <div>
                        <label for="commodityBalance" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-calculator mr-1"></i>Commodity Balance
                        </label>
                        <input type="text" id="commodityBalance" name="commodityBalance" value="" readonly
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 text-gray-600">
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                    <button type="button" onclick="show_modal_page('getCommodityList.php')"
                        class="px-6 py-3 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                        <i class="fas fa-eye mr-2"></i>View All Commodities
                    </button>

                    <div class="flex space-x-3">
                        <button type="button" id="clear-form"
                            class="px-6 py-3 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors">
                            <i class="fas fa-undo mr-2"></i>Clear Form
                        </button>
                        <button type="submit" id="btnAddemp"
                            class="px-6 py-3 text-sm font-medium text-white bg-green-600 border border-transparent rounded-lg hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors">
                            <i class="fas fa-save mr-2"></i>Save Commodity
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Commodities Table -->
        <div id="commodities-table" class="hidden">
            <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-list mr-2"></i>Commodities for Selected Period
                    </h3>
                    <div class="text-sm text-gray-500" id="period-info"></div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Member</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Commodity</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Type</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Amount</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody id="commodities-tbody" class="bg-white divide-y divide-gray-200">
                            <!-- Commodities will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>


        <!-- Hidden Fields -->
        <input type="hidden" id="coopid" name="coopid"
            value="<?php if(isset($_POST['item'])) {echo $_POST['item'];} ?>" />








        </div>
        </div>
        </div>
        <!-- Modal for Commodity List -->
        <div id="page_model_view_data"
            class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
            <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Commodity List</h3>
                        <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    <div class="modal-body" style="height:500px; overflow:auto;">
                        <div class="flex items-center justify-center h-64">
                            <div class="text-center">
                                <i class="fas fa-spinner fa-spin text-blue-600 text-2xl mb-2"></i>
                                <p class="text-gray-600">Loading commodity list...</p>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end mt-4">
                        <button onclick="closeModal()"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Autocomplete container -->
    <ul class="ui-autocomplete ui-front ui-menu ui-widget ui-widget-content ui-corner-all" id="ui-id-1" tabindex="0"
        style="display: none;"></ul>



    <script type="text/javascript">
    COMMON_SUCCESS = "Success";
    COMMON_ERROR = "Error";
    $.ajaxSetup({
        cache: false,
        headers: {
            "cache-control": "no-cache"
        }
    });

    $(document).ready(function() {
        var last_focused_id = null;

        if (last_focused_id && last_focused_id != 'item' && $('#' + last_focused_id).is(
                'input[type=text]'))

        {
            $('#' + last_focused_id).focus();
            $('#' + last_focused_id).select();
            $('#item').focus();
        }

        $(document).focusin(function(event) {
            last_focused_id = $(event.target).attr('id');
        });

        $("#item").autocomplete({
            source: 'searchStaff.php',
            type: 'POST',
            delay: 10,
            autoFocus: false,
            minLength: 1,
            select: function(event, ui) {
                event.preventDefault();
                $("#item").val(ui.item.value);
                $item = $("#item").val();

                $.post('getNamee.php', {
                        item: $("#item").val()
                    },
                    function(data) {
                        $(' #namee').html(data);
                        $('#employee-info').fadeIn(300);
                    });

                // Show the commodity form
                $('#commodity-form').fadeIn(300);




                ;

            }
        });





        $('#btnAddemp').click(function() {

            $.post('getCommodityProcessing.php', {
                    item: $('#coopid').val(),
                    Commodity: $('#Commodity').val(),
                    amount: $('#amount').val(),
                    period: $('#period').val(),
                    CommodityType: $('#CommodityType').val(),

                },
                function(data) {
                    $(' #cont').html(data);
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: "Member's Commodity Saved Successfully",
                        confirmButtonColor: '#10b981'
                    });
                    
                    // Refresh commodities table if it's visible
                    if (window.commodityManager && window.commodityManager.currentPeriod) {
                        window.commodityManager.loadCommodities();
                    }
                });

        });

        $('#item').focus();

        var submitting = false;

        function salesBeforeSubmit(formData, jqForm, options) {
            if (submitting) {
                return false;
            }
            submitting = true;
            $("#ajax-loader").show();

        }

        function itemScannedSuccess(responseText, statusText, xhr, $form) {

            if (($('#code').val()) == 1) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Item not Found',
                    confirmButtonColor: '#ef4444'
                });
            } else {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: "Staff No Found Successfully",
                    confirmButtonColor: '#10b981'
                });
                //	window.location.reload(true);
                $("#ajax-loader").hide();
            }
            setTimeout(function() {
                $('#item').focus();
            }, 10);

            // Gritter cleanup removed - using SweetAlert2 instead

        }



        $('#item').click(function() {
            $(this).attr('placeholder', '');
            $(this).select();
        });
        //Ajax submit current location
        $("#employee_current_location_id").change(function() {
            $("#form_set_employee_current_location_id").ajaxSubmit(function() {
                window.location.reload(true);
            });
        });


        // Validation removed - using modern SweetAlert2 validation instead

        document.getElementById('item').focus();




    });
    </script>

    <!-- New Commodity Manager JavaScript -->
    <script>
        class CommodityManager {
            constructor() {
                this.currentEmployee = null;
                this.currentPeriod = null;
                this.editingCommodityId = null;
                this.init();
            }

            init() {
                this.bindEvents();
            }

            bindEvents() {
                // Clear search button
                $('#clear-search').click(() => this.clearSearch());
                
                // Period selection
                $('#period-select').change(() => this.onPeriodChange());
                $('#load-commodities').click(() => this.loadCommodities());
                
                // Form actions
                $('#btnAddemp').click((e) => this.saveCommodity(e));
                $('#clear-form').click(() => this.clearForm());
                
                // Commodity form period change
                $(document).on('change', '#period', () => this.onCommodityPeriodChange());
            }

            clearSearch() {
                $("#item").val('');
                this.currentEmployee = null;
                $('#employee-info').hide();
                $('#commodity-form').hide();
                $('#commodities-table').hide();
            }

            onPeriodChange() {
                const period = $('#period-select').val();
                if (period) {
                    $('#load-commodities').prop('disabled', false);
                } else {
                    $('#load-commodities').prop('disabled', true);
                    $('#commodities-table').hide();
                }
            }

            loadCommodities() {
                const period = $('#period-select').val();
                console.log('Selected period:', period);
                if (!period) return;

                this.currentPeriod = period;
                
                // Show loading
                $('#commodities-tbody').html(`
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center">
                                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
                                <span class="ml-2 text-gray-600">Loading commodities...</span>
                            </div>
                        </td>
                    </tr>
                `);
                $('#commodities-table').show();

                console.log('Sending AJAX request with period:', period);
                $.post('api/commodity.php', {
                    action: 'get_commodities',
                    period: period
                })
                .done((response) => {
                    console.log('API Response:', response);
                    if (response.success) {
                        this.displayCommodities(response.data);
                        $('#period-info').text(`Period: ${response.data[0]?.PayrollPeriod || 'Unknown'}`);
                    } else {
                        this.showError(response.message);
                    }
                })
                .fail((xhr, status, error) => {
                    console.log('AJAX Error:', xhr, status, error);
                    console.log('Response Text:', xhr.responseText);
                    this.showError('Failed to load commodities: ' + error);
                });
            }

            displayCommodities(commodities) {
                if (commodities.length === 0) {
                    $('#commodities-tbody').html(`
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                <i class="fas fa-inbox text-2xl mb-2"></i>
                                <p>No commodities found for this period</p>
                            </td>
                        </tr>
                    `);
                    return;
                }

                let html = '';
                commodities.forEach(commodity => {
                    const typeText = commodity.CommodityType == 1 ? 'Commodity' : 'Non-Commodity';
                    const typeClass = commodity.CommodityType == 1 ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800';
                    
                    html += `
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                ${commodity.member_name || 'Unknown'}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                ${commodity.Commodity}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full ${typeClass}">
                                    ${typeText}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                ₦${parseFloat(commodity.amount).toLocaleString()}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                ${commodity.dateOfInsertion}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button onclick="commodityManager.editCommodity(${commodity.commodity_id})" 
                                        class="text-indigo-600 hover:text-indigo-900 mr-3">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="commodityManager.deleteCommodity(${commodity.commodity_id})" 
                                        class="text-red-600 hover:text-red-900">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });
                
                $('#commodities-tbody').html(html);
            }

            editCommodity(commodityId) {
                // Find the commodity in the current data by looking at the table
                const row = $(`button[onclick*="${commodityId}"]`).closest('tr');
                const commodity = row.find('td:nth-child(2)').text().trim();
                const typeText = row.find('td:nth-child(3) span').text().trim();
                const type = typeText === 'Commodity' ? '1' : '2';
                const amount = row.find('td:nth-child(4)').text().replace('₦', '').replace(/,/g, '').trim();
                
                console.log('Editing commodity:', { commodity, type, amount, commodityId });
                
                // Populate the form
                $('#Commodity').val(commodity);
                $('#CommodityType').val(type);
                $('#amount').val(amount);
                $('#period').val(this.currentPeriod);
                
                this.editingCommodityId = commodityId;
                
                // Show the form if it's hidden
                $('#commodity-form').show();
                
                // Scroll to form
                $('#commodity-form')[0].scrollIntoView({ behavior: 'smooth' });
            }

            deleteCommodity(commodityId) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post('api/commodity.php', {
                            action: 'delete_commodity',
                            commodity_id: commodityId
                        })
                        .done((response) => {
                            if (response.success) {
                                this.showSuccess('Commodity deleted successfully');
                                this.loadCommodities(); // Refresh the table
                            } else {
                                this.showError(response.message);
                            }
                        })
                        .fail(() => {
                            this.showError('Failed to delete commodity');
                        });
                    }
                });
            }

            saveCommodity(e) {
                e.preventDefault();
                
                const commodity = $('#Commodity').val();
                const amount = $('#amount').val();
                const commodityType = $('#CommodityType').val();
                const period = $('#period').val();
                
                if (!commodity || !amount || !period) {
                    this.showError('Please fill in all required fields');
                    return;
                }
                
                if (!this.currentEmployee) {
                    this.showError('Please select an employee first');
                    return;
                }
                
                const formData = {
                    item: this.currentEmployee,
                    Commodity: commodity,
                    amount: amount,
                    period: period,
                    CommodityType: commodityType
                };
                
                // If editing, use the API to update
                if (this.editingCommodityId) {
                    formData.action = 'edit_commodity';
                    formData.commodity_id = this.editingCommodityId;
                    
                    $.post('api/commodity.php', formData)
                    .done((response) => {
                        if (response.success) {
                            this.showSuccess('Commodity updated successfully');
                            this.clearForm();
                            this.loadCommodities();
                        } else {
                            this.showError(response.message);
                        }
                    })
                    .fail(() => {
                        this.showError('Failed to update commodity');
                    });
                } else {
                    // Creating new commodity
                    $.post('getCommodityProcessing.php', formData)
                    .done((data) => {
                        this.showSuccess("Member's Commodity Saved Successfully");
                        this.clearForm();
                        
                        // Refresh commodities table if it's visible
                        if (this.currentPeriod) {
                            this.loadCommodities();
                        }
                    })
                    .fail(() => {
                        this.showError('Failed to save commodity');
                    });
                }
            }

            clearForm() {
                $('#Commodity').val('');
                $('#amount').val('');
                $('#CommodityType').val('1');
                $('#period').val('0');
                this.editingCommodityId = null;
            }

            onCommodityPeriodChange() {
                // Update the period selection if it matches
                const period = $('#period').val();
                if (period && period !== '0') {
                    $('#period-select').val(period);
                    this.currentPeriod = period;
                }
            }

            showSuccess(message) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: message,
                    confirmButtonColor: '#10b981'
                });
            }

            showError(message) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: message,
                    confirmButtonColor: '#ef4444'
                });
            }
        }

        // Initialize the commodity manager
        $(document).ready(function() {
            window.commodityManager = new CommodityManager();
        });
    </script>


    <script src="js/tableExport.js"></script>
    <script src="js/main.js"></script>
    </div>
    <!--end #content-->
    </div>
    <!--end #wrapper-->

    <ul class="ui-autocomplete ui-front ui-menu ui-widget ui-widget-content ui-corner-all" id="ui-id-1" tabindex="0"
        style="display: none;"></ul>


<?php include 'includes/footer.php'; ?>
