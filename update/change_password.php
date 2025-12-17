<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
</head>
<body class="flex items-center justify-center h-screen bg-gray-100">
<div class="w-full max-w-md bg-white p-8 rounded shadow">
    <h2 class="text-2xl font-bold mb-6 text-center">Reset Password</h2>
    <form action="reset_password.php" method="post">
        <input type="hidden" name="token" value="<?php echo $_GET['token']; ?>">
        <div class="mb-4">
            <label for="password" class="block text-sm font-bold mb-2">New Password</label>
            <input type="password" name="password" id="password" class="w-full p-2 border rounded" required>
        </div>
        <div class="mb-4">
            <label for="confirm_password" class="block text-sm font-bold mb-2">Confirm Password</label>
            <input type="password" name="confirm_password" id="confirm_password" class="w-full p-2 border rounded" required>
        </div>
        <button type="submit" class="w-full bg-blue-500 text-white p-2 rounded">Reset Password</button>
    </form>
</div>
</body>
</html>
