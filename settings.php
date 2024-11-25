<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Get user data
$stmt = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get user settings
$stmt = $conn->prepare("SELECT theme, notifications_enabled FROM user_settings WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$settings = $stmt->get_result()->fetch_assoc();

if (!$settings) {
    // Create default settings if none exist
    $stmt = $conn->prepare("INSERT INTO user_settings (user_id) VALUES (?)");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $settings = ['theme' => 'light', 'notifications_enabled' => 1];
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_username':
                $new_username = $conn->real_escape_string($_POST['username']);
                if ($new_username !== $user['username']) {
                    // Check if username is already taken
                    $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
                    $check_stmt->bind_param("si", $new_username, $user_id);
                    $check_stmt->execute();
                    if ($check_stmt->get_result()->num_rows === 0) {
                        $update_stmt = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
                        $update_stmt->bind_param("si", $new_username, $user_id);
                        if ($update_stmt->execute()) {
                            $_SESSION['username'] = $new_username;
                            $success_message = "Username updated successfully!";
                            $user['username'] = $new_username;
                        } else {
                            $error_message = "Failed to update username.";
                        }
                    } else {
                        $error_message = "Username already taken.";
                    }
                }
                break;

            case 'update_settings':
                $theme = $_POST['theme'];
                $notifications = isset($_POST['notifications']) ? 1 : 0;
                $preferred_categories = isset($_POST['preferred_categories']) ? $_POST['preferred_categories'] : [];
                
                // Update user_settings
                $update_stmt = $conn->prepare("UPDATE user_settings SET theme = ?, notifications_enabled = ? WHERE user_id = ?");
                $update_stmt->bind_param("sii", $theme, $notifications, $user_id);
                
                // Update user_preferences
                $categories_json = json_encode($preferred_categories);
                $prefs_stmt = $conn->prepare("INSERT INTO user_preferences (user_id, preferred_categories) 
                                             VALUES (?, ?) 
                                             ON DUPLICATE KEY UPDATE preferred_categories = ?");
                $prefs_stmt->bind_param("iss", $user_id, $categories_json, $categories_json);
                
                if ($update_stmt->execute() && $prefs_stmt->execute()) {
                    $settings['theme'] = $theme;
                    $settings['notifications_enabled'] = $notifications;
                    $success_message = "Settings updated successfully!";
                } else {
                    $error_message = "Failed to update settings.";
                }
                break;

            case 'delete_account':
                if (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'true') {
                    $delete_stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                    $delete_stmt->bind_param("i", $user_id);
                    if ($delete_stmt->execute()) {
                        session_destroy();
                        header('Location: login.php');
                        exit();
                    } else {
                        $error_message = "Failed to delete account.";
                    }
                }
                break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Omni</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="assets/css/theme.css" rel="stylesheet">
    <script src="assets/js/theme.js"></script>
</head>
<body class="bg-gray-100" data-theme="<?php echo htmlspecialchars($settings['theme']); ?>">
    <!-- Header -->
    <header class="bg-white shadow-md">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <div class="flex items-center">
                <a href="home.php" class="text-2xl font-bold text-orange-500">Omni</a>
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-gray-700">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="logout.php" class="text-red-500 hover:text-red-700">Logout</a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <?php if ($success_message): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <!-- Profile Section -->
                <div class="p-6 border-b">
                    <h2 class="text-xl font-semibold mb-4">Profile Settings</h2>
                    <div class="flex items-center mb-6">
                        <div class="w-20 h-20 bg-gray-200 rounded-full flex items-center justify-center mr-4">
                            <span class="text-3xl text-gray-500">
                                <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                            </span>
                        </div>
                        <div>
                            <h3 class="font-semibold"><?php echo htmlspecialchars($user['username']); ?></h3>
                            <p class="text-gray-600"><?php echo htmlspecialchars($user['email']); ?></p>
                        </div>
                    </div>

                    <form action="" method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="update_username">
                        <div>
                            <label for="username" class="block text-sm font-medium text-gray-700">Change Username</label>
                            <input type="text" 
                                   id="username" 
                                   name="username" 
                                   value="<?php echo htmlspecialchars($user['username']); ?>"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                        </div>
                        <button type="submit" 
                                class="bg-orange-500 text-white px-4 py-2 rounded-md hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                            Update Username
                        </button>
                    </form>
                </div>

                <!-- Preferences Section -->
                <div class="p-6 border-b">
                    <h2 class="text-xl font-semibold mb-4">Preferences</h2>
                    <form action="" method="POST" class="space-y-6">
                        <input type="hidden" name="action" value="update_settings">
                        
                        <!-- Theme Settings -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Theme Preference</label>
                            <div class="flex items-center space-x-6">
                                <label class="inline-flex items-center">
                                    <input type="radio" 
                                           name="theme" 
                                           value="light" 
                                           <?php echo $settings['theme'] === 'light' ? 'checked' : ''; ?>
                                           class="form-radio text-orange-500 focus:ring-orange-500">
                                    <span class="ml-2 flex items-center">
                                        <span class="material-icons mr-1">light_mode</span>
                                        Light
                                    </span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" 
                                           name="theme" 
                                           value="dark" 
                                           <?php echo $settings['theme'] === 'dark' ? 'checked' : ''; ?>
                                           class="form-radio text-orange-500 focus:ring-orange-500">
                                    <span class="ml-2 flex items-center">
                                        <span class="material-icons mr-1">dark_mode</span>
                                        Dark
                                    </span>
                                </label>
                            </div>
                        </div>

                        <!-- News Preferences -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Preferred News Categories</label>
                            <div class="grid grid-cols-2 gap-4">
                                <?php
                                $categories = [
                                    'general' => 'General News',
                                    'technology' => 'Technology',
                                    'business' => 'Business',
                                    'science' => 'Science',
                                    'health' => 'Health',
                                    'sports' => 'Sports',
                                    'entertainment' => 'Entertainment'
                                ];
                                
                                // Get user's preferred categories
                                $stmt = $conn->prepare("SELECT preferred_categories FROM user_preferences WHERE user_id = ?");
                                $stmt->bind_param("i", $user_id);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                $prefs = $result->fetch_assoc();
                                $selected_categories = $prefs ? json_decode($prefs['preferred_categories'], true) : [];
                                
                                foreach ($categories as $value => $label):
                                ?>
                                <label class="inline-flex items-center">
                                    <input type="checkbox" 
                                           name="preferred_categories[]" 
                                           value="<?php echo $value; ?>"
                                           <?php echo in_array($value, $selected_categories) ? 'checked' : ''; ?>
                                           class="form-checkbox text-orange-500">
                                    <span class="ml-2"><?php echo $label; ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Notification Settings -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Notifications</label>
                            <div class="space-y-2">
                                <label class="inline-flex items-center">
                            <input type="checkbox" 
                                   name="notifications" 
                                   <?php echo $settings['notifications_enabled'] ? 'checked' : ''; ?>
                                           class="form-checkbox text-orange-500">
                                    <span class="ml-2">Enable News Notifications</span>
                            </label>
                            </div>
                        </div>

                        <button type="submit" 
                                class="w-full bg-orange-500 text-white px-4 py-2 rounded-md hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                            Save Preferences
                        </button>
                    </form>
                </div>

                <!-- Delete Account Section -->
                <div class="p-6 bg-gray-50">
                    <h2 class="text-xl font-semibold mb-4 text-red-600">Danger Zone</h2>
                    <form action="" method="POST" onsubmit="return confirmDelete()">
                        <input type="hidden" name="action" value="delete_account">
                        <input type="hidden" name="confirm_delete" value="true">
                        <button type="submit" 
                                class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                            Delete Account
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const themeManager = ThemeManager.getInstance();
        const themeInputs = document.querySelectorAll('input[name="theme"]');

        // Function to update theme everywhere
        function updateTheme(newTheme) {
            // Update document theme
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);

            // Update body classes
            if (newTheme === 'dark') {
                document.body.classList.add('dark');
            } else {
                document.body.classList.remove('dark');
            }
        }

        // Initialize theme
        const currentTheme = '<?php echo $settings['theme']; ?>';
        updateTheme(currentTheme);

        // Listen for theme changes
        themeInputs.forEach(input => {
            input.addEventListener('change', async (e) => {
                const newTheme = e.target.value;
                updateTheme(newTheme);
                
                // Save theme preference via AJAX
                try {
                    const response = await fetch('save_preferences.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'update_theme',
                            theme: newTheme
                        })
                    });

                    const data = await response.json();
                    if (!data.success) {
                        console.error('Failed to save theme preference');
                    }
                } catch (error) {
                    console.error('Error saving theme:', error);
                }
            });
        });
    });

    // Delete account confirmation
    function confirmDelete() {
        return confirm('Are you sure you want to delete your account? This action cannot be undone.');
    }
    </script>
</body>
</html> 