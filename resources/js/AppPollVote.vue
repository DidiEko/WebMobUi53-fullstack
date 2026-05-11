<script setup>
import { computed, ref, onMounted } from 'vue';
import { useFetchApi } from './composables/useFetchApi';
import { usePolling } from './composables/usePolling';

const props = defineProps({
  token: { type: String, required: true },
  loginUrl: { type: String, default: null },
  csrfToken: { type: String, required: true },
});

const { fetchApi } = useFetchApi();

// Contient le sondage chargé depuis l'API.
const poll = ref(null);

// Contient l'option choisie par l'utilisateur.
const selectedOptionId = ref(null);

// Message affiché à l'utilisateur.
const message = ref('');

// Indique si l'utilisateur a déjà voté pendant cette session.
const hasVoted = ref(false);

/**
 * Calcule le nombre total de votes.
 *
 * On additionne les votes_count de toutes les options.
 * Cette valeur est automatiquement recalculée quand poll change.
 */
const totalVotes = computed(() => {
  if (!poll.value?.options) {
    return 0;
  }

  return poll.value.options.reduce((total, option) => {
    return total + option.votes_count;
  }, 0);
});

/**
 * Calcule le pourcentage d'une option.
 *
 * Si aucun vote n'existe encore, on retourne 0
 * pour éviter une division par zéro.
 */
function getPercentage(option) {
  if (totalVotes.value === 0) {
    return 0;
  }

  return Math.round((option.votes_count / totalVotes.value) * 100);
}

// Charge le sondage grâce au token présent dans l'URL.
async function loadPoll() {
  try {
    poll.value = await fetchApi({
      url: `polls/${props.token}`,
    });
  } catch (err) {
    console.error(err);
    message.value = 'Impossible de charger ce sondage.';
  }
}

// Envoie le vote à l'API.
async function submitVote() {
  if (!selectedOptionId.value) {
    message.value = 'Veuillez choisir une option.';
    return;
  }

  try {
    const response = await fetch(`/api/v1/polls/${props.token}/vote`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': props.csrfToken,
      },
      body: JSON.stringify({
        poll_option_id: selectedOptionId.value,
      }),
    });

    const data = await response.json();

    if (!response.ok) {
      throw data;
    }

    message.value = 'Votre vote a bien été enregistré.';
    hasVoted.value = true;

    // On recharge le sondage pour mettre à jour les résultats.
    loadPoll();
  } catch (err) {
    console.error(err);
    message.value = err?.message || 'Erreur lors du vote.';
  }
}

onMounted(loadPoll);

// Recharge les résultats régulièrement.
// Cela permet de voir les votes évoluer sans recharger la page.
usePolling(loadPoll);
</script>

<template>
  <main class="min-h-screen p-6">
    <p v-if="message" class="mb-4 rounded bg-gray-100 p-3">
      {{ message }}
    </p>

    <section v-if="poll">
      <h1 class="mb-2 text-2xl font-bold">
        {{ poll.title || 'Sondage' }}
      </h1>

      <h2 class="mb-6 text-xl">
        {{ poll.question }}
      </h2>

      <!-- Si le sondage est encore brouillon, on bloque le vote. -->
      <p v-if="poll.is_draft">
        Ce sondage n'est pas encore actif.
      </p>

      <form v-else class="space-y-4" @submit.prevent="submitVote">
        <label
          v-for="option in poll.options"
          :key="option.id"
          class="block rounded border p-3"
        >
          <input
            v-model="selectedOptionId"
            type="radio"
            :value="option.id"
            class="mr-2"
          />
          {{ option.label }}
        </label>

        <button
          type="submit"
          class="rounded bg-teal-600 px-4 py-2 text-white hover:bg-teal-700"
        >
          Voter
        </button>
      </form>

      <!-- Résultats du sondage.
           Ils sont visibles après le vote ou si les résultats sont publics. -->
      <section
        v-if="hasVoted || poll.results_public"
        class="mt-8 rounded border p-4"
      >
        <h3 class="mb-4 text-lg font-bold">
          Résultats
        </h3>

        <p class="mb-4 text-sm text-gray-600">
          Total des votes : {{ totalVotes }}
        </p>

        <div
          v-for="option in poll.options"
          :key="option.id"
          class="mb-4"
        >
          <div class="mb-1 flex justify-between">
            <span>{{ option.label }}</span>
            <span>
              {{ option.votes_count }} vote(s) — {{ getPercentage(option) }}%
            </span>
          </div>

          <!-- Barre visuelle simple, sans bibliothèque externe. -->
          <div class="h-3 rounded bg-gray-200">
            <div
              class="h-3 rounded bg-teal-600"
              :style="{ width: getPercentage(option) + '%' }"
            ></div>
          </div>
        </div>
      </section>
    </section>

    <p v-else>
      Chargement...
    </p>
  </main>
</template>