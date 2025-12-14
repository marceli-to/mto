<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useApi } from '@/composables/useApi'
import { useToast } from '@/composables/useToast'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import FileUpload from '@/components/ui/FileUpload.vue'

const props = defineProps({
  expenseId: {
    type: [Number, String],
    default: null
  }
})

const emit = defineEmits(['saved', 'cancel'])

const { get, post } = useApi()
const { success, error } = useToast()

const isEdit = computed(() => !!props.expenseId)
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
  currency: 'CHF',
  temp_file: '',
  delete_file: false
})

const existingFile = ref('')

function onDeleteExisting() {
  expense.value.delete_file = true
}

async function fetchExpense() {
  if (!isEdit.value) {
    // Set today's date for new expenses
    expense.value.date = new Date().toISOString().split('T')[0]
    return
  }
  loading.value = true
  try {
    const data = await get(`/api/expense/edit/${props.expenseId}`)
    expense.value = {
      ...data,
      date: data.date ? new Date(data.date).toISOString().split('T')[0] : '',
      temp_file: ''
    }
    existingFile.value = data.file_name || ''
  } catch (e) {
    error('Failed to load expense')
    emit('cancel')
  } finally {
    loading.value = false
  }
}

function resetForm() {
  expense.value = {
    title: '',
    description: '',
    date: new Date().toISOString().split('T')[0],
    amount: '',
    currency: 'CHF',
    temp_file: '',
    delete_file: false
  }
  existingFile.value = ''
  errors.value = {}
}

watch(() => props.expenseId, (newId) => {
  if (newId) {
    fetchExpense()
  } else {
    resetForm()
  }
})

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
    let savedExpense
    if (isEdit.value) {
      savedExpense = await post(`/api/expense/update/${props.expenseId}`, expense.value)
      success('Expense updated')
    } else {
      savedExpense = await post('/api/expense/create', expense.value)
      success('Expense created')
    }
    emit('saved', savedExpense)
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
    <div v-if="loading" class="text-center py-12 text-gray-500">
      Loading...
    </div>

    <form v-else @submit.prevent="submit">
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
          <label class="block text-sm text-gray-500 mb-2">Description</label>
          <textarea
            v-model="expense.description"
            rows="3"
            class="w-full px-3 py-3 border border-gray-200 rounded-xs text-sm focus:outline-none focus:ring-2 focus:ring-gray-200 focus:border-gray-300"
          />
        </div>
        
        <div class="grid grid-cols-12 gap-x-4 w-full">
          <div class="col-span-8">
            <BaseInput
              v-model="expense.amount"
              label="Amount"
              type="number"
              step="0.01"
              required
              :error="errors.amount"
              @focus="errors.amount = null"
            />
          </div>
          <div class="col-span-4">
            <BaseSelect
              v-model="expense.currency"
              label="Currency"
              :options="currencyOptions"
            />
          </div>
        </div>

        <FileUpload
          v-model="expense.temp_file"
          label="Receipt"
          :existing-file="existingFile"
          @delete-existing="onDeleteExisting"
        />
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
  </div>
</template>
