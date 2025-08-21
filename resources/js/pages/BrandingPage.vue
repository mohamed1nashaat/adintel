<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="md:flex md:items-center md:justify-between">
      <div class="flex-1 min-w-0">
        <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
          Custom Branding
        </h2>
        <p class="mt-1 text-sm text-gray-500">
          Customize your brand identity with logos, colors, and themes
        </p>
      </div>
      <div class="mt-4 flex md:mt-0 md:ml-4 space-x-3">
        <button
          @click="saveBranding"
          class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          Save Changes
        </button>
      </div>
    </div>

    <!-- Branding Settings -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Logo Upload -->
      <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-medium text-gray-900">Logo Upload</h3>
          <p class="text-sm text-gray-500">Upload your company logo for reports and dashboards</p>
        </div>
        <div class="p-6">
          <div class="flex items-center space-x-6">
            <div class="shrink-0">
              <img class="h-16 w-16 object-cover rounded-lg" :src="currentLogo" alt="Current logo">
            </div>
            <div class="flex-1">
              <label for="logo-upload" class="block text-sm font-medium text-gray-700">
                Choose new logo
              </label>
              <input
                id="logo-upload"
                type="file"
                accept="image/*"
                @change="handleLogoUpload"
                class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
              >
              <p class="mt-1 text-xs text-gray-500">PNG, JPG up to 2MB</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Color Theme -->
      <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-medium text-gray-900">Color Theme</h3>
          <p class="text-sm text-gray-500">Customize your brand colors</p>
        </div>
        <div class="p-6 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Primary Color</label>
            <div class="mt-1 flex items-center space-x-3">
              <input
                v-model="branding.primaryColor"
                type="color"
                class="h-10 w-20 rounded border border-gray-300"
              >
              <input
                v-model="branding.primaryColor"
                type="text"
                class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                placeholder="#6366f1"
              >
            </div>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700">Secondary Color</label>
            <div class="mt-1 flex items-center space-x-3">
              <input
                v-model="branding.secondaryColor"
                type="color"
                class="h-10 w-20 rounded border border-gray-300"
              >
              <input
                v-model="branding.secondaryColor"
                type="text"
                class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                placeholder="#f59e0b"
              >
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Accent Color</label>
            <div class="mt-1 flex items-center space-x-3">
              <input
                v-model="branding.accentColor"
                type="color"
                class="h-10 w-20 rounded border border-gray-300"
              >
              <input
                v-model="branding.accentColor"
                type="text"
                class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                placeholder="#10b981"
              >
            </div>
          </div>
        </div>
      </div>

      <!-- Typography -->
      <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-medium text-gray-900">Typography</h3>
          <p class="text-sm text-gray-500">Choose fonts for your brand</p>
        </div>
        <div class="p-6 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Primary Font</label>
            <select
              v-model="branding.primaryFont"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            >
              <option value="Inter">Inter</option>
              <option value="Roboto">Roboto</option>
              <option value="Open Sans">Open Sans</option>
              <option value="Lato">Lato</option>
              <option value="Montserrat">Montserrat</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Secondary Font</label>
            <select
              v-model="branding.secondaryFont"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            >
              <option value="Inter">Inter</option>
              <option value="Roboto">Roboto</option>
              <option value="Open Sans">Open Sans</option>
              <option value="Lato">Lato</option>
              <option value="Montserrat">Montserrat</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Preview -->
      <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-medium text-gray-900">Preview</h3>
          <p class="text-sm text-gray-500">See how your branding looks</p>
        </div>
        <div class="p-6">
          <div class="border rounded-lg p-4" :style="previewStyle">
            <div class="flex items-center space-x-3 mb-4">
              <img :src="currentLogo" alt="Logo" class="h-8 w-8 rounded">
              <h4 class="text-lg font-semibold" :style="{ fontFamily: branding.primaryFont }">
                Your Company Name
              </h4>
            </div>
            <div class="space-y-2">
              <div class="h-4 rounded" :style="{ backgroundColor: branding.primaryColor, width: '60%' }"></div>
              <div class="h-3 bg-gray-200 rounded" style="width: 80%"></div>
              <div class="h-3 bg-gray-200 rounded" style="width: 40%"></div>
            </div>
            <div class="mt-4 flex space-x-2">
              <button
                class="px-3 py-1 rounded text-white text-sm"
                :style="{ backgroundColor: branding.primaryColor }"
              >
                Primary Button
              </button>
              <button
                class="px-3 py-1 rounded text-white text-sm"
                :style="{ backgroundColor: branding.secondaryColor }"
              >
                Secondary Button
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Current Settings -->
    <div class="bg-white shadow rounded-lg">
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Current Branding Settings</h3>
        <p class="text-sm text-gray-500">Active branding configuration</p>
      </div>
      <div class="p-6">
        <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
          <div>
            <dt class="text-sm font-medium text-gray-500">Primary Color</dt>
            <dd class="mt-1 flex items-center space-x-2">
              <div class="h-4 w-4 rounded" :style="{ backgroundColor: branding.primaryColor }"></div>
              <span class="text-sm text-gray-900">{{ branding.primaryColor }}</span>
            </dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">Secondary Color</dt>
            <dd class="mt-1 flex items-center space-x-2">
              <div class="h-4 w-4 rounded" :style="{ backgroundColor: branding.secondaryColor }"></div>
              <span class="text-sm text-gray-900">{{ branding.secondaryColor }}</span>
            </dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">Primary Font</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ branding.primaryFont }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ formatDate(branding.updatedAt) }}</dd>
          </div>
        </dl>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'

const currentLogo = ref('/favicon.ico')

const branding = ref({
  primaryColor: '#6366f1',
  secondaryColor: '#f59e0b',
  accentColor: '#10b981',
  primaryFont: 'Inter',
  secondaryFont: 'Inter',
  updatedAt: new Date().toISOString()
})

const previewStyle = computed(() => ({
  fontFamily: branding.value.primaryFont,
  borderColor: branding.value.primaryColor
}))

const handleLogoUpload = (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (file) {
    const reader = new FileReader()
    reader.onload = (e) => {
      currentLogo.value = e.target?.result as string
    }
    reader.readAsDataURL(file)
  }
}

const saveBranding = async () => {
  try {
    // TODO: Implement API call to save branding
    console.log('Saving branding:', branding.value)
    branding.value.updatedAt = new Date().toISOString()
    // Show success message
  } catch (error) {
    console.error('Error saving branding:', error)
  }
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString()
}

onMounted(() => {
  // TODO: Load current branding settings from API
})
</script>
