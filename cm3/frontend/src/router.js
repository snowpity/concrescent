import Home from './routes/Home.vue';
import AdminRoutes from './router-admin'

//Vue.use(Router);

export default [{
        path: '/',
        name: 'home',
        meta: {
            title: 'Main Menu',
        },
        component: Home,
    },
    {
        path: '/redirect',
        redirect: (to) => ({
            name: to.query.route,
            query: to.query,
        }),

    },
    {
        path: '/about',
        name: 'AppAbout',
        meta: {
            title: 'About ConCrescent',
        },
        component: () => import( /* webpackChunkName: "about" */ './routes/About.vue'),
    },
    {
        path: '/config',
        name: 'config',
        meta: {
            title: 'Configuration',
        },
        component: () => import( /* webpackChunkName: "about" */ './routes/Config.vue'),
    },
    {
        path: '/login',
        name: 'login',
        meta: {
            title: 'Login',
        },
        component: () => import( /* webpackChunkName: "login" */ './routes/Login.vue'),
    },
    {
        path: '/myBadges',
        name: 'myBadges',
        meta: {
            title: 'My Badges',
        },
        component: () => import( /* webpackChunkName: "login" */ './routes/register/mybadges.vue'),
    },
    {
        path: '/addbadge',
        name: 'addbadge',
        meta: {
            title: 'Add a badge',
        },
        component: () => import( /* webpackChunkName: "AddEditBadge" */ './routes/register/addeditbadge.vue'),
    },
    {
        path: '/editbadge/:cartIx?',
        name: 'editbadge',
        meta: {
            title: 'Edit badge',
        },
        component: () => import( /* webpackChunkName: "AddEditBadge" */ './routes/register/addeditbadge.vue'),
    },
    {
        path: '/cart',
        name: 'cart',
        meta: {
            title: 'View Cart',
        },
        component: () => import( /* webpackChunkName: "Cart" */ './routes/register/cart.vue'),
    },
    {
        path: '/checkout',
        name: 'checkout',
        meta: {
            title: 'Checkout',
        },
        component: () => import( /* webpackChunkName: "Cart" */ './routes/register/checkout.vue'),
    },
    {
        path: '/unsubscribe',
        name: 'unsubscribe',
        meta: {
            title: 'Unsubscribe',
        },
        component: () => import( /* webpackChunkName: "Cart" */ './routes/register/unsubscribe.vue'),
    },
    {
        path: '/account/profile',
        name: 'profile',
        meta: {
            title: 'My Profile',
        },
        component: () => import( /* webpackChunkName: "Cart" */ './routes/account/profile.vue'),
    },
    {
        path: '/account/settings',
        name: 'settings',
        meta: {
            title: 'My Admin Settings',
        },
        component: () => import( /* webpackChunkName: "admin-settings" */ './routes/account/settings.vue'),
    },
    {
        path: '/account/logout',
        name: 'logout',
        meta: {
            title: 'Logged out',
        },
        component: () => import( /* webpackChunkName: "Cart" */ './routes/account/logout.vue'),
    },
    {
        path: "/Admin",
        children: AdminRoutes,
        component: () => import('./routes/dummy.vue')
    }
];