<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useApi } from '@/composables/useApi'
import { useToast } from '@/composables/useToast'
import { useCurrency } from '@/composables/useCurrency'

const router = useRouter()
const { get } = useApi()
const { error } = useToast()
const { formatCurrency } = useCurrency()

const data = ref(null)
const loading = ref(true)

async function fetchDashboard() {
  loading.value = true
  try {
    const response = await get('/api/dashboard/get')
    data.value = response
  } catch (e) {
    error('Failed to load dashboard')
  } finally {
    loading.value = false
  }
}

function navigateTo(route) {
  router.push({ name: route })
}

onMounted(fetchDashboard)
</script>

<template>
  <div>
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-12">
      <h1 class="text-xl text-gray-900 font-bold">Dashboard</h1>
      <span v-if="data" class="text-sm text-gray-400">{{ data.year }}</span>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-16 text-gray-400">
      <div class="animate-pulse">Loading...</div>
    </div>

    <div v-else-if="data">
      <!-- Revenue Overview -->
      <section class="mb-10">
        <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Revenue Overview</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div class="bg-white rounded-xl border-2 border-gray-100 p-4">
            <div class="flex items-center justify-between mb-4">
              <p class="text-sm font-medium text-gray-500">Total Revenue</p>
              <div class="p-2 bg-gray-50 rounded-lg text-gray-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
              </div>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(data.invoices.totals.total) }}</p>
            <p class="text-xs text-gray-400 mt-1">Gross volume</p>
          </div>

          <div class="bg-white rounded-xl border-2 border-gray-100 p-4">
            <div class="flex items-center justify-between mb-4">
              <p class="text-sm font-medium text-gray-500">Paid</p>
              <div class="p-2 bg-green-50 rounded-lg text-green-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
              </div>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ formatCurrency((data.invoices.totals.paid || 0) + (data.invoices.totals.closed || 0)) }}</p>
            <div class="flex items-center mt-1">
              <span class="text-xs font-medium text-green-600 bg-green-100 px-2 py-0.5 rounded-full">
                {{ (((data.invoices.totals.paid || 0) + (data.invoices.totals.closed || 0)) / data.invoices.totals.total * 100).toFixed(1) }}%
              </span>
              <span class="text-xs text-gray-400 ml-2">collected</span>
            </div>
          </div>

          <div class="bg-white rounded-xl border-2 border-gray-100 p-4">
            <div class="flex items-center justify-between mb-4">
              <p class="text-sm font-medium text-gray-500">Pending</p>
              <div class="p-2 bg-amber-50 rounded-lg text-amber-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
              </div>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(data.invoices.totals.pending || 0) }}</p>
            <p class="text-xs text-gray-400 mt-1">Processing</p>
          </div>

          <div class="bg-white rounded-xl border-2 border-gray-100 p-4">
            <div class="flex items-center justify-between mb-4">
              <p class="text-sm font-medium text-gray-500">Open</p>
              <div class="p-2 bg-blue-50 rounded-lg text-blue-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
              </div>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(data.invoices.totals.open || 0) }}</p>
            <p class="text-xs text-gray-400 mt-1">Due soon</p>
          </div>
        </div>
      </section>

      <!-- Profit Summary -->
      <section class="mb-10">
        <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Profit Summary</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div class="bg-white rounded-xl border-2 border-gray-100 p-4">
            <div class="flex items-center justify-between mb-4">
              <p class="text-sm font-medium text-gray-500">Collected</p>
              <div class="p-2 bg-green-50 rounded-lg text-green-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
              </div>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ formatCurrency((data.invoices.totals.paid || 0) + (data.invoices.totals.closed || 0)) }}</p>
            <p class="text-xs text-gray-400 mt-1">Paid invoices</p>
          </div>

          <div class="bg-white rounded-xl border-2 border-gray-100 p-4">
            <div class="flex items-center justify-between mb-4">
              <p class="text-sm font-medium text-gray-500">Expenses</p>
              <div class="p-2 bg-red-50 rounded-lg text-red-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" /></svg>
              </div>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(data.expenses.total) }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ data.expenses.count }} expenses</p>
          </div>

          <div class="bg-white rounded-xl border-2 border-gray-100 p-4">
            <div class="flex items-center justify-between mb-4">
              <p class="text-sm font-medium text-gray-500">Net Profit</p>
              <div class="p-2 bg-emerald-50 rounded-lg text-emerald-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
              </div>
            </div>
            <p class="text-2xl font-bold" :class="data.profit.net >= 0 ? 'text-emerald-600' : 'text-red-600'">{{ formatCurrency(data.profit.net) }}</p>
            <div class="flex items-center mt-1">
              <span class="text-xs font-medium px-2 py-0.5 rounded-full" :class="data.profit.margin >= 0 ? 'text-emerald-600 bg-emerald-100' : 'text-red-600 bg-red-100'">
                {{ data.profit.margin }}%
              </span>
              <span class="text-xs text-gray-400 ml-2">margin</span>
            </div>
          </div>

          <div class="bg-white rounded-xl border-2 border-gray-100 p-4">
            <div class="flex items-center justify-between mb-4">
              <p class="text-sm font-medium text-gray-500">{{ data.year }} Net</p>
              <div class="p-2 bg-indigo-50 rounded-lg text-indigo-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
              </div>
            </div>
            <p class="text-2xl font-bold" :class="data.invoices.thisYear.net >= 0 ? 'text-indigo-600' : 'text-red-600'">{{ formatCurrency(data.invoices.thisYear.net) }}</p>
            <p class="text-xs text-gray-400 mt-1">This year</p>
          </div>
        </div>
      </section>

      <!-- Quick Counts -->
      <section class="mb-10">
        <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Quick Stats</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <button @click="navigateTo('invoices')" class="bg-white rounded-xl border-2 border-gray-100 p-4 text-left hover:border-gray-200 transition-colors cursor-pointer">
            <div class="flex items-center justify-between mb-4">
              <p class="text-sm font-medium text-gray-500">Invoices</p>
              <div class="p-2 bg-blue-50 rounded-lg text-blue-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
              </div>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ data.invoices.count }}</p>
            <p class="text-xs text-gray-400 mt-1">Avg {{ formatCurrency(data.invoices.average) }}</p>
          </button>

          <button @click="navigateTo('expenses')" class="bg-white rounded-xl border-2 border-gray-100 p-4 text-left hover:border-gray-200 transition-colors cursor-pointer">
            <div class="flex items-center justify-between mb-4">
              <p class="text-sm font-medium text-gray-500">Expenses</p>
              <div class="p-2 bg-red-50 rounded-lg text-red-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
              </div>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ data.expenses.count }}</p>
            <p class="text-xs text-gray-400 mt-1">Total {{ formatCurrency(data.expenses.total) }}</p>
          </button>

          <button @click="navigateTo('clients')" class="bg-white rounded-xl border-2 border-gray-100 p-4 text-left hover:border-gray-200 transition-colors cursor-pointer">
            <div class="flex items-center justify-between mb-4">
              <p class="text-sm font-medium text-gray-500">Clients</p>
              <div class="p-2 bg-purple-50 rounded-lg text-purple-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
              </div>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ data.clients.count }}</p>
            <p class="text-xs text-gray-400 mt-1">Total clients</p>
          </button>

          <button @click="navigateTo('projects')" class="bg-white rounded-xl border-2 border-gray-100 p-4 text-left hover:border-gray-200 transition-colors cursor-pointer">
            <div class="flex items-center justify-between mb-4">
              <p class="text-sm font-medium text-gray-500">Projects</p>
              <div class="p-2 bg-orange-50 rounded-lg text-orange-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" /></svg>
              </div>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ data.projects.active }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ data.projects.archived }} archived</p>
          </button>
        </div>
      </section>

      <!-- Top Clients & Recent Activity -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Top Clients -->
        <section>
          <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Top Clients by Revenue</h2>
          <div class="bg-white rounded-xl border-2 border-gray-100 overflow-hidden">
            <ul class="divide-y divide-gray-100">
              <li v-for="(client, index) in data.clients.top" :key="client.id" class="flex items-center justify-between p-4">
                <div class="flex items-center gap-3">
                  <span class="w-6 h-6 flex items-center justify-center rounded-full text-xs font-bold" :class="index === 0 ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-500'">
                    {{ index + 1 }}
                  </span>
                  <div>
                    <p class="font-medium text-gray-900">{{ client.acronym }}</p>
                    <p class="text-xs text-gray-400">{{ client.count }} invoices</p>
                  </div>
                </div>
                <p class="font-semibold text-gray-900">{{ formatCurrency(client.total) }}</p>
              </li>
              <li v-if="data.clients.top.length === 0" class="p-4 text-center text-gray-400 text-sm">
                No paid invoices yet
              </li>
            </ul>
          </div>
        </section>

        <!-- Recent Invoices -->
        <section>
          <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Recent Invoices</h2>
          <div class="bg-white rounded-xl border-2 border-gray-100 overflow-hidden">
            <ul class="divide-y divide-gray-100">
              <li v-for="invoice in data.recent.invoices" :key="invoice.id" class="flex items-center justify-between p-4">
                <div class="flex items-center gap-3">
                  <span class="px-2 py-1 rounded-md text-xs font-medium capitalize"
                    :class="{
                      'bg-blue-100 text-blue-800': invoice.state === 'open',
                      'bg-yellow-100 text-yellow-800': invoice.state === 'pending',
                      'bg-green-100 text-green-800': invoice.state === 'paid',
                      'bg-gray-100 text-gray-800': invoice.state === 'cancelled' || invoice.state === 'closed'
                    }">
                    {{ invoice.state }}
                  </span>
                  <div>
                    <p class="font-medium text-gray-900">{{ invoice.number }}</p>
                    <p class="text-xs text-gray-400">{{ invoice.client }} &middot; {{ invoice.title?.length > 30 ? invoice.title.slice(0, 30) + '...' : invoice.title }}</p>
                  </div>
                </div>
                <p class="font-semibold text-gray-900">{{ formatCurrency(invoice.amount) }}</p>
              </li>
              <li v-if="data.recent.invoices.length === 0" class="p-4 text-center text-gray-400 text-sm">
                No invoices yet
              </li>
            </ul>
          </div>
        </section>
      </div>
    </div>
  </div>
</template>
