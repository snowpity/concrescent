import Vue from 'vue';
import Home from './routes/Home.vue';


const result = [{
        path: 'Badge_Checkin',
        name: 'Badge_Checkin',
        meta: {
            title: 'Badge Checkin',
        },
        component: () => import( /* webpackChunkName: "admin_badge_checkin" */ './routes/admin/badge_checkin.vue'),
    },
    // {
    //   path: '/Locations',
    //   name: 'Locations',
    //   meta: {
    //     title: 'Venue Locations',
    //   },
    //   component: () => import(/* webpackChunkName: "login" */ './routes/admin/locations.vue'),
    // },
    {
        path: 'Attendee',
        name: 'Attendee',
        meta: {
            title: 'Attendee',
            subTabs: [
                'Badges',
                'Types',
                'Questions',
                'Promos',
                'Notifications'
            ]
        },
        component: () => import( /* webpackChunkName: "admin_attendee" */ './routes/admin/attendee.vue'),
    },
    {
        path: 'Application/:context_code',
        name: 'Application',
        meta: {
            title: 'Group Applications',
            subTabs: [
                'Badges',
                'Types',
                'Questions',
                'Departments',
                'Notifications'
            ]
        },
        component: () => import( /* webpackChunkName: "admin_group" */ './routes/admin/applications.vue'),
    },
    {
        path: 'Staff',
        name: 'Staff',
        meta: {
            title: 'Staff',
            subTabs: [
                'Badges',
                'Types',
                'Questions',
                'Departments',
                'Notifications'
            ]
        },
        component: () => import( /* webpackChunkName: "admin_staff" */ './routes/admin/staff.vue'),
    },
    {
        path: 'Users',
        name: 'Users',
        meta: {
            title: 'Users',
        },
        component: () => import( /* webpackChunkName: "admin_users" */ './routes/admin/users.vue'),
    },
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