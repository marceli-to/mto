<script setup>
import { ref, computed, watch } from 'vue'
import { useApi } from '@/composables/useApi'
import { useToast } from '@/composables/useToast'
import { useCurrency } from '@/composables/useCurrency'
import BaseCheckbox from '@/components/ui/BaseCheckbox.vue'
import MoveEntriesBar from '@/components/time/MoveEntriesBar.vue'

const props = defineProps({
  projectId: {
    type: [Number, String],
    default: null
  }
})

const emit = defineEmits(['moved'])

const { get, post } = useApi()
const { error } = useToast()
const { formatCurrency } = useCurrency()

const entries = ref([])
const totals = ref({ hours: 0, revenue: 0 })
const loading = ref(false)

// Entries picked for a bulk move. Billed entries can't be picked.
const selected = ref([])
const movable = computed(() => entries.value.filter(e => !e.is_billed).map(e => e.id))
const allSelected = computed(() => movable.value.length > 0 && selected.value.length === movable.value.length)

function toggleSelected(id, checked) {
  selected.value = checked
    ? [...selected.value, id]
    : selected.value.filter(s => s !== id)
}

function toggleAll(checked) {
  selected.value = checked ? [...movable.value] : []
}

function onMoved() {
  selected.value = []
  fetchEntries()
  emit('moved')
}

// Inline description edit: click to edit, saved on blur, Enter saves, Escape cancels.
const editingId = ref(null)
const draft = ref('')

function startEdit(entry) {
  if (entry.is_billed) return
  editingId.value = entry.id
  draft.value = entry.description || ''
}

function cancelEdit() {
  editingId.value = null
}

async function saveEdit(entry) {
  // Escape already closed the field; a trailing blur must not save.
  if (editingId.value !== entry.id) return
  editingId.value = null

  const description = draft.value.trim()
  const previous = entry.description
  if (description === (previous || '')) return

  entry.description = description || null
  try {
    await post(`/api/time-entry/description/${entry.id}`, { description: entry.description })
  } catch (e) {
    entry.description = previous
    error(e?.response?.data?.message || 'Failed to save description')
  }
}

async function fetchEntries() {
  if (!props.projectId) return
  loading.value = true
  selected.value = []
  try {
    const data = await get(`/api/time-entries/project/${props.projectId}`)
    entries.value = data.entries || []
    totals.value = data.totals || { hours: 0, revenue: 0 }
  } catch (e) {
    error('Failed to load time entries')
  } finally {
    loading.value = false
  }
}

watch(() => props.projectId, fetchEntries, { immediate: true })
</script>

<template>
  <div v-if="loading" class="text-center py-16 text-gray-400">
    <div class="animate-pulse">Loading...</div>
  </div>

  <div v-else-if="entries.length === 0" class="text-center py-16 text-gray-400">
    No time entries yet
  </div>

  <div v-else>
    <div v-if="movable.length" class="pb-3">
      <BaseCheckbox
        :model-value="allSelected"
        label="Select all unbilled"
        @update:model-value="toggleAll"
      />
    </div>
    <ul class="divide-y divide-gray-200 border-t border-gray-200">
      <li
        v-for="entry in entries"
        :key="entry.id"
        class="flex items-center justify-between gap-4 py-4"
        :class="{ 'opacity-60': !entry.is_billable }"
      >
        <div class="flex items-center gap-x-3 min-w-0 flex-1">
          <BaseCheckbox
            :model-value="selected.includes(entry.id)"
            :disabled="entry.is_billed"
            :title="entry.is_billed ? 'Billed entries cannot be moved' : 'Select'"
            @update:model-value="toggleSelected(entry.id, $event)"
          />
          <span class="tabular-nums text-gray-500 shrink-0">{{ entry.periode }}</span>
          <input
            v-if="editingId === entry.id"
            :ref="el => el?.focus()"
            v-model="draft"
            type="text"
            class="min-w-0 flex-1 -my-1 px-2 py-1 border border-gray-200 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-gray-200 focus:border-gray-300"
            @blur="saveEdit(entry)"
            @keydown.enter.prevent="$event.target.blur()"
            @keydown.esc.prevent="cancelEdit"
          />
          <span
            v-else-if="entry.is_billed"
            class="truncate"
            title="Billed entries cannot be edited"
          >{{ entry.description }}</span>
          <button
            v-else
            type="button"
            class="truncate text-left cursor-text rounded-sm hover:bg-gray-50"
            :class="{ 'text-gray-300': !entry.description }"
            title="Click to edit"
            @click="startEdit(entry)"
          >{{ entry.description || 'Add description' }}</button>
          <span v-if="!entry.is_billable" class="bg-amber-100 text-amber-800 px-2 py-1 rounded-md text-xs font-medium shrink-0">
            Non-billable
          </span>
          <span v-if="entry.is_billed" class="bg-green-100 text-green-800 px-2 py-1 rounded-md text-xs font-medium shrink-0">
            Billed
          </span>
        </div>
        <div class="flex items-center gap-4 shrink-0">
          <span class="tabular-nums w-16 text-right">{{ entry.hours }} h</span>
          <span class="tabular-nums w-24 text-right text-gray-500">{{ formatCurrency(entry.revenue) }}</span>
        </div>
      </li>
    </ul>

    <div class="flex items-center justify-between gap-4 border-t border-gray-200 pt-4 font-bold">
      <span>Total</span>
      <div class="flex items-center gap-4">
        <span class="tabular-nums w-16 text-right">{{ totals.hours }} h</span>
        <span class="tabular-nums w-24 text-right">{{ formatCurrency(totals.revenue) }}</span>
      </div>
    </div>

    <div v-if="selected.length" class="sticky bottom-4 z-10 mt-6 rounded-xl border-2 border-gray-100 bg-white p-3 shadow-lg">
      <MoveEntriesBar
        :ids="selected"
        :exclude-project-id="projectId"
        @moved="onMoved"
        @clear="selected = []"
      />
    </div>
  </div>
</template>
