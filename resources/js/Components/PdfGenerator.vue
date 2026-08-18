<template>
  <div class="pdf-generator">
    <!-- Step 1: Upload Images -->
    <div class="section">
      <h2>1. Upload Images</h2>
      <input
        type="file"
        multiple
        accept="image/jpeg,image/png,image/gif,image/webp"
        @change="onFilesSelected"
      />
      <button :disabled="!selectedFiles.length || uploading" @click="uploadImages">
        {{ uploading ? 'Uploading...' : 'Upload Images' }}
      </button>

      <div v-if="uploadedImages.length" class="image-preview">
        <div v-for="img in uploadedImages" :key="img.id" class="thumb">
          <img :src="img.url" :alt="img.original_filename" />
          <small>{{ img.original_filename }}</small>
        </div>
      </div>

      <p v-if="uploadError" class="error">{{ uploadError }}</p>
    </div>

    <!-- Step 2: Configure PDF -->
    <div class="section" v-if="uploadedImages.length">
      <h2>2. Configure PDF</h2>

      <label>
        Template (optional)
        <select v-model="form.template_id">
          <option value="">— Default layout —</option>
          <option v-for="t in templates" :key="t.id" :value="t.id">{{ t.name }}</option>
        </select>
      </label>

      <label>
        Title
        <input v-model="form.variables.title" placeholder="My Photo Book" />
      </label>

      <label>
        Subtitle
        <input v-model="form.variables.subtitle" placeholder="A collection of memories" />
      </label>

      <button :disabled="generating" @click="generatePdf">
        {{ generating ? 'Generating...' : 'Generate PDF' }}
      </button>

      <p v-if="pdfError" class="error">{{ pdfError }}</p>
    </div>

    <!-- Step 3: Status & Download -->
    <div class="section" v-if="jobUuid">
      <h2>3. Download PDF</h2>

      <p>Status: <strong>{{ jobStatus }}</strong></p>

      <div v-if="jobStatus === 'completed'">
        <a :href="pdfUrl" target="_blank" class="download-btn">⬇ Download PDF</a>
      </div>

      <div v-if="jobStatus === 'failed'" class="error">
        Generation failed. Please try again.
      </div>

      <div v-if="jobStatus === 'pending' || jobStatus === 'processing'">
        <progress max="100" :value="progress"></progress>
        <small>Processing… please wait.</small>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onUnmounted } from 'vue'

const props = defineProps({
  apiBase: { type: String, default: '/api' },
  templates: { type: Array, default: () => [] },
})

// Upload state
const selectedFiles = ref([])
const uploadedImages = ref([])
const uploading = ref(false)
const uploadError = ref(null)

// PDF form
const form = reactive({
  template_id: '',
  variables: { title: '', subtitle: '' },
})

// Generation state
const generating = ref(false)
const pdfError = ref(null)
const jobUuid = ref(null)
const jobStatus = ref(null)
const pdfUrl = ref(null)
const progress = ref(0)

let pollInterval = null

function onFilesSelected(e) {
  selectedFiles.value = Array.from(e.target.files)
}

async function uploadImages() {
  uploading.value = true
  uploadError.value = null

  const formData = new FormData()
  selectedFiles.value.forEach(f => formData.append('images[]', f))

  try {
    const res = await fetch(`${props.apiBase}/images`, {
      method: 'POST',
      body: formData,
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })

    if (!res.ok) {
      const err = await res.json()
      throw new Error(err.message || 'Upload failed')
    }

    const json = await res.json()
    uploadedImages.value = json.data
  } catch (e) {
    uploadError.value = e.message
  } finally {
    uploading.value = false
  }
}

async function generatePdf() {
  generating.value = true
  pdfError.value = null

  const payload = {
    template_id: form.template_id || null,
    image_ids: uploadedImages.value.map(i => i.id),
    variables: { ...form.variables },
  }

  try {
    const res = await fetch(`${props.apiBase}/pdfs`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify(payload),
    })

    if (!res.ok) {
      const err = await res.json()
      throw new Error(err.message || 'Could not queue PDF generation')
    }

    const json = await res.json()
    jobUuid.value = json.data.uuid
    jobStatus.value = json.data.status
    progress.value = 10

    // Start polling
    pollInterval = setInterval(pollStatus, 3000)
  } catch (e) {
    pdfError.value = e.message
  } finally {
    generating.value = false
  }
}

async function pollStatus() {
  if (!jobUuid.value) return

  try {
    const res = await fetch(`${props.apiBase}/pdfs/${jobUuid.value}`)
    const json = await res.json()
    const job = json.data

    jobStatus.value = job.status
    pdfUrl.value = job.pdf_url

    if (job.status === 'processing') progress.value = 60
    if (job.status === 'completed') {
      progress.value = 100
      clearInterval(pollInterval)
    }
    if (job.status === 'failed') {
      clearInterval(pollInterval)
    }
  } catch (_) {
    // silently ignore polling errors
  }
}

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval)
})
</script>

<style scoped>
.pdf-generator { max-width: 700px; margin: 0 auto; font-family: sans-serif; }
.section { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 24px; margin-bottom: 20px; }
h2 { font-size: 18px; margin-bottom: 16px; }
label { display: block; margin-bottom: 12px; font-size: 14px; font-weight: 500; }
input[type="text"], select { display: block; width: 100%; margin-top: 4px; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 14px; }
button { padding: 10px 20px; background: #4f46e5; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; }
button:disabled { opacity: 0.5; cursor: not-allowed; }
.error { color: #dc2626; font-size: 14px; margin-top: 8px; }
.image-preview { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; }
.thumb { text-align: center; }
.thumb img { width: 80px; height: 80px; object-fit: cover; border-radius: 4px; border: 1px solid #e5e7eb; }
.thumb small { display: block; font-size: 10px; color: #6b7280; max-width: 80px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.download-btn { display: inline-block; padding: 10px 20px; background: #16a34a; color: white; border-radius: 4px; text-decoration: none; }
progress { width: 100%; height: 8px; margin-top: 8px; }
</style>
