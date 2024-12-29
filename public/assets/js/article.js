

function articleEditor(initialSections) {
    const generateId = () => '_' + Math.random().toString(36).substr(2, 9);
    return {
        sections: initialSections.map(section => ({
            ...section,
            id: section.id || generateId(),
            isGenerating: false
        })),
        showSectionOptions: false,
        showSectionContentOptions: true,
        generateId,
        // Upewnij się, że wszystkie metody są poprawnie zdefiniowane
        addTextSection() {
            this.sections.push({ type: 'text', content: '', id: generateId(), isGenerated: true });
        },
        addImageSection() {
            this.sections.push({ type: 'image', content: '', id: generateId(), isGenerated: true  });
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
            this.sections.push({ type: 'full_width', content: '', isGenerated: true, id: generateId() });
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
        generateText(sectionId, index) {
            this.sections[index].isGenerating = true;

            // Show a loading popup
            var notyf = new Notyf();
            notyf.success('Rozpoczęto generowanie...');
            fetch(`${urlBasic}/pages/generate-article-content/${articleId}/${sectionId}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' && data.content) {
                    // Update section content with generated text
                    this.sections[index].content = data.content;
                    // Mark the section as generated and hide the button
                    this.sections[index].isGenerated = true;
                }
            })
            .finally(() => {
                // Close loading popup
                notyf.success('Zakończono generowanie. Przeładuj stronę aby zobaczyć zmiany.');
            });

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
            var notyf = new Notyf();

            // Aktualizuj this.sections najnowszą zawartością z CKEditor
            document.querySelectorAll('textarea.contents-textarea').forEach(textarea => {
                const id = textarea.getAttribute('data-id');
                const editorInstance = textarea.editorInstance;
                if (editorInstance) {
                    const content = editorInstance.getData();
                    // Znajdź sekcję o danym id i zaktualizuj jej zawartość
                    const section = this.sections.find(sec => sec.id === id);
                    if (section) {
                        section.content = content;
                    }
                }
            });

            // Zapisanie danych
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
                        if(routeNamed  == 'pages.create') {
                            window.location.href = `${urlBasic}/pages/${articleId}/edit`;
                        }

                        notyf.success('Zapisano zmiany treści artykułu');
                    } else {
                        notyf.warning('Wystąpił problem podczas zapisywania');
                    }
                });
        }
    }
}

function initializeCKEditors() {
    document.querySelectorAll('.content-article').forEach((textarea) => {
        ClassicEditor
            .create(textarea, editorConfig)
            .catch(error => {
                console.error(error);
            });
    });
}



