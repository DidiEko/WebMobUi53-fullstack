<script setup>
import { ref, watch } from 'vue';
import PollTable from './components/PollTable.vue';
import { useFetchApi } from './composables/useFetchApi';
import { usePolling } from './composables/usePolling';

const props = defineProps({
  polls: { type: Array, default: () => [] },
  loginUrl: { type: String, default: null },
});

const { fetchApiToRef, fetchApi } = useFetchApi();

const { data: getResult, error: getError, fetchNow } = fetchApiToRef({ url: 'polls/' });
const { data: postResult, error: postError } = fetchApiToRef({ url: '/foo', data: { id: 1 } });

// Données du formulaire de création.
const newPoll = ref({
  title: '',
  question: '',
  option1: '',
  option2: '',
});

// Contient l'id du sondage actuellement en cours de modification.
// Si la valeur est null, le formulaire sert à créer un nouveau sondage.
const editingPollId = ref(null);

function handleError(err) {
  if (!err) return;

  if (err?.status === 401) {
    window.location.href = props.loginUrl;
  } else {
    console.error(err);
  }
}

// Crée ou modifie un sondage à partir du formulaire.
async function createPoll() {
  try {
    const data = {
      title: newPoll.value.title,
      question: newPoll.value.question,
      options: [
        { label: newPoll.value.option1 },
        { label: newPoll.value.option2 },
      ],
      is_draft: true,
      allow_multiple_choices: false,
      allow_vote_change: false,
      results_public: true,
      duration: null,
    };

    if (editingPollId.value) {
      // Si editingPollId contient un id, on modifie un sondage existant.
      await fetchApi({
        url: `polls/${editingPollId.value}`,
        method: 'PUT',
        data,
      });
    } else {
      // Sinon, on crée un nouveau sondage.
      await fetchApi({
        url: 'polls/',
        method: 'POST',
        data,
      });
    }

    // On vide le formulaire et on quitte le mode modification si besoin.
    cancelEdit();

    // On recharge la liste des sondages.
    fetchNow();
  } catch (err) {
    handleError(err);
  }
}

// Remplit le formulaire avec les données du sondage choisi.
function editPoll(poll) {
  editingPollId.value = poll.id;

  newPoll.value = {
    title: poll.title || '',
    question: poll.question || '',
    option1: poll.options?.[0]?.label || '',
    option2: poll.options?.[1]?.label || '',
  };
}

// Annule la modification et remet le formulaire à zéro.
function cancelEdit() {
  editingPollId.value = null;

  newPoll.value = {
    title: '',
    question: '',
    option1: '',
    option2: '',
  };
}

// Supprime un sondage après confirmation.
async function deletePoll(poll) {
  const confirmed = confirm(`Supprimer le sondage "${poll.question}" ?`);

  if (!confirmed) {
    return;
  }

  try {
    await fetchApi({
      url: `polls/${poll.id}`,
      method: 'DELETE',
    });

    // On recharge la liste après suppression.
    fetchNow();
  } catch (err) {
    handleError(err);
  }
}

watch(getError, err => handleError(err));
watch(postError, handleError);

usePolling(fetchNow);
</script>

<template>
  <main class="min-h-screen p-6">
    <h1 class="mb-4 text-xl font-semibold">Mes sondages</h1>

    <!-- Formulaire simple de création ou de modification d'un sondage. -->
    <form class="mb-6 space-y-3" @submit.prevent="createPoll">
      <div>
        <label class="block font-medium">Titre</label>
        <input
          v-model="newPoll.title"
          type="text"
          class="w-full rounded border px-3 py-2"
          placeholder="Exemple : Sondage de satisfaction"
        />
      </div>

      <div>
        <label class="block font-medium">Question</label>
        <input
          v-model="newPoll.question"
          type="text"
          class="w-full rounded border px-3 py-2"
          placeholder="Exemple : Quelle option préfères-tu ?"
          required
        />
      </div>

      <div>
        <label class="block font-medium">Option 1</label>
        <input
          v-model="newPoll.option1"
          type="text"
          class="w-full rounded border px-3 py-2"
          placeholder="Exemple : Option A"
          required
        />
      </div>

      <div>
        <label class="block font-medium">Option 2</label>
        <input
          v-model="newPoll.option2"
          type="text"
          class="w-full rounded border px-3 py-2"
          placeholder="Exemple : Option B"
          required
        />
      </div>

      <button
        type="submit"
        class="rounded bg-teal-600 px-4 py-2 text-white hover:bg-teal-700"
      >
        {{ editingPollId ? 'Modifier le sondage' : 'Créer le sondage' }}
      </button>

      <!-- Bouton affiché uniquement quand on est en mode modification. -->
      <button
        v-if="editingPollId"
        type="button"
        class="ml-2 rounded bg-gray-500 px-4 py-2 text-white hover:bg-gray-600"
        @click="cancelEdit"
      >
        Annuler
      </button>
    </form>

    <!-- Tableau des sondages récupérés depuis l'API. -->
    <PollTable
      :polls="getResult || []"
      @delete-poll="deletePoll"
      @edit-poll="editPoll"
    />

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