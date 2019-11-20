<template>
  <div>
    <page-header />
    <client-form type="create"></client-form>
  </div>
</template>
<script>
import PageHeader from '@/layout/PageHeader.vue';
import clientForm from '@/components/client/form.vue';
  export default {
    components: {
      PageHeader: PageHeader,
      clientForm: clientForm
    }
  }
</script>