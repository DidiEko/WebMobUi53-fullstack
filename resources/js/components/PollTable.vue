<script setup>
defineProps({
  polls: { type: Array, default: () => [] },
});

// Le composant enfant ne supprime pas directement.
// Il envoie un événement au composant parent.
const emit = defineEmits(['delete-poll']);
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