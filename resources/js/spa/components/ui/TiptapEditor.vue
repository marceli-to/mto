<script setup>
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Underline from '@tiptap/extension-underline'
import { watch } from 'vue'

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  label: {
    type: String,
    default: null
  }
})

const emit = defineEmits(['update:modelValue'])

const editor = useEditor({
  content: props.modelValue,
  extensions: [
    StarterKit.configure({
      heading: {
        levels: [2, 3]
      }
    }),
    Underline,
  ],
  editorProps: {
    attributes: {
      class: 'prose prose-sm max-w-none focus:outline-none min-h-[120px] px-3 py-3'
    }
  },
  onUpdate: ({ editor }) => {
    emit('update:modelValue', editor.getHTML())
  }
})

watch(() => props.modelValue, (value) => {
  if (editor.value && editor.value.getHTML() !== value) {
    editor.value.commands.setContent(value, false)
  }
})
</script>

<template>
  <div>
    <label v-if="label" class="block text-sm text-gray-500 mb-2">{{ label }}</label>
    <div class="border border-gray-200 rounded-md overflow-hidden">
      <!-- Toolbar -->
      <div v-if="editor" class="flex items-center gap-1 px-2 py-1.5 border-b border-gray-200 bg-gray-50">
        <button
          type="button"
          @click="editor.chain().focus().toggleBold().run()"
          :class="[editor.isActive('bold') ? 'bg-gray-200 text-gray-900' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100']"
          class="p-1.5 rounded text-xs font-bold cursor-pointer transition-colors"
          title="Bold"
        >
          B
        </button>
        <button
          type="button"
          @click="editor.chain().focus().toggleItalic().run()"
          :class="[editor.isActive('italic') ? 'bg-gray-200 text-gray-900' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100']"
          class="p-1.5 rounded text-xs italic cursor-pointer transition-colors"
          title="Italic"
        >
          I
        </button>
        <div class="w-px h-4 bg-gray-300 mx-1"></div>
        <button
          type="button"
          @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
          :class="[editor.isActive('heading', { level: 2 }) ? 'bg-gray-200 text-gray-900' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100']"
          class="p-1.5 rounded text-xs font-bold cursor-pointer transition-colors"
          title="Heading 2"
        >
          H2
        </button>
        <button
          type="button"
          @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
          :class="[editor.isActive('heading', { level: 3 }) ? 'bg-gray-200 text-gray-900' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100']"
          class="p-1.5 rounded text-xs font-bold cursor-pointer transition-colors"
          title="Heading 3"
        >
          H3
        </button>
        <div class="w-px h-4 bg-gray-300 mx-1"></div>
        <button
          type="button"
          @click="editor.chain().focus().toggleBulletList().run()"
          :class="[editor.isActive('bulletList') ? 'bg-gray-200 text-gray-900' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100']"
          class="p-1.5 rounded text-xs cursor-pointer transition-colors"
          title="Bullet List"
        >
          &bull; List
        </button>
      </div>
      <!-- Editor -->
      <EditorContent :editor="editor" />
    </div>
  </div>
</template>

<style>
.tiptap {
  min-height: 120px;
}
.tiptap h2 {
  font-size: 1rem;
  font-weight: 600;
  margin-top: 0.75rem;
  margin-bottom: 0.25rem;
}
.tiptap h3 {
  font-size: 0.9rem;
  font-weight: 600;
  margin-top: 0.5rem;
  margin-bottom: 0.25rem;
}
.tiptap p {
  margin-bottom: 0.25rem;
}
.tiptap ul {
  list-style-type: disc;
  padding-left: 1.5rem;
  margin-bottom: 0.25rem;
}
</style>
