import { defineStore } from 'pinia';
import { ref } from 'vue';
import { http, submitWithCsrf } from './http.js';

export const useAuthStore = defineStore('auth', () => {
    const user = ref(null);
    const sessionError = ref(false);
    let initialized = false;
    let sessionRequest;

    function loadSession() {
        sessionRequest ??= http.get('/api/v1/user')
            .then(({ data }) => {
                user.value = data.data;
                initialized = true;
            })
            .catch((error) => {
                if (error.response?.status !== 401) {
                    throw error;
                }

                user.value = null;
                initialized = true;
            })
            .finally(() => {
                sessionRequest = undefined;
            });

        return sessionRequest;
    }

    async function initialize() {
        if (initialized) {
            return;
        }

        sessionError.value = false;

        try {
            await loadSession();
        } catch {
            sessionError.value = true;
        }
    }

    async function authenticate(endpoint, credentials) {
        await submitWithCsrf('post', endpoint, credentials);
        await loadSession();

        if (!user.value) {
            throw new Error('The authenticated session could not be loaded.');
        }
    }

    async function logout() {
        try {
            await submitWithCsrf('post', '/logout');
        } catch (error) {
            if (error.response?.status !== 401) {
                throw error;
            }
        }

        user.value = null;
    }

    async function updateName(name) {
        const { data } = await submitWithCsrf('patch', `/api/v1/users/${user.value.id}`, { name });
        user.value = data.data;
    }

    return { user, sessionError, initialize, authenticate, logout, updateName };
});
