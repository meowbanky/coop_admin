<?php
require_once('Connections/coop.php');
session_start();
if (!isset($_SESSION['user_id'])) header("Location:index.php");
require_once('includes/header.php');

// Fetch periods for dropdown
$periods = [];
$res = $coop->query("SELECT id, PayrollPeriod FROM tbpayrollperiods ORDER BY id DESC");
if ($res) $periods = $res->fetch_all(MYSQLI_ASSOC);

// Fetch categories for both types
function getCategories($coop, $type) {
    $stmt = $coop->prepare("SELECT id, name FROM coop_categories WHERE type=? ORDER BY name");
    $stmt->bind_param('s', $type);
    $stmt->execute();
    $res = $stmt->get_result();
    $cats = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $cats;
}
$incomeCats = getCategories($coop, 'income');
$expendCats = getCategories($coop, 'expenditure');
?>
<div class="max-w-4xl mx-auto bg-white rounded-xl shadow-lg mt-8 mb-16 p-6 md:p-10">
    <h2 class="text-2xl font-bold text-blue-900 mb-6">Cooperative Society Income & Expenditure</h2>
    <form id="financeForm" class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8" autocomplete="off">
        <div>
            <label class="block font-semibold mb-1">Period<span class="text-red-500">*</span></label>
            <select name="periodid" id="periodid" class="w-full border px-3 py-2 rounded" required>
                <option value="">Select Period...</option>
                <?php foreach($periods as $p): ?>
                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['PayrollPeriod']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block font-semibold mb-1">Type<span class="text-red-500">*</span></label>
            <select name="type" id="type" class="w-full border px-3 py-2 rounded" required>
                <option value="">Select Type...</option>
                <option value="income">Income</option>
                <option value="expenditure">Expenditure</option>
            </select>
        </div>
        <div>
            <label class="block font-semibold mb-1">Category<span class="text-red-500">*</span> <button type="button"
                    id="manageCatsBtn" class="ml-2 text-xs text-blue-600 underline">Manage Categories</button></label>
            <select name="category_id" id="category_id" class="w-full border px-3 py-2 rounded" required>
                <option value="">Select Category...</option>
                <?php foreach($incomeCats as $cat): ?>
                <option value="<?= $cat['id'] ?>" data-type="income">[Income] <?= htmlspecialchars($cat['name']) ?>
                </option>
                <?php endforeach; ?>
                <?php foreach($expendCats as $cat): ?>
                <option value="<?= $cat['id'] ?>" data-type="expenditure">[Expenditure]
                    <?= htmlspecialchars($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block font-semibold mb-1">Amount<span class="text-red-500">*</span></label>
            <input name="amount" id="amount" type="text" class="w-full border px-3 py-2 rounded" required
                placeholder="e.g. 10000.00">
        </div>
        <div class="md:col-span-2">
            <label class="block font-semibold mb-1">Description</label>
            <textarea name="description" id="description" class="w-full border px-3 py-2 rounded" rows="2"></textarea>
        </div>
        <div class="md:col-span-2 flex items-center gap-4 mt-2">
            <button type="submit"
                class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded">Save</button>
            <button type="reset"
                class="bg-gray-400 hover:bg-gray-500 text-white font-bold py-2 px-6 rounded">Reset</button>
        </div>
    </form>
    <div class="mb-6">
        <label class="block font-semibold mb-1">Show Report for Period:</label>
        <select id="reportPeriod" class="w-full border px-3 py-2 rounded mb-2">
            <option value="">Select Period...</option>
            <?php foreach($periods as $p): ?>
            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['PayrollPeriod']) ?></option>
            <?php endforeach; ?>
        </select>
        <div class="flex gap-2 mb-2">
            <button id="exportExcel" class="bg-green-500 text-white px-3 py-1 rounded">Export to Excel</button>
            <button id="exportPDF" class="bg-blue-500 text-white px-3 py-1 rounded">Export to PDF</button>
        </div>
        <div id="reportTable"></div>
    </div>

    <!-- Edit Transaction Modal -->
    <div id="editTransModal" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md relative">
            <button id="closeEditTransModal"
                class="absolute top-2 right-2 text-gray-500 hover:text-red-600 text-2xl">&times;</button>
            <h3 class="text-lg font-bold mb-4">Edit Transaction</h3>
            <form id="editTransForm" class="space-y-3">
                <input type="hidden" id="edit_id" name="id">
                <div>
                    <label class="block font-semibold mb-1">Amount</label>
                    <input type="text" id="edit_amount" name="amount" class="w-full border px-3 py-2 rounded" required>
                </div>
                <div>
                    <label class="block font-semibold mb-1">Description</label>
                    <textarea id="edit_description" name="description"
                        class="w-full border px-3 py-2 rounded"></textarea>
                </div>
                <div class="flex gap-2 mt-2">
                    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded">Save Changes</button>
                    <button type="button" id="cancelEditTrans"
                        class="bg-gray-400 text-white px-4 py-2 rounded">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Category Management Modal -->
<div id="catModal" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-lg p-4 w-full max-w-md relative">
        <button id="closeCatModal"
            class="absolute top-2 right-2 text-gray-500 hover:text-red-600 text-2xl">&times;</button>
        <h3 class="text-lg font-bold mb-4">Manage Categories</h3>
        <form id="catForm" class="flex flex-col md:flex-row gap-2 mb-4">
            <select id="catType" class="border rounded px-2 py-1" required>
                <option value="income">Income</option>
                <option value="expenditure">Expenditure</option>
            </select>
            <input type="text" id="catName" class="border rounded px-2 py-1 flex-1" placeholder="Category name"
                required>
            <button type="submit" class="bg-blue-600 text-white px-3 py-1 rounded">Add</button>
        </form>
        <div id="catList"></div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function fetchCategories(type, cb) {
    $.get('coop_finance_categories.php', {
        type
    }, function(data) {
        cb(data);
    }, 'json');
}

function refreshCategoryDropdown(type) {
    fetchCategories(type, function(cats) {
        let $cat = $('#category_id');
        $cat.html('<option value="">Select Category...</option>');
        cats.forEach(function(cat) {
            $cat.append(`<option value="${cat.id}">${cat.name}</option>`);
        });
    });
}
$('#type').on('change', function() {
    let type = $(this).val();
    refreshCategoryDropdown(type);
});
$('#manageCatsBtn').on('click', function() {
    $('#catModal').removeClass('hidden');
    loadCatList($('#catType').val());
});
$('#closeCatModal').on('click', function() {
    $('#catModal').addClass('hidden');
});
$('#catType').on('change', function() {
    loadCatList($(this).val());
});

function loadCatList(type) {
    fetchCategories(type, function(cats) {
        let html = '<ul class="mb-2">';
        cats.forEach(function(cat) {
            html +=
                `<li class="flex items-center justify-between py-1"><span>${cat.name}</span> <button class="editCatBtn text-xs text-yellow-600 mr-2" data-id="${cat.id}" data-name="${cat.name}" data-type="${type}">Edit</button> <button class="delCatBtn text-xs text-red-600" data-id="${cat.id}" data-type="${type}">Delete</button></li>`;
        });
        html += '</ul>';
        $('#catList').html(html);
    });
}
$('#catForm').on('submit', function(e) {
    e.preventDefault();
    let type = $('#catType').val();
    let name = $('#catName').val().trim();
    if (!name) return;
    $.post('coop_finance_categories.php', {
        action: 'add',
        type,
        name
    }, function(resp) {
        if (resp.success) {
            Swal.fire('Added!', resp.success, 'success');
            $('#catName').val('');
            loadCatList(type);
            if ($('#type').val() === type) refreshCategoryDropdown(type);
        } else {
            Swal.fire('Error', resp.error, 'error');
        }
    }, 'json');
});
$(document).on('click', '.delCatBtn', function() {
    let id = $(this).data('id');
    let type = $(this).data('type');
    Swal.fire({
        title: 'Delete?',
        text: 'Delete this category?',
        icon: 'warning',
        showCancelButton: true
    }).then(r => {
        if (r.isConfirmed) {
            $.post('coop_finance_categories.php', {
                action: 'delete',
                id
            }, function(resp) {
                if (resp.success) {
                    Swal.fire('Deleted!', resp.success, 'success');
                    loadCatList(type);
                    if ($('#type').val() === type) refreshCategoryDropdown(type);
                } else {
                    Swal.fire('Error', resp.error, 'error');
                }
            }, 'json');
        }
    });
});
$(document).on('click', '.editCatBtn', function() {
    let id = $(this).data('id');
    let type = $(this).data('type');
    let name = prompt('Edit category name:', $(this).data('name'));
    if (name && name.trim()) {
        $.post('coop_finance_categories.php', {
            action: 'edit',
            id,
            name,
            type
        }, function(resp) {
            if (resp.success) {
                Swal.fire('Updated!', resp.success, 'success');
                loadCatList(type);
                if ($('#type').val() === type) refreshCategoryDropdown(type);
            } else {
                Swal.fire('Error', resp.error, 'error');
            }
        }, 'json');
    }
});
$('#financeForm').on('submit', function(e) {
    e.preventDefault();
    let formData = $(this).serialize();
    $.post('coop_finance_action.php', formData, function(resp) {
        if (resp.success) {
            Swal.fire('Saved!', resp.success, 'success');
            $('#financeForm')[0].reset();
            if ($('#reportPeriod').val()) loadReport($('#reportPeriod').val());
        } else {
            Swal.fire('Error', resp.error, 'error');
        }
    }, 'json');
});
$('#reportPeriod').on('change', function() {
    let pid = $(this).val();
    if (pid) loadReport(pid);
    else $('#reportTable').html('');
});

function loadReport(periodid) {
    $.get('coop_finance_report.php', {
        periodid,
        admin: true
    }, function(html) {
        $('#reportTable').html(html);
    });
}
// Edit/Delete/Export logic
$(document).on('click', '.editTransBtn', function() {
    let id = $(this).data('id');
    let amount = $(this).data('amount');
    let description = $(this).data('description');
    $('#edit_id').val(id);
    $('#edit_amount').val(amount);
    $('#edit_description').val(description);
    $('#editTransModal').removeClass('hidden');
});
$('#closeEditTransModal, #cancelEditTrans').on('click', function() {
    $('#editTransModal').addClass('hidden');
});
$('#editTransForm').on('submit', function(e) {
    e.preventDefault();
    let formData = $(this).serialize();
    $.post('coop_finance_edit.php', formData, function(resp) {
        if (resp.success) {
            Swal.fire('Updated!', resp.success, 'success');
            $('#editTransModal').addClass('hidden');
            if ($('#reportPeriod').val()) loadReport($('#reportPeriod').val());
        } else {
            Swal.fire('Error', resp.error, 'error');
        }
    }, 'json');
});
$(document).on('click', '.deleteTransBtn', function() {
    let id = $(this).data('id');
    Swal.fire({
        title: 'Delete?',
        text: 'Delete this transaction?',
        icon: 'warning',
        showCancelButton: true
    }).then(r => {
        if (r.isConfirmed) {
            $.post('coop_finance_delete.php', {
                id
            }, function(resp) {
                if (resp.success) {
                    Swal.fire('Deleted!', resp.success, 'success');
                    if ($('#reportPeriod').val()) loadReport($('#reportPeriod').val());
                } else {
                    Swal.fire('Error', resp.error, 'error');
                }
            }, 'json');
        }
    });
});
$('#exportExcel').on('click', function() {
    let pid = $('#reportPeriod').val();
    if (!pid) return Swal.fire('Select a period first.', '', 'info');
    window.open('coop_finance_export.php?periodid=' + pid + '&type=excel', '_blank');
});
$('#exportPDF').on('click', function() {
    let pid = $('#reportPeriod').val();
    if (!pid) return Swal.fire('Select a period first.', '', 'info');
    window.open('coop_finance_export.php?periodid=' + pid + '&type=pdf', '_blank');
});
</script>
<?php require_once('includes/footer.php'); ?>