<script setup>
import { PhWarning } from '@phosphor-icons/vue'
import BaseButton from './BaseButton.vue'

defineProps({
  show: Boolean,
  title: {
    type: String,
    default: 'Confirm'
  },
  message: {
    type: String,
    default: 'Are you sure you want to proceed?'
  },
  confirmLabel: {
    type: String,
    default: 'Delete'
  },
  loading: Boolean
})

const emit = defineEmits(['confirm', 'cancel'])
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
      >
        <div
          class="absolute inset-0 bg-black/10"
          @click="emit('cancel')"
        />
        <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
          <div class="flex items-start gap-4">

            <div class="flex-1">
              <h3 class="text-lg text-gray-900 mb-4">{{ title }}</h3>
              <p class="text-gray-500">{{ message }}</p>
            </div>
          </div>
          <div class="flex justify-end gap-3 mt-8">
            <BaseButton variant="secondary" @click="emit('cancel')">
              Cancel
            </BaseButton>
            <BaseButton variant="danger" :loading="loading" @click="emit('confirm')">
              {{ confirmLabel }}
            </BaseButton>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
</style>
