<x-app-layout>
    <div class="container mx-auto py-4">
        @csrf
            @foreach($page as $section)
                <div class="mx-auto grid max-w-7xl bg-white shadow shadow-2xl rounded-2xl my-6 p-10">

                    <div class="border-b-2 border-gray-200 mb-5 pb-3">
                        Sekcja {{ $loop->iteration }}
                    </div>

                    @if(!empty($section['content']))

                        @foreach($section['content'] as $content)
                            @include('cms_page.partials.content', ['content' => $content])
                        @endforeach

                    @endif



                    @if(!empty($section['subsections']))
                        @include('cms_page.partials.subsections', ['section' => $section])
                    @endif


                </div>
            @endforeach



        <script src="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.js"></script>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // Wyłącz automatyczne przypisanie Dropzone
                Dropzone.autoDiscover = false;

                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                // Znajdź wszystkie elementy Dropzone i zainicjuj je
                document.querySelectorAll(".dropzone").forEach(function (dropzoneElement) {
                    const elementId = dropzoneElement.getAttribute('id');
                    const inputFieldId = `image-input-${elementId.split("-")[1]}`;


                    // Inicjalizacja Dropzone dla każdego pola
                    const dropzoneInstance = new Dropzone(`#${elementId}`, {
                        url: "{{ route('upload.image') }}", // Poprawny URL do przesyłania plików
                        paramName: "file", // Nazwa pliku w żądaniu
                        maxFilesize: 2, // Maksymalny rozmiar pliku w MB
                        acceptedFiles: ".jpeg,.jpg,.png,.gif",
                        uploadMultiple: false, // Tylko jeden plik
                        maxFiles: 1,
                        headers: {
                            'X-CSRF-TOKEN': csrfToken
                        },
                        init: function () {
                            // Usuwanie starych plików przed wgraniem nowego
                            this.on("addedfile", function (file) {
                                if (this.files.length > 1) {
                                    this.removeFile(this.files[0]);
                                }
                            });
                        },
                        success: function (file, response) {
                            // Po przesłaniu przypisz URL do ukrytego pola
                            document.getElementById(inputFieldId).value = response.filePath;
                            var notyf = new Notyf();
                            notyf.success('Plik został wgrany');
                            console.log('fsdfs');

                            // Opcjonalnie: podgląd obrazu można dodać tutaj (jeśli chcesz)
                            // document.getElementById('image-preview-' + elementId.split("-")[1]).src = response.filePath;
                        },
                        error: function (file, response) {
                            console.error("Błąd przy przesyłaniu pliku: ", response);
                            console.log('fsdfs');
                        }
                    });
                });
            });

            document.addEventListener('DOMContentLoaded', function() {
                var notyf = new Notyf();
                // Funkcja, która zapisuje dane z pojedynczego input/textarea
                function saveFormData(event) {
                    const input = event.target; // Pobierz aktualnie zmodyfikowane pole
                    const formData = {};

                    // Dodaj dane do obiektu formData (tylko jedno pole)
                    const key = input.name; // name jako klucz
                    const value = input.value; // wartość wpisana przez użytkownika

                    if (key) {
                        formData[key] = value;
                        formData['website'] = '{{ $namePage }}';
                    }

                    // Wyślij dane na backend metodą POST
                    fetch('{{ route('cmspage.update') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(formData)
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log('Dane zapisane:', data);
                        notyf.success('Zapisano informacje');
                    })
                    .catch(error => {
                        console.error('Błąd:', error);
                        notyf.error('Wystąpił błąd podczas zapisywania danych');
                    });
                }

                // Nasłuchiwanie eventów blur na input[type="text"] i textarea
                const inputs = document.querySelectorAll('input[type="text"], textarea');
                inputs.forEach(input => {
                    input.addEventListener('blur', saveFormData); // Event na opuszczenie pola
                });
            });



            document.addEventListener('DOMContentLoaded', function() {
                var notyf = new Notyf();
                const imageUploadElements = document.querySelectorAll('input[type="file"][id^="image-upload-"]');

                imageUploadElements.forEach((inputElement) => {
                    inputElement.addEventListener('change', async function(event) {
                        const file = event.target.files[0];
                        const elementKey = event.target.id.replace('image-upload-', ''); // Extract element key

                        if (file) {
                            const formData = new FormData();
                            formData.append('file', file);
                            formData.append('website', '{{ $namePage }}');
                            formData.append('key', elementKey);

                            try {
                                const response = await fetch('{{ route('upload.image') }}', {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                    },
                                    body: formData
                                })
                                .then(response => response.json())
                                .then(data => {
                                    console.log('Dane zapisane:', data);
                                    notyf.success('Zapisano informacje');
                                    const previewImage = document.getElementById(`preview-image-${elementKey}`);
                                    previewImage.src = data.filePath;
                                })
                                .catch(error => {
                                    console.error('Błąd:', error);
                                    notyf.error('Wystąpił błąd podczas zapisywania danych');
                                });
                            } catch (error) {
                                console.error('Error during upload', error);
                            }
                        }
                    });
                });
            });



        </script>
    </div>
</x-app-layout>
