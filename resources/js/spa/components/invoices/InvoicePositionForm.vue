<script setup>
import { ref, computed, watch } from 'vue'
import { PhX } from '@phosphor-icons/vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import BaseButton from '@/components/ui/BaseButton.vue'

const props = defineProps({
  position: Object
})

const emit = defineEmits(['close', 'save'])

const typeOptions = [
  { value: 'hourly', label: 'Hourly' },
  { value: 'flat', label: 'Flat Rate' },
  { value: 'reminder', label: 'Reminder Fee' }
]

const form = ref({
  periode: '',
  description: '',
  type: 'hourly',
  hours: '',
  rate: '',
  amount: '',
  is_flat: false,
  _index: undefined
})

// Initialize form with existing position data
if (props.position) {
  form.value = {
    ...props.position,
    type: props.position.is_flat ? 'flat' : 'hourly'
  }
}

const isFlat = computed(() => form.value.type !== 'hourly')

// Auto-calculate amount for hourly
watch([() => form.value.hours, () => form.value.rate, () => form.value.type], () => {
  if (!isFlat.value && form.value.hours && form.value.rate) {
    form.value.amount = (parseFloat(form.value.hours) * parseFloat(form.value.rate)).toFixed(2)
  }
})

function submit() {
  const position = {
    ...form.value,
    is_flat: isFlat.value,
    amount: parseFloat(form.value.amount) || 0
  }
  emit('save', position)
}
</script>

<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/50" @click="emit('close')" />
      <div class="relative bg-white rounded-lg shadow-xl max-w-lg w-full p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold">{{ position ? 'Edit Position' : 'Add Position' }}</h3>
          <button @click="emit('close')" class="p-1 hover:bg-gray-100 rounded">
            <PhX class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
          <BaseInput
            v-model="form.periode"
            label="Period"
            placeholder="e.g., January 2024"
          />

          <BaseInput
            v-model="form.description"
            label="Description"
          />

          <BaseSelect
            v-model="form.type"
            label="Type"
            :options="typeOptions"
          />

          <div v-if="!isFlat" class="grid grid-cols-2 gap-4">
            <BaseInput
              v-model="form.hours"
              label="Hours"
              type="number"
              step="0.25"
            />
            <BaseInput
              v-model="form.rate"
              label="Rate (CHF/h)"
              type="number"
              step="0.01"
            />
          </div>

          <BaseInput
            v-model="form.amount"
            label="Amount (CHF)"
            type="number"
            step="0.01"
            :disabled="!isFlat"
          />

          <div class="flex justify-end gap-3 pt-4">
            <BaseButton variant="secondary" type="button" @click="emit('close')">
              Cancel
            </BaseButton>
            <BaseButton type="submit">
              Save
            </BaseButton>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>
