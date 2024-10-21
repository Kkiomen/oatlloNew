<!-- AlpineJS component -->
<script>
    function articleGenerator() {
        return {
            isGenerating: false,
            isProcessing: false,
            isGenerateContent: false,
            step: 0,
            articleId: null,
            async startGenerating() { // Dodanie async
                this.isProcessing = true;
                this.isGenerating = true;
                this.isGenerateContent = false;
                this.step = 1;

                try {
                    await this.createArticle(); // Czekaj aż zakończy się createArticle
                    this.step = 2;
                    await this.generateBasicInfo(); // Czekaj aż zakończy się generateBasicInfo
                    this.step = 3;
                    await this.generateContent(); // Czekaj aż zakończy się generateContent
                    this.isProcessing = false;
                    this.isGenerating = false;
                    console.log('Article generated successfully');
                    window.location.href = '{{ route('index') }}/pages/' + this.articleId + '/edit';
                } catch (error) {
                    this.isProcessing = false;
                    console.error(error);
                    alert('Wystąpił błąd podczas generowania artykułu.');
                }
            },
            createArticle() {
                // Make an AJAX POST request to the server
                return fetch('{{ route('pages.createArticle') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        about: this.$refs.about.value,
                    }),
                })
                    .then(response => response.json())
                    .then(data => {
                        // Handle response data if needed
                        if (data.status !== 'success') {
                            return Promise.reject('Error in createArticle');
                        }
                    });
            },
            generateBasicInfo() {
                // Make an AJAX POST request to the server
                return fetch('{{ route('pages.generateBasicInfo') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        // Pass necessary data
                    }),
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            this.articleId = data.articleId;
                        }

                        if (data.status !== 'success') {
                            return Promise.reject('Error in generateBasicInfo');
                        }
                    });
            },
            async generateContent() { // Dodanie async
                try {
                    const response = await fetch('{{ route('index') }}/pages/to-generate-content/' + this.articleId, {
                        method: 'GET',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                    });
                    const data = await response.json();

                    if (data.status !== 'success') {
                        return Promise.reject('Error in generateContent');
                    }

                    const list = document.querySelector('.list-schema-generate');

                    data.contents.forEach(item => {
                        const li = document.createElement('li');
                        li.textContent = item.heading;

                        // Dodanie id do każdego li
                        li.id = item.id;
                        li.style.color = item.isGenerated ? 'green' : 'red';

                        list.appendChild(li);
                    });
                    this.isGenerateContent = true;

                    for (const item of data.contents) {
                        if (!item.isGenerated) {
                            await this.generateArticleContentById(item.id); // Czekaj na zakończenie generowania
                        }
                    }
                } catch (error) {
                    console.error(error);
                }
            },
            async generateArticleContentById(schemaId) {
                document.getElementById(schemaId).style.color = '#bea252';

                const response = await fetch('{{ route('index') }}/pages/generate-article-content/' + this.articleId + '/' + schemaId, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                });
                const data = await response.json();

                if (data.status === 'success') {
                    document.getElementById(data.generatedKey).style.color = 'green';
                    if (data.nextKey !== null) {
                        await this.generateArticleContentById(data.nextKey); // Czekaj na kolejne generowanie
                    }
                } else {
                    document.getElementById(data.generatedKey).style.color = 'red';
                }
            },
        }
    }
</script>
