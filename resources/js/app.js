import './bootstrap';

import Alpine from 'alpinejs';
import Sortable from 'sortablejs';
import toastr from 'toastr';
import 'toastr/build/toastr.min.css';



window.Alpine = Alpine;

Alpine.start();



const url = 'https://temp-dashboard.oatllo.pl/';
const urlSegments = window.location.pathname.split('/');
const pageId = urlSegments[2]; //

import {
    ClassicEditor,
    AccessibilityHelp,
    Alignment,
    AutoLink,
    Autosave,
    BalloonToolbar,
    Bold,
    Essentials,
    FontBackgroundColor,
    FontColor,
    FontFamily,
    FontSize,
    GeneralHtmlSupport,
    Heading,
    ImageBlock,
    ImageToolbar,
    Italic,
    Link,
    Paragraph,
    PasteFromOffice,
    RemoveFormat,
    SelectAll,
    SourceEditing,
    Strikethrough,
    Subscript,
    Superscript,
    Table,
    TableCaption,
    TableCellProperties,
    TableColumnResize,
    TableProperties,
    TableToolbar,
    Underline,
    Undo
} from 'ckeditor5';

import 'ckeditor5/ckeditor5.css';


const editorConfig = {
    toolbar: {
        items: [
            'undo',
            'redo',
            '|',
            'sourceEditing',
            '|',
            'heading',
            '|',
            // 'fontSize',
            'fontFamily',
            'fontColor',
            'fontBackgroundColor',
            '|',
            'bold',
            'italic',
            'underline',
            // 'strikethrough',
            // 'subscript',
            // 'superscript',
            // 'removeFormat',
            '|',
            'link',
            'insertTable',
            '|',
            'alignment'
        ],
        shouldNotGroupWhenFull: false
    },
    plugins: [
        AccessibilityHelp,
        Alignment,
        AutoLink,
        Autosave,
        BalloonToolbar,
        Bold,
        Essentials,
        FontBackgroundColor,
        FontColor,
        FontFamily,
        FontSize,
        GeneralHtmlSupport,
        Heading,
        ImageBlock,
        ImageToolbar,
        Italic,
        Link,
        Paragraph,
        PasteFromOffice,
        RemoveFormat,
        SelectAll,
        SourceEditing,
        Strikethrough,
        Subscript,
        Superscript,
        Table,
        TableCaption,
        TableCellProperties,
        TableColumnResize,
        TableProperties,
        TableToolbar,
        Underline,
        Undo
    ],
    balloonToolbar: ['bold', 'italic', '|', 'link'],
    fontFamily: {
        supportAllValues: true
    },
    fontSize: {
        options: [10, 12, 14, 'default', 18, 20, 22],
        supportAllValues: true
    },
    heading: {
        options: [
            {
                model: 'paragraph',
                title: 'Paragraph',
                class: 'ck-heading_paragraph'
            },
            {
                model: 'heading2',
                view: 'h2',
                title: 'Heading 2',
                class: 'ck-heading_heading2'
            },
            {
                model: 'heading3',
                view: 'h3',
                title: 'Heading 3',
                class: 'ck-heading_heading3'
            },
            {
                model: 'heading4',
                view: 'h4',
                title: 'Heading 4',
                class: 'ck-heading_heading4'
            },
            {
                model: 'heading5',
                view: 'h5',
                title: 'Heading 5',
                class: 'ck-heading_heading5'
            },
            {
                model: 'heading6',
                view: 'h6',
                title: 'Heading 6',
                class: 'ck-heading_heading6'
            }
        ]
    },
    htmlSupport: {
        allow: [
            {
                name: /^.*$/,
                styles: true,
                attributes: true,
                classes: true
            }
        ]
    },
    image: {
        toolbar: ['imageTextAlternative']
    },

    link: {
        addTargetToExternalLinks: true,
        defaultProtocol: 'https://',
        decorators: {
            toggleDownloadable: {
                mode: 'manual',
                label: 'Downloadable',
                attributes: {
                    download: 'file'
                }
            }
        }
    },
    table: {
        contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells', 'tableProperties', 'tableCellProperties']
    }
};


initializeCKEditors();

document.addEventListener('DOMContentLoaded', () => {
    const addSectionBtn = document.getElementById('add-section-btn');
    const generateButton = document.getElementById('generate-content-btn');
    const generateContentModal = document.getElementById('content-generate-modal');
    const sectionTypeModal = document.getElementById('section-type-modal');
    const closeModalBtn = document.getElementById('close-modal-btn');
    const selectSectionTypeButtons = document.querySelectorAll('.select-section-type');
    const sectionsContainer = document.getElementById('sections-container');

    if (addSectionBtn) {
        addSectionBtn.addEventListener('click', () => {
            sectionTypeModal.classList.remove('hidden');
        });

        closeModalBtn.addEventListener('click', () => {
            sectionTypeModal.classList.add('hidden');
        });

        generateButton.addEventListener('click', () => {
            generateContentModal.classList.remove('hidden');
        });

        document.getElementById('close-generate-content-modal-btn').addEventListener('click', () => {
            generateContentModal.classList.add('hidden');
        });

        selectSectionTypeButtons.forEach(button => {
            button.addEventListener('click', () => {
                const type = button.getAttribute('data-type');

                axios.post(`${url}/pages/${pageId}/sections`, { type })
                    .then(response => {
                        location.reload();
                    })
                    .catch(error => {
                        console.error(error);
                    });
            });
        });

        sectionsContainer.addEventListener('click', (e) => {
            if (e.target.classList.contains('delete-section-btn')) {
                const sectionElement = e.target.closest('.section');
                const sectionId = sectionElement.getAttribute('data-section-id');

                axios.delete(`${url}/sections/${sectionId}`)
                    .then(response => {
                        sectionElement.remove();
                    });
            }

            if (e.target.classList.contains('select-content-type')) {
                const position = e.target.getAttribute('data-position');
                const sectionElement = e.target.closest('.section');
                const sectionId = sectionElement.getAttribute('data-section-id');

                // Tutaj możesz otworzyć modal do wyboru typu treści (tekst lub obraz)
                // Dla uproszczenia, zakładamy że wybieramy tekst
                const contentType = prompt('Wpisz "text" dla tekstu lub "image" dla obrazu:');

                if (contentType === 'text') {
                    const textarea = document.createElement('textarea');
                    textarea.classList.add('w-full', 'border', 'rounded', 'px-2', 'py-1');
                    textarea.setAttribute('data-type', 'text');

                    const contentArea = e.target.parentElement.nextElementSibling;
                    contentArea.innerHTML = '';
                    contentArea.appendChild(textarea);
                    initializeCKEditors();

                    textarea.addEventListener('blur', () => {
                        const textContent = textarea.value;
                        initializeCKEditors();
                        axios.post(`${url}/section-contents`, {
                            section_id: sectionId,
                            position,
                            content_type: 'text',
                            text_content: textContent
                        });
                    });
                }

                // Obsługa obrazu analogicznie
            }
        });
    }
});


// Inicjalizacja SortableJS na kontenerze sekcji
const sectionsContainer = document.getElementById('sections-container');
if (sectionsContainer) {
    Sortable.create(sectionsContainer, {
        handle: '.section-handle',
        animation: 150,
        onEnd: function (evt) {
            // Pobierz nową kolejność sekcji
            const orderedSectionIds = Array.from(sectionsContainer.children).map(section => section.dataset.sectionId);

            // Wyślij AJAX z nową kolejnością
            axios.post(`${url}/pages/${pageId}/sections/order`, { order: orderedSectionIds })
                .then(response => {
                    console.log('Kolejność sekcji zaktualizowana.');
                    // initializeCKEditors();
                })
                .catch(error => {
                    console.error('Błąd przy aktualizacji kolejności sekcji:', error);
                });
        }
    });
}


let selectedSectionId = null;
let selectedPosition = null;

sectionsContainer.addEventListener('click', (e) => {
    if (e.target.closest('.select-content-type-btn')) {
        selectedSectionId = e.target.closest('.section').dataset.sectionId;
        selectedPosition = e.target.closest('.select-content-type-btn').dataset.position;
        document.getElementById('content-type-modal').classList.remove('hidden');
    }
});

document.querySelectorAll('.select-content-type-option').forEach(button => {
    button.addEventListener('click', () => {
        const contentType = button.dataset.contentType;
        const contentArea = document.querySelector(`.section[data-section-id="${selectedSectionId}"] .grid > div:nth-child(${selectedPosition})`);

        if (contentType === 'text') {
            contentArea.innerHTML = `
                <textarea class="rich-text-editor h-40" data-type="text"></textarea>
            `;


            ClassicEditor.create(contentArea.querySelector('.rich-text-editor'), editorConfig)
                .then(editor => {
                    editor.model.document.on('change:data', () => {
                        const textContent = editor.getData();
                        axios.post(`${url}/section-contents`, {
                            section_id: selectedSectionId,
                            position: selectedPosition,
                            content_type: 'text',
                            text_content: textContent
                        }).then(response => {
                            console.log('Treść tekstowa zapisana.');
                        });
                    });
                })
                .catch(error => {
                    console.error(error);
                });
        } else if (contentType === 'image') {
            contentArea.innerHTML = `
                <input type="file" class="image-input w-full mb-2" data-type="image">
                <input type="text" class="w-full border rounded px-2 py-1" placeholder="Alt text" data-type="alt_text">
            `;
            const fileInput = contentArea.querySelector('.image-input');
            const altTextInput = contentArea.querySelector('[data-type="alt_text"]');

            fileInput.addEventListener('change', () => {
                const formData = new FormData();

                formData.append('section_id', selectedSectionId);
                formData.append('position', selectedPosition);
                formData.append('content_type', 'image');
                formData.append('image', fileInput.files[0]);
                formData.append('alt_text', altTextInput.value);

                axios.post(`${url}/section-contents`, formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                }).then(response => {
                    const imagePath = `${url}/storage/${response.data.content.image_path}`; // Ścieżka do zapisanego obrazu
                    const altText = altTextInput.value;
                    const contentArea = document.querySelector(`.section[data-section-id="${selectedSectionId}"] .grid > div:nth-child(${selectedPosition})`);
                    const existingImg = contentArea.querySelector('img.preview-image'); // Znajdź istniejący obraz

                    if (existingImg) {
                        // Aktualizuj istniejący obraz
                        existingImg.src = imagePath;
                        existingImg.alt = altText;
                    } else {
                        // Jeśli obraz nie istnieje, dodaj go
                        const imgHtml = `
                            <div class="mb-2">
                                <img src="${imagePath}" alt="${altText}" class="w-full h-auto rounded-lg preview-image" data-content-id="${response.data.content.id}">
                            </div>
                        `;
                        contentArea.insertAdjacentHTML('afterbegin', imgHtml);
                    }
                    console.log('Obraz zapisany.');
                });
            });
        }

        document.getElementById('content-type-modal').classList.add('hidden');
    });
});

document.getElementById('close-content-modal-btn').addEventListener('click', () => {
    document.getElementById('content-type-modal').classList.add('hidden');
});



function collectSectionsData() {
    const sectionsData = [];
    document.querySelectorAll('.section').forEach(section => {
        const sectionId = section.dataset.sectionId;
        const order = Array.from(section.parentNode.children).indexOf(section);
        const contents = [];

        section.querySelectorAll('[data-content-id]').forEach(contentElement => {
            const contentId = contentElement.dataset.contentId;
            const contentType = contentElement.dataset.type;
            let contentValue = '';
            let altText = '';

            console.log(contentType);
            if (contentType === 'text') {
                // Pobierz dane z edytora CKEditor
                const editorInstance = contentElement.editorInstance;
                contentValue = editorInstance.getData();
            } else if (contentType === 'image') {
                // Pobierz ścieżkę do obrazu i alt text
                const imageElement = contentElement.querySelector('img'); // image inside the contentElement
                const altTextInput = contentElement.querySelector('input.text_alt'); // alt text inside the contentElement
                contentValue = imageElement ? imageElement.src : ''; // Ścieżka do obrazu
                altText = altTextInput ? altTextInput.value : ''; // Tekst alternatywny
            }

            // Dodaj treść do tablicy contents tylko wtedy, gdy mamy `content_type`
            if (contentType) {
                contents.push({
                    content_id: contentId,
                    content_type: contentType,
                    content_value: contentValue,
                    alt_text: altText
                });
            }
        });

        sectionsData.push({
            section_id: sectionId,
            order: order,
            contents: contents
        });
    });

    return sectionsData;
}


// Obsługa kliknięcia przycisku "Zapisz sekcje"
const saveSectionsBtn = document.getElementById('save-sections-btn');
if (saveSectionsBtn) {
    saveSectionsBtn.addEventListener('click', () => {
        const sectionsData = collectSectionsData();

        axios.post(`${url}/pages/${pageId}/sections/save`, { sections: sectionsData })
            .then(response => {
                toastr.success('Sekcje zostały zapisane.');
            })
            .catch(error => {
                toastr.error('Wystąpił błąd podczas zapisywania sekcji.');
                console.error(error);
            });
    });
}


function initializeCKEditors() {
    document.querySelectorAll('.rich-text-editor').forEach(textarea => {
        ClassicEditor.create(textarea, editorConfig)
            .then(editor => {
                textarea.editorInstance = editor;
            })
            .catch(error => {
                console.error(error);
            });
    });
}


document.querySelectorAll('.section').forEach(section => {
    section.addEventListener('click', function() {
        document.querySelectorAll('.section').forEach(currentSection => {
            currentSection.classList.add('border-white');
            currentSection.classList.remove('border-gray-300');
        });

        // Ukryj wszystkie kosze na śmieci
        document.querySelectorAll('.section-header').forEach(header => {
            header.classList.add('hidden');
        });
        // Pokaż kosz dla klikniętej sekcji
        this.querySelector('.section-header').classList.remove('hidden');
        this.querySelector('.section-header').classList.add('border-gray-300');
    });
});

document.addEventListener('click', function(event) {
    // Jeśli kliknięcie jest poza sekcją, ukryj wszystkie kosze na śmieci
    if (!event.target.closest('.section')) {
        document.querySelectorAll('.section-header').forEach(header => {
            header.classList.add('hidden');
        });
    }
});


document.querySelectorAll('.image-input').forEach(input => {
    input.addEventListener('change', function() {
        const file = this.files[0];
        const contentId = this.getAttribute('data-content-id');
        const positionId = this.getAttribute('data-position-id');
        const sectionId = this.closest('.section').getAttribute('data-section-id');

        const formData = new FormData();

        formData.append('image', file);
        formData.append('section_id', sectionId);
        formData.append('position', positionId);
        formData.append('content_type', 'image');
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

        axios.post(`${url}/section-contents`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        }).then(response => {
            const imagePath = response.data.content.image_path;
            const section = document.querySelector(`.section[data-section-id="${sectionId}"]`);
            const previewImage = section.querySelector(`img[data-position-id="${positionId}"]`);

            previewImage.src = `${url}/storage/${imagePath}`;  // Zaktualizuj źródło obrazka
        }).catch(error => {
            console.error('Błąd podczas wgrywania zdjęcia', error);
        });
    });
});

function fetchSections() {
    const sectionsContainer = document.getElementById('sections-container');

    // Wyślij żądanie AJAX do endpointa
    axios.get(`${url}/pages/${pageId}/sections`)
        .then(response => {
            // Odbierz HTML wygenerowany przez backend
            const html = response.data.html;

            // Zastąp aktualną zawartość kontenera nowymi danymi
            sectionsContainer.innerHTML = html;

            // Inicjalizuj CKEditor i inne eventy ponownie
            initializeCKEditors();
            bindImageUploadHandlers();
        })
        .catch(error => {
            console.error('Błąd podczas pobierania sekcji:', error);
        });
}
