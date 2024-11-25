<div id="loader" class="fixed inset-0 bg-white bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75 z-50 flex items-center justify-center transition-opacity duration-300 opacity-0 pointer-events-none">
    <div class="flex flex-col items-center">
        <div class="animate-spin rounded-full h-16 w-16 border-t-4 border-b-4 border-orange-500"></div>
        <p class="mt-4 text-gray-600 dark:text-gray-300">Loading...</p>
    </div>
</div>

<style>
.loader-active {
    opacity: 1 !important;
    pointer-events: auto !important;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>

<script>
const loader = {
    show() {
        document.getElementById('loader').classList.add('loader-active');
    },
    hide() {
        document.getElementById('loader').classList.remove('loader-active');
    }
};

// Show loader before page unload
window.addEventListener('beforeunload', () => {
    loader.show();
});

// Hide loader when page is fully loaded
window.addEventListener('load', () => {
    loader.hide();
});

// For AJAX requests
document.addEventListener('DOMContentLoaded', () => {
    // Show loader before fetch requests
    const originalFetch = window.fetch;
    window.fetch = function() {
        loader.show();
        return originalFetch.apply(this, arguments)
            .finally(() => {
                loader.hide();
            });
    };
});
</script> 