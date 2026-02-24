class AdvancedSearch {
    constructor() {
        this.searchInput = null;
        this.suggestionsContainer = null;
        this.resultsContainer = null;
        this.currentQuery = '';
        this.debounceTimer = null;
        this.init();
    }

    init() {
        // Creer la barre de recherche uniquement sur les pages shop
        if (!window.location.pathname.includes('/shop')) {
            return;
        }

        // Attendre un peu pour éviter les conflits avec le profiler Symfony
        setTimeout(() => {
            this.createSearchBar();
            this.attachEventListeners();
        }, 500);
    }

    createSearchBar() {
        const searchBarHTML = `
            <div class="advanced-search-container" style="max-width: 600px; margin: 20px auto; position: relative;">
                <div class="search-input-group" style="position: relative;">
                    <input 
                        type="text" 
                        id="advanced-search-input"
                        placeholder="Rechercher des produits, jeux, tailles..."
                        autocomplete="off"
                        style="
                            width: 100%;
                            padding: 12px 45px 12px 15px;
                            border: 2px solid #e5e7eb;
                            border-radius: 25px;
                            font-size: 16px;
                            outline: none;
                            transition: all 0.3s ease;
                            box-sizing: border-box;
                        "
                    >
                    <button 
                        type="button" 
                        id="search-btn"
                        style="
                            position: absolute;
                            right: 5px;
                            top: 50%;
                            transform: translateY(-50%);
                            background: #7c3aed;
                            color: white;
                            border: none;
                            border-radius: 50%;
                            width: 35px;
                            height: 35px;
                            cursor: pointer;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            transition: background 0.3s ease;
                        "
                    >
                        🔍
                    </button>
                </div>
                
                <!-- Filtres -->
                <div class="search-filters" style="margin-top: 10px; display: flex; gap: 10px; flex-wrap: wrap;">
                    <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                        <input type="radio" name="search-type" value="" checked>
                        <span>Tous</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                        <input type="radio" name="search-type" value="merch">
                        <span>Merch</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                        <input type="radio" name="search-type" value="skin">
                        <span>Skins</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                        <input type="checkbox" id="show-source" style="margin-right: 5px;">
                        <span>Afficher la source</span>
                    </label>
                </div>
                
                <!-- Suggestions -->
                <div id="search-suggestions" style="
                    position: absolute;
                    top: 100%;
                    left: 0;
                    right: 0;
                    background: white;
                    border: 1px solid #e5e7eb;
                    border-radius: 8px;
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                    max-height: 300px;
                    overflow-y: auto;
                    z-index: 1000;
                    display: none;
                "></div>
                
                <!-- Resultats -->
                <div id="search-results" style="
                    margin-top: 20px;
                    display: none;
                "></div>
            </div>
        `;

        // Inserer sous la navbar
        const navbar = document.querySelector('nav');
        if (navbar) {
            navbar.insertAdjacentHTML('afterend', searchBarHTML);
        }

        this.searchInput = document.getElementById('advanced-search-input');
        this.suggestionsContainer = document.getElementById('search-suggestions');
        this.resultsContainer = document.getElementById('search-results');
    }

    attachEventListeners() {
        // Recherche avec debounce
        this.searchInput.addEventListener('input', (e) => {
            clearTimeout(this.debounceTimer);
            const query = e.target.value.trim();
            
            if (query.length < 2) {
                this.hideSuggestions();
                this.hideResults();
                return;
            }

            this.debounceTimer = setTimeout(() => {
                this.showSuggestions(query);
            }, 300);
        });

        // Focus/blur
        this.searchInput.addEventListener('focus', () => {
            if (this.currentQuery.length >= 2) {
                this.showSuggestions(this.currentQuery);
            }
        });

        this.searchInput.addEventListener('blur', () => {
            setTimeout(() => this.hideSuggestions(), 200);
        });

        // Bouton de recherche
        document.getElementById('search-btn').addEventListener('click', () => {
            this.performSearch();
        });

        // Recherche au Enter
        this.searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.performSearch();
            }
        });

        // Clic sur les suggestions
        this.suggestionsContainer.addEventListener('click', (e) => {
            const suggestionItem = e.target.closest('.suggestion-item');
            if (suggestionItem) {
                window.location.href = suggestionItem.dataset.url;
            }
        });

        // Changement de filtre
        setTimeout(() => {
            document.querySelectorAll('input[name="search-type"]').forEach(radio => {
                radio.addEventListener('change', () => {
                    if (this.currentQuery.length >= 2) {
                        this.performSearch();
                    }
                });
            });
        }, 100);

        // Styles dynamiques
        this.addDynamicStyles();
    }

    async performSearch() {
        const query = this.searchInput.value.trim();
        const type = document.querySelector('input[name="search-type"]:checked').value;
        const showSource = document.getElementById('show-source').checked;
        const limit = 20;
        
        if (query.length < 2) {
            this.showMessage('Veuillez entrer au moins 2 caractères');
            return;
        }

        this.showLoading();

        try {
            const response = await fetch(`/search?q=${encodeURIComponent(query)}&type=${type}&limit=${limit}&show_source=${showSource}`);
            const data = await response.json();
            
            if (data.success) {
                this.renderResults(data.results, data.debug);
            } else {
                this.showMessage(data.message || 'Erreur lors de la recherche');
            }
        } catch (error) {
            console.error('Erreur recherche:', error);
            this.showMessage('Erreur de connexion');
        }
    }

    async showSuggestions(query) {
        this.currentQuery = query;

        try {
            const response = await fetch(`/search/suggestions?q=${encodeURIComponent(query)}`);
            const data = await response.json();
            
            if (data.suggestions && data.suggestions.length > 0) {
                this.renderSuggestions(data.suggestions);
            } else {
                this.hideSuggestions();
            }
        } catch (error) {
            console.error('Erreur suggestions:', error);
            this.hideSuggestions();
        }
    }

    renderSuggestions(suggestions) {
        const suggestionsHTML = suggestions.map(suggestion => `
            <div class="suggestion-item" data-url="${suggestion.url}" style="
                padding: 12px 15px;
                cursor: pointer;
                border-bottom: 1px solid #f3f4f6;
                transition: background 0.2s ease;
            ">
                <div style="font-weight: 500; color: #1f2937;">${suggestion.text}</div>
                <div style="font-size: 14px; color: #6b7280;">${suggestion.subtitle}</div>
            </div>
        `).join('');

        this.suggestionsContainer.innerHTML = suggestionsHTML;
        this.suggestionsContainer.style.display = 'block';

        // Ajouter les styles hover
        this.suggestionsContainer.querySelectorAll('.suggestion-item').forEach(item => {
            item.addEventListener('mouseenter', () => {
                item.style.background = '#f9fafb';
            });
            item.addEventListener('mouseleave', () => {
                item.style.background = 'white';
            });
        });
    }

    renderResults(results, debug = null) {
        if (results.length === 0) {
            this.resultsContainer.innerHTML = `
                <div style="text-align: center; padding: 40px; color: #6b7280;">
                    <div style="font-size: 48px; margin-bottom: 10px;">🔍</div>
                    <div>Aucun résultat trouvé pour "${this.currentQuery}"</div>
                    <div style="font-size: 14px; margin-top: 5px;">Essayez d'autres mots-clés</div>
                </div>
            `;
            this.resultsContainer.style.display = 'block';
            return;
        }

        // Afficher les infos de debug si disponibles
        let debugInfo = '';
        if (debug) {
            debugInfo = `
                <div class="search-debug-info" style="
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    border: none;
                    border-radius: 12px;
                    padding: 20px;
                    margin-bottom: 25px;
                    font-size: 14px;
                    color: white;
                    box-shadow: 0 8px 32px rgba(102, 126, 234, 0.15);
                    position: relative;
                    overflow: hidden;
                ">
                    <div style="
                        position: absolute;
                        top: 0;
                        left: 0;
                        right: 0;
                        height: 4px;
                        background: linear-gradient(90deg, #fbbf24, #f59e0b, #ef4444);
                    "></div>
                    <div style="
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        margin-bottom: 15px;
                    ">
                        <strong style="font-size: 16px; display: flex; align-items: center;">
                            📊 <span style="margin-left: 8px;">Analyse de recherche</span>
                        </strong>
                        <span style="
                            background: rgba(255, 255, 255, 0.2);
                            padding: 4px 12px;
                            border-radius: 20px;
                            font-size: 12px;
                        ">Debug Info</span>
                    </div>
                    <div style="
                        display: grid;
                        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                        gap: 15px;
                    ">
                        <div style="
                            background: rgba(255, 255, 255, 0.1);
                            padding: 12px;
                            border-radius: 8px;
                            border-left: 3px solid #10b981;
                        ">
                            <div style="opacity: 0.8; font-size: 12px; margin-bottom: 4px;">🔍 Elasticsearch</div>
                            <div style="font-size: 18px; font-weight: bold;">${debug.elasticsearch_count || 0}</div>
                            <div style="opacity: 0.8; font-size: 11px;">résultats</div>
                        </div>
                        <div style="
                            background: rgba(255, 255, 255, 0.1);
                            padding: 12px;
                            border-radius: 8px;
                            border-left: 3px solid #3b82f6;
                        ">
                            <div style="opacity: 0.8; font-size: 12px; margin-bottom: 4px;">🗄️ Base locale</div>
                            <div style="font-size: 18px; font-weight: bold;">${debug.local_count || 0}</div>
                            <div style="opacity: 0.8; font-size: 11px;">résultats</div>
                        </div>
                        <div style="
                            background: rgba(255, 255, 255, 0.1);
                            padding: 12px;
                            border-radius: 8px;
                            border-left: 3px solid #f59e0b;
                        ">
                            <div style="opacity: 0.8; font-size: 12px; margin-bottom: 4px;">📈 Total</div>
                            <div style="font-size: 18px; font-weight: bold;">${debug.total_count || 0}</div>
                            <div style="opacity: 0.8; font-size: 11px;">résultats</div>
                        </div>
                    </div>
                    <div style="
                        margin-top: 15px;
                        padding-top: 15px;
                        border-top: 1px solid rgba(255, 255, 255, 0.2);
                        font-size: 12px;
                        opacity: 0.8;
                    ">
                        Recherche: "${debug.query || 'N/A'}" • Filtre: "${debug.type || 'Tous'}"
                    </div>
                </div>
            `;
        }

        const resultsHTML = results.map(result => `
                <div class="search-result-item" style="
                    background: white;
                    border: 1px solid #e5e7eb;
                    border-radius: 12px;
                    padding: 20px;
                    margin-bottom: 15px;
                    display: flex;
                    gap: 20px;
                    cursor: pointer;
                    transition: all 0.3s ease;
                " onclick="window.location.href='${result.url}'">
                    ${result.image ? `
                        <img src="/uploads/shop/${result.image}" alt="${result.name}" style="
                            width: 100px;
                            height: 100px;
                            object-fit: cover;
                            border-radius: 8px;
                        ">
                    ` : ''}
                    <div style="flex: 1;">
                        <h3 style="margin: 0 0 10px 0; color: #1f2937;">
                            ${result.highlighted || result.name}
                            ${result.source_label ? `<span style="font-size: 12px; color: #6b7280; margin-left: 8px; padding: 2px 6px; background: #f3f4f6; border-radius: 4px;">${result.source_label}</span>` : ''}
                        </h3>
                        <div style="color: #6b7280; margin-bottom: 10px;">
                            ${result.game ? `🎮 ${result.game}` : ''}
                            ${result.type ? ` • ${result.type.toUpperCase()}` : ''}
                        </div>
                        <div style="font-size: 20px; font-weight: bold; color: #7c3aed;">
                            ${result.price} €
                        </div>
                    </div>
                </div>
            `).join('');

        this.resultsContainer.innerHTML = debugInfo + resultsHTML;
        this.resultsContainer.style.display = 'block';

        // Ajouter les animations hover
        this.resultsContainer.querySelectorAll('.search-result-item').forEach(item => {
            item.addEventListener('mouseenter', () => {
                item.style.transform = 'translateY(-2px)';
                item.style.boxShadow = '0 8px 25px rgba(0, 0, 0, 0.1)';
            });
            item.addEventListener('mouseleave', () => {
                item.style.transform = 'translateY(0)';
                item.style.boxShadow = 'none';
            });
        });
    }

    showLoading() {
        this.resultsContainer.style.display = 'block';
        this.resultsContainer.innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <div style="font-size: 24px; margin-bottom: 10px;">🔍</div>
                <div>Recherche en cours...</div>
            </div>
        `;
    }

    showMessage(message) {
        this.resultsContainer.style.display = 'block';
        this.resultsContainer.innerHTML = `
            <div style="text-align: center; padding: 20px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; color: #dc2626;">
                ${message}
            </div>
        `;
    }

    hideSuggestions() {
        this.suggestionsContainer.style.display = 'none';
    }

    hideResults() {
        this.resultsContainer.style.display = 'none';
    }

    addDynamicStyles() {
        const style = document.createElement('style');
        style.textContent = `
            #advanced-search-input:focus {
                border-color: #7c3aed;
                box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
            }
            
            #search-btn:hover {
                background: #6d28d9 !important;
            }
            
            .search-filters label {
                padding: 8px 16px;
                background: #f3f4f6;
                border-radius: 20px;
                transition: all 0.2s ease;
            }
            
            .search-filters label:hover {
                background: #e5e7eb;
            }
            
            .search-filters input[type="radio"]:checked + span {
                color: #7c3aed;
                font-weight: 500;
            }
            
            .search-filters input[type="radio"]:checked + span::before {
                content: '✓ ';
            }
            
            mark {
                background: #fef3c7;
                color: #92400e;
                padding: 1px 2px;
                border-radius: 2px;
            }
            
            .suggestion-item:hover {
                background: #f9fafb;
            }
            
            .search-result-item:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            }
        `;
        document.head.appendChild(style);
    }
}

// Initialiser la recherche quand le DOM est chargé
document.addEventListener('DOMContentLoaded', () => {
    new AdvancedSearch();
});
