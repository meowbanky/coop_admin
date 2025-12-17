<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OOUTH Registration</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/themes/base/jquery-ui.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
<div class="min-h-screen flex items-center justify-center px-4">
    <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-xl shadow-lg">
        <!-- Header -->
        <div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Staff Registration
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Please complete your registration
            </p>
        </div>

        <!-- Error/Info Messages -->
        <div id="messageBox" class="hidden rounded-md p-4">
            <p class="text-sm" id="messageText"></p>
        </div>

        <!-- Main Form -->
        <form id="registrationForm" class="mt-8 space-y-6">
            <div class="rounded-md shadow-sm space-y-4">
                <div>
                    <label for="staffSearch" class="block text-sm font-medium text-gray-700">
                        Search by Name
                    </label>
                    <input id="staffSearch" type="text"
                           class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                           placeholder="Start typing your name...">
                </div>

                <div>
                    <label for="staffId" class="block text-sm font-medium text-gray-700">
                        Staff ID
                    </label>
                    <input id="staffId" name="staffId" type="text" readonly
                           class="appearance-none relative block w-full px-3 py-2 border border-gray-300 text-gray-900 rounded-md bg-gray-50 sm:text-sm">
                </div>

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">
                        Full Name
                    </label>
                    <input id="name" name="name" type="text" readonly
                           class="appearance-none relative block w-full px-3 py-2 border border-gray-300 text-gray-900 rounded-md bg-gray-50 sm:text-sm">
                </div>

                <div>
                    <label for="mobileNo" class="block text-sm font-medium text-gray-700">
                        Mobile Number
                    </label>
                    <input id="mobileNo" name="mobileNo" type="tel" required pattern="[0-9]{11}"
                           class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                           placeholder="11 digit mobile number">
                    <p class="mt-1 text-sm text-gray-500">Format: 08012345678</p>
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">
                        Primary Email
                    </label>
                    <input id="email" name="email" type="email" required
                           class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm">
                </div>

                <div>
                    <label for="alternateEmail" class="block text-sm font-medium text-gray-700">
                        Alternate Email
                    </label>
                    <input id="alternateEmail" name="alternateEmail" type="email" required
                           class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">
                        Password
                    </label>
                    <div class="relative">
                        <input type="password" id="password" name="password" required minlength="8"
                               class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                               placeholder="Enter password">
                        <button type="button" class="toggle-password absolute inset-y-0 right-0 pr-3 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                 class="eye-icon text-gray-500">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Password must be at least 8 characters</p>
                </div>

                <div>
                    <label for="confirmPassword" class="block text-sm font-medium text-gray-700">
                        Confirm Password
                    </label>
                    <div class="relative">
                        <input type="password" id="confirmPassword" name="confirmPassword" required
                               class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                               placeholder="Confirm password">
                        <button type="button" class="toggle-password absolute inset-y-0 right-0 pr-3 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                 class="eye-icon text-gray-500">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" id="submitBtn"
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Register
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function setLoading(button, isLoading) {
        if (isLoading) {
            const originalText = button.html();
            button.data('original-text', originalText);
            button.prop('disabled', true);
            button.html('<span class="inline-flex items-center"><svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Processing...</span>');
        } else {
            button.prop('disabled', false);
            button.html(button.data('original-text'));
        }
    }

    function showMessage(message, isError = false) {
        const messageBox = $("#messageBox");
        messageBox.removeClass('bg-red-100 bg-green-100');
        messageBox.addClass(isError ? 'bg-red-100' : 'bg-green-100');
        $("#messageText").text(message);
        messageBox.removeClass('hidden');
        setTimeout(() => messageBox.addClass('hidden'), 5000);
    }

    $(document).ready(function() {
        // Initialize autocomplete
        $("#staffSearch").autocomplete({
            source: function(request, response) {
                $.ajax({
                    url: "search_staff.php",
                    dataType: "json",
                    data: { term: request.term },
                    success: function(data) { response(data); },
                    error: function() { response([]); }
                });
            },
            minLength: 2,
            select: function(event, ui) {
                $("#staffId").val(ui.item.staff_id);
                $("#name").val(ui.item.NAME);
                $("#mobileNo").val(ui.item.MOBILE_NO);
                $("#email").val(ui.item.EMAIL);
                $("#alternateEmail").val(ui.item.alternate_email);
                return false;
            }
        });

        // Toggle password visibility
        $('.toggle-password').on('click', function() {
            const passwordInput = $(this).siblings('input');
            const icon = $(this).find('svg');

            if (passwordInput.attr('type') === 'password') {
                passwordInput.attr('type', 'text');
                icon.addClass('text-blue-500');
            } else {
                passwordInput.attr('type', 'password');
                icon.removeClass('text-blue-500');
            }
        });

        // Form validation and submission
        $("#registrationForm").on('submit', function(e) {
            e.preventDefault();

            const password = $("#password").val();
            const confirmPassword = $("#confirmPassword").val();

            if (password.length < 8) {
                showMessage('Password must be at least 8 characters long', true);
                return;
            }

            if (password !== confirmPassword) {
                showMessage('Passwords do not match', true);
                return;
            }

            const submitButton = $("#submitBtn");
            setLoading(submitButton, true);

            $.ajax({
                url: 'complete_registration.php',
                method: 'POST',
                data: JSON.stringify({
                    staff_id: $("#staffId").val(),
                    mobile_no: $("#mobileNo").val(),
                    alternate_email: $("#alternateEmail").val(),
                    email: $("#email").val(),
                    password: password
                }),
                contentType: 'application/json',
                success: function(response) {
                    if (response.success) {
                        showMessage('Registration completed successfully!');
                        setTimeout(() => {
                            window.location.href = 'https://oouthsalary.com.ng/download.html';
                        }, 1500);
                    } else {
                        showMessage(response.message, true);
                    }
                },
                error: function(xhr, status, error) {
                    showMessage('An error occurred during registration: ' + error, true);
                },
                complete: function() {
                    setLoading(submitButton, false);
                }
            });
        });
    });
</script>
</body>
</html>