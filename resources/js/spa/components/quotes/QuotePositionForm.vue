<script setup>
import { ref, onMounted } from 'vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'

const props = defineProps({
  position: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close', 'save'])

const form = ref({
  description: '',
  amount: 0,
})

onMounted(() => {
  if (props.position) {
    form.value = {
      description: props.position.description || '',
      amount: props.position.amount || 0,
    }
  }
})

function submit() {
  if (!form.value.description?.trim()) return

  const data = {
    ...form.value,
    amount: parseFloat(form.value.amount) || 0,
  }

  if (props.position) {
    data.id = props.position.id || null
    data._index = props.position._index
  }

  emit('save', data)
}
</script>

<template>
  <Teleport to="body">
    <div class="fixed inset-0 bg-black/30 flex items-center justify-center z-50" @click.self="emit('close')">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
        <div class="px-6 py-4 border-b border-gray-100">
          <h3 class="font-bold text-gray-900">{{ position ? 'Edit Position' : 'Add Position' }}</h3>
        </div>
        <form @submit.prevent="submit" class="p-6 space-y-4">
          <div>
            <label class="block text-sm text-gray-500 mb-2">Description</label>
            <textarea
              v-model="form.description"
              rows="2"
              class="w-full px-3 py-3 border border-gray-200 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-gray-200 focus:border-gray-300"
              required
            />
          </div>
          <BaseInput
            v-model="form.amount"
            label="Amount (CHF)"
            type="number"
            step="0.01"
          />
          <div class="flex justify-end gap-3 pt-2">
            <BaseButton type="button" variant="secondary" @click="emit('close')">Cancel</BaseButton>
            <BaseButton type="submit">Save</BaseButton>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>
