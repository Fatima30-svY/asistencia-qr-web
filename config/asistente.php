<?php

return [
    'enabled' => env('ASISTENTE_ENABLED', true),
    'url' => env('OLLAMA_URL', 'http://127.0.0.1:11434'),
    'model' => env('OLLAMA_MODEL', 'qwen2.5:1.5b'),
    'timeout' => 60,
    'context_tokens' => 2048,
    'chat_tokens' => 220,
    'description_tokens' => 160,
];
