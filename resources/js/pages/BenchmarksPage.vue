<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="md:flex md:items-center md:justify-between">
      <div class="flex-1 min-w-0">
        <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
          Industry Benchmarks
        </h2>
        <p class="mt-1 text-sm text-gray-500">
          Compare your performance against industry standards and competitors
        </p>
      </div>
      <div class="mt-4 flex md:mt-0 md:ml-4 space-x-3">
        <select
          v-model="selectedIndustry"
          class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
        >
          <option value="">All Industries</option>
          <option value="ecommerce">E-commerce</option>
          <option value="saas">SaaS</option>
          <option value="finance">Finance</option>
          <option value="healthcare">Healthcare</option>
          <option value="education">Education</option>
        </select>
        <button
          @click="refreshBenchmarks"
          class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
          </svg>
          Refresh
        </button>
      </div>
    </div>

    <!-- Performance Overview -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <svg class="h-6 w-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
              </svg>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Your CTR</dt>
                <dd class="flex items-baseline">
                  <div class="text-lg font-medium text-gray-900">2.4%</div>
                  <div class="ml-2 flex items-baseline text-sm font-semibold text-green-600">
                    <svg class="self-center flex-shrink-0 h-4 w-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="sr-only">Increased by</span>
                    12%
                  </div>
                </dd>
                <dd class="text-xs text-gray-500">vs Industry: 1.9%</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <svg class="h-6 w-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
              </svg>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Your CPC</dt>
                <dd class="flex items-baseline">
                  <div class="text-lg font-medium text-gray-900">$1.23</div>
                  <div class="ml-2 flex items-baseline text-sm font-semibold text-green-600">
                    <svg class="self-center flex-shrink-0 h-4 w-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="sr-only">Decreased by</span>
                    8%
                  </div>
                </dd>
                <dd class="text-xs text-gray-500">vs Industry: $1.45</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <svg class="h-6 w-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
              </svg>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Your CVR</dt>
                <dd class="flex items-baseline">
                  <div class="text-lg font-medium text-gray-900">3.8%</div>
                  <div class="ml-2 flex items-baseline text-sm font-semibold text-red-600">
                    <svg class="self-center flex-shrink-0 h-4 w-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="sr-only">Decreased by</span>
                    5%
                  </div>
                </dd>
                <dd class="text-xs text-gray-500">vs Industry: 4.2%</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <svg class="h-6 w-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
              </svg>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Your ROAS</dt>
                <dd class="flex items-baseline">
                  <div class="text-lg font-medium text-gray-900">4.2x</div>
                  <div class="ml-2 flex items-baseline text-sm font-semibold text-green-600">
                    <svg class="self-center flex-shrink-0 h-4 w-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="sr-only">Increased by</span>
                    15%
                  </div>
                </dd>
                <dd class="text-xs text-gray-500">vs Industry: 3.1x</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Benchmark Comparison Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- CTR Benchmark -->
      <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-medium text-gray-900">Click-Through Rate Comparison</h3>
          <p class="text-sm text-gray-500">Your performance vs industry benchmarks</p>
        </div>
        <div class="p-6">
          <div class="space-y-4">
            <div class="flex items-center justify-between">
              <span class="text-sm font-medium text-gray-900">Your Performance</span>
              <span class="text-sm text-gray-500">2.4%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
              <div class="bg-blue-600 h-2 rounded-full" style="width: 80%"></div>
            </div>
            
            <div class="flex items-center justify-between">
              <span class="text-sm font-medium text-gray-900">Industry Average</span>
              <span class="text-sm text-gray-500">1.9%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
              <div class="bg-gray-400 h-2 rounded-full" style="width: 63%"></div>
            </div>
            
            <div class="flex items-center justify-between">
              <span class="text-sm font-medium text-gray-900">Top 10%</span>
              <span class="text-sm text-gray-500">3.2%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
              <div class="bg-green-500 h-2 rounded-full" style="width: 100%"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- CPC Benchmark -->
      <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-medium text-gray-900">Cost Per Click Comparison</h3>
          <p class="text-sm text-gray-500">Lower is better</p>
        </div>
        <div class="p-6">
          <div class="space-y-4">
            <div class="flex items-center justify-between">
              <span class="text-sm font-medium text-gray-900">Your Performance</span>
              <span class="text-sm text-gray-500">$1.23</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
              <div class="bg-green-600 h-2 rounded-full" style="width: 75%"></div>
            </div>
            
            <div class="flex items-center justify-between">
              <span class="text-sm font-medium text-gray-900">Industry Average</span>
              <span class="text-sm text-gray-500">$1.45</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
              <div class="bg-gray-400 h-2 rounded-full" style="width: 90%"></div>
            </div>
            
            <div class="flex items-center justify-between">
              <span class="text-sm font-medium text-gray-900">Top 10%</span>
              <span class="text-sm text-gray-500">$0.89</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
              <div class="bg-green-500 h-2 rounded-full" style="width: 55%"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Industry Benchmarks Table -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
      <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
        <h3 class="text-lg leading-6 font-medium text-gray-900">Industry Benchmarks by Sector</h3>
        <p class="mt-1 max-w-2xl text-sm text-gray-500">Compare performance across different industries</p>
      </div>
      
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Industry</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg CTR</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg CPC</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg CVR</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg ROAS</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Your Rank</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="benchmark in benchmarks" :key="benchmark.industry" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="text-sm font-medium text-gray-900">{{ benchmark.industry }}</div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ benchmark.avgCtr }}%</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${{ benchmark.avgCpc }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ benchmark.avgCvr }}%</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ benchmark.avgRoas }}x</td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="getRankBadgeClass(benchmark.yourRank)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                  {{ benchmark.yourRank }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Recommendations -->
    <div class="bg-white shadow rounded-lg">
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Performance Recommendations</h3>
        <p class="text-sm text-gray-500">AI-powered suggestions to improve your metrics</p>
      </div>
      <div class="p-6">
        <div class="space-y-4">
          <div class="flex items-start space-x-3">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
              </svg>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-900">Great CTR Performance</p>
              <p class="text-sm text-gray-500">Your click-through rate is 26% above industry average. Keep using compelling ad copy and relevant targeting.</p>
            </div>
          </div>
          
          <div class="flex items-start space-x-3">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
              </svg>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-900">Improve Conversion Rate</p>
              <p class="text-sm text-gray-500">Your CVR is 10% below industry average. Consider optimizing landing pages and improving user experience.</p>
            </div>
          </div>
          
          <div class="flex items-start space-x-3">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
              </svg>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-900">Optimize for Mobile</p>
              <p class="text-sm text-gray-500">Mobile traffic shows 15% lower conversion rates. Focus on mobile-first design and faster loading times.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'

const selectedIndustry = ref('')

const benchmarks = ref([
  {
    industry: 'E-commerce',
    avgCtr: 1.8,
    avgCpc: 1.52,
    avgCvr: 4.1,
    avgRoas: 3.2,
    yourRank: 'Top 25%'
  },
  {
    industry: 'SaaS',
    avgCtr: 2.1,
    avgCpc: 2.34,
    avgCvr: 3.8,
    avgRoas: 4.1,
    yourRank: 'Top 10%'
  },
  {
    industry: 'Finance',
    avgCtr: 1.6,
    avgCpc: 3.45,
    avgCvr: 5.2,
    avgRoas: 2.8,
    yourRank: 'Average'
  },
  {
    industry: 'Healthcare',
    avgCtr: 1.9,
    avgCpc: 2.89,
    avgCvr: 4.5,
    avgRoas: 3.5,
    yourRank: 'Top 25%'
  },
  {
    industry: 'Education',
    avgCtr: 2.3,
    avgCpc: 1.98,
    avgCvr: 3.2,
    avgRoas: 2.9,
    yourRank: 'Top 50%'
  }
])

const getRankBadgeClass = (rank: string) => {
  switch (rank) {
    case 'Top 10%':
      return 'bg-green-100 text-green-800'
    case 'Top 25%':
      return 'bg-blue-100 text-blue-800'
    case 'Top 50%':
      return 'bg-yellow-100 text-yellow-800'
    case 'Average':
      return 'bg-gray-100 text-gray-800'
    default:
      return 'bg-red-100 text-red-800'
  }
}

const refreshBenchmarks = async () => {
  try {
    // TODO: Implement API call to refresh benchmarks
    console.log('Refreshing benchmarks for industry:', selectedIndustry.value)
  } catch (error) {
    console.error('Error refreshing benchmarks:', error)
  }
}

onMounted(() => {
  // TODO: Load benchmarks from API
})
</script>
