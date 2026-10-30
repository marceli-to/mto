<script setup>
import { ref, computed, onMounted } from 'vue'
import { PhPlus, PhPencil, PhTrash, PhArrowLeft } from '@phosphor-icons/vue'
import { useApi } from '@/composables/useApi'
import { useToast } from '@/composables/useToast'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import Flyout from '@/components/ui/Flyout.vue'
import QuoteBlockForm from './QuoteBlockForm.vue'

const { get, del } = useApi()
const { success, error } = useToast()

const blocks = ref([])
const loading = ref(true)
const deleteDialog = ref({ show: false, id: null, loading: false })
const flyout = ref({ show: false, block: null })

const flyoutTitle = computed(() => flyout.value.block?.id ? 'Edit Text Block' : 'New Text Block')

async function fetchBlocks() {
  loading.value = true
  try {
    const data = await get('/api/quote-blocks/get')
    blocks.value = data.data || []
  } catch (e) {
    error('Failed to load text blocks')
  } finally {
    loading.value = false
  }
}

function openCreate() {
  flyout.value = { show: true, block: null }
}

function openEdit(block) {
  flyout.value = { show: true, block }
}

function closeFlyout() {
  flyout.value = { show: false, block: null }
}

function onSaved() {
  closeFlyout()
  fetchBlocks()
}

function confirmDelete(id) {
  deleteDialog.value = { show: true, id, loading: false }
}

async function deleteBlock() {
  deleteDialog.value.loading = true
  try {
    await del(`/api/quote-block/destroy/${deleteDialog.value.id}`)
    await fetchBlocks()
    success('Text block deleted')
    deleteDialog.value.show = false
  } catch (e) {
    error('Failed to delete text block')
  } finally {
    deleteDialog.value.loading = false
  }
}

onMounted(fetchBlocks)
</script>

<template>
  <div>
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-12">
      <div class="flex items-center gap-2">
        <button
          @click="openCreate"
          class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer rounded-sm transition-colors"
          title="Add Text Block"
        >
          <PhPlus class="w-4 h-4" />
        </button>
        <h1 class="text-xl text-gray-900 font-bold">
          Text Blocks
        </h1>
      </div>

      <router-link
        :to="{ name: 'quotes' }"
        class="flex items-center gap-2 text-sm text-gray-500 hover:text-black transition-colors"
      >
        <PhArrowLeft class="w-4 h-4" />
        Quotes
      </router-link>
    </div>

    <div v-if="loading" class="text-center py-16 text-gray-400">
      <div class="animate-pulse">Loading...</div>
    </div>

    <div v-else-if="blocks.length === 0" class="text-center py-16">
      <div class="text-gray-400 mb-2">No text blocks yet</div>
      <p class="text-sm text-gray-400">Save reusable text like terms or hosting requirements here</p>
    </div>

    <div v-else class="overflow-hidden border-t border-gray-100">
      <ul class="divide-y divide-gray-100">
        <li
          v-for="block in blocks"
          :key="block.id"
          class="flex items-center justify-between gap-6 py-4 hover:bg-gray-50/50 transition-colors"
        >
          <div class="flex items-center gap-3 min-w-0">
            <span class="truncate">{{ block.title }}</span>
            <span v-if="block.is_default" class="px-2 py-0.5 rounded-md text-xs font-medium bg-green-100 text-green-800">
              default
            </span>
          </div>
          <div class="flex items-center gap-1 shrink-0">
            <button
              @click="openEdit(block)"
              class="p-2.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer rounded-sm transition-colors"
              title="Edit"
            >
              <PhPencil class="w-5 h-5" />
            </button>
            <button
              @click="confirmDelete(block.id)"
              class="p-2.5 text-gray-400 hover:text-red-600 hover:bg-red-50 cursor-pointer rounded-sm transition-colors"
              title="Delete"
            >
              <PhTrash class="w-5 h-5" />
            </button>
          </div>
        </li>
      </ul>
    </div>

    <ConfirmDialog
      :show="deleteDialog.show"
      title="Delete Text Block"
      message="Are you sure you want to delete this text block? Quotes that already use it keep their copy."
      :loading="deleteDialog.loading"
      @confirm="deleteBlock"
      @cancel="deleteDialog.show = false"
    />

    <Flyout
      :show="flyout.show"
      :title="flyoutTitle"
      size="2xl"
      @close="closeFlyout"
    >
      <QuoteBlockForm
        :block="flyout.block"
        @saved="onSaved"
        @cancel="closeFlyout"
      />
    </Flyout>
  </div>
</template>
