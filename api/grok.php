<?php
/**
 * ============================================================================
 * GROK AI API ENDPOINT
 * ============================================================================
 * 
 * AI-Powered Budget Analysis API using Groq Cloud (Llama 3.3 70B)
 * 
 * This endpoint enables natural language queries against the budget system.
 * Users can ask questions like "Where can we cut costs?" or "Show spending
 * trends" and receive intelligent, contextual responses.
 * 
 * HOW IT WORKS:
 * 1. Receives user query via POST request
 * 2. Gathers current budget context (KPIs, cost centers, alerts)
 * 3. Constructs a system prompt with this financial data
 * 4. Sends to Groq API (Llama 3.3 70B model)
 * 5. Returns formatted AI response
 * 
 * API REQUEST FORMAT:
 * POST /api/grok.php
 * Content-Type: application/json
 * Body: { "query": "Your natural language question" }
 * 
 * API RESPONSE FORMAT:
 * Success: { "success": true, "response": "AI generated answer", "query": "..." }
 * Error: { "error": "Error description" }
 * 
 * DEPENDENCIES:
 * - Groq API Key (stored in config/api_keys.php)
 * - BudgetEngine for context data
 * 
 * @author    Yusuf Gundarwala
 * @version   1.0.0
 * @package   FurnitureERP
 * ============================================================================
 */

// Set JSON response headers and CORS support
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Include required dependencies
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../config/api_keys.php';
require_once __DIR__ . '/../controllers/BudgetEngine.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get the query
$input = json_decode(file_get_contents('php://input'), true);
$query = trim($input['query'] ?? '');

if (empty($query)) {
    echo json_encode(['error' => 'No query provided']);
    exit;
}

try {
    // Gather context data for AI
    $kpis = BudgetEngine::getDashboardKPIs();
    $budgetData = BudgetEngine::getBudgetVsActual();
    $alerts = BudgetEngine::getSmartAlerts();

    // Build context string
    $contextData = [
        'total_budget' => $kpis['total_budget'],
        'total_spent' => $kpis['total_actual'],
        'remaining' => $kpis['total_remaining'],
        'utilization_percent' => $kpis['utilization'],
        'health_status' => $kpis['health']['status'],
        'cost_centers' => array_map(function ($cc) {
            return [
                'name' => $cc['cost_center_name'],
                'budget' => $cc['budget_amount'],
                'spent' => $cc['actual_spend'],
                'remaining' => $cc['remaining'],
                'utilization' => $cc['utilization'] . '%',
                'status' => $cc['health_status']
            ];
        }, $budgetData),
        'active_alerts' => $alerts
    ];

    $systemPrompt = "You are a helpful AI assistant for a furniture company's budget management system. 
You have access to the following budget data:
" . json_encode($contextData, JSON_PRETTY_PRINT) . "

Answer questions about budgets, spending, cost centers, and provide actionable insights.
Be concise but helpful. Use bullet points for lists. 
If asked about trends or predictions, provide reasonable analysis based on the data.
Format currency in Indian Rupees (₹).";

    // Call Groq API
    $response = callGroqAPI($query, $systemPrompt);

    echo json_encode([
        'success' => true,
        'response' => $response,
        'query' => $query
    ]);

} catch (Exception $e) {
    echo json_encode([
        'error' => 'AI service error: ' . $e->getMessage()
    ]);
}

/**
 * Call the Groq API
 */
function callGroqAPI($userQuery, $systemPrompt)
{
    $data = [
        'model' => GROQ_MODEL,
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userQuery]
        ],
        'temperature' => 0.7,
        'max_tokens' => 500
    ];

    $ch = curl_init(GROQ_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . GROQ_API_KEY
        ],
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception('API returned status ' . $httpCode);
    }

    $result = json_decode($response, true);

    if (!isset($result['choices'][0]['message']['content'])) {
        throw new Exception('Invalid API response');
    }

    return $result['choices'][0]['message']['content'];
}
