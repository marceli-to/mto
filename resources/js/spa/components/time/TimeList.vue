<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { PhPlus, PhPencil, PhTrash, PhCaretDown, PhCaretRight } from '@phosphor-icons/vue'
import { useApi } from '@/composables/useApi'
import { useToast } from '@/composables/useToast'
import { useCurrency } from '@/composables/useCurrency'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import Flyout from '@/components/ui/Flyout.vue'
import TimeForm from './TimeForm.vue'

const { get, del, post } = useApi()
const { success, error } = useToast()
const { formatCurrency } = useCurrency()

const days = ref([])
const stats = ref({ day: 0, week: 0, month: 0 })
const loading = ref(true)

const today = () => new Date().toISOString().split('T')[0]
const selectedDate = ref(today())
const expanded = reactive({}) // date -> bool

const deleteDialog = ref({ show: false, id: null, loading: false })
const flyout = ref({ show: false, timeEntryId: null })

const flyoutTitle = computed(() => flyout.value.timeEntryId ? 'Edit Time Entry' : 'New Time Entry')

function openCreate() {
  flyout.value = { show: true, timeEntryId: null }
}

function openEdit(id) {
  flyout.value = { show: true, timeEntryId: id }
}

function closeFlyout() {
  flyout.value = { show: false, timeEntryId: null }
}

function onSaved() {
  closeFlyout()
  fetchEntries() // refresh so grouping + stats stay authoritative (server-computed)
}

async function fetchEntries() {
  loading.value = true
  try {
    const data = await get(`/api/time-entries/get?date=${selectedDate.value}`)
    days.value = data.days || []
    stats.value = data.stats || { day: 0, week: 0, month: 0 }
    // Expand today by default (first load); keep any user-toggled state otherwise.
    if (Object.keys(expanded).length === 0) {
      const t = today()
      days.value.forEach(d => { expanded[d.date] = d.date === t })
    } else {
      days.value.forEach(d => { if (!(d.date in expanded)) expanded[d.date] = false })
    }
  } catch (e) {
    error('Failed to load time entries')
  } finally {
    loading.value = false
  }
}

async function toggleDay(date) {
  expanded[date] = !expanded[date]
  // Opening a day selects it -> refresh the "selected day" stat card.
  if (expanded[date] && date !== selectedDate.value) {
    selectedDate.value = date
    await refreshStats()
  }
}

async function refreshStats() {
  try {
    const data = await get(`/api/time-entries/get?date=${selectedDate.value}`)
    stats.value = data.stats || stats.value
  } catch (e) {
    // non-fatal
  }
}

function confirmDelete(id) {
  deleteDialog.value = { show: true, id, loading: false }
}

async function deleteEntry() {
  deleteDialog.value.loading = true
  try {
    await del(`/api/time-entry/destroy/${deleteDialog.value.id}`)
    success('Time entry deleted')
    deleteDialog.value.show = false
    await fetchEntries()
  } catch (e) {
    const msg = e?.response?.data?.message || 'Failed to delete time entry'
    error(msg)
  } finally {
    deleteDialog.value.loading = false
  }
}

const selectedDayLabel = computed(() => {
  const d = days.value.find(x => x.date === selectedDate.value)
  if (d) return d.weekday_label
  return new Date(selectedDate.value).toLocaleDateString('de-CH')
})

onMounted(fetchEntries)
</script>

<template>
  <div>
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-8">
      <div class="flex items-center gap-2">
        <button
          @click="openCreate"
          class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer rounded-sm transition-colors"
          title="Add Time Entry"
        >
          <PhPlus class="w-4 h-4" />
        </button>
        <h1 class="text-xl text-gray-900 font-bold">Time</h1>
      </div>
    </div>

    <!-- Stat cards -->
    <div class="grid grid-cols-3 gap-4 mb-10">
      <div class="border border-gray-200 bg-gray-50/50 rounded-md p-4">
        <p class="text-sm text-gray-500 mb-1">{{ selectedDayLabel }}</p>
        <p class="text-xl text-gray-900">{{ formatCurrency(stats.day) }}</p>
      </div>
      <div class="border border-gray-200 bg-gray-50/50 rounded-md p-4">
        <p class="text-sm text-gray-500 mb-1">This week</p>
        <p class="text-xl text-gray-900">{{ formatCurrency(stats.week) }}</p>
      </div>
      <div class="border border-gray-200 bg-gray-50/50 rounded-md p-4">
        <p class="text-sm text-gray-500 mb-1">This month</p>
        <p class="text-xl text-gray-900">{{ formatCurrency(stats.month) }}</p>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-16 text-gray-400">
      <div class="animate-pulse">Loading...</div>
    </div>

    <!-- Empty -->
    <div v-else-if="days.length === 0" class="text-center py-16">
      <div class="text-gray-400 mb-2">No time entries yet</div>
      <p class="text-sm text-gray-400">Add your first entry to get started</p>
    </div>

    <!-- Day list -->
    <div v-else class="border-t border-gray-100">
      <div v-for="day in days" :key="day.date" class="border-b border-gray-100">
        <!-- Day header -->
        <button
          @click="toggleDay(day.date)"
          class="w-full flex items-center justify-between py-4 hover:bg-gray-50/50 transition-colors cursor-pointer"
          :class="{ 'text-black': day.date === selectedDate }"
        >
          <div class="flex items-center gap-2">
            <component :is="expanded[day.date] ? PhCaretDown : PhCaretRight" class="w-4 h-4 text-gray-400" />
            <span class="font-medium text-gray-900">{{ day.weekday_label }}</span>
          </div>
          <span class="text-gray-500">{{ day.total_hours }} h</span>
        </button>

        <!-- Entries -->
        <ul v-if="expanded[day.date]" class="divide-y divide-gray-50 pb-2">
          <li
            v-for="entry in day.entries"
            :key="entry.id"
            class="flex items-center justify-between py-3 pl-6 hover:bg-gray-50/50 transition-colors"
            :class="{ 'opacity-60': !entry.is_billable }"
          >
            <div class="flex items-center gap-x-4 min-w-0">
              <span class="font-medium text-gray-900 truncate">{{ entry.label }}</span>
              <span v-if="entry.description" class="text-gray-500 truncate">{{ entry.description }}</span>
              <span v-if="entry.is_activity" class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-500">Activity</span>
              <span v-else-if="!entry.is_billable" class="text-xs px-2 py-0.5 rounded-full bg-amber-50 text-amber-600">Non-billable</span>
              <span v-if="entry.is_billed" class="text-xs px-2 py-0.5 rounded-full bg-green-50 text-green-600">Billed</span>
            </div>
            <div class="flex items-center gap-4">
              <span class="text-gray-500 tabular-nums">{{ entry.hours }} h</span>
              <span class="text-gray-900 tabular-nums w-24 text-right">
                {{ entry.revenue > 0 ? formatCurrency(entry.revenue) : '—' }}
              </span>
              <div class="flex items-center gap-1">
                <button
                  @click="openEdit(entry.id)"
                  :disabled="entry.is_billed"
                  class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-sm transition-colors"
                  :class="entry.is_billed ? 'opacity-30 cursor-not-allowed' : 'cursor-pointer'"
                  title="Edit"
                >
                  <PhPencil class="w-4 h-4" />
                </button>
                <button
                  @click="confirmDelete(entry.id)"
                  :disabled="entry.is_billed"
                  class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-sm transition-colors"
                  :class="entry.is_billed ? 'opacity-30 cursor-not-allowed' : 'cursor-pointer'"
                  title="Delete"
                >
                  <PhTrash class="w-4 h-4" />
                </button>
              </div>
            </div>
          </li>
        </ul>
      </div>
    </div>

    <ConfirmDialog
      :show="deleteDialog.show"
      title="Delete Time Entry"
      message="Are you sure you want to delete this time entry?"
      :loading="deleteDialog.loading"
      @confirm="deleteEntry"
      @cancel="deleteDialog.show = false"
    />

    <Flyout
      :show="flyout.show"
      :title="flyoutTitle"
      size="md"
      @close="closeFlyout"
    >
      <TimeForm
        :time-entry-id="flyout.timeEntryId"
        @saved="onSaved"
        @cancel="closeFlyout"
      />
    </Flyout>
  </div>
</template>
