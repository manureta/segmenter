// Import Prism.js for syntax highlighting
import Prism from 'prismjs';
import 'prismjs/components/prism-bash';
import 'prismjs/components/prism-javascript';
import 'prismjs/components/prism-json';
import 'prismjs/components/prism-php';

document.addEventListener('DOMContentLoaded', function() {
    // Mejorar el renderizado de métodos HTTP en el contenido markdown convertido
    enhanceApiMethodsDisplay();
    
    // Smooth scroll para los enlaces del TOC
    setupSmoothScrolling();
    
    // Copiar código al portapapeles
    setupCodeCopy();
    
    // Resaltar sintaxis con Prism.js
    Prism.highlightAll();
    
    // Indicador de estado de la API
    checkApiStatus();
});

function enhanceApiMethodsDisplay() {
    const content = document.querySelector('.api-content');
    if (!content) return;
    
    // Buscar y mejorar la presentación de métodos HTTP
    const codeBlocks = content.querySelectorAll('code');
    codeBlocks.forEach(code => {
        const text = code.textContent;
        if (text.match(/^(GET|POST|PUT|DELETE|PATCH)\s+\//)) {
            code.classList.add('endpoint-code');
            const parts = text.split(' ');
            const method = parts[0];
            const url = parts.slice(1).join(' ');
            
            code.innerHTML = `<span class="method-badge method-${method.toLowerCase()}">${method}</span> ${url}`;
        }
    });
}

function setupSmoothScrolling() {
    document.querySelectorAll('.toc a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            const target = document.getElementById(targetId) || 
                          document.querySelector(`h2:contains("${targetId}")`) ||
                          document.querySelector(`h3:contains("${targetId}")`);
            
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

function setupCodeCopy() {
    // Agregar botones de copia a los bloques de código
    document.querySelectorAll('pre code').forEach(codeBlock => {
        const pre = codeBlock.parentElement;
        const button = document.createElement('button');
        button.className = 'copy-code-btn';
        button.innerHTML = '📋 Copiar';
        button.title = 'Copiar código al portapapeles';
        
        button.addEventListener('click', function() {
            navigator.clipboard.writeText(codeBlock.textContent).then(() => {
                button.innerHTML = '✅ Copiado';
                button.classList.add('copied');
                
                setTimeout(() => {
                    button.innerHTML = '📋 Copiar';
                    button.classList.remove('copied');
                }, 2000);
            });
        });
        
        pre.style.position = 'relative';
        pre.appendChild(button);
    });
}

async function checkApiStatus() {
    const indicator = document.querySelector('.status-indicator');
    if (!indicator) return;
    
    try {
        const response = await fetch('/api/health');
        if (response.ok) {
            indicator.style.backgroundColor = '#28a745';
            indicator.innerHTML = '🟢 API Online - localhost:8090';
        } else {
            indicator.style.backgroundColor = '#ffc107';
            indicator.innerHTML = '🟡 API con problemas';
        }
    } catch (error) {
        indicator.style.backgroundColor = '#dc3545';
        indicator.innerHTML = '🔴 API desconectada';
    }
}

// Utilidades adicionales para la documentación
const ApiDocs = {
    // Resaltar una sección específica
    highlightSection: function(sectionId) {
        const section = document.getElementById(sectionId);
        if (section) {
            section.classList.add('highlight-section');
            setTimeout(() => {
                section.classList.remove('highlight-section');
            }, 3000);
        }
    },
    
    // Buscar en la documentación
    search: function(query) {
        const content = document.querySelector('.api-content');
        if (!content) return [];
        
        const results = [];
        const walker = document.createTreeWalker(
            content,
            NodeFilter.SHOW_TEXT,
            null,
            false
        );
        
        let node;
        while (node = walker.nextNode()) {
            if (node.textContent.toLowerCase().includes(query.toLowerCase())) {
                results.push(node.parentElement);
            }
        }
        
        return results;
    }
};

// Exportar para uso global
window.ApiDocs = ApiDocs;