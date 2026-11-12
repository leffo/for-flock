import { createRouter, createWebHistory } from 'vue-router';
import AuthPage from './AuthPage.vue';
import DashboardPage from './DashboardPage.vue';
import { useAuthStore } from './auth.js';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/', redirect: { name: 'dashboard' } },
        {
            path: '/login',
            name: 'login',
            component: AuthPage,
            props: { mode: 'login' },
            meta: { guest: true },
        },
        {
            path: '/register',
            name: 'register',
            component: AuthPage,
            props: { mode: 'register' },
            meta: { guest: true },
        },
        {
            path: '/forgot-password',
            name: 'forgot-password',
            component: AuthPage,
            props: { mode: 'forgot-password' },
            meta: { guest: true },
        },
        {
            path: '/reset-password/:token',
            name: 'reset-password',
            component: AuthPage,
            props: { mode: 'reset-password' },
            meta: { guest: true },
        },
        {
            path: '/dashboard',
            name: 'dashboard',
            component: DashboardPage,
            meta: { requiresAuth: true },
        },
    ],
});

router.beforeEach(async (to) => {
    const auth = useAuthStore();

    await auth.initialize();

    if (auth.sessionError) {
        return;
    }

    if (to.meta.requiresAuth && !auth.user) {
        return { name: 'login' };
    }

    if (to.meta.guest && auth.user) {
        return { name: 'dashboard' };
    }
});

export default router;
