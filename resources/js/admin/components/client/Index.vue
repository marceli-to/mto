<template>
  <div>
    <page-header/>
    <notifications classes="notification"/>
    <div class="container">
      <main class="content" role="main">
        <div>
          <h1>Clients</h1>
          <router-link :to="{ name: 'client-create' }" class="btn-add">
            <span>Add</span>
          </router-link>
          <div class="list-items" v-if="clients.length">
            <div
              :class="[client.publish == 0 ? 'is-disabled' : '', 'list-item']"
              v-for="client in clientList"
              :key="client.id"
              data-icons="5"
            >
              <div class="list-item-body">
                <strong>{{ client.name }}</strong><span v-if="client.city"> &bull; {{ client.city }}</span>
              </div>
              <div class="list-item-action" data-icons="5">
                <router-link
                  :to="{name: 'contacts', params: { id: client.id }}"
                  class="icon-users icon-mini"
                ></router-link>
                <a
                  href="javascript:;"
                  :class="[client.publish == 1 ? 'icon-eye' : 'icon-eye-off', 'icon-mini']"
                  @click.prevent="toggleStatus(client.id,$event)"
                ></a>
                <router-link
                  :to="{name: 'client-edit', params: { id: client.id }}"
                  class="icon-edit icon-mini"
                ></router-link>
                <a
                  href="javascript:;"
                  class="icon-copy icon-mini"
                  @click.prevent="clone(client.id,$event)"
                ></a>
                <a
                  href="javascript:;"
                  class="icon-trash icon-mini"
                  @click.prevent="destroy(client.id,$event)"
                ></a>
              </div>
            </div>
          </div>
          <div v-else>
            <p>No records available...</p>
          </div>
          <footer class="form-footer">
            <div>
              <div class="search-wrapper">
                <a href="javascript:;" 
                   class="icon-delete" v-if="search"
                   @click.prevent="clearSearch()"
                >
                </a>
                <input type="text" class="search" v-model="search" placeholder="Suche nach Name">
              </div>
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
      clients: [],
      search: '',
    };
  },

  created() {
    this.fetch();
  },

  methods: {
    fetch() {
      let uri = "/api/clients/get";
      this.axios.get(uri).then(response => {
        this.clients = response.data.data;
      });
    },

    destroy(id,event) {
      if (confirm("Bitte löschen bestätigen!")) {
        let uri = `/api/client/destroy/${id}`;
        let el = this.progress(event.target);
        this.axios.delete(uri).then(response => {
          this.fetch();
          this.$notify({ type: "success", text: "Record deleted" });
          this.progress(el);
        });
      }
    },

    clone(id,event) {
      let uri = `/api/client/clone/${id}`;
      let el = this.progress(event.target);
      this.axios.get(uri).then(response => {
        this.clients.unshift(response.data);
        this.$notify({ type: "success", text: "Record cloned" });
        this.progress(el);
      });
    },

    toggleStatus(id,event) {
      let uri = `/api/client/status/${id}`;
      let el = this.progress(event.target);
      this.axios.get(uri).then(response => {
        const index = this.clients.findIndex(x => x.id === id);
        this.clients[index].publish = response.data;
        this.$notify({ type: "success", text: "Status changed" });
        this.progress(el)
      });
    },
    
    clearSearch() {
      this.search = '';
    },
  },

  computed: {
    clientList() {
      return this.clients.filter(client => {
        let name = client.name, city = client.city;
        if (
          name.toLowerCase().includes(this.search.toLowerCase()) ||
          city.toLowerCase().includes(this.search.toLowerCase())
        ) {
          return client;
        }
      })
    }
  }
};
</script>