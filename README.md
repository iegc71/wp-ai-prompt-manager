# prompt-plugin

prompts-plugin/
├── prompts-plugin.php          # Archivo principal del plugin
├── uninstall.php               # Archivo de desinstalación del plugin
├── includes/                   # Carpeta para clases y lógica del plugin
│   ├── class-post-type.php     # Registro del Custom Post Type "prompt"
│   ├── class-taxonomy.php      # Registro de la taxonomía "categoria-de-prompt"
│   ├── class-metaboxes.php     # Metaboxes para campos personalizados
│   ├── class-scripts.php       # Carga de scripts y estilos (JS/CSS)
│   ├── class-templates.php     # Gestión de plantillas personalizadas
│   ├── class-settings.php      # Nueva clase para el panel de configuración
│   ├── class-uninstall.php     # Limpieza al desinstalar el plugin
│   ├── helpers.php             # Funciones auxiliares para reutilización
├── templates/                  # Carpeta para plantillas personalizadas
│   ├── archive-prompt.php      # Plantilla para la lista de prompts
│   ├── single-prompt.php       # Plantilla para la página individual de un prompt
├── assets/                     # Carpeta para archivos estáticos
│   ├── css/                    # Estilos CSS
│   │   ├── admin-style.css
│   │   ├── frontend-style.css
│   ├── js/                     # Scripts JavaScript
│   │   ├── admin-script.js
│   │   ├── frontend-script.js
└── readme.txt                  # Descripción del plugin
