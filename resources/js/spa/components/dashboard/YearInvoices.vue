<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApi } from '@/composables/useApi'
import { useToast } from '@/composables/useToast'
import { useCurrency } from '@/composables/useCurrency'

const route = useRoute()
const router = useRouter()
const { get } = useApi()
const { error } = useToast()
const { formatCurrency } = useCurrency()

const invoices = ref([])
const loading = ref(true)
const year = computed(() => parseInt(route.params.year))

const stateColors = {
  open: 'bg-blue-100 text-blue-800',
  pending: 'bg-yellow-100 text-yellow-800',
  paid: 'bg-green-100 text-green-800',
  overdue: 'bg-red-100 text-red-800',
  cancelled: 'bg-gray-100 text-gray-800',
  closed: 'bg-gray-100 text-gray-800'
}

// Fiscal year: Jan 26 of year to Jan 25 of year+1
function isInFiscalYear(invoice) {
  if (!invoice.state || invoice.state.description === 'cancelled') return false

  const fiscalStart = new Date(year.value, 0, 26) // Jan 26
  const fiscalEnd = new Date(year.value + 1, 0, 25, 23, 59, 59) // Jan 25 next year

  // Pending invoices: use invoice date
  if (invoice.state.description === 'pending') {
    const invoiceDate = new Date(invoice.date)
    return invoiceDate.getFullYear() === year.value
  }

  // Paid/closed invoices: use date_paid, fall back to invoice date
  if (['paid', 'closed'].includes(invoice.state.description)) {
    const paidDate = new Date(invoice.date_paid ?? invoice.date)
    return paidDate >= fiscalStart && paidDate <= fiscalEnd
  }

  return false
}

const filteredInvoices = computed(() => {
  return invoices.value.filter(isInFiscalYear)
})

const total = computed(() => {
  return filteredInvoices.value.reduce((sum, inv) => sum + parseFloat(inv.grandtotal || 0), 0)
})

async function fetchInvoices() {
  loading.value = true
  try {
    const data = await get('/api/invoices/get')
    invoices.value = data.data || []
  } catch (e) {
    error('Failed to load invoices')
  } finally {
    loading.value = false
  }
}

function goBack() {
  router.push({ name: 'dashboard' })
}

onMounted(fetchInvoices)
</script>

<template>
  <div>
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-12">
      <div class="flex items-center gap-3">
        <button
          @click="goBack"
          class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer rounded-sm transition-colors"
          title="Back to Dashboard"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </button>
        <h1 class="text-xl text-gray-900 font-bold">
          Invoices: {{ year }}
        </h1>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-16 text-gray-400">
      <div class="animate-pulse">Loading...</div>
    </div>

    <!-- Empty State -->
    <div v-else-if="filteredInvoices.length === 0" class="text-center py-16">
      <div class="text-gray-400 mb-2">No invoices found for {{ year }}</div>
    </div>

    <div v-else>
      <!-- Invoices List -->
      <div class="overflow-hidden border-t border-gray-100">
        <ul class="divide-y divide-gray-100">
          <li
            v-for="invoice in filteredInvoices"
            :key="invoice.id"
            class="flex items-center justify-between py-4"
          >
            <div class="flex items-center gap-x-6">
              <span
                :class="[stateColors[invoice.state?.description] || 'bg-gray-100', 'px-2 py-1 rounded-md text-xs font-medium capitalize']"
              >
                {{ invoice.state?.description }}
              </span>
              <span class="text-gray-500 w-24">{{ invoice.number }}</span>
              <span class="font-bold w-16">{{ invoice.client?.acronym }}</span>
              <span>{{ invoice.title }}</span>
            </div>
            <div class="text-right font-medium">
              {{ formatCurrency(invoice.grandtotal) }}
            </div>
          </li>
        </ul>
      </div>

      <!-- Total Row -->
      <div class="border-t-2 border-gray-200 mt-2 pt-4">
        <div class="flex items-center justify-between py-2">
          <span class="text-xl font-bold text-gray-900">Total</span>
          <span class="text-xl font-bold text-gray-900">{{ formatCurrency(total) }}</span>
        </div>
      </div>
    </div>
  </div>
</template>
