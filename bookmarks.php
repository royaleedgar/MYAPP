<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

// Fetch bookmarked articles
$sql = "SELECT * FROM bookmarks WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$bookmarks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Omni - Saved Articles</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="assets/css/theme.css" rel="stylesheet">
    <script src="assets/js/theme.js"></script>
    <script>
        // Initialize theme on page load
        document.addEventListener('DOMContentLoaded', () => {
            ThemeManager.getInstance();
        });
    </script>
</head>
<body class="bg-gray-100">
    <!-- Fixed Header -->
    <header class="fixed top-0 left-0 right-0 bg-white shadow-md z-50">
        <div class="flex items-center justify-between px-4 py-2">
            <div class="flex items-center">
                <button id="menuToggle" class="p-2 hover:bg-gray-100 rounded-full">
                    <span class="material-icons">menu</span>
                </button>
                <h1 class="text-2xl font-bold text-orange-500 ml-4">Omni</h1>
            </div>
            
            <div class="flex items-center space-x-4">
                <div class="relative group">
                    <button class="flex items-center space-x-1 p-2 hover:bg-gray-100 rounded-full">
                        <span class="material-icons">account_circle</span>
                        <span class="text-sm"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    </button>
                    <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg hidden group-hover:block">
                        <a href="settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</a>
                        <a href="logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">Logout</a>
                    </div>
                </div>
                <button id="themeToggle" class="p-2 hover:bg-gray-100 rounded-full">
                    <span class="material-icons">dark_mode</span>
                </button>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="flex pt-14"> <!-- pt-14 to account for fixed header -->
        <!-- Sidebar -->
        <aside id="sidebar" class="fixed left-0 top-14 bottom-0 w-64 bg-white overflow-y-auto">
            <nav class="py-4">
                <a href="home.php" class="flex items-center px-6 py-3 text-sm hover:bg-gray-100">
                    <span class="material-icons mr-4">home</span>
                    Home
                </a>
                <a href="bookmarks.php" class="flex items-center px-6 py-3 text-sm bg-gray-100 font-medium">
                    <span class="material-icons mr-4">bookmark</span>
                    Saved Articles
                </a>
                <hr class="my-2 border-gray-200">
                <a href="?category=technology" class="flex items-center px-6 py-3 text-sm hover:bg-gray-100">
                    <span class="material-icons mr-4">computer</span>
                    Technology
                </a>
                <a href="?category=business" class="flex items-center px-6 py-3 text-sm hover:bg-gray-100">
                    <span class="material-icons mr-4">business</span>
                    Business
                </a>
                <a href="?category=science" class="flex items-center px-6 py-3 text-sm hover:bg-gray-100">
                    <span class="material-icons mr-4">science</span>
                    Science
                </a>
                <a href="?category=health" class="flex items-center px-6 py-3 text-sm hover:bg-gray-100">
                    <span class="material-icons mr-4">health_and_safety</span>
                    Health
                </a>
                <a href="?category=sports" class="flex items-center px-6 py-3 text-sm hover:bg-gray-100">
                    <span class="material-icons mr-4">sports_soccer</span>
                    Sports
                </a>
            </nav>
        </aside>

        <!-- Bookmarks Feed -->
        <main class="flex-1 ml-64 p-6">
            <h2 class="text-2xl font-bold mb-6">Saved Articles</h2>
            
            <?php if (!empty($bookmarks)): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($bookmarks as $article): ?>
                        <article class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-all duration-300 flex flex-col">
                            <div class="relative pb-[56.25%]">
                                <?php if ($article['article_image']): ?>
                                    <img class="absolute h-full w-full object-cover" 
                                         src="<?php echo htmlspecialchars($article['article_image']); ?>" 
                                         alt="Article image"
                                         onerror="this.src='placeholder.jpg'">
                                <?php else: ?>
                                    <div class="absolute h-full w-full bg-gray-200 flex items-center justify-center">
                                        <span class="material-icons text-4xl text-gray-400">image_not_available</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="p-4 flex-1 flex flex-col">
                                <div class="flex items-start space-x-2 mb-2">
                                    <div class="h-9 w-9 rounded-full bg-gray-200 flex items-center justify-center">
                                        <span class="material-icons text-gray-500">newspaper</span>
                                    </div>
                                    <div>
                                        <h3 class="font-medium text-sm">
                                            <?php echo htmlspecialchars($article['article_source']); ?>
                                        </h3>
                                        <time class="text-xs text-gray-500">
                                            <?php echo date('M j, Y', strtotime($article['published_at'])); ?>
                                        </time>
                                    </div>
                                </div>
                                
                                <h2 class="text-lg font-semibold leading-tight mb-2 line-clamp-2">
                                    <?php echo htmlspecialchars($article['article_title']); ?>
                                </h2>
                                
                                <p class="text-sm text-gray-600 mb-4 line-clamp-2">
                                    <?php echo htmlspecialchars($article['article_description']); ?>
                                </p>
                                
                                <div class="mt-auto flex justify-between items-center">
                                    <a href="<?php echo htmlspecialchars($article['article_url']); ?>" 
                                       target="_blank"
                                       class="text-orange-500 hover:text-orange-600 text-sm font-medium">
                                        Read More
                                    </a>
                                    <button onclick='removeBookmark("<?php echo $article['article_url']; ?>")' 
                                            class="p-2 hover:bg-gray-100 rounded-full">
                                        <span class="material-icons text-orange-500">bookmark</span>
                                    </button>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-8">
                    <span class="material-icons text-6xl text-gray-400">bookmark_border</span>
                    <p class="text-gray-600 mt-4">No saved articles yet.</p>
                    <a href="home.php" class="text-orange-500 hover:text-orange-600 mt-2 inline-block">
                        Browse articles to save
                    </a>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Toast Container -->
    <div id="toast" class="fixed bottom-5 right-5 transform transition-transform duration-300 translate-y-full">
        <div class="bg-gray-800 text-white px-6 py-3 rounded-lg shadow-lg flex items-center space-x-2">
            <span id="toastIcon" class="material-icons"></span>
            <span id="toastMessage"></span>
        </div>
    </div>

    <style>
        #toast.show {
            transform: translateY(0);
        }
    </style>

    <script>
    // Sidebar toggle functionality
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('main');
    
    menuToggle.addEventListener('click', () => {
        sidebar.classList.toggle('-translate-x-full');
        mainContent.classList.toggle('ml-0');
        mainContent.classList.toggle('ml-64');
    });

    // Remove bookmark functionality
    async function removeBookmark(articleUrl) {
        const articleElement = event.currentTarget.closest('article');
        
        try {
            const response = await fetch('api/bookmark.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    url: articleUrl
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Fade out the article
                articleElement.style.opacity = '0';
                articleElement.style.transform = 'scale(0.95)';
                
                showToast('Article removed from bookmarks', 'success');
                
                // Remove the article after animation
                setTimeout(() => {
                    articleElement.remove();
                    
                    // Check if there are no more bookmarks
                    const remainingArticles = document.querySelectorAll('article');
                    if (remainingArticles.length === 0) {
                        // Show empty state without page reload
                        const main = document.querySelector('main');
                        main.innerHTML = `
                            <h2 class="text-2xl font-bold mb-6">Saved Articles</h2>
                            <div class="text-center py-8">
                                <span class="material-icons text-6xl text-gray-400">bookmark_border</span>
                                <p class="text-gray-600 mt-4">No saved articles yet.</p>
                                <a href="home.php" class="text-orange-500 hover:text-orange-600 mt-2 inline-block">
                                    Browse articles to save
                                </a>
                            </div>
                        `;
                    }
                }, 300);
            } else {
                showToast(data.message || 'Failed to remove bookmark', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Failed to remove bookmark', 'error');
        }
    }

    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        const toastMessage = document.getElementById('toastMessage');
        const toastIcon = document.getElementById('toastIcon');
        
        // Set message and icon
        toastMessage.textContent = message;
        toastIcon.textContent = type === 'success' ? 'check_circle' : 'error';
        
        // Show toast
        toast.classList.add('show');
        
        // Hide toast after 3 seconds
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }

    // Theme toggle button
    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = themeToggle.querySelector('.material-icons');
    
    function updateThemeIcon(theme) {
        themeIcon.textContent = theme === 'dark' ? 'light_mode' : 'dark_mode';
    }
    
    // Initialize icon
    updateThemeIcon(localStorage.getItem('theme') || 'light');
    
    themeToggle.addEventListener('click', () => {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        // Dispatch theme change event
        document.dispatchEvent(new CustomEvent('themeChange', {
            detail: { theme: newTheme }
        }));
        
        updateThemeIcon(newTheme);
    });
    </script>
</body>
</html> 