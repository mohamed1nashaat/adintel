<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click="$emit('close')">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 shadow-lg rounded-md bg-white" @click.stop>
      <div class="flex items-center justify-between pb-4 border-b">
        <h3 class="text-lg font-semibold text-gray-900">Upload Leads File</h3>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>

      <div class="mt-6">
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Select CSV File</label>
          <input type="file" accept=".csv" @change="handleFileSelect" class="w-full">
        </div>
        
        <div class="flex justify-end space-x-3 pt-4 border-t">
          <button @click="$emit('close')" class="px-4 py-2 text-gray-600 border rounded">Cancel</button>
          <button @click="uploadFile" class="px-4 py-2 bg-blue-600 text-white rounded">Upload</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';

const emit = defineEmits<{
  close: []
  upload: [data: any]
}>()

const selectedFile = ref<File | null>(null)

const handleFileSelect = (event: Event) => {
  const target = event.target as HTMLInputElement
  selectedFile.value = target.files?.[0] || null
}

const uploadFile = () => {
  if (selectedFile.value) {
    emit('upload', { file: selectedFile.value })
  }
}
</script>
