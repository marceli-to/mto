<script setup>
defineProps({
  modelValue: [Boolean, Number],
  label: String,
  disabled: Boolean
})

const emit = defineEmits(['update:modelValue'])
</script>

<template>
  <label
    class="inline-flex items-center gap-2.5 select-none"
    :class="disabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'"
  >
    <span class="relative flex items-center justify-center">
      <input
        type="checkbox"
        class="peer sr-only"
        :checked="!!modelValue"
        :disabled="disabled"
        @change="emit('update:modelValue', $event.target.checked)"
      />
      <span
        class="w-[18px] h-[18px] flex items-center justify-center rounded-md border border-gray-200 bg-white transition-all
               peer-hover:border-gray-300
               peer-checked:bg-gray-900 peer-checked:border-gray-900
               peer-focus-visible:ring-2 peer-focus-visible:ring-gray-200
               peer-checked:[&_svg]:scale-100 peer-checked:[&_svg]:opacity-100"
      >
        <svg
          class="w-3 h-3 text-white scale-50 opacity-0 transition-all duration-150"
          viewBox="0 0 16 16"
          fill="none"
          stroke="currentColor"
          stroke-width="2.5"
          stroke-linecap="round"
          stroke-linejoin="round"
        >
          <path d="M3 8.5L6.5 12L13 4.5" />
        </svg>
      </span>
    </span>
    <span v-if="label || $slots.default" class="text-sm text-gray-600">
      <slot>{{ label }}</slot>
    </span>
  </label>
</template>
