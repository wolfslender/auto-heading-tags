<?php
/*
Plugin Name: Auto Heading Tags by Hierarchy
Description: Asigna automáticamente etiquetas H1, H2, H3, etc. a los títulos según su jerarquía
Version: 1.8
Author: Alexis Olivero
Web: www.oliverodev.com
*/

// Evitar acceso directo al archivo
if (!defined('ABSPATH')) {
    exit;
}

class SEOHeadingHierarchy {
    private $used_levels = [];
    private $last_level = 0;
    
    public function __construct() {
        add_filter('the_content', array($this, 'process_headings'), 99);
    }
    
    public function process_headings($content) {
        // Reiniciar el seguimiento de niveles usados para cada nuevo contenido
        $this->used_levels = [];
        $this->last_level = 0;

        // Si no hay contenido o no hay encabezados, retornar el contenido original
        if (empty($content) || !preg_match('/<h[1-6][^>]*>/i', $content)) {
            return $content;
        }

        // Encontrar todos los encabezados con su contenido
        $pattern = '/<h([1-6])(.*?)>(.*?)<\/h\1>/i';
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

        if (empty($matches)) {
            return $content;
        }

        $replacements = array();

        // Procesar cada encabezado encontrado
        foreach ($matches as $match) {
            $original_tag = $match[0];        // Tag completo
            $level = (int)$match[1];         // Nivel del encabezado (1-6)
            $attributes = $match[2];         // Atributos HTML si existen
            $content_text = $match[3];       // Contenido del encabezado

            // Determinar el nuevo nivel
            $new_level = $this->determine_new_level($level);

            // Crear el nuevo tag
            $new_tag = "<h{$new_level}{$attributes}>{$content_text}</h{$new_level}>";
            
            // Almacenar el reemplazo
            $replacements[$original_tag] = $new_tag;
        }

        // Realizar todos los reemplazos de una vez
        if (!empty($replacements)) {
            $content = str_replace(
                array_keys($replacements),
                array_values($replacements),
                $content
            );
        }

        return $content;
    }

    private function determine_new_level($current_level) {
        // Si es el primer encabezado, debe ser siempre un H1
        if (empty($this->used_levels)) {
            $this->used_levels[] = 1;
            $this->last_level = 1;
            return 1;
        }

        // Encontrar el siguiente nivel disponible
        $new_level = $this->find_next_available_level($current_level);
        
        // Actualizar el seguimiento
        $this->used_levels[] = $new_level;
        $this->last_level = $new_level;

        return $new_level;
    }

    private function find_next_available_level($current_level) {
        // Si el nivel actual es menor que el último usado, 
        // buscar el siguiente nivel disponible en el contenido
        if ($current_level <= $this->last_level) {
            $proposed_level = $this->last_level + 1;
        } else {
            $proposed_level = $current_level;
        }

        // Asegurarse de que el nivel propuesto no exceda 6
        $proposed_level = min($proposed_level, 6);

        // Si el nivel propuesto ya está usado, buscar el siguiente disponible
        while (in_array($proposed_level, $this->used_levels) && $proposed_level < 6) {
            $proposed_level++;
        }

        return $proposed_level;
    }
}

// Inicializar el plugin
function initialize_seo_heading_hierarchy() {
    new SEOHeadingHierarchy();
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