<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AIService
{
    protected string $baseUrl;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('services.ai.url', 'http://localhost:8001');
        $this->timeout = config('services.ai.timeout', 120);
    }

    /**
     * Check if AI service is healthy
     */
    public function isHealthy(): bool
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/healthz");
            return $response->successful() && $response->json('ok') === true;
        } catch (\Exception $e) {
            Log::warning('AI service health check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Process a cadastral plan document
     */
    public function processPlanCadastral(UploadedFile $file): array
    {
        return $this->processDocument($file, '/api/process/plan-cadastral');
    }

    /**
     * Process a land title document
     */
    public function processTitreFoncier(UploadedFile $file): array
    {
        return $this->processDocument($file, '/api/process/titre-foncier');
    }

    /**
     * Process any document (auto-detect type)
     */
    public function processDocument(UploadedFile $file, string $endpoint = '/api/process/document'): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->attach('file', $file->get(), $file->getClientOriginalName())
                ->post("{$this->baseUrl}{$endpoint}");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('error', 'Unknown error'),
                'message' => $response->json('message'),
            ];
        } catch (\Exception $e) {
            Log::error('AI document processing failed', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Service unavailable',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Analyze architectural plans for investment study
     */
    public function analyzePlans(array $files, ?string $context = null): array
    {
        try {
            $request = Http::timeout($this->timeout);

            foreach ($files as $index => $file) {
                if ($file instanceof UploadedFile) {
                    $request = $request->attach(
                        "plans[{$index}]",
                        $file->get(),
                        $file->getClientOriginalName()
                    );
                } elseif (is_string($file) && Storage::exists($file)) {
                    $request = $request->attach(
                        "plans[{$index}]",
                        Storage::get($file),
                        basename($file)
                    );
                }
            }

            if ($context) {
                $request = $request->asMultipart()->attach('context', $context);
            }

            $response = $request->post("{$this->baseUrl}/api/analyze-plans");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json('analysis', []),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('error', 'Unknown error'),
            ];
        } catch (\Exception $e) {
            Log::error('AI plan analysis failed', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => 'Service unavailable',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get AI suggestions for investment study parameters
     */
    public function getInvestmentSuggestions(array $terrainData): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/api/investment-study/suggest", $terrainData);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json('suggestions', []),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('error', 'Unknown error'),
            ];
        } catch (\Exception $e) {
            Log::error('AI investment suggestions failed', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => 'Service unavailable',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Estimate land price
     */
    public function estimatePrice(array $terrainData): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/api/estimate-price", $terrainData);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json('estimation', []),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('error', 'Unknown error'),
            ];
        } catch (\Exception $e) {
            Log::error('AI price estimation failed', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => 'Service unavailable',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate business plan PDF
     */
    public function generateBusinessPlanPdf(array $data, array $planFiles = []): array
    {
        try {
            $request = Http::timeout($this->timeout);

            // Add plan files if provided
            foreach ($planFiles as $index => $file) {
                if ($file instanceof UploadedFile) {
                    $request = $request->attach(
                        "plans[{$index}]",
                        $file->get(),
                        $file->getClientOriginalName()
                    );
                }
            }

            $response = $request
                ->asMultipart()
                ->attach('data', json_encode($data), null, ['Content-Type' => 'application/json'])
                ->post("{$this->baseUrl}/api/generate-pdf/business-plan");

            if ($response->successful()) {
                // Save PDF to storage
                $filename = 'business-plans/' . uniqid('bp_') . '.pdf';
                Storage::put($filename, $response->body());

                return [
                    'success' => true,
                    'path' => $filename,
                    'url' => Storage::url($filename),
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to generate PDF',
            ];
        } catch (\Exception $e) {
            Log::error('AI PDF generation failed', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => 'Service unavailable',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Analyze terrain from images and description
     */
    public function analyzeTerrain(array $images, ?string $description = null, ?float $latitude = null, ?float $longitude = null): array
    {
        try {
            $request = Http::timeout($this->timeout);

            foreach ($images as $index => $image) {
                if ($image instanceof UploadedFile) {
                    $request = $request->attach(
                        "images[{$index}]",
                        $image->get(),
                        $image->getClientOriginalName()
                    );
                }
            }

            $formData = [];
            if ($description) {
                $formData['description'] = $description;
            }
            if ($latitude !== null) {
                $formData['latitude'] = $latitude;
            }
            if ($longitude !== null) {
                $formData['longitude'] = $longitude;
            }

            foreach ($formData as $key => $value) {
                $request = $request->asMultipart()->attach($key, (string) $value);
            }

            $response = $request->post("{$this->baseUrl}/api/analyze-terrain");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json('analysis', []),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('error', 'Unknown error'),
            ];
        } catch (\Exception $e) {
            Log::error('AI terrain analysis failed', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => 'Service unavailable',
                'message' => $e->getMessage(),
            ];
        }
    }
}
