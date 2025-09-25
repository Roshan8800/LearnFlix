<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Panel</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
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

    <script>
        document.getElementById('showTimeBtn').addEventListener('click', function() {
            const now = new Date();
            document.getElementById('timeContainer').innerText = 'Current Time: ' + now.toLocaleTimeString();
        });
    </script>
</body>
</html>