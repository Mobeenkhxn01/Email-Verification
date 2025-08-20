<?php
require_once 'functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

$email = $_POST['email'] ?? ($_GET['email'] ?? '');
$verification_code = $_POST['verification_code'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['email']) && !isset($_POST['verification_code'])) {
        $code = generateVerificationCode();
        $_SESSION["verification_code_subscribe_$email"] = $code;

        if (sendVerificationEmail($email, $code, 'subscribe')) {
            $_SESSION['message'] = "Verification code sent to $email";
            header("Location: {$_SERVER['PHP_SELF']}?email=" . urlencode($email));
            exit;
        } else {
            $message = "Failed to send verification code. Please try again.";
        }
    }

    if (!empty($verification_code) && !empty($email)) {
        $session_key = "verification_code_subscribe_$email";
        if (isset($_SESSION[$session_key]) && $_SESSION[$session_key] === $verification_code) {
            registerEmail($email);
            unset($_SESSION[$session_key]);
            $message = "✅ Email $email successfully verified and registered!";
        } else {
            $message = "❌ Invalid verification code.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>XKCD Email Subscription</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script>
        window.onload = function () {
            const emailPresent = "<?= $email ?>";
            if (emailPresent) {
                document.querySelector("input[name='verification_code']")?.focus();
            }
        };
    </script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="flex flex-col items-center gap-8 w-full max-w-xl mx-auto">
        <form method="POST" class="w-full bg-white shadow-md rounded-lg p-6">
            <h2 class="text-xl font-semibold mb-4 text-center text-red-600">XKCD Email Subscription</h2>
            <div class="flex flex-col gap-4">
                <div>
                    <label for="email" class="block mb-2 text-sm font-medium text-gray-900">Email</label>
                    <input 
                        type="email" 
                        name="email" 
                        value="<?= htmlspecialchars($email) ?>" 
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" 
                        placeholder="Enter your email" 
                        required 
                    />
                </div>
                <button 
                    type="submit" 
                    id="submit-email" 
                    class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5"
                >
                    Get OTP
                </button>
            </div>
        </form>

        <form method="POST" class="w-full bg-white shadow-md rounded-lg p-6">
            <h2 class="text-xl font-semibold mb-4 text-center">Verify Subscription</h2>
            <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
            <div class="flex flex-col gap-4">
                <div>
                    <label for="verification_code" class="block mb-2 text-sm font-medium text-gray-900">Verification Code</label>
                    <input 
                        type="text" 
                        name="verification_code" 
                        maxlength="6" 
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" 
                        placeholder="Enter 6-digit code"
                        required 
                    />
                </div>
                <button 
                    type="submit" 
                    id="submit-verification" 
                    class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5"
                >
                    Verify
                </button>
            </div>
        </form>

        <?php if (!empty($message)): ?>
            <p class="text-center font-medium <?= str_starts_with($message, '✅') ? 'text-green-600' : 'text-red-600' ?>">
                <?= htmlspecialchars($message) ?>
            </p>
        <?php endif; ?>
    </div>
</body>
</html>
