<template>
  <div>
    <page-header />
    <project-form type="edit"></project-form>
  </div>
</template>
<script>
import PageHeader from '@/layout/PageHeader.vue';
import projectForm from '@/components/project/form.vue';
  export default {
    components: {
      PageHeader: PageHeader,
      projectForm: projectForm
    }
  }
</script>