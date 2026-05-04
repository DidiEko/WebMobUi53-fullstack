<script setup>
import { ref, onMounted } from 'vue';
import { useFetchApi } from './composables/useFetchApi';

const props = defineProps({
  token: String,
});

const { fetchApi } = useFetchApi();

const poll = ref(null);

// Charger le sondage au chargement de la page
async function loadPoll() {
  try {
    poll.value = await fetchApi({
      url: `polls/${props.token}`,
    });
  } catch (err) {
    console.error(err);
  }
}

// Voter
async function vote(optionId) {
  try {
    await fetchApi({
      url: `polls/${props.token}/vote`,
      method: 'POST',
      data: {
        poll_option_id: optionId,
      },
    });

    alert('Vote enregistré !');
  } catch (err) {
    console.error(err);
  }
}

onMounted(loadPoll);
</script>

<template>
  <div class="p-6">
    <div v-if="poll">
      <h1 class="text-xl font-bold mb-4">{{ poll.question }}</h1>

      <div v-for="option in poll.options" :key="option.id" class="mb-2">
        <button
          class="w-full rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700"
          @click="vote(option.id)"
        >
          {{ option.label }}
        </button>
      </div>
    </div>

    <p v-else>Chargement...</p>
  </div>
</template>