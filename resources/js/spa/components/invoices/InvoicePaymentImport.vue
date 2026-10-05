<script setup>
import { ref, computed } from 'vue'
import { useApi } from '@/composables/useApi'
import { useToast } from '@/composables/useToast'
import { useCurrency } from '@/composables/useCurrency'
import FileUpload from '@/components/ui/FileUpload.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseCheckbox from '@/components/ui/BaseCheckbox.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'

const props = defineProps({
  // The invoice list, so a payment can be pointed at another unpaid invoice
  invoices: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['applied', 'cancel'])

const { post } = useApi()
const { success, error } = useToast()
const { formatCurrency } = useCurrency()

const tempFile = ref('')
const scanning = ref(false)
const saving = ref(false)
const rows = ref(null)

const statusLabels = {
  matched: { label: 'matched', class: 'bg-green-100 text-green-800' },
  suggested: { label: 'by amount', class: 'bg-yellow-100 text-yellow-800' },
  amount_mismatch: { label: 'amount differs', class: 'bg-red-100 text-red-800' },
  already_paid: { label: 'already paid', class: 'bg-gray-100 text-gray-500' },
  unmatched: { label: 'no match', class: 'bg-gray-100 text-gray-500' }
}

const unpaidOptions = computed(() =>
  props.invoices
    .filter(inv => ['open', 'pending'].includes(inv.state?.description))
    .map(inv => ({
      value: inv.id,
      label: `${inv.number} ${inv.client?.acronym ?? ''} – ${formatCurrency(inv.grandtotal)}`
    }))
)

function invoiceAmount(id) {
  const invoice = props.invoices.find(inv => inv.id === Number(id))
  return invoice ? Number(invoice.grandtotal) : null
}

async function scan(filename) {
  if (!filename) return
  scanning.value = true
  rows.value = null
  try {
    const data = await post('/api/invoices/payments/scan', { temp_file: filename })
    rows.value = (data.payments || []).map(payment => {
      const assignable = ['matched', 'suggested', 'amount_mismatch'].includes(payment.status)
      return {
        ...payment,
        invoice_id: assignable ? payment.invoice.id : '',
        // A wrong amount gets a look before it counts as paid
        checked: ['matched', 'suggested'].includes(payment.status)
      }
    })
  } catch (e) {
    error(e?.response?.data?.message || 'Failed to scan statement')
  } finally {
    scanning.value = false
  }
}

function onInvoiceChange(row, id) {
  row.invoice_id = id ? Number(id) : ''
  row.checked = !!id
}

const toApply = computed(() => (rows.value || []).filter(row => row.checked && row.invoice_id))

// The same invoice picked for two payments would be paid on whichever date wins
const duplicate = computed(() => {
  const ids = toApply.value.map(row => row.invoice_id)
  return ids.length !== new Set(ids).size
})

async function apply() {
  saving.value = true
  try {
    const data = await post('/api/invoices/payments/apply', {
      payments: toApply.value.map(row => ({ invoice_id: row.invoice_id, date_paid: row.date }))
    })
    const updated = data.updated?.length ?? 0
    success(`${updated} ${updated === 1 ? 'invoice' : 'invoices'} marked paid`)
    emit('applied')
  } catch (e) {
    error(e?.response?.data?.message || 'Failed to update invoices')
  } finally {
    saving.value = false
  }
}

function formatDate(date) {
  return date ? new Date(date).toLocaleDateString('de-CH') : ''
}
</script>

<template>
  <div>
    <div class="space-y-6">
      <FileUpload
        v-model="tempFile"
        label="Bank statement (PDF)"
        @uploaded="scan"
      />

      <div v-if="scanning" class="text-sm text-gray-500 animate-pulse">
        Reading statement...
      </div>

      <div v-else-if="rows && rows.length === 0" class="text-sm text-gray-500">
        No incoming payments found on this statement.
      </div>

      <ul v-else-if="rows" class="divide-y divide-gray-100 border-t border-gray-100">
        <li v-for="(row, i) in rows" :key="i" class="flex items-start gap-4 py-4">
          <BaseCheckbox
            v-model="row.checked"
            :disabled="!row.invoice_id"
            class="mt-0.5"
          />
          <div class="flex-1 min-w-0">
            <div class="flex items-baseline justify-between gap-4">
              <span class="font-medium truncate">{{ row.payer }}</span>
              <span class="tabular-nums shrink-0">{{ formatCurrency(row.amount) }}</span>
            </div>
            <div class="text-xs text-gray-500 mt-0.5 truncate">
              {{ formatDate(row.date) }}<template v-if="row.reference"> · {{ row.reference }}</template>
            </div>
            <div class="flex items-center gap-3 mt-3">
              <span :class="[statusLabels[row.status]?.class, 'px-2 py-1 rounded-md text-xs font-medium shrink-0']">
                {{ statusLabels[row.status]?.label }}
              </span>
              <span v-if="row.status === 'already_paid'" class="text-sm text-gray-500 truncate">
                {{ row.invoice.number }} {{ row.invoice.client }}
              </span>
              <div v-else class="flex-1 min-w-0">
                <BaseSelect
                  :model-value="row.invoice_id"
                  :options="unpaidOptions"
                  placeholder="Pick invoice…"
                  compact
                  @update:model-value="onInvoiceChange(row, $event)"
                />
              </div>
            </div>
            <p
              v-if="row.invoice_id && invoiceAmount(row.invoice_id) !== null && Math.abs(invoiceAmount(row.invoice_id) - row.amount) >= 0.005"
              class="text-xs text-red-600 mt-2"
            >
              Invoice is {{ formatCurrency(invoiceAmount(row.invoice_id)) }}, payment is {{ formatCurrency(row.amount) }}.
            </p>
          </div>
        </li>
      </ul>

      <p v-if="duplicate" class="text-sm text-red-600">
        The same invoice is picked for more than one payment.
      </p>
    </div>

    <div class="flex items-center justify-end gap-3 mt-8">
      <BaseButton type="button" variant="secondary" @click="emit('cancel')">
        Cancel
      </BaseButton>
      <BaseButton
        v-if="rows"
        :disabled="toApply.length === 0 || duplicate"
        :loading="saving"
        @click="apply"
      >
        Mark {{ toApply.length }} paid
      </BaseButton>
    </div>
  </div>
</template>
