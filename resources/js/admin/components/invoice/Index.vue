<template>
  <div>
    <page-header/>
    <notifications classes="notification"/>
    <div v-if="showStatusForm">
      <status-form :record="invoice"></status-form>
    </div>
    <div :class="[hasForm ? 'is-hidden' : '', 'container']">
      <main class="content" role="main">
        <div>
          <h1>Invoices</h1>
          <router-link :to="{ name: 'invoice-create' }" class="btn-add">
            <span>Add</span>
          </router-link>
          <div class="list-items" v-if="invoices.length">
            <div
              class="list-item"
              v-for="invoice in invoiceList"
              :key="invoice.id"
              data-icons="4"
            >
              <div class="list-item-body">
                <div>
                  <span :class="['badge-status is-' + invoice.status]">{{ invoice.status }}</span>
                  {{ invoice.number }} &bull; <span v-if="invoice.client"> <strong>{{ invoice.client.acronym }}</strong></span> &bull; {{ invoice.title }}
                </div>
              </div>
              <div class="list-item-action" data-icons="4">
                <router-link
                  :to="{name: 'invoice-download', params: { id: invoice.id }}"
                  class="icon-document icon-mini"
                  target="_blank"
                ></router-link>
                <div v-if="invoice.status == 'open'">
                  <router-link
                    :to="{name: 'invoice-edit', params: { id: invoice.id }}"
                    class="icon-edit icon-mini"
                  ></router-link>
                </div>
                <div v-else>
                  <span class="icon-edit icon-mini is-disabled"></span>
                </div>
                <a
                  href="javascript:;"
                  class="icon-copy icon-mini"
                  @click.prevent="clone(invoice.id,$event)"
                ></a>
                <div v-if="invoice.status == 'open'">
                  <a
                    href="javascript:;"
                    class="icon-trash icon-mini"
                    @click.prevent="destroy(invoice.id,$event)"
                  ></a>
                </div>
                <div v-else>
                  <span class="icon-trash icon-mini is-disabled"></span>
                </div>
              </div>
            </div>
            <div class="grid-invoices-total">
              <div>Total</div>
              <div class="align-right">{{ total | formatCurrency }}</div>
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

// Views
import PageHeader from "@/layout/PageHeader.vue";
import StatusForm from "@/components/invoice/status/form.vue";

// Helpers
import Progress from "@/mixins/progress";

export default {
  components: {
    PageHeader: PageHeader,
  },

  mixins: [Progress],

  data() {
    return {
      invoices: [],
      search: '',
      invoice: null,
      showStatusForm: false,
      hasForm: false,

    };
  },

  created() {
    this.fetch();
  },

  methods: {

    fetch() {
      let uri = "/api/invoices/get";
      this.axios.get(uri).then(response => {
        this.invoices = response.data.data;
      });
    },

    reload() {
      this.fetch();
    },

    destroy(id,event) {
      if (confirm("Bitte löschen bestätigen!")) {
        let uri = `/api/invoice/destroy/${id}`;
        let el = this.progress(event.target);
        this.axios.delete(uri).then(response => {
          this.fetch();
          this.$notify({ type: "success", text: "Record deleted" });
          this.progress(el);
        });
      }
    },

    clone(id,event) {
      let uri = `/api/invoice/clone/${id}`;
      let el = this.progress(event.target);
      this.axios.get(uri).then(response => {
        this.$notify({ type: "success", text: "Record cloned" });
        this.progress(el);
        this.fetch();
      });
    },
    
    clearSearch() {
      this.search = '';
    },

    toggleStatusForm() {
      this.showStatusForm = this.showStatusForm ? false : true;
      this.hasForm = this.hasForm ? false : true;
    }
  },

  computed: {
    
    invoiceList() {
      return this.invoices.filter(invoice => {
        let title = invoice.title;
        if (
          title.toLowerCase().includes(this.search.toLowerCase())
        ) {
          return invoice;
        }
      })
    },

    total() {
     return _.sumBy(this.invoices, function(o) { return parseFloat(o.total); });
    },


  }
};
</script>