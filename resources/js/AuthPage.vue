<script setup>
import { reactive, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { useAuthStore } from './auth.js';
import { errorMessage, submitWithCsrf } from './http.js';

const { mode } = defineProps({
    mode: { type: String, required: true },
});

const auth = useAuthStore();
const route = useRoute();
const router = useRouter();

const pages = {
    login: { title: 'Вход в аккаунт', action: 'Войти' },
    register: { title: 'Создание аккаунта', action: 'Зарегистрироваться' },
    'forgot-password': { title: 'Восстановление пароля', action: 'Отправить ссылку' },
    'reset-password': { title: 'Новый пароль', action: 'Сохранить пароль' },
};

const page = pages[mode];
const hasPassword = mode !== 'forgot-password';
const confirmsPassword = mode === 'register' || mode === 'reset-password';
const form = reactive({
    name: '',
    email: typeof route.query.email === 'string' ? route.query.email : '',
    password: '',
    password_confirmation: '',
    remember: false,
});
const error = ref('');
const status = ref('');
const busy = ref(false);

async function submit() {
    busy.value = true;
    error.value = '';
    status.value = '';

    try {
        const { name, email, password, password_confirmation, remember } = form;

        switch (mode) {
            case 'login':
                await auth.authenticate('/login', { email, password, remember });
                await router.replace({ name: 'dashboard' });
                break;
            case 'register':
                await auth.authenticate('/register', { name, email, password, password_confirmation });
                await router.replace({ name: 'dashboard' });
                break;
            case 'forgot-password':
                await submitWithCsrf('post', '/forgot-password', { email });
                status.value = 'Ссылка отправлена. Проверьте почту.';
                break;
            case 'reset-password':
                await submitWithCsrf('post', '/reset-password', {
                    email, password, password_confirmation, token: route.params.token,
                });
                await router.replace({ name: 'login', query: { reset: '1' } });
                break;
        }
    } catch (requestError) {
        error.value = errorMessage(requestError);
    } finally {
        busy.value = false;
    }
}
</script>

<template>
    <section class="panel mx-auto w-full max-w-md" aria-labelledby="auth-title">
        <h1 id="auth-title" class="text-3xl font-semibold tracking-tight">{{ page.title }}</h1>

        <p v-if="mode === 'forgot-password'" class="text-sm text-stone-600 dark:text-stone-400">
            Укажите почту аккаунта, чтобы получить ссылку для сброса пароля.
        </p>
        <p v-if="mode === 'login' && route.query.reset === '1'" class="notice" role="status">
            Пароль обновлён. Войдите с новым паролем.
        </p>

        <form class="grid gap-4" @submit.prevent="submit">
            <label v-if="mode === 'register'" class="field">
                Имя
                <input v-model="form.name" class="input" type="text" autocomplete="name" maxlength="255" required>
            </label>

            <label class="field">
                Электронная почта
                <input v-model="form.email" class="input" type="email" autocomplete="username" maxlength="255" required>
            </label>

            <label v-if="hasPassword" class="field">
                Пароль
                <input
                    v-model="form.password"
                    class="input"
                    type="password"
                    :autocomplete="confirmsPassword ? 'new-password' : 'current-password'"
                    :minlength="confirmsPassword ? 8 : undefined"
                    required
                >
            </label>

            <label v-if="confirmsPassword" class="field">
                Повторите пароль
                <input v-model="form.password_confirmation" class="input" type="password" autocomplete="new-password" required>
            </label>

            <div v-if="mode === 'login'" class="flex flex-wrap items-center justify-between gap-3 text-sm">
                <label class="flex items-center gap-2">
                    <input v-model="form.remember" class="accent-teal-700" type="checkbox">
                    Запомнить меня
                </label>
                <RouterLink class="text-link" :to="{ name: 'forgot-password' }">Забыли пароль?</RouterLink>
            </div>

            <p v-if="error" class="notice-error" role="alert">{{ error }}</p>
            <p v-if="status" class="notice" role="status">{{ status }}</p>

            <button class="primary-button" type="submit" :disabled="busy">
                {{ busy ? 'Подождите…' : page.action }}
            </button>
        </form>

        <p class="text-sm text-stone-600 dark:text-stone-400">
            <template v-if="mode === 'login'">
                Нет аккаунта?
                <RouterLink class="text-link" :to="{ name: 'register' }">Зарегистрироваться</RouterLink>
            </template>
            <RouterLink v-else class="text-link" :to="{ name: 'login' }">Вернуться ко входу</RouterLink>
        </p>
    </section>
</template>
