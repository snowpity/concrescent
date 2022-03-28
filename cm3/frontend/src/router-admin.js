import Vue from 'vue';
import Home from './routes/Home.vue';


const result = [
  {
    path: 'Badge_Checkin',
    name: 'Badge_Checkin',
    meta: {
      title: 'Badge Checkin',
    },
    component: () => import(/* webpackChunkName: "admin_badge_checkin" */ './routes/admin/badge_checkin.vue'),
  },
  // {
  //   path: '/Locations',
  //   name: 'Locations',
  //   meta: {
  //     title: 'Venue Locations',
  //   },
  //   component: () => import(/* webpackChunkName: "login" */ './routes/admin/locations.vue'),
  // },
  // {
  //   path: '/Attendee',
  //   name: 'Attendee',
  //   meta: {
  //     title: 'Attendee',
  //   },
  //   component: () => import(/* webpackChunkName: "login" */ './routes/admin/attendee.vue'),
  // },
  // {
  //   path: '/Group/:context_code',
  //   name: 'Attendee',
  //   meta: {
  //     title: 'Group',
  //   },
  //   component: () => import(/* webpackChunkName: "login" */ './routes/admin/group.vue'),
  // },
  // {
  //   path: '/Staff',
  //   name: 'Staff',
  //   meta: {
  //     title: 'Staff',
  //   },
  //   component: () => import(/* webpackChunkName: "login" */ './routes/admin/staff.vue'),
  // },
  // {
  //   path: '/Printing',
  //   name: 'Printing',
  //   meta: {
  //     title: 'Printing',
  //   },
  //   component: () => import(/* webpackChunkName: "login" */ './routes/admin/printing.vue'),
  // },
  ];
export default result;
