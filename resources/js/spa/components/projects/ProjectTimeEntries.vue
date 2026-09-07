<script setup>
import { ref, watch } from 'vue'
import { useApi } from '@/composables/useApi'
import { useToast } from '@/composables/useToast'
import { useCurrency } from '@/composables/useCurrency'

const props = defineProps({
  projectId: {
    type: [Number, String],
    default: null
  }
})

const { get } = useApi()
const { error } = useToast()
const { formatCurrency } = useCurrency()

const entries = ref([])
const totals = ref({ hours: 0, revenue: 0 })
const loading = ref(false)

async function fetchEntries() {
  if (!props.projectId) return
  loading.value = true
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
    <ul class="divide-y divide-gray-200 border-t border-gray-200">
      <li
        v-for="entry in entries"
        :key="entry.id"
        class="flex items-center justify-between gap-4 py-4"
        :class="{ 'opacity-60': !entry.is_billable }"
      >
        <div class="flex items-center gap-x-3 min-w-0">
          <span class="tabular-nums text-gray-500 shrink-0">{{ entry.periode }}</span>
          <span v-if="entry.description" class="truncate">{{ entry.description }}</span>
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
  </div>
</template>
