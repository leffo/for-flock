import axios from 'axios';

export const http = axios.create({
    headers: { Accept: 'application/json' },
});

export async function submitWithCsrf(method, url, data) {
    await http.get('/sanctum/csrf-cookie');

    return http.request({ method, url, data });
}

export function errorMessage(error) {
    const response = error.response;

    switch (response?.status) {
        case 401:
            return 'Войдите в аккаунт и повторите попытку.';
        case 419:
            return 'Сессия истекла. Повторите попытку.';
        case 422:
            return Object.values(response.data.errors ?? {}).flat()[0]
                ?? 'Проверьте введённые данные.';
        case 429:
            return 'Слишком много попыток. Попробуйте чуть позже.';
        default:
            return 'Не удалось выполнить запрос. Попробуйте ещё раз.';
    }
}
