<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApi } from '@/composables/useApi'
import { useToast } from '@/composables/useToast'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import FormActions from '@/components/ui/FormActions.vue'

const route = useRoute()
const router = useRouter()
const { get, post } = useApi()
const { success, error } = useToast()

const isEdit = computed(() => !!route.params.id)
const title = computed(() => isEdit.value ? 'Edit Project' : 'New Project')

const loading = ref(false)
const saving = ref(false)
const errors = ref({})
const clients = ref([])
const rates = ref([])

const project = ref({
  name: '',
  description: '',
  client_id: '',
  rate_id: '',
  budget: '',
  is_collection: false,
  is_archive: false
})

async function fetchData() {
  loading.value = true
  try {
    const [clientsData, ratesData] = await Promise.all([
      get('/api/clients/get'),
      get('/api/rates/get')
    ])
    clients.value = clientsData.data || []
    rates.value = ratesData.data || []

    if (isEdit.value) {
      const data = await get(`/api/project/edit/${route.params.id}`)
      project.value = {
        ...data,
        client_id: data.client_id || '',
        rate_id: data.rate_id || ''
      }
    }
  } catch (e) {
    error('Failed to load data')
    if (isEdit.value) router.push({ name: 'projects' })
  } finally {
    loading.value = false
  }
}

function validate() {
  errors.value = {}
  if (!project.value.name?.trim()) {
    errors.value.name = 'Name is required'
  }
  if (!project.value.client_id) {
    errors.value.client_id = 'Client is required'
  }
  return Object.keys(errors.value).length === 0
}

async function submit() {
  if (!validate()) {
    error('Please fix the errors')
    return
  }

  saving.value = true
  try {
    if (isEdit.value) {
      await post(`/api/project/update/${route.params.id}`, project.value)
      success('Project updated')
    } else {
      await post('/api/project/create', project.value)
      success('Project created')
    }
    router.push({ name: 'projects' })
  } catch (e) {
    error('Failed to save project')
  } finally {
    saving.value = false
  }
}

const clientOptions = computed(() =>
  clients.value.map(c => ({ value: c.id, label: c.name }))
)

const rateOptions = computed(() =>
  rates.value.map(r => ({ value: r.id, label: `${r.description} (${r.amount} CHF/h)` }))
)

onMounted(fetchData)
</script>

<template>
  <div>
    <h1 class="text-xl font-bold text-gray-900 mb-6">{{ title }}</h1>

    <div v-if="loading" class="text-center py-12 text-gray-500">
      Loading...
    </div>

    <form v-else @submit.prevent="submit" class="bg-white rounded-lg shadow p-6 max-w-2xl">
      <div class="space-y-4">
        <BaseInput
          v-model="project.name"
          label="Name"
          required
          :error="errors.name"
          @focus="errors.name = null"
        />

        <BaseSelect
          v-model="project.client_id"
          label="Client"
          required
          :options="clientOptions"
          placeholder="Select a client..."
          :error="errors.client_id"
        />

        <BaseInput
          v-model="project.description"
          label="Description"
        />

        <div class="grid grid-cols-2 gap-4">
          <BaseSelect
            v-model="project.rate_id"
            label="Hourly Rate"
            :options="rateOptions"
            placeholder="Select rate..."
          />
          <BaseInput
            v-model="project.budget"
            label="Budget (CHF)"
            type="number"
            step="0.01"
          />
        </div>

        <div class="flex gap-6 pt-2">
          <label class="flex items-center gap-2 cursor-pointer">
            <input
              type="checkbox"
              v-model="project.is_collection"
              class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
            />
            <span class="text-sm text-gray-700">Collection</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input
              type="checkbox"
              v-model="project.is_archive"
              class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
            />
            <span class="text-sm text-gray-700">Archived</span>
          </label>
        </div>
      </div>

      <FormActions
        :loading="saving"
        :back-route="{ name: 'projects' }"
      />
    </form>
  </div>
</template>
