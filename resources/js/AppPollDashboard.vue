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

// Données du formulaire de création.
// Les options sont maintenant un tableau dynamique.
// Cela permet d'ajouter ou de supprimer des options facilement.
const newPoll = ref({
  title: '',
  question: '',
  options: [
    { label: '' },
    { label: '' },
  ],
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

// Ajoute une nouvelle option vide dans le formulaire.
// Vue met automatiquement l'affichage à jour grâce à la réactivité.
function addOption() {
  newPoll.value.options.push({ label: '' });
}

// Supprime une option du formulaire.
// On garde toujours au minimum deux options, car un sondage doit proposer au moins deux choix.
function removeOption(index) {
  if (newPoll.value.options.length <= 2) {
    alert('Un sondage doit contenir au moins deux options.');
    return;
  }

  newPoll.value.options.splice(index, 1);
}

// Crée ou modifie un sondage à partir du formulaire.
async function createPoll() {
  try {
    const data = {
      title: newPoll.value.title,
      question: newPoll.value.question,

      // On envoie toutes les options du tableau dynamique.
      // trim() enlève les espaces inutiles au début et à la fin.
      options: newPoll.value.options.map(option => ({
        label: option.label.trim(),
      })),

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

    // On récupère toutes les options existantes du sondage.
    // Si aucune option n'est disponible, on garde deux champs vides.
    options: poll.options?.length
      ? poll.options.map(option => ({ label: option.label }))
      : [{ label: '' }, { label: '' }],
  };
}

// Annule la modification et remet le formulaire à zéro.
function cancelEdit() {
  editingPollId.value = null;

  newPoll.value = {
    title: '',
    question: '',
    options: [
      { label: '' },
      { label: '' },
    ],
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

// Démarre un sondage brouillon.
async function startPoll(poll) {
  const confirmed = confirm(`Démarrer le sondage "${poll.question}" ?`);

  if (!confirmed) {
    return;
  }

  try {
    await fetchApi({
      url: `polls/${poll.id}/start`,
      method: 'POST',
    });

    // On recharge la liste après démarrage.
    fetchNow();
  } catch (err) {
    handleError(err);
  }
}

watch(getError, err => handleError(err));

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

      <!-- Liste dynamique des options.
           v-for permet d'afficher autant de champs qu'il y a d'options dans le tableau. -->
      <div
        v-for="(option, index) in newPoll.options"
        :key="index"
      >
        <label class="block font-medium">
          Option {{ index + 1 }}
        </label>

        <div class="flex gap-2">
          <input
            v-model="option.label"
            type="text"
            class="w-full rounded border px-3 py-2"
            :placeholder="`Exemple : Option ${index + 1}`"
            required
          />

          <button
            type="button"
            class="rounded bg-red-600 px-3 py-2 text-white hover:bg-red-700"
            @click="removeOption(index)"
          >
            Supprimer
          </button>
        </div>
      </div>

      <!-- Bouton pour ajouter une option supplémentaire. -->
      <button
        type="button"
        class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700"
        @click="addOption"
      >
        Ajouter une option
      </button>

      <div>
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
      </div>
    </form>

    <!-- Tableau des sondages récupérés depuis l'API. -->
    <PollTable
      :polls="getResult || []"
      @delete-poll="deletePoll"
      @edit-poll="editPoll"
      @start-poll="startPoll"
    />
  </main>
</template>