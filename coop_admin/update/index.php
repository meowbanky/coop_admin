<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>UPDATE -- Powered By BankSoft</title>

    <meta name="google-signin-client_id" content="278661884826-47g3nhch01lqq1tfn88f4ls4ep53p4at.apps.googleusercontent.com">
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">

    <script>
        function onSignIn(response) {
            const responsePayload = decodeJwtResponse(response.credential);

            const givenName = responsePayload.given_name;
            const familyName = responsePayload.family_name;
            const email = responsePayload.email;

            // Send the name and email to the server for comparison
            fetch('/authenticate_coop.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ givenName, familyName, email })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        // Redirect or take any other action on successful login
                    } else {
                        alert('Login failed: ' + data.message);
                    }
                });
        }

        function decodeJwtResponse(token) {
            const base64Url = token.split('.')[1];
            const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
            const jsonPayload = decodeURIComponent(atob(base64).split('').map(c =>
                `%${('00' + c.charCodeAt(0).toString(16)).slice(-2)}`
            ).join(''));

            return JSON.parse(jsonPayload);
        }
    </script>
</head>
<body class="flex items-center justify-center h-screen bg-gray-100">
<div id="g_id_onload"
     data-client_id="278661884826-47g3nhch01lqq1tfn88f4ls4ep53p4at.apps.googleusercontent.com"
     data-callback="onSignIn">
</div>
<div class="g_id_signin"
     data-type="standard"
     data-shape="rectangular"
     data-theme="outline"
     data-text="signin_with"
     data-size="large">
</div>
</body>
</html>
