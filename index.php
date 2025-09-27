<?php
// Start the session to maintain state across requests
session_start();

// --- API & Backend Logic ---

/**
 * Sends a request to the OpenRouter API.
 * @param array $messages The conversation history or prompt.
 * @param string $model The AI model to use.
 * @return string The AI's response text.
 * @throws Exception If the API call fails.
 */
function call_openrouter_api(array $messages, string $model_name = 'deepseek/deepseek-chat'): string {
    if (empty($_SESSION['api_key'])) {
        throw new Exception("API Key not set. Please set it in the Settings page.");
    }
    $apiKey = $_SESSION['api_key'];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://openrouter.ai/api/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'model' => $model_name,
        'messages' => $messages
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);

    $result = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        throw new Exception('cURL Error: ' . curl_error($ch));
    }

    if ($httpcode >= 400) {
        if ($httpcode === 401) {
            throw new Exception("Authentication failed (Error 401). Your API Key is likely invalid or expired. Please verify it in Settings.");
        }
        throw new Exception("API request failed with status code {$httpcode}: {$result}");
    }

    curl_close($ch);

    $response = json_decode($result, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($response['choices'][0]['message']['content'])) {
        throw new Exception('Failed to decode API response or invalid response structure.');
    }

    return $response['choices'][0]['message']['content'];
}

// --- Request Handling ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    // It's better to get action from a dedicated field, not from the GET param for POST requests.
    // But for this simple app, we will use a field in the JSON body.
    $postData = json_decode(file_get_contents('php://input'), true);
    $action = $postData['action'] ?? null;

    if ($action === 'chat') {
        try {
            // Initialize conversation history if it doesn't exist
            if (!isset($_SESSION['conversation'])) {
                $_SESSION['conversation'] = [];
            }

            // Add user message to history
            $userMessage = $postData['message'] ?? '';
            if (empty($userMessage)) {
                 throw new Exception("Message cannot be empty.");
            }

            $_SESSION['conversation'][] = ['role' => 'user', 'content' => $userMessage];

            // Get AI response
            $system_prompt = ['role' => 'system', 'content' => 'You are a helpful AI Design Assistant. Your goal is to understand what kind of application the user wants to build. Keep your responses concise.'];
            $messages_to_send = array_merge([$system_prompt], $_SESSION['conversation']);

            $ai_response = call_openrouter_api($messages_to_send, 'deepseek/deepseek-chat');

            // Add AI response to history
            $_SESSION['conversation'][] = ['role' => 'assistant', 'content' => $ai_response];

            echo json_encode(['success' => true, 'conversation' => $_SESSION['conversation']]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit; // Stop script execution
    }
    // Handle Design Generation
    elseif ($action === 'generate_design') {
        try {
            if (empty($_SESSION['conversation'])) {
                throw new Exception("The conversation is empty. Please chat with the AI first.");
            }

            $model_for_design = 'deepseek/deepseek-coder';

            $conversation_text = implode("\n", array_map(function($msg) {
                return "{$msg['role']}: {$msg['content']}";
            }, $_SESSION['conversation']));

            $design_prompt = [
                [
                    'role' => 'system',
                    'content' => "You are a world-class frontend developer specializing in creating single-file, production-quality HTML with Tailwind CSS. Your task is to generate the complete HTML for a web application based on the following conversation. The design should be modern, professional, and mobile-first responsive. Fill the screen with relevant, high-quality placeholder content (text, images from unsplash.com, etc.). Respond with ONLY the raw HTML code. Do not include any explanations, markdown formatting, or any text outside of the HTML itself."
                ],
                [
                    'role' => 'user',
                    'content' => "Here is the conversation history. Generate the complete HTML code for the application described.\n\n---\n\n{$conversation_text}"
                ]
            ];

            $generated_html = call_openrouter_api($design_prompt, $model_for_design);

            // Clean up the response if it's wrapped in markdown
            if (preg_match('/```html\s*([\s\S]+?)\s*```/', $generated_html, $matches)) {
                $generated_html = $matches[1];
            }

            $_SESSION['generated_preview_html'] = $generated_html;
            $_SESSION['final_code'] = null; // Reset final code when new preview is generated

            echo json_encode(['success' => true, 'message' => 'Design generated successfully.']);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
    // Handle "Accept Design" from Preview screen
    elseif ($action === 'accept_design') {
        if (!empty($_SESSION['generated_preview_html'])) {
            $_SESSION['final_code'] = $_SESSION['generated_preview_html'];
        }
        // This action happens via a standard form POST, so no JSON response is needed.
        // The browser will be redirected to the 'code' screen by the form's action attribute.
        return;
    }
}

// Handle POST actions from regular forms (like settings)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'save_api_key') {
        $apiKey = trim($_POST['api_key'] ?? '');
        $_SESSION['api_key'] = $apiKey;
        $_SESSION['settings_message'] = 'API Key saved successfully!';
        // Redirect back to settings page to show the message and prevent form resubmission
        header('Location: index.php?screen=settings');
        exit;
    }
}


// Handle Download ZIP action (as a GET request for simplicity)
if (isset($_GET['action']) && $_GET['action'] === 'download_zip') {
    if (!empty($_SESSION['final_code'])) {
        $zip = new ZipArchive();
        $zipFileName = tempnam(sys_get_temp_dir(), 'ai_design_') . '.zip';

        if ($zip->open($zipFileName, ZipArchive::CREATE) === TRUE) {
            $zip->addFromString('index.html', $_SESSION['final_code']);
            $zip->close();

            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="ai_generated_design.zip"');
            header('Content-Length: ' . filesize($zipFileName));
            readfile($zipFileName);

            unlink($zipFileName);
            exit;
        }
    }
    // If no code, just fall through to rendering the page normally.
}


// --- Page Routing ---
// We need to handle the POST action from the preview screen before routing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'accept_design') {
     if (!empty($_SESSION['generated_preview_html'])) {
        $_SESSION['final_code'] = $_SESSION['generated_preview_html'];
    }
}
$screen = $_GET['screen'] ?? 'chat';
if (!in_array($screen, ['chat', 'preview', 'code', 'settings'])) {
    $screen = 'chat';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Application - <?php echo ucfirst($screen); ?> View</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #111827; color: #e5e7eb; }
    </style>
</head>
<body class="antialiased">
    <!-- Navigation Bar -->
    <nav class="bg-gray-800/50 backdrop-blur-sm p-4 sticky top-0 z-20 shadow-lg">
        <div class="max-w-5xl mx-auto flex justify-center items-center gap-4 md:gap-8">
            <?php
                $preview_enabled = !empty($_SESSION['generated_preview_html']);
                $code_enabled = !empty($_SESSION['final_code']);
            ?>
            <a href="index.php?screen=chat" class="text-sm md:text-base font-medium transition-colors <?php echo $screen === 'chat' ? 'text-blue-400' : 'text-gray-400 hover:text-white'; ?>">
                AI Chat
            </a>
            <span class="text-gray-600">|</span>
            <a href="<?php echo $preview_enabled ? 'index.php?screen=preview' : '#'; ?>" class="text-sm md:text-base font-medium transition-colors
                <?php echo $screen === 'preview' ? 'text-blue-400' : ($preview_enabled ? 'text-gray-400 hover:text-white' : 'text-gray-600 cursor-not-allowed'); ?>">
                Design Preview
            </a>
            <span class="text-gray-600">|</span>
            <a href="<?php echo $code_enabled ? 'index.php?screen=code' : '#'; ?>" class="text-sm md:text-base font-medium transition-colors
                <?php echo $screen === 'code' ? 'text-blue-400' : ($code_enabled ? 'text-gray-400 hover:text-white' : 'text-gray-600 cursor-not-allowed'); ?>">
                View Code
            </a>
            <span class="text-gray-600 hidden md:inline">|</span>
            <a href="index.php?screen=settings" class="text-sm md:text-base font-medium transition-colors <?php echo $screen === 'settings' ? 'text-blue-400' : 'text-gray-400 hover:text-white'; ?>">
                Settings
            </a>
        </div>
    </nav>

    <!-- Main Content Area -->
    <main class="flex-grow">
        <?php if ($screen === 'chat'): ?>
            <div id="chat-screen" class="flex flex-col h-[calc(100vh-64px)] max-w-3xl mx-auto">
                <div id="chat-container" class="flex-grow p-4 space-y-6 overflow-y-auto">
                    <!-- Messages will be dynamically inserted here by JavaScript -->
                </div>
                <footer class="bg-gray-800 p-4">
                    <?php if (empty($_SESSION['api_key'])): ?>
                        <div class="text-center p-4 bg-yellow-900/50 rounded-lg border border-yellow-700">
                            <p class="font-bold text-yellow-300">API Key Not Set</p>
                            <p class="text-yellow-400 text-sm mt-1">
                                Please go to the <a href="index.php?screen=settings" class="underline font-semibold hover:text-white">Settings</a> page to add your OpenRouter API key to enable the chat.
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="flex items-center gap-4">
                            <button id="generate-design-btn" class="bg-green-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-green-700 transition-colors disabled:bg-gray-500 disabled:cursor-not-allowed flex-shrink-0">
                               Generate Design
                            </button>
                            <form id="chat-form" class="flex items-center gap-3 w-full">
                                <input id="message-input" type="text" placeholder="Type your message..." class="w-full bg-gray-900 text-white p-3 rounded-lg border border-gray-600 focus:ring-2 focus:ring-blue-500 focus:outline-none transition" autocomplete="off">
                                <button id="send-btn" type="submit" class="bg-blue-600 text-white rounded-lg p-3 hover:bg-blue-700 transition-colors flex-shrink-0 disabled:bg-gray-500 disabled:cursor-not-allowed">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                                </button>
                            </form>
                        </div>
                        <p id="error-message" class="text-red-400 text-sm mt-2 text-center h-4"></p>
                    <?php endif; ?>
                </footer>
            </div>
        <?php elseif ($screen === 'preview'): ?>
            <div id="preview-screen" class="flex flex-col items-center justify-center p-4">
                <header class="w-full max-w-3xl mx-auto mb-8 text-center">
                    <h1 class="text-3xl font-bold text-white">Generated Design Preview</h1>
                </header>

                <?php if (!empty($_SESSION['generated_preview_html'])): ?>
                    <p class="text-gray-400 mb-8">Here is the design the AI has created for you.</p>
                    <!-- Mobile Mockup -->
                    <div class="w-80 h-[560px] bg-gray-800 rounded-[40px] border-[12px] border-gray-900 shadow-2xl overflow-hidden">
                        <iframe srcdoc="<?php echo htmlspecialchars($_SESSION['generated_preview_html']); ?>" class="w-full h-full" sandbox="allow-scripts allow-same-origin"></iframe>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-8 flex gap-4">
                        <a href="index.php?screen=chat" class="bg-red-600 text-white font-bold py-3 px-8 rounded-lg hover:bg-red-700 transition-colors transform hover:scale-105">
                            Go Back & Regenerate
                        </a>
                        <form method="POST" action="index.php?screen=code">
                             <input type="hidden" name="action" value="accept_design">
                             <button type="submit" class="bg-green-600 text-white font-bold py-3 px-8 rounded-lg hover:bg-green-700 transition-colors transform hover:scale-105">
                                Accept & View Code
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="text-center bg-gray-800 p-8 rounded-lg shadow-lg">
                        <h2 class="text-2xl font-bold text-white">No Design Generated Yet</h2>
                        <p class="text-gray-400 mt-4">Please go to the AI Chat screen to describe your application and generate a design.</p>
                        <a href="index.php?screen=chat" class="mt-6 inline-block bg-blue-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-blue-700 transition-colors">
                            Go to Chat
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php elseif ($screen === 'code'): ?>
            <div id="code-screen" class="flex flex-col items-center p-4">
                <header class="w-full max-w-4xl mx-auto mb-8 text-center">
                    <h1 class="text-3xl font-bold text-white">Final Source Code</h1>
                </header>

                <?php if (!empty($_SESSION['final_code'])): ?>
                    <div class="w-full max-w-4xl bg-gray-800 rounded-xl shadow-2xl overflow-hidden">
                        <div class="bg-gray-900 p-3 flex items-center justify-between">
                            <span class="text-gray-400 text-sm">index.html</span>
                            <button id="copy-btn" class="text-gray-400 hover:text-white transition-colors text-sm flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                                Copy Code
                            </button>
                        </div>
                        <pre class="max-h-[500px] overflow-y-auto p-4"><code id="code-block" class="language-html text-sm"><?php echo htmlspecialchars($_SESSION['final_code']); ?></code></pre>
                    </div>

                    <!-- Action Button -->
                    <div class="mt-8">
                        <a href="index.php?action=download_zip" class="bg-indigo-600 text-white font-bold py-3 px-8 rounded-lg hover:bg-indigo-700 transition-colors transform hover:scale-105">
                            Download ZIP
                        </a>
                    </div>
                <?php else: ?>
                     <div class="text-center bg-gray-800 p-8 rounded-lg shadow-lg">
                        <h2 class="text-2xl font-bold text-white">No Final Code Available</h2>
                        <p class="text-gray-400 mt-4">Please generate a design and accept it on the preview screen to view the final code.</p>
                        <a href="index.php?screen=preview" class="mt-6 inline-block bg-blue-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-blue-700 transition-colors">
                            Go to Preview
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php elseif ($screen === 'settings'): ?>
            <div id="settings-screen" class="flex flex-col items-center p-4">
                 <header class="w-full max-w-3xl mx-auto mb-8 text-center">
                    <h1 class="text-3xl font-bold text-white">Settings</h1>
                    <p class="text-gray-400 mt-2">Manage your API Key here.</p>
                </header>

                <div class="w-full max-w-md bg-gray-800 p-8 rounded-lg shadow-lg">
                    <?php
                        // Check for and display the success message
                        if (isset($_SESSION['settings_message'])):
                    ?>
                        <div class="mb-4 p-3 bg-green-900/50 border border-green-700 text-green-300 text-sm rounded-lg text-center">
                            <?php echo $_SESSION['settings_message']; ?>
                        </div>
                    <?php
                        // Unset the message so it doesn't show again
                        unset($_SESSION['settings_message']);
                        endif;
                    ?>

                    <form method="POST" action="index.php?screen=settings">
                        <input type="hidden" name="action" value="save_api_key">
                        <div class="mb-4">
                            <label for="api_key" class="block text-gray-300 text-sm font-bold mb-2">OpenRouter API Key</label>
                            <input type="password" name="api_key" id="api_key" value="<?php echo htmlspecialchars($_SESSION['api_key'] ?? ''); ?>" class="w-full bg-gray-900 text-white p-3 rounded-lg border border-gray-600 focus:ring-2 focus:ring-blue-500 focus:outline-none transition" placeholder="sk-or-v1-...">
                        </div>
                        <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-blue-700 transition-colors">
                            Save API Key
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <script>
    if (document.getElementById('chat-screen')) {
        const chatContainer = document.getElementById('chat-container');
        const chatForm = document.getElementById('chat-form');
        const messageInput = document.getElementById('message-input');
        const sendBtn = document.getElementById('send-btn');
        const errorMessage = document.getElementById('error-message');

        const renderConversation = (conversation) => {
            chatContainer.innerHTML = '';
            if (conversation.length === 0) {
                chatContainer.innerHTML = `
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center font-bold text-white flex-shrink-0">AI</div>
                    <div class="bg-gray-700 rounded-lg p-3 max-w-xs md:max-w-md">
                        <p class="text-sm">Hello! I am your AI Design Assistant. Describe the application you want to build, and I will create it for you.</p>
                    </div>
                </div>`;
                return;
            }
            conversation.forEach(msg => {
                const isUser = msg.role === 'user';
                const bubble = document.createElement('div');
                bubble.className = `flex items-start gap-3 ${isUser ? 'justify-end' : ''}`;
                bubble.innerHTML = `
                    ${!isUser ? '<div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center font-bold text-white flex-shrink-0">AI</div>' : ''}
                    <div class="${isUser ? 'bg-blue-600' : 'bg-gray-700'} rounded-lg p-3 max-w-xs md:max-w-md">
                        <p class="text-sm whitespace-pre-wrap">${msg.content}</p>
                    </div>
                    ${isUser ? '<div class="w-10 h-10 rounded-full bg-gray-600 flex items-center justify-center font-bold text-white flex-shrink-0">You</div>' : ''}
                `;
                chatContainer.appendChild(bubble);
            });
            chatContainer.scrollTop = chatContainer.scrollHeight;
        };

        const showLoadingBubble = (show = true) => {
            let loadingBubble = document.getElementById('loading-bubble');
            if (show && !loadingBubble) {
                loadingBubble = document.createElement('div');
                loadingBubble.id = 'loading-bubble';
                loadingBubble.className = 'flex items-start gap-3';
                loadingBubble.innerHTML = `
                    <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center font-bold text-white flex-shrink-0">AI</div>
                    <div class="bg-gray-700 rounded-lg p-3 max-w-xs md:max-w-md">
                        <p class="text-sm animate-pulse">...</p>
                    </div>`;
                chatContainer.appendChild(loadingBubble);
                chatContainer.scrollTop = chatContainer.scrollHeight;
            } else if (!show && loadingBubble) {
                loadingBubble.remove();
            }
        };

        let conversation = <?php echo json_encode($_SESSION['conversation'] ?? []); ?>;
        renderConversation(conversation);

        chatForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const message = messageInput.value.trim();
            if (!message) return;

            messageInput.value = '';
            messageInput.disabled = true;
            sendBtn.disabled = true;
            errorMessage.textContent = '';

            conversation.push({ role: 'user', content: message });
            renderConversation(conversation);
            showLoadingBubble(true);

            try {
                const response = await fetch('index.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'chat', message: message })
                });
                const data = await response.json();
                if (!data.success) throw new Error(data.error);

                conversation = data.conversation;
                renderConversation(conversation);
            } catch (error) {
                console.error('Chat error:', error);
                errorMessage.textContent = `Error: ${error.message}`;
                conversation.pop(); // Remove the optimistic user message on failure
                renderConversation(conversation);
            } finally {
                showLoadingBubble(false);
                messageInput.disabled = false;
                sendBtn.disabled = false;
                messageInput.focus();
            }
        });

        const generateDesignBtn = document.getElementById('generate-design-btn');
        generateDesignBtn.addEventListener('click', async () => {
            generateDesignBtn.disabled = true;
            sendBtn.disabled = true;
            messageInput.disabled = true;
            generateDesignBtn.textContent = 'Generating...';
            errorMessage.textContent = '';

            try {
                const response = await fetch('index.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'generate_design' })
                });

                const data = await response.json();
                if (!data.success) {
                    throw new Error(data.error || 'Unknown error occurred.');
                }

                // On success, redirect to the preview page
                window.location.href = 'index.php?screen=preview';

            } catch (error) {
                console.error('Design generation error:', error);
                errorMessage.textContent = `Generation failed: ${error.message}`;
                // Re-enable buttons on failure
                generateDesignBtn.disabled = false;
                sendBtn.disabled = false;
                messageInput.disabled = false;
                generateDesignBtn.textContent = 'Generate Design';
            }
        });
    }

    if (document.getElementById('code-screen')) {
        const copyBtn = document.getElementById('copy-btn');
        if (copyBtn) {
            const codeBlock = document.getElementById('code-block');
            copyBtn.addEventListener('click', () => {
                navigator.clipboard.writeText(codeBlock.textContent.trim())
                    .then(() => {
                        const originalText = copyBtn.innerHTML;
                        copyBtn.innerHTML = `<span class="flex items-center gap-2 text-green-400"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg> Copied!</span>`;
                        setTimeout(() => { copyBtn.innerHTML = originalText; }, 2000);
                    })
                    .catch(err => {
                        console.error('Failed to copy text: ', err);
                        alert('Failed to copy code.');
                    });
            });
        }
    }
    </script>
</body>
</html>