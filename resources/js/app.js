import './bootstrap';

import Alpine from 'alpinejs';
import Sortable from 'sortablejs';
import toastr from 'toastr';
import 'toastr/build/toastr.min.css';


window.Alpine = Alpine;

Alpine.start();


// const url = '/automatyka/public';
// const urlSegments = window.location.pathname.split('/');
// const pageId = urlSegments[4]; //

const url = 'https://temp-dashboard.oatllo.pl';
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
    viewportTopOffset: 30,
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
setInterval(() => {
    initializeCKEditors();
}, 3000);


function initializeCKEditors() {
    document.querySelectorAll('textarea.contents-textarea').forEach(textarea => {
        // Sprawdź, czy edytor został już zainicjalizowany dla tego textarea
        if (!textarea.editorInstance) {
            ClassicEditor.create(textarea, editorConfig)
                .then(editor => {
                    // Przypisz instancję edytora do textarea, aby nie inicjalizować go ponownie
                    textarea.editorInstance = editor;
                })
                .catch(error => {
                    console.error(error);
                });
        }
    });
}

window.addEventListener('load', function () {
    document.getElementById('loading-screen').classList.add('hidden');
    document.getElementById('main-content').classList.remove('blur-lg');
});

// Monitorowanie zmian rozmiaru ekranu
window.addEventListener('resize', () => {
    // Re-inicjalizacja edytorów na zmianę rozmiaru ekranu
    document.querySelectorAll('textarea.contents-textarea').forEach(textarea => {
        if (textarea.editorInstance) {
            textarea.editorInstance.destroy().then(() => {
                // Zainicjuj ponownie z odpowiednią konfiguracją dla nowego rozmiaru
                ClassicEditor.create(textarea, editorConfig)
                    .then(editor => {
                        textarea.editorInstance = editor;
                    })
                    .catch(error => {
                        console.error(error);
                    });
            });
        }
    });
});
