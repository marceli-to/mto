<template>
  <div>
    <page-header />
    <contact-form type="create"></contact-form>
  </div>
</template>
<script>
import PageHeader from '@/layout/PageHeader.vue';
import contactForm from '@/components/contact/form.vue';
  export default {
    components: {
      PageHeader: PageHeader,
      contactForm: contactForm
    }
  }
</script>