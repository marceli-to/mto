<script setup>
import { ref, computed, onMounted } from 'vue'
import { PhPlus, PhPencil, PhTrash, PhFilePdf } from '@phosphor-icons/vue'
import { useApi } from '@/composables/useApi'
import { useToast } from '@/composables/useToast'
import { useCurrency } from '@/composables/useCurrency'
import SearchInput from '@/components/ui/SearchInput.vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'

const { get, del } = useApi()
const { success, error } = useToast()
const { formatCurrency } = useCurrency()

const expenses = ref([])
const search = ref('')
const loading = ref(true)
const deleteDialog = ref({ show: false, id: null, loading: false })

const filteredExpenses = computed(() => {
  if (!search.value) return expenses.value
  const q = search.value.toLowerCase()
  return expenses.value.filter(e =>
    e.title?.toLowerCase().includes(q) ||
    e.description?.toLowerCase().includes(q)
  )
})

const totalAmount = computed(() => {
  return expenses.value.reduce((sum, e) => sum + parseFloat(e.amount || 0), 0)
})

async function fetchExpenses() {
  loading.value = true
  try {
    const data = await get('/api/expenses/get')
    expenses.value = data.data || []
  } catch (e) {
    error('Failed to load expenses')
  } finally {
    loading.value = false
  }
}

function confirmDelete(id) {
  deleteDialog.value = { show: true, id, loading: false }
}

async function deleteExpense() {
  deleteDialog.value.loading = true
  try {
    await del(`/api/expense/destroy/${deleteDialog.value.id}`)
    expenses.value = expenses.value.filter(e => e.id !== deleteDialog.value.id)
    success('Expense deleted')
    deleteDialog.value.show = false
  } catch (e) {
    error('Failed to delete expense')
  } finally {
    deleteDialog.value.loading = false
  }
}

function downloadPdf(id) {
  window.open(`/admin/expense/pdf/${id}`, '_blank')
}

function formatDate(date) {
  if (!date) return ''
  return new Date(date).toLocaleDateString('de-CH')
}

onMounted(fetchExpenses)
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Expenses</h1>
      <router-link
        :to="{ name: 'expense-create' }"
        class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
      >
        <PhPlus class="w-5 h-5" />
        Add Expense
      </router-link>
    </div>

    <div class="mb-6">
      <SearchInput v-model="search" placeholder="Search by title or description..." />
    </div>

    <div v-if="loading" class="text-center py-12 text-gray-500">
      Loading...
    </div>

    <div v-else-if="filteredExpenses.length === 0" class="text-center py-12 text-gray-500">
      No expenses found
    </div>

    <div v-else>
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <ul class="divide-y divide-gray-200">
          <li
            v-for="expense in filteredExpenses"
            :key="expense.id"
            class="flex items-center justify-between px-6 py-4 hover:bg-gray-50"
          >
            <div>
              <p class="font-medium text-gray-900">{{ expense.title }}</p>
              <p class="text-sm text-gray-500">
                {{ formatDate(expense.date) }}
                <span v-if="expense.number"> - {{ expense.number }}</span>
              </p>
            </div>
            <div class="flex items-center gap-4">
              <span class="font-medium">{{ formatCurrency(expense.amount) }} {{ expense.currency || 'CHF' }}</span>
              <div class="flex items-center gap-2">
                <button
                  @click="downloadPdf(expense.id)"
                  class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded"
                  title="Download PDF"
                >
                  <PhFilePdf class="w-5 h-5" />
                </button>
                <router-link
                  :to="{ name: 'expense-edit', params: { id: expense.id } }"
                  class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded"
                  title="Edit"
                >
                  <PhPencil class="w-5 h-5" />
                </router-link>
                <button
                  @click="confirmDelete(expense.id)"
                  class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded"
                  title="Delete"
                >
                  <PhTrash class="w-5 h-5" />
                </button>
              </div>
            </div>
          </li>
        </ul>
      </div>

      <!-- Total -->
      <div class="mt-4 bg-white rounded-lg shadow p-4">
        <div class="flex justify-between font-medium">
          <span>Total</span>
          <span>{{ formatCurrency(totalAmount) }} CHF</span>
        </div>
      </div>
    </div>

    <ConfirmDialog
      :show="deleteDialog.show"
      title="Delete Expense"
      message="Are you sure you want to delete this expense?"
      :loading="deleteDialog.loading"
      @confirm="deleteExpense"
      @cancel="deleteDialog.show = false"
    />
  </div>
</template>
