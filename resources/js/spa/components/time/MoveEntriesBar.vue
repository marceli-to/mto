<script setup>
import { ref, computed, onMounted } from 'vue'
import { PhX } from '@phosphor-icons/vue'
import { useApi } from '@/composables/useApi'
import { useToast } from '@/composables/useToast'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import BaseButton from '@/components/ui/BaseButton.vue'

const props = defineProps({
  ids: {
    type: Array,
    default: () => []
  },
  // The project the entries currently sit on, when known — pointless as a target.
  excludeProjectId: {
    type: [Number, String],
    default: null
  }
})

const emit = defineEmits(['moved', 'clear'])

const { get, post } = useApi()
const { success, error } = useToast()

const projects = ref([])
const projectId = ref('')
const saving = ref(false)

// Archived projects are billed and done, so they never take moved time.
const projectOptions = computed(() =>
  projects.value
    .filter(p => !p.is_archive && String(p.id) !== String(props.excludeProjectId))
    .map(p => ({ value: p.id, label: p.name }))
)

async function fetchProjects() {
  try {
    const data = await get('/api/projects/get')
    projects.value = data.data || data || []
  } catch (e) {
    error('Failed to load projects')
  }
}

async function move() {
  if (!projectId.value || props.ids.length === 0) return
  saving.value = true
  try {
    const data = await post('/api/time-entries/move', {
      project_id: projectId.value,
      time_entry_ids: props.ids
    })
    const moved = data.moved?.length ?? 0
    const skipped = data.skipped?.length ?? 0
    success(`${moved} ${moved === 1 ? 'entry' : 'entries'} moved`)
    if (skipped) error(`${skipped} billed ${skipped === 1 ? 'entry was' : 'entries were'} skipped. Unbill first.`)
    projectId.value = ''
    emit('moved')
  } catch (e) {
    error(e?.response?.data?.message || 'Failed to move entries')
  } finally {
    saving.value = false
  }
}

onMounted(fetchProjects)
</script>

<template>
  <div class="flex items-center gap-3">
    <button
      @click="emit('clear')"
      class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer rounded-sm transition-colors"
      title="Clear selection"
    >
      <PhX class="w-4 h-4" />
    </button>
    <span class="shrink-0 text-sm font-medium tabular-nums">{{ ids.length }} selected</span>
    <div class="min-w-0 flex-1">
      <BaseSelect
        v-model="projectId"
        :options="projectOptions"
        placeholder="Move to project…"
      />
    </div>
    <BaseButton :disabled="!projectId" :loading="saving" @click="move">Move</BaseButton>
  </div>
</template>
