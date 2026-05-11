import { createApp } from 'vue';
import AppPollVote from './AppPollVote.vue';
import { setDefaultBaseUrl } from './composables/useFetchApi';

// Toutes les requêtes API partiront de /api/v1.
setDefaultBaseUrl('/api/v1');

const el = document.getElementById('app');

// On récupère les props envoyées par Blade.
const props = JSON.parse(el.dataset.props || '{}');

createApp(AppPollVote, props).mount(el);