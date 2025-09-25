<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
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

    <script>
        document.getElementById('showAlertBtn').addEventListener('click', function() {
            alert('Hello from JavaScript!');
        });
    </script>
</body>
</html>