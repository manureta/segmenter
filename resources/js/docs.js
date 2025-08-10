// Documentación Portal - JavaScript
import 'bootstrap';

// Solo cargar librerías si estamos en la página de docs
if (window.location.pathname.includes('/docs')) {
    // Importar marked para procesamiento de Markdown
    import('marked').then(({ marked }) => {
        window.marked = marked;
    });

    // Importar Prism para syntax highlighting
    import('prismjs').then((Prism) => {
        window.Prism = Prism.default || Prism;
        
        // Cargar componentes adicionales de Prism
        import('prismjs/components/prism-javascript');
        import('prismjs/components/prism-php');
        import('prismjs/components/prism-sql');
        import('prismjs/components/prism-bash');
    });
}

// Funciones para el portal de documentación
window.openDoc = function(filename) {
    const modal = new bootstrap.Modal(document.getElementById('docModal'));
    const title = document.getElementById('docTitle');
    const content = document.getElementById('docContent');
    const downloadLink = document.getElementById('downloadLink');
    
    // Configurar título y descarga
    title.textContent = filename.replace('.md', '').replace(/_/g, ' ');
    downloadLink.href = filename;
    downloadLink.download = filename;
    
    // Cargar contenido
    fetch(filename)
        .then(response => response.text())
        .then(markdown => {
            // Verificar que marked esté disponible
            if (window.marked) {
                // Convertir markdown a HTML
                const html = window.marked.parse(markdown);
                content.innerHTML = html;
                
                // Resaltar código si Prism está disponible
                if (window.Prism) {
                    window.Prism.highlightAllUnder(content);
                }
            } else {
                // Fallback: mostrar markdown crudo con formato básico
                content.innerHTML = `<pre style="white-space: pre-wrap; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6;">${markdown}</pre>`;
            }
            
            // Mostrar modal
            modal.show();
        })
        .catch(error => {
            content.innerHTML = '<div class="alert alert-danger">Error cargando el documento: ' + error + '</div>';
            modal.show();
        });
};

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('📚 Portal de documentación iniciado');
});