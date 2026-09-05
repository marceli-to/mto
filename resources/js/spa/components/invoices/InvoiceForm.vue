<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { PhPlus, PhPencil, PhTrash } from '@phosphor-icons/vue'
import { useApi } from '@/composables/useApi'
import { useToast } from '@/composables/useToast'
import { useCurrency } from '@/composables/useCurrency'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import InvoicePositionForm from './InvoicePositionForm.vue'

const props = defineProps({
  invoiceId: {
    type: [Number, String],
    default: null
  }
})

const emit = defineEmits(['saved', 'cancel'])

const { get, post, del } = useApi()
const { success, error } = useToast()
const { formatCurrency, formatDecimal } = useCurrency()

const isEdit = computed(() => !!props.invoiceId)
const title = computed(() => isEdit.value ? 'Edit Invoice' : 'New Invoice')

const loading = ref(false)
const saving = ref(false)
const errors = ref({})
const clients = ref([])
const positionDialog = ref({ show: false, position: null })

// Invoice source (create mode only): 'manual' or 'collection' (bill a collection project's time)
const source = ref('manual')
const collectionProjects = ref([])
const selectedProjectId = ref('')
const billableEntries = ref([]) // unbilled entries pulled in for a collection project
const loadingEntries = ref(false)

const vatOptions = [
  { value: 0, label: 'None' },
  { value: 7.7, label: '7.7%' },
  { value: 8.1, label: '8.1%' }
]

const invoice = ref({
  title: '',
  text: '',
  date: '',
  date_due: '',
  client_id: '',
  vat_rate: 8.1,
  has_rate_increase_notice: false,
  is_reminder: false,
  reminder_level: 1,
  positions: []
})

// Computed totals
const total = computed(() => {
  return invoice.value.positions.reduce((sum, p) => sum + parseFloat(p.amount || 0), 0)
})

const vat = computed(() => {
  return Math.ceil((total.value / 100 * parseFloat(invoice.value.vat_rate || 0)) * 20) / 20
})

const grandtotal = computed(() => {
  return total.value + vat.value
})

async function fetchData() {
  loading.value = true
  try {
    const clientsData = await get('/api/clients/get')
    clients.value = clientsData.data || []

    // Collection projects are billing candidates (create mode only).
    if (!isEdit.value) {
      const projectsData = await get('/api/projects/get')
      collectionProjects.value = (projectsData.data || []).filter(p => p.is_collection)
    }

    if (isEdit.value) {
      const data = await get(`/api/invoice/edit/${props.invoiceId}`)
      invoice.value = {
        ...data,
        client_id: data.client_id || '',
        date: formatDateForInput(data.date),
        date_due: formatDateForInput(data.date_due)
      }
    } else {
      // Set today's date and due date (3 weeks later)
      const today = new Date()
      const dueDate = new Date(today)
      dueDate.setDate(dueDate.getDate() + 21)
      invoice.value.date = formatDateForInput(today)
      invoice.value.date_due = formatDateForInput(dueDate)
    }
  } catch (e) {
    error('Failed to load data')
    if (isEdit.value) emit('cancel')
  } finally {
    loading.value = false
  }
}

function resetForm() {
  const today = new Date()
  const dueDate = new Date(today)
  dueDate.setDate(dueDate.getDate() + 21)
  invoice.value = {
    title: '',
    text: '',
    date: formatDateForInput(today),
    date_due: formatDateForInput(dueDate),
    client_id: '',
    vat_rate: 8.1,
    has_rate_increase_notice: false,
    is_reminder: false,
    reminder_level: 1,
    positions: []
  }
  errors.value = {}
  source.value = 'manual'
  selectedProjectId.value = ''
  billableEntries.value = []
}

watch(() => props.invoiceId, (newId) => {
  if (newId) {
    fetchData()
  } else {
    resetForm()
    fetchData() // Still need to load clients
  }
})

function formatDateForInput(date) {
  if (!date) return ''
  const d = new Date(date)
  if (isNaN(d.getTime())) return ''
  return d.toISOString().split('T')[0]
}

function setDueDate() {
  if (invoice.value.date) {
    const date = new Date(invoice.value.date)
    date.setDate(date.getDate() + 21)
    invoice.value.date_due = formatDateForInput(date)
  }
}

const collectionProjectOptions = computed(() =>
  collectionProjects.value.map(p => ({ value: p.id, label: p.name }))
)

function setSource(next) {
  source.value = next
  if (next === 'manual') {
    selectedProjectId.value = ''
    billableEntries.value = []
  }
}

async function onCollectionProjectSelected() {
  billableEntries.value = []
  if (!selectedProjectId.value) return
  loadingEntries.value = true
  try {
    const data = await get(`/api/time-entries/unbilled/${selectedProjectId.value}`)
    billableEntries.value = data.entries || []
    // Prefill title + client + positions preview from the project's entries.
    invoice.value.client_id = data.project?.client_id || invoice.value.client_id
    if (!invoice.value.title?.trim()) {
      invoice.value.title = data.project?.name || ''
    }
    invoice.value.positions = billableEntries.value.map(e => ({
      periode: e.periode,
      description: e.description || '',
      rate: e.rate,
      hours: e.hours,
      amount: e.amount,
      is_flat: false,
      is_fee: false,
      _from_entry: e.id // marker: created via Bill, not sent as a manual position
    }))
    if (!billableEntries.value.length) {
      error('This project has no unbilled billable time entries')
    }
  } catch (e) {
    error('Failed to load time entries')
  } finally {
    loadingEntries.value = false
  }
}

function validate() {
  errors.value = {}
  if (!invoice.value.title?.trim()) {
    errors.value.title = 'Title is required'
  }
  if (source.value === 'collection' && !billableEntries.value.length) {
    error('Select a collection project with unbilled entries')
    return false
  }
  return Object.keys(errors.value).length === 0
}

async function submit() {
  if (!validate()) {
    error('Please fix the errors')
    return
  }

  // Update totals before saving
  invoice.value.total = total.value
  invoice.value.vat = vat.value
  invoice.value.grandtotal = grandtotal.value

  const billFromEntries = !isEdit.value && source.value === 'collection' && billableEntries.value.length > 0

  saving.value = true
  try {
    let savedInvoice
    if (isEdit.value) {
      savedInvoice = await post(`/api/invoice/update/${props.invoiceId}`, invoice.value)
      success('Invoice updated')
    } else if (billFromEntries) {
      // Create the invoice WITHOUT the entry-derived positions; Bill creates those
      // server-side and links each entry (so they can't be double-billed).
      const payload = { ...invoice.value, positions: [] }
      savedInvoice = await post('/api/invoice/create', payload)
      const invoiceId = savedInvoice.invoiceId || savedInvoice.id
      await post('/api/time-entries/bill', {
        invoice_id: invoiceId,
        time_entry_ids: billableEntries.value.map(e => e.id)
      })
      success('Invoice created from time entries')
    } else {
      savedInvoice = await post('/api/invoice/create', invoice.value)
      success('Invoice created')
    }
    emit('saved', savedInvoice)
  } catch (e) {
    error('Failed to save invoice')
  } finally {
    saving.value = false
  }
}

function addPosition() {
  positionDialog.value = { show: true, position: null }
}

function editPosition(position, index) {
  positionDialog.value = { show: true, position: { ...position, _index: index } }
}

async function deletePosition(position, index) {
  if (!confirm('Delete this position?')) return

  // Remove from local array
  invoice.value.positions.splice(index, 1)

  // If it has an ID, delete from server
  if (position.id) {
    try {
      await del(`/api/invoice/position/destroy/${position.id}`)
      success('Position deleted')
    } catch (e) {
      error('Failed to delete position')
    }
  }
}

function onPositionSaved(position) {
  if (position._index !== undefined) {
    // Editing existing position
    invoice.value.positions[position._index] = position
  } else {
    // Adding new position
    invoice.value.positions.push(position)
  }
  positionDialog.value.show = false
}

const clientOptions = computed(() =>
  clients.value.map(c => ({ value: c.id, label: `${c.name} - ${c.city || ''}` }))
)

onMounted(fetchData)
</script>

<template>
  <div>
    <div v-if="loading" class="text-center py-12 text-gray-500">
      Loading...
    </div>

    <form v-else @submit.prevent="submit" class="space-y-6">
      <div>
        <div class="space-y-4">
          <!-- Source selector (create mode only) -->
          <div v-if="!isEdit">
            <label class="block text-sm text-gray-500 mb-2">Invoice source</label>
            <div class="flex gap-2 mb-2">
              <button
                type="button"
                @click="setSource('manual')"
                class="px-3 py-1.5 rounded-full text-sm border transition-colors cursor-pointer"
                :class="source === 'manual'
                  ? 'bg-gray-900 text-white border-gray-900'
                  : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300'"
              >
                Manual
              </button>
              <button
                type="button"
                @click="setSource('collection')"
                class="px-3 py-1.5 rounded-full text-sm border transition-colors cursor-pointer"
                :class="source === 'collection'
                  ? 'bg-gray-900 text-white border-gray-900'
                  : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300'"
              >
                From collection project
              </button>
            </div>
            <div v-if="source === 'collection'">
              <BaseSelect
                v-model="selectedProjectId"
                :options="collectionProjectOptions"
                placeholder="Select a collection project..."
                @update:modelValue="onCollectionProjectSelected"
              />
              <p v-if="loadingEntries" class="text-sm text-gray-400 mt-2 animate-pulse">Loading time entries...</p>
              <p v-else-if="selectedProjectId && billableEntries.length" class="text-sm text-gray-500 mt-2">
                {{ billableEntries.length }} unbilled {{ billableEntries.length === 1 ? 'entry' : 'entries' }} → positions below.
              </p>
            </div>
          </div>

          <BaseInput
            v-model="invoice.title"
            label="Title"
            required
            :error="errors.title"
            @focus="errors.title = null"
          />

          <BaseSelect
            v-model="invoice.client_id"
            label="Client"
            :options="clientOptions"
            placeholder="Select a client..."
          />

          <div>
            <label class="block text-sm text-gray-500 mb-2">Text</label>
            <textarea
              v-model="invoice.text"
              rows="3"
              class="w-full px-3 py-3 border border-gray-200 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-gray-200 focus:border-gray-300"
            />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <BaseInput
              v-model="invoice.date"
              label="Date"
              type="date"
              @change="setDueDate"
            />
            <BaseInput
              v-model="invoice.date_due"
              label="Date due"
              type="date"
            />
          </div>

          <BaseSelect
            v-model="invoice.vat_rate"
            label="VAT"
            :options="vatOptions"
          />

          <label class="flex items-center gap-2 cursor-pointer mt-6">
            <input
              type="checkbox"
              v-model="invoice.has_rate_increase_notice"
              class="w-4 h-4 rounded border-gray-300 text-gray-600 focus:ring-gray-200"
            />
            <span class="text-sm text-gray-600">Hinweis Stundenansatz-Erhöhung</span>
          </label>

          <div class="flex items-center gap-4 mt-4">
            <label class="flex items-center gap-2 cursor-pointer">
              <input
                type="checkbox"
                v-model="invoice.is_reminder"
                class="w-4 h-4 rounded border-gray-300 text-gray-600 focus:ring-gray-200"
              />
              <span class="text-sm text-gray-600">Mahnung</span>
            </label>
            <select
              v-if="invoice.is_reminder"
              v-model="invoice.reminder_level"
              class="px-3 py-2 border border-gray-200 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-gray-200 focus:border-gray-300"
            >
              <option :value="1">1. Mahnung</option>
              <option :value="2">2. Mahnung</option>
              <option :value="3">3. Mahnung</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Positions -->
      <div class="">
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-bold">Positions</h2>
          <button
            type="button"
            @click="addPosition"
            class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer rounded-sm transition-colors"
            title="Add Position"
          >
            <PhPlus class="w-5 h-5" />
          </button>
        </div>

        <div v-if="invoice.positions.length === 0" class="text-center py-8 text-gray-500">
          No positions yet. Add one to get started.
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm border-b border-gray-100">
            <thead>
              <tr class="border-b border-gray-100 text-left text-gray-500">
                <th class="pb-2 font-normal">Period</th>
                <th class="pb-2 font-normal">Description</th>
                <th class="pb-2 text-right font-normal">Hours</th>
                <th class="pb-2 text-right font-normal">Rate</th>
                <th class="pb-2 text-right font-normal">Amount</th>
                <th class="pb-2 w-20"></th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="(position, index) in invoice.positions" :key="index">
                <td class="py-3">{{ position.periode }}</td>
                <td class="py-3">{{ position.description }}</td>
                <td class="py-3 text-right">{{ position.is_flat ? '–' : formatDecimal(position.hours) }}</td>
                <td class="py-3 text-right">{{ position.is_flat ? 'Flat' : formatCurrency(position.rate) }}</td>
                <td class="py-3 text-right font-medium">{{ formatCurrency(position.amount) }}</td>
                <td class="py-3">
                  <div class="flex justify-end gap-1">
                    <button
                      type="button"
                      @click="editPosition(position, index)"
                      class="p-1.5 cursor-pointer text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-sm"
                    >
                      <PhPencil class="w-4 h-4" />
                    </button>
                    <button
                      type="button"
                      @click="deletePosition(position, index)"
                      class="p-1.5 cursor-pointer text-gray-400 hover:text-red-600 hover:bg-red-100 rounded-sm"
                    >
                      <PhTrash class="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Totals -->
          <div class="mt-8 divide-y divide-gray-200 bg-gray-50 rounded-sm px-2 py-1">
            <div class="flex justify-between text-sm py-3">
              <span>Subtotal</span>
              <span>{{ formatCurrency(total) }}</span>
            </div>
            <div class="flex justify-between text-sm py-3">
              <span>VAT ({{ invoice.vat_rate }}%)</span>
              <span>{{ formatCurrency(vat) }}</span>
            </div>
            <div class="flex justify-between font-bold py-3">
              <span>Total</span>
              <span>{{ formatCurrency(grandtotal) }}</span>
            </div>
          </div>
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

    <InvoicePositionForm
      v-if="positionDialog.show"
      :position="positionDialog.position"
      @close="positionDialog.show = false"
      @save="onPositionSaved"
    />
  </div>
</template>
