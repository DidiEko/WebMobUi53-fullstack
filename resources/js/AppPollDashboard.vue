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

  // Paramètres du sondage.
  // is_draft permet de choisir si le sondage reste en brouillon ou démarre directement.
  is_draft: true,

  // allow_multiple_choices permet d'autoriser plusieurs réponses.
  allow_multiple_choices: false,

  // results_public permet de rendre les résultats visibles publiquement.
  results_public: true,

  // Durée en minutes saisie dans le formulaire.
  // Elle sera convertie en secondes avant l'envoi à Laravel.
  duration_minutes: '',
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
    const duration = newPoll.value.duration_minutes
      ? Number(newPoll.value.duration_minutes) * 60
      : null;

    const data = {
      title: newPoll.value.title,
      question: newPoll.value.question,

      // On envoie toutes les options du tableau dynamique.
      // trim() enlève les espaces inutiles au début et à la fin.
      options: newPoll.value.options.map(option => ({
        label: option.label.trim(),
      })),

      // Paramètres configurés depuis le formulaire.
      is_draft: newPoll.value.is_draft,
      allow_multiple_choices: newPoll.value.allow_multiple_choices,
      results_public: newPoll.value.results_public,
      duration,
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

    // On récupère aussi les paramètres existants du sondage.
    is_draft: poll.is_draft,
    allow_multiple_choices: poll.allow_multiple_choices,
    results_public: poll.results_public,
    duration_minutes: poll.duration ? poll.duration / 60 : '',
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
    is_draft: true,
    allow_multiple_choices: false,
    results_public: true,
    duration_minutes: '',
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
        <input v-model="newPoll.title" type="text" class="w-full rounded border px-3 py-2"
          placeholder="Exemple : Sondage de satisfaction" />
      </div>

      <div>
        <label class="block font-medium">Question</label>
        <input v-model="newPoll.question" type="text" class="w-full rounded border px-3 py-2"
          placeholder="Exemple : Quelle option préfères-tu ?" required />
      </div>

      <!-- Liste dynamique des options.
           v-for permet d'afficher autant de champs qu'il y a d'options dans le tableau. -->
      <div v-for="(option, index) in newPoll.options" :key="index">
        <label class="block font-medium">
          Option {{ index + 1 }}
        </label>

        <div class="flex gap-2">
          <input v-model="option.label" type="text" class="w-full rounded border px-3 py-2"
            :placeholder="`Exemple : Option ${index + 1}`" required />

          <button type="button" class="rounded bg-red-600 px-3 py-2 text-white hover:bg-red-700"
            @click="removeOption(index)">
            Supprimer
          </button>
        </div>
      </div>

      <!-- Bouton pour ajouter une option supplémentaire. -->
      <button type="button" class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700" @click="addOption">
        Ajouter une option
      </button>

      <!-- Paramètres du sondage. -->
      <section class="mt-6 rounded border border-slate-300 bg-white p-4 space-y-4 text-slate-900">
        <h2 class="font-semibold text-slate-900">
          Paramètres du sondage
        </h2>

        <label class="flex items-center gap-3 text-slate-900">
          <input v-model="newPoll.is_draft" type="checkbox" class="h-4 w-4" />
          <span>Créer en brouillon</span>
        </label>

        <label class="flex items-center gap-3 text-slate-900">
          <input v-model="newPoll.allow_multiple_choices" type="checkbox" class="h-4 w-4" />
          <span>Autoriser plusieurs réponses</span>
        </label>

        <label class="flex items-center gap-3 text-slate-900">
          <input v-model="newPoll.results_public" type="checkbox" class="h-4 w-4" />
          <span>Rendre les résultats publics</span>
        </label>

        <div>
          <label class="block font-medium text-slate-900">
            Durée du sondage en minutes
          </label>

          <input v-model="newPoll.duration_minutes" type="number" min="1"
            class="w-full rounded border px-3 py-2 text-slate-900" placeholder="Exemple : 60" />

          <p class="mt-1 text-sm text-slate-600">
            Laisse vide si le sondage n'a pas de durée limite.
          </p>
        </div>
      </section>

      <div>
        <button type="submit" class="rounded bg-teal-600 px-4 py-2 text-white hover:bg-teal-700">
          {{ editingPollId ? 'Modifier le sondage' : 'Créer le sondage' }}
        </button>

        <!-- Bouton affiché uniquement quand on est en mode modification. -->
        <button v-if="editingPollId" type="button"
          class="ml-2 rounded bg-gray-500 px-4 py-2 text-white hover:bg-gray-600" @click="cancelEdit">
          Annuler
        </button>
      </div>
    </form>

    <!-- Tableau des sondages récupérés depuis l'API. -->
    <PollTable :polls="getResult || []" @delete-poll="deletePoll" @edit-poll="editPoll" @start-poll="startPoll" />
  </main>
</template>