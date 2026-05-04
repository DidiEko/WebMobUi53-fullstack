<script setup>
import { ref, onMounted } from 'vue';
import { useFetchApi } from './composables/useFetchApi';

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
// Envoie le vote à l'API.
async function submitVote() {
    if (!selectedOptionId.value) {
        message.value = 'Veuillez choisir une option.';
        return;
    }

    try {
        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content');

        const response = await fetch(`/api/v1/polls/${props.token}/vote`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
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
    } catch (err) {
        console.error(err);
        message.value = err?.message || 'Erreur lors du vote.';
    }
}

onMounted(loadPoll);
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
                <label v-for="option in poll.options" :key="option.id" class="block rounded border p-3">
                    <input v-model="selectedOptionId" type="radio" :value="option.id" class="mr-2" />
                    {{ option.label }}
                </label>

                <button type="submit" class="rounded bg-teal-600 px-4 py-2 text-white hover:bg-teal-700">
                    Voter
                </button>
            </form>
        </section>

        <p v-else>
            Chargement...
        </p>
    </main>
</template>