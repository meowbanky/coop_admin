<?php ini_set('max_execution_time', '300');
require_once('Connections/coop.php');
include_once('classes/model.php'); ?>
<?php

//Start session
session_start();

//Check whether the session variable SESS_MEMBER_ID is present or not
if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '') || $_SESSION['role'] != 'Admin') {
    header("location: index.php");
    exit();
}



$currentPage = $_SERVER["PHP_SELF"];






$today = '';
$today = date('Y-m-d');
?>
<!DOCTYPE html>
<!-- saved from url=(0055)http://www.optimumlinkup.com.ng/pos/index.php/customers -->
<html>
<?php include('header1.php'); ?>

<body data-color="grey" class="flat" style="zoom: 1;">
    <div class="modal fade hidden-print" id="myModal"></div>
    <div id="wrapper">
        <div id="header" class="hidden-print">
            <h1><a href="index.php"><img src="img/header_logo.png" class="hidden-print header-log" id="header-logo"
                        alt=""></a></h1>
            <a id="menu-trigger" href="#"><i class="fa fa-bars fa fa-2x"></i></a>
            <div class="clear"></div>
        </div>

        <?php include('header.php'); ?>


        <?php include('sidebar.php'); ?>



        <div id="content" class="clearfix sales_content_minibar">

            <script type="text/javascript">
            $(document).ready(function() {


            });
            </script>
            <div id="content-header" class="hidden-print">
                <h1> <i class="icon fa fa-search"></i>
                    Master Report</h1>


            </div>


            <div id="breadcrumb" class="hidden-print">
                <a href="home.php"><i class="fa fa-home"></i> Dashboard</a><a class="current"
                    href="masterReport.php">Report Manager</a>
            </div>
            <div class="clear"></div>
            <div id="datatable_wrapper"></div>
            <div class=" pull-right">
                <div class="row">
                    <div id="datatable_wrapper"></div>
                    <div class="col-md-12 center" style="text-align: center;">
                        <?php if (!isset($_GET['item'])) {
                            $_GET['item'] = -1;
                        }
                        if (isset($_SESSION['msg'])) {
                            echo '<div class="alert alert-' . $_SESSION['alertcolor'] . ' alert-dismissable role="alert"> <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' . $_SESSION['msg'] . '</div>';
                            unset($_SESSION['msg']);
                            unset($_SESSION['alertcolor']);
                        }
                        ?>
                        <?php
                        if (isset($_SESSION['msg'])) {
                            echo '<div class="alert alert-' . $_SESSION['alertcolor'] . ' alert-dismissable role="alert"> <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' . $_SESSION['msg'] . '</div>';
                            unset($_SESSION['msg']);
                            unset($_SESSION['alertcolor']);
                        }
                        ?>
                        <div class="row">

                        </div>
                    </div>
                </div>
            </div>
            <div id="enquiry_add">
                <div class="row hidden-print">
                    <form action="" method="post" accept-charset="utf-8" id="add_item_form" autocomplete="off">

                        <div class="col-md-2">
                            <div class="form-group">
                                <input type="text" name="item" value="" id="item" class="ui-autocomplete-input"
                                    accesskey="i" placeholder="Enter Staff Name or Staff No" />
                                <span id="ajax-loader"><img src="img/ajax-loader.gif" alt="" /></span>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                <select id="periodFrom" name="periodFrom" class="form-inps ">
                                    <option value="-1">Select Period</option>
                                    <?php $query = $conn->prepare('SELECT * FROM tbpayrollperiods order by id desc');
                                    $res = $query->execute();
                                    $out = $query->fetchAll(PDO::FETCH_ASSOC);

                                    while ($row = array_shift($out)) {
                                        echo ('<option value="' . $row['id'] . '"');

                                        echo ('>' .  $row['PayrollPeriod'] . '</option>');
                                    } ?>
                                </select>
                            </div>

                        </div>

                        <div class="col-md-2">

                            <div class="form-group">
                                <select id="periodTo" name="periodTo" class="form-inps ">
                                    <option value="-1">Select Period</option>
                                    <?php $query = $conn->prepare('SELECT * FROM tbpayrollperiods order by id desc');
                                    $res = $query->execute();
                                    $out = $query->fetchAll(PDO::FETCH_ASSOC);

                                    while ($row = array_shift($out)) {
                                        echo ('<option value="' . $row['id'] . '"');

                                        echo ('>' .  $row['PayrollPeriod'] . '</option>');
                                    } ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-2">

                            <div class="form-group">
                                <select id="no_of_records_per_page" name="no_of_records_per_page" class="form-inps ">
                                    <option value="100">Select Record/Page</option>
                                    <option value="250">250</option>
                                    <option value="500">500</option>
                                    <option value="1000">1000</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-3 pull-right">

                            <button type="button" class="btn btn-danger btn-flat" id="search"><i class="fa fa-search"
                                    aria-hidden="true"></i>
                                View Report
                            </button>

                        </div>



                    </form>
                </div>


                <div class="row">
                    <div class="col-md-12">
                        <div class="widget-box">
                            <div class="widget-title">
                                <span class="icon">
                                    <i class="fa fa-th"></i>
                                </span>
                                <h5 id="namee"></h5>
                                <span title="" class="label label-info tip-left" data-original-title="total users">Total
                                    Users<?php echo '100' ?></span>

                            </div>
                            <!--endbegiing of employee details-->
                            <div id="datatable_wrapper">

                                <div class="row top-spacer-20">

                                    <div class="col-md-12" id="sample_1">

                                        <div id="progress" style="border:1px solid #ccc; border-radius: 5px;"></div>
                                        <div id="information" style="width:0"></div>

                                        <table id="arrestedDevelopment"
                                            class="table table-striped table-hover table-bordered table-condensed">
                                            <thead class="hidden-print">
                                                <tr>
                                                    <th> </th>
                                                    <th> Coop No. </th>
                                                    <th> Name </th>
                                                    <th> Period </th>
                                                    <th> Share Amount </th>
                                                    <th> Share Balance </th>
                                                    <th> Savings Amount </th>
                                                    <th> Savings Balance </th>
                                                    <th> Interest Paid </th>
                                                    <th> Dev. Levy</th>
                                                    <th> Stationery </th>
                                                    <th> Entry Fee </th>
                                                    <th> Loan</th>
                                                    <th> Loan Repayment </th>
                                                    <th> Loan Balance </th>
                                                    <th> Commodity </th>
                                                    <th> Commodity Payment </th>
                                                    <th> Commodity Balance </th>
                                                    <th> Period </th>
                                                    <th> Total </th>


                                                </tr>
                                            </thead>
                                            <tbody>
                                            </tbody>
                                            <tfoot> </tfoot>
                                        </table>







                                    </div>

                                </div>
                            </div>
                        </div>
                        <!-- Button trigger modal -->








                    </div>
                </div>
            </div>
            <div id="footer" class="col-md-12 hidden-print">
                Please visit our
                <a href="#" target="_blank">
                    website </a>
                to learn the latest information about the project.
                <span class="text-info"> <span class="label label-info"> 14.1</span></span>
            </div>



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
                        // $('#add_item_form').ajaxSubmit({beforeSubmit: salesBeforeSubmit, success: itemScannedSuccess});
                        $.post('getNamee.php', {
                                item: $("#item").val()
                            },
                            function(data) {
                                $(' #namee').html(data)

                            });


                        //});
                        // $("#ajax-loader").hide();

                    }
                });

                $('#item').focus();
                var last_focused_id = null;
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
                        gritter("Error", 'Item not Found', 'gritter-item-error', false, true);

                    } else {
                        gritter("Success", "Staff No Found Successfully", 'gritter-item-success', false, true);
                        //  window.location.reload(true);
                        $("#ajax-loader").hide();

                    }
                    setTimeout(function() {
                        $('#item').focus();
                    }, 10);

                    setTimeout(function() {

                        $.gritter.removeAll();
                        return false;

                    }, 1000);

                }



                $('#item').click(function() {
                    $(this).attr('placeholder', '');
                });
                //Ajax submit current location
                $("#employee_current_location_id").change(function() {
                    $("#form_set_employee_current_location_id").ajaxSubmit(function() {
                        window.location.reload(true);
                    });
                });


                $('#employee_form').validate({

                    // Specify the validation rules
                    rules: {

                        namee: "required",
                        dept: "required",
                        acct_no: {
                            required: {
                                depends: function(element) {
                                    if (($("#bank option:selected").text() != 'CHEQUE/CASH') || $(
                                            "#bank option:selected").text() != 'CHEQUE/CASH') {
                                        return true;
                                    } else {
                                        return false;
                                    }
                                }
                            },
                            //"required": false,
                            minlength: 10,
                            maxlength: 10,
                            number: true
                        },

                        rsa_pin: {
                            required: {
                                depends: function(element) {
                                    if ($("#pfa option:selected").text() != 'OTHERS') {
                                        return true;
                                    } else {
                                        return false;
                                    }
                                }
                            },
                            number: true
                        }


                    },

                    // Specify the validation error messages
                    messages: {
                        namee: "The name is a required field.",


                    },

                    errorClass: "text-danger",
                    errorElement: "span",
                    highlight: function(element, errorClass, validClass) {
                        $(element).parents('.form-group').removeClass('has-success').addClass(
                            'has-error');
                    },
                    unhighlight: function(element, errorClass, validClass) {
                        $(element).parents('.form-group').removeClass('has-error').addClass(
                            'has-success');
                    },

                    submitHandler: function(form) {

                        //form.submit();
                        doEmployeeSubmit(form);
                    }
                });

                document.getElementById('item').focus();




            });

            $('#search').click(function() {
                $('#search').attr('disabled', true);
                if ($('#item').val() == '') {
                    $('#coopid').val('');
                }

                $.ajax({
                    url: 'getMasterReport.php',
                    data: {
                        periodTo: $('#periodTo').val(),
                        coopid: $('#coopid').val(),
                        periodFrom: $('#periodFrom').val(),
                        no_of_records_per_page: $('#no_of_records_per_page').val()
                    },
                    type: 'POST',
                    xhrFields: {
                        onprogress: function(e) {
                            $('#sample_1').html(e.target.responseText);
                            $("#ajax-loader").show();
                        }
                    },
                    success: function(response, message) {
                        if (message == 'success') {
                            $('#sample_1').html(response);
                            $("#ajax-loader").hide();
                            $('#search').attr('disabled', false);

                        } else {
                            gritter("Error", message, 'gritter-item-error', false, false);
                        }

                        $('#search').attr('disabled', false);
                        $("#ajax-loader").hide();


                    }

                })
                $('#search').attr('disabled', false);
                $("#ajax-loader").hide();
            });





            $(document).on('click', '.page-link', function(event) {
                event.preventDefault();
                var url = $(this).attr('href');
                $('#search').attr('disabled', true);
                $.ajax({
                    url: url,
                    data: {
                        periodTo: $('#periodTo').val(),
                        coopid: $('#coopid').val(),
                        periodFrom: $('#periodFrom').val(),
                        no_of_records_per_page: $('#no_of_records_per_page').val()
                    },
                    xhrFields: {
                        onprogress: function(e) {
                            $('#sample_1').html(e.target.responseText);
                            $("#ajax-loader").show();
                            $('#search').attr('disabled', true);
                        }
                    },
                    type: 'POST',
                    beforeSend: function() {
                        $("#ajax-loader").show();
                    },
                    success: function(data) {
                        $(' #sample_1').html(data);
                        $("#ajax-loader").hide();
                        $('#search').attr('disabled', false);

                    }


                })

            });
            $(document).on('click', '#selectAll', function() {

                $("input[type=checkbox]").prop("checked", $(this).prop("checked"));
            });

            $(document).on('click', 'input[type=checkbox]', function() {

                if (!$(this).prop("checked")) {

                    $("#selectAll").prop("checked", false);
                }
            });

            $(document).on('click', '#delete', function() {
                if (confirm('Are you sure you want to Delete the selected Transactions?')) {
                    $('#delete').prop("disabled", true);
                    $('#delete').prop("text", "Deleting...");
                    var myarray = [];
                    $('input[type=checkbox]:checked').each(function() {
                        var id = $(this).attr('value');
                        myarray.push(id);
                    });
                    $.ajax({
                        url: 'getMasterReport.php',
                        type: 'POST',
                        data: {
                            myarray: myarray
                        },
                        type: 'POST',
                        beforeSend: function() {
                            $("#ajax-loader").show();
                        },
                        xhrFields: {
                            onprogress: function(e) {
                                $('#sample_1').html(e.target.responseText);
                                $("#ajax-loader").show();
                                $('#arrestedDevelopment').hide();
                                $('#delete').attr('disabled', true);
                                $('#delete').prop("text", "DELETING SELECTED");
                            }
                        },
                        success: function(data) {
                            $(' #sample_1').html(data);
                            $("#ajax-loader").hide();
                            gritter("Success", "Selected Records Deleted Successfully",
                                'gritter-item-success', false, false);
                            $('#delete').prop("disabled", false);
                            $('#search').prop("disabled", false);
                            $('#delete').prop("text", "DELETE SELECTED");
                            $('#information').hide();
                            $('#progress').hide();
                            $('#arrestedDevelopment').hide();
                            $('#arrestedDevelopment').show();

                        }

                    })


                }
            });




            $(".select2").select2();
            </script>


            <script src="js/tableExport.js"></script>
            <script src="js/main.js"></script>
        </div>
        <!--end #content-->
    </div>
    <!--end #wrapper-->

    <ul class="ui-autocomplete ui-front ui-menu ui-widget ui-widget-content ui-corner-all" id="ui-id-1" tabindex="0"
        style="display: none;"></ul>

</body>

</html>
<?php
//mysqli_free_result($employee);
?>