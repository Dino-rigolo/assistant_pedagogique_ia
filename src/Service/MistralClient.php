<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;

class MistralClient
{
    private HttpClientInterface $httpClient;
    private string $apiKey;
    private const API_URL = 'https://api.mistral.ai/v1/chat/completions';

    public function __construct(HttpClientInterface $httpClient, string $mistralApiKey)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $mistralApiKey;
    }

    /**
     * Appel l'API Mistral avec un prompt et retourne la réponse
     */
    public function generateText(string $prompt): string
    {
        try {
            $response = $this->httpClient->request('POST', self::API_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'mistral-small-latest',
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.7,
                ],
            ]);

            // Vérifie le code HTTP
            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                throw new \Exception("Mistral API returned status code $statusCode");
            }

            $data = $response->toArray();

            // Vérifie que la réponse contient les champs attendus
            if (!isset($data['choices'][0]['message']['content'])) {
                throw new \Exception('Invalid response structure from Mistral API: ' . json_encode($data));
            }

            $content = $data['choices'][0]['message']['content'];

            if (empty($content)) {
                throw new \Exception('Empty response from Mistral API');
            }

            return $content;

        } catch (HttpExceptionInterface $e) {
            throw new \Exception('HTTP Error from Mistral API: ' . $e->getMessage());
        } catch (\Exception $e) {
            throw new \Exception('Mistral API error: ' . $e->getMessage());
        }
    }

    /**
     * Appel l'API Mistral et retourne un JSON parsé
     */
    public function generateJson(string $prompt): array
    {
        $response = $this->generateText($prompt);
        $cleanedJson = $this->extractJsonFromMarkdown($response);
        
        $decoded = json_decode($cleanedJson, true);
        
        if ($decoded === null) {
            throw new \Exception('Failed to parse JSON from Mistral response: ' . $cleanedJson);
        }
        
        return $decoded;
    }

    /**
     * Extrait le JSON des balises markdown (```json ... ```)
     */
    private function extractJsonFromMarkdown(string $response): string
    {
        $response = trim($response);
        
        // Enlève les balises de début
        if (str_starts_with($response, '```json')) {
            $response = substr($response, 7);
        } elseif (str_starts_with($response, '```')) {
            $response = substr($response, 3);
        }
        
        // Enlève les balises de fin
        if (str_ends_with($response, '```')) {
            $response = substr($response, 0, -3);
        }
        
        return trim($response);
    }
}