<x-vue-app-layout>
    <x-slot:scripts>
        @vite(['resources/js/poll-vote.js'])
    </x-slot>

    <x-slot:title>
        Vote
    </x-slot>

    <div
        id="app"
        data-props='@json(["token" => $token, "loginUrl" => route("login"), "csrfToken" => csrf_token()])'
    ></div>
</x-vue-app-layout>