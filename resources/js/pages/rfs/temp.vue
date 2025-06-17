<script setup>
import AppLayout from '@/layouts/AppLayout.vue'
import { usePage, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

const page = usePage();
const currentPath = new URL(page.props.ziggy.location).pathname;

// Form data
const form = useForm({
  nama: page.props.auth.user.name || '',
  npk: page.props.auth.user.npk || '',
  priority: 'medium',
  tanggal_input: new Date().toISOString().split('T')[0],
  detail_keperluan: '',
  lampiran: null
})

const fileInput = ref(null)
const selectedFileName = ref('')

// Handle file selection
const handleFileSelect = (event) => {
  const file = event.target.files[0]
  if (file) {
    form.lampiran = file
    selectedFileName.value = file.name
  }
}

// Submit form
const submitForm = () => {
  form.post('/rfs', {
    onSuccess: () => {
      // Reset form or redirect
      form.reset()
      selectedFileName.value = ''
    }
  })
}

// Reset form
const resetForm = () => {
  form.reset()
  selectedFileName.value = ''
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}
</script>

<template>
  <AppLayout>
    <div class="p-6">
      <div class="max-w-2xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
          <h1 class="text-2xl font-bold text-foreground mb-2">Request for Service</h1>
          <p class="text-muted-foreground">Fill this form below to request a service</p>
        </div>

        <!-- Form -->
        <div class="bg-card rounded-lg shadow-sm border border-border p-6">
          <form @submit.prevent="submitForm" class="space-y-6">
            
            <!-- Nama (Read-only) -->
            <div class="space-y-2">
              <label class="text-sm font-medium text-card-foreground">Name :</label>
              <input 
                type="text" 
                v-model="form.nama"
                class="w-full px-3 py-2 bg-muted border border-border rounded-md text-card-foreground cursor-not-allowed"
                readonly
                disabled
              />
            </div>

            <!-- NPK (Read-only) -->
            <div class="space-y-2">
              <label class="text-sm font-medium text-card-foreground">NPK :</label>
              <input 
                type="text" 
                v-model="form.npk"
                class="w-full px-3 py-2 bg-muted border border-border rounded-md text-card-foreground cursor-not-allowed"
                readonly
                disabled
              />
            </div>

            <!-- Priority -->
            <div class="space-y-2">
              <label class="text-sm font-medium text-card-foreground">Priority :</label>
              <select 
                v-model="form.priority"
                class="w-full px-3 py-2 bg-background border border-border rounded-md text-card-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                :class="{ 'border-red-500': form.errors.priority }"
              >
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
                <option value="urgent">Urgent</option>
              </select>
              <div v-if="form.errors.priority" class="text-red-500 text-sm">{{ form.errors.priority }}</div>
            </div>

            <!-- Tanggal Input (Read-only) -->
            <div class="space-y-2">
              <label class="text-sm font-medium text-card-foreground">Submit Date :</label>
              <input 
                type="date" 
                v-model="form.tanggal_input"
                class="w-full px-3 py-2 bg-muted border border-border rounded-md text-card-foreground cursor-not-allowed"
                readonly
                disabled
              />
            </div>

            <!-- Detail Keperluan -->
            <div class="space-y-2">
              <label class="text-sm font-medium text-card-foreground">Requirement details :</label>
              <textarea 
                v-model="form.detail_keperluan"
                rows="5"
                class="w-full px-3 py-2 bg-background border border-border rounded-md text-card-foreground focus:outline-none focus:ring-2 focus:ring-ring resize-none"
                :class="{ 'border-red-500': form.errors.detail_keperluan }"
                placeholder="Describe the details of your service needs or requests..."
                required
              ></textarea>
              <div v-if="form.errors.detail_keperluan" class="text-red-500 text-sm">{{ form.errors.detail_keperluan }}</div>
            </div>

            <!-- Lampiran -->
            <div class="space-y-2">
              <label class="text-sm font-medium text-card-foreground">Attachment : </label>
              <div class="flex items-center space-x-3">
                <input 
                  type="file"
                  ref="fileInput"
                  @change="handleFileSelect"
                  class="hidden"
                  accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.xlsx,.xls"
                />
                <button
                  type="button"
                  @click="fileInput?.click()"
                  class="px-4 py-2 bg-secondary text-secondary-foreground border border-border rounded-md hover:bg-secondary/80 transition-colors"
                >
                  Pilih File
                </button>
                <span v-if="selectedFileName" class="text-sm text-muted-foreground">{{ selectedFileName }}</span>
                <span v-else class="text-sm text-muted-foreground">No files selected</span>
              </div>
              <p class="text-xs text-muted-foreground">Supported formats: PDF, DOC, DOCX, JPG, PNG, XLSX, XLS (Max: 10MB)</p>
              <div v-if="form.errors.lampiran" class="text-red-500 text-sm">{{ form.errors.lampiran }}</div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end space-x-3 pt-4 border-t border-border">
              <button
                type="button"
                @click="resetForm"
                class="px-6 py-2 border border-border rounded-md text-muted-foreground hover:bg-muted transition-colors"
              >
                Reset
              </button>
              <button
                type="submit"
                :disabled="form.processing"
                class="px-6 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <span v-if="form.processing">Mengirim...</span>
                <span v-else>Submit Request</span>
              </button>
            </div>
          </form>
        </div>

        <!-- Status Messages -->
        <div v-if="form.recentlySuccessful" class="mt-4 p-4 bg-green-50 border border-green-200 rounded-md">
          <p class="text-green-800">Request berhasil dikirim!</p>
        </div>
      </div>
    </div>
  </AppLayout>
</template>