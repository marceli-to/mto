<script setup>
import { ref, onMounted } from 'vue'
import { PhX } from '@phosphor-icons/vue'
import { useApi } from '@/composables/useApi'
import { useToast } from '@/composables/useToast'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import BaseButton from '@/components/ui/BaseButton.vue'

const props = defineProps({
  invoice: Object
})

const emit = defineEmits(['close', 'updated'])

const { get, post } = useApi()
const { success, error } = useToast()

const states = ref([])
const saving = ref(false)
const form = ref({
  state_id: props.invoice?.state_id || '',
  date_paid: props.invoice?.date_paid || '',
  remarks: props.invoice?.remarks || ''
})

async function fetchStates() {
  try {
    const data = await get('/api/invoice/states')
    states.value = data.data || []
  } catch (e) {
    error('Failed to load states')
  }
}

async function submit() {
  saving.value = true
  try {
    await post(`/api/invoice/update/state/${props.invoice.id}`, form.value)
    success('Invoice status updated')
    emit('updated')
  } catch (e) {
    error('Failed to update status')
  } finally {
    saving.value = false
  }
}

const stateOptions = ref([])
onMounted(async () => {
  await fetchStates()
  stateOptions.value = states.value.map(s => ({
    value: s.id,
    label: s.description.charAt(0).toUpperCase() + s.description.slice(1)
  }))
})

const showRemarks = ref(false)
function onStateChange() {
  const selectedState = states.value.find(s => s.id == form.value.state_id)
  showRemarks.value = selectedState?.description === 'cancelled'
}
</script>

<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/50" @click="emit('close')" />
      <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="">Update Invoice Status</h3>
          <button @click="emit('close')" class="p-1 hover:bg-gray-100 rounded">
            <PhX class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
          <BaseSelect
            v-model="form.state_id"
            label="Status"
            :options="stateOptions"
            @change="onStateChange"
          />

          <BaseInput
            v-model="form.date_paid"
            label="Date Paid"
            type="date"
          />

          <BaseInput
            v-if="showRemarks"
            v-model="form.remarks"
            label="Remarks"
          />

          <div class="flex justify-end gap-3 pt-4">
            <BaseButton variant="secondary" @click="emit('close')">
              Cancel
            </BaseButton>
            <BaseButton type="submit" :loading="saving">
              Update
            </BaseButton>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>
