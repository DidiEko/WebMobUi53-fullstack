<script setup>
defineProps({
  polls: { type: Array, default: () => [] },
});

// Le composant enfant ne supprime pas directement.
// Il envoie un événement au composant parent.
// On ajoute aussi edit-poll pour prévenir le parent qu'on veut modifier un sondage.
// On ajoute start-poll pour prévenir le parent qu'on veut démarrer un sondage.
const emit = defineEmits(['delete-poll', 'edit-poll', 'start-poll']);
</script>

<template>
  <p v-if="polls.length === 0">Aucun sondage.</p>

  <table v-else class="w-full border-collapse text-left">
    <thead>
      <tr>
        <th class="border px-3 py-2">ID</th>
        <th class="border px-3 py-2">Titre</th>
        <th class="border px-3 py-2">Question</th>
        <th class="border px-3 py-2">Brouillon</th>
        <th class="border px-3 py-2">Debut</th>
        <th class="border px-3 py-2">Fin</th>
        <th class="border px-3 py-2">Lien</th>
        <th class="border px-3 py-2">Actions</th>
      </tr>
    </thead>

    <tbody>
      <tr v-for="poll in polls" :key="poll.id">
        <td class="border px-3 py-2">{{ poll.id }}</td>
        <td class="border px-3 py-2">{{ poll.title || '-' }}</td>
        <td class="border px-3 py-2">{{ poll.question }}</td>
        <td class="border px-3 py-2">{{ poll.is_draft ? 'Oui' : 'Non' }}</td>
        <td class="border px-3 py-2">{{ poll.started_at || '-' }}</td>
        <td class="border px-3 py-2">{{ poll.ends_at || '-' }}</td>

        <td class="border px-3 py-2">
          <!-- Lien de partage basé sur le token secret du sondage. -->
          <a
            v-if="poll.secret_token"
            class="text-blue-600 underline"
            :href="`/polls/${poll.secret_token}`"
            target="_blank"
          >
            Ouvrir
          </a>
        </td>

        <td class="border px-3 py-2">
          <button
            v-if="poll.is_draft"
            type="button"
            class="mr-2 rounded bg-green-600 px-3 py-1 text-white hover:bg-green-700"
            @click="emit('start-poll', poll)"
          >
            Démarrer
          </button>

          <button
            v-if="poll.is_draft"
            type="button"
            class="mr-2 rounded bg-blue-600 px-3 py-1 text-white hover:bg-blue-700"
            @click="emit('edit-poll', poll)"
          >
            Modifier
          </button>

          <button
            type="button"
            class="rounded bg-red-600 px-3 py-1 text-white hover:bg-red-700"
            @click="emit('delete-poll', poll)"
          >
            Supprimer
          </button>
        </td>
      </tr>
    </tbody>
  </table>
</template>