<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
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

        /* Original Admin Panel Styles */
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
        <img src="data:image/jpeg;base64,/9j/4QB8RXhpZgAATU0AKgAAAAgABAEAAAQAAAABAAAEYAEBAAQAAAABAAAEYIdpAAQAAAABAAAA
PgESAAMAAAABAAAAAAAAAAAAApKGAAIAAAAYAAAAXJIIAAMAAAABAAAAAAAAAABBU0NJSQAAAE5J
RDpTSVpFOjg1NyBrQgD/4AAQSkZJRgABAQAAAQABAAD/4gHYSUNDX1BST0ZJTEUAAQEAAAHIAAAA
AAQwAABtbnRyUkdCIFhZWiAH4AABAAEAAAAAAABhY3NwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAQAA9tYAAQAAAADTLQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAlkZXNjAAAA8AAAACRyWFlaAAABFAAAABRnWFlaAAABKAAAABRiWFlaAAABPAAA
ABR3dHB0AAABUAAAABRyVFJDAAABZAAAAChnVFJDAAABZAAAAChiVFJDAAABZAAAAChjcHJ0AAAB
jAAAADxtbHVjAAAAAAAAAAEAAAAMZW5VUwAAAAgAAAAcAHMAUgBHAEJYWVogAAAAAAAAb6IAADj1
AAADkFhZWiAAAAAAAABimQAAt4UAABjaWFlaIAAAAAAAACSgAAAPhAAAts9YWVogAAAAAAAA9tYA
AQAAAADTLXBhcmEAAAAAAAQAAAACZmYAAPKnAAANWQAAE9AAAApbAAAAAAAAAABtbHVjAAAAAAAA
AAEAAAAMZW5VUwAAACAAAAAcAEcAbwBvAGcAbABlACAASQBuAGMALgAgADIAMAAxADb/2wBDABAL
DA4MChAODQ4SERATGCgaGBYWGDEjJR0oOjM9PDkzODdASFxOQERXRTc4UG1RV19iZ2hnPk1xeXBk
eFxlZ2P/2wBDARESEhgVGC8aGi9jQjhCY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj
Y2NjY2NjY2NjY2NjY2NjY2P/wAARCARgBGADASIAAhEBAxEB/8QAGwAAAQUBAQAAAAAAAAAAAAAA
AAECAwQFBgf/xABMEAABAwIDBAcFBgQEBAYCAQUBAAIDBBESITEFQVFhEyJxgZGhsQYywdHwFCNC
UnLhJDNi8RU0Q4JTc5KyFiU1Y6LCRIPSB1STlOL/xAAZAQEAAwEBAAAAAAAAAAAAAAAAAQIDBAX/
xAArEQEBAAICAgIDAQACAwADAQEAAQIRAzESIQRBEzJRImFxFDNCI1KRgbH/2gAMAwEAAhEDEQA/
AO/QhCAQhCAQhCAQhCAQhCAQhCAQhCAQhCAQhCAQhCAQhCAQhCAQhCAQhCAQhCAQhCAQhCAQhCAQ
hCAQhCAQhCAQhCAQhCAQhCAQhCAQhCAQhCAQhCAQhCAQhCAQhCAQhCAQhCAQhCAQhCAQhCAQhCAQ
hCAQhCAQhCAQhCAQhCAQhCAQhCAQhCAQhCAQhCAQhCAQhCAQhCAQhCAQhCAQhCAQhCAQhCAQhCAQ
hCAQhCAQhCAQhCAQhCAQhCAQhCBEJUIBCEIBCEIBCEIBCEIBCEIEQlSKAqEIUgQhCAQhCAQhCAQh
CAQhCAQhCAQhKgRCEIBCEIBCEIBCEIBCVIgEIQgEIQgEIQgEISoEQhCAQhCAQlQgRCVIgEIQgEIQg
EJUIEQlSIBCEIBCVIgEIQgEIQgEqEIEQlSIBCEIBCEIBCVCBEIQgEIQgEJUiAQhCAQhCAQhCAQhCA
QhCAQhCAQhCAQhCAQhCAQhKgRCEIBCEIBCEIBCEIBCEIBCEIBCEIBCEIBCEIBCEIBCEIBC
EIBCEIBCEIBCEIBCEIBCEIBCEIBCEIBCEKAIQhAIQhSBIlQgEIQgEqRKgEiVCBEJUIEQlSIBCVIgE
qRKgEiVIgVCEIEQhCAQhCAQhCAQhCBUiVIgEJUiBUJEqBEJUiAQhCBUiVIgEIQgVCRCBUiVIgVCRC
BUJEIFQkQgEqRCAQhCASpEIBCEqBEJUIEQhCBUiEqBEIQgEIQgEIQgEIQgEIQgEIQgEIQgEISoEQlS
IBCVIgEISoEQhCAQhCAQhKgRCEIBCEIBCEIBKhCASIQgEqRCAQhKgEISIFSIQgEIQgEIQgEIQgEIQg
EIQgEIQgEIQgEIQgEIQgEIQoAhCEAhCEAhCEAhCEAhCFIEIQgEIQgVIhCASoQgRCEIBKhC
AQhCAQhCAQhCBEIQgEqRCBUiVCAQkQgEIQgEIQgVCEIBIhKgEiEIBCEIBCEqASIQgEIQgEIQgEIQg
EIQgEIQgEISoEQhCAQhCBUISIFQhCBEISoBIlSIBCVIgEJUiAQoairgprdLIGk6DUnuWbP7QQsy
jie88yAqZZ4490bCFzcvtBUuv0cccY53cVSk2pXTyCNtQ8vdo1vVA5m25Z3nx+kbdhjZ0mDEMdr4
b524pVlez9P0dPJM9xfJK/N51IGXrday1xu5tJEJUisBCVCBEqRCAQhCAQhCAQhCAQhCAQhCAQhC
ASpEIBCEIFQkQgEIQgEIQgEIQgEIQgEIQgEIQgEIQgEIQgEIQgEIQgEIQgEIQgEIQgEIQgEIQoAhC
EAhCEAhCEAkSpECoQhSBCEIBCVIgVCEIBIhCAQhKgRCEIBKkSoEQhCASpEIBCEqBEIQgVIhCAQh
CAQhCBUIQgEiEIBCEIFSIQgEIQgEqRCAQhCAQhCAQhCAQmvkZG273Bo4k2VKXaT
WzKMOkPIWHmq3OY90X02WVkMZfI4NaMySsWfa81iRhibyFysmaeeumDC5zhq6590LG88+kbdix4k
Y17TdrhcHknKKm/y0X6B6KRbxJUiVCkCEIQCEIQCEIQCEJEATYXKztlTCaWreL4Xy4mniLW+Cr7Z
2hcGjpzd7zhceHJO2XaOoawaFuEfXcufLklzkg2EISLoAsLam3MBdFSEEjIya+HzRt3aeHFSQu3fe
OHoucc/NcvLy+/HFFqcyOcS97i5zjmSblRveL35KMvs1V3yOfII4wXyONg0akrn1tCcPfJI2KFuOR
/utvbtJ4ALWp6QUkRzxPcLySW97kOASbOoRRxFzyHzP99w9ByHnr2WXB0nVb+I4Qe3JW/4g3aCPo
qGFlrdQE9pzKnQBYADclXfJqLBCEKQJEIQKhCEAkSpECpEIQCEXTOnivbpWX/UFGw9CAQdChSBCEI
BCEIBCEIBCEIBCEIBCEIBCEIBCEIBCEIBCEIBCEIBCEIBCEIBCEIBCEIBCEIBCEIBCEKAIQhAIQhA
IQhAIQhAIQhAIQhAIQhSBCEIBCEIFSJUiAQhCASpEqBEIQgEqRCAQhCAQhCASoQgRCEIBCEIBCEI
BCEIBCEIBCVCBEIQgEIQgFDDUxzTSxN96M2PNNrqkUtOX/jOTRzXN01VJDVGRp6wzz38brHk5fHK
QdYhR
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
Twu/iJBe4/AO
Pbw8eCreyzI/t0bAHGQjETwA+gr4erKfbuFxntXtQ1FT9khd93E6xtvfv8NPFdJtXaAotmT1DT12
3Yy+92gXm75bOzJNt5Oq35ct+ojK6WMYFyTpko5KkN5kmwHHgpaHZ9XtGxjYGQk3Mr/d7uPpzXR7
P2XTbPONgMs++V+vdw7lh6nasjKodh1FTaSsLoGHPB+N3/8AH17FvQUVPTRdHCwMZwG/md5PapcV
9R5pwlp2i8jixt7XzOdr2Vd7Xxx3dQ1kJkkbFGOs7fbJo4lbUETIIWRRizWiwWUytgjsWPeI2O/m
ZYXk2Fj3ny5K9QVbauLE03tvGhG5dPFqf9tLx3GbWkIQt1CpnSM6XosXXLcQHEJy5z2lqpKevpXw
uwyRsc6/aRl5FUyy8ZsdGhZuytsQ7QYGutHONWHf2LSVpZfcAhCFIEIQgEIQgEIQgEIQgEIQgEIQ
gEIQgEIQgEIQgEIQoAhCEAhCEAhCEAhCEAhCEAhCEAhCEAkSpECoQhSBCEIBCVIgVCRKgEiEIFSI
QgVIlSIBCEIBCEIBCEIBCEIBCEIIaucU8Dn79GjmsF8hcXFxuTndXNrz4qhsQOTRn2lZj3WXFzZb
y0inOdcXRRu+5eOErh6H4qLFcWRRuynbvEl//i1YoWnHNo71b2fOYahrSepIcJ7d31zVHFnz0Su
dYDNWxvjdpdMqFbU4j0bT1fxHismP2jihdJS1TXxyNOES2u03FwcyPIEkjbK0Ojc18ZHvNNwujk5
dzUNnnrDk44QpsfR9catNwoQbyMA0aC4/Xeh7sWS5t+xtuayeEtcMTJG2I4grz+SN9PPLTSe9C8t
7efxXeUL8dFC7iwLl/a2l6Cv
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
oBCEIBKhCAQkQgVCEIBCEIBCEIP//Z" alt="Sahu Family Logo" id="logo">
        <p id="tagline">Sahu Family</p>
        <div id="progress-container">
            <div id="progress-bar"></div>
        </div>
        <button id="retry-button" style="display: none;">Retry</button>
    </div>

    <div id="main-content" style="display: none;">
        <header>
            <div class="container">
                <h1>Admin Panel</h1>
            </div>
        </header>

        <div class="container content">
            <h2>Welcome, Admin!</h2>
            <p>This is the admin panel. You can manage users, view statistics, and perform other administrative tasks here.</p>

            <?php
                // Simple PHP example
                $users = ["Alice", "Bob", "Charlie"];
                echo "<h3>Registered Users:</h3>";
                echo "<ul>";
                foreach ($users as $user) {
                    echo "<li>" . htmlspecialchars($user) . "</li>";
                }
                echo "</ul>";
            ?>

            <button id="showAlertBtn">Click Me</button>
        </div>

        <footer>
            <p>Admin Panel &copy; 2024</p>
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

            // --- Original Admin Panel Logic ---
            const showAlertBtn = document.getElementById('showAlertBtn');
            if(showAlertBtn) {
                showAlertBtn.addEventListener('click', function() {
                    alert('Hello from JavaScript!');
                });
            }
        });
    </script>
</body>
</html>