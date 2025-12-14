<script setup>
import { ref, computed, onMounted } from 'vue'
import { PhPlus, PhPencil, PhTrash, PhCopy, PhFilePdf, PhCaretDown, PhCaretUp } from '@phosphor-icons/vue'
import { useApi } from '@/composables/useApi'
import { useToast } from '@/composables/useToast'
import { useCurrency } from '@/composables/useCurrency'
import SearchInput from '@/components/ui/SearchInput.vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import Flyout from '@/components/ui/Flyout.vue'
import InvoiceForm from './InvoiceForm.vue'
import InvoiceStateForm from './InvoiceStateForm.vue'

const { get, del } = useApi()
const { success, error } = useToast()
const { formatCurrency } = useCurrency()

const invoices = ref([])
const totals = ref({ paid: 0, pending: 0, open: 0, overdue: 0, closed: 0, total: 0 })
const search = ref('')
const loading = ref(true)
const activeFilters = ref(['open', 'pending'])
const showSubtotals = ref(false)
const deleteDialog = ref({ show: false, id: null, loading: false })
const stateDialog = ref({ show: false, invoice: null })
const flyout = ref({ show: false, invoiceId: null })

const flyoutTitle = computed(() => flyout.value.invoiceId ? 'Edit Invoice' : 'New Invoice')

function openCreate() {
  flyout.value = { show: true, invoiceId: null }
}

function openEdit(id) {
  flyout.value = { show: true, invoiceId: id }
}

function closeFlyout() {
  flyout.value = { show: false, invoiceId: null }
}

function onInvoiceSaved() {
  closeFlyout()
  fetchInvoices()
}

const filteredInvoices = computed(() => {
  let result = invoices.value
  
  // Filter by state
  if (activeFilters.value.length > 0) {
    result = result.filter(inv => activeFilters.value.includes(inv.state?.description))
  }
  
  // Filter by search
  if (search.value) {
    const q = search.value.toLowerCase()
    result = result.filter(inv =>
      inv.title?.toLowerCase().includes(q) ||
      inv.client?.name?.toLowerCase().includes(q) ||
      inv.number?.toLowerCase().includes(q)
    )
  }
  
  return result
})

const stateFilters = ['open', 'pending', 'paid', 'cancelled']

function toggleFilter(state) {
  const index = activeFilters.value.indexOf(state)
  if (index === -1) {
    activeFilters.value.push(state)
  } else {
    activeFilters.value.splice(index, 1)
  }
}

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
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-12">
      
      <h1 class="text-xl text-gray-900 font-bold">
        Invoices
      </h1>

      <!-- Search -->
      <div>
        <SearchInput v-model="search" />
      </div>

      <button
        v-if="!flyout.show"
        @click="openCreate"
        class="fixed right-4 bottom-4 z-20 inline-flex items-center gap-2 pr-4 pl-3 py-2 bg-black text-white text-md rounded-xs hover:bg-gray-800 transition-colors cursor-pointer">
        <PhPlus class="w-5 h-5" />
        Add Invoice
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-16 text-gray-400">
      <div class="animate-pulse">Loading...</div>
    </div>

    <!-- Empty State -->
    <div v-else-if="filteredInvoices.length === 0" class="text-center py-16">
      <div class="text-gray-400 mb-2">No invoices found</div>
      <p class="text-sm text-gray-400">Create your first invoice to get started</p>
    </div>

    <div v-else>
      <!-- State Filters -->
      <div class="flex items-center gap-2 mb-6">
        <button
          v-for="state in stateFilters"
          :key="state"
          @click="toggleFilter(state)"
          :class="[
            activeFilters.includes(state) ? stateColors[state] : 'bg-gray-100 text-gray-400',
            'px-2 py-1 rounded-xs text-xs font-medium capitalize cursor-pointer transition-colors'
          ]"
        >
          {{ state }}
        </button>
      </div>

      <!-- Invoices List -->
      <div class="overflow-hidden border-t border-gray-100">
        <ul class="divide-y divide-gray-100">
          <li
            v-for="invoice in filteredInvoices"
            :key="invoice.id"
            class="flex items-center justify-between py-4 hover:bg-gray-50/50 transition-colors"
          >
            <div class="flex items-center gap-x-6">
              <button
                @click="openStateDialog(invoice)"
                :class="[stateColors[invoice.state?.description] || 'bg-gray-100', 'px-2 py-1 rounded-xs text-xs font-medium capitalize cursor-pointer transition-colors']"
              >
                {{ invoice.state?.description }}
              </button>
              <div>
                <div class="flex items-center gap-x-8">
                  {{ invoice.number }}
                  <span v-if="invoice.client" class="font-bold">{{ invoice.client.acronym }}</span>
                  {{ invoice.title }}
                  <span v-if="invoice.remarks" class="text-sm text-gray-500">({{ invoice.remarks }})</span>
                </div>
              </div>
            </div>
            <div class="flex items-center gap-1">
              <button
                @click="downloadPdf(invoice.id)"
                class="p-2.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer rounded-sm transition-colors"
                title="Download PDF"
              >
                <PhFilePdf class="w-5 h-5" />
              </button>
              <button
                v-if="invoice.state?.description === 'open'"
                @click="openEdit(invoice.id)"
                class="p-2.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer rounded-sm transition-colors"
                title="Edit"
              >
                <PhPencil class="w-5 h-5" />
              </button>
              <span v-else class="p-2.5 text-gray-200">
                <PhPencil class="w-5 h-5" />
              </span>
              <button
                @click="cloneInvoice(invoice.id)"
                class="p-2.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer rounded-sm transition-colors"
                title="Clone"
              >
                <PhCopy class="w-5 h-5" />
              </button>
              <button
                v-if="invoice.state?.description === 'open'"
                @click="confirmDelete(invoice.id)"
                class="p-2.5 text-gray-400 hover:text-red-600 hover:bg-red-50 cursor-pointer rounded-sm transition-colors"
                title="Delete"
              >
                <PhTrash class="w-5 h-5" />
              </button>
              <span v-else class="p-2.5 text-gray-200">
                <PhTrash class="w-5 h-5" />
              </span>
            </div>
          </li>
        </ul>
      </div>

      <!-- Totals Summary -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
        <div class="border border-gray-200 bg-gray-50/50 rounded-xs p-4">
          <p class="text-sm text-gray-500 mb-1">Total</p>
          <p class="text-xl text-gray-900 font-bold">{{ formatCurrency(totals.total) }}</p>
        </div>
        <div class="border border-gray-200 bg-gray-50/50 rounded-xs p-4">
          <p class="text-sm text-gray-500 mb-1">Paid</p>
          <p class="text-xl text-green-600 font-bold">{{ formatCurrency(totals.paid + totals.closed) }}</p>
        </div>
        <div class="border border-gray-200 bg-gray-50/50 rounded-xs p-4">
          <p class="text-sm text-gray-500 mb-1">Open</p>
          <p class="text-xl text-blue-600 font-bold">{{ formatCurrency(totals.open) }}</p>
        </div>
        <div class="border border-gray-200 bg-gray-50/50 rounded-xs p-4">
          <p class="text-sm text-gray-500 mb-1">Pending</p>
          <p class="text-xl text-yellow-600 font-bold">{{ formatCurrency(totals.pending) }}</p>
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

    <Flyout
      :show="flyout.show"
      :title="flyoutTitle"
      size="2xl"
      @close="closeFlyout"
    >
      <InvoiceForm
        :invoice-id="flyout.invoiceId"
        @saved="onInvoiceSaved"
        @cancel="closeFlyout"
      />
    </Flyout>
  </div>
</template>
