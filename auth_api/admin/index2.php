<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OOUTH Registration</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/themes/base/jquery-ui.min.css" rel="stylesheet">
    <script src="https://www.gstatic.com/firebasejs/9.6.1/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.6.1/firebase-auth-compat.js"></script>
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

                <div id="recaptcha-container" class="my-4"></div>

                <button type="submit" id="submitBtn"
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Register
                </button>
            </div>
        </form>

        <!-- Admin Tools (hidden in production) -->
        <div class="text-center space-y-2">
            <button onclick="clearFirebaseCache()" class="text-sm text-gray-500 hover:text-gray-700">
                Clear Firebase Cache
            </button>
            <button onclick="resetVerificationState()" class="text-sm text-gray-500 hover:text-gray-700 ml-4">
                Reset Verification State
            </button>
        </div>

        <!-- OTP Verification Modal -->
        <div id="otpModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3 text-center">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Enter OTP</h3>
                    <p class="text-sm text-gray-500 mt-2">
                        Enter the OTP sent to your phone
                    </p>
                    <div class="mt-2 px-7 py-3">
                        <input type="text" id="otpInput" maxlength="6"
                               class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                               placeholder="Enter 6-digit OTP">
                    </div>
                    <div class="items-center px-4 py-3">
                        <button id="verifyOtp"
                                class="px-4 py-2 bg-blue-600 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            Verify OTP
                        </button>
                    </div>
                    <div class="mt-3">
                        <button id="resendOtp" class="text-sm text-blue-600 hover:text-blue-800">
                            Resend OTP
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Password Creation Modal -->
        <div id="passwordModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3 text-center">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Create Password</h3>
                    <p class="text-sm text-gray-500 mt-2">
                        Create a strong password for your account
                    </p>
                    <div class="mt-4 space-y-4">
                        <div class="relative">
                            <input type="password" id="password"
                                   class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                                   placeholder="Enter password" minlength="8">
                            <button type="button" class="toggle-password absolute inset-y-0 right-0 pr-3 flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-icon text-gray-500">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-off-icon hidden text-gray-500">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                                    <line x1="1" y1="1" x2="23" y2="23"/>
                                </svg>
                            </button>
                            <p class="text-xs text-gray-500 mt-1">
                                Password must be at least 8 characters
                            </p>
                        </div>
                        <div class="relative">
                            <input type="password" id="confirmPassword"
                                   class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                                   placeholder="Confirm password">
                            <button type="button" class="toggle-password absolute inset-y-0 right-0 pr-3 flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-icon text-gray-500">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-off-icon hidden text-gray-500">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                                    <line x1="1" y1="1" x2="23" y2="23"/>
                                </svg>
                            </button>
                        </div>
                        <div class="items-center px-4 py-3">
                            <button id="createPassword"
                                    class="px-4 py-2 bg-blue-600 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                Create Password
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Constants
    const MAX_ATTEMPTS_PER_HOUR = 5;
    const COOLDOWN_PERIOD = 60 * 60 * 1000; // 1 hour in milliseconds
    const RESEND_COOLDOWN = 60; // 60 seconds cooldown for resend

    // Global variables
    let lastAttemptTime = null;
    let attemptCount = 0;
    let recaptchaVerifier;
    let recaptchaWidgetId;
    let resendTimer = null;

    // Firebase configuration
    const firebaseConfig = {
        apiKey: "AIzaSyD2k2NzXlgfxwstKdaWKvOcrBe3_00ExGg",
        authDomain: "oouth2.firebaseapp.com",
        projectId: "oouth2",
        storageBucket: "oouth2.firebasestorage.app",
        messagingSenderId: "190406680104",
        appId: "1:190406680104:web:e0103eeef8b211eabe5f1d",
        measurementId: "G-C2KPEBR2X7"
    };

    // Initialize Firebase
    firebase.initializeApp(firebaseConfig);

    // Helper functions
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

    function isValidNigerianNumber(phoneNumber) {
        const cleanNumber = phoneNumber.replace(/\D/g, '');
        const validPrefixes = ['070', '080', '081', '090', '091'];
        const prefix = cleanNumber.substring(0, 3);
        return cleanNumber.length === 11 && validPrefixes.includes(prefix);
    }

    function canAttemptVerification() {
        const now = Date.now();
        const attempts = JSON.parse(localStorage.getItem('otpAttempts') || '[]');

        // Remove attempts older than 1 hour
        const recentAttempts = attempts.filter(timestamp =>
            now - timestamp < COOLDOWN_PERIOD
        );

        if (recentAttempts.length >= MAX_ATTEMPTS_PER_HOUR) {
            const timeLeft = Math.ceil((COOLDOWN_PERIOD - (now - recentAttempts[0])) / 60000);
            showMessage(`Too many attempts. Please try again in ${timeLeft} minutes`, true);
            return false;
        }

        recentAttempts.push(now);
        localStorage.setItem('otpAttempts', JSON.stringify(recentAttempts));
        return true;
    }

    function startResendTimer() {
        let timeLeft = RESEND_COOLDOWN;
        const resendBtn = $("#resendOtp");
        resendBtn.prop('disabled', true);

        resendTimer = setInterval(() => {
            resendBtn.text(`Resend OTP (${timeLeft}s)`);
            timeLeft--;

            if (timeLeft < 0) {
                clearInterval(resendTimer);
                resendBtn.text('Resend OTP');
                resendBtn.prop('disabled', false);
            }
        }, 1000);
    }

    function clearFirebaseCache() {
        firebase.app().delete()
            .then(() => {
                localStorage.clear();
                sessionStorage.clear();
                document.cookie.split(";").forEach(function(c) {
                    document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/");
                });
                window.location.reload(true);
            });
    }

    function resetVerificationState() {
        localStorage.removeItem('otpAttempts');
        sessionStorage.removeItem('phoneVerified');
        if (recaptchaVerifier) {
            recaptchaVerifier.clear();
        }
        initializeRecaptcha();
        showMessage('Verification state has been reset');
    }

    $(document).ready(function() {
        // Initialize reCAPTCHA
        function initializeRecaptcha() {
            if (recaptchaVerifier) {
                recaptchaVerifier.clear();
            }
            recaptchaVerifier = new firebase.auth.RecaptchaVerifier('recaptcha-container', {
                'size': 'normal',
                'callback': (response) => {
                    console.log('reCAPTCHA verified');
                },
                'expired-callback': () => {
                    console.log('reCAPTCHA expired');
                    if (recaptchaWidgetId !== undefined) {
                        grecaptcha.reset(recaptchaWidgetId);
                    }
                }
            });

            recaptchaVerifier.render().then(function(widgetId) {
                recaptchaWidgetId = widgetId;
            });
        }

        initializeRecaptcha();

        // Password toggle functionality
        $('.toggle-password').on('click', function() {
            const passwordInput = $(this).siblings('input');
            const eyeIcon = $(this).find('.eye-icon');
            const eyeOffIcon = $(this).find('.eye-off-icon');

            if (passwordInput.attr('type') === 'password') {
                passwordInput.attr('type', 'text');
                eyeIcon.addClass('hidden');
                eyeOffIcon.removeClass('hidden');
            } else {
                passwordInput.attr('type', 'password');
                eyeIcon.removeClass('hidden');
                eyeOffIcon.addClass('hidden');
            }
        });

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

        // Form validation
        function validateForm() {
            const staffId = $("#staffId").val();
            const mobileNo = $("#mobileNo").val();
            const email = $("#email").val();
            const alternateEmail = $("#alternateEmail").val();

            if (!staffId || !mobileNo || !email || !alternateEmail) {
                showMessage('Please fill in all required fields', true);
                return false;
            }

            if (!isValidNigerianNumber(mobileNo)) {
                showMessage('Please enter a valid Nigerian phone number', true);
                return false;
            }

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email) || !emailRegex.test(alternateEmail)) {
                showMessage('Please enter valid email addresses', true);
                return false;
            }

            return true;
        }

        // Handle form submission
        $("#registrationForm").on('submit', function(e) {
            e.preventDefault();

            if (!validateForm() || !canAttemptVerification()) {
                return;
            }

            const submitButton = $("#submitBtn");
            setLoading(submitButton, true);

            let phoneNumber = $("#mobileNo").val();
            phoneNumber = phoneNumber.replace(/\D/g, '');

            if (phoneNumber.startsWith('0')) {
                phoneNumber = '+234' + phoneNumber.substring(1);
            } else if (!phoneNumber.startsWith('+')) {
                phoneNumber = '+234' + phoneNumber;
            }

            // Try Firebase Phone Auth
            firebase.auth().signInWithPhoneNumber(phoneNumber, recaptchaVerifier)
                .then((confirmationResult) => {
                    window.confirmationResult = confirmationResult;
                    $("#otpModal").removeClass('hidden');
                    startResendTimer();
                    showMessage('OTP sent successfully');
                })
                .catch((error) => {
                    console.error('Firebase error:', error);
                    showMessage(error.message, true);
                    if (error.code === 'auth/invalid-recaptcha-response') {
                        initializeRecaptcha();
                    }
                })
                .finally(() => {
                    setLoading(submitButton, false);
                });
        });

        // Handle OTP verification
        $("#verifyOtp").on('click', function() {
            const verifyButton = $(this);
            const code = $("#otpInput").val();

            if (!code || code.length !== 6) {
                showMessage('Please enter a valid 6-digit OTP', true);
                return;
            }

            setLoading(verifyButton, true);

            window.confirmationResult.confirm(code)
                .then((result) => {
                    $("#otpModal").addClass('hidden');
                    $("#passwordModal").removeClass('hidden');
                    showMessage('OTP verified successfully');
                    clearInterval(resendTimer);
                })
                .catch((error) => {
                    showMessage('Invalid OTP. Please try again.', true);
                })
                .finally(() => {
                    setLoading(verifyButton, false);
                });
        });

        // Handle password creation
        $("#createPassword").on('click', function() {
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

            const createButton = $(this);
            setLoading(createButton, true);

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
                        resetVerificationState();
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
                    setLoading(createButton, false);
                }
            });
        });

        // Handle resend OTP
        $("#resendOtp").on('click', function() {
            if ($(this).prop('disabled')) return;
            $("#registrationForm").submit();
        });
    });
</script>