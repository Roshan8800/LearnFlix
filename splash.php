<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sahu Family App</title>
    <style>
        html, body {
            height: 100%;
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #fdfdfd;
            overflow: hidden; /* Hide scrollbars */
        }

        #splash-screen {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            text-align: center;
        }

        #logo {
            max-width: 150px;
            height: auto;
            animation: fadeIn 1.5s ease-in-out;
        }

        #tagline {
            font-size: 1.2em;
            color: #555;
            margin-top: 10px; /* Space below the logo */
            animation: fadeIn 1.5s ease-in-out;
        }

        #progress-container {
            position: absolute;
            bottom: 30px;
            width: 80%;
            max-width: 300px;
            height: 8px;
            background-color: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
        }

        #progress-bar {
            width: 0%;
            height: 100%;
            background-color: #4a90e2;
            border-radius: 4px;
            transition: width 0.4s ease-out;
        }

        #retry-button {
            margin-top: 20px;
            padding: 10px 20px;
            font-size: 1em;
            cursor: pointer;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #fff;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }

        /* Accessibility: Respect reduced motion settings */
        @media (prefers-reduced-motion: reduce) {
            #logo, #tagline {
                animation: none;
            }
            #progress-bar {
                transition: none;
            }
        }
    </style>
</head>
<body>
    <div id="splash-screen" role="application" aria-label="Sahu Family App is loading">
        <img src="sahu_logo.jpeg" alt="Sahu Family Logo" id="logo">
        <p id="tagline">Sahu Family</p>
        <div id="progress-container">
            <div id="progress-bar"></div>
        </div>
        <button id="retry-button" style="display: none;">Retry</button>
    </div>

    <!-- Placeholder for the main content of the app -->
    <div id="main-content" style="display: none;">
        <h1>Welcome to the Sahu Family App!</h1>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const splashScreen = document.getElementById('splash-screen');
            const mainContent = document.getElementById('main-content');
            const progressBar = document.getElementById('progress-bar');
            const progressContainer = document.getElementById('progress-container');
            const retryButton = document.getElementById('retry-button');

            let progressInterval = null;
            let loadingTimeout = null;

            function showMainContent() {
                // Clear any running timers
                clearInterval(progressInterval);
                clearTimeout(loadingTimeout);

                splashScreen.style.display = 'none';
                mainContent.style.display = 'block';
            }

            function startLoading() {
                let width = 0;
                // Reset UI
                retryButton.style.display = 'none';
                progressContainer.style.display = 'block';
                progressBar.style.width = '0%';

                progressInterval = setInterval(() => {
                    // Simulate loading progress
                    width += Math.random() * 10;
                    progressBar.style.width = width + '%';

                    // Simulate a random failure (e.g., 10% chance)
                    if (Math.random() < 0.1 && width < 80) {
                        clearInterval(progressInterval);
                        progressContainer.style.display = 'none';
                        retryButton.style.display = 'block';
                        return; // Stop loading
                    }

                    if (width >= 100) {
                        clearInterval(progressInterval);
                        progressBar.style.width = '100%';
                        // Wait a moment after completion before transitioning
                        loadingTimeout = setTimeout(showMainContent, 500);
                    }
                }, 200); // Update progress every 200ms

                // Set a max timeout for the splash screen
                loadingTimeout = setTimeout(showMainContent, 5000);
            }

            // Allow user to click to skip
            splashScreen.addEventListener('click', () => {
                // Don't skip if the retry button is the source of the click
                if (event.target !== retryButton) {
                    showMainContent();
                }
            });

            // Retry button functionality
            retryButton.addEventListener('click', (event) => {
                event.stopPropagation(); // Prevent the splash screen click listener from firing
                startLoading();
            });

            // Initial start
            startLoading();
        });
    </script>
</body>
</html>