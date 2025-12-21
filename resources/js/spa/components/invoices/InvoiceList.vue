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

      <div class="flex items-center gap-2">
        <button
          @click="openCreate"
          class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer rounded-sm transition-colors"
          title="Add Invoice"
        >
          <PhPlus class="w-4 h-4" />
        </button>
        <h1 class="text-xl text-gray-900 font-bold">
          Invoices
        </h1>
      </div>

      <!-- Search -->
      <div>
        <SearchInput v-model="search" />
      </div>
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
            'px-2 py-1 rounded-md text-xs font-medium capitalize cursor-pointer transition-colors'
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
            class="flex items-center justify-between py-4 hover:bg-gray-50/50 transition-colors">
            <div class="flex items-center gap-x-6 w-full">
              <button
                @click="openStateDialog(invoice)"
                :class="[stateColors[invoice.state?.description] || 'bg-gray-100', 'px-2 py-1 rounded-md text-xs font-medium capitalize cursor-pointer transition-colors']"
              >
                {{ invoice.state?.description }}
              </button>
              <div class="flex justify-between w-full">
                <div class="flex items-center gap-x-8">
                  {{ invoice.number }}
                  <span v-if="invoice.client" class="font-bold">{{ invoice.client.acronym }}</span>
                  {{ invoice.title }}
                  <span v-if="invoice.remarks" class="text-sm text-gray-500">({{ invoice.remarks }})</span>
                </div>
                <div class="text-right pr-4">
                  {{ formatCurrency(invoice.grandtotal) }}
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
        <div class="bg-white rounded-xl border-2 border-gray-100 p-4 transition-shadow duration-300">
          <div class="flex items-center justify-between mb-4">
            <p class="text-sm font-medium text-gray-500">Total Revenue</p>
            <div class="p-2 bg-gray-50 rounded-lg text-gray-600">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
          </div>
          <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(totals.total) }}</p>
          <p class="text-xs text-gray-400 mt-1">Gross volume</p>
        </div>

        <div class="bg-white rounded-xl border-2 border-gray-100 p-4 transition-shadow duration-300">
          <div class="flex items-center justify-between mb-4">
            <p class="text-sm font-medium text-gray-500">Paid</p>
            <div class="p-2 bg-green-50 rounded-lg text-green-600">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
          </div>
          <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(totals.paid + totals.closed) }}</p>
          <div class="flex items-center mt-1">
            <span class="text-xs font-medium text-green-600 bg-green-100 px-2 py-0.5 rounded-full">
              {{ ((totals.paid + totals.closed) / totals.total * 100).toFixed(1) }}%
            </span>
            <span class="text-xs text-gray-400 ml-2">collected</span>
          </div>
        </div>
        <div class="bg-white rounded-xl border-2 border-gray-100 p-4 transition-shadow duration-300">
          <div class="flex items-center justify-between mb-4">
            <p class="text-sm font-medium text-gray-500">Pending</p>
            <div class="p-2 bg-amber-50 rounded-lg text-amber-600">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
          </div>
          <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(totals.pending) }}</p>
          <p class="text-xs text-gray-400 mt-1">Processing</p>
        </div>
        <div class="bg-white rounded-xl border-2 border-gray-100 p-4 transition-shadow duration-300">
          <div class="flex items-center justify-between mb-4">
            <p class="text-sm font-medium text-gray-500">Open</p>
            <div class="p-2 bg-blue-50 rounded-lg text-blue-600">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
          </div>
          <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(totals.open) }}</p>
          <p class="text-xs text-gray-400 mt-1">Due soon</p>
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
      size="xl"
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
