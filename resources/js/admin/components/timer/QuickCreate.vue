<template>
  <div>
    <div class="quick-form">
      <div>
        <div>
          <a href @click.prevent="toggleForm()" class="icon-close-quick"></a>
          <h1>Add time entry</h1>
          <form @submit.prevent="submit">
            <div>
              <label>What?</label>
              <div class="form-row is-sm" :class="errors.task ? 'has-error': ''">
                <input
                  type="text"
                  @focus="removeError('task')"
                  name="name"
                  ref="name"
                  v-model="time.task"
                >
                <div class="autocomplete">
                    <ul>
                      <li><a href=""><span>Redesign Hypno</span> &bull; Studio am Meer</a></li>
                      <li><a href=""><span>Sammelauftrag</span> «www.swissport.com» &bull; Swissport</a></li>
                      <li><a href=""><span>Redesign «wbg.ch»</span> &bull; WBG AG</a></li>
                    </ul>
                </div>
              </div>
              <div class="form-row is-sm">
                <label>For whom?</label>
                <div class="select-wrapper is-wide">
                  <select v-model="time.project_id" name="client_id">
                    <option :value="null">Select project...</option>
                    <option
                      v-for="record in projects"
                      :key="record.id"
                      :value="record.id"
                    >{{ record.name }} &bull; {{ record.client.name }}</option>
                  </select>
                </div>
              </div>
              <div class="form-row is-sm">
                <label>When?</label>
                <div class="grid-timer">
                  <div>
                    <the-mask
                      type="text"
                      mask="##.##.####"
                      :masked="true"
                      class="is-datetime is-datepicker"
                      name="date"
                      placeholder="Today, 8.11.2019"
                      v-model="time.date"
                    ></the-mask>
                  </div>
                  <div></div>
                  <div>
                    <the-mask
                      type="text"
                      mask="##.##"
                      :masked="true"
                      class="is-datetime"
                      name="timeStart"
                      placeholder="09.15"
                      v-model="time.timeStart"
                    ></the-mask>
                  </div>
                  <div class="is-separator">
                    <svg
                      xmlns="http://www.w3.org/2000/svg"
                      width="24"
                      height="24"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="#e0e0e0"
                      stroke-width="2"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      class="feather feather-arrow-right"
                    >
                      <line x1="5" y1="12" x2="19" y2="12"></line>
                      <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                  </div>
                  <div>
                    <the-mask
                      type="text"
                      mask="##.##"
                      :masked="true"
                      class="is-datetime"
                      name="timeEnd"
                      placeholder="10.45"
                      v-model="time.timeEnd"
                    ></the-mask>
                  </div>
                </div>
              </div>
            </div>
            <div>
              <button type="submit" class="btn-secondary">Save</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
import Utils from "@/mixins/utils";
import Date from "@/mixins/date";
import Quick from "@/mixins/quick";
import { TheMask } from "vue-the-mask";

export default {
  components: {
    TheMask: TheMask
  },

  props: {
    type: String
  },

  mixins: [Utils, Quick, Date],

  data() {
    return {
      errors: {
        task: false
      },

      time: {
        task: null,
        project_id: null,
        is_billable: 1,
        date: null,
        timeStart: null,
        timeEnd: null,
        minutes: null
      },

      projects: null
    };
  },

  created() {
    // remove event listener
    window.removeEventListener("keypress", this.command);

    // set default date
    this.date = this.dateToday();

    // get projects
    this.axios.get(`/api/projects/get`).then(response => {
      this.projects = response.data.data;
      console.log(this.projects);
    });
  },

  methods: {
    validate() {
      if (!this.time.task) {
        this.errors.task = true;
        return false;
      }
      return true;
    },

    submit() {
      if (!this.validate()) {
        this.validationError();
        return false;
      }
      this.store();
    },

    store() {
      this.axios.post(`/api/time/create`, this.time).then(response => {
        this.$router.push({ name: "timer" });
        this.$parent.reload();
      });
    },

    toggleForm() {
      this.$parent.toggleForm();
    }
  }
};
</script>