<?php
require_once 'functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$email = $_POST['unsubscribe_email'] ?? ($_GET['email'] ?? '');
$verification_code = $_POST['verification_code'] ?? '';

$message = $_SESSION['message'] ?? '';
$alertClass = 'hidden';
unset($_SESSION['message']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['unsubscribe_email']) && !isset($_POST['verification_code'])) {
        $code = generateVerificationCode();
        $_SESSION["verification_code_unsubscribe_$email"] = $code;

        if (sendVerificationEmail($email, $code, 'unsubscribe')) {
            $_SESSION['message'] = "Verification code sent to $email";
            header("Location: {$_SERVER['PHP_SELF']}?email=" . urlencode($email));
            exit;
        } else {
            $message = "Failed to send verification code. Please try again.";
            $alertClass = 'text-red-600';
        }
    }
   if (!empty($verification_code) && !empty($email)) {
        $session_key = "verification_code_unsubscribe_$email";
        if (isset($_SESSION[$session_key]) && $_SESSION[$session_key] === $verification_code) {
            if (unsubscribeEmail($email)) {
                unset($_SESSION[$session_key]);
                $message = "Email $email has been unsubscribed.";
                $alertClass = 'text-green-600';
            } else {
                $message = "Email not found or already unsubscribed.";
                $alertClass = 'text-red-600';
            }
        } else {
            $message = "Invalid verification code.";
            $alertClass = 'text-red-600';
        }
    }
      if (!empty($message) && $alertClass === 'hidden') {
        $alertClass = 'text-green-600';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Unsubscribe - XKCD</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="flex flex-col items-center gap-8 w-full max-w-xl mx-auto">
        <!-- Email input form -->
        <form method="POST" class="w-full bg-white shadow-md rounded-lg p-6">
            <h2 class="text-xl font-semibold mb-4 text-center text-red-600">Unsubscribe from XKCD Emails</h2>
            <div class="flex flex-col gap-4">
                <div>
                    <label for="unsubscribe_email" class="block mb-2 text-sm font-medium text-gray-900">Email</label>
                    <input 
                        type="email" 
                        name="unsubscribe_email" 
                        value="<?= htmlspecialchars($email) ?>" 
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-red-500 focus:border-red-500 block w-full p-2.5" 
                        placeholder="Enter your email to unsubscribe" 
                        required 
                    />
                </div>
                <button 
                    type="submit" 
                    class="text-white bg-red-600 hover:bg-red-700 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-2.5"
                >
                    Unsubscribe
                </button>
            </div>
        </form>
       <form method="POST" class="w-full bg-white shadow-md rounded-lg p-6">
            <h2 class="text-xl font-semibold mb-4 text-center text-red-600">Enter Verification Code</h2>
            <input type="hidden" name="unsubscribe_email" value="<?= htmlspecialchars($email) ?>">
            <div class="flex flex-col gap-4">
                <div>
                    <label for="verification_code" class="block mb-2 text-sm font-medium text-gray-900">Verification Code</label>
                    <input 
                        type="text" 
                        name="verification_code" 
                        maxlength="6" 
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-red-500 focus:border-red-500 block w-full p-2.5" 
                        placeholder="Enter 6-digit code" 
                        required 
                    />
                </div>
                <button 
                    type="submit" 
                    class="text-white bg-red-600 hover:bg-red-700 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-2.5"
                >
                    Verify
                </button>
            </div>
        </form>
      <p class="font-medium <?= $alertClass ?>"><?= htmlspecialchars($message) ?></p>
    </div>
</body>
</html>
