<x-app-layout>
    <div class="container mx-auto py-4">


        @csrf
            @foreach($page as $section)
                <div class="md:mx-auto grid max-w-7xl bg-white md:shadow md:shadow-2xl md:rounded-2xl my-6 px-3 py-3 md:p-10">

                    @if(!empty($section['label']))
                    <div class="border-b-2 border-gray-200 mb-5 pb-3">
                        {{ $section['label'] }}
                    </div>
                    @endif

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
                        if(data.changes){
                            notyf.success('Zapisano informacje');
                        }
                    })
                    .catch(error => {
                        console.error('Błąd:', error);
                        notyf.error('Wystąpił błąd podczas zapisywania danych');
                    });
                }

                // Nasłuchiwanie eventów blur na input[type="text"] i textarea
                const inputs = document.querySelectorAll('input[type="text"],input[type="date"],input[type="datetime"], textarea');
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

                            console.log(elementKey)

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
