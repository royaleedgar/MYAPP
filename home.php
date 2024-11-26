<?php
session_start();
require_once 'config/database.php';
require_once 'config/news_api.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT preferred_categories FROM user_preferences WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$preferences = $result->fetch_assoc();

if ($preferences && !empty($preferences['preferred_categories'])) {
    $preferred_categories = json_decode($preferences['preferred_categories'], true);
    $default_category = $preferred_categories[0] ?? 'general';
} else {
    $default_category = 'general';
}

$category = isset($_GET['category']) ? $_GET['category'] : $default_category;
$search = isset($_GET['search']) ? $_GET['search'] : null;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$sortBy = isset($_GET['sortBy']) ? $_GET['sortBy'] : 'publishedAt';

$newsapi = new NewsAPI();

try {
    if ($search) {
        $news = $newsapi->getEverything([
            'q' => $search,
            'searchIn' => 'title,description',
            'sortBy' => $sortBy,
            'page' => $page,
            'pageSize' => 12
        ]);
    } else {
        $news = $newsapi->getTopHeadlines([
            'category' => $category,
            'page' => $page,
            'pageSize' => 12
        ]);
    }
} catch (Exception $e) {
    $error_message = $e->getMessage();
    $news = ['articles' => []];
}

// Check which articles are already bookmarked
function isBookmarked($conn, $user_id, $article_url)
{
    $stmt = $conn->prepare("SELECT id FROM bookmarks WHERE user_id = ? AND article_url = ?");
    $stmt->bind_param("is", $user_id, $article_url);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Omni - Your News Feed</title>
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
    <?php include 'components/loader.php'; ?>
    <!-- Fixed Header -->
    <header class="fixed top-0 left-0 right-0 bg-white shadow-md z-50">
        <div class="flex items-center justify-between px-4 py-2">
            <div class="flex items-center">
                <button id="menuToggle" class="p-2 hover:bg-gray-100 rounded-full">
                    <span class="material-icons">menu</span>
                </button>
                <h1 class="text-2xl font-bold text-orange-500 ml-4">Omni</h1>
            </div>

            <div class="flex-1 max-w-2xl px-4">
                <form action="" method="GET" class="relative">
                    <div class="flex">
                        <div class="relative flex-1">
                            <input type="text"
                                name="search"
                                placeholder="Search news..."
                                value="<?php echo htmlspecialchars($search ?? ''); ?>"
                                class="w-full px-4 py-2 border rounded-l-full focus:outline-none focus:ring-2 focus:ring-orange-500">
                        </div>
                        <button type="submit"
                            class="px-6 bg-gray-100 border border-l-0 rounded-r-full hover:bg-gray-200">
                            <span class="material-icons text-gray-600">search</span>
                        </button>
                    </div>
                </form>
            </div>

            <div class="flex items-center space-x-4">
                <button class="p-2 hover:bg-gray-100 rounded-full">
                    <span class="material-icons">notifications</span>
                </button>
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
        <aside id="sidebar" class="fixed left-0 top-14 bottom-0 w-64 bg-white overflow-y-auto transition-transform duration-300">
            <nav class="py-4">
                <a href="?category=general"
                    class="flex items-center px-6 py-3 text-sm <?php echo $category === 'general' ? 'bg-gray-100 font-medium' : ''; ?> hover:bg-gray-100">
                    <span class="material-icons mr-4">home</span>
                    General
                </a>
                <a href="?category=technology"
                    class="flex items-center px-6 py-3 text-sm <?php echo $category === 'technology' ? 'bg-gray-100 font-medium' : ''; ?> hover:bg-gray-100">
                    <span class="material-icons mr-4">computer</span>
                    Technology
                </a>
                <a href="?category=business"
                    class="flex items-center px-6 py-3 text-sm <?php echo $category === 'business' ? 'bg-gray-100 font-medium' : ''; ?> hover:bg-gray-100">
                    <span class="material-icons mr-4">business</span>
                    Business
                </a>
                <a href="?category=science"
                    class="flex items-center px-6 py-3 text-sm <?php echo $category === 'science' ? 'bg-gray-100 font-medium' : ''; ?> hover:bg-gray-100">
                    <span class="material-icons mr-4">science</span>
                    Science
                </a>
                <a href="?category=health"
                    class="flex items-center px-6 py-3 text-sm <?php echo $category === 'health' ? 'bg-gray-100 font-medium' : ''; ?> hover:bg-gray-100">
                    <span class="material-icons mr-4">health_and_safety</span>
                    Health
                </a>
                <a href="?category=sports"
                    class="flex items-center px-6 py-3 text-sm <?php echo $category === 'sports' ? 'bg-gray-100 font-medium' : ''; ?> hover:bg-gray-100">
                    <span class="material-icons mr-4">sports_soccer</span>
                    Sports
                </a>
                <a href="bookmarks.php"
                    class="flex items-center px-6 py-3 text-sm hover:bg-gray-100">
                    <span class="material-icons mr-4">bookmark</span>
                    Saved Articles
                </a>
            </nav>
        </aside>

        <!-- News Feed -->
        <main class="flex-1 ml-64 p-6">
            <?php if ($news && isset($news['articles'])): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($news['articles'] as $article): ?>
                        <article class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-all duration-300 flex flex-col cursor-pointer"
                                 onclick="window.location.href='article.php?<?php echo http_build_query([
                                     'url' => $article['url'],
                                     'title' => $article['title'],
                                     'image' => $article['urlToImage'] ?? '',
                                     'source' => $article['source']['name'],
                                     'description' => $article['description'],
                                     'published' => $article['publishedAt']
                                 ]); ?>'">
                            <div class="relative pb-[56.25%]"> <!-- 16:9 aspect ratio -->
                                <?php if ($article['urlToImage']): ?>
                                    <img class="absolute h-full w-full object-cover"
                                        src="<?php echo htmlspecialchars($article['urlToImage']); ?>"
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
                                            <?php echo htmlspecialchars($article['source']['name']); ?>
                                        </h3>
                                        <time class="text-xs text-gray-500">
                                            <?php echo date('M j, Y', strtotime($article['publishedAt'])); ?>
                                        </time>
                                    </div>
                                </div>

                                <h2 class="text-lg font-semibold leading-tight mb-2 line-clamp-2">
                                    <?php echo htmlspecialchars($article['title']); ?>
                                </h2>

                                <p class="text-sm text-gray-600 mb-4 line-clamp-2">
                                    <?php echo htmlspecialchars($article['description']); ?>
                                </p>

                                <div class="mt-auto flex justify-between items-center">
                                    <a href="<?php echo htmlspecialchars($article['url']); ?>"
                                        target="_blank"
                                        class="text-orange-500 hover:text-orange-600 text-sm font-medium">
                                        Read More
                                    </a>
                                    <?php
                                    $is_bookmarked = isBookmarked($conn, $user_id, $article['url']);
                                    ?>
                                    <button onclick='toggleBookmark(<?php echo json_encode([
                                                                        "url" => $article["url"],
                                                                        "title" => $article["title"],
                                                                        "image" => $article["urlToImage"],
                                                                        "source" => $article["source"]["name"],
                                                                        "description" => $article["description"],
                                                                        "publishedAt" => $article["publishedAt"]
                                                                    ]); ?>)' class="p-2 hover:bg-gray-100 rounded-full">
                                        <span class="material-icons <?php echo $is_bookmarked ? 'text-orange-500' : 'text-gray-500'; ?>">
                                            <?php echo $is_bookmarked ? 'bookmark' : 'bookmark_border'; ?>
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <div class="mt-8 flex justify-center space-x-4">
                    <?php if ($page > 1): ?>
                        <a href="?category=<?php echo $category; ?>&page=<?php echo $page - 1; ?>&sortBy=<?php echo $sortBy; ?>"
                            class="px-4 py-2 bg-white text-orange-500 border border-orange-500 rounded-full hover:bg-orange-50">
                            Previous
                        </a>
                    <?php endif; ?>

                    <?php if (count($news['articles']) >= 12): ?>
                        <a href="?category=<?php echo $category; ?>&page=<?php echo $page + 1; ?>&sortBy=<?php echo $sortBy; ?>"
                            class="px-4 py-2 bg-orange-500 text-white rounded-full hover:bg-orange-600">
                            Next
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-8">
                    <span class="material-icons text-6xl text-gray-400">search_off</span>
                    <p class="text-gray-600 mt-4">No news articles found.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>

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
    </script>
    <script>
        // Theme toggle functionality
        document.addEventListener('DOMContentLoaded', () => {
            const themeManager = ThemeManager.getInstance();
            const themeToggle = document.getElementById('themeToggle');
            const themeIcon = themeToggle.querySelector('.material-icons');

            function updateThemeIcon(theme) {
                themeIcon.textContent = theme === 'dark' ? 'light_mode' : 'dark_mode';
            }

            // Initialize icon based on current theme
            updateThemeIcon(themeManager.theme);

            themeToggle.addEventListener('click', () => {
                const newTheme = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                themeManager.applyTheme(newTheme);
                updateThemeIcon(newTheme);
            });
        });
    </script>

    <!-- Toast Container -->
    <div id="toast" class="fixed bottom-5 right-5 transform transition-transform duration-300 translate-y-full opacity-0 z-50">
        <div class="bg-gray-800 text-white px-6 py-3 rounded-lg shadow-lg flex items-center space-x-2">
            <span id="toastIcon" class="material-icons"></span>
            <span id="toastMessage"></span>
        </div>
    </div>

    <style>
        #toast.show {
            transform: translateY(0);
            opacity: 1;
        }
    </style>

    <script>
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toastMessage');
            const toastIcon = document.getElementById('toastIcon');

            // Set message and icon
            toastMessage.textContent = message;
            if (type === 'success') {
                toastIcon.textContent = 'check_circle';
                toastIcon.className = 'material-icons text-green-500';
            } else {
                toastIcon.textContent = 'error';
                toastIcon.className = 'material-icons text-red-500';
            }

            // Show toast
            toast.classList.add('show');

            // Hide toast after 3 seconds
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }
    </script>

    <script>
        // Show loader for navigation
        document.addEventListener('click', (e) => {
            const link = e.target.closest('a');
            if (link && !e.ctrlKey && !e.shiftKey && !e.metaKey && !e.altKey) {
                loader.show();
            }
        });

        // Show loader for form submissions
        document.addEventListener('submit', (e) => {
            const form = e.target;
            if (!form.hasAttribute('data-no-loader')) {
                loader.show();
            }
        });

        // Handle AJAX errors
        window.addEventListener('error', () => {
            loader.hide();
        });
    </script>

    <script>
    async function toggleBookmark(articleData) {
        const button = event.currentTarget;
        const icon = button.querySelector('.material-icons');
        
        try {
            const response = await fetch('api/bookmark.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(articleData)
            });
            
            const data = await response.json();
            
            if (data.success) {
                if (data.bookmarked) {
                    icon.textContent = 'bookmark';
                    icon.classList.remove('text-gray-500');
                    icon.classList.add('text-orange-500');
                    showToast('Article saved to bookmarks', 'success');
                } else {
                    icon.textContent = 'bookmark_border';
                    icon.classList.remove('text-orange-500');
                    icon.classList.add('text-gray-500');
                    showToast('Article removed from bookmarks', 'success');
                }
            } else {
                showToast(data.message || 'Failed to update bookmark', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Failed to update bookmark', 'error');
        }
    }
    </script>
</body>

</html>