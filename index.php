<?php
// --- BACKEND LOGIC ---

/**
 * Handles all POST requests to the backend.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $postData = json_decode(file_get_contents('php://input'), true);
    $action = $postData['action'] ?? null;

    if ($action === 'proxy') {
        handle_proxy($postData);
    } elseif ($action === 'download_zip') {
        handle_zip($postData);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action.']);
    }
    exit;
}

/**
 * Proxies a request to the OpenRouter API.
 */
function handle_proxy($data) {
    if (empty($data['api_key']) || empty($data['messages'])) {
        http_response_code(400);
        echo json_encode(['error' => 'API key and messages are required.']);
        return;
    }

    $apiKey = $data['api_key'];
    $messages = $data['messages'];
    $model = $data['model'] ?? 'deepseek/deepseek-chat';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://openrouter.ai/api/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['model' => $model, 'messages' => $messages]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey]);
    // The SSL bypass might be needed for some environments. Let's keep it here but commented out for now.
    // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $result = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        http_response_code(500);
        echo json_encode(['error' => 'cURL Error: ' . curl_error($ch)]);
    } else {
        http_response_code($httpcode);
        echo $result;
    }
    curl_close($ch);
}

/**
 * Creates and streams a ZIP file.
 */
function handle_zip($data) {
    if (empty($data['code'])) {
        http_response_code(400);
        echo json_encode(['error' => 'No code provided to zip.']);
        return;
    }

    $zip = new ZipArchive();
    $zipFileName = tempnam(sys_get_temp_dir(), 'ai_design_') . '.zip';

    if ($zip->open($zipFileName, ZipArchive::CREATE) === TRUE) {
        $zip->addFromString('index.html', $data['code']);
        $zip->close();

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="ai_generated_design.zip"');
        header('Content-Length: ' . filesize($zipFileName));
        readfile($zipFileName);

        unlink($zipFileName);
        exit; // Exit after streaming the file
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create ZIP file.']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Structured Design Generator</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        html { scroll-behavior: smooth; }
        body { font-family: 'Inter', sans-serif; background-color: #111827; color: #e5e7eb; }
        .screen { display: none; }
        .screen.active { display: block; }
        .nav-link.active { color: #60a5fa; /* text-blue-400 */ }
    </style>
</head>
<body class="antialiased">

    <div class="min-h-screen flex flex-col">
        <!-- Main Navigation Bar -->
        <nav class="bg-gray-800/70 backdrop-blur-sm p-4 sticky top-0 z-20 shadow-lg">
            <div id="nav-container" class="max-w-5xl mx-auto flex justify-center items-center gap-4 md:gap-8">
                <a href="#chat" data-screen="chat" class="nav-link text-sm md:text-base font-medium transition-colors text-gray-400 hover:text-white">AI Chat</a>
                <span class="text-gray-600">|</span>
                <a href="#preview" data-screen="preview" class="nav-link text-sm md:text-base font-medium transition-colors text-gray-400 hover:text-white">Preview</a>
                <span class="text-gray-600">|</span>
                <a href="#code" data-screen="code" class="nav-link text-sm md:text-base font-medium transition-colors text-gray-400 hover:text-white">Code</a>
                <span class="text-gray-600 hidden md:inline">|</span>
                <a href="#settings" data-screen="settings" class="nav-link text-sm md:text-base font-medium transition-colors text-gray-400 hover:text-white">Settings</a>
            </div>
        </nav>

        <!-- Screens Container -->
        <main class="flex-grow">
            <div id="chat-screen" class="screen flex flex-col h-[calc(100vh-64px)]">
                <header class="p-4 text-center border-b border-gray-700">
                    <h1 class="text-xl font-bold text-white">AI Design Chat</h1>
                </header>
                <div id="chat-container" class="flex-grow p-4 space-y-6 overflow-y-auto">
                    <!-- Chat messages will be rendered here -->
                </div>
                <div id="chat-blocker" class="p-4 text-center bg-yellow-900/50 border-t border-yellow-700 hidden">
                     <p class="font-bold text-yellow-300">API Key Not Set</p>
                     <p class="text-yellow-400 text-sm mt-1">
                         Please go to the <a href="#settings" data-screen-link="settings" class="underline font-semibold hover:text-white">Settings</a> page to add your API key.
                     </p>
                </div>
                <footer id="chat-footer" class="bg-gray-800 p-4 border-t border-gray-700">
                    <form id="chat-form" class="flex items-center gap-3">
                        <input id="message-input" type="text" placeholder="e.g., Create a modern login screen" class="w-full bg-gray-900 text-white p-3 rounded-lg border border-gray-600 focus:ring-2 focus:ring-blue-500 focus:outline-none transition" autocomplete="off">
                        <button type="submit" id="send-btn" class="bg-blue-600 text-white rounded-lg p-3 hover:bg-blue-700 transition-colors flex-shrink-0 disabled:bg-gray-500 disabled:cursor-not-allowed">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                        </button>
                        <button type="button" id="clear-chat-btn" title="Clear Chat History" class="bg-red-600 text-white rounded-lg p-3 hover:bg-red-700 transition-colors flex-shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        </button>
                    </form>
                    <p id="chat-error" class="text-red-400 text-sm mt-2 text-center h-4"></p>
                </footer>
            </div>
            <div id="preview-screen" class="screen">
                 <div class="flex flex-col items-center p-4">
                    <header class="w-full max-w-3xl mx-auto mb-8 text-center">
                        <h1 class="text-3xl font-bold text-white">Live Design Preview</h1>
                        <p class="text-gray-400 mt-2">This preview is generated in real-time from the latest structured design data in your chat.</p>
                    </header>

                    <!-- Mobile Mockup -->
                    <div class="w-80 h-[560px] bg-gray-900 rounded-[40px] border-[12px] border-gray-900 shadow-2xl overflow-hidden">
                        <div id="preview-content" class="w-full h-full overflow-y-auto bg-white text-gray-800 p-4 space-y-4">
                            <!-- Dynamic preview content will be rendered here by JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
            <div id="code-screen" class="screen">
                <div class="flex flex-col items-center p-4">
                    <header class="w-full max-w-4xl mx-auto mb-8 text-center">
                        <h1 class="text-3xl font-bold text-white">Final Source Code</h1>
                        <p class="text-gray-400 mt-2">The AI is generating production-ready code based on the design data. This may take a moment.</p>
                    </header>

                    <div id="code-container" class="w-full max-w-4xl">
                        <!-- Code will be loaded here -->
                    </div>
                </div>
            </div>
            <div id="settings-screen" class="screen">
                <div class="max-w-md mx-auto">
                    <h1 class="text-3xl font-bold text-center">Settings</h1>
                    <p class="text-center text-gray-400 mt-2 mb-8">Your API key is saved securely in your browser's local storage.</p>

                    <div class="bg-gray-800 p-8 rounded-lg shadow-lg">
                        <form id="settings-form">
                            <div class="mb-4">
                                <label for="api_key" class="block text-gray-300 text-sm font-bold mb-2">OpenRouter API Key</label>
                                <input type="password" id="api_key_input" class="w-full bg-gray-900 text-white p-3 rounded-lg border border-gray-600 focus:ring-2 focus:ring-blue-500 focus:outline-none transition" placeholder="sk-or-v1-...">
                            </div>
                            <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-blue-700 transition-colors">
                                Save API Key
                            </button>
                        </form>
                        <p id="settings-feedback" class="text-green-400 text-sm mt-4 text-center h-4"></p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // --- CLIENT-SIDE LOGIC ---
        document.addEventListener('DOMContentLoaded', () => {
            // --- App State ---
            const state = {
                apiKey: '',
                chatHistory: [],
                // other state properties will be added later
            };

            // --- DOM Elements ---
            const screens = document.querySelectorAll('.screen');
            const navLinks = document.querySelectorAll('.nav-link');
            const settingsForm = document.getElementById('settings-form');
            const apiKeyInput = document.getElementById('api_key_input');
            const settingsFeedback = document.getElementById('settings-feedback');
            const previewContent = document.getElementById('preview-content');
            const codeContainer = document.getElementById('code-container');

            // --- Functions ---
            async function renderCode() {
                const lastStructuredMessage = [...state.chatHistory].reverse().find(msg => msg.isStructured);
                codeContainer.innerHTML = '';

                if (!checkApiKey() || !lastStructuredMessage) {
                    codeContainer.innerHTML = `<div class="text-center bg-gray-800 p-8 rounded-lg shadow-lg">
                        <h2 class="text-2xl font-bold text-white">No Design Data Available</h2>
                        <p class="text-gray-400 mt-4">Please go to the AI Chat screen and ask the AI to create a design first.</p>
                        <a href="#chat" data-screen-link="chat" class="mt-6 inline-block bg-blue-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-blue-700 transition-colors">
                            Go to Chat
                        </a>
                    </div>`;
                    return;
                }

                codeContainer.innerHTML = `<div class="text-center text-gray-400 p-8"><p class="animate-pulse">Generating final code...</p></div>`;

                try {
                    const design_prompt = `You are a world-class frontend developer. Your task is to generate the complete, production-ready HTML and Tailwind CSS code for a web application based on the following structured data. Respond with ONLY the raw HTML code. Do not include any explanations, markdown formatting, or any text outside of the HTML itself.

                    Data: ${JSON.stringify(lastStructuredMessage.content)}`;

                    const response = await fetch('index.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'proxy',
                            api_key: state.apiKey,
                            messages: [{ role: 'user', content: design_prompt }],
                            model: 'deepseek/deepseek-coder'
                        })
                    });

                    if (!response.ok) {
                        const err = await response.json();
                        throw new Error(err.error?.message || 'Code generation failed.');
                    }

                    const aiResponse = await response.json();
                    let finalCode = aiResponse.choices[0].message.content;

                    // Clean up markdown if present
                    const match = finalCode.match(/```html\s*([\s\S]*?)\s*```/);
                    if (match) finalCode = match[1];

                    codeContainer.innerHTML = `
                        <div class="w-full max-w-4xl bg-gray-800 rounded-xl shadow-2xl overflow-hidden">
                            <div class="bg-gray-900 p-3 flex items-center justify-between">
                                <span class="text-gray-400 text-sm">index.html</span>
                                <button id="copy-btn" class="text-gray-400 hover:text-white transition-colors text-sm flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                                    Copy Code
                                </button>
                            </div>
                            <pre class="max-h-[500px] overflow-y-auto p-4"><code id="code-block" class="language-html text-sm"></code></pre>
                        </div>
                        <div class="mt-8 text-center">
                            <button id="download-zip-btn" class="bg-indigo-600 text-white font-bold py-3 px-8 rounded-lg hover:bg-indigo-700 transition-colors transform hover:scale-105">
                                Download ZIP
                            </button>
                        </div>`;

                    const codeBlock = document.getElementById('code-block');
                    codeBlock.textContent = finalCode; // Use textContent to prevent HTML rendering

                    // Add event listeners for new buttons
                    document.getElementById('copy-btn').addEventListener('click', () => {
                         navigator.clipboard.writeText(finalCode).then(() => {
                            const copyBtn = document.getElementById('copy-btn');
                            const originalText = copyBtn.innerHTML;
                            copyBtn.innerHTML = `<span class="flex items-center gap-2 text-green-400">Copied!</span>`;
                            setTimeout(() => { copyBtn.innerHTML = originalText; }, 2000);
                         });
                    });

                    document.getElementById('download-zip-btn').addEventListener('click', async () => {
                        const btn = document.getElementById('download-zip-btn');
                        btn.textContent = 'Packaging...';
                        btn.disabled = true;
                        try {
                            const zipResponse = await fetch('index.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ action: 'download_zip', code: finalCode })
                            });
                            if (!zipResponse.ok) throw new Error('Failed to create ZIP file.');
                            const blob = await zipResponse.blob();
                            const url = window.URL.createObjectURL(blob);
                            const a = document.createElement('a');
                            a.href = url;
                            a.download = 'ai_generated_design.zip';
                            document.body.appendChild(a);
                            a.click();
                            a.remove();
                            window.URL.revokeObjectURL(url);
                        } catch (err) {
                            alert(err.message);
                        } finally {
                            btn.textContent = 'Download ZIP';
                            btn.disabled = false;
                        }
                    });

                } catch (error) {
                    codeContainer.innerHTML = `<div class="text-center text-red-400 p-8 bg-red-900/50 rounded-lg">Error generating code: ${error.message}</div>`;
                }
            }

            function renderPreview() {
                const lastStructuredMessage = [...state.chatHistory].reverse().find(msg => msg.isStructured);
                previewContent.innerHTML = '';

                if (!lastStructuredMessage) {
                    previewContent.innerHTML = `<div class="text-center text-gray-500 mt-10 p-4">
                        <p class="font-bold">No design data available.</p>
                        <p class="text-sm mt-2">Go to the chat and ask the AI to create a design. For example: "Make a modern login screen".</p>
                    </div>`;
                    return;
                }

                const { component_list, color_scheme, design_description } = lastStructuredMessage.content;

                previewContent.style.backgroundColor = color_scheme.background || '#ffffff';
                previewContent.style.color = color_scheme.text || '#111827';

                const componentGenerators = {
                    'header': () => `<div class="p-4 rounded-lg" style="background-color: ${color_scheme.secondary || '#f3f4f6'};"><h2 class="text-xl font-bold" style="color: ${color_scheme.text || '#111827'};">${design_description}</h2></div>`,
                    'email input': () => `<div><label class="block text-sm font-medium mb-1">Email</label><input type="email" class="w-full p-2 rounded border" style="background-color: #ffffff; border-color: ${color_scheme.secondary || '#d1d5db'};" placeholder="you@example.com"></div>`,
                    'password input': () => `<div><label class="block text-sm font-medium mb-1">Password</label><input type="password" class="w-full p-2 rounded border" style="background-color: #ffffff; border-color: ${color_scheme.secondary || '#d1d5db'};" placeholder="••••••••"></div>`,
                    'username input': () => `<div><label class="block text-sm font-medium mb-1">Username</label><input type="text" class="w-full p-2 rounded border" style="background-color: #ffffff; border-color: ${color_scheme.secondary || '#d1d5db'};" placeholder="your_username"></div>`,
                    'text input': () => `<div><label class="block text-sm font-medium mb-1">Field</label><input type="text" class="w-full p-2 rounded border" style="background-color: #ffffff; border-color: ${color_scheme.secondary || '#d1d5db'};" placeholder="Enter text..."></div>`,
                    'button': () => `<button class="w-full font-bold py-2 px-4 rounded text-white" style="background-color: ${color_scheme.primary || '#3b82f6'};">Submit</button>`,
                    'image placeholder': () => `<div class="w-full h-32 rounded-lg" style="background-color: ${color_scheme.secondary || '#e5e7eb'};"></div>`,
                    'avatar': () => `<div class="w-16 h-16 rounded-full mx-auto" style="background-color: ${color_scheme.accent || '#8b5cf6'};"></div>`,
                    'title': () => `<h1 class="text-2xl font-bold text-center">${design_description}</h1>`,
                    'paragraph': () => `<p class="text-sm">This is a paragraph describing the design, using the specified text color to provide context.</p>`,
                    'default': (name) => `<div class="p-2 border rounded text-center text-xs border-dashed" style="border-color: ${color_scheme.secondary || '#d1d5db'};">${name}</div>`
                };

                component_list.forEach(componentName => {
                    const nameLower = componentName.toLowerCase();
                    let generatorKey = 'default';

                    // Find the best matching generator
                    for (const key in componentGenerators) {
                        if (nameLower.includes(key)) {
                            generatorKey = key;
                            break;
                        }
                    }

                    const componentHtml = componentGenerators[generatorKey](componentName);
                    previewContent.innerHTML += componentHtml;
                });
            }

            function showScreen(screenId) {
                screens.forEach(screen => {
                    screen.classList.toggle('active', screen.id === `${screenId}-screen`);
                });
                navLinks.forEach(link => {
                    link.classList.toggle('active', link.dataset.screen === screenId);
                });
                if (window.location.hash !== `#${screenId}`) {
                    history.pushState({screen: screenId}, '', `#${screenId}`);
                }
                if (screenId === 'preview') {
                    renderPreview();
                } else if (screenId === 'code') {
                    renderCode();
                }
            }

            function initialize() {
                // Load API key from local storage
                const savedApiKey = localStorage.getItem('ai_design_api_key');
                if (savedApiKey) {
                    state.apiKey = savedApiKey;
                    apiKeyInput.value = savedApiKey;
                }

                // Show initial screen based on URL hash or default to 'chat'
                const initialScreen = window.location.hash.substring(1) || 'chat';
                showScreen(initialScreen);
            }

            // --- Event Listeners ---
            navLinks.forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    const screenId = link.dataset.screen;
                    showScreen(screenId);
                });
            });

            window.addEventListener('popstate', (e) => {
                const screenId = e.state?.screen || 'chat';
                showScreen(screenId);
            });

            settingsForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const newApiKey = apiKeyInput.value.trim();
                if (newApiKey) {
                    state.apiKey = newApiKey;
                    localStorage.setItem('ai_design_api_key', newApiKey);
                    settingsFeedback.textContent = 'API Key saved successfully!';
                    setTimeout(() => {
                        settingsFeedback.textContent = '';
                    }, 3000);
                }
            });

            // --- DOM Elements (Chat) ---
            const chatContainer = document.getElementById('chat-container');
            const chatForm = document.getElementById('chat-form');
            const messageInput = document.getElementById('message-input');
            const sendBtn = document.getElementById('send-btn');
            const clearChatBtn = document.getElementById('clear-chat-btn');
            const chatBlocker = document.getElementById('chat-blocker');
            const chatFooter = document.getElementById('chat-footer');
            const chatError = document.getElementById('chat-error');

            // --- Functions (Chat) ---
            function saveChatHistory() {
                localStorage.setItem('ai_design_chat_history', JSON.stringify(state.chatHistory));
            }

            function loadChatHistory() {
                const savedHistory = localStorage.getItem('ai_design_chat_history');
                if (savedHistory) {
                    state.chatHistory = JSON.parse(savedHistory);
                }
            }

            function renderChat() {
                chatContainer.innerHTML = '';
                if (state.chatHistory.length === 0) {
                    const welcomeBubble = document.createElement('div');
                    welcomeBubble.className = 'flex items-start gap-3';
                    welcomeBubble.innerHTML = `
                        <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center font-bold text-white flex-shrink-0">AI</div>
                        <div class="bg-gray-700 rounded-lg p-4 max-w-xs md:max-w-md">
                            <p class="text-sm">Hello! I'm your AI Design Assistant. What would you like to create? Try something like "Make a modern login screen for a social media app".</p>
                        </div>`;
                    chatContainer.appendChild(welcomeBubble);
                    return;
                }

                state.chatHistory.forEach(msg => {
                    const isUser = msg.role === 'user';
                    const bubble = document.createElement('div');
                    bubble.className = `flex items-start gap-3 ${isUser ? 'justify-end' : ''}`;
                    let contentHtml = '';

                    if (isUser) {
                        contentHtml = `<p class="text-sm whitespace-pre-wrap">${msg.content}</p>`;
                    } else { // AI message
                        if (msg.isStructured) {
                            contentHtml = `
                                <p class="text-sm mb-2">${msg.content.design_description}</p>
                                <div class="border-t border-gray-600 pt-2 mt-2">
                                    <h4 class="font-bold text-xs text-gray-400 mb-1">Components:</h4>
                                    <ul class="list-disc list-inside text-sm mb-2">
                                        ${msg.content.component_list.map(c => `<li>${c}</li>`).join('')}
                                    </ul>
                                    <h4 class="font-bold text-xs text-gray-400 mb-1">Color Scheme:</h4>
                                    <div class="flex gap-2">
                                        ${Object.entries(msg.content.color_scheme).map(([name, color]) => `
                                            <div class="text-center">
                                                <div class="w-8 h-8 rounded-full border-2 border-gray-500" style="background-color: ${color};"></div>
                                                <span class="text-xs text-gray-400">${name}</span>
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>`;
                        } else {
                             contentHtml = `<p class="text-sm whitespace-pre-wrap">${msg.content}</p>`;
                        }
                    }

                    bubble.innerHTML = `
                        ${!isUser ? '<div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center font-bold text-white flex-shrink-0">AI</div>' : ''}
                        <div class="${isUser ? 'bg-blue-600' : 'bg-gray-700'} rounded-lg p-3 max-w-xs md:max-w-md">
                            ${contentHtml}
                        </div>
                        ${isUser ? '<div class="w-10 h-10 rounded-full bg-gray-600 flex items-center justify-center font-bold text-white flex-shrink-0">You</div>' : ''}
                    `;
                    chatContainer.appendChild(bubble);
                });
                chatContainer.scrollTop = chatContainer.scrollHeight;
            }

            function toggleChatLoading(isLoading) {
                sendBtn.disabled = isLoading;
                messageInput.disabled = isLoading;
                if (isLoading) {
                    const loadingBubble = document.createElement('div');
                    loadingBubble.id = 'loading-bubble';
                    loadingBubble.className = 'flex items-start gap-3';
                    loadingBubble.innerHTML = `
                        <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center font-bold text-white flex-shrink-0">AI</div>
                        <div class="bg-gray-700 rounded-lg p-3 max-w-xs md:max-w-md animate-pulse">
                            <p class="text-sm">...</p>
                        </div>`;
                    chatContainer.appendChild(loadingBubble);
                    chatContainer.scrollTop = chatContainer.scrollHeight;
                } else {
                    const loadingBubble = document.getElementById('loading-bubble');
                    if(loadingBubble) loadingBubble.remove();
                }
            }

            function checkApiKey() {
                if (!state.apiKey) {
                    chatBlocker.classList.remove('hidden');
                    chatFooter.classList.add('hidden');
                    return false;
                }
                chatBlocker.classList.add('hidden');
                chatFooter.classList.remove('hidden');
                return true;
            }

            // --- Event Listeners (Chat) ---
            chatForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                if (!checkApiKey()) return;

                const message = messageInput.value.trim();
                if (!message) return;

                messageInput.value = '';
                chatError.textContent = '';
                toggleChatLoading(true);

                state.chatHistory.push({ role: 'user', content: message });
                renderChat();
                saveChatHistory();

                try {
                    const systemPrompt = {
                        role: 'system',
                        content: `You are an AI design assistant. Your task is to understand the user's request for a web component or page and respond with a structured JSON object. The JSON object MUST contain three keys: 'design_description' (a brief, one-sentence summary of the design), 'component_list' (an array of strings listing the key UI components, e.g., "Header", "Email Input", "Password Input", "Login Button"), and 'color_scheme' (an object with keys like 'primary', 'secondary', 'accent', 'background', 'text' and corresponding hex color codes). Respond ONLY with the raw JSON object, no other text or markdown formatting.`
                    };
                    const messages = [systemPrompt, ...state.chatHistory];

                    const response = await fetch('index.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'proxy',
                            api_key: state.apiKey,
                            messages: messages,
                            model: 'deepseek/deepseek-chat'
                        })
                    });

                    if (!response.ok) {
                        const err = await response.json();
                        throw new Error(err.error?.message || 'An unknown API error occurred.');
                    }

                    const aiResponse = await response.json();
                    let aiMessageContent = aiResponse.choices[0].message.content;

                    try {
                        const structuredData = JSON.parse(aiMessageContent);
                        state.chatHistory.push({ role: 'assistant', content: structuredData, isStructured: true });
                    } catch (jsonError) {
                        // If parsing fails, treat it as a regular text response
                        state.chatHistory.push({ role: 'assistant', content: aiMessageContent, isStructured: false });
                    }

                } catch (error) {
                    chatError.textContent = error.message;
                    state.chatHistory.pop(); // Remove the user's message on failure
                } finally {
                    toggleChatLoading(false);
                    renderChat();
                    saveChatHistory();
                }
            });

            clearChatBtn.addEventListener('click', () => {
                if (confirm('Are you sure you want to clear the chat history?')) {
                    state.chatHistory = [];
                    saveChatHistory();
                    renderChat();
                }
            });

            // --- App Initialization ---
            function initialize() {
                // Load API key from local storage
                const savedApiKey = localStorage.getItem('ai_design_api_key');
                if (savedApiKey) {
                    state.apiKey = savedApiKey;
                    apiKeyInput.value = savedApiKey;
                }

                // Load chat history
                loadChatHistory();
                renderChat();
                checkApiKey();

                // Show initial screen based on URL hash or default to 'chat'
                const initialScreen = window.location.hash.substring(1) || 'chat';
                showScreen(initialScreen);
            }

            // Re-check for API key when returning to page or when settings might have changed
            document.addEventListener('visibilitychange', checkApiKey);
            navLinks.forEach(link => link.addEventListener('click', checkApiKey));

            initialize();
        });
    </script>
</body>
</html>