<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadSource;
use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use Illuminate\Support\Facades\Log;

class GoogleSheetsService
{
    private Client $client;
    private Sheets $service;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setApplicationName('AdIntel Lead Management');
        $this->client->setScopes([Sheets::SPREADSHEETS]);
        $this->client->setAuthConfig(config('services.google.credentials_path'));
        $this->client->setAccessType('offline');
        
        $this->service = new Sheets($this->client);
    }

    public function syncLead(Lead $lead): bool
    {
        try {
            $leadSource = $lead->leadSource;
            
            if (!$leadSource->hasGoogleSheets()) {
                throw new \Exception('Lead source does not have Google Sheets configuration');
            }

            $spreadsheetId = $leadSource->google_sheet_id;
            $sheetName = $leadSource->google_sheet_name ?: 'Leads';

            // Ensure headers exist
            $this->ensureHeaders($spreadsheetId, $sheetName);

            // Add lead data
            $this->appendLeadData($spreadsheetId, $sheetName, $lead);

            // Mark as synced
            $lead->update([
                'synced_to_sheets' => true,
                'sheets_synced_at' => now(),
            ]);

            Log::info('Lead synced to Google Sheets', [
                'lead_id' => $lead->id,
                'spreadsheet_id' => $spreadsheetId,
                'sheet_name' => $sheetName,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to sync lead to Google Sheets', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function syncMultipleLeads(array $leads): array
    {
        $results = ['success' => 0, 'failed' => 0, 'errors' => []];

        foreach ($leads as $lead) {
            try {
                $this->syncLead($lead);
                $results['success']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'lead_id' => $lead->id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    public function createSpreadsheet(string $title, array $headers = null): array
    {
        try {
            $spreadsheet = new \Google\Service\Sheets\Spreadsheet([
                'properties' => [
                    'title' => $title
                ]
            ]);

            $spreadsheet = $this->service->spreadsheets->create($spreadsheet);
            $spreadsheetId = $spreadsheet->getSpreadsheetId();

            // Add headers if provided
            if ($headers) {
                $this->setHeaders($spreadsheetId, 'Sheet1', $headers);
            } else {
                $this->ensureHeaders($spreadsheetId, 'Sheet1');
            }

            return [
                'spreadsheet_id' => $spreadsheetId,
                'spreadsheet_url' => "https://docs.google.com/spreadsheets/d/{$spreadsheetId}",
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create Google Spreadsheet', [
                'title' => $title,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function getSpreadsheetInfo(string $spreadsheetId): array
    {
        try {
            $spreadsheet = $this->service->spreadsheets->get($spreadsheetId);
            
            return [
                'title' => $spreadsheet->getProperties()->getTitle(),
                'sheets' => collect($spreadsheet->getSheets())->map(function ($sheet) {
                    return [
                        'title' => $sheet->getProperties()->getTitle(),
                        'sheet_id' => $sheet->getProperties()->getSheetId(),
                    ];
                })->toArray(),
                'url' => "https://docs.google.com/spreadsheets/d/{$spreadsheetId}",
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get spreadsheet info', [
                'spreadsheet_id' => $spreadsheetId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function validateSpreadsheetAccess(string $spreadsheetId): bool
    {
        try {
            $this->service->spreadsheets->get($spreadsheetId);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getLeadsFromSheet(string $spreadsheetId, string $sheetName = 'Leads'): array
    {
        try {
            $range = "{$sheetName}!A:Z";
            $response = $this->service->spreadsheets_values->get($spreadsheetId, $range);
            $values = $response->getValues();

            if (empty($values)) {
                return [];
            }

            $headers = array_shift($values); // Remove header row
            $leads = [];

            foreach ($values as $row) {
                $lead = [];
                foreach ($headers as $index => $header) {
                    $lead[$header] = $row[$index] ?? '';
                }
                $leads[] = $lead;
            }

            return $leads;
        } catch (\Exception $e) {
            Log::error('Failed to get leads from sheet', [
                'spreadsheet_id' => $spreadsheetId,
                'sheet_name' => $sheetName,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function importLeadsFromSheet(LeadSource $leadSource): array
    {
        try {
            $leads = $this->getLeadsFromSheet(
                $leadSource->google_sheet_id,
                $leadSource->google_sheet_name ?: 'Leads'
            );

            $imported = 0;
            $skipped = 0;
            $errors = [];

            foreach ($leads as $leadData) {
                try {
                    // Skip if email already exists
                    if (Lead::where('email', $leadData['Email'] ?? '')->exists()) {
                        $skipped++;
                        continue;
                    }

                    // Map sheet data to lead fields
                    $mappedData = $this->mapSheetDataToLead($leadData, $leadSource);
                    
                    Lead::create($mappedData);
                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = [
                        'data' => $leadData,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            return [
                'imported' => $imported,
                'skipped' => $skipped,
                'errors' => $errors,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to import leads from sheet', [
                'lead_source_id' => $leadSource->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function ensureHeaders(string $spreadsheetId, string $sheetName): void
    {
        $headers = [
            'ID', 'Date', 'First Name', 'Last Name', 'Email', 'Phone',
            'Company', 'Job Title', 'Message', 'Status', 'Quality',
            'Estimated Value', 'Source', 'UTM Source', 'UTM Medium',
            'UTM Campaign', 'Landing Page', 'Assigned To'
        ];

        $this->setHeaders($spreadsheetId, $sheetName, $headers);
    }

    private function setHeaders(string $spreadsheetId, string $sheetName, array $headers): void
    {
        $range = "{$sheetName}!A1:" . chr(64 + count($headers)) . "1";
        $valueRange = new ValueRange([
            'values' => [$headers]
        ]);

        $this->service->spreadsheets_values->update(
            $spreadsheetId,
            $range,
            $valueRange,
            ['valueInputOption' => 'RAW']
        );
    }

    private function appendLeadData(string $spreadsheetId, string $sheetName, Lead $lead): void
    {
        $range = "{$sheetName}!A:A";
        $valueRange = new ValueRange([
            'values' => [$lead->toSheetsArray()]
        ]);

        $this->service->spreadsheets_values->append(
            $spreadsheetId,
            $range,
            $valueRange,
            [
                'valueInputOption' => 'RAW',
                'insertDataOption' => 'INSERT_ROWS'
            ]
        );
    }

    private function mapSheetDataToLead(array $sheetData, LeadSource $leadSource): array
    {
        return [
            'tenant_id' => $leadSource->tenant_id,
            'lead_source_id' => $leadSource->id,
            'first_name' => $sheetData['First Name'] ?? '',
            'last_name' => $sheetData['Last Name'] ?? '',
            'email' => $sheetData['Email'] ?? '',
            'phone' => $sheetData['Phone'] ?? null,
            'company' => $sheetData['Company'] ?? null,
            'job_title' => $sheetData['Job Title'] ?? null,
            'message' => $sheetData['Message'] ?? null,
            'status' => strtolower($sheetData['Status'] ?? 'new'),
            'quality' => strtolower($sheetData['Quality'] ?? null),
            'estimated_value' => is_numeric($sheetData['Estimated Value'] ?? null) 
                ? (float) $sheetData['Estimated Value'] 
                : null,
            'utm_source' => $sheetData['UTM Source'] ?? null,
            'utm_medium' => $sheetData['UTM Medium'] ?? null,
            'utm_campaign' => $sheetData['UTM Campaign'] ?? null,
            'landing_page' => $sheetData['Landing Page'] ?? null,
        ];
    }

    public function testConnection(): bool
    {
        try {
            // Try to access the user's drive to test authentication
            $this->service->spreadsheets->create(new \Google\Service\Sheets\Spreadsheet([
                'properties' => ['title' => 'Test Connection - Delete Me']
            ]));
            
            return true;
        } catch (\Exception $e) {
            Log::error('Google Sheets connection test failed', [
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
}
