<script setup>
import { PhCheckCircle, PhXCircle, PhInfo, PhWarning, PhX } from '@phosphor-icons/vue'
import { useToast } from '@/composables/useToast'

const { toasts, removeToast } = useToast()

const icons = {
  success: PhCheckCircle,
  error: PhXCircle,
  info: PhInfo,
  warning: PhWarning
}

const colors = {
  success: 'bg-green-50 text-green-800 border-green-200',
  error: 'bg-red-50 text-red-800 border-red-200',
  info: 'bg-blue-50 text-blue-800 border-blue-200',
  warning: 'bg-yellow-50 text-yellow-800 border-yellow-200'
}
</script>

<template>
  <div class="fixed top-4 right-4 z-50 flex flex-col gap-2">
    <TransitionGroup name="toast">
      <div
        v-for="toast in toasts"
        :key="toast.id"
        :class="[colors[toast.type], 'flex items-center gap-3 px-4 py-3 rounded-lg border shadow-lg min-w-[300px]']"
      >
        <component :is="icons[toast.type]" class="w-5 h-5 flex-shrink-0" weight="fill" />
        <span class="flex-1">{{ toast.message }}</span>
        <button
          @click="removeToast(toast.id)"
          class="p-1 hover:bg-black/5 rounded"
        >
          <PhX class="w-4 h-4" />
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s ease;
}

.toast-enter-from {
  opacity: 0;
  transform: translateX(100%);
}

.toast-leave-to {
  opacity: 0;
  transform: translateX(100%);
}
</style>
