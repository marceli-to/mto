<script setup>
import { ref, computed, onMounted } from 'vue'
import { PhPlus, PhPencil, PhTrash, PhCopy } from '@phosphor-icons/vue'
import { useApi } from '@/composables/useApi'
import { useToast } from '@/composables/useToast'
import SearchInput from '@/components/ui/SearchInput.vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'

const { get, del } = useApi()
const { success, error } = useToast()

const projects = ref([])
const search = ref('')
const loading = ref(true)
const deleteDialog = ref({ show: false, id: null, loading: false })

const filteredProjects = computed(() => {
  if (!search.value) return projects.value
  const q = search.value.toLowerCase()
  return projects.value.filter(p =>
    p.name?.toLowerCase().includes(q) ||
    p.client?.name?.toLowerCase().includes(q)
  )
})

async function fetchProjects() {
  loading.value = true
  try {
    const data = await get('/api/projects/get')
    projects.value = data.data || []
  } catch (e) {
    error('Failed to load projects')
  } finally {
    loading.value = false
  }
}

async function cloneProject(id) {
  try {
    const data = await get(`/api/project/clone/${id}`)
    projects.value.unshift(data)
    success('Project cloned')
  } catch (e) {
    error('Failed to clone project')
  }
}

function confirmDelete(id) {
  deleteDialog.value = { show: true, id, loading: false }
}

async function deleteProject() {
  deleteDialog.value.loading = true
  try {
    await del(`/api/project/destroy/${deleteDialog.value.id}`)
    projects.value = projects.value.filter(p => p.id !== deleteDialog.value.id)
    success('Project deleted')
    deleteDialog.value.show = false
  } catch (e) {
    error('Failed to delete project')
  } finally {
    deleteDialog.value.loading = false
  }
}

onMounted(fetchProjects)
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Projects</h1>
      <router-link
        :to="{ name: 'project-create' }"
        class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
      >
        <PhPlus class="w-5 h-5" />
        Add Project
      </router-link>
    </div>

    <div class="mb-6">
      <SearchInput v-model="search" placeholder="Search by name or client..." />
    </div>

    <div v-if="loading" class="text-center py-12 text-gray-500">
      Loading...
    </div>

    <div v-else-if="filteredProjects.length === 0" class="text-center py-12 text-gray-500">
      No projects found
    </div>

    <div v-else class="bg-white rounded-lg shadow overflow-hidden">
      <ul class="divide-y divide-gray-200">
        <li
          v-for="project in filteredProjects"
          :key="project.id"
          class="flex items-center justify-between px-6 py-4 hover:bg-gray-50"
        >
          <div>
            <p class="font-medium text-gray-900">{{ project.name }}</p>
            <p v-if="project.client" class="text-sm text-gray-500">{{ project.client.name }}</p>
          </div>
          <div class="flex items-center gap-2">
            <router-link
              :to="{ name: 'project-edit', params: { id: project.id } }"
              class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded"
              title="Edit"
            >
              <PhPencil class="w-5 h-5" />
            </router-link>
            <button
              @click="cloneProject(project.id)"
              class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded"
              title="Clone"
            >
              <PhCopy class="w-5 h-5" />
            </button>
            <button
              @click="confirmDelete(project.id)"
              class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded"
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
      title="Delete Project"
      message="Are you sure you want to delete this project?"
      :loading="deleteDialog.loading"
      @confirm="deleteProject"
      @cancel="deleteDialog.show = false"
    />
  </div>
</template>
