<script setup>
import { PhX } from '@phosphor-icons/vue'

defineProps({
  title: {
    type: String,
    default: ''
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value)
  }
})

const emit = defineEmits(['close'])

const sizeClasses = {
  sm: 'max-w-sm',
  md: 'max-w-md',
  lg: 'max-w-lg'
}
</script>

<template>
  <Teleport to="body">
    <Transition name="modal" appear>
      <!-- Above flyouts (z-50): it is often opened from inside one. The backdrop
           doesn't close it, so a stray click can't throw away what was typed. -->
      <div class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/10" />
        <div :class="['relative bg-white rounded-lg shadow-xl w-full', sizeClasses[size]]">
          <div class="flex items-center justify-between px-6 pt-5">
            <h3 class="text-lg font-bold text-gray-900">{{ title }}</h3>
            <button
              type="button"
              class="p-1 text-gray-400 hover:text-gray-600 cursor-pointer rounded-sm transition-colors"
              @click="emit('close')"
            >
              <PhX class="w-5 h-5" />
            </button>
          </div>
          <div class="p-6">
            <slot />
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
