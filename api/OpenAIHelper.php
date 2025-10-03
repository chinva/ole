<?php
class OpenAIHelper {
    private $apiKey;
    private $model;
    private $maxTokens;
    
    public function __construct($apiKey = null) {
        $this->apiKey = $apiKey ?? OPENAI_API_KEY;
        $this->model = OPENAI_MODEL;
        $this->maxTokens = OPENAI_MAX_TOKENS;
    }
    
    public function generateExamQuestions($category, $numQuestions = 10) {
        if (empty($this->apiKey)) {
            return ['success' => false, 'message' => 'OpenAI API key not configured'];
        }
        
        $prompt = $this->buildExamPrompt($category, $numQuestions);
        
        try {
            $response = $this->callOpenAI($prompt);
            
            if (!$response['success']) {
                return $response;
            }
            
            $questions = $this->parseExamQuestions($response['content']);
            
            if (empty($questions)) {
                return ['success' => false, 'message' => 'Failed to parse generated questions'];
            }
            
            return ['success' => true, 'questions' => $questions];
            
        } catch (Exception $e) {
            error_log("OpenAI exam generation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to generate exam: ' . $e->getMessage()];
        }
    }
    
    private function buildExamPrompt($category, $numQuestions) {
        return "Generate a multiple-choice exam with exactly $numQuestions questions for the category: $category.
        
Each question must follow this exact format:
1. Question text (clear and concise)
2. 4 answer options labeled A, B, C, D
3. Exactly one correct answer
4. Brief explanation for the correct answer

Format the output as valid JSON with this structure:
{
  &quot;questions&quot;: [
    {
      &quot;question&quot;: &quot;Question text here&quot;,
      &quot;options&quot;: [
        {&quot;label&quot;: &quot;A&quot;, &quot;text&quot;: &quot;Option A text&quot;, &quot;correct&quot;: true/false},
        {&quot;label&quot;: &quot;B&quot;, &quot;text&quot;: &quot;Option B text&quot;, &quot;correct&quot;: true/false},
        {&quot;label&quot;: &quot;C&quot;, &quot;text&quot;: &quot;Option C text&quot;, &quot;correct&quot;: true/false},
        {&quot;label&quot;: &quot;D&quot;, &quot;text&quot;: &quot;Option D text&quot;, &quot;correct&quot;: true/false}
      ],
      &quot;explanation&quot;: &quot;Explanation for the correct answer&quot;
    }
  ]
}

Important requirements:
- Questions should be relevant to $category
- Mix of easy, medium, and hard difficulty levels
- Ensure factual accuracy
- Make questions engaging and educational
- Each option should be plausible
- Return only valid JSON without any markdown formatting or additional text";
    }
    
    private function callOpenAI($prompt) {
        $ch = curl_init();
        
        $payload = [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an expert educational content creator specializing in creating high-quality multiple-choice questions for online examinations.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => $this->maxTokens,
            'temperature' => 0.7,
            'response_format' => ['type' => 'json_object']
        ];
        
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.openai.com/v1/chat/completions',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey
            ],
            CURLOPT_TIMEOUT => 60
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return ['success' => false, 'message' => 'CURL error: ' . $error];
        }
        
        if ($httpCode !== 200) {
            return ['success' => false, 'message' => 'API error: HTTP ' . $httpCode];
        }
        
        $data = json_decode($response, true);
        
        if (!isset($data['choices'][0]['message']['content'])) {
            return ['success' => false, 'message' => 'Invalid API response format'];
        }
        
        return [
            'success' => true,
            'content' => $data['choices'][0]['message']['content']
        ];
    }
    
    private function parseExamQuestions($jsonContent) {
        try {
            $data = json_decode($jsonContent, true);
            
            if (!isset($data['questions']) || !is_array($data['questions'])) {
                return [];
            }
            
            $questions = [];
            foreach ($data['questions'] as $q) {
                if (isset($q['question']) && isset($q['options']) && is_array($q['options']) && count($q['options']) === 4) {
                    $correctFound = false;
                    foreach ($q['options'] as $option) {
                        if (isset($option['correct']) && $option['correct']) {
                            $correctFound = true;
                            break;
                        }
                    }
                    
                    if ($correctFound) {
                        $questions[] = [
                            'question' => $q['question'],
                            'options' => $q['options'],
                            'explanation' => $q['explanation'] ?? ''
                        ];
                    }
                }
            }
            
            return $questions;
            
        } catch (Exception $e) {
            error_log("Question parsing error: " . $e->getMessage());
            return [];
        }
    }
    
    public function validateApiKey() {
        if (empty($this->apiKey)) {
            return false;
        }
        
        try {
            $ch = curl_init();
            
            curl_setopt_array($ch, [
                CURLOPT_URL => 'https://api.openai.com/v1/models',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $this->apiKey
                ],
                CURLOPT_TIMEOUT => 10
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            return $httpCode === 200;
            
        } catch (Exception $e) {
            return false;
        }
    }
}
?>