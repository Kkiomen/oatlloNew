<!doctype html>
<html>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- Meta tag z tokenem CSRF -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <!-- Podpięcie Alpine.js -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.12.0/dist/cdn.min.js" defer></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:ital,wght@0,100..800;1,100..800&family=Montserrat:ital,wght@0,100..900;1,100..900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <style>
        .poppins-regular {
            font-family: "Poppins", sans-serif;
            font-weight: 400;
            font-style: normal;
        }
        body {
            font-family: 'Poppins', sans-serif !important;
        }
    </style>
</head>
<body>
<div x-data="app()" x-init="init()">
    <!-- Modal z hasłem – wyświetlany przy braku ciasteczka -->
    <div x-show="!accessGranted" class="fixed inset-0 flex items-center justify-center bg-gray-800 bg-opacity-75 z-50">
        <div class="bg-white p-6 rounded shadow-md">
            <h2 class="text-xl font-bold mb-4">Podaj hasło</h2>
            <input type="password" x-model="passwordInput" class="border p-2 mb-4 w-full" placeholder="Wpisz hasło">
            <div x-show="errorMessage" class="text-red-500 mb-4" x-text="errorMessage"></div>
            <button @click="checkPassword" class="bg-indigo-600 text-white px-4 py-2 rounded">Zatwierdź</button>
        </div>
    </div>

    <!-- Główna zawartość – widoczna po poprawnym wpisaniu hasła -->
    <div x-show="accessGranted" style="display: none;">
        <div class="isolate bg-white px-6 py-24 sm:py-32 lg:px-8">
            <div class="absolute inset-x-0 top-[-10rem] -z-10 transform-gpu overflow-hidden blur-3xl sm:top-[-20rem]" aria-hidden="true">
                <div class="relative left-1/2 -z-10 aspect-1155/678 w-[36.125rem] max-w-none -translate-x-1/2 rotate-[30deg] bg-linear-to-tr from-[#ff80b5] to-[#9089fc] opacity-30 sm:left-[calc(50%-40rem)] sm:w-[72.1875rem]" style="clip-path: polygon(74.1% 44.1%, 100% 61.6%, 97.5% 26.9%, 85.5% 0.1%, 80.7% 2%, 72.5% 32.5%, 60.2% 62.4%, 52.4% 68.1%, 47.5% 58.3%, 45.2% 34.5%, 27.5% 76.7%, 0.1% 64.9%, 17.9% 100%, 27.6% 76.8%, 76.1% 97.7%, 74.1% 44.1%)"></div>
            </div>
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-4xl font-semibold tracking-tight text-balance text-gray-900 sm:text-5xl">Informacja</h2>
                <p class="mt-2 text-lg/8 text-gray-600">Dowiedz się, czy twój projekt jest zgodny etycznie</p>
            </div>
            <!-- Formularz z obsługą AJAX oraz checkboxem -->
            <form @submit.prevent="submitForm" class="mx-auto mt-16 max-w-xl sm:mt-20">
                <div class="grid grid-cols-1 gap-x-8 gap-y-6 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="message" class="block text-sm/6 font-semibold text-gray-900">Opisz swój projekt/pomysł</label>
                        <div class="mt-2.5">
                            <textarea x-model="message" name="message" id="message" rows="4" class="block w-full rounded-md bg-white px-3.5 py-2 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600"></textarea>
                            <p class="mt-4 text-sm/6 text-gray-500">Asystent nie zapisuje informacji o konwersacji!</p>

                        </div>
                    </div>
                    <div class="flex gap-x-4 sm:col-span-2 items-center">
                        <div class="flex h-6 items-center">
                            <button type="button"
                                    @click="accepted = !accepted"
                                    :class="accepted ? 'bg-indigo-600' : 'bg-gray-200'"
                                    class="flex w-8 flex-none cursor-pointer rounded-full p-px ring-1 ring-gray-900/5 transition-colors duration-200 ease-in-out ring-inset focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                                    role="switch" :aria-checked="accepted.toString()" aria-labelledby="switch-1-label">
                                <span class="sr-only">Agree to policies</span>
                                <span :class="accepted ? 'translate-x-3.5' : 'translate-x-0'" aria-hidden="true" class="size-4 transform rounded-full bg-white ring-1 shadow-xs ring-gray-900/5 transition duration-200 ease-in-out"></span>
                            </button>
                        </div>
                        <label class="text-sm/6 text-gray-600" id="switch-1-label">
                            Zdaje sobię sprawę, iż witryna oraz zamieszczone na niej informacje nie są w żadnym stopniu powiązane z podmiotami: Centrum Zastosowań Sztucznej Inteligencji i Analiz Danych NASK,OPI PIB, Ośrodek Przetwarzania Informacji, Państwowy Instytut Badawczy, Urząd Ochrony Danych Osobowych.<br/>
                            Otrzymane informacje mogą zawierać błędy. Wszelkie decyzje podejmowane na podstawie informacji zawartych na tej stronie są decyzjami użytkownika.
                        </label>
                    </div>
                </div>
                <div class="mt-10">
                    <button type="submit" class="block w-full rounded-md bg-indigo-600 px-3.5 py-2.5 text-center text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Let's talk</button>
                </div>
                <div class="mt-4 text-center text-red-600" x-text="response"></div>
            </form>
        </div>
    </div>
</div>

<!-- Skrypt z logiką Alpine.js umieszczony na dole strony -->
<script>
    function app() {
        return {
            accessGranted: false,
            passwordInput: '',
            errorMessage: '',
            message: '',
            accepted: false,
            response: '',
            init() {
                // Sprawdzenie ciasteczka z informacją o dostępie
                const cookie = document.cookie.split('; ').find(row => row.startsWith('accessGranted='));
                if (cookie && cookie.split('=')[1] === 'true') {
                    this.accessGranted = true;
                }
            },
            checkPassword() {
                if (this.passwordInput === 'test123') {
                    // Ustawienie ciasteczka – dostęp przyznany
                    document.cookie = 'accessGranted=true; path=/';
                    this.accessGranted = true;
                    this.errorMessage = '';
                } else {
                    this.errorMessage = 'Niepoprawne hasło!';
                }
            },
            submitForm() {
                if (!this.accepted) {
                    this.response = 'Musisz zaakceptować warunki!';
                    return;
                }
                let url = '{{ route("post-etic-index") }}';
                // Pobranie tokena CSRF z meta tagu
                let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                let formData = new FormData();
                formData.append('message', this.message);
                fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        this.response = 'Formularz wysłany poprawnie!';
                    })
                    .catch(error => {
                        this.response = 'Wystąpił błąd: ' + error.message;
                    });
            }
        };
    }
</script>
</body>
</html>
