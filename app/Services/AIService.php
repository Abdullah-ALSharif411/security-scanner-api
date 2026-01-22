<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIService
{
    public function analyze($xss, $sql, $headers, $url): string
    {
        $prompt = "
Analyze the following website scan results:

URL: $url

XSS Result:
$xss

SQL Injection Result:
$sql

Headers Result:
$headers

Provide:
1. Risk level (High/Medium/Low)
2. Explanation of vulnerabilities
3. Possible exploitation scenarios
4. Recommended fixes
5. Security best practices
";

        try {
            /** @var \Illuminate\Http\Client\Response $response */

            $response = Http::timeout(15) // ⬅️ مهم جدًا
                ->withToken(config('services.openai.key'))
                ->post(config('services.openai.url'), [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]);

            if (!$response->successful()) {
                Log::error('AI API failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return "AI analysis unavailable due to API error.";
            }

            $data = $response->json();

            return $data['choices'][0]['message']['content']
                ?? "AI analysis unavailable.";

        } catch (\Exception $e) {
            Log::error('AIService exception', [
                'message' => $e->getMessage(),
            ]);

            return "AI analysis failed due to a system error.";
        }
    }
}
