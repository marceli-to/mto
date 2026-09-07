<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useApi } from '@/composables/useApi'
import { useToast } from '@/composables/useToast'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import BaseButton from '@/components/ui/BaseButton.vue'

const props = defineProps({
  projectId: {
    type: [Number, String],
    default: null
  }
})

const emit = defineEmits(['saved', 'cancel'])

const { get, post } = useApi()
const { success, error } = useToast()

const isEdit = computed(() => !!props.projectId)
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
  is_collection: false
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
      const data = await get(`/api/project/edit/${props.projectId}`)
      project.value = {
        ...data,
        client_id: data.client_id || '',
        rate_id: data.rate_id || '',
        is_collection: !!data.is_collection
      }
    } else {
      // New projects start on the rate flagged as the default.
      project.value.rate_id = rates.value.find(r => r.is_default)?.id ?? ''
    }
  } catch (e) {
    error('Failed to load data')
    if (isEdit.value) emit('cancel')
  } finally {
    loading.value = false
  }
}

function resetForm() {
  project.value = {
    name: '',
    description: '',
    client_id: '',
    rate_id: '',
    budget: '',
    is_collection: false
  }
  errors.value = {}
}

watch(() => props.projectId, (newId) => {
  if (newId) {
    fetchData()
  } else {
    resetForm()
    fetchData() // Still need to load clients and rates
  }
})

function validate() {
  errors.value = {}
  if (!project.value.name?.trim()) {
    errors.value.name = 'Name is required'
  }
  if (!project.value.client_id) {
    errors.value.client_id = 'Client is required'
  }
  if (!project.value.rate_id) {
    errors.value.rate_id = 'Rate is required'
  }
  // Flat-rate projects are billed at a fixed price, so the budget IS the price.
  // Collection projects may leave it empty, meaning uncapped.
  if (!project.value.is_collection && !(parseFloat(project.value.budget) > 0)) {
    errors.value.budget = 'A budget is required for flat-rate projects.'
  }
  return Object.keys(errors.value).length === 0
}

/** Surface a 422 from the API on the fields themselves, not just as a toast. */
function applyServerErrors(e) {
  const serverErrors = e?.response?.data?.errors
  if (!serverErrors) return false

  errors.value = Object.fromEntries(
    Object.entries(serverErrors).map(([field, messages]) => [field, messages[0]])
  )

  return true
}

async function submit() {
  if (!validate()) {
    error('Please fix the errors')
    return
  }

  saving.value = true
  try {
    let savedProject
    if (isEdit.value) {
      savedProject = await post(`/api/project/update/${props.projectId}`, project.value)
      success('Project updated')
    } else {
      savedProject = await post('/api/project/create', project.value)
      success('Project created')
    }
    emit('saved', savedProject)
  } catch (e) {
    error(applyServerErrors(e) ? 'Please fix the errors' : 'Failed to save project')
  } finally {
    saving.value = false
  }
}

// A project is billed one way or the other, and which one decides whether the
// budget is a required fixed price or an optional cap.
const billingModes = [
  { value: true, label: 'Collection' },
  { value: false, label: 'Fixed' }
]

function setBilling(isCollection) {
  if (project.value.is_collection === isCollection) return

  project.value.is_collection = isCollection
  errors.value.budget = null
  // The fixed price is meaningless once the project bills by collection, and the
  // field is hidden from here on — don't leave a value behind that can't be seen.
  if (isCollection) project.value.budget = ''
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
    <div v-if="loading" class="text-center py-12 text-gray-500">
      Loading...
    </div>

    <form v-else @submit.prevent="submit">
      <div class="space-y-6">
        <!-- Billing mode: governs whether the budget below is required. -->
        <div>
          <label class="block text-sm text-gray-500 mb-2">Billing</label>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="mode in billingModes"
              :key="mode.label"
              type="button"
              @click="setBilling(mode.value)"
              class="px-3 py-1.5 rounded-full text-sm border transition-colors cursor-pointer"
              :class="project.is_collection === mode.value
                ? 'bg-gray-900 text-white border-gray-900'
                : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300'"
            >
              {{ mode.label }}
            </button>
          </div>
        </div>

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

        <BaseSelect
          v-model="project.rate_id"
          label="Hourly Rate"
          required
          :options="rateOptions"
          placeholder="Select rate..."
          :error="errors.rate_id"
        />

        <!-- Only flat-rate projects carry a price; collection projects bill by time. -->
        <BaseInput
          v-if="!project.is_collection"
          v-model="project.budget"
          label="Fixed price (CHF)"
          type="number"
          step="0.01"
          required
          :error="errors.budget"
          @focus="errors.budget = null"
        />
      </div>

      <div class="flex items-center justify-end gap-3 mt-8">
        <BaseButton type="button" variant="secondary" @click="emit('cancel')">
          Cancel
        </BaseButton>
        <BaseButton type="submit" :loading="saving">
          {{ isEdit ? 'Update' : 'Create' }}
        </BaseButton>
      </div>
    </form>
  </div>
</template>
