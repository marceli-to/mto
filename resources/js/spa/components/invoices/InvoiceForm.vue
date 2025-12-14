<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { PhPlus, PhPencil, PhTrash } from '@phosphor-icons/vue'
import { useApi } from '@/composables/useApi'
import { useToast } from '@/composables/useToast'
import { useCurrency } from '@/composables/useCurrency'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import FormActions from '@/components/ui/FormActions.vue'
import InvoicePositionForm from './InvoicePositionForm.vue'

const route = useRoute()
const router = useRouter()
const { get, post, del } = useApi()
const { success, error } = useToast()
const { formatCurrency, formatDecimal } = useCurrency()

const isEdit = computed(() => !!route.params.id)
const title = computed(() => isEdit.value ? 'Edit Invoice' : 'New Invoice')

const loading = ref(false)
const saving = ref(false)
const errors = ref({})
const clients = ref([])
const positionDialog = ref({ show: false, position: null })

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

    if (isEdit.value) {
      const data = await get(`/api/invoice/edit/${route.params.id}`)
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
    if (isEdit.value) router.push({ name: 'invoices' })
  } finally {
    loading.value = false
  }
}

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

function validate() {
  errors.value = {}
  if (!invoice.value.title?.trim()) {
    errors.value.title = 'Title is required'
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

  saving.value = true
  try {
    if (isEdit.value) {
      await post(`/api/invoice/update/${route.params.id}`, invoice.value)
      success('Invoice updated')
    } else {
      await post('/api/invoice/create', invoice.value)
      success('Invoice created')
    }
    router.push({ name: 'invoices' })
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
    <h1 class="text-2xl font-bold text-gray-900 mb-6">{{ title }}</h1>

    <div v-if="loading" class="text-center py-12 text-gray-500">
      Loading...
    </div>

    <form v-else @submit.prevent="submit" class="space-y-6">
      <div class="bg-white rounded-lg shadow p-6">
        <div class="space-y-4">
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
            <label class="block text-sm font-medium text-gray-700 mb-1">Text</label>
            <textarea
              v-model="invoice.text"
              rows="3"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
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
              label="Due Date"
              type="date"
            />
          </div>

          <BaseSelect
            v-model="invoice.vat_rate"
            label="VAT"
            :options="vatOptions"
          />
        </div>
      </div>

      <!-- Positions -->
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold">Positions</h2>
          <button
            type="button"
            @click="addPosition"
            class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-700"
          >
            <PhPlus class="w-5 h-5" />
            Add Position
          </button>
        </div>

        <div v-if="invoice.positions.length === 0" class="text-center py-8 text-gray-500">
          No positions yet. Add one to get started.
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-gray-200 text-left text-gray-500">
                <th class="pb-2">Period</th>
                <th class="pb-2">Description</th>
                <th class="pb-2 text-right">Hours</th>
                <th class="pb-2 text-right">Rate</th>
                <th class="pb-2 text-right">Amount</th>
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
                      class="p-1 text-gray-400 hover:text-blue-600"
                    >
                      <PhPencil class="w-4 h-4" />
                    </button>
                    <button
                      type="button"
                      @click="deletePosition(position, index)"
                      class="p-1 text-gray-400 hover:text-red-600"
                    >
                      <PhTrash class="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Totals -->
          <div class="mt-4 pt-4 border-t border-gray-200 space-y-2">
            <div class="flex justify-between text-sm">
              <span class="text-gray-600">Subtotal</span>
              <span>{{ formatCurrency(total) }} CHF</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-gray-600">VAT ({{ invoice.vat_rate }}%)</span>
              <span>{{ formatCurrency(vat) }} CHF</span>
            </div>
            <div class="flex justify-between font-semibold text-lg pt-2 border-t border-gray-200">
              <span>Total</span>
              <span>{{ formatCurrency(grandtotal) }} CHF</span>
            </div>
          </div>
        </div>
      </div>

      <FormActions
        :loading="saving"
        :back-route="{ name: 'invoices' }"
      />
    </form>

    <InvoicePositionForm
      v-if="positionDialog.show"
      :position="positionDialog.position"
      @close="positionDialog.show = false"
      @save="onPositionSaved"
    />
  </div>
</template>
