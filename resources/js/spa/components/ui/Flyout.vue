<script setup>
import { PhX } from '@phosphor-icons/vue'

const props = defineProps({
  show: Boolean,
  title: {
    type: String,
    default: ''
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg', 'xl'].includes(value)
  }
})

const emit = defineEmits(['close'])

const sizeClasses = {
  sm: 'max-w-sm',
  md: 'max-w-md',
  lg: 'max-w-xl',
  xl: 'max-w-3xl',
  '2xl': 'max-w-4xl'
}
</script>

<template>
  <Teleport to="body">
    <Transition name="flyout">
      <div
        v-if="show"
        class="fixed inset-0 z-50 flex justify-end"
      >
        <!-- Backdrop -->
        <div
          class="absolute inset-0 bg-black/10"
          @click="emit('close')"
        />
        
        <!-- Panel -->
        <div
          :class="[
            'relative bg-white w-full shadow-xl rounded-xl flex flex-col my-4 mr-4 h-[calc(100%-2rem)] overflow-y-auto',
            sizeClasses[size]
          ]">
          <!-- Header -->
          <div class="flex items-center justify-between px-6 py-4">
            <h2 class="text-lg font-bold text-gray-900">{{ title }}</h2>
            <button
              type="button"
              class="p-1 text-gray-400 hover:text-gray-600 cursor-pointer rounded-sm transition-colors"
              @click="emit('close')"
            >
              <PhX class="w-5 h-5" />
            </button>
          </div>
          
          <!-- Content -->
          <div class="flex-1 overflow-y-auto px-6 py-6">
            <slot />
          </div>
          
          <!-- Footer -->
          <div v-if="$slots.footer" class="px-6 py-4">
            <slot name="footer" />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.flyout-enter-active,
.flyout-leave-active {
  transition: opacity 0.2s ease;
}

.flyout-enter-from,
.flyout-leave-to {
  opacity: 0;
}
</style>
