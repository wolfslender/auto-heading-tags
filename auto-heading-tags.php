<?php
/*
Plugin Name: Auto Heading Tags by Hierarchy
Description: Asigna automáticamente etiquetas H1, H2, H3, etc. a los títulos según su jerarquía
Version: 1.0.6
Author: Alexis Olivero
Web: www.oliverodev.com
*/

// Evitar acceso directo al archivo
if (!defined('ABSPATH')) {
    exit;
}

class SimpleHeadingHierarchy {
    
    public function __construct() {
        add_filter('the_content', array($this, 'process_headings'), 99);
    }
    
    public function process_headings($content) {
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

        $has_h1 = false;
        $current_level = 1;
        $replacements = array();

        // Procesar cada encabezado encontrado
        foreach ($matches as $match) {
            $original_tag = $match[0];         // Tag completo
            $level = (int)$match[1];          // Nivel del encabezado (1-6)
            $attributes = $match[2];          // Atributos HTML si existen
            $content_text = $match[3];        // Contenido del encabezado

            // Manejar el primer H1
            if ($level === 1 && !$has_h1) {
                $has_h1 = true;
                continue; // Mantener el primer H1 sin cambios
            }

            // Determinar el nuevo nivel para los encabezados subsiguientes
            if ($has_h1) {
                if ($level <= $current_level) {
                    $current_level++;
                }
                $new_level = min($current_level, 6);
            } else {
                $new_level = $level;
            }

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
}

// Inicializar el plugin
function initialize_simple_heading_hierarchy() {
    new SimpleHeadingHierarchy();
}
add_action('plugins_loaded', 'initialize_simple_heading_hierarchy');

// Activación del plugin
register_activation_hook(__FILE__, function() {
    // Verificar versión mínima de PHP
    if (version_compare(PHP_VERSION, '7.0.0', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('Este plugin requiere PHP 7.0 o superior.');
    }
});
?>