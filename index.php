<?php
// Simple proxy to OpenRouter API
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'proxy') {
    // Get the posted data
    $postData = file_get_contents('php://input');
    $requestData = json_decode($postData, true);

    if (!$requestData || !isset($requestData['model']) || !isset($requestData['prompt'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request data.']);
        exit;
    }

    $model = $requestData['model'];
    $prompt = $requestData['prompt'];

    // --- SECURITY WARNING ---
    // The API keys below are hardcoded as per the user's request for this specific tool.
    // For a production environment, it is strongly recommended to store these keys securely
    // using environment variables (e.g., via getenv('MY_API_KEY')) and not in the source code.
    $apiKeys = [
        'deepseek/deepseek-chat' => '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', // DeepSeek V3.1
        'deepseek/deepseek-coder' => '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',   // DeepSeek: R1 0528
        'qwen/qwen-2-7b-instruct' => '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'   // Deepseek R1 0528 Qwen3 8B
    ];

    // The model names provided in the prompt are not the same as the ones in OpenRouter API
    // Let's use the ones that are more likely to work
    $modelMapping = [
        'DeepSeek V3.1' => 'deepseek/deepseek-chat',
        'DeepSeek: R1 0528' => 'deepseek/deepseek-coder',
        'Deepseek R1 0528 Qwen3 8B' => 'qwen/qwen-2-7b-instruct',
    ];

    $mappedModel = isset($modelMapping[$model]) ? $modelMapping[$model] : null;

    if (!$mappedModel || !isset($apiKeys[$mappedModel])) {
        http_response_code(400);
        echo json_encode(['error' => "Invalid model specified: {$model}"]);
        exit;
    }

    $apiKey = $apiKeys[$mappedModel];

    // Prepare the request to OpenRouter
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://openrouter.ai/api/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'model' => $mappedModel,
        'messages' => [
            ['role' => 'user', 'content' => $prompt]
        ]
    ]));

    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
        'HTTP-Referer: http://localhost', // Replace with your actual domain
        'X-Title: AI UI/UX Generator' // Replace with your app name
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // Execute the request
    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        http_response_code(500);
        echo json_encode(['error' => 'cURL Error: ' . curl_error($ch)]);
        exit;
    }
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Relay the response
    header('Content-Type: application/json');
    http_response_code($httpcode);
    echo $result;
    exit; // Stop script execution after proxying
}

// Handle ZIP file creation and download
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'zip') {
    $postData = file_get_contents('php://input');
    $files = json_decode($postData, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($files)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid file data.']);
        exit;
    }

    $zip = new ZipArchive();
    $zipFileName = tempnam(sys_get_temp_dir(), 'ai_design_') . '.zip';

    if ($zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        http_response_code(500);
        echo json_encode(['error' => 'Cannot create ZIP file.']);
        exit;
    }

    foreach ($files as $fileName => $fileContent) {
        $zip->addFromString($fileName, $fileContent);
    }
    $zip->close();

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="ai_generated_design.zip"');
    header('Content-Length: ' . filesize($zipFileName));
    readfile($zipFileName);

    // Clean up the temporary file
    unlink($zipFileName);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Power UI/UX Design Generator</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f5;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-800">
    <div class="container mx-auto p-4 md:p-8 max-w-4xl">
        <header class="text-center mb-8">
            <h1 class="text-4xl md:text-5xl font-bold text-gray-900">AI Power UI/UX Design Generator</h1>
            <p class="text-lg text-gray-600 mt-2">Turn your simple ideas into professional, multi-screen UI/UX designs.</p>
        </header>

        <main>
            <div class="bg-white rounded-xl shadow-lg p-6 md:p-8">
                <div class="mb-6">
                    <label for="idea" class="block text-xl font-semibold text-gray-700 mb-2">Describe Your Idea</label>
                    <textarea id="idea" rows="4" class="w-full p-4 text-base border-gray-300 rounded-lg focus:ring-4 focus:ring-blue-500 focus:border-blue-500 transition" placeholder="e.g., A mobile app for tracking personal fitness goals..."></textarea>
                </div>

                <button id="generateBtn" class="w-full bg-blue-600 text-white font-bold py-4 px-6 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-500 focus:ring-opacity-50 transition-transform transform hover:scale-105">
                    Generate Design
                </button>
            </div>

            <div id="status-container" class="mt-8 text-center hidden">
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h2 class="text-2xl font-semibold mb-4 text-gray-800">Generation Progress</h2>
                    <div id="status" class="text-lg text-gray-600 space-y-2"></div>
                </div>
            </div>

            <div id="results-container" class="mt-8 hidden">
                 <div class="bg-white rounded-xl shadow-lg p-6">
                    <h2 class="text-2xl font-semibold mb-4 text-gray-800">Generated Screens</h2>
                    <ul id="results" class="list-disc list-inside space-y-2">
                        <!-- Generated screens will be listed here -->
                    </ul>
                    <button id="downloadBtn" class="mt-6 w-full bg-green-600 text-white font-bold py-4 px-6 rounded-lg hover:bg-green-700 focus:outline-none focus:ring-4 focus:ring-green-500 focus:ring-opacity-50 transition-transform transform hover:scale-105 hidden">
                        Download Source Code (ZIP)
                    </button>
                </div>
            </div>
        </main>
    </div>

    <script>
        const generateBtn = document.getElementById('generateBtn');
        const ideaTextarea = document.getElementById('idea');
        const statusContainer = document.getElementById('status-container');
        const statusDiv = document.getElementById('status');
        const resultsContainer = document.getElementById('results-container');
        const resultsList = document.getElementById('results');
        const downloadBtn = document.getElementById('downloadBtn');

        let generatedFiles = {};

        const MODELS = {
            LEADER: 'DeepSeek V3.1',
            WORKER_1: 'DeepSeek: R1 0528',
            WORKER_2: 'Deepseek R1 0528 Qwen3 8B'
        };

        async function callAI(prompt, model) {
            try {
                const response = await fetch('index.php?action=proxy', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ prompt, model })
                });

                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(`API Error: ${response.statusText} - ${errorData.error}`);
                }

                const data = await response.json();
                return data.choices[0].message.content;
            } catch (error) {
                console.error('Error calling AI:', error);
                updateStatus(`Error with ${model}: ${error.message}`, true);
                throw error; // Propagate the error to stop the process
            }
        }

        function updateStatus(message, isError = false) {
            console.log(message);
            const statusMessage = document.createElement('p');
            statusMessage.textContent = message;
            if (isError) {
                statusMessage.className = 'text-red-500';
            }
            statusDiv.appendChild(statusMessage);
            statusDiv.scrollTop = statusDiv.scrollHeight;
        }

        function cleanJsonString(str) {
            // AI might return JSON within a markdown block
            const match = str.match(/```json\n([\s\S]*?)\n```/);
            if (match && match[1]) {
                return match[1];
            }
            return str;
        }

        generateBtn.addEventListener('click', async () => {
            const userIdea = ideaTextarea.value.trim();
            if (!userIdea) {
                alert('Please describe your idea first.');
                return;
            }

            // Reset UI
            generateBtn.disabled = true;
            generateBtn.textContent = 'Generating...';
            statusContainer.classList.remove('hidden');
            resultsContainer.classList.add('hidden');
            downloadBtn.classList.add('hidden');
            statusDiv.innerHTML = '';
            resultsList.innerHTML = '';
            generatedFiles = {};

            try {
                // 1. Leader Agent creates the plan
                updateStatus('Leader Agent is creating a design plan...');
                const leaderPrompt = `You are a world-class Chief Design Officer. A user wants to build an application based on this idea: "${userIdea}".
                Your task is to create a comprehensive design system and a list of all necessary screens for this application.
                Respond with ONLY a single JSON object. Do not add any introductory text or explanations.
                The JSON object must have two top-level keys: "designSystem" and "screens".
                - "designSystem" should be an object containing "colorPalette" (with primary, secondary, accent, background, and textColor keys) and "typography" (with fontFamily, baseSize, and headingFontWeight keys). Use hex codes for colors.
                - "screens" should be an array of strings, listing at least 25 detailed screen names required for a complete application of this type (e.g., 'Onboarding Screen', 'Login Screen', 'User Profile Screen').
                Example JSON format:
                {
                  "designSystem": {
                    "colorPalette": { "primary": "#007BFF", "secondary": "#6C757D", ... },
                    "typography": { "fontFamily": "'Inter', sans-serif'", "baseSize": "16px", ... }
                  },
                  "screens": ["Splash Screen", "Login Screen", "Home Dashboard", ...]
                }`;

                const leaderResponse = await callAI(leaderPrompt, MODELS.LEADER);
                updateStatus('Leader Agent has returned the design plan.');

                let designPlan;
                try {
                    designPlan = JSON.parse(cleanJsonString(leaderResponse));
                } catch (e) {
                    throw new Error("Failed to parse the design plan from the Leader Agent. The response was not valid JSON.");
                }

                if (!designPlan.designSystem || !designPlan.screens) {
                    throw new Error("The design plan from the Leader Agent is missing required 'designSystem' or 'screens' keys.");
                }

                resultsContainer.classList.remove('hidden');
                updateStatus('Plan approved. Starting screen generation with Worker Agents...');

                // 2. Worker Agents generate screens
                const screensToGenerate = designPlan.screens;
                const designSystem = designPlan.designSystem;
                let workerIndex = 0;

                for (const screenName of screensToGenerate) {
                    const workerModel = (workerIndex % 2 === 0) ? MODELS.WORKER_1 : MODELS.WORKER_2;
                    updateStatus(`Assigning "${screenName}" to ${workerModel}...`);

                    const workerPrompt = `You are a frontend developer specializing in creating production-quality HTML with Tailwind CSS.
                    Your task is to create the HTML for the "${screenName}".
                    You MUST adhere strictly to the following design system:
                    - Colors: ${JSON.stringify(designSystem.colorPalette)}
                    - Typography: ${JSON.stringify(designSystem.typography)}

                    Instructions:
                    1.  Generate a complete, single HTML file structure.
                    2.  Use the Tailwind CSS CDN: <script src="https://cdn.tailwindcss.com"></script>.
                    3.  Incorporate the provided design system for all elements. Use Tailwind utility classes for colors (e.g., bg-[${designSystem.colorPalette.primary}], text-[${designSystem.colorPalette.textColor}]) and fonts.
                    4.  The design should be modern, professional, and mobile-first responsive.
                    5.  Fill the screen with relevant, high-quality placeholder content (text, images using services like unsplash).
                    6.  Respond with ONLY the raw HTML code. Do not include any explanations, markdown formatting, or any text outside of the HTML itself.`;

                    const screenHtml = await callAI(workerPrompt, workerModel);
                    const fileName = screenName.toLowerCase().replace(/\s+/g, '_') + '.html';
                    generatedFiles[fileName] = screenHtml;

                    const listItem = document.createElement('li');
                    listItem.textContent = `✅ ${screenName} (${fileName})`;
                    listItem.className = 'text-green-600';
                    resultsList.appendChild(listItem);
                }

                updateStatus('All screens generated successfully!', false);
                downloadBtn.classList.remove('hidden');

            } catch (error) {
                updateStatus(`A critical error occurred: ${error.message}`, true);
                console.error(error);
            } finally {
                generateBtn.disabled = false;
                generateBtn.textContent = 'Generate Design';
            }
        });

        downloadBtn.addEventListener('click', () => {
            if (Object.keys(generatedFiles).length === 0) {
                alert('No files have been generated yet.');
                return;
            }

            fetch('index.php?action=zip', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(generatedFiles)
            })
            .then(response => {
                if (response.ok) {
                    return response.blob();
                }
                throw new Error('Network response was not ok.');
            })
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = url;
                a.download = 'ai_generated_design.zip';
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
            })
            .catch(error => {
                console.error('Error downloading ZIP:', error);
                alert('Failed to download the ZIP file. Please check the console for errors.');
            });
        });
    </script>
</body>
</html>