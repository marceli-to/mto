<script setup>
import { ref, computed, onMounted } from 'vue'
import { PhPlus, PhTrash, PhBank, PhArrowDown, PhArrowUp, PhChartLine } from '@phosphor-icons/vue'
import { useApi } from '@/composables/useApi'
import { useToast } from '@/composables/useToast'
import { useCurrency } from '@/composables/useCurrency'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseCheckbox from '@/components/ui/BaseCheckbox.vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'

const { get, post, del } = useApi()
const { success, error } = useToast()
const { formatCurrency } = useCurrency()

const loading = ref(true)
const saving = ref(false)

// Live, pickable sources — pending invoices and unbilled projects with current amounts.
const sources = ref({ invoices: [], projects: [] })
const snapshots = ref([])

// The state being edited. Invoices and projects are kept by id only, so a loaded
// state always shows today's amounts.
const today = () => new Date().toISOString().split('T')[0]
const emptyState = () => ({
  id: null,
  name: '',
  balance: '',
  balance_date: today(),
  items: [],
  invoice_ids: [],
  project_ids: [],
})
const state = ref(emptyState())
const errors = ref({})

const deleteDialog = ref({ show: false, loading: false })

const directionOptions = [
  { value: 'in', label: 'In' },
  { value: 'out', label: 'Out' },
]

// Collection vs fixed-price colours, matching the project list.
const kindColors = {
  collection: 'bg-amber-50 text-amber-800 inset-ring-amber-600/20',
  fixed: 'bg-blue-50 text-blue-700 inset-ring-blue-600/20',
}
const badgeBase = 'shrink-0 px-2 py-1 rounded-md text-xs font-medium inset-ring-1'

const snapshotOptions = computed(() => snapshots.value.map(s => ({ value: s.id, label: s.name })))

const pickedInvoices = computed(() => sources.value.invoices.filter(i => state.value.invoice_ids.includes(i.id)))
const pickedProjects = computed(() => sources.value.projects.filter(p => state.value.project_ids.includes(p.id)))

const sum = (rows) => rows.reduce((total, row) => total + (Number(row.amount) || 0), 0)

const itemsIn = computed(() => sum(state.value.items.filter(i => i.direction === 'in')))
const itemsOut = computed(() => sum(state.value.items.filter(i => i.direction === 'out')))
const invoicesTotal = computed(() => sum(pickedInvoices.value))
const projectsTotal = computed(() => sum(pickedProjects.value))

const incoming = computed(() => itemsIn.value + invoicesTotal.value + projectsTotal.value)
const outgoing = computed(() => itemsOut.value)
const projected = computed(() => (Number(state.value.balance) || 0) + incoming.value - outgoing.value)

const allInvoicesPicked = computed(() =>
  sources.value.invoices.length > 0 && pickedInvoices.value.length === sources.value.invoices.length
)
const allProjectsPicked = computed(() =>
  sources.value.projects.length > 0 && pickedProjects.value.length === sources.value.projects.length
)

function formatDate(date) {
  if (!date) return '—'
  const [y, m, d] = String(date).split('T')[0].split('-')
  return `${d}.${m}.${y}`
}

function toggle(key, id, checked) {
  state.value[key] = checked
    ? [...state.value[key], id]
    : state.value[key].filter(x => x !== id)
}

function toggleAll(key, rows, checked) {
  state.value[key] = checked ? rows.map(r => r.id) : []
}

function addItem() {
  state.value.items.push({ direction: 'out', label: '', amount: '', date: '' })
}

function removeItem(index) {
  state.value.items.splice(index, 1)
}

async function fetchSources() {
  sources.value = await get('/api/liquidity/sources')
}

async function fetchSnapshots() {
  snapshots.value = await get('/api/liquidity/snapshots/get')
}

function reset() {
  state.value = emptyState()
  errors.value = {}
}

async function loadSnapshot(id) {
  if (!id) return reset()
  try {
    // Re-read the sources too, so the amounts are current as of loading.
    const [snapshot] = await Promise.all([get(`/api/liquidity/snapshot/edit/${id}`), fetchSources()])
    const data = snapshot.data || {}

    // Whatever was paid, cancelled or archived since saving is no longer open — and a
    // paid invoice is already in the balance, so keeping it would count it twice.
    const invoiceIds = new Set(sources.value.invoices.map(i => i.id))
    const projectIds = new Set(sources.value.projects.map(p => p.id))
    const keptInvoices = (data.invoice_ids || []).filter(id => invoiceIds.has(id))
    const keptProjects = (data.project_ids || []).filter(id => projectIds.has(id))
    const dropped = (data.invoice_ids || []).length - keptInvoices.length
      + (data.project_ids || []).length - keptProjects.length

    state.value = {
      id: snapshot.id,
      name: snapshot.name,
      balance: data.balance ?? '',
      balance_date: data.balance_date || '',
      items: (data.items || []).map(item => ({
        direction: item.amount < 0 ? 'out' : 'in',
        label: item.label,
        amount: Math.abs(item.amount),
        date: item.date || '',
      })),
      invoice_ids: keptInvoices,
      project_ids: keptProjects,
    }
    errors.value = {}

    if (dropped > 0) {
      error(`${dropped} ${dropped === 1 ? 'entry is' : 'entries are'} no longer open and ${dropped === 1 ? 'was' : 'were'} left out`)
    }
  } catch (e) {
    error('Failed to load state')
  }
}

function payload() {
  return {
    name: state.value.name,
    data: {
      balance: state.value.balance === '' ? 0 : Number(state.value.balance),
      balance_date: state.value.balance_date || null,
      items: state.value.items.map(item => ({
        label: item.label,
        amount: (item.direction === 'out' ? -1 : 1) * (Number(item.amount) || 0),
        date: item.date || null,
      })),
      invoice_ids: state.value.invoice_ids,
      project_ids: state.value.project_ids,
    },
  }
}

async function save(asNew = false) {
  saving.value = true
  errors.value = {}
  try {
    const url = state.value.id && !asNew
      ? `/api/liquidity/snapshot/update/${state.value.id}`
      : '/api/liquidity/snapshot/create'
    const saved = await post(url, payload())
    state.value.id = saved.id
    await fetchSnapshots()
    success('State saved')
  } catch (e) {
    if (e.response?.status === 422) {
      errors.value = e.response.data.errors || {}
      error(Object.values(errors.value)[0]?.[0] || 'Please check the form')
    } else {
      error('Failed to save state')
    }
  } finally {
    saving.value = false
  }
}

async function deleteSnapshot() {
  deleteDialog.value.loading = true
  try {
    await del(`/api/liquidity/snapshot/destroy/${state.value.id}`)
    success('State deleted')
    deleteDialog.value.show = false
    reset()
    await fetchSnapshots()
  } catch (e) {
    error('Failed to delete state')
  } finally {
    deleteDialog.value.loading = false
  }
}

function itemError(index, field) {
  return errors.value[`data.items.${index}.${field}`]?.[0]
}

onMounted(async () => {
  try {
    await Promise.all([fetchSources(), fetchSnapshots()])
  } catch (e) {
    error('Failed to load liquidity data')
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div>
    <!-- Page Header -->
    <div class="flex items-center justify-between gap-4 min-h-10 mb-8">
      <div class="flex items-center gap-2">
        <button
          @click="reset"
          class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer rounded-sm transition-colors"
          title="New State"
        >
          <PhPlus class="w-4 h-4" />
        </button>
        <h1 class="text-xl text-gray-900 font-bold">Liquidity</h1>
      </div>
      <BaseSelect
        :model-value="state.id ?? ''"
        :options="snapshotOptions"
        placeholder="Load a saved state…"
        compact
        class="w-full max-w-xs"
        @update:model-value="loadSnapshot(Number($event))"
      />
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-16 text-gray-400">
      <div class="animate-pulse">Loading...</div>
    </div>

    <div v-else>
      <!-- Stat cards -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-10">
        <div class="bg-white rounded-xl border-2 border-gray-100 p-4">
          <div class="flex items-center justify-between mb-4">
            <p class="text-sm font-medium text-gray-500">Bank balance</p>
            <div class="p-2 bg-gray-50 rounded-lg text-gray-600">
              <PhBank class="h-5 w-5" />
            </div>
          </div>
          <BaseInput
            v-model="state.balance"
            type="number"
            step="0.01"
            placeholder="0.00"
            compact
          />
          <input
            v-model="state.balance_date"
            type="date"
            class="mt-1 text-xs text-gray-400 bg-transparent focus:outline-none"
            title="Balance as of"
          />
        </div>

        <div class="bg-white rounded-xl border-2 border-gray-100 p-4">
          <div class="flex items-center justify-between mb-4">
            <p class="text-sm font-medium text-gray-500">Incoming</p>
            <div class="p-2 bg-green-50 rounded-lg text-green-600">
              <PhArrowDown class="h-5 w-5" />
            </div>
          </div>
          <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(incoming) }}</p>
          <p class="text-xs text-gray-400 mt-1">Items, invoices, projects</p>
        </div>

        <div class="bg-white rounded-xl border-2 border-gray-100 p-4">
          <div class="flex items-center justify-between mb-4">
            <p class="text-sm font-medium text-gray-500">Outgoing</p>
            <div class="p-2 bg-red-50 rounded-lg text-red-600">
              <PhArrowUp class="h-5 w-5" />
            </div>
          </div>
          <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(outgoing) }}</p>
          <p class="text-xs text-gray-400 mt-1">Items</p>
        </div>

        <div class="bg-white rounded-xl border-2 border-gray-100 p-4">
          <div class="flex items-center justify-between mb-4">
            <p class="text-sm font-medium text-gray-500">Projected</p>
            <div class="p-2 bg-emerald-50 rounded-lg text-emerald-600">
              <PhChartLine class="h-5 w-5" />
            </div>
          </div>
          <p class="text-2xl font-bold" :class="projected >= 0 ? 'text-emerald-600' : 'text-red-600'">
            {{ formatCurrency(projected) }}
          </p>
          <p class="text-xs text-gray-400 mt-1">Balance + in − out</p>
        </div>
      </div>

      <!-- Manual items -->
      <section class="mb-10">
        <div class="flex items-center justify-between border-b border-gray-100 pb-2 pl-2">
          <div class="flex items-center gap-2">
            <h2 class="text-sm font-medium text-gray-500">Other items</h2>
            <button
              @click="addItem"
              class="p-1 text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer rounded-sm transition-colors"
              title="Add Item"
            >
              <PhPlus class="w-4 h-4" />
            </button>
          </div>
          <span class="text-sm text-gray-500 tabular-nums">{{ formatCurrency(itemsIn - itemsOut) }}</span>
        </div>

        <p v-if="state.items.length === 0" class="py-5 pl-2 text-sm text-gray-400">
          Anything not on an invoice or project — taxes, rent, a refund.
        </p>

        <ul v-else role="list" class="divide-y divide-gray-100">
          <li v-for="(item, index) in state.items" :key="index" class="flex items-start gap-3 py-3 pl-2">
            <BaseSelect v-model="item.direction" :options="directionOptions" compact class="w-24 shrink-0" />
            <!-- BaseInput puts class on the input itself, so size the wrappers -->
            <div class="flex-1 min-w-0">
              <BaseInput v-model="item.label" placeholder="Label" compact :error="itemError(index, 'label')" />
            </div>
            <div class="w-40 shrink-0">
              <BaseInput v-model="item.date" type="date" compact />
            </div>
            <div class="w-32 shrink-0">
              <BaseInput
                v-model="item.amount"
                type="number"
                step="0.01"
                min="0"
                placeholder="0.00"
                compact
                :error="itemError(index, 'amount')"
              />
            </div>
            <button
              @click="removeItem(index)"
              class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-sm transition-colors cursor-pointer"
              title="Remove"
            >
              <PhTrash class="w-4 h-4" />
            </button>
          </li>
        </ul>
      </section>

      <!-- Pending invoices -->
      <section class="mb-10">
        <div class="flex items-center justify-between border-b border-gray-100 pb-2 pl-2">
          <BaseCheckbox
            :model-value="allInvoicesPicked"
            :disabled="sources.invoices.length === 0"
            @update:model-value="toggleAll('invoice_ids', sources.invoices, $event)"
          >
            <span class="text-sm font-medium text-gray-500">Pending invoices</span>
          </BaseCheckbox>
          <span class="text-sm text-gray-500 tabular-nums">{{ formatCurrency(invoicesTotal) }}</span>
        </div>

        <p v-if="sources.invoices.length === 0" class="py-5 pl-2 text-sm text-gray-400">No pending invoices.</p>

        <ul v-else role="list" class="divide-y divide-gray-100">
          <li
            v-for="invoice in sources.invoices"
            :key="invoice.id"
            class="flex items-center justify-between gap-4 py-4 pl-2 hover:bg-gray-50/50 transition-colors"
          >
            <BaseCheckbox
              :model-value="state.invoice_ids.includes(invoice.id)"
              class="min-w-0 flex-1"
              @update:model-value="toggle('invoice_ids', invoice.id, $event)"
            >
              <span class="tabular-nums text-gray-900">{{ invoice.number }}</span>
              <span class="ml-3">{{ invoice.client }}</span>
              <span v-if="invoice.title" class="ml-2 text-gray-400 truncate">{{ invoice.title }}</span>
            </BaseCheckbox>
            <span class="w-28 text-right text-sm text-gray-400 tabular-nums" title="Due">{{ formatDate(invoice.date_due) }}</span>
            <span class="w-28 text-right tabular-nums">{{ formatCurrency(invoice.amount) }}</span>
          </li>
        </ul>
      </section>

      <!-- Unbilled projects -->
      <section class="mb-10">
        <div class="flex items-center justify-between border-b border-gray-100 pb-2 pl-2">
          <BaseCheckbox
            :model-value="allProjectsPicked"
            :disabled="sources.projects.length === 0"
            @update:model-value="toggleAll('project_ids', sources.projects, $event)"
          >
            <span class="text-sm font-medium text-gray-500">Unbilled projects</span>
          </BaseCheckbox>
          <span class="text-sm text-gray-500 tabular-nums">{{ formatCurrency(projectsTotal) }}</span>
        </div>

        <p v-if="sources.projects.length === 0" class="py-5 pl-2 text-sm text-gray-400">No unbilled projects.</p>

        <ul v-else role="list" class="divide-y divide-gray-100">
          <li
            v-for="project in sources.projects"
            :key="project.id"
            class="flex items-center justify-between gap-4 py-4 pl-2 hover:bg-gray-50/50 transition-colors"
          >
            <BaseCheckbox
              :model-value="state.project_ids.includes(project.id)"
              class="min-w-0 flex-1"
              @update:model-value="toggle('project_ids', project.id, $event)"
            >
              <span
                :class="[badgeBase, project.is_collection ? kindColors.collection : kindColors.fixed]"
                :title="project.is_collection ? 'Collection: current unbilled value' : 'Fixed price: budget'"
              >{{ project.is_collection ? 'Collection' : 'Fixed' }}</span>
              <span class="ml-3 text-gray-900">{{ project.name }}</span>
              <span v-if="project.client" class="ml-2 text-gray-400">{{ project.client }}</span>
            </BaseCheckbox>
            <span class="w-28 text-right tabular-nums">{{ formatCurrency(project.amount) }}</span>
          </li>
        </ul>
      </section>

      <!-- Save -->
      <div class="flex items-end gap-3 border-t border-gray-100 pt-6">
        <div class="flex-1 max-w-sm">
          <BaseInput
            v-model="state.name"
            label="Name"
            placeholder="e.g. October outlook"
            compact
            :error="errors.name?.[0]"
          />
        </div>
        <BaseButton :loading="saving" @click="save()">
          {{ state.id ? 'Save' : 'Save state' }}
        </BaseButton>
        <BaseButton v-if="state.id" variant="secondary" :disabled="saving" @click="save(true)">
          Save as new
        </BaseButton>
        <BaseButton
          v-if="state.id"
          variant="ghost"
          class="ml-auto"
          @click="deleteDialog = { show: true, loading: false }"
        >
          <PhTrash class="w-4 h-4" />
          Delete
        </BaseButton>
      </div>
    </div>

    <ConfirmDialog
      :show="deleteDialog.show"
      title="Delete State"
      :message="`Delete the saved state “${state.name}”?`"
      :loading="deleteDialog.loading"
      @confirm="deleteSnapshot"
      @cancel="deleteDialog.show = false"
    />
  </div>
</template>
