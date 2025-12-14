<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApi } from '@/composables/useApi'
import { useToast } from '@/composables/useToast'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import FormActions from '@/components/ui/FormActions.vue'

const route = useRoute()
const router = useRouter()
const { get, post } = useApi()
const { success, error } = useToast()

const isEdit = computed(() => !!route.params.id)
const title = computed(() => isEdit.value ? 'Edit Expense' : 'New Expense')

const loading = ref(false)
const saving = ref(false)
const errors = ref({})

const currencyOptions = [
  { value: 'CHF', label: 'CHF' },
  { value: 'EUR', label: 'EUR' },
  { value: 'USD', label: 'USD' }
]

const expense = ref({
  title: '',
  description: '',
  date: '',
  amount: '',
  currency: 'CHF'
})

async function fetchExpense() {
  if (!isEdit.value) {
    // Set today's date for new expenses
    expense.value.date = new Date().toISOString().split('T')[0]
    return
  }
  loading.value = true
  try {
    const data = await get(`/api/expense/edit/${route.params.id}`)
    expense.value = {
      ...data,
      date: data.date ? new Date(data.date).toISOString().split('T')[0] : ''
    }
  } catch (e) {
    error('Failed to load expense')
    router.push({ name: 'expenses' })
  } finally {
    loading.value = false
  }
}

function validate() {
  errors.value = {}
  if (!expense.value.title?.trim()) {
    errors.value.title = 'Title is required'
  }
  if (!expense.value.amount) {
    errors.value.amount = 'Amount is required'
  }
  return Object.keys(errors.value).length === 0
}

async function submit() {
  if (!validate()) {
    error('Please fix the errors')
    return
  }

  saving.value = true
  try {
    if (isEdit.value) {
      await post(`/api/expense/update/${route.params.id}`, expense.value)
      success('Expense updated')
    } else {
      await post('/api/expense/create', expense.value)
      success('Expense created')
    }
    router.push({ name: 'expenses' })
  } catch (e) {
    error('Failed to save expense')
  } finally {
    saving.value = false
  }
}

onMounted(fetchExpense)
</script>

<template>
  <div>
    <h1 class="text-2xl font-bold text-gray-900 mb-6">{{ title }}</h1>

    <div v-if="loading" class="text-center py-12 text-gray-500">
      Loading...
    </div>

    <form v-else @submit.prevent="submit" class="bg-white rounded-lg shadow p-6 max-w-2xl">
      <div class="space-y-4">
        <BaseInput
          v-model="expense.title"
          label="Title"
          required
          :error="errors.title"
          @focus="errors.title = null"
        />

        <BaseInput
          v-model="expense.date"
          label="Date"
          type="date"
        />

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
          <textarea
            v-model="expense.description"
            rows="3"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <div class="grid grid-cols-2 gap-4">
          <BaseInput
            v-model="expense.amount"
            label="Amount"
            type="number"
            step="0.01"
            required
            :error="errors.amount"
            @focus="errors.amount = null"
          />
          <BaseSelect
            v-model="expense.currency"
            label="Currency"
            :options="currencyOptions"
          />
        </div>
      </div>

      <FormActions
        :loading="saving"
        :back-route="{ name: 'expenses' }"
      />
    </form>
  </div>
</template>
