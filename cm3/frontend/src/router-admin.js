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
        },
        component: () => import( /* webpackChunkName: "admin_attendee" */ './routes/admin/attendee.vue'),
    },
    {
        path: 'Application/:context_code',
        name: 'Application',
        meta: {
            title: 'Group Applications',
        },
        component: () => import( /* webpackChunkName: "admin_group" */ './routes/admin/applications.vue'),
    },
    {
        path: 'Staff',
        name: 'Staff',
        meta: {
            title: 'Staff',
        },
        component: () => import( /* webpackChunkName: "admin_staff" */ './routes/admin/staff.vue'),
    },
    {
        path: 'OrgChart',
        name: 'OrgChart',
        meta: {
            title: 'Organization Chart',
        },
        component: () => import( /* webpackChunkName: "admin_orgchart" */ './routes/admin/orgchart.vue'),
    },
    {
        path: 'Users',
        name: 'Users',
        meta: {
            title: 'Users',
        },
        component: () => import( /* webpackChunkName: "admin_users" */ './routes/admin/users.vue'),
    },
    {
        path: 'System',
        name: 'System',
        meta: {
            title: 'System',
        },
        component: () => import( /* webpackChunkName: "admin_system" */ './routes/admin/system.vue'),
    },
    {
        path: 'Printing',
        name: 'Printing',
        meta: {
            title: 'Badge Printing',
        },
        component: () => import( /* webpackChunkName: "badgeprinting" */ './routes/admin/badgeprinting.vue'),
    },
];
export default result;