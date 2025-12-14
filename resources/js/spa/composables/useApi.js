import { ref } from 'vue'
import axios from 'axios'

// Configure axios defaults
axios.defaults.withCredentials = true
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'

// Set CSRF token
const csrfToken = document.head.querySelector('meta[name="csrf-token"]')
if (csrfToken) {
  axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken.content
}

// Handle 401 responses
axios.interceptors.response.use(
  response => response,
  error => {
    if (error.response && error.response.status === 401) {
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

export function useApi() {
  const loading = ref(false)
  const error = ref(null)

  async function get(url) {
    loading.value = true
    error.value = null
    try {
      const response = await axios.get(url)
      return response.data
    } catch (e) {
      error.value = e.response?.data?.message || 'An error occurred'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function post(url, data = {}) {
    loading.value = true
    error.value = null
    try {
      const response = await axios.post(url, data)
      return response.data
    } catch (e) {
      error.value = e.response?.data?.message || 'An error occurred'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function put(url, data = {}) {
    loading.value = true
    error.value = null
    try {
      const response = await axios.put(url, data)
      return response.data
    } catch (e) {
      error.value = e.response?.data?.message || 'An error occurred'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function del(url) {
    loading.value = true
    error.value = null
    try {
      const response = await axios.delete(url)
      return response.data
    } catch (e) {
      error.value = e.response?.data?.message || 'An error occurred'
      throw e
    } finally {
      loading.value = false
    }
  }

  return {
    loading,
    error,
    get,
    post,
    put,
    del
  }
}
