<script setup>
import { watch } from 'vue';
import PollTable from './components/PollTable.vue';
import { useFetchApi } from './composables/useFetchApi';
import { usePolling } from './composables/usePolling';

const props = defineProps({
  polls: { type: Array, default: () => [] },
  loginUrl: { type: String, default: null },
});

const { fetchApiToRef } = useFetchApi();

const { data: getResult, error: getError, fetchNow } = fetchApiToRef({ url: 'polls/' });
const { data: postResult, error: postError } = fetchApiToRef({ url: '/foo', data: { id: 1 } });

// ⭐️ Fonction temporaire pour tester la création d'un sondage.
// Elle sera remplacée plus tard par un vrai formulaire Vue.
const { fetchApi } = useFetchApi();

async function createTestPoll() {
  try {
    await fetchApi({
      url: 'polls/',
      method: 'POST',
      data: {
        title: 'Sondage de test',
        question: 'Quelle option préfères-tu ?',
        options: [
          { label: 'Option A' },
          { label: 'Option B' },
        ],
        is_draft: true,
        allow_multiple_choices: false,
        allow_vote_change: false,
        results_public: true,
        duration: null,
      },
    });

    fetchNow();
  } catch (err) {
    handleError(err);
  }
}

function handleError(err) {
  if (!err) return;
  if (err?.status === 401) {
    window.location.href = props.loginUrl;
  } else {
    console.error(err);
  }
}

watch(getError, err => handleError(err));
watch(postError, handleError);

usePolling(fetchNow);
</script>

<template>
  <main class="min-h-screen p-6">
    <h1 class="mb-4 text-xl font-semibold">Mes sondages</h1>

    <!-- ⭐️ Bouton temporaire pour vérifier que la création API fonctionne.
         Plus tard, on le remplacera par un vrai formulaire de création. -->
    <button type="button" class="mb-4 rounded bg-teal-600 px-4 py-2 text-white hover:bg-teal-700"
      @click="createTestPoll">
      Créer un sondage de test
    </button>

    <PollTable :polls="props.polls" />

    <section class="mt-6">
      <h2>GET /api/v1/polls</h2>
      <pre v-if="getResult">{{ getResult }}</pre>
      <p v-else>Chargement...</p>
    </section>

    <section class="mt-4">
      <h2>POST /api/v1/foo</h2>
      <pre v-if="postResult">{{ postResult }}</pre>
      <p v-else>Chargement...</p>
    </section>
  </main>
</template>
