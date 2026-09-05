<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useApi } from '@/composables/useApi'
import { useToast } from '@/composables/useToast'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import BaseButton from '@/components/ui/BaseButton.vue'

const props = defineProps({
  timeEntryId: {
    type: [Number, String],
    default: null
  }
})

const emit = defineEmits(['saved', 'cancel'])

const { get, post } = useApi()
const { success, error } = useToast()

const isEdit = computed(() => !!props.timeEntryId)

const loading = ref(false)
const saving = ref(false)
const errors = ref({})

const projects = ref([])
const activities = ref([])

// mode: 'project' | 'activity'
const mode = ref('project')

const today = () => new Date().toISOString().split('T')[0]

const entry = ref({
  project_id: '',
  activity: '',
  is_billable: true,
  date: today(),
  hours: '',
  description: '',
  rate: ''
})

const projectOptions = computed(() =>
  projects.value.map(p => ({ value: p.id, label: p.name }))
)

function selectActivity(name) {
  mode.value = 'activity'
  entry.value.activity = name
  entry.value.project_id = ''
  entry.value.rate = ''
  entry.value.is_billable = false
}

function switchToProject() {
  mode.value = 'project'
  entry.value.activity = ''
  entry.value.is_billable = true
}

async function fetchOptions() {
  try {
    const [projectData, config] = await Promise.all([
      get('/api/projects/get'),
      get('/api/time-entries/config')
    ])
    projects.value = projectData.data || projectData || []
    activities.value = config.activities || []
  } catch (e) {
    error('Failed to load form options')
  }
}

async function fetchEntry() {
  if (!isEdit.value) {
    entry.value.date = today()
    return
  }
  loading.value = true
  try {
    const data = await get(`/api/time-entry/edit/${props.timeEntryId}`)
    mode.value = data.activity ? 'activity' : 'project'
    entry.value = {
      project_id: data.project_id || '',
      activity: data.activity || '',
      is_billable: !!data.is_billable,
      date: data.date ? new Date(data.date).toISOString().split('T')[0] : today(),
      hours: data.hours ?? '',
      description: data.description || '',
      rate: data.rate ?? ''
    }
  } catch (e) {
    error('Failed to load time entry')
    emit('cancel')
  } finally {
    loading.value = false
  }
}

function resetForm() {
  mode.value = 'project'
  entry.value = {
    project_id: '',
    activity: '',
    is_billable: true,
    date: today(),
    hours: '',
    description: '',
    rate: ''
  }
  errors.value = {}
}

watch(() => props.timeEntryId, (newId) => {
  if (newId) fetchEntry()
  else resetForm()
})

function validate() {
  errors.value = {}
  if (mode.value === 'project' && !entry.value.project_id) {
    errors.value.project_id = 'Select a project'
  }
  if (mode.value === 'activity' && !entry.value.activity) {
    errors.value.activity = 'Select an activity'
  }
  if (!entry.value.hours || parseFloat(entry.value.hours) <= 0) {
    errors.value.hours = 'Hours are required'
  }
  if (!entry.value.date) {
    errors.value.date = 'Date is required'
  }
  return Object.keys(errors.value).length === 0
}

async function submit() {
  if (!validate()) {
    error('Please fix the errors')
    return
  }

  // Build payload according to mode.
  const payload = {
    date: entry.value.date,
    hours: entry.value.hours,
    description: entry.value.description
  }
  if (mode.value === 'activity') {
    payload.activity = entry.value.activity
  } else {
    payload.project_id = entry.value.project_id
    payload.is_billable = entry.value.is_billable
    payload.rate = entry.value.rate === '' ? null : entry.value.rate
  }

  saving.value = true
  try {
    let saved
    if (isEdit.value) {
      saved = await post(`/api/time-entry/update/${props.timeEntryId}`, payload)
      success('Time entry updated')
    } else {
      saved = await post('/api/time-entry/create', payload)
      success('Time entry created')
    }
    emit('saved', saved)
  } catch (e) {
    const msg = e?.response?.data?.message || 'Failed to save time entry'
    error(msg)
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  await fetchOptions()
  await fetchEntry()
})
</script>

<template>
  <div>
    <div v-if="loading" class="text-center py-12 text-gray-500">
      Loading...
    </div>

    <form v-else @submit.prevent="submit">
      <div class="space-y-4">
        <!-- Activity chips -->
        <div>
          <label class="block text-sm text-gray-500 mb-2">Activity</label>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="name in activities"
              :key="name"
              type="button"
              @click="selectActivity(name)"
              class="px-3 py-1.5 rounded-full text-sm border transition-colors cursor-pointer"
              :class="mode === 'activity' && entry.activity === name
                ? 'bg-gray-900 text-white border-gray-900'
                : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300'"
            >
              {{ name }}
            </button>
            <button
              v-if="mode === 'activity'"
              type="button"
              @click="switchToProject"
              class="px-3 py-1.5 rounded-full text-sm border border-gray-200 text-gray-500 hover:border-gray-300 cursor-pointer"
            >
              ← Back to project
            </button>
          </div>
        </div>

        <!-- Project mode fields -->
        <template v-if="mode === 'project'">
          <BaseSelect
            v-model="entry.project_id"
            label="Project"
            :options="projectOptions"
            placeholder="Select a project"
            required
            :error="errors.project_id"
          />

          <div class="grid grid-cols-12 gap-x-4">
            <div class="col-span-6">
              <BaseInput
                v-model="entry.rate"
                label="Rate (optional)"
                type="number"
                step="0.01"
                placeholder="Uses project rate"
              />
            </div>
            <div class="col-span-6 flex items-end pb-3">
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  v-model="entry.is_billable"
                  class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                />
                <span class="text-sm text-gray-700">Billable</span>
              </label>
            </div>
          </div>
        </template>

        <div class="grid grid-cols-12 gap-x-4">
          <div class="col-span-6">
            <BaseInput
              v-model="entry.date"
              label="Date"
              type="date"
              required
              :error="errors.date"
            />
          </div>
          <div class="col-span-6">
            <BaseInput
              v-model="entry.hours"
              label="Hours"
              type="number"
              step="0.25"
              required
              :error="errors.hours"
              @focus="errors.hours = null"
            />
          </div>
        </div>

        <div>
          <label class="block text-sm text-gray-500 mb-2">Description</label>
          <textarea
            v-model="entry.description"
            rows="3"
            class="w-full px-3 py-3 border border-gray-200 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-gray-200 focus:border-gray-300"
          />
        </div>
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
