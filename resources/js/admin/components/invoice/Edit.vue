<template>
  <div>
    <page-header />
    <invoice-form type="edit"></invoice-form>
  </div>
</template>
<script>
import PageHeader from '@/layout/PageHeader.vue';
import invoiceForm from '@/components/invoice/form.vue';
  export default {
    components: {
      PageHeader: PageHeader,
      invoiceForm: invoiceForm
    }
  }
</script>