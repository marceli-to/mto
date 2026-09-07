<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useApi } from '@/composables/useApi'
import { useToast } from '@/composables/useToast'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseCheckbox from '@/components/ui/BaseCheckbox.vue'
import { normalizeTime, toMinutes } from '@/utils/time'

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

const blankEntry = () => ({
  project_id: '',
  activity: '',
  is_billable: true,
  date: today(),
  time_from: '',
  time_to: '',
  description: ''
})

const entry = ref(blankEntry())

/** Hint the next start time with the last end time booked on that date. */
const lastEnd = ref('')
const fromPlaceholder = computed(() => lastEnd.value ? lastEnd.value.replace(':', '.') : '08.30')

async function fetchLastEnd() {
  lastEnd.value = ''
  if (isEdit.value || !entry.value.date) return
  try {
    const data = await get(`/api/time-entries/last-end/${entry.value.date}`)
    lastEnd.value = data.time_to || ''
  } catch (e) {
    // A missing hint is not worth bothering the user about.
  }
}

watch(() => entry.value.date, fetchLastEnd)

const projectOptions = computed(() =>
  projects.value.map(p => ({ value: p.id, label: p.name }))
)

function selectProject() {
  mode.value = 'project'
  entry.value.activity = ''
  entry.value.is_billable = true
  errors.value.activity = null
}

function selectActivity(name) {
  mode.value = 'activity'
  entry.value.activity = name
  entry.value.project_id = ''
  entry.value.is_billable = false
  errors.value.project_id = null
}

/** Snap what was typed to a displayable "HH:MM" as soon as the field is left. */
function blurTime(field) {
  const normalized = normalizeTime(entry.value[field])
  if (normalized) entry.value[field] = normalized
  errors.value[field] = null
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
      time_from: data.time_from || '',
      time_to: data.time_to || '',
      description: data.description || ''
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
  entry.value = blankEntry()
  errors.value = {}
}

watch(() => props.timeEntryId, (newId) => {
  if (newId) {
    fetchEntry()
  } else {
    resetForm()
    fetchLastEnd()
  }
})

function validate() {
  errors.value = {}

  if (mode.value === 'project' && !entry.value.project_id) {
    errors.value.project_id = 'Select a project'
  }
  if (mode.value === 'activity' && !entry.value.activity) {
    errors.value.activity = 'Select an activity'
  }
  if (!entry.value.date) {
    errors.value.date = 'Date is required'
  }

  const from = normalizeTime(entry.value.time_from)
  const to = normalizeTime(entry.value.time_to)

  if (!from) {
    errors.value.time_from = 'From'
  }
  if (!to) {
    errors.value.time_to = 'To'
  }
  if (from && to && toMinutes(to) <= toMinutes(from)) {
    errors.value.time_to = 'After start'
  }

  return Object.keys(errors.value).length === 0
}

async function submit() {
  if (!validate()) {
    error('Please fix the errors')
    return
  }

  // Hours, rate and billability of activity entries are all derived server-side.
  const payload = {
    date: entry.value.date,
    time_from: normalizeTime(entry.value.time_from),
    time_to: normalizeTime(entry.value.time_to),
    description: entry.value.description
  }
  if (mode.value === 'activity') {
    payload.activity = entry.value.activity
  } else {
    payload.project_id = entry.value.project_id
    payload.is_billable = entry.value.is_billable
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
  await fetchLastEnd()
})
</script>

<template>
  <div>
    <div v-if="loading" class="text-center py-12 text-gray-500">
      Loading...
    </div>

    <form v-else @submit.prevent="submit">
      <div class="space-y-6">
        <BaseInput
          v-model="entry.date"
          label="Date"
          type="date"
          required
          :error="errors.date"
        />

        <div class="grid grid-cols-2 gap-x-4">
          <BaseInput
            v-model="entry.time_from"
            label="From"
            :placeholder="fromPlaceholder"
            required
            :error="errors.time_from"
            @blur="blurTime('time_from')"
          />
          <BaseInput
            v-model="entry.time_to"
            label="To"
            placeholder="10.15"
            required
            :error="errors.time_to"
            @blur="blurTime('time_to')"
          />
        </div>

        <div>
          <label class="block text-sm text-gray-500 mb-2">Description</label>
          <textarea
            v-model="entry.description"
            rows="3"
            class="w-full px-3 py-3 border border-gray-200 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-gray-200 focus:border-gray-300"
          />
        </div>

        <!-- Activity chips: "Project" is one of them and is the default. -->
        <div>
          <label class="block text-sm text-gray-500 mb-2">Activity</label>
          <div class="flex flex-wrap gap-2">
            <button
              type="button"
              @click="selectProject"
              class="px-3 py-1.5 rounded-full text-sm border transition-colors cursor-pointer"
              :class="mode === 'project'
                ? 'bg-gray-900 text-white border-gray-900'
                : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300'"
            >
              Project
            </button>
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
          </div>
        </div>

        <BaseSelect
          v-if="mode === 'project'"
          v-model="entry.project_id"
          label="Project"
          :options="projectOptions"
          placeholder="Select a project"
          required
          :error="errors.project_id"
        />

        <BaseCheckbox
          v-if="mode === 'project'"
          v-model="entry.is_billable"
          label="Billable"
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
