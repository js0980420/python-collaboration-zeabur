<?php
class XAIService {
    private $apiKey;
    private $baseUrl;
    
    public function __construct() {
        $this->apiKey = $_ENV['XAI_API_KEY'] ?? 'xai-e4IkGBt411Vrj0jEOKIfu6anO1OapqvMpcavAKDS35xRJrfUxTYSZLzuF9X28BBpJPuR4TPwBI2Lo7sL';
        $this->baseUrl = 'https://api.x.ai/v1';
    }
    
    /**
     * 與AI進行對話
     */
    public function chat($messages, $options = []) {
        $defaultOptions = [
            'model' => 'grok-beta',
            'max_tokens' => 1000,
            'temperature' => 0.7
        ];
        
        $options = array_merge($defaultOptions, $options);
        
        $data = [
            'model' => $options['model'],
            'messages' => $messages,
            'max_tokens' => $options['max_tokens'],
            'temperature' => $options['temperature']
        ];
        
        return $this->makeRequest('/chat/completions', $data);
    }
    
    /**
     * 檢查Python程式碼
     */
    public function checkCode($code) {
        $messages = [
            [
                'role' => 'system',
                'content' => '你是一個專業的Python程式碼審查助手。請分析程式碼的：1.語法正確性 2.邏輯合理性 3.代碼風格 4.性能優化建議 5.安全性問題。用繁體中文回答，提供具體的改進建議。'
            ],
            [
                'role' => 'user',
                'content' => "請詳細分析這段Python程式碼：\n\n```python\n{$code}\n```\n\n請提供：語法檢查、邏輯分析、改進建議。"
            ]
        ];
        
        return $this->chat($messages, ['temperature' => 0.3]);
    }
    
    /**
     * 發送HTTP請求到XAI API
     */
    private function makeRequest($endpoint, $data) {
        $url = $this->baseUrl . $endpoint;
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("cURL error: " . $error);
        }
        
        if ($httpCode !== 200) {
            throw new Exception("HTTP error: " . $httpCode . " - " . $response);
        }
        
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON decode error: " . json_last_error_msg());
        }
        
        return $decoded;
    }
}
?> 