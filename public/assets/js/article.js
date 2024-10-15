

function articleEditor(initialSections) {
    const generateId = () => '_' + Math.random().toString(36).substr(2, 9);
    return {
        sections: initialSections.map(section => ({ ...section, id: generateId() })),
        showSectionOptions: false,
        showSectionContentOptions: true,
        generateId,
        initEditors() {
            // Find all textareas and initialize CKEditor
            this.$nextTick(() => {
                document.querySelectorAll('textarea').forEach((textarea) => {
                    if (!textarea.classList.contains('editor-initialized')) {
                        ClassicEditor
                            .create(textarea, editorConfig)
                            .then(editor => {
                                // Mark this textarea as initialized
                                textarea.classList.add('editor-initialized');
                            })
                            .catch(error => {
                                console.error(error);
                            });
                    }
                });
            });
        },
        // Upewnij się, że wszystkie metody są poprawnie zdefiniowane
        addTextSection() {
            this.sections.push({ type: 'text', content: '', id: generateId() });
            this.initEditors();
        },
        addImageSection() {
            this.sections.push({ type: 'image', content: '', id: generateId()  });
        },
        onClickShowSectionOptions() {
            this.showSectionContentOptions = false;
            this.showSectionOptions = true;
        },
        onCloseShowSectionOptions() {
            this.showSectionContentOptions = true;
            this.showSectionOptions = false;
        },
        addFullWidthSection() {
            // Możesz dostosować tę sekcję do swoich potrzeb
            this.sections.push({ type: 'full_width', content: '' });
            this.onCloseShowSectionOptions();
        },
        addTwoColumnsSection() {
            this.sections.push({
                type: 'columns',
                columns: [
                    { type: '', content: '' },
                    { type: '', content: '' }
                ]
            });
            this.onCloseShowSectionOptions();
        },
        removeSection(id) {
            const index = this.sections.findIndex(section => section.id === id);
            if (index !== -1) {
                this.sections.splice(index, 1);
            }
        },
        moveUp(id) {
            let sections = this.sections;
            const index = sections.findIndex(section => section.id === id);
            if (index > 0) {
                const temp = sections[index];

                sections.splice(index, 1);
                sections.splice(index - 1, 0, temp);
                this.sections = sections;
            }
        },
        moveDown(id) {
            let sections = this.sections;
            const index = sections.findIndex(section => section.id === id);
            if (index < sections.length - 1) {
                const temp = sections[index];
                sections.splice(index, 1);
                sections.splice(index + 1, 0, temp);
                this.sections = sections;
            }
        },
        dragStart(event, index) {
            event.dataTransfer.setData('text/plain', index);
            event.dataTransfer.effectAllowed = 'move';
        },
        drop(event, index) {
            event.preventDefault();
            const draggedIndex = parseInt(event.dataTransfer.getData('text/plain'));
            if (!isNaN(draggedIndex)) {
                const draggedItem = this.sections[draggedIndex];
                this.sections.splice(draggedIndex, 1);
                if (draggedIndex < index) {
                    index--;
                }
                this.sections.splice(index, 0, draggedItem);
            }
        },
        uploadImage(event, index) {
            const file = event.target.files[0];
            if (file) {
                const formData = new FormData();
                formData.append('image', file);
                fetch(`${urlUploadImage}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: formData
                })
            .then(response => response.json())
                    .then(data => {
                        this.sections[index].content = data.url;
                    });
            }
        },
        uploadColumnImage(event, sectionIndex, columnIndex) {
            const file = event.target.files[0];
            if (file) {
                const formData = new FormData();
                formData.append('image', file);
                fetch(`${urlUploadImage}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: formData
                })
            .then(response => response.json())
                    .then(data => {
                        this.sections[sectionIndex].columns[columnIndex].content = data.url;
                    });
            }
        },
        save() {
            fetch(`${urlUpdateContents}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    contents: this.sections
                })
            })
            .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert('Zapisano pomyślnie!');
                    } else {
                        alert('Wystąpił błąd podczas zapisywania.');
                    }
                });
        }
    }
}

function initializeCKEditors() {
    document.querySelectorAll('.content-article').forEach((textarea) => {
        console.log(textarea)
        ClassicEditor
            .create(textarea, editorConfig)
            .catch(error => {
                console.error(error);
            });
    });
}



