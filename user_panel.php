<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Panel</title>
    <style>
        /* Splash Screen Styles */
        html, body {
            height: 100%;
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            overflow: hidden; /* Hide scrollbars initially */
        }

        #splash-screen {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            text-align: center;
            background-color: #fdfdfd;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 9999;
        }

        #logo {
            max-width: 150px;
            height: auto;
            animation: fadeIn 1.5s ease-in-out;
        }

        #tagline {
            font-size: 1.2em;
            color: #555;
            margin-top: 10px;
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

        @media (prefers-reduced-motion: reduce) {
            #logo, #tagline { animation: none; }
            #progress-bar { transition: none; }
        }

        /* Original User Panel Styles */
        body.loaded {
            overflow: auto; /* Restore scrollbars when content is loaded */
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
        }
        .container {
            width: 80%;
            margin: auto;
            overflow: hidden;
        }
        header {
            background: #333;
            color: #fff;
            padding-top: 30px;
            min-height: 70px;
            border-bottom: #77aaff 3px solid;
        }
        header h1 {
            text-align: center;
            text-transform: uppercase;
            margin: 0;
        }
        .content {
            padding: 20px;
            background: #fff;
            margin-top: 20px;
        }
        footer {
            background: #333;
            color: #fff;
            text-align: center;
            padding: 10px;
            margin-top: 20px;
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

    <div id="main-content" style="display: none;">
        <header>
            <div class="container">
                <h1>User Panel</h1>
            </div>
        </header>

        <div class="container content">
            <h2>Welcome, User!</h2>
            <p>This is your user panel. You can view your profile, see your activity, and manage your settings here.</p>

            <?php
                // Simple PHP example
                $user_data = [
                    "Username" => "TestUser",
                    "Email" => "testuser@example.com",
                    "MemberSince" => "2024-01-01"
                ];
                echo "<h3>Your Information:</h3>";
                echo "<ul>";
                foreach ($user_data as $key => $value) {
                    echo "<li><strong>" . htmlspecialchars($key) . ":</strong> " . htmlspecialchars($value) . "</li>";
                }
                echo "</ul>";
            ?>

            <button id="showTimeBtn">Show Current Time</button>
            <p id="timeContainer"></p>
        </div>

        <footer>
            <p>User Panel &copy; 2024</p>
        </footer>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // --- Splash Screen Logic ---
            const splashScreen = document.getElementById('splash-screen');
            const mainContent = document.getElementById('main-content');
            const progressBar = document.getElementById('progress-bar');
            const progressContainer = document.getElementById('progress-container');
            const retryButton = document.getElementById('retry-button');

            let progressInterval = null;
            let loadingTimeout = null;

            function showMainContent() {
                clearInterval(progressInterval);
                clearTimeout(loadingTimeout);

                if (splashScreen) {
                    splashScreen.style.display = 'none';
                }
                if (mainContent) {
                    mainContent.style.display = 'block';
                }
                document.body.classList.add('loaded');
            }

            function startLoading() {
                let width = 0;
                retryButton.style.display = 'none';
                progressContainer.style.display = 'block';
                progressBar.style.width = '0%';

                progressInterval = setInterval(() => {
                    width += Math.random() * 10;
                    progressBar.style.width = width + '%';

                    if (Math.random() < 0.1 && width < 80) {
                        clearInterval(progressInterval);
                        progressContainer.style.display = 'none';
                        retryButton.style.display = 'block';
                        return;
                    }

                    if (width >= 100) {
                        clearInterval(progressInterval);
                        progressBar.style.width = '100%';
                        loadingTimeout = setTimeout(showMainContent, 500);
                    }
                }, 200);

                loadingTimeout = setTimeout(showMainContent, 5000);
            }

            if (splashScreen) {
                splashScreen.addEventListener('click', (event) => {
                    if (event.target !== retryButton) {
                        showMainContent();
                    }
                });
            }

            retryButton.addEventListener('click', (event) => {
                event.stopPropagation();
                startLoading();
            });

            startLoading();

            // --- Original User Panel Logic ---
            const showTimeBtn = document.getElementById('showTimeBtn');
            const timeContainer = document.getElementById('timeContainer');
            if(showTimeBtn) {
                showTimeBtn.addEventListener('click', function() {
                    const now = new Date();
                    if(timeContainer) {
                        timeContainer.innerText = 'Current Time: ' + now.toLocaleTimeString();
                    }
                });
            }
        });
    </script>
</body>
</html>