<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;
use OpenAI\Laravel\Facades\OpenAI;

class OpenAIService
{
    /**
     * Analyze a water meter reading image and verify the reading and serial number
     *
     * @param string $imagePath Path to the image in storage
     * @param string $serialNumber The water meter serial number to verify
     * @param float $reportedReading The reading value reported by the user
     * @return array Analysis results containing success status and details
     */
    public function analyzeMeterReading(string $imagePath, string $serialNumber, float $reportedReading): array
    {
        try {
            // Get full storage path
            $fullPath = Storage::disk('public')->path($imagePath);
            
            if (!file_exists($fullPath)) {
                return [
                    'success' => false,
                    'message' => 'Снимката не може да бъде намерена',
                    'matches' => [
                        'serial_number' => false,
                        'reading' => false
                    ]
                ];
            }
            
            // Encode image to base64
            $imageData = base64_encode(file_get_contents($fullPath));
            
            // Create the prompt with the expected information
            $prompt = "Analyze this water meter image. Extract the following information:\n\n"
                    . "1. The serial number of the water meter\n"
                    . "2. The current reading value on the meter (cubic meters)\n\n"
                    . "Expected serial number: {$serialNumber}\n"
                    . "Reported reading value: {$reportedReading} m³\n\n"
                    . "Provide your analysis in this JSON format:\n"
                    . "{\n"
                    . "  \"extracted_serial\": \"the serial number you can see in the image\",\n"
                    . "  \"extracted_reading\": \"the reading value you can see in the image\",\n"
                    . "  \"serial_matches\": true/false,\n"
                    . "  \"reading_matches\": true/false,\n"
                    . "  \"confidence\": \"high/medium/low\",\n"
                    . "  \"issues\": \"description of any issues with the image (e.g., blurry, poor lighting)\"\n"
                    . "}";
            
            // Call OpenAI Vision API
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o-2024-08-06',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => $prompt,
                            ],
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => "data:image/jpeg;base64,{$imageData}",
                                ],
                            ],
                        ],
                    ],
                ],
                'max_tokens' => 1000,
            ]);
            
            // Extract response content
            $responseContent = $response->choices[0]->message->content;
            
            // Parse JSON response
            $jsonStartPos = strpos($responseContent, '{');
            $jsonEndPos = strrpos($responseContent, '}');
            
            if ($jsonStartPos === false || $jsonEndPos === false) {
                throw new Exception('Invalid response format from OpenAI API');
            }
            
            $jsonString = substr($responseContent, $jsonStartPos, $jsonEndPos - $jsonStartPos + 1);
            $analysisResult = json_decode($jsonString, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Failed to parse JSON from OpenAI response');
            }
            
            // Format response
            $successMessage = '';
            $failureMessage = '';
            
            if ($analysisResult['serial_matches'] && $analysisResult['reading_matches']) {
                $successMessage = 'Показанието и серийният номер са потвърдени успешно!';
            } elseif (!$analysisResult['serial_matches'] && !$analysisResult['reading_matches']) {
                $failureMessage = 'Серийният номер и показанието не съответстват на снимката.';
            } elseif (!$analysisResult['serial_matches']) {
                $failureMessage = 'Серийният номер на водомера не съответства на снимката.';
            } elseif (!$analysisResult['reading_matches']) {
                $failureMessage = 'Показанието не съответства на стойността от снимката.';
            }
            
            return [
                'success' => ($analysisResult['serial_matches'] && $analysisResult['reading_matches']),
                'message' => $successMessage ?: $failureMessage,
                'matches' => [
                    'serial_number' => $analysisResult['serial_matches'],
                    'reading' => $analysisResult['reading_matches']
                ],
                'extracted' => [
                    'serial_number' => $analysisResult['extracted_serial'] ?? null,
                    'reading' => $analysisResult['extracted_reading'] ?? null,
                ],
                'confidence' => $analysisResult['confidence'] ?? 'low',
                'issues' => $analysisResult['issues'] ?? null
            ];
            
        } catch (Exception $e) {
            Log::error('OpenAI meter reading analysis failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Не успяхме да анализираме снимката: ' . $e->getMessage(),
                'matches' => [
                    'serial_number' => false,
                    'reading' => false
                ]
            ];
        }
    }
}