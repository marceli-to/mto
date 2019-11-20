<template>
  <div>
    <page-header/>
    <notifications classes="notification"/>
    <div class="container">
      <main class="content" role="main">
        <div>
          <h1>Contacts</h1>
          <router-link :to="{ name: 'contact-create', params: { client_id: this.$route.params.id }}" class="btn-add">
            <span>Add</span>
          </router-link>
          <div class="list-items" v-if="contacts.length">
            <div
              :class="[contact.publish == 0 ? 'is-disabled' : '', 'list-item']"
              v-for="contact in contacts"
              :key="contact.id"
               data-icons="2"
            >
              <div class="list-item-body">
                <strong>{{ contact.firstname }} {{ contact.name }}</strong>
              </div>
              <div class="list-item-action" data-icons="2">
                <router-link
                  :to="{name: 'contact-edit', params: { id: contact.id }}"
                  class="icon-edit icon-mini"
                ></router-link>
                <a
                  href="javascript:;"
                  class="icon-trash icon-mini"
                  @click.prevent="destroy(contact.id,$event)"
                ></a>
              </div>
            </div>
          </div>
          <div v-else>
            <p>No records available...</p>
          </div>
          <footer class="form-footer">
              <div>
                  <router-link :to="{ name: 'clients'}" class="btn-tertiary">Zurück</router-link>
              </div>
          </footer>
        </div>
      </main>
    </div>
  </div>
</template>
<script>
import PageHeader from "@/layout/PageHeader.vue";
import Progress from "@/mixins/progress";

export default {
  components: {
    PageHeader: PageHeader
  },

  mixins: [Progress],

  data() {
    return {
      contacts: [],
      client_id: null
    };
  },

  created() {
    this.fetch();
  },

  methods: {
    
    fetch() {
      let uri = `/api/contacts/get/${this.$route.params.id}`;
      this.axios.get(uri).then(response => {
        this.contacts = response.data.data;
        this.client_id = this.$route.params.id;
      });
    },

    destroy(id,event) {
      if (confirm("Bitte löschen bestätigen!")) {
        let uri = `/api/contact/destroy/${id}`;
        let el = this.progress(event.target);
        this.axios.delete(uri).then(response => {
          this.fetch();
          this.$notify({ type: "success", text: "Record deleted" });
          this.progress(el);
        });
      }
    },

    clone(id,event) {
      let uri = `/api/contact/clone/${id}`;
      let el = this.progress(event.target);
      this.axios.get(uri).then(response => {
        this.contacts.unshift(response.data);
        this.$notify({ type: "success", text: "Record cloned" });
        this.progress(el);
      });
    },

    toggleStatus(id,event) {
      let uri = `/api/contact/status/${id}`;
      let el = this.progress(event.target);
      this.axios.get(uri).then(response => {
        const index = this.contacts.findIndex(x => x.id === id);
        this.contacts[index].publish = response.data;
        this.$notify({ type: "success", text: "Status changed" });
        this.progress(el)
      });
    },
  },
};
</script>