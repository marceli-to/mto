<script setup>
import { ref, onMounted } from 'vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import Modal from '@/components/ui/Modal.vue'

const props = defineProps({
  position: {
    type: Object,
    default: null
  },
  currency: {
    type: String,
    default: 'CHF'
  }
})

const emit = defineEmits(['close', 'save'])

const form = ref({
  description: '',
  amount: 0,
  amount_text: '',
})

onMounted(() => {
  if (props.position) {
    form.value = {
      description: props.position.description || '',
      amount: props.position.amount || 0,
      amount_text: props.position.amount_text || '',
    }
  }
})

function submit() {
  if (!form.value.description?.trim()) return

  const data = {
    ...form.value,
    amount: parseFloat(form.value.amount) || 0,
    amount_text: form.value.amount_text?.trim() || null,
  }

  if (props.position) {
    data.id = props.position.id || null
    data._index = props.position._index
  }

  emit('save', data)
}
</script>

<template>
  <Modal :title="position ? 'Edit Position' : 'Add Position'" size="md" @close="emit('close')">
    <form @submit.prevent="submit" class="space-y-6">
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
        :label="`Amount (${currency})`"
        type="number"
        step="0.01"
        :disabled="!!form.amount_text"
      />
      <BaseInput
        v-model="form.amount_text"
        label="Text instead of amount"
        placeholder="Fixpreis folgt"
      />
      <div class="flex justify-end gap-3 pt-2">
        <BaseButton type="button" variant="secondary" @click="emit('close')">Cancel</BaseButton>
        <BaseButton type="submit">Save</BaseButton>
      </div>
    </form>
  </Modal>
</template>
