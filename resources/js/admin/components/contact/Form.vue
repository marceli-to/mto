<template>
  <div class="container">
    <notifications classes="notification"/>
    <main class="content" role="main">
      <div>
        <h1>{{title}}</h1>
        <form @submit.prevent="submit">
          <div>
            <div class="form-row" :class="errors.name ? 'has-error': ''">
              <label>Name *</label>
              <input
                type="text"
                @focus="removeError('name')"
                name="name"
                v-model="contact.name">
            </div>
            <div class="form-row" :class="errors.name ? 'has-error': ''">
              <label>Firstname *</label>
              <input
                type="text"
                @focus="removeError('firstname')"
                name="firstname"
                v-model="contact.firstname">
            </div>
            <div class="form-row">
              <label>E-Mail</label>
              <input
                type="text"
                name="email"
                v-model="contact.email">
            </div>
            <div class="form-row">
              <label>Phone</label>
              <input 
                type="text" 
                name="phone" 
                v-model="contact.phone">
            </div>
          </div>
          <form-buttons :route="'contacts'" :param="parseInt(contact.client_id)"></form-buttons>
        </form>
      </div>
    </main>
  </div>
</template>
<script>
import PageHeader from "@/layout/PageHeader.vue";
import FormButtons from "@/components/ui/buttons/FormButtons.vue";
import Helpers from "@/mixins/helpers";

export default {
  components: {
    FormButtons: FormButtons,
  },

  props: {
    type: String
  },

  mixins: [Helpers],

  data() {
    return {

      errors: {
        name: false,
        firstname: false,
      },

      contact: {
        name: null,
        firstname: null,
        email: null,
        phone: null,
        client_id: null
      },
    };
  },

  created() {

    if (this.$props.type == "create") {
      this.contact.client_id = this.$route.params.client_id;
    }

    if (this.$props.type == "edit") {
      let uri = `/api/contact/edit/${this.$route.params.id}`;
      this.axios.get(uri).then(response => {
        this.contact = response.data;
      });
    }
  },

  methods: {
    validate() {

      if (this.contact.name && this.contact.firstname) {
        return true;
      }

      if (!this.contact.name) {
        this.errors.name = true;
      }

      if (!this.firstname.name) {
        this.errors.firstname = true;
      }

      return false;
    },

    submit() {
      if (!this.validate()) {
        this.validationError();
        return;
      }

      if (this.$props.type == "edit") {
        this.update();
      }

      if (this.$props.type == "create") {
        this.store();
      }
    },

    store() {
      let uri = "/api/contact/create";
      this.axios.post(uri, this.contact).then(response => {
        this.$router.push({name: 'contacts', params: { id: this.contact.client_id }});
      });
    },

    update() {
      let uri = `/api/contact/update/${this.$route.params.id}`;
      this.axios.post(uri, this.contact).then(response => {
        this.$router.push({name: 'contacts', params: { id: this.contact.client_id }});
      });
    },
  },

  computed: {
    title: function() {
      return this.$props.type == "edit"
        ? "Edit contact"
        : "Add contact";
    }
  }
};
</script>