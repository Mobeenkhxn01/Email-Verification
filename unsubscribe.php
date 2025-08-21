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
            $message = "❌ Failed to send verification code. Please try again.";
            $alertClass = 'bg-red-50 text-red-700 border border-red-200';
        }
    }

    if (!empty($verification_code) && !empty($email)) {
        $session_key = "verification_code_unsubscribe_$email";
        if (isset($_SESSION[$session_key]) && $_SESSION[$session_key] === $verification_code) {
            if (unsubscribeEmail($email)) {
                unset($_SESSION[$session_key]);
                $message = "✅ Email $email has been unsubscribed.";
                $alertClass = 'bg-green-50 text-green-700 border border-green-200';
            } else {
                $message = "❌ Email not found or already unsubscribed.";
                $alertClass = 'bg-red-50 text-red-700 border border-red-200';
            }
        } else {
            $message = "❌ Invalid verification code.";
            $alertClass = 'bg-red-50 text-red-700 border border-red-200';
        }
    }

    if (!empty($message) && $alertClass === 'hidden') {
        $alertClass = 'bg-green-50 text-green-700 border border-green-200';
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
<body class="bg-gradient-to-br from-red-50 via-white to-pink-50 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-2xl mx-auto">
        <div class="bg-white shadow-2xl rounded-2xl p-8 border border-gray-100">
            <h1 class="text-3xl font-bold text-center text-red-600 mb-2">📮 Unsubscribe</h1>
            <p class="text-center text-gray-600 mb-6">We're sad to see you go. Enter your email below to unsubscribe.</p>
            
            <!-- Email Input Form -->
            <form method="POST" class="mb-6">
                <div class="flex flex-col gap-4">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900">Email Address</label>
                        <input 
                            type="email" 
                            name="unsubscribe_email" 
                            value="<?= htmlspecialchars($email) ?>" 
                            class="w-full rounded-xl border-gray-300 focus:ring-2 focus:ring-red-500 focus:border-red-500 shadow-sm p-3" 
                            placeholder="your@email.com" 
                            required
                        />
                    </div>
                    <button 
                        type="submit" 
                        class="w-full text-white bg-red-600 hover:bg-red-700 focus:ring-4 focus:ring-red-300 font-medium rounded-xl text-base px-5 py-3 transition-all duration-200"
                    >
                        Send Verification Code
                    </button>
                </div>
            </form>

            <!-- Verification Form -->
            <form method="POST">
                <input type="hidden" name="unsubscribe_email" value="<?= htmlspecialchars($email) ?>">
                <div class="flex flex-col gap-4">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900">Verification Code</label>
                        <input 
                            type="text" 
                            name="verification_code" 
                            maxlength="6" 
                            class="w-full rounded-xl border-gray-300 focus:ring-2 focus:ring-red-500 focus:border-red-500 shadow-sm p-3 tracking-widest text-center font-mono text-lg" 
                            placeholder="123456" 
                        />
                    </div>
                    <button 
                        type="submit" 
                        class="w-full text-white bg-red-600 hover:bg-red-700 focus:ring-4 focus:ring-red-300 font-medium rounded-xl text-base px-5 py-3 transition-all duration-200"
                    >
                        Confirm Unsubscribe
                    </button>
                </div>
            </form>

            <!-- Message Alert -->
            <?php if (!empty($message)): ?>
                <div class="mt-6 p-4 rounded-xl text-center font-medium <?= $alertClass ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
