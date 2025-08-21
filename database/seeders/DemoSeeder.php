<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Integration;
use App\Models\AdAccount;
use App\Models\AdCampaign;
use App\Models\AdMetric;
use Carbon\Carbon;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Create demo tenant
        $tenant = Tenant::create([
            'name' => 'Demo Company',
            'slug' => 'demo-company',
            'status' => 'active',
            'settings' => json_encode([
                'timezone' => 'UTC',
                'currency' => 'USD'
            ])
        ]);

        // Create demo admin user
        $adminUser = User::create([
            'name' => 'Demo Admin',
            'email' => 'admin@demo.com',
            'password' => Hash::make('password'),
            'default_tenant_id' => $tenant->id
        ]);

        // Create demo viewer user
        $viewerUser = User::create([
            'name' => 'Demo Viewer',
            'email' => 'viewer@demo.com',
            'password' => Hash::make('password'),
            'default_tenant_id' => $tenant->id
        ]);

        // Attach users to tenant
        $tenant->users()->attach($adminUser->id, ['role' => 'admin']);
        $tenant->users()->attach($viewerUser->id, ['role' => 'viewer']);

        // Create integrations
        $platforms = ['facebook', 'google', 'tiktok'];
        $integrations = [];

        foreach ($platforms as $platform) {
            $integration = Integration::create([
                'tenant_id' => $tenant->id,
                'platform' => $platform,
                'app_config' => json_encode([
                    'app_id' => 'demo_' . $platform . '_app_id',
                    'app_secret' => 'demo_' . $platform . '_secret',
                    'access_token' => 'demo_' . $platform . '_token'
                ]),
                'status' => 'active',
                'created_by' => $adminUser->id
            ]);
            $integrations[$platform] = $integration;
        }

        // Create ad accounts
        $accounts = [];
        foreach ($platforms as $platform) {
            for ($i = 1; $i <= 2; $i++) {
                $account = AdAccount::create([
                    'tenant_id' => $tenant->id,
                    'integration_id' => $integrations[$platform]->id,
                    'external_account_id' => $platform . '_account_' . $i,
                    'account_name' => ucfirst($platform) . ' Account ' . $i,
                    'status' => 'active'
                ]);
                $accounts[] = $account;
            }
        }

        // Create campaigns
        $objectives = ['awareness', 'leads', 'sales', 'calls'];
        $campaigns = [];

        foreach ($accounts as $account) {
            foreach ($objectives as $objective) {
                $campaign = AdCampaign::create([
                    'tenant_id' => $tenant->id,
                    'ad_account_id' => $account->id,
                    'external_campaign_id' => $account->external_account_id . '_campaign_' . $objective,
                    'name' => ucfirst($objective) . ' Campaign - ' . $account->account_name,
                    'objective' => $objective,
                    'status' => 'active'
                ]);
                $campaigns[] = $campaign;
            }
        }

        // Generate metrics for the last 30 days
        $startDate = Carbon::now()->subDays(30);
        $endDate = Carbon::now();

        foreach ($campaigns as $campaign) {
            $currentDate = $startDate->copy();
            
            while ($currentDate <= $endDate) {
                // Generate realistic metrics based on objective
                $metrics = $this->generateMetricsForObjective($campaign->objective);
                
                AdMetric::create([
                    'tenant_id' => $tenant->id,
                    'date' => $currentDate->format('Y-m-d'),
                    'platform' => $this->getPlatformFromAccount($campaign->adAccount),
                    'ad_account_id' => $campaign->ad_account_id,
                    'ad_campaign_id' => $campaign->id,
                    'objective' => $campaign->objective,
                    'spend' => $metrics['spend'],
                    'impressions' => $metrics['impressions'],
                    'reach' => $metrics['reach'],
                    'clicks' => $metrics['clicks'],
                    'video_views' => $metrics['video_views'],
                    'conversions' => $metrics['conversions'],
                    'revenue' => $metrics['revenue'],
                    'purchases' => $metrics['purchases'],
                    'leads' => $metrics['leads'],
                    'calls' => $metrics['calls'],
                    'sessions' => $metrics['sessions'],
                    'atc' => $metrics['atc'],
                    'checksum' => sha1($tenant->id . $currentDate->format('Y-m-d') . $campaign->id)
                ]);
                
                $currentDate->addDay();
            }
        }
    }

    private function generateMetricsForObjective(string $objective): array
    {
        $baseSpend = rand(100, 1000);
        $baseImpressions = rand(10000, 100000);
        $baseClicks = rand(100, 2000);
        
        $metrics = [
            'spend' => $baseSpend,
            'impressions' => $baseImpressions,
            'reach' => rand(8000, $baseImpressions),
            'clicks' => $baseClicks,
            'video_views' => rand(50, $baseClicks),
            'conversions' => 0,
            'revenue' => 0,
            'purchases' => 0,
            'leads' => 0,
            'calls' => 0,
            'sessions' => rand($baseClicks, $baseClicks * 2),
            'atc' => 0
        ];

        switch ($objective) {
            case 'awareness':
                // Focus on impressions and reach
                $metrics['video_views'] = rand(500, 2000);
                break;
                
            case 'leads':
                // Focus on lead generation
                $metrics['leads'] = rand(5, 50);
                $metrics['conversions'] = $metrics['leads'];
                break;
                
            case 'sales':
                // Focus on purchases and revenue
                $metrics['purchases'] = rand(2, 20);
                $metrics['revenue'] = $metrics['purchases'] * rand(50, 500);
                $metrics['conversions'] = $metrics['purchases'];
                $metrics['atc'] = rand($metrics['purchases'], $metrics['purchases'] * 3);
                break;
                
            case 'calls':
                // Focus on phone calls
                $metrics['calls'] = rand(3, 30);
                $metrics['conversions'] = $metrics['calls'];
                break;
        }

        return $metrics;
    }

    private function getPlatformFromAccount($account): string
    {
        $integration = $account->integration;
        return $integration->platform;
    }
}
