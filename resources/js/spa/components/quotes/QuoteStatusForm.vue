<script setup>
import { ref } from 'vue'
import { useApi } from '@/composables/useApi'
import { useToast } from '@/composables/useToast'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import BaseButton from '@/components/ui/BaseButton.vue'

const props = defineProps({
  quote: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'updated'])

const { post } = useApi()
const { success, error } = useToast()

const status = ref(props.quote.status || 'draft')
const saving = ref(false)

const statusOptions = [
  { value: 'draft', label: 'Draft' },
  { value: 'sent', label: 'Sent' },
  { value: 'accepted', label: 'Accepted' },
  { value: 'declined', label: 'Declined' },
]

async function submit() {
  saving.value = true
  try {
    await post(`/api/quote/update/status/${props.quote.id}`, { status: status.value })
    success('Status updated')
    emit('updated')
  } catch (e) {
    error('Failed to update status')
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <Teleport to="body">
    <div class="fixed inset-0 bg-black/30 flex items-center justify-center z-50" @click.self="emit('close')">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-sm mx-4">
        <div class="px-6 py-4 border-b border-gray-100">
          <h3 class="font-bold text-gray-900">Update Status</h3>
        </div>
        <form @submit.prevent="submit" class="p-6 space-y-4">
          <BaseSelect
            v-model="status"
            label="Status"
            :options="statusOptions"
          />
          <div class="flex justify-end gap-3 pt-2">
            <BaseButton type="button" variant="secondary" @click="emit('close')">Cancel</BaseButton>
            <BaseButton type="submit" :loading="saving">Update</BaseButton>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>
