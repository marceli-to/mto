<template>
  <div>
    <page-header/>
    <notifications classes="notification"/>
    <quick-create v-bind:is="componentName"></quick-create>
    <div :class="[hasForm ? 'is-hidden' : '', 'container']">
      <main class="content" role="main">
        <div>
          <h1>Time entries</h1>
          <router-link :to="{ name: 'time-create' }" class="btn-add">
            <span>Add</span>
          </router-link>
          <div class="list-items" v-if="times">
            <div class="time-items" v-for="(day, index) in times" :key="day.key">

              <div class="time-summary">
                <h2 class="time-day">{{day.date | moment('D. MMMM YYYY')}}</h2>
                <div class="time-total">{{day.total}} h</div>
              </div>
              <div
                class="list-item time-item"
                v-for="time in day.entries"
                :key="time.id"
                data-icons="2"
              >
                <div class="list-item-body">
                  <div class="time-entry">
                    <div class="time-entry__duration">
                      <edit-time :date="time.date" :start="time.timeStart" :end="time.timeEnd" :minutes="time.minutes"></edit-time>
                    </div>
                    <div class="time-entry__description">
                      <strong>{{ time.task }}</strong> &bull; <span v-if="time.project">{{ time.project.name}}, {{ time.project.client.name}}</span>
                    </div>
                  </div>
                </div>
                <div class="list-item-action" data-icons="2">
                  <router-link
                    :to="{name: 'time-edit', params: { id: time.id }}"
                    class="icon-edit icon-mini"
                  ></router-link>
                  <a
                    href="javascript:;"
                    class="icon-trash icon-mini"
                    @click.prevent="destroy(time.id,$event)"
                  ></a>
                </div>
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
                <input type="text" class="search" v-model="search" placeholder="Suche...">
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
import QuickCreate from "@/components/timer/QuickCreate.vue";

// Partials
import EditTime from "@/components/timer/partials/editTime.vue";

// Helpers
import Progress from "@/mixins/progress";
import Quick from "@/mixins/quick";

export default {
  components: {
    PageHeader: PageHeader,
    QuickCreate: QuickCreate,
    EditTime: EditTime,
  },

  mixins: [Progress, Quick],

  data() {
    return {
      times: [],
      search: '',
      componentName: '',
      hasForm: false,
    };
  },

  created() {
    this.fetch();
  	window.addEventListener('keypress', this.command);
  },
  
  destroyed() {
    window.removeEventListener('keypress', this.command);
  },

  methods: {

    command(e) {
      if (e.keyCode == 43) {
        this.toggleForm('QuickCreate');
      }
      if (e.keyCode == 45) {
        this.toggleForm(null);
      }
    },

    fetch() {
      let uri = "";
      this.axios.get(`/api/times/get/byDay`).then(response => {
        this.times = response.data;
      });
    },

    reload() {
      this.toggleForm();
      this.fetch();
    },

    destroy(id,event) {
      if (confirm("Bitte löschen bestätigen!")) {
        let uri = `/api/time/destroy/${id}`;
        let el = this.progress(event.target);
        this.axios.delete(uri).then(response => {
          this.fetch();
          this.$notify({ type: "success", text: "Record deleted" });
          this.progress(el);
        });
      }
    },
    
    clearSearch() {
      this.search = '';
    },
  
    dayTotal(day) {
      console.log(day)
    }
  },

  computed: {
    timeList() {
      return this.times.filter(time => {
        let task = time.task;
        if (
          task.toLowerCase().includes(this.search.toLowerCase())
        ) {
          return time;
        }
      })
    },
  }
};
</script>