<?php
/*
 * ====================================================================
 * PHP BACKEND LOGIC
 * ====================================================================
 * This section will handle API proxying and ZIP file generation.
 */

// Action controller
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    $action = $_GET['action'];

    if ($action === 'proxy') {
        handle_proxy();
    } elseif ($action === 'zip') {
        handle_zip();
    }
}

function handle_zip() {
    $requestBody = file_get_contents('php://input');
    $files = json_decode($requestBody, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($files) || empty($files)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid file data provided.']);
        exit;
    }

    $zip = new ZipArchive();
    $zipFileName = tempnam(sys_get_temp_dir(), 'ai_design_') . '.zip';

    if ($zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create ZIP archive.']);
        exit;
    }

    foreach ($files as $fileName => $fileContent) {
        // Sanitize filename one last time on the server
        $safeFileName = preg_replace('/[^a-zA-Z0-9_.-]/', '', $fileName);
        if (!empty($safeFileName)) {
            $zip->addFromString($safeFileName, $fileContent);
        }
    }
    $zip->close();

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="ai_generated_design.zip"');
    header('Content-Length: ' . filesize($zipFileName));
    header('Connection: close');

    readfile($zipFileName);

    // Clean up the temporary file
    unlink($zipFileName);
    exit;
}

function handle_proxy() {
    $requestBody = file_get_contents('php://input');
    $requestData = json_decode($requestBody, true);

    if (!$requestData || !isset($requestData['model']) || !isset($requestData['prompt'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request: Missing model or prompt.']);
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

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://openrouter.ai/api/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'model' => $mappedModel,
        'messages' => [['role' => 'user', 'content' => $prompt]]
    ]));

    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
        'HTTP-Referer: http://localhost', // Replace with your actual domain in production
        'X-Title: AI UI-UX Generator'      // Replace with your app name
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        http_response_code(500);
        echo json_encode(['error' => 'cURL Error: ' . curl_error($ch)]);
    } else {
        header('Content-Type: application/json');
        http_response_code($httpcode);
        echo $result;
    }

    curl_close($ch);
    exit; // Stop script execution after handling the proxy request
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Power UI/UX Design Generator</title>
    <!-- This application uses Tailwind CSS via a CDN for styling. -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
            background-color: #111827; /* Dark background */
            color: #d1d5db; /* Light gray text */
        }
        .glass-card {
            background: rgba(31, 41, 55, 0.5); /* Semi-transparent dark card */
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        #status p {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            background-color: rgba(55, 65, 81, 0.5);
            border-left: 4px solid #3b82f6; /* Blue accent */
            margin-bottom: 0.5rem;
        }
        #status p.error {
            border-left-color: #ef4444; /* Red accent for errors */
            color: #fca5a5;
        }
    </style>
</head>
<body class="antialiased">
    <div class="container mx-auto p-4 md:p-8 max-w-4xl">
        <header class="text-center mb-10">
            <h1 class="text-4xl md:text-5xl font-bold text-white">AI Power UI/UX Design Generator</h1>
            <p class="text-lg text-gray-400 mt-2">Transform your ideas into professional, multi-screen UI/UX designs with AI.</p>
        </header>

        <main>
            <div class="glass-card rounded-xl shadow-2xl p-6 md:p-8">
                <div class="mb-6">
                    <label for="idea" class="block text-xl font-semibold text-gray-200 mb-3">1. Describe Your Application Idea</label>
                    <textarea id="idea" rows="4" class="w-full p-4 text-base bg-gray-900 text-gray-200 border-gray-600 rounded-lg focus:ring-4 focus:ring-blue-500 focus:border-blue-500 transition-all duration-300" placeholder="e.g., A mobile app for discovering local hiking trails with user reviews..."></textarea>
                </div>

                <button id="generateBtn" class="w-full bg-blue-600 text-white font-bold py-4 px-6 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-500 focus:ring-opacity-50 transition-all duration-300 transform hover:scale-105 disabled:bg-gray-500 disabled:cursor-not-allowed">
                    <span class="text-lg">Generate Design</span>
                </button>
            </div>

            <div id="progress-container" class="mt-8 hidden">
                <div class="glass-card rounded-xl shadow-2xl p-6">
                    <h2 class="text-2xl font-semibold mb-4 text-white">2. Generation Progress</h2>
                    <div id="status" class="text-sm text-gray-300 space-y-2 max-h-60 overflow-y-auto pr-2"></div>
                </div>
            </div>

            <div id="results-container" class="mt-8 hidden">
                 <div class="glass-card rounded-xl shadow-2xl p-6">
                    <h2 class="text-2xl font-semibold mb-4 text-white">3. Generated Screens</h2>
                    <ul id="results" class="list-disc list-inside space-y-2 text-gray-300">
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
        /*
         * ====================================================================
         * JAVASCRIPT APPLICATION LOGIC
         * ====================================================================
         */
        const generateBtn = document.getElementById('generateBtn');
        const ideaTextarea = document.getElementById('idea');
        const progressContainer = document.getElementById('progress-container');
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

        function updateStatus(message, isError = false) {
            console.log(message);
            const statusMessage = document.createElement('p');
            statusMessage.textContent = message;
            if (isError) {
                statusMessage.className = 'error';
            }
            statusDiv.appendChild(statusMessage);
            statusDiv.scrollTop = statusDiv.scrollHeight; // Auto-scroll to the latest message
        }

        async function callAI(prompt, model, retries = 3) {
            for (let i = 0; i < retries; i++) {
                try {
                    const response = await fetch('index.php?action=proxy', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: JSON.stringify({ prompt, model })
                    });

                    if (!response.ok) {
                        const errorData = await response.json().catch(() => ({ error: 'Failed to parse error response.' }));
                        throw new Error(`API Error (${response.status}): ${errorData.error || response.statusText}`);
                    }

                    const data = await response.json();
                    if (!data.choices || !data.choices[0] || !data.choices[0].message) {
                        throw new Error('Invalid response structure from AI.');
                    }
                    return data.choices[0].message.content;
                } catch (error) {
                    if (i === retries - 1) {
                        updateStatus(`Error with ${model}: ${error.message}. No retries left.`, true);
                        throw error;
                    }
                    updateStatus(`Error with ${model}: ${error.message}. Retrying (${i + 1}/${retries-1})...`, true);
                    await new Promise(res => setTimeout(res, 2000)); // Wait 2 seconds before retrying
                }
            }
        }

        function cleanJsonString(str) {
            const match = str.match(/```json\n([\s\S]*?)\n```/);
            return match ? match[1] : str;
        }

        generateBtn.addEventListener('click', async () => {
            const userIdea = ideaTextarea.value.trim();
            if (!userIdea) {
                alert('Please describe your application idea first.');
                return;
            }

            // --- 1. Reset UI ---
            generateBtn.disabled = true;
            generateBtn.querySelector('span').textContent = 'Generating... Please Wait';
            progressContainer.classList.remove('hidden');
            resultsContainer.classList.add('hidden');
            downloadBtn.classList.add('hidden');
            statusDiv.innerHTML = '';
            resultsList.innerHTML = '';
            generatedFiles = {};

            try {
                // --- 2. Leader Agent Creates the Plan ---
                updateStatus('🚀 Engaging Leader Agent to create a design blueprint...');
                const leaderPrompt = `You are a world-class Chief Design Officer. A user wants to build an application based on this idea: "${userIdea}".
                Your task is to create a comprehensive design system and a list of all necessary screens for this application.
                Respond with ONLY a single, raw JSON object. Do not add any introductory text, explanations, or markdown formatting.
                The JSON object must have two top-level keys: "designSystem" and "screens".
                - "designSystem" must be an object containing "colorPalette" (with primary, secondary, accent, background, and textColor hex codes) and "typography" (with fontFamily, baseSize, and headingFontWeight).
                - "screens" must be an array of at least 25 detailed, unique screen names required for a complete application of this type (e.g., 'Onboarding Screen', 'Login Screen', 'User Profile Screen').`;

                const leaderResponse = await callAI(leaderPrompt, MODELS.LEADER);
                updateStatus('✅ Blueprint received from Leader Agent.');

                let designPlan;
                try {
                    designPlan = JSON.parse(cleanJsonString(leaderResponse));
                } catch (e) {
                    console.error("Raw response from leader:", leaderResponse);
                    throw new Error("Failed to parse the JSON design plan from the Leader Agent. The response was not valid JSON.");
                }

                if (!designPlan.designSystem || !designPlan.screens || designPlan.screens.length < 1) {
                    throw new Error("The design plan is invalid or missing 'designSystem' or 'screens'.");
                }

                resultsContainer.classList.remove('hidden');
                updateStatus('📋 Plan approved. Deploying Worker Agents to generate screens...');

                // --- 3. Worker Agents Generate Screens ---
                const screensToGenerate = designPlan.screens;
                const designSystem = designPlan.designSystem;

                for (let i = 0; i < screensToGenerate.length; i++) {
                    const screenName = screensToGenerate[i];
                    const workerModel = (i % 2 === 0) ? MODELS.WORKER_1 : MODELS.WORKER_2;
                    updateStatus(`[${i+1}/${screensToGenerate.length}] Assigning "${screenName}" to ${workerModel}...`);

                    const workerPrompt = `You are a frontend developer creating production-quality HTML with Tailwind CSS.
                    Your task is to create the complete HTML for the "${screenName}".
                    You MUST adhere strictly to this design system:
                    - Design System: ${JSON.stringify(designSystem)}

                    Instructions:
                    1. Generate a complete, single HTML file structure including <!DOCTYPE>, <html>, <head>, and <body>.
                    2. Inside <head>, include the Tailwind CSS CDN: <script src="https://cdn.tailwindcss.com"></script>.
                    3. Use Tailwind utility classes for all styling. For colors, use arbitrary values like \`bg-[${designSystem.colorPalette.primary}]\` and \`text-[${designSystem.colorPalette.textColor}]\`.
                    4. The design must be modern, professional, and mobile-first responsive.
                    5. Fill the screen with relevant, high-quality placeholder content (text, and images from services like Pexels or Unsplash).
                    6. Respond with ONLY the raw HTML code. Do not include any explanations, markdown formatting, or any text outside of the HTML itself.`;

                    const screenHtml = await callAI(workerPrompt, workerModel);
                    const fileName = screenName.toLowerCase().replace(/[^a-z0-9]+/g, '_') + '.html';
                    generatedFiles[fileName] = screenHtml;

                    const listItem = document.createElement('li');
                    listItem.textContent = `✅ ${screenName} (${fileName})`;
                    listItem.className = 'text-green-400';
                    resultsList.appendChild(listItem);
                }

                // --- 4. Finalize ---
                updateStatus('🎉 All screens generated successfully!', false);
                downloadBtn.classList.remove('hidden');

            } catch (error) {
                updateStatus(`A critical error occurred: ${error.message}`, true);
                console.error(error);
            } finally {
                generateBtn.disabled = false;
                generateBtn.querySelector('span').textContent = 'Generate Design';
            }
        });

        downloadBtn.addEventListener('click', () => {
            if (Object.keys(generatedFiles).length === 0) {
                alert('No files have been generated to download.');
                return;
            }

            // Show loading state on download button
            downloadBtn.textContent = 'Packaging...';
            downloadBtn.disabled = true;

            fetch('index.php?action=zip', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(generatedFiles)
            })
            .then(response => {
                if (response.ok) {
                    return response.blob();
                }
                // Try to get error message from backend
                return response.json().then(errorData => {
                    throw new Error(errorData.error || 'ZIP generation failed on the server.');
                });
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
                a.remove();
            })
            .catch(error => {
                console.error('Error downloading ZIP:', error);
                updateStatus(`Failed to download ZIP: ${error.message}`, true);
            })
            .finally(() => {
                // Restore button state
                downloadBtn.textContent = 'Download Source Code (ZIP)';
                downloadBtn.disabled = false;
            });
        });
    </script>
</body>
</html>