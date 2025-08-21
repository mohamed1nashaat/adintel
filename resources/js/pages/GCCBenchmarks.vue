<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="md:flex md:items-center md:justify-between">
      <div class="flex-1 min-w-0">
        <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
          📊 GCC Market Benchmarks
        </h2>
        <p class="mt-1 text-sm text-gray-500">
          Performance benchmarks and market intelligence for Gulf Cooperation Council countries
        </p>
      </div>
      <div class="mt-4 flex md:mt-0 md:ml-4 space-x-3">
        <select class="border border-gray-300 rounded-md px-3 py-2 text-sm">
          <option>All Industries</option>
          <option>E-commerce</option>
          <option>Real Estate</option>
          <option>Healthcare</option>
          <option>Education</option>
        </select>
        <button class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
          Export Report
        </button>
      </div>
    </div>

    <!-- GCC Countries Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div v-for="country in gccCountries" :key="country.code" class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center mb-4">
          <span class="text-3xl mr-3">{{ country.flag }}</span>
          <div>
            <h3 class="text-lg font-medium text-gray-900">{{ country.name }}</h3>
            <p class="text-sm text-gray-500">{{ country.population }} population</p>
          </div>
        </div>
        
        <div class="space-y-3">
          <div class="flex justify-between">
            <span class="text-sm text-gray-600">Avg CPM</span>
            <span class="text-sm font-medium text-gray-900">${{ country.cpm }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-sm text-gray-600">Avg CTR</span>
            <span class="text-sm font-medium text-gray-900">{{ country.ctr }}%</span>
          </div>
          <div class="flex justify-between">
            <span class="text-sm text-gray-600">Avg CPC</span>
            <span class="text-sm font-medium text-gray-900">${{ country.cpc }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-sm text-gray-600">ROAS</span>
            <span class="text-sm font-medium" :class="country.roas >= 3 ? 'text-green-600' : 'text-red-600'">
              {{ country.roas }}x
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Performance Calculator -->
    <div class="bg-white shadow rounded-lg p-6">
      <h3 class="text-lg font-medium text-gray-900 mb-4">🧮 Performance Calculator</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Budget (USD)</label>
          <input v-model="calculator.budget" type="number" class="w-full border border-gray-300 rounded-md px-3 py-2">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Target Country</label>
          <select v-model="calculator.country" class="w-full border border-gray-300 rounded-md px-3 py-2">
            <option v-for="country in gccCountries" :key="country.code" :value="country.code">
              {{ country.flag }} {{ country.name }}
            </option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Campaign Objective</label>
          <select v-model="calculator.objective" class="w-full border border-gray-300 rounded-md px-3 py-2">
            <option value="awareness">Brand Awareness</option>
            <option value="traffic">Website Traffic</option>
            <option value="leads">Lead Generation</option>
            <option value="sales">Sales</option>
          </select>
        </div>
        <div class="flex items-end">
          <button @click="calculateProjections" class="w-full px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
            Calculate
          </button>
        </div>
      </div>

      <!-- Calculation Results -->
      <div v-if="projections" class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-blue-50 p-4 rounded-lg">
          <p class="text-sm text-blue-600 font-medium">Estimated Impressions</p>
          <p class="text-2xl font-bold text-blue-900">{{ projections.impressions.toLocaleString() }}</p>
        </div>
        <div class="bg-green-50 p-4 rounded-lg">
          <p class="text-sm text-green-600 font-medium">Estimated Clicks</p>
          <p class="text-2xl font-bold text-green-900">{{ projections.clicks.toLocaleString() }}</p>
        </div>
        <div class="bg-purple-50 p-4 rounded-lg">
          <p class="text-sm text-purple-600 font-medium">Estimated Conversions</p>
          <p class="text-2xl font-bold text-purple-900">{{ projections.conversions }}</p>
        </div>
        <div class="bg-orange-50 p-4 rounded-lg">
          <p class="text-sm text-orange-600 font-medium">Projected ROAS</p>
          <p class="text-2xl font-bold text-orange-900">{{ projections.roas }}x</p>
        </div>
      </div>
    </div>

    <!-- Industry Benchmarks -->
    <div class="bg-white shadow rounded-lg p-6">
      <h3 class="text-lg font-medium text-gray-900 mb-4">Industry Benchmarks</h3>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Industry</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg CPM</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg CTR</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg CPC</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Conv. Rate</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ROAS</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="industry in industries" :key="industry.name" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <span class="text-lg mr-2">{{ industry.emoji }}</span>
                  <span class="text-sm font-medium text-gray-900">{{ industry.name }}</span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${{ industry.cpm }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ industry.ctr }}%</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${{ industry.cpc }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ industry.conversion_rate }}%</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium" 
                  :class="industry.roas >= 3 ? 'text-green-600' : 'text-red-600'">
                {{ industry.roas }}x
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Market Insights -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">🎯 Best Performing Platforms</h3>
        <div class="space-y-4">
          <div v-for="platform in topPlatforms" :key="platform.name" class="flex items-center justify-between">
            <div class="flex items-center">
              <span class="text-xl mr-3">{{ platform.emoji }}</span>
              <span class="text-sm font-medium text-gray-900">{{ platform.name }}</span>
            </div>
            <div class="text-right">
              <p class="text-sm font-medium text-gray-900">{{ platform.score }}/10</p>
              <p class="text-xs text-gray-500">Performance Score</p>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">📈 Market Trends</h3>
        <div class="space-y-4">
          <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
            <div>
              <p class="text-sm font-medium text-green-900">Mobile Traffic</p>
              <p class="text-xs text-green-700">Increasing across all GCC countries</p>
            </div>
            <span class="text-green-600 font-bold">+15%</span>
          </div>
          <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
            <div>
              <p class="text-sm font-medium text-blue-900">Video Content</p>
              <p class="text-xs text-blue-700">Higher engagement rates</p>
            </div>
            <span class="text-blue-600 font-bold">+23%</span>
          </div>
          <div class="flex items-center justify-between p-3 bg-purple-50 rounded-lg">
            <div>
              <p class="text-sm font-medium text-purple-900">E-commerce</p>
              <p class="text-xs text-purple-700">Growing market opportunity</p>
            </div>
            <span class="text-purple-600 font-bold">+31%</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

const gccCountries = ref([
  { code: 'SA', name: 'Saudi Arabia', flag: '🇸🇦', population: '35M', cpm: 3.45, ctr: 2.8, cpc: 1.25, roas: 4.2 },
  { code: 'AE', name: 'UAE', flag: '🇦🇪', population: '10M', cpm: 4.12, ctr: 3.1, cpc: 1.45, roas: 3.8 },
  { code: 'QA', name: 'Qatar', flag: '🇶🇦', population: '3M', cpm: 3.89, ctr: 2.9, cpc: 1.38, roas: 4.1 },
  { code: 'KW', name: 'Kuwait', flag: '🇰🇼', population: '4M', cpm: 3.67, ctr: 2.7, cpc: 1.32, roas: 3.9 },
  { code: 'BH', name: 'Bahrain', flag: '🇧🇭', population: '2M', cpm: 3.23, ctr: 2.6, cpc: 1.28, roas: 3.7 },
  { code: 'OM', name: 'Oman', flag: '🇴🇲', population: '5M', cpm: 2.98, ctr: 2.4, cpc: 1.15, roas: 3.5 }
])

const industries = ref([
  { name: 'E-commerce', emoji: '🛒', cpm: 3.25, ctr: 2.8, cpc: 1.15, conversion_rate: 3.2, roas: 4.1 },
  { name: 'Real Estate', emoji: '🏢', cpm: 4.50, ctr: 1.9, cpc: 2.35, conversion_rate: 1.8, roas: 3.8 },
  { name: 'Healthcare', emoji: '🏥', cpm: 3.80, ctr: 2.1, cpc: 1.80, conversion_rate: 2.5, roas: 3.2 },
  { name: 'Education', emoji: '🎓', cpm: 2.90, ctr: 3.2, cpc: 0.95, conversion_rate: 4.1, roas: 4.5 },
  { name: 'Finance', emoji: '💰', cpm: 5.20, ctr: 1.6, cpc: 3.25, conversion_rate: 1.2, roas: 2.8 },
  { name: 'Travel', emoji: '✈️', cpm: 3.15, ctr: 2.5, cpc: 1.25, conversion_rate: 2.8, roas: 3.6 }
])

const topPlatforms = ref([
  { name: 'Instagram', emoji: '📷', score: 9.2 },
  { name: 'Facebook', emoji: '📘', score: 8.8 },
  { name: 'TikTok', emoji: '🎵', score: 8.5 },
  { name: 'YouTube', emoji: '📺', score: 8.1 },
  { name: 'Snapchat', emoji: '👻', score: 7.9 }
])

const calculator = ref({
  budget: 1000,
  country: 'SA',
  objective: 'awareness'
})

const projections = ref<{
  impressions: number;
  clicks: number;
  conversions: number;
  roas: number;
} | null>(null)

const calculateProjections = () => {
  const selectedCountry = gccCountries.value.find(c => c.code === calculator.value.country)
  if (!selectedCountry) return

  const budget = calculator.value.budget
  const impressions = Math.round(budget / selectedCountry.cpm * 1000)
  const clicks = Math.round(impressions * (selectedCountry.ctr / 100))
  
  let conversionRate = 0.02 // Default 2%
  if (calculator.value.objective === 'leads') conversionRate = 0.05
  if (calculator.value.objective === 'sales') conversionRate = 0.03
  if (calculator.value.objective === 'traffic') conversionRate = 0.08

  const conversions = Math.round(clicks * conversionRate)
  
  projections.value = {
    impressions,
    clicks,
    conversions,
    roas: selectedCountry.roas
  }
}
</script>
