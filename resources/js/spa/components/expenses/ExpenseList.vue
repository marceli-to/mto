<script setup>
import { ref, computed, onMounted } from 'vue'
import { PhPlus, PhPencil, PhTrash, PhFilePdf, PhCurrencyDollar } from '@phosphor-icons/vue'
import { useApi } from '@/composables/useApi'
import { useToast } from '@/composables/useToast'
import { useCurrency } from '@/composables/useCurrency'
import SearchInput from '@/components/ui/SearchInput.vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import Flyout from '@/components/ui/Flyout.vue'
import ExpenseForm from './ExpenseForm.vue'

const { get, del } = useApi()
const { success, error } = useToast()
const { formatCurrency } = useCurrency()

const expenses = ref([])
const search = ref('')
const loading = ref(true)
const deleteDialog = ref({ show: false, id: null, loading: false })
const flyout = ref({ show: false, expenseId: null })

const flyoutTitle = computed(() => flyout.value.expenseId ? 'Edit Expense' : 'New Expense')

function openCreate() {
  flyout.value = { show: true, expenseId: null }
}

function openEdit(id) {
  flyout.value = { show: true, expenseId: id }
}

function closeFlyout() {
  flyout.value = { show: false, expenseId: null }
}

function onExpenseSaved(savedExpense) {
  if (flyout.value.expenseId) {
    const index = expenses.value.findIndex(e => e.id === flyout.value.expenseId)
    if (index !== -1) {
      expenses.value[index] = { ...expenses.value[index], ...savedExpense }
    }
  } else {
    expenses.value.unshift(savedExpense)
  }
  closeFlyout()
}

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
  window.open(`/expense/pdf/${id}`, '_blank')
}

function formatDate(date) {
  if (!date) return ''
  return new Date(date).toLocaleDateString('de-CH')
}

onMounted(fetchExpenses)
</script>

<template>
  <div>
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-12">

      <div class="flex items-center gap-2">
        <button
          @click="openCreate"
          class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer rounded-sm transition-colors"
          title="Add Expense"
        >
          <PhPlus class="w-4 h-4" />
        </button>
        <h1 class="text-xl text-gray-900 font-bold">
          Expenses
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
    <div v-else-if="filteredExpenses.length === 0" class="text-center py-16">
      <div class="text-gray-400 mb-2">No expenses found</div>
      <p class="text-sm text-gray-400">Add your first expense to get started</p>
    </div>

    <div v-else>
      <!-- Expenses List -->
      <div class="overflow-hidden border-t border-gray-100">
        <ul class="divide-y divide-gray-100">
          <li
            v-for="expense in filteredExpenses"
            :key="expense.id"
            class="flex items-center justify-between py-4 hover:bg-gray-50/50 transition-colors"
          >
            <div class="flex items-center gap-x-6">
              <span>{{ formatDate(expense.date) }}</span>
              {{ expense.title }}
              <span v-if="expense.number" class="font-bold">{{ expense.number }}</span>
            </div>
            <div class="flex items-center gap-4">
              <span class="text-gray-900">{{ formatCurrency(expense.amount) }}</span>
              <div class="flex items-center gap-1">
                <button
                  @click="downloadPdf(expense.id)"
                  class="p-2.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer rounded-sm transition-colors"
                  title="Download PDF"
                >
                  <PhFilePdf class="w-5 h-5" />
                </button>
                <button
                  @click="openEdit(expense.id)"
                  class="p-2.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer rounded-sm transition-colors"
                  title="Edit"
                >
                  <PhPencil class="w-5 h-5" />
                </button>
                <button
                  @click="confirmDelete(expense.id)"
                  class="p-2.5 text-gray-400 hover:text-red-600 hover:bg-red-50 cursor-pointer rounded-sm transition-colors"
                  title="Delete"
                >
                  <PhTrash class="w-5 h-5" />
                </button>
              </div>
            </div>
          </li>
        </ul>
      </div>

      <!-- Total Summary -->
      <div class="grid grid-cols-4 gap-4 mt-6">
        <div class="border border-gray-200 bg-gray-50/50 rounded-xs p-4">
          <p class="text-sm text-gray-500 mb-1">Total</p>
          <p class="text-xl  text-gray-900">{{ formatCurrency(totalAmount) }}</p>
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

    <Flyout
      :show="flyout.show"
      :title="flyoutTitle"
      size="md"
      @close="closeFlyout"
    >
      <ExpenseForm
        :expense-id="flyout.expenseId"
        @saved="onExpenseSaved"
        @cancel="closeFlyout"
      />
    </Flyout>
  </div>
</template>
