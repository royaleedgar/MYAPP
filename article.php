<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

// Get article data from URL parameters
$article_url = isset($_GET['url']) ? urldecode($_GET['url']) : null;
$article_title = isset($_GET['title']) ? urldecode($_GET['title']) : null;
$article_image = isset($_GET['image']) ? urldecode($_GET['image']) : null;
$article_source = isset($_GET['source']) ? urldecode($_GET['source']) : null;
$article_description = isset($_GET['description']) ? urldecode($_GET['description']) : null;
$article_published = isset($_GET['published']) ? urldecode($_GET['published']) : null;

// Check if article is bookmarked
function isBookmarked($conn, $user_id, $article_url) {
    $stmt = $conn->prepare("SELECT id FROM bookmarks WHERE user_id = ? AND article_url = ?");
    $stmt->bind_param("is", $user_id, $article_url);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

$is_bookmarked = isBookmarked($conn, $user_id, $article_url);

// Add to reading history
$stmt = $conn->prepare("INSERT INTO reading_history (user_id, article_url, article_title) VALUES (?, ?, ?)");
$stmt->bind_param("iss", $user_id, $article_url, $article_title);
$stmt->execute();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($article_title); ?> - Omni</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="assets/css/theme.css" rel="stylesheet">
    <script src="assets/js/theme.js"></script>
</head>
<body class="bg-gray-100">
    <?php include 'components/loader.php'; ?>
    
    <!-- Header -->
    <header class="fixed top-0 left-0 right-0 bg-white shadow-md z-50">
        <div class="flex items-center justify-between px-4 py-2">
            <div class="flex items-center">
                <a href="home.php" class="p-2 hover:bg-gray-100 rounded-full">
                    <span class="material-icons">arrow_back</span>
                </a>
                <h1 class="text-2xl font-bold text-orange-500 ml-4">Omni</h1>
            </div>
            
            <div class="flex items-center space-x-4">
                <button onclick='toggleBookmark(<?php echo json_encode([
                    "url" => $article_url,
                    "title" => $article_title,
                    "image" => $article_image,
                    "source" => $article_source,
                    "description" => $article_description,
                    "publishedAt" => $article_published
                ]); ?>)' class="p-2 hover:bg-gray-100 rounded-full">
                    <span class="material-icons <?php echo $is_bookmarked ? 'text-orange-500' : 'text-gray-500'; ?>">
                        <?php echo $is_bookmarked ? 'bookmark' : 'bookmark_border'; ?>
                    </span>
                </button>
                <a href="<?php echo htmlspecialchars($article_url); ?>" 
                   target="_blank"
                   class="p-2 hover:bg-gray-100 rounded-full">
                    <span class="material-icons">open_in_new</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 pt-20 pb-8 max-w-4xl">
        <article class="bg-white rounded-lg shadow-md overflow-hidden">
            <?php if ($article_image): ?>
                <div class="relative h-96 w-full">
                    <img src="<?php echo htmlspecialchars($article_image); ?>" 
                         alt="Article image"
                         class="absolute h-full w-full object-cover"
                         onerror="this.src='placeholder.jpg'">
                </div>
            <?php endif; ?>

            <div class="p-6">
                <div class="flex items-center space-x-2 mb-4">
                    <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center">
                        <span class="material-icons text-gray-500 text-sm">newspaper</span>
                    </div>
                    <div>
                        <h3 class="font-medium text-sm">
                            <?php echo htmlspecialchars($article_source); ?>
                        </h3>
                        <time class="text-xs text-gray-500">
                            <?php echo date('M j, Y', strtotime($article_published)); ?>
                        </time>
                    </div>
                </div>

                <h1 class="text-3xl font-bold mb-4">
                    <?php echo htmlspecialchars($article_title); ?>
                </h1>

                <div class="prose max-w-none">
                    <p class="text-lg text-gray-700 leading-relaxed mb-6">
                        <?php echo htmlspecialchars($article_description); ?>
                    </p>
                </div>

                <div class="mt-8 border-t pt-8">
                    <h3 class="text-xl font-semibold mb-4 flex items-center">
                        <span class="material-icons mr-2">auto_awesome</span>
                        AI Summary
                    </h3>
                    
                    <div id="summarySection" class="prose max-w-none">
                        <div id="summaryPlaceholder" class="animate-pulse">
                            <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                            <div class="h-4 bg-gray-200 rounded w-full mb-2"></div>
                            <div class="h-4 bg-gray-200 rounded w-5/6"></div>
                        </div>
                        <div id="summaryContent" class="hidden">
                            <p class="text-gray-700 dark:text-gray-300"></p>
                        </div>
                        <button id="regenerateButton" 
                                onclick="generateSummary(true)"
                                class="mt-4 text-orange-500 hover:text-orange-600 hidden flex items-center">
                            <span class="material-icons mr-1">refresh</span>
                            Regenerate Summary
                        </button>
                    </div>
                </div>

                <div class="mt-8 flex justify-between items-center">
                    <a href="<?php echo htmlspecialchars($article_url); ?>" 
                       target="_blank"
                       class="inline-flex items-center space-x-2 text-orange-500 hover:text-orange-600">
                        <span>Read full article</span>
                        <span class="material-icons">arrow_forward</span>
                    </a>
                </div>
            </div>
        </article>
    </main>

    <!-- Toast Container -->
    <div id="toast" class="fixed bottom-5 right-5 transform transition-transform duration-300 translate-y-full opacity-0 z-50">
        <div class="bg-gray-800 text-white px-6 py-3 rounded-lg shadow-lg flex items-center space-x-2">
            <span id="toastIcon" class="material-icons"></span>
            <span id="toastMessage"></span>
        </div>
    </div>

    <script>
    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        const toastMessage = document.getElementById('toastMessage');
        const toastIcon = document.getElementById('toastIcon');
        
        toastMessage.textContent = message;
        if (type === 'success') {
            toastIcon.textContent = 'check_circle';
            toastIcon.className = 'material-icons text-green-500';
        } else {
            toastIcon.textContent = 'error';
            toastIcon.className = 'material-icons text-red-500';
        }
        
        toast.classList.add('show');
        
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }

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

    async function generateSummary(regenerate = false) {
        const summaryPlaceholder = document.getElementById('summaryPlaceholder');
        const summaryContent = document.getElementById('summaryContent');
        const regenerateButton = document.getElementById('regenerateButton');
        
        if (regenerate) {
            summaryContent.classList.add('hidden');
            summaryPlaceholder.classList.remove('hidden');
        }
        
        try {
            const response = await fetch('api/summarize.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    text: `${<?php echo json_encode($article_title); ?>}\n\n${<?php echo json_encode($article_description); ?>}`,
                    url: <?php echo json_encode($article_url); ?>,
                    regenerate: regenerate
                })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                summaryPlaceholder.classList.add('hidden');
                summaryContent.querySelector('p').textContent = data.summary;
                summaryContent.classList.remove('hidden');
                regenerateButton.classList.remove('hidden');
            } else {
                throw new Error(data.message || 'Failed to generate summary');
            }
        } catch (error) {
            console.error('Error:', error);
            summaryContent.querySelector('p').textContent = 'Failed to generate summary. Please try again.';
            summaryContent.classList.remove('hidden');
            summaryPlaceholder.classList.add('hidden');
            regenerateButton.classList.remove('hidden');
        }
    }

    // Generate summary when page loads
    document.addEventListener('DOMContentLoaded', generateSummary);
    </script>
</body>
</html> 