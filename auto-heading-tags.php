<?php
/*
Plugin Name: Auto Heading Tags by Hierarchy
Description: Asigna automáticamente etiquetas H1, H2, H3, etc. a los títulos según su jerarquía
Version: 2.0.0
Author: Alexis Olivero
Web: www.oliverodev.com
*/

// Evitar acceso directo al archivo
if (!defined('ABSPATH')) {
    exit;
}

class SEOHeadingHierarchy {
    private static $used_levels = [];
    private static $last_level = 0;
    private static $hierarchy_stack = [];
    private static $instance = null;
    private static $found_h1 = false;
    private static $toc_items = [];  // Nueva propiedad para los items del TOC
    private static $section_numbers = [0];  // Para la numeración jerárquica
    private static $level_counters = [];  // Nueva propiedad para contar secuencias por nivel
    private static $current_main_number = 0;
    private static $current_sub_numbers = [];
    
    public function __construct() {
        add_filter('the_content', array($this, 'process_headings'), 99);
        add_action('wp', array($this, 'reset_hierarchy_on_new_page'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_toc_assets'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_plugin_settings'));
    }

    // Agregar método para cargar assets
    public function enqueue_toc_assets() {
        wp_enqueue_style(
            'modern-toc-styles',
            plugins_url('css/toc-styles.css', __FILE__),
            array(),
            '1.0.0'
        );
        wp_enqueue_script(
            'modern-toc-script',
            plugins_url('js/toc-script.js', __FILE__),
            array('jquery'),
            '1.0.0',
            true
        );
    }

    // Método singleton para mantener una única instancia
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Reiniciar la jerarquía solo cuando se carga una nueva página
    public function reset_hierarchy_on_new_page() {
        if (!is_singular()) {
            self::$used_levels = [];
            self::$last_level = 0;
            self::$hierarchy_stack = [];
            self::$found_h1 = false;
            self::$level_counters = [];  // Agregar reinicio de contadores
            self::$current_main_number = 0;
            self::$current_sub_numbers = [];
        }
    }
    
    public function process_headings($content) {
        // Verificar si las características están habilitadas
        $enable_auto_headings = get_option('enable_auto_headings', 1);
        $enable_toc = get_option('enable_toc', 1);

        if (!is_single() || !$enable_auto_headings) {
            return $content;
        }
    
        self::$toc_items = [];
        self::$section_numbers = [0];

        // Extraer solo el contenido principal
        $main_content = $this->extract_main_content($content);

        if (empty($main_content) || !preg_match('/<h[1-6][^>]*>/i', $main_content)) {
            return $content;
        }

        $pattern = '/<h([1-6])(.*?)>(.*?)<\/h\1>/i';
        preg_match_all($pattern, $main_content, $matches, PREG_SET_ORDER);

        if (empty($matches)) {
            return $content;
        }

        $replacements = array();
        foreach ($matches as $index => $match) {
            // Verificar si el encabezado pertenece a un widget o sección relacionada
            if ($this->is_related_content($match[0])) {
                continue;
            }

            $original_tag = $match[0];
            $level = (int)$match[1];
            $attributes = $match[2];
            $content_text = strip_tags($match[3]);
            
            $heading_id = 'heading-' . sanitize_title($content_text) . '-' . $index;
            $new_level = $this->determine_new_level($level);
            
            self::$toc_items[] = array(
                'level' => $new_level,
                'text' => $content_text,
                'id' => $heading_id
            );
            
            $new_tag = "<h{$new_level} id='{$heading_id}'{$attributes}>{$content_text}</h{$new_level}>";
            $replacements[$original_tag] = $new_tag;
        }

        // Generar TOC solo si hay elementos válidos
        $toc_html = (!empty(self::$toc_items) && $enable_toc) ? $this->generate_toc() : '';
        
        // Aplicar reemplazos solo al contenido principal
        foreach ($replacements as $original => $new) {
            $content = preg_replace('/' . preg_quote($original, '/') . '/', $new, $content, 1);
        }

        return $toc_html . $content;
    }

    private function extract_main_content($content) {
        // Lista de clases y IDs que indican contenido no principal
        $exclude_patterns = [
            'related-posts',
            'yarpp-related',
            'widget',
            'sidebar',
            'comments',
            'footer',
            'nav-',
            'menu-'
        ];
        
        // Crear patrón de exclusión
        $pattern = '/<div\s+(?:[^>]*class=["\'][^"\']*(?:' . 
                implode('|', $exclude_patterns) . 
                ')[^"\']*["\'])?[^>]*>.*?<\/div>/is';
        
        // Eliminar secciones que coincidan con el patrón
        $main_content = preg_replace($pattern, '', $content);
        
        return $main_content;
    }

    private function is_related_content($html) {
        $excluded_classes = [
            'related',
            'widget',
            'sidebar',
            'comments',
            'footer',
            'nav-',
            'menu-'
        ];

        foreach ($excluded_classes as $class) {
            if (stripos($html, $class) !== false) {
                return true;
            }
        }

        return false;
    }

    private function generate_section_number($level) {
        // Eliminar la lógica de numeración
        return '';
    }

    private function generate_toc() {
        if (empty(self::$toc_items)) {
            return '';
        }

        $toc = '<div class="modern-toc-container">';
        $toc .= '<div class="modern-toc-header">';
        $toc .= '<span class="modern-toc-title">Tabla de Contenidos</span>';
        $toc .= '<button class="modern-toc-toggle" type="button" aria-expanded="true" aria-label="Toggle tabla de contenidos">✕</button>';
        $toc .= '</div>';
        $toc .= '<div class="modern-toc-content" style="display:block;">';
        $toc .= '<ul class="modern-toc-list">';

        foreach (self::$toc_items as $item) {
            $indent = ($item['level'] - 1) * 20;
            $toc .= sprintf(
                '<li class="toc-level-%d" style="margin-left: %dpx;">
                    <a href="#%s">%s</a>
                </li>',
                $item['level'],
                $indent,
                $item['id'],
                $item['text']
            );
        }

        $toc .= '</ul></div></div>';
        return $toc;
    }

    private function determine_new_level($current_level) {
        // Si es el primer encabezado de todos
        if (empty(self::$used_levels)) {
            self::$hierarchy_stack[] = 1;
            self::$used_levels[] = 1;
            self::$last_level = 1;
            self::$found_h1 = true;
            return 1;
        }

        // Si detectamos un H1 adicional, lo convertimos al siguiente nivel disponible
        if ($current_level === 1 && self::$found_h1) {
            $new_level = end(self::$hierarchy_stack) + 1;
        }
        // Para los demás casos, mantener la lógica jerárquica
        else if ($current_level < self::$last_level) {
            // Retroceder en la jerarquía pero mantener al menos el nivel anterior + 1
            while (!empty(self::$hierarchy_stack) && end(self::$hierarchy_stack) >= $current_level) {
                array_pop(self::$hierarchy_stack);
            }
            $new_level = empty(self::$hierarchy_stack) ? 
                        (self::$found_h1 ? 2 : 1) : // Si está vacío y ya hay H1, empezar en 2
                        end(self::$hierarchy_stack) + 1;
        } else {
            $new_level = end(self::$hierarchy_stack) + 1;
        }

        // Si es el primer H1 que encontramos
        if ($current_level === 1 && !self::$found_h1) {
            $new_level = 1;
            self::$found_h1 = true;
        }

        // Asegurarse de que el nivel no exceda 6
        $new_level = min($new_level, 6);
        
        // Actualizar el seguimiento
        self::$hierarchy_stack[] = $new_level;
        self::$used_levels[] = $new_level;
        self::$last_level = $new_level;

        return $new_level;
    }

    public function add_admin_menu() {
        add_options_page(
            'Auto Headings', 
            'Auto Headings', 
            'manage_options', 
            'auto-headings-settings', 
            array($this, 'render_settings_page')
        );
    }

    public function register_plugin_settings() {
        register_setting('auto-headings-settings-group', 'enable_toc');
        register_setting('auto-headings-settings-group', 'enable_auto_headings');
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h2>Auto Headings Configuration</h2>
            <form method="post" action="options.php">
                <?php settings_fields('auto-headings-settings-group'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="enable_toc">Table of Contents</label></th>
                        <td>
                            <label>
                                <input type="checkbox" id="enable_toc" name="enable_toc" value="1" 
                                    <?php checked(1, get_option('enable_toc', 1)); ?> />
                                Enable automatic table of contents
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="enable_auto_headings">Heading Hierarchy</label></th>
                        <td>
                            <label>
                                <input type="checkbox" id="enable_auto_headings" name="enable_auto_headings" value="1" 
                                    <?php checked(1, get_option('enable_auto_headings', 1)); ?> />
                                Enable automatic heading hierarchy
                            </label>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Save Changes'); ?>
            </form>
        </div>
        <?php
    }
}

// Modificar la inicialización para usar singleton
function initialize_seo_heading_hierarchy() {
    SEOHeadingHierarchy::get_instance();
}
add_action('plugins_loaded', 'initialize_seo_heading_hierarchy');

// Activación del plugin
register_activation_hook(__FILE__, function() {
    // Verificar versión mínima de PHP
    if (version_compare(PHP_VERSION, '7.0.0', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('Este plugin requiere PHP 7.0 o superior.');
    }
});
?>
