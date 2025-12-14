<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { PhMagnifyingGlass } from '@phosphor-icons/vue'

const props = defineProps({
  modelValue: String,
  placeholder: {
    type: String,
    default: 'Suchen...'
  }
})

const emit = defineEmits(['update:modelValue'])
const inputRef = ref(null)

function handleKeydown(e) {
  if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
    e.preventDefault()
    inputRef.value?.focus()
  }
}

onMounted(() => {
  document.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
})
</script>

<template>
  <div class="relative w-64">
    <PhMagnifyingGlass class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
    <input
      ref="inputRef"
      type="text"
      :value="modelValue"
      :placeholder="placeholder"
      class="w-full pl-9 pr-14 py-2 bg-white border border-gray-200 rounded-xs text-sm placeholder-gray-400 focus:outline-none focus:border-gray-300 transition-all"
      @input="emit('update:modelValue', $event.target.value)"
    />
    <div class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-1 text-gray-400 text-xs pointer-events-none">
      <span>⌘K</span>
    </div>
  </div>
</template>
