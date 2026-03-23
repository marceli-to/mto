<script setup>
import { ref, watch, onMounted, computed } from 'vue'
import axios from 'axios'
import { PhTrash } from '@phosphor-icons/vue'
import vueFilePond from 'vue-filepond'
import 'filepond/dist/filepond.min.css'
import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type'
import FilePondPluginImagePreview from 'filepond-plugin-image-preview'
import 'filepond-plugin-image-preview/dist/filepond-plugin-image-preview.min.css'

const FilePond = vueFilePond(FilePondPluginFileValidateType, FilePondPluginImagePreview)

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  label: {
    type: String,
    default: 'Receipt'
  },
  existingFile: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['update:modelValue', 'delete-existing', 'uploaded'])

const pond = ref(null)
const files = ref([])
const uploadedFilename = ref('')
const showExisting = ref(true)

const hasExistingFile = computed(() => props.existingFile && showExisting.value && !uploadedFilename.value)

function deleteExisting() {
  showExisting.value = false
  emit('delete-existing')
}

const serverConfig = {
  process: (fieldName, file, metadata, load, error, progress, abort) => {
    const formData = new FormData()
    formData.append(fieldName, file, file.name)

    const controller = new AbortController()

    axios.post('/api/upload/temp', formData, {
      signal: controller.signal,
      onUploadProgress: (e) => {
        progress(e.lengthComputable, e.loaded, e.total)
      }
    })
    .then(response => {
      uploadedFilename.value = response.data.filename
      emit('update:modelValue', response.data.filename)
      emit('uploaded', response.data.filename)
      load(response.data.filename)
    })
    .catch(err => {
      if (axios.isCancel(err)) {
        abort()
      } else {
        error('Upload failed')
      }
    })

    return {
      abort: () => {
        controller.abort()
        abort()
      }
    }
  },
  revert: (uniqueFileId, load, error) => {
    axios.delete('/api/upload/revert', {
      data: uniqueFileId,
      headers: { 'Content-Type': 'text/plain' }
    })
    .then(() => {
      uploadedFilename.value = ''
      emit('update:modelValue', '')
      load()
    })
    .catch(() => {
      error('Revert failed')
    })
  }
}

function handleInit() {
  // FilePond initialized
}

function handleProcessFile(error, file) {
  if (error) {
    console.error('Process error:', error)
  }
}

function handleRemoveFile(error, file) {
  if (!error) {
    uploadedFilename.value = ''
    emit('update:modelValue', '')
  }
}

watch(() => props.modelValue, (newVal) => {
  if (newVal && newVal !== uploadedFilename.value) {
    uploadedFilename.value = newVal
  }
})

onMounted(() => {
  if (props.modelValue) {
    uploadedFilename.value = props.modelValue
  }
})
</script>

<template>
  <div>
    <label v-if="label" class="block text-sm text-gray-500 mb-2">{{ label }}</label>

    <div v-if="hasExistingFile" class="relative rounded-md bg-gray-50 border border-gray-200 p-4 flex justify-center">
      <button
        type="button"
        @click="deleteExisting"
        class="absolute top-2 right-2 p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 cursor-pointer rounded-sm transition-colors"
        title="Delete receipt"
      >
        <PhTrash class="w-5 h-5" />
      </button>
      <a :href="`/storage/media/expenses/${existingFile}`" target="_blank">
        <img
          :src="`/storage/media/expenses/${existingFile}`"
          :alt="existingFile"
          class="max-w-full max-h-48 rounded-md"
        />
      </a>
    </div>

    <FilePond
      v-if="!hasExistingFile"
      ref="pond"
      :files="files"
      :server="serverConfig"
      :allow-multiple="false"
      :accepted-file-types="['image/jpeg', 'application/pdf']"
      label-idle="Drop JPG or PDF file or <span class='filepond--label-action'>Browse</span>"
      credits=""
      @init="handleInit"
      @processfile="handleProcessFile"
      @removefile="handleRemoveFile"
    />
  </div>
</template>

<style>
.filepond--root {
  font-family: inherit;
  font-size: inherit;
}

.filepond--panel-root {
  border-radius: 0.125rem;
  background-color: #f9fafb;
  border: 1px solid #e5e7eb;
}

.filepond--drop-label {
  color: #6b7280;
}

.filepond--label-action {
  text-decoration: underline;
  color: #374151;
}

.filepond--item-panel {
  border-radius: 0.125rem;
}
</style>
