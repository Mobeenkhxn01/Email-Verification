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
<html lang="en">
<head>
    <meta charset="UTF-8">
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
<body class="bg-gradient-to-br from-blue-50 via-white to-indigo-50 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-2xl mx-auto">
        <div class="bg-white shadow-2xl rounded-2xl p-8 border border-gray-100">
            <h1 class="text-3xl font-bold text-center text-indigo-700 mb-2">📩 XKCD Subscription</h1>
            <p class="text-center text-gray-600 mb-6">Stay updated with the latest XKCD comics. Subscribe now!</p>
            
            <!-- Email Form -->
            <form method="POST" class="mb-6">
                <div class="flex flex-col gap-4">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900">Email Address</label>
                        <input 
                            type="email" 
                            name="email" 
                            value="<?= htmlspecialchars($email) ?>" 
                            class="w-full rounded-xl border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm p-3" 
                            placeholder="your@email.com" 
                            required
                        />
                    </div>
                    <button 
                        type="submit" 
                        class="w-full text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-300 font-medium rounded-xl text-base px-5 py-3 transition-all duration-200"
                    >
                        Get Verification Code
                    </button>
                </div>
            </form>

            <!-- Verification Form -->
            <form method="POST" class="mb-6">
                <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
                <div class="flex flex-col gap-4">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900">Verification Code</label>
                        <input 
                            type="text" 
                            name="verification_code" 
                            maxlength="6" 
                            class="w-full rounded-xl border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm p-3 tracking-widest text-center font-mono text-lg" 
                            placeholder="123456"
                        />
                    </div>
                    <button 
                        type="submit" 
                        class="w-full text-white bg-green-600 hover:bg-green-700 focus:ring-4 focus:ring-green-300 font-medium rounded-xl text-base px-5 py-3 transition-all duration-200"
                    >
                        Verify Subscription
                    </button>
                </div>
            </form>

            <!-- Message -->
            <?php if (!empty($message)): ?>
                <div class="text-center font-medium mt-4 p-3 rounded-lg 
                    <?= str_starts_with($message, '✅') 
                        ? 'bg-green-50 text-green-700 border border-green-200' 
                        : 'bg-red-50 text-red-700 border border-red-200' ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
