<script setup>
import { ref, computed, onMounted } from 'vue'
import { PhPlus, PhPencil, PhTrash, PhCopy, PhFilePdf, PhCaretDown, PhCaretUp } from '@phosphor-icons/vue'
import { useApi } from '@/composables/useApi'
import { useToast } from '@/composables/useToast'
import { useCurrency } from '@/composables/useCurrency'
import SearchInput from '@/components/ui/SearchInput.vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import InvoiceStateForm from './InvoiceStateForm.vue'

const { get, del } = useApi()
const { success, error } = useToast()
const { formatCurrency } = useCurrency()

const invoices = ref([])
const totals = ref({ paid: 0, pending: 0, open: 0, overdue: 0, closed: 0, total: 0 })
const search = ref('')
const loading = ref(true)
const showSubtotals = ref(false)
const deleteDialog = ref({ show: false, id: null, loading: false })
const stateDialog = ref({ show: false, invoice: null })

const filteredInvoices = computed(() => {
  if (!search.value) return invoices.value
  const q = search.value.toLowerCase()
  return invoices.value.filter(inv =>
    inv.title?.toLowerCase().includes(q) ||
    inv.client?.name?.toLowerCase().includes(q) ||
    inv.number?.toLowerCase().includes(q)
  )
})

const stateColors = {
  open: 'bg-blue-100 text-blue-800',
  pending: 'bg-yellow-100 text-yellow-800',
  paid: 'bg-green-100 text-green-800',
  overdue: 'bg-red-100 text-red-800',
  cancelled: 'bg-gray-100 text-gray-800',
  closed: 'bg-gray-100 text-gray-800'
}

async function fetchInvoices() {
  loading.value = true
  try {
    const data = await get('/api/invoices/get')
    invoices.value = data.data || []
    totals.value = data.totals || { paid: 0, pending: 0, open: 0, overdue: 0, closed: 0, total: 0 }
  } catch (e) {
    error('Failed to load invoices')
  } finally {
    loading.value = false
  }
}

async function cloneInvoice(id) {
  try {
    await get(`/api/invoice/duplicate/${id}`)
    await fetchInvoices()
    success('Invoice cloned')
  } catch (e) {
    error('Failed to clone invoice')
  }
}

function confirmDelete(id) {
  deleteDialog.value = { show: true, id, loading: false }
}

async function deleteInvoice() {
  deleteDialog.value.loading = true
  try {
    await del(`/api/invoice/destroy/${deleteDialog.value.id}`)
    await fetchInvoices()
    success('Invoice deleted')
    deleteDialog.value.show = false
  } catch (e) {
    error('Failed to delete invoice')
  } finally {
    deleteDialog.value.loading = false
  }
}

function openStateDialog(invoice) {
  stateDialog.value = { show: true, invoice }
}

function onStateUpdated() {
  stateDialog.value.show = false
  fetchInvoices()
}

function downloadPdf(id) {
  window.open(`/invoice/pdf/${id}`, '_blank')
}

onMounted(fetchInvoices)
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Invoices</h1>
      <router-link
        :to="{ name: 'invoice-create' }"
        class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
      >
        <PhPlus class="w-5 h-5" />
        Add Invoice
      </router-link>
    </div>

    <div class="mb-6">
      <SearchInput v-model="search" placeholder="Search by title, client or number..." />
    </div>

    <div v-if="loading" class="text-center py-12 text-gray-500">
      Loading...
    </div>

    <div v-else-if="filteredInvoices.length === 0" class="text-center py-12 text-gray-500">
      No invoices found
    </div>

    <div v-else>
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <ul class="divide-y divide-gray-200">
          <li
            v-for="invoice in filteredInvoices"
            :key="invoice.id"
            class="flex items-center justify-between px-6 py-4 hover:bg-gray-50"
          >
            <div class="flex items-center gap-4">
              <button
                @click="openStateDialog(invoice)"
                :class="[stateColors[invoice.state?.description] || 'bg-gray-100', 'px-2 py-1 rounded text-xs font-medium capitalize']"
              >
                {{ invoice.state?.description }}
              </button>
              <div>
                <p class="font-medium text-gray-900">
                  {{ invoice.number }}
                  <span v-if="invoice.client" class="font-bold">{{ invoice.client.acronym }}</span>
                  - {{ invoice.title }}
                </p>
                <p v-if="invoice.remarks" class="text-sm text-gray-500">[{{ invoice.remarks }}]</p>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <button
                @click="downloadPdf(invoice.id)"
                class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded"
                title="Download PDF"
              >
                <PhFilePdf class="w-5 h-5" />
              </button>
              <router-link
                v-if="invoice.state?.description === 'open'"
                :to="{ name: 'invoice-edit', params: { id: invoice.id } }"
                class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded"
                title="Edit"
              >
                <PhPencil class="w-5 h-5" />
              </router-link>
              <span v-else class="p-2 text-gray-300">
                <PhPencil class="w-5 h-5" />
              </span>
              <button
                @click="cloneInvoice(invoice.id)"
                class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded"
                title="Clone"
              >
                <PhCopy class="w-5 h-5" />
              </button>
              <button
                v-if="invoice.state?.description === 'open'"
                @click="confirmDelete(invoice.id)"
                class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded"
                title="Delete"
              >
                <PhTrash class="w-5 h-5" />
              </button>
              <span v-else class="p-2 text-gray-300">
                <PhTrash class="w-5 h-5" />
              </span>
            </div>
          </li>
        </ul>
      </div>

      <!-- Totals -->
      <div class="mt-4 bg-white rounded-lg shadow p-4">
        <button
          @click="showSubtotals = !showSubtotals"
          class="flex items-center gap-2 font-medium text-gray-900 w-full justify-between"
        >
          <span>Total: {{ formatCurrency(totals.total) }} CHF</span>
          <PhCaretDown v-if="!showSubtotals" class="w-5 h-5" />
          <PhCaretUp v-else class="w-5 h-5" />
        </button>
        <div v-if="showSubtotals" class="mt-4 space-y-2 text-sm">
          <div class="flex justify-between">
            <span class="text-gray-600">Paid</span>
            <span>{{ formatCurrency(totals.paid + totals.closed) }} CHF</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">Pending</span>
            <span>{{ formatCurrency(totals.pending) }} CHF</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">Open</span>
            <span>{{ formatCurrency(totals.open) }} CHF</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">Overdue</span>
            <span class="text-red-600">{{ formatCurrency(totals.overdue) }} CHF</span>
          </div>
        </div>
      </div>
    </div>

    <ConfirmDialog
      :show="deleteDialog.show"
      title="Delete Invoice"
      message="Are you sure you want to delete this invoice?"
      :loading="deleteDialog.loading"
      @confirm="deleteInvoice"
      @cancel="deleteDialog.show = false"
    />

    <InvoiceStateForm
      v-if="stateDialog.show"
      :invoice="stateDialog.invoice"
      @close="stateDialog.show = false"
      @updated="onStateUpdated"
    />
  </div>
</template>
