<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use OpenAI\Laravel\Facades\OpenAI;

class OpenAIService
{
    /**
     * Check if the OpenAI API is configured
     *
     * @return bool
     */
    public static function isConfigured(): bool
    {
        return !empty(config('openai.api_key'));
    }
    /**
     * Analyze a water meter reading image and verify the reading and serial number
     *
     * @param  string  $imagePath  Path to the image in storage
     * @param  string  $serialNumber  The water meter serial number to verify
     * @param  float  $reportedReading  The reading value reported by the user
     * @return array Analysis results containing success status and details
     */
    public function analyzeMeterReading(string $imagePath, string $serialNumber, float $reportedReading): array
    {
        try {
            // Log the start of analysis
            Log::info('Starting meter reading analysis', [
                'image_path' => $imagePath,
                'serial_number' => $serialNumber,
                'reported_reading' => $reportedReading
            ]);
            
            // Get full storage path
            $fullPath = Storage::disk('public')->path($imagePath);

            if (! file_exists($fullPath)) {
                Log::warning('Meter reading analysis failed: image file not found', [
                    'image_path' => $imagePath, 
                    'full_path' => $fullPath
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Снимката не може да бъде намерена',
                    'matches' => [
                        'serial_number' => false,
                        'reading' => false,
                    ],
                ];
            }

            // Encode image to base64
            $imageData = base64_encode(file_get_contents($fullPath));

            // Create the prompt with the expected information
            $prompt = "Analyze this water meter image. Extract the following information:\n\n"
                    ."1. The serial number of the water meter\n"
                    ."2. The current reading value on the meter (cubic meters)\n\n"
                    ."IMPORTANT INSTRUCTIONS FOR READING THE VALUES:\n"
                    ."- Extract 5 digits before the decimal point and 3 digits after\n"
                    ."- Format should be 00000.000 (with decimal point)\n"
                    ."- Red-colored numbers on the dial are typically decimal fractions\n"
                    ."- Black numbers are typically whole numbers\n"
                    ."- Precision is important - include all visible decimal digits\n\n"
                    ."Expected serial number: {$serialNumber}\n"
                    ."Reported reading value: {$reportedReading} m³\n\n"
                    ."Provide your analysis in this JSON format:\n"
                    ."{\n"
                    ."  \"extracted_serial\": \"the serial number you can see in the image\",\n"
                    ."  \"extracted_reading\": \"the complete reading with decimal point (format: 00000.000)\",\n"
                    ."  \"serial_matches\": true/false,\n"
                    ."  \"reading_matches\": true/false,\n"
                    ."  \"confidence\": \"high/medium/low\",\n"
                    ."  \"issues\": \"description of any issues with the image (e.g., blurry, poor lighting)\"\n"
                    .'}';

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

            // Process the extracted reading properly with decimals
            $extractedReading = $analysisResult['extracted_reading'];
            
            // Clean the extracted reading, ensuring it has a proper format
            // First, remove any non-digit, non-decimal point characters
            $cleanExtractedReading = preg_replace('/[^0-9.]/', '', $extractedReading);
            
            // Ensure we have a single decimal point
            if (substr_count($cleanExtractedReading, '.') > 1) {
                // Multiple decimal points - keep only the first one
                $parts = explode('.', $cleanExtractedReading);
                $cleanExtractedReading = $parts[0] . '.' . $parts[1];
            }
            
            // Format the extracted and reported readings for comparison
            // Use 3 decimal precision for comparison
            $extractedValue = number_format((float)$cleanExtractedReading, 3, '.', '');
            $reportedValue = number_format((float)$reportedReading, 3, '.', '');
            
            // Compare with a small tolerance for rounding errors (0.001)
            $readingMatches = (abs((float)$extractedValue - (float)$reportedValue) < 0.001);
            
            // Store the cleaned value for later use
            $extractedReading = $cleanExtractedReading;
            
            // Format response
            $successMessage = '';
            $failureMessage = '';

            if ($analysisResult['serial_matches'] && $readingMatches) {
                $successMessage = 'Показанието и серийният номер са потвърдени успешно!';
            } elseif (! $analysisResult['serial_matches'] && ! $readingMatches) {
                $failureMessage = 'Серийният номер и показанието не съответстват на снимката.';
            } elseif (! $analysisResult['serial_matches']) {
                $failureMessage = 'Серийният номер на водомера не съответства на снимката.';
            } elseif (! $readingMatches) {
                $failureMessage = 'Показанието не съответства на стойността от снимката. Отчетено: ' . $extractedReading;
            }
            
            // Log analysis results
            Log::info('Meter reading analysis results', [
                'image_path' => $imagePath,
                'serial_number_matches' => $analysisResult['serial_matches'],
                'reading_matches' => $readingMatches,
                'expected_serial' => $serialNumber,
                'extracted_serial' => $analysisResult['extracted_serial'] ?? null,
                'expected_reading' => $reportedReading,
                'extracted_reading' => $extractedReading,
                'confidence' => $analysisResult['confidence'] ?? 'unknown',
                'issues' => $analysisResult['issues'] ?? null,
                'success' => ($analysisResult['serial_matches'] && $readingMatches)
            ]);

            return [
                'success' => ($analysisResult['serial_matches'] && $readingMatches),
                'message' => $successMessage ?: $failureMessage,
                'matches' => [
                    'serial_number' => $analysisResult['serial_matches'],
                    'reading' => $readingMatches,
                ],
                'extracted' => [
                    'serial_number' => $analysisResult['extracted_serial'] ?? null,
                    'reading' => $analysisResult['extracted_reading'] ?? null,
                ],
                'confidence' => $analysisResult['confidence'] ?? 'low',
                'issues' => $analysisResult['issues'] ?? null,
            ];

        } catch (Exception $e) {
            Log::error('OpenAI meter reading analysis failed: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Не успяхме да анализираме снимката: '.$e->getMessage(),
                'matches' => [
                    'serial_number' => false,
                    'reading' => false,
                ],
            ];
        }
    }

    /**
     * Extract multiple water meter readings from a single or multiple photos
     * 
     * @param array $imagePaths Array of paths to images in storage
     * @param array $waterMeters Array of water meter objects with serial numbers to match against
     * @return array Results containing extracted readings for each water meter found in images
     */
    public function extractMultipleReadings(array $imagePaths, array $waterMeters): array
    {
        $results = [];
        $allSerialNumbers = array_map(fn($meter) => $meter['serial_number'], $waterMeters);
        
        try {
            foreach ($imagePaths as $imagePath) {
                // Get full storage path
                $fullPath = Storage::disk('public')->path($imagePath);
                
                if (!file_exists($fullPath)) {
                    continue;
                }
                
                // Encode image to base64
                $imageData = base64_encode(file_get_contents($fullPath));
                
                // Create prompt for extracting multiple water meter readings
                $prompt = "This image contains one or more water meters. Extract all water meter readings visible in this image.\n\n"
                        . "For each water meter in the image, identify:\n"
                        . "1. The serial number of the water meter\n"
                        . "2. The current reading value on the meter (cubic meters)\n\n"
                        . "IMPORTANT INSTRUCTIONS FOR READING THE VALUES:\n"
                        . "- Extract 5 digits before the decimal point and 3 digits after\n"
                        . "- Format should be 00000.000 (with decimal point)\n"
                        . "- Red-colored numbers on the dial are typically decimal fractions\n"
                        . "- Black numbers are typically whole numbers\n"
                        . "- Precision is important - include all visible decimal digits\n\n"
                        . "Known water meter serial numbers that might be in this image: " . implode(", ", $allSerialNumbers) . "\n\n"
                        . "Provide your analysis in this JSON format:\n"
                        . "{\n"
                        . "  \"meters\": [\n"
                        . "    {\n"
                        . "      \"extracted_serial\": \"the serial number you can see\",\n"
                        . "      \"extracted_reading\": \"the complete reading with decimal point (format: 00000.000)\",\n"
                        . "      \"confidence\": \"high/medium/low\"\n"
                        . "    },\n"
                        . "    {...more meters if found...}\n"
                        . "  ],\n"
                        . "  \"issues\": \"description of any issues with the image\"\n"
                        . "}\n\n"
                        . "If you can't identify any water meters in the image, return an empty meters array.";
                
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
                    'max_tokens' => 2000,
                ]);
                
                // Extract response content
                $responseContent = $response->choices[0]->message->content;
                
                // Parse JSON response
                $jsonStartPos = strpos($responseContent, '{');
                $jsonEndPos = strrpos($responseContent, '}');
                
                if ($jsonStartPos === false || $jsonEndPos === false) {
                    // Log invalid response format
                    Log::warning('OpenAI extraction failed: Invalid response format (no JSON object found)', [
                        'image_path' => $imagePath,
                        'response_content' => substr($responseContent, 0, 500) . (strlen($responseContent) > 500 ? '...' : '')
                    ]);
                    continue;
                }
                
                $jsonString = substr($responseContent, $jsonStartPos, $jsonEndPos - $jsonStartPos + 1);
                $analysisResult = json_decode($jsonString, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    // Log JSON parsing error
                    Log::warning('OpenAI extraction failed: JSON parsing error', [
                        'image_path' => $imagePath,
                        'json_error' => json_last_error_msg(),
                        'json_string' => substr($jsonString, 0, 500) . (strlen($jsonString) > 500 ? '...' : '')
                    ]);
                    continue;
                }
                
                if (!isset($analysisResult['meters'])) {
                    // Log missing 'meters' field
                    Log::warning('OpenAI extraction failed: Missing meters field in response', [
                        'image_path' => $imagePath,
                        'analysis_result' => $analysisResult
                    ]);
                    continue;
                }
                
                // Process each meter found in this image
                foreach ($analysisResult['meters'] as $meter) {
                    $serialNumber = $meter['extracted_serial'] ?? '';
                    $reading = $meter['extracted_reading'] ?? '';
                    $confidence = $meter['confidence'] ?? 'low';
                    
                    // Skip if we couldn't extract needed info
                    if (empty($serialNumber) || empty($reading)) {
                        continue;
                    }
                    
                    // Match with known meters
                    foreach ($waterMeters as $index => $knownMeter) {
                        $matchConfidence = $this->calculateSerialNumberMatch($serialNumber, $knownMeter['serial_number']);
                        
                        // If we have a match, add to results
                        if ($matchConfidence >= 0.8) {
                            // Log successful recognition
                            Log::info('Meter recognized', [
                                'meter_id' => $knownMeter['id'],
                                'serial_number' => $knownMeter['serial_number'],
                                'extracted_serial' => $serialNumber,
                                'extracted_reading' => $reading,
                                'confidence' => $confidence,
                                'match_confidence' => $matchConfidence,
                                'image_path' => $imagePath
                            ]);
                            
                            // Create entry for this meter if it doesn't exist
                            if (!isset($results[$knownMeter['id']])) {
                                $results[$knownMeter['id']] = [
                                    'meter_id' => $knownMeter['id'],
                                    'serial_number' => $knownMeter['serial_number'],
                                    'extracted_reading' => $reading,
                                    'confidence' => $confidence,
                                    'image_path' => $imagePath,
                                    'match_confidence' => $matchConfidence,
                                ];
                            } 
                            // If we already have a match but this one has higher confidence, update it
                            elseif ($matchConfidence > $results[$knownMeter['id']]['match_confidence']) {
                                $results[$knownMeter['id']]['extracted_reading'] = $reading;
                                $results[$knownMeter['id']]['confidence'] = $confidence;
                                $results[$knownMeter['id']]['image_path'] = $imagePath;
                                $results[$knownMeter['id']]['match_confidence'] = $matchConfidence;
                            }
                        }
                        // Log near-matches for debugging
                        else if ($matchConfidence > 0.5) {
                            Log::info('Meter recognition near-match', [
                                'meter_id' => $knownMeter['id'],
                                'serial_number' => $knownMeter['serial_number'],
                                'extracted_serial' => $serialNumber,
                                'match_confidence' => $matchConfidence,
                                'threshold' => 0.8,
                                'image_path' => $imagePath
                            ]);
                        }
                    }
                }
            }
            
            // Log the final extraction result
            $success = !empty($results);
            $resultCount = count($results);
            
            Log::info('OpenAI meter extraction completed', [
                'success' => $success,
                'meters_found' => $resultCount,
                'total_meters' => count($waterMeters),
                'image_count' => count($imagePaths),
                'timestamp' => now()->toDateTimeString()
            ]);
            
            return [
                'success' => $success,
                'message' => empty($results) ? 'Не успяхме да разпознаем водомери на снимките' : 'Успешно разпознахме ' . $resultCount . ' водомера',
                'results' => array_values($results),
            ];
            
        } catch (Exception $e) {
            Log::error('OpenAI multiple meter reading extraction failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Не успяхме да анализираме снимките: ' . $e->getMessage(),
                'results' => [],
            ];
        }
    }
    
    /**
     * Calculate the similarity match between extracted and known serial numbers
     * 
     * @param string $extracted The extracted serial number
     * @param string $known The known serial number to match against
     * @return float Match confidence between 0 and 1
     */
    private function calculateSerialNumberMatch(string $extracted, string $known): float
    {
        // Clean up both strings (remove spaces, dashes, etc)
        $extracted = preg_replace('/[^a-zA-Z0-9]/', '', $extracted);
        $known = preg_replace('/[^a-zA-Z0-9]/', '', $known);
        
        // If exact match after cleanup
        if (strcasecmp($extracted, $known) === 0) {
            return 1.0;
        }
        
        // Calculate longest common substring
        $lcs = $this->longestCommonSubstring($extracted, $known);
        $longestCommonLength = strlen($lcs);
        
        // If no common substring
        if ($longestCommonLength === 0) {
            return 0.0;
        }
        
        // Calculate match percentage based on longest common substring
        $maxLength = max(strlen($extracted), strlen($known));
        return $longestCommonLength / $maxLength;
    }
    
    /**
     * Find the longest common substring between two strings
     * 
     * @param string $str1 First string
     * @param string $str2 Second string
     * @return string The longest common substring
     */
    private function longestCommonSubstring(string $str1, string $str2): string
    {
        $str1 = strtolower($str1);
        $str2 = strtolower($str2);
        
        $len1 = strlen($str1);
        $len2 = strlen($str2);
        
        $table = array_fill(0, $len1 + 1, array_fill(0, $len2 + 1, 0));
        $maxLength = 0;
        $endPos = 0;
        
        for ($i = 1; $i <= $len1; $i++) {
            for ($j = 1; $j <= $len2; $j++) {
                if ($str1[$i-1] === $str2[$j-1]) {
                    $table[$i][$j] = $table[$i-1][$j-1] + 1;
                    
                    if ($table[$i][$j] > $maxLength) {
                        $maxLength = $table[$i][$j];
                        $endPos = $i - 1;
                    }
                }
            }
        }
        
        return $maxLength > 0 ? substr($str1, $endPos - $maxLength + 1, $maxLength) : '';
    }
}
