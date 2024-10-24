<?php
/*
Plugin Name: Auto Heading Tags by Hierarchy
Description: Asigna automáticamente etiquetas H1, H2, H3, etc. a los títulos según su jerarquía
Version: 1.7
Author: Alexis Olivero
Web: www.oliverodev.com
*/

// Evitar acceso directo al archivo
if (!defined('ABSPATH')) {
    exit;
}

class SequentialHeadingHierarchy {
    
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

        $current_level = 1;
        $last_level = 1;
        $replacements = array();

        // Procesar cada encabezado encontrado
        foreach ($matches as $match) {
            $original_tag = $match[0];         // Tag completo
            $level = (int)$match[1];          // Nivel del encabezado (1-6)
            $attributes = $match[2];          // Atributos HTML si existen
            $content_text = $match[3];        // Contenido del encabezado

            // Determinar el nuevo nivel
            if ($level === 1) {
                // Si es un H1, asignar el siguiente nivel disponible
                $new_level = $current_level;
                $current_level = min($current_level + 1, 6);
            } else {
                // Para otros niveles, mantener la jerarquía relativa
                if ($level <= $last_level) {
                    $new_level = min($current_level, 6);
                    $current_level = min($current_level + 1, 6);
                } else {
                    $new_level = min($level + $current_level - 2, 6);
                }
            }

            $last_level = $new_level;

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
function initialize_sequential_heading_hierarchy() {
    new SequentialHeadingHierarchy();
}
add_action('plugins_loaded', 'initialize_sequential_heading_hierarchy');

// Activación del plugin
register_activation_hook(__FILE__, function() {
    // Verificar versión mínima de PHP
    if (version_compare(PHP_VERSION, '7.0.0', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('Este plugin requiere PHP 7.0 o superior.');
    }
});
?>